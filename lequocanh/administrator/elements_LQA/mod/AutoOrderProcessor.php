<?php

/**
 * Auto Order Processor
 * Xử lý tự động đơn hàng
 */

require_once 'database.php';
require_once 'CustomerNotificationManager.php';

class AutoOrderProcessor
{
    private $db;
    private $notificationManager;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->notificationManager = new CustomerNotificationManager();
    }

    /**
     * Tự động duyệt đơn hàng đã thanh toán
     */
    public function autoApprovePaymentConfirmedOrders()
    {
        try {
            // Lấy cấu hình
            $autoApproveEnabled = $this->getConfig('auto_approve_paid_orders', '1');

            if ($autoApproveEnabled !== '1') {
                return ['success' => false, 'message' => 'Tự động duyệt đã bị tắt'];
            }

            // Tìm các đơn hàng đã thanh toán nhưng chưa được duyệt
            // Bao gồm cả 'paid' và 'completed' để tương thích
            $sql = "SELECT id, ma_nguoi_dung, ma_don_hang_text, tong_tien
                    FROM don_hang
                    WHERE trang_thai = 'pending'
                    AND (trang_thai_thanh_toan = 'completed' OR trang_thai_thanh_toan = 'paid')
                    AND phuong_thuc_thanh_toan != 'cod'
                    AND (auto_approved = 0 OR auto_approved IS NULL)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $approvedCount = 0;

            foreach ($orders as $order) {
                if ($this->approveOrder($order['id'], true)) {
                    // Gửi thông báo cho khách hàng
                    $this->notificationManager->notifyOrderApproved($order['id'], $order['ma_nguoi_dung']);
                    $this->notificationManager->notifyPaymentConfirmed($order['id'], $order['ma_nguoi_dung']);

                    $approvedCount++;

                    // Log
                    error_log("Auto approved order #{$order['id']} for user {$order['ma_nguoi_dung']}");
                }
            }

            return [
                'success' => true,
                'message' => "Đã tự động duyệt {$approvedCount} đơn hàng",
                'approved_count' => $approvedCount
            ];
        } catch (Exception $e) {
            error_log("Error in autoApprovePaymentConfirmedOrders: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Duyệt đơn hàng cụ thể (public method)
     */
    public function approveSpecificOrder($orderId, $isAutoApproved = false)
    {
        try {
            // Kiểm tra đơn hàng có tồn tại và đang ở trạng thái pending không
            $checkSql = "SELECT id, trang_thai, trang_thai_thanh_toan, phuong_thuc_thanh_toan
                        FROM don_hang WHERE id = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$orderId]);
            $order = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                return ['success' => false, 'message' => 'Đơn hàng không tồn tại'];
            }

            if ($order['trang_thai'] !== 'pending') {
                return ['success' => false, 'message' => 'Đơn hàng đã được xử lý'];
            }

            // Duyệt đơn hàng
            if ($this->approveOrder($orderId, $isAutoApproved)) {
                return [
                    'success' => true,
                    'message' => 'Đơn hàng đã được duyệt thành công',
                    'order_id' => $orderId
                ];
            } else {
                return ['success' => false, 'message' => 'Lỗi khi duyệt đơn hàng'];
            }
        } catch (Exception $e) {
            error_log("Error in approveSpecificOrder: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Duyệt đơn hàng (private method)
     */
    private function approveOrder($orderId, $isAutoApproved = false)
    {
        try {
            $this->db->beginTransaction();

            // Cập nhật trạng thái đơn hàng
            $sql = "UPDATE don_hang
                    SET trang_thai = 'approved',
                        auto_approved = ?,
                        ngay_cap_nhat = NOW()
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$isAutoApproved ? 1 : 0, $orderId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error approving order {$orderId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Xử lý đơn hàng hết hạn hủy
     */
    public function processExpiredCancelDeadlines()
    {
        try {
            // Tìm các đơn hàng COD đã hết hạn hủy và chưa được duyệt
            $sql = "SELECT id, ma_nguoi_dung 
                    FROM don_hang 
                    WHERE trang_thai = 'pending' 
                    AND phuong_thuc_thanh_toan = 'cod'
                    AND cancel_deadline IS NOT NULL 
                    AND cancel_deadline < NOW()";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $processedCount = 0;

            foreach ($orders as $order) {
                // Xóa deadline để không thể hủy nữa
                $updateSql = "UPDATE don_hang SET cancel_deadline = NULL WHERE id = ?";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute([$order['id']]);

                $processedCount++;
            }

            return [
                'success' => true,
                'message' => "Đã xử lý {$processedCount} đơn hàng hết hạn hủy",
                'processed_count' => $processedCount
            ];
        } catch (Exception $e) {
            error_log("Error in processExpiredCancelDeadlines: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Kiểm tra và xử lý đơn hàng cần duyệt thủ công
     */
    public function getOrdersRequiringManualApproval()
    {
        try {
            // Lấy các đơn COD hoặc đơn chưa thanh toán cần duyệt thủ công
            $sql = "SELECT id, ma_don_hang_text, ma_nguoi_dung, tong_tien, 
                           phuong_thuc_thanh_toan, trang_thai_thanh_toan, ngay_tao,
                           cancel_deadline
                    FROM don_hang 
                    WHERE trang_thai = 'pending' 
                    AND (
                        phuong_thuc_thanh_toan = 'cod' 
                        OR trang_thai_thanh_toan = 'pending'
                    )
                    ORDER BY ngay_tao ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Phân loại đơn hàng
            $codOrders = [];
            $pendingPaymentOrders = [];

            foreach ($orders as $order) {
                if ($order['phuong_thuc_thanh_toan'] === 'cod') {
                    $codOrders[] = $order;
                } else {
                    $pendingPaymentOrders[] = $order;
                }
            }

            return [
                'success' => true,
                'cod_orders' => $codOrders,
                'pending_payment_orders' => $pendingPaymentOrders,
                'total_count' => count($orders)
            ];
        } catch (Exception $e) {
            error_log("Error in getOrdersRequiringManualApproval: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Lấy cấu hình
     */
    private function getConfig($key, $default = '')
    {
        try {
            $sql = "SELECT config_value FROM order_auto_config WHERE config_key = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? $result['config_value'] : $default;
        } catch (Exception $e) {
            return $default;
        }
    }

    /**
     * Cập nhật cấu hình
     */
    public function updateConfig($key, $value, $description = '')
    {
        try {
            $sql = "INSERT INTO order_auto_config (config_key, config_value, description) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    config_value = VALUES(config_value),
                    description = VALUES(description)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$key, $value, $description]);
        } catch (Exception $e) {
            error_log("Error updating config: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lấy thống kê đơn hàng
     */
    public function getOrderStats()
    {
        try {
            $stats = [];

            // Tổng đơn hàng pending
            $sql = "SELECT COUNT(*) as count FROM don_hang WHERE trang_thai = 'pending'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['pending_total'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Đơn COD cần duyệt
            $sql = "SELECT COUNT(*) as count FROM don_hang 
                    WHERE trang_thai = 'pending' AND phuong_thuc_thanh_toan = 'cod'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['cod_pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Đơn đã thanh toán chờ duyệt
            $sql = "SELECT COUNT(*) as count FROM don_hang 
                    WHERE trang_thai = 'pending' AND trang_thai_thanh_toan = 'completed'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['paid_pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Đơn tự động duyệt hôm nay
            $sql = "SELECT COUNT(*) as count FROM don_hang 
                    WHERE auto_approved = 1 AND DATE(ngay_cap_nhat) = CURDATE()";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['auto_approved_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            return ['success' => true, 'stats' => $stats];
        } catch (Exception $e) {
            error_log("Error getting order stats: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
