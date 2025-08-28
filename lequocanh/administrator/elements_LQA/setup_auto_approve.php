<?php

/**
 * Setup Auto Approve System
 * Thiết lập hệ thống tự động duyệt đơn hàng
 */

require_once 'mod/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    echo "<h2>🔧 Thiết lập hệ thống tự động duyệt</h2>";

    // 1. Thêm cột auto_approved nếu chưa có
    echo "<h3>1. Kiểm tra cột auto_approved...</h3>";

    $checkAutoApprovedSql = "SHOW COLUMNS FROM don_hang LIKE 'auto_approved'";
    $stmt = $conn->prepare($checkAutoApprovedSql);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        echo "Thêm cột auto_approved...<br>";
        $addAutoApprovedSql = "ALTER TABLE don_hang ADD COLUMN auto_approved TINYINT(1) DEFAULT 0";
        $conn->exec($addAutoApprovedSql);
        echo "✅ Đã thêm cột auto_approved<br>";
    } else {
        echo "✅ Cột auto_approved đã tồn tại<br>";
    }

    // 2. Thêm cột cancel_deadline nếu chưa có
    echo "<h3>2. Kiểm tra cột cancel_deadline...</h3>";

    $checkCancelDeadlineSql = "SHOW COLUMNS FROM don_hang LIKE 'cancel_deadline'";
    $stmt = $conn->prepare($checkCancelDeadlineSql);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        echo "Thêm cột cancel_deadline...<br>";
        $addCancelDeadlineSql = "ALTER TABLE don_hang ADD COLUMN cancel_deadline TIMESTAMP NULL";
        $conn->exec($addCancelDeadlineSql);
        echo "✅ Đã thêm cột cancel_deadline<br>";
    } else {
        echo "✅ Cột cancel_deadline đã tồn tại<br>";
    }

    // 3. Cập nhật enum cho trang_thai_thanh_toan
    echo "<h3>3. Cập nhật trạng thái thanh toán...</h3>";

    $updatePaymentStatusSql = "ALTER TABLE don_hang MODIFY COLUMN trang_thai_thanh_toan
                              ENUM('pending', 'paid', 'completed', 'failed') DEFAULT 'pending'";
    $conn->exec($updatePaymentStatusSql);
    echo "✅ Đã cập nhật enum trạng thái thanh toán<br>";

    // 3. Tạo bảng cấu hình nếu chưa có
    echo "<h3>3. Tạo bảng cấu hình...</h3>";

    $createConfigTableSql = "CREATE TABLE IF NOT EXISTS system_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        config_key VARCHAR(100) NOT NULL UNIQUE,
        config_value TEXT,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $conn->exec($createConfigTableSql);
    echo "✅ Đã tạo bảng system_config<br>";

    // 4. Thêm cấu hình mặc định
    echo "<h3>4. Thêm cấu hình mặc định...</h3>";

    $configs = [
        ['auto_approve_paid_orders', '1', 'Tự động duyệt đơn hàng đã thanh toán (1=bật, 0=tắt)'],
        ['auto_approve_momo', '1', 'Tự động duyệt đơn hàng MoMo (1=bật, 0=tắt)'],
        ['auto_approve_bank_transfer', '1', 'Tự động duyệt đơn hàng chuyển khoản (1=bật, 0=tắt)'],
        ['manual_approve_cod', '1', 'Duyệt thủ công đơn hàng COD (1=bật, 0=tắt)']
    ];

    foreach ($configs as $config) {
        $insertConfigSql = "INSERT INTO system_config (config_key, config_value, description) 
                           VALUES (?, ?, ?) 
                           ON DUPLICATE KEY UPDATE 
                           config_value = VALUES(config_value),
                           description = VALUES(description)";

        $stmt = $conn->prepare($insertConfigSql);
        $stmt->execute($config);
        echo "✅ Đã thêm cấu hình: {$config[0]}<br>";
    }

    // 5. Test auto approve system
    echo "<h3>5. Test hệ thống tự động duyệt...</h3>";

    require_once 'mod/AutoOrderProcessor.php';
    $processor = new AutoOrderProcessor();

    $result = $processor->autoApprovePaymentConfirmedOrders();
    if ($result['success']) {
        echo "✅ " . $result['message'] . "<br>";
    } else {
        echo "❌ " . $result['message'] . "<br>";
    }

    echo "<h3>✅ Hoàn thành thiết lập!</h3>";
    echo "<p><strong>Hệ thống tự động duyệt đã được cấu hình:</strong></p>";
    echo "<ul>";
    echo "<li>✅ MoMo: Tự động duyệt khi thanh toán thành công</li>";
    echo "<li>✅ Chuyển khoản: Tự động duyệt khi admin xác nhận</li>";
    echo "<li>⚠️ COD: Cần duyệt thủ công</li>";
    echo "</ul>";

    echo "<p><a href='test_auto_process.php' class='btn btn-primary'>Test Auto Process</a></p>";
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Lỗi: " . $e->getMessage() . "</div>";
    error_log("Setup Auto Approve Error: " . $e->getMessage());
}
