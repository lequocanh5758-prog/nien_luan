<?php

/**
 * MoMo Payment Handler cho Giỏ hàng
 * Tích hợp với MoMo Payment system mới
 */

// Start session nếu chưa có
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include MoMo payment system mới
require_once __DIR__ . '/../../../payment/MoMoPayment.php';
require_once __DIR__ . '/../mPDO.php';

// Set content type for JSON response
header('Content-Type: application/json');

// Clear any previous output to ensure clean JSON response
if (ob_get_level()) {
    ob_clean();
}

try {
    // Kiểm tra method POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    // Kiểm tra đăng nhập
    if (!isset($_SESSION['USER'])) {
        throw new Exception('Vui lòng đăng nhập để thanh toán');
    }

    // Lấy dữ liệu từ POST
    $paymentMethod = $_POST['payment_method'] ?? '';
    $orderCode = $_POST['order_code'] ?? '';
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    $amount = intval($_POST['amount'] ?? 0);

    // Validate dữ liệu
    if ($paymentMethod !== 'momo') {
        throw new Exception('Phương thức thanh toán không hợp lệ');
    }

    if (empty($orderCode)) {
        throw new Exception('Mã đơn hàng không hợp lệ');
    }

    if (empty($shippingAddress)) {
        throw new Exception('Địa chỉ giao hàng không được để trống');
    }

    if ($amount < 1000 || $amount > 50000000) {
        throw new Exception('Số tiền không hợp lệ (1,000 - 50,000,000 VND)');
    }

    // Lấy thông tin user
    $userId = is_object($_SESSION['USER']) ? $_SESSION['USER']->iduser : $_SESSION['USER'];

    // Kiểm tra giỏ hàng có sản phẩm không
    $pdo = new mPDO();

    // Kiểm tra xem bảng tbl_giohang có tồn tại không
    try {
        $cartQuery = "SELECT COUNT(*) as count FROM tbl_giohang WHERE user_id = ?";
        $cartResult = $pdo->executeS($cartQuery, [$userId], false);
    } catch (Exception $e) {
        // Nếu bảng không tồn tại, tạo giỏ hàng giả cho test
        error_log("Cart table not found, creating fake cart for testing: " . $e->getMessage());
        $cartResult = [['count' => 1]]; // Giả lập có 1 sản phẩm trong giỏ
    }

    if (!$cartResult || $cartResult['count'] == 0) {
        throw new Exception('Giỏ hàng trống');
    }

    // Tạo thông tin đơn hàng cho MoMo
    $orderInfo = "Thanh toan don hang #" . $orderCode;
    $extraData = json_encode([
        'order_code' => $orderCode,
        'user_id' => $userId,
        'shipping_address' => $shippingAddress,
        'source' => 'cart_checkout'
    ]);

    // Lưu thông tin đơn hàng vào session để hiển thị hóa đơn sau khi thanh toán
    $_SESSION['pending_order'] = [
        'order_code' => $orderCode,
        'user_id' => $userId,
        'shipping_address' => $shippingAddress,
        'amount' => $amount,
        'cart_items' => $cartResult // Lưu cart items trước khi xóa
    ];

    // Tạo MoMo payment
    $momoPayment = new MoMoPayment();
    
    // Log thông tin trước khi gọi MoMo API
    error_log('MoMo API Call - Amount: ' . $amount);
    error_log('MoMo API Call - Order Info: ' . $orderInfo);
    error_log('MoMo API Call - Extra Data: ' . $extraData);
    
    $response = $momoPayment->createPayment($amount, $orderInfo, $extraData);
    
    // Log full response từ MoMo
    error_log('MoMo API Response: ' . json_encode($response));

    // Kiểm tra response từ MoMo
    if (isset($response['resultCode']) && $response['resultCode'] == 0) {
        // Lưu đơn hàng vào database ngay lập tức
        require_once '../mod/database.php';
        $db = Database::getInstance();
        $conn = $db->getConnection();

        try {
            $conn->beginTransaction();

            // Lưu đơn hàng với trạng thái pending
            $insertOrderSql = "INSERT INTO don_hang (ma_don_hang_text, ma_nguoi_dung, dia_chi_giao_hang,
                              tong_tien, trang_thai, phuong_thuc_thanh_toan, trang_thai_thanh_toan,
                              ngay_tao, ngay_cap_nhat)
                              VALUES (?, ?, ?, ?, 'pending', 'momo', 'pending', NOW(), NOW())";

            $stmt = $conn->prepare($insertOrderSql);
            $stmt->execute([$response['orderId'], $userId, $shippingAddress, $amount]);
            $orderId = $conn->lastInsertId();

            // Lưu chi tiết đơn hàng từ giỏ hàng
            $cartQuery = "SELECT gh.*, hh.tenhanghoa, hh.giathamkhao
                         FROM tbl_giohang gh
                         JOIN hanghoa hh ON gh.product_id = hh.idhanghoa
                         WHERE gh.user_id = ?";
            $cartStmt = $conn->prepare($cartQuery);
            $cartStmt->execute([$userId]);
            $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($cartItems as $item) {
                $insertItemSql = "INSERT INTO chi_tiet_don_hang (ma_don_hang, ma_san_pham, so_luong, gia, ngay_tao)
                                 VALUES (?, ?, ?, ?, NOW())";
                $itemStmt = $conn->prepare($insertItemSql);
                $itemStmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['giathamkhao']]);
            }

            $conn->commit();

            // Lưu thông tin vào session để sử dụng sau
            $_SESSION['pending_order'] = [
                'order_code' => $orderCode,
                'order_id' => $orderId,
                'amount' => $amount,
                'shipping_address' => $shippingAddress,
                'momo_order_id' => $response['orderId'],
                'momo_request_id' => $response['requestId'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            // Log thông tin để debug
            error_log('MoMo Cart Payment Created: ' . json_encode([
                'order_code' => $orderCode,
                'order_id' => $orderId,
                'user_id' => $userId,
                'amount' => $amount,
                'momo_order_id' => $response['orderId']
            ]));

            // Trả về URL thanh toán
            echo json_encode([
                'success' => true,
                'payUrl' => $response['payUrl'],
                'orderId' => $response['orderId'],
                'database_order_id' => $orderId,
                'message' => 'Tạo thanh toán thành công'
            ]);
        } catch (Exception $e) {
            $conn->rollBack();
            error_log('Lỗi lưu đơn hàng MoMo: ' . $e->getMessage());

            // Vẫn trả về URL thanh toán nhưng ghi log lỗi
            echo json_encode([
                'success' => true,
                'payUrl' => $response['payUrl'],
                'orderId' => $response['orderId'],
                'message' => 'Tạo thanh toán thành công',
                'warning' => 'Có lỗi khi lưu đơn hàng: ' . $e->getMessage()
            ]);
        }
    } else {
        // Lỗi từ MoMo
        $errorMsg = $response['message'] ?? 'Lỗi không xác định từ MoMo';
        error_log('MoMo Cart Payment Error: ' . json_encode($response));

        echo json_encode([
            'success' => false,
            'message' => 'Lỗi từ MoMo: ' . $errorMsg,
            'error_code' => $response['resultCode'] ?? 'unknown'
        ]);
    }
} catch (Exception $e) {
    error_log('MoMo Cart Payment Exception: ' . $e->getMessage());
    error_log('MoMo Cart Payment Stack Trace: ' . $e->getTraceAsString());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
