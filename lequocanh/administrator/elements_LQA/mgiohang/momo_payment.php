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
require_once $_SERVER['DOCUMENT_ROOT'] . '/payment/MoMoPayment.php';
require_once __DIR__ . '/../mPDO.php';

// Set content type for JSON response
header('Content-Type: application/json');

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
    $cartQuery = "SELECT COUNT(*) as count FROM tbl_giohang WHERE user_id = ?";
    $cartResult = $pdo->executeS($cartQuery, [$userId], false);

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
    $response = $momoPayment->createPayment($amount, $orderInfo, $extraData);

    // Kiểm tra response từ MoMo
    if (isset($response['resultCode']) && $response['resultCode'] == 0) {
        // Thành công - lưu thông tin tạm thời vào session
        $_SESSION['pending_order'] = [
            'order_code' => $orderCode,
            'amount' => $amount,
            'shipping_address' => $shippingAddress,
            'momo_order_id' => $response['orderId'],
            'momo_request_id' => $response['requestId'],
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Log thông tin để debug
        error_log('MoMo Cart Payment Created: ' . json_encode([
            'order_code' => $orderCode,
            'user_id' => $userId,
            'amount' => $amount,
            'momo_order_id' => $response['orderId']
        ]));

        // Trả về URL thanh toán
        echo json_encode([
            'success' => true,
            'payUrl' => $response['payUrl'],
            'orderId' => $response['orderId'],
            'message' => 'Tạo thanh toán thành công'
        ]);
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

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
