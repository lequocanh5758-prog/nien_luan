<?php
/**
 * Auto-cancel expired pending orders
 * 
 * Hủy tự động các đơn hàng MoMo/bank_transfer pending quá lâu
 * và hoàn lại tồn kho
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/mtonkhoCls.php';

/**
 * Hủy các đơn hàng pending quá thời gian cho phép
 * 
 * @param int $expiryMinutes Số phút trước khi hủy (mặc định: 15 phút)
 * @return array Kết quả xử lý
 */
function cancelExpiredPendingOrders(int $expiryMinutes = 15): array
{
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Tìm các đơn hàng pending quá hạn
        $sql = "SELECT id, ma_don_hang_text, ma_nguoi_dung, phuong_thuc_thanh_toan, ngay_tao
                FROM don_hang 
                WHERE trang_thai = 'pending' 
                AND trang_thai_thanh_toan = 'pending'
                AND phuong_thuc_thanh_toan IN ('momo', 'bank_transfer')
                AND ngay_tao < DATE_SUB(NOW(), INTERVAL ? MINUTE)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$expiryMinutes]);
        $expiredOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($expiredOrders)) {
            return ['success' => true, 'cancelled' => 0, 'message' => 'No expired orders'];
        }
        
        $cancelledCount = 0;
        $restoredProducts = [];
        $tonkho = new MTonKho();
        
        foreach ($expiredOrders as $order) {
            $orderId = $order['id'];
            
            try {
                // Bắt đầu transaction
                if (!$conn->inTransaction()) {
                    $conn->beginTransaction();
                }
                
                // 1. Lấy chi tiết đơn hàng để hoàn kho
                $itemsSql = "SELECT ma_san_pham, so_luong FROM chi_tiet_don_hang WHERE ma_don_hang = ?";
                $itemsStmt = $conn->prepare($itemsSql);
                $itemsStmt->execute([$orderId]);
                $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // 2. Hoàn tồn kho
                foreach ($orderItems as $item) {
                    $restoreResult = $tonkho->updateSoLuong(
                        $item['ma_san_pham'], 
                        $item['so_luong'], 
                        true,  // increment
                        true   // use external transaction
                    );
                    
                    if ($restoreResult) {
                        $restoredProducts[] = [
                            'product_id' => $item['ma_san_pham'],
                            'quantity' => $item['so_luong']
                        ];
                    }
                }
                
                // 3. Cập nhật trạng thái đơn hàng
                $updateSql = "UPDATE don_hang 
                             SET trang_thai = 'cancelled', 
                                 trang_thai_thanh_toan = 'failed',
                                 ngay_cap_nhat = NOW()
                             WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([$orderId]);
                
                // Commit transaction
                if ($conn->inTransaction()) {
                    $conn->commit();
                }
                
                $cancelledCount++;
                
                error_log("Auto-cancel: Order #{$order['ma_don_hang_text']} (ID: {$orderId}) cancelled after {$expiryMinutes} minutes");
                
            } catch (Exception $e) {
                // Rollback nếu có lỗi
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                error_log("Auto-cancel error for order #{$orderId}: " . $e->getMessage());
            }
        }
        
        return [
            'success' => true,
            'cancelled' => $cancelledCount,
            'restored' => $restoredProducts,
            'message' => "Cancelled {$cancelledCount} expired orders"
        ];
        
    } catch (Exception $e) {
        error_log("cancelExpiredPendingOrders error: " . $e->getMessage());
        return [
            'success' => false,
            'cancelled' => 0,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Kiểm tra và hủy đơn hàng pending của user cụ thể
 * Dùng khi user truy cập lại trang giỏ hàng
 * 
 * @param string $userId ID người dùng
 * @param int $expiryMinutes Số phút trước khi hủy
 * @return array Kết quả xử lý
 */
function cancelUserExpiredOrders(string $userId, int $expiryMinutes = 15): array
{
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Tìm đơn hàng pending của user này
        $sql = "SELECT id, ma_don_hang_text, phuong_thuc_thanh_toan, ngay_tao
                FROM don_hang 
                WHERE ma_nguoi_dung = ?
                AND trang_thai = 'pending' 
                AND trang_thai_thanh_toan = 'pending'
                AND phuong_thuc_thanh_toan IN ('momo', 'bank_transfer')
                AND ngay_tao < DATE_SUB(NOW(), INTERVAL ? MINUTE)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$userId, $expiryMinutes]);
        $expiredOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($expiredOrders)) {
            return ['success' => true, 'cancelled' => 0];
        }
        
        $cancelledCount = 0;
        $tonkho = new MTonKho();
        
        foreach ($expiredOrders as $order) {
            $orderId = $order['id'];
            
            try {
                if (!$conn->inTransaction()) {
                    $conn->beginTransaction();
                }
                
                // Lấy chi tiết đơn hàng
                $itemsSql = "SELECT ma_san_pham, so_luong FROM chi_tiet_don_hang WHERE ma_don_hang = ?";
                $itemsStmt = $conn->prepare($itemsSql);
                $itemsStmt->execute([$orderId]);
                $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Hoàn tồn kho
                foreach ($orderItems as $item) {
                    $tonkho->updateSoLuong($item['ma_san_pham'], $item['so_luong'], true, true);
                }
                
                // Hủy đơn hàng
                $updateSql = "UPDATE don_hang 
                             SET trang_thai = 'cancelled', 
                                 trang_thai_thanh_toan = 'failed',
                                 ngay_cap_nhat = NOW()
                             WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->execute([$orderId]);
                
                if ($conn->inTransaction()) {
                    $conn->commit();
                }
                
                $cancelledCount++;
                error_log("Auto-cancel user order: #{$order['ma_don_hang_text']} for user {$userId}");
                
            } catch (Exception $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                error_log("Auto-cancel user order error #{$orderId}: " . $e->getMessage());
            }
        }
        
        return ['success' => true, 'cancelled' => $cancelledCount];
        
    } catch (Exception $e) {
        error_log("cancelUserExpiredOrders error: " . $e->getMessage());
        return ['success' => false, 'cancelled' => 0, 'message' => $e->getMessage()];
    }
}
