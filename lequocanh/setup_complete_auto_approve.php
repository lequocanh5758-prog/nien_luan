<?php
/**
 * Script thiết lập hoàn chỉnh hệ thống tự động duyệt đơn hàng
 * Bao gồm MoMo và thanh toán ngân hàng
 */

echo "<h1>🚀 Thiết Lập Hệ Thống Tự Động Duyệt Đơn Hàng</h1>";

require_once 'administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h2>✅ Bước 1: Kiểm tra và cập nhật cấu trúc database</h2>";
    
    // 1. Kiểm tra và thêm cột auto_approved
    $checkAutoApprovedSql = "SHOW COLUMNS FROM don_hang LIKE 'auto_approved'";
    $checkStmt = $conn->prepare($checkAutoApprovedSql);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        echo "➕ Thêm cột auto_approved...<br>";
        $addColumnSql = "ALTER TABLE don_hang ADD COLUMN auto_approved TINYINT(1) DEFAULT 0 AFTER trang_thai";
        $conn->exec($addColumnSql);
        echo "✅ Đã thêm cột auto_approved<br>";
    } else {
        echo "✅ Cột auto_approved đã tồn tại<br>";
    }
    
    // 2. Tạo bảng system_config
    $createConfigTableSql = "CREATE TABLE IF NOT EXISTS system_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        config_key VARCHAR(100) NOT NULL UNIQUE,
        config_value TEXT,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($createConfigTableSql);
    echo "✅ Bảng system_config đã sẵn sàng<br>";
    echo "</div>";
    
    echo "<div style='background: #e8f4fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h2>⚙️ Bước 2: Cấu hình tự động duyệt</h2>";
    
    // 3. Thiết lập cấu hình
    $configs = [
        ['key' => 'auto_approve_paid_orders', 'value' => '1', 'desc' => 'Tự động duyệt đơn hàng đã thanh toán'],
        ['key' => 'auto_approve_momo', 'value' => '1', 'desc' => 'Tự động duyệt thanh toán MoMo'],
        ['key' => 'auto_approve_bank_transfer', 'value' => '1', 'desc' => 'Tự động duyệt chuyển khoản ngân hàng'],
        ['key' => 'auto_process_interval', 'value' => '300', 'desc' => 'Khoảng thời gian xử lý tự động (giây)']
    ];
    
    foreach ($configs as $config) {
        $insertConfigSql = "INSERT INTO system_config (config_key, config_value, description) 
                           VALUES (?, ?, ?) 
                           ON DUPLICATE KEY UPDATE 
                           config_value = VALUES(config_value),
                           updated_at = NOW()";
        
        $stmt = $conn->prepare($insertConfigSql);
        $stmt->execute([$config['key'], $config['value'], $config['desc']]);
        echo "✅ {$config['key']}: {$config['value']} - {$config['desc']}<br>";
    }
    echo "</div>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h2>🧪 Bước 3: Test hệ thống</h2>";
    
    // 4. Test AutoOrderProcessor
    require_once 'administrator/elements_LQA/mod/AutoOrderProcessor.php';
    $processor = new AutoOrderProcessor();
    $result = $processor->autoApprovePaymentConfirmedOrders();
    
    if ($result['success']) {
        echo "✅ AutoOrderProcessor hoạt động: " . $result['message'] . "<br>";
    } else {
        echo "⚠️ AutoOrderProcessor: " . $result['message'] . "<br>";
    }
    echo "</div>";
    
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h2>📊 Bước 4: Thống kê đơn hàng hiện tại</h2>";
    
    // 5. Thống kê đơn hàng
    $statsSql = "SELECT 
                    trang_thai,
                    trang_thai_thanh_toan,
                    phuong_thuc_thanh_toan,
                    COUNT(*) as count,
                    SUM(tong_tien) as total_amount
                 FROM don_hang 
                 GROUP BY trang_thai, trang_thai_thanh_toan, phuong_thuc_thanh_toan
                 ORDER BY trang_thai, phuong_thuc_thanh_toan";
    
    $statsStmt = $conn->prepare($statsSql);
    $statsStmt->execute();
    $stats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Trạng thái đơn</th><th>TT Thanh toán</th><th>Phương thức</th><th>Số lượng</th><th>Tổng tiền</th></tr>";
    
    foreach ($stats as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['trang_thai']) . "</td>";
        echo "<td>" . htmlspecialchars($row['trang_thai_thanh_toan']) . "</td>";
        echo "<td>" . htmlspecialchars($row['phuong_thuc_thanh_toan']) . "</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "<td>" . number_format($row['total_amount']) . " VND</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h2>🔗 Bước 5: URLs và Webhook</h2>";
    
    $baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/lequocanh';
    
    echo "<h3>MoMo Webhook URLs:</h3>";
    echo "<p><strong>Notify URL:</strong> <code>{$baseUrl}/payment/notify.php</code></p>";
    echo "<p><strong>Return URL:</strong> <code>{$baseUrl}/payment/return.php</code></p>";
    
    echo "<h3>Bank Transfer Webhook URL:</h3>";
    echo "<p><strong>Notify URL:</strong> <code>{$baseUrl}/payment/bank_notify.php</code></p>";
    
    echo "<h3>Test URLs:</h3>";
    echo "<p><a href='{$baseUrl}/test_bank_payment.php' target='_blank'>🏦 Test Thanh Toán Ngân Hàng</a></p>";
    echo "<p><a href='{$baseUrl}/test_momo_callback.php' target='_blank'>💳 Test Thanh Toán MoMo</a></p>";
    echo "</div>";
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h2>⏰ Bước 6: Cron Job (Tùy chọn)</h2>";
    echo "<p>Để tự động xử lý đơn hàng định kỳ, thêm cron job sau:</p>";
    echo "<code>*/5 * * * * /usr/bin/php " . __DIR__ . "/administrator/elements_LQA/cron/auto_process_orders.php</code>";
    echo "<p><em>Cron job này sẽ chạy mỗi 5 phút để xử lý các đơn hàng chưa được duyệt tự động</em></p>";
    echo "</div>";
    
    echo "<div style='background: #f0f9ff; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 5px solid #0ea5e9;'>";
    echo "<h2>🎉 Thiết Lập Hoàn Tất!</h2>";
    echo "<h3>Tính năng đã được kích hoạt:</h3>";
    echo "<ul>";
    echo "<li>✅ Tự động duyệt đơn hàng thanh toán MoMo</li>";
    echo "<li>✅ Tự động duyệt đơn hàng chuyển khoản ngân hàng</li>";
    echo "<li>✅ Webhook xử lý thông báo thanh toán</li>";
    echo "<li>✅ Hệ thống thông báo khách hàng</li>";
    echo "<li>✅ Logging và theo dõi giao dịch</li>";
    echo "</ul>";
    
    echo "<h3>Cách hoạt động:</h3>";
    echo "<ol>";
    echo "<li>Khách hàng thanh toán qua MoMo hoặc chuyển khoản ngân hàng</li>";
    echo "<li>Hệ thống nhận webhook từ nhà cung cấp thanh toán</li>";
    echo "<li>Đơn hàng được tự động duyệt ngay lập tức</li>";
    echo "<li>Khách hàng nhận thông báo xác nhận</li>";
    echo "</ol>";
    
    echo "<p><strong>Kiểm tra:</strong> <a href='administrator/index.php?req=don_hang'>📋 Xem danh sách đơn hàng</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>❌ Lỗi thiết lập</h3>";
    echo "<p style='color: red;'>Lỗi: " . $e->getMessage() . "</p>";
    echo "</div>";
    error_log("Setup complete auto approve error: " . $e->getMessage());
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h1 { color: #2c5aa0; text-align: center; }
h2 { color: #333; margin-top: 0; }
h3 { color: #555; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
a { color: #007cba; text-decoration: none; }
a:hover { text-decoration: underline; }
ul, ol { margin: 10px 0; padding-left: 20px; }
</style>
