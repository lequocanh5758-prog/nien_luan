<?php
/**
 * Setup Customer Notifications System
 * Tạo bảng thông báo cho khách hàng và hệ thống hủy đơn
 */

require_once 'mod/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<h2>🔔 Thiết lập hệ thống thông báo khách hàng</h2>\n";
    
    // 1. Tạo bảng thông báo khách hàng
    $createNotificationsTable = "
        CREATE TABLE IF NOT EXISTS customer_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(50) NOT NULL,
            order_id INT NOT NULL,
            type ENUM('order_approved', 'order_cancelled', 'order_shipped', 'order_delivered', 'payment_confirmed') NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            read_at TIMESTAMP NULL,
            
            INDEX idx_user_id (user_id),
            INDEX idx_order_id (order_id),
            INDEX idx_is_read (is_read),
            INDEX idx_created_at (created_at),
            
            FOREIGN KEY (order_id) REFERENCES don_hang(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $conn->exec($createNotificationsTable);
    echo "✅ Đã tạo bảng customer_notifications\n";
    
    // 2. Tạo bảng lý do hủy đơn
    $createCancelReasonsTable = "
        CREATE TABLE IF NOT EXISTS order_cancel_reasons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            user_id VARCHAR(50) NOT NULL,
            reason_code VARCHAR(50) NOT NULL,
            reason_text VARCHAR(255) NOT NULL,
            custom_reason TEXT NULL,
            cancelled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            INDEX idx_order_id (order_id),
            INDEX idx_user_id (user_id),
            INDEX idx_reason_code (reason_code),
            
            FOREIGN KEY (order_id) REFERENCES don_hang(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $conn->exec($createCancelReasonsTable);
    echo "✅ Đã tạo bảng order_cancel_reasons\n";
    
    // 3. Thêm cột cancel_deadline vào bảng don_hang
    $addCancelDeadlineColumn = "
        ALTER TABLE don_hang 
        ADD COLUMN IF NOT EXISTS cancel_deadline TIMESTAMP NULL AFTER ngay_cap_nhat,
        ADD COLUMN IF NOT EXISTS auto_approved TINYINT(1) DEFAULT 0 AFTER cancel_deadline
    ";
    
    try {
        $conn->exec($addCancelDeadlineColumn);
        echo "✅ Đã thêm cột cancel_deadline và auto_approved\n";
    } catch (Exception $e) {
        echo "⚠️ Cột có thể đã tồn tại: " . $e->getMessage() . "\n";
    }
    
    // 4. Tạo bảng cấu hình tự động
    $createAutoConfigTable = "
        CREATE TABLE IF NOT EXISTS order_auto_config (
            id INT AUTO_INCREMENT PRIMARY KEY,
            config_key VARCHAR(100) NOT NULL UNIQUE,
            config_value TEXT NOT NULL,
            description TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $conn->exec($createAutoConfigTable);
    echo "✅ Đã tạo bảng order_auto_config\n";
    
    // 5. Thêm cấu hình mặc định
    $defaultConfigs = [
        ['cancel_time_limit', '15', 'Thời gian cho phép hủy đơn (phút)'],
        ['auto_approve_paid_orders', '1', 'Tự động duyệt đơn hàng đã thanh toán (1=có, 0=không)'],
        ['manual_approve_cod', '1', 'Duyệt thủ công đơn COD (1=có, 0=không)'],
        ['notification_enabled', '1', 'Bật thông báo cho khách hàng (1=có, 0=không)']
    ];
    
    foreach ($defaultConfigs as $config) {
        $insertConfig = "
            INSERT INTO order_auto_config (config_key, config_value, description) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            config_value = VALUES(config_value),
            description = VALUES(description)
        ";
        $stmt = $conn->prepare($insertConfig);
        $stmt->execute($config);
    }
    echo "✅ Đã thêm cấu hình mặc định\n";
    
    // 6. Tạo trigger tự động set cancel_deadline
    $createTrigger = "
        CREATE TRIGGER IF NOT EXISTS set_cancel_deadline 
        BEFORE INSERT ON don_hang
        FOR EACH ROW
        BEGIN
            IF NEW.phuong_thuc_thanh_toan = 'cod' THEN
                SET NEW.cancel_deadline = DATE_ADD(NOW(), INTERVAL 15 MINUTE);
            ELSEIF NEW.trang_thai_thanh_toan = 'pending' THEN
                SET NEW.cancel_deadline = DATE_ADD(NOW(), INTERVAL 15 MINUTE);
            END IF;
        END
    ";
    
    try {
        $conn->exec($createTrigger);
        echo "✅ Đã tạo trigger set_cancel_deadline\n";
    } catch (Exception $e) {
        echo "⚠️ Trigger có thể đã tồn tại: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 <strong>Hoàn thành thiết lập hệ thống thông báo!</strong>\n";
    echo "\n📋 <strong>Các tính năng đã được thêm:</strong>\n";
    echo "• Thông báo cho khách hàng khi đơn hàng được duyệt/hủy\n";
    echo "• Cho phép hủy đơn trong 15 phút với lý do\n";
    echo "• Tự động duyệt đơn hàng đã thanh toán\n";
    echo "• Duyệt thủ công chỉ cho đơn COD\n";
    echo "• Cấu hình linh hoạt cho admin\n";
    
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}
?>
