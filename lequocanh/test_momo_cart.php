<?php
/**
 * Test file để kiểm tra MoMo payment từ giỏ hàng
 */

session_start();

// Giả lập user đăng nhập
if (!isset($_SESSION['USER'])) {
    $_SESSION['USER'] = (object)[
        'iduser' => 'test_user_123'
    ];
}

// Giả lập POST data
$_POST = [
    'payment_method' => 'momo',
    'order_code' => 'ORDER_' . time(),
    'shipping_address' => '123 Đường ABC, Quận 1, TP.HCM',
    'amount' => 50000
];

$_SERVER['REQUEST_METHOD'] = 'POST';

echo "<h2>🧪 Test MoMo Cart Payment</h2>";
echo "<p><strong>POST Data:</strong></p>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

echo "<p><strong>Session User:</strong></p>";
echo "<pre>" . print_r($_SESSION['USER'], true) . "</pre>";

echo "<hr>";
echo "<p><strong>Kết quả từ momo_payment.php:</strong></p>";

// Include file momo_payment.php
try {
    ob_start();
    include 'administrator/elements_LQA/mgiohang/momo_payment.php';
    $output = ob_get_clean();
    
    echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    echo htmlspecialchars($output);
    echo "</pre>";
    
    // Thử decode JSON
    $json = json_decode($output, true);
    if ($json) {
        echo "<p><strong>JSON Decoded:</strong></p>";
        echo "<pre>" . print_r($json, true) . "</pre>";
        
        if (isset($json['payUrl'])) {
            echo "<p><a href='" . $json['payUrl'] . "' target='_blank' class='btn btn-primary'>🚀 Mở MoMo Payment</a></p>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #ffe6e6; padding: 10px; border-radius: 5px;'>";
    echo "<strong>❌ Lỗi:</strong> " . $e->getMessage();
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
</style>
