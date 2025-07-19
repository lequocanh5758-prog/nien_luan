<?php
/**
 * Test MoMo Payment cho localhost
 * Mô phỏng thanh toán thành công và redirect về momo_return.php
 */

session_start();
require_once '../mPDO.php';

// Lấy thông tin từ POST
$orderCode = $_POST['order_code'] ?? '';
$amount = intval($_POST['amount'] ?? 0);
$shippingAddress = $_POST['shipping_address'] ?? '';

// Validate
if (empty($orderCode) || $amount <= 0 || empty($shippingAddress)) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin thanh toán'
    ]);
    exit;
}

// Lưu thông tin vào session
$_SESSION['pending_order'] = [
    'order_code' => $orderCode,
    'amount' => $amount,
    'shipping_address' => $shippingAddress,
    'created_at' => date('Y-m-d H:i:s')
];

// Tạo URL redirect giả lập MoMo
$redirectUrl = 'test_momo_payment_page.php?' . http_build_query([
    'orderId' => $orderCode,
    'amount' => $amount,
    'orderInfo' => 'Thanh toan don hang ' . $orderCode
]);

echo json_encode([
    'success' => true,
    'payUrl' => $redirectUrl,
    'orderId' => $orderCode,
    'message' => 'Tạo thanh toán test thành công'
]);
?>
