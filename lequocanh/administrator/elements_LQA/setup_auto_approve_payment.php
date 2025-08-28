<?php
/**
 * Thiết lập tự động duyệt đơn hàng cho thanh toán MoMo và ngân hàng
 * Script này sẽ:
 * 1. Kích hoạt tự động duyệt đơn hàng đã thanh toán
 * 2. Thiết lập cron job để xử lý tự động
 * 3. Cấu hình webhook cho MoMo
 */

require_once './mod/database.php';
require_once './mod/AutoOrderProcessor.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<h2>🚀 Thiết lập tự động duyệt thanh toán</h2>";
    
    // 1. Kiểm tra và tạo cột auto_approved nếu chưa có
    echo "<h3>1. Kiểm tra cấu trúc database...</h3>";
    
    $checkColumnSql = "SHOW COLUMNS FROM don_hang LIKE 'auto_approved'";
    $checkStmt = $conn->prepare($checkColumnSql);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        echo "➕ Thêm cột auto_approved vào bảng don_hang...<br>";
        $addColumnSql = "ALTER TABLE don_hang ADD COLUMN auto_approved TINYINT(1) DEFAULT 0 AFTER trang_thai";
        $conn->exec($addColumnSql);
        echo "✅ Đã thêm cột auto_approved<br>";
    } else {
        echo "✅ Cột auto_approved đã tồn tại<br>";
    }
    
    // 2. Kiểm tra và tạo bảng cấu hình nếu chưa có
    echo "<h3>2. Thiết lập bảng cấu hình...</h3>";
    
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
    
    // 3. Thiết lập cấu hình tự động duyệt
    echo "<h3>3. Cấu hình tự động duyệt...</h3>";
    
    $configs = [
        [
            'key' => 'auto_approve_paid_orders',
            'value' => '1',
            'description' => 'Tự động duyệt đơn hàng đã thanh toán (1=bật, 0=tắt)'
        ],
        [
            'key' => 'auto_approve_momo',
            'value' => '1',
            'description' => 'Tự động duyệt thanh toán MoMo (1=bật, 0=tắt)'
        ],
        [
            'key' => 'auto_approve_bank_transfer',
            'value' => '1',
            'description' => 'Tự động duyệt chuyển khoản ngân hàng (1=bật, 0=tắt)'
        ],
        [
            'key' => 'auto_process_interval',
            'value' => '300',
            'description' => 'Khoảng thời gian xử lý tự động (giây)'
        ]
    ];
    
    foreach ($configs as $config) {
        $insertConfigSql = "INSERT INTO system_config (config_key, config_value, description) 
                           VALUES (?, ?, ?) 
                           ON DUPLICATE KEY UPDATE 
                           config_value = VALUES(config_value),
                           description = VALUES(description),
                           updated_at = NOW()";
        
        $stmt = $conn->prepare($insertConfigSql);
        $stmt->execute([$config['key'], $config['value'], $config['description']]);
        echo "✅ Cấu hình {$config['key']}: {$config['value']}<br>";
    }
    
    // 4. Test tự động duyệt
    echo "<h3>4. Test tự động duyệt...</h3>";
    
    $processor = new AutoOrderProcessor();
    $result = $processor->autoApprovePaymentConfirmedOrders();
    
    if ($result['success']) {
        echo "✅ " . $result['message'] . "<br>";
    } else {
        echo "⚠️ " . $result['message'] . "<br>";
    }
    
    // 5. Hiển thị hướng dẫn thiết lập cron job
    echo "<h3>5. Thiết lập Cron Job</h3>";
    echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Để tự động xử lý đơn hàng, thêm cron job sau vào server:</strong></p>";
    echo "<code>*/5 * * * * /usr/bin/php " . __DIR__ . "/cron/auto_process_orders.php</code><br>";
    echo "<p><em>Cron job này sẽ chạy mỗi 5 phút để tự động duyệt đơn hàng đã thanh toán</em></p>";
    echo "</div>";
    
    // 6. Hiển thị webhook URLs
    echo "<h3>6. Webhook URLs cho MoMo</h3>";
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Notify URL:</strong> " . $_SERVER['HTTP_HOST'] . "/lequocanh/payment/notify.php</p>";
    echo "<p><strong>Return URL:</strong> " . $_SERVER['HTTP_HOST'] . "/lequocanh/payment/return.php</p>";
    echo "<p><em>Cấu hình các URL này trong tài khoản MoMo của bạn</em></p>";
    echo "</div>";
    
    // 7. Hiển thị trạng thái hiện tại
    echo "<h3>7. Trạng thái đơn hàng hiện tại</h3>";
    
    $statusSql = "SELECT 
                    trang_thai,
                    trang_thai_thanh_toan,
                    phuong_thuc_thanh_toan,
                    COUNT(*) as count
                  FROM don_hang 
                  GROUP BY trang_thai, trang_thai_thanh_toan, phuong_thuc_thanh_toan
                  ORDER BY trang_thai, phuong_thuc_thanh_toan";
    
    $statusStmt = $conn->prepare($statusSql);
    $statusStmt->execute();
    $statusData = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Trạng thái đơn hàng</th><th>Trạng thái thanh toán</th><th>Phương thức</th><th>Số lượng</th></tr>";
    
    foreach ($statusData as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['trang_thai']) . "</td>";
        echo "<td>" . htmlspecialchars($row['trang_thai_thanh_toan']) . "</td>";
        echo "<td>" . htmlspecialchars($row['phuong_thuc_thanh_toan']) . "</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>✅ Thiết lập hoàn tất!</h3>";
    echo "<p>Hệ thống tự động duyệt đã được kích hoạt. Các đơn hàng thanh toán qua MoMo và chuyển khoản ngân hàng sẽ được tự động duyệt khi nhận được xác nhận thanh toán.</p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Lỗi thiết lập</h3>";
    echo "<p style='color: red;'>Lỗi: " . $e->getMessage() . "</p>";
    error_log("Setup auto approve error: " . $e->getMessage());
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #2c5aa0; }
h3 { color: #5a5a5a; margin-top: 25px; }
table { margin: 10px 0; }
th { background: #f0f0f0; padding: 8px; }
td { padding: 8px; }
code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; }
</style>
