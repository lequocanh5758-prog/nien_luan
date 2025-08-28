<?php
/**
 * Test script để mô phỏng thanh toán ngân hàng thành công
 * Sử dụng để test tự động duyệt đơn hàng khi có thanh toán ngân hàng
 */

echo "<h2>🏦 Test Thanh Toán Ngân Hàng</h2>";

// Lấy danh sách đơn hàng pending để test
require_once 'administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Lấy đơn hàng pending với phương thức bank_transfer
    $sql = "SELECT id, ma_don_hang_text, tong_tien, trang_thai, trang_thai_thanh_toan, phuong_thuc_thanh_toan 
            FROM don_hang 
            WHERE trang_thai = 'pending' 
            AND (phuong_thuc_thanh_toan = 'bank_transfer' OR phuong_thuc_thanh_toan IS NULL)
            ORDER BY ngay_tao DESC 
            LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orders)) {
        echo "<p>⚠️ Không có đơn hàng pending nào để test. Hãy tạo đơn hàng mới trước.</p>";
        
        // Tạo đơn hàng test
        echo "<h3>Tạo đơn hàng test</h3>";
        $testOrderId = 'ORDER_BANK_' . time() . '_' . rand(1000, 9999);
        $testAmount = 50000;
        
        $insertSql = "INSERT INTO don_hang (ma_don_hang_text, tong_tien, trang_thai, phuong_thuc_thanh_toan, trang_thai_thanh_toan, ngay_tao)
                      VALUES (?, ?, 'pending', 'bank_transfer', 'pending', NOW())";
        
        $insertStmt = $conn->prepare($insertSql);
        $insertResult = $insertStmt->execute([$testOrderId, $testAmount]);
        
        if ($insertResult) {
            echo "<p>✅ Đã tạo đơn hàng test: $testOrderId</p>";
            
            // Refresh để lấy đơn hàng mới
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            echo "<p>❌ Lỗi tạo đơn hàng test</p>";
        }
    }
    
    if (!empty($orders)) {
        echo "<h3>📋 Danh sách đơn hàng có thể test:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Mã đơn hàng</th><th>Tổng tiền</th><th>Trạng thái</th><th>TT Thanh toán</th><th>Phương thức</th><th>Thao tác</th></tr>";
        
        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td>" . $order['id'] . "</td>";
            echo "<td>" . $order['ma_don_hang_text'] . "</td>";
            echo "<td>" . number_format($order['tong_tien']) . " VND</td>";
            echo "<td>" . $order['trang_thai'] . "</td>";
            echo "<td>" . $order['trang_thai_thanh_toan'] . "</td>";
            echo "<td>" . $order['phuong_thuc_thanh_toan'] . "</td>";
            echo "<td><button onclick=\"testBankPayment('{$order['ma_don_hang_text']}', {$order['tong_tien']})\">Test Thanh Toán</button></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Lỗi: " . $e->getMessage() . "</p>";
}

// Xử lý test thanh toán nếu có request
if (isset($_POST['test_payment'])) {
    $orderId = $_POST['order_id'];
    $amount = $_POST['amount'];
    $transactionId = 'BANK_' . time() . '_' . rand(100000, 999999);
    
    echo "<h3>🔄 Đang test thanh toán cho đơn hàng: $orderId</h3>";
    
    // Tạo dữ liệu thanh toán giả lập
    $paymentData = [
        'order_id' => $orderId,
        'amount' => $amount,
        'transaction_id' => $transactionId,
        'status' => 'SUCCESS',
        'bank_code' => 'MB',
        'timestamp' => time()
    ];
    
    // Gọi bank_notify.php
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . '/lequocanh/payment/bank_notify.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($paymentData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'User-Agent: BankSystem/1.0'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<h4>📤 Dữ liệu gửi:</h4>";
    echo "<pre>" . json_encode($paymentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    
    echo "<h4>📥 Response từ bank_notify.php:</h4>";
    echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    if ($httpCode == 200) {
        echo "<p style='color: green;'>✅ Thanh toán đã được xử lý thành công!</p>";
        echo "<p><a href='administrator/index.php?req=don_hang'>🔍 Kiểm tra danh sách đơn hàng</a></p>";
    } else {
        echo "<p style='color: red;'>❌ Có lỗi xảy ra khi xử lý thanh toán</p>";
    }
}
?>

<script>
function testBankPayment(orderId, amount) {
    if (confirm('Bạn có muốn test thanh toán ngân hàng cho đơn hàng ' + orderId + '?')) {
        // Tạo form và submit
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        var orderInput = document.createElement('input');
        orderInput.type = 'hidden';
        orderInput.name = 'order_id';
        orderInput.value = orderId;
        form.appendChild(orderInput);
        
        var amountInput = document.createElement('input');
        amountInput.type = 'hidden';
        amountInput.name = 'amount';
        amountInput.value = amount;
        form.appendChild(amountInput);
        
        var testInput = document.createElement('input');
        testInput.type = 'hidden';
        testInput.name = 'test_payment';
        testInput.value = '1';
        form.appendChild(testInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #2c5aa0; }
h3 { color: #5a5a5a; margin-top: 25px; }
table { margin: 10px 0; }
th { background: #f0f0f0; padding: 8px; }
td { padding: 8px; }
button { background: #007cba; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; }
button:hover { background: #005a87; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>
