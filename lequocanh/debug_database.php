<?php
require_once './administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Database connection: SUCCESS<br>";
    
    // Check tables
    $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found: " . implode(', ', $tables) . "<br><br>";
    
    // Check customer_notifications table
    if (in_array('customer_notifications', $tables)) {
        echo "✅ customer_notifications table EXISTS<br>";
        $count = $db->query('SELECT COUNT(*) FROM customer_notifications')->fetchColumn();
        echo "Total notifications: $count<br>";
        
        // Show structure
        $structure = $db->query('DESCRIBE customer_notifications')->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($structure as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
        }
        echo "</table>";
        
        // Show sample data
        $notifications = $db->query('SELECT * FROM customer_notifications ORDER BY created_at DESC LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($notifications)) {
            echo "<h3>Recent Notifications:</h3>";
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>User</th><th>Order</th><th>Type</th><th>Title</th><th>Is Read</th><th>Created</th></tr>";
            foreach ($notifications as $notif) {
                echo "<tr>";
                echo "<td>{$notif['id']}</td>";
                echo "<td>{$notif['user_id']}</td>";
                echo "<td>{$notif['order_id']}</td>";
                echo "<td>{$notif['type']}</td>";
                echo "<td>{$notif['title']}</td>";
                echo "<td>" . ($notif['is_read'] ? 'Yes' : 'No') . "</td>";
                echo "<td>{$notif['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "No notifications found<br>";
        }
    } else {
        echo "❌ customer_notifications table DOES NOT exist<br>";
        echo "Creating table...<br>";
        
        $createSQL = "
        CREATE TABLE customer_notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id VARCHAR(50),
            order_id INT,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            read_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_order_id (order_id),
            INDEX idx_is_read (is_read),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $db->exec($createSQL);
        echo "✅ Table created successfully<br>";
    }
    
    // Check recent orders
    echo "<h3>Recent Orders:</h3>";
    $orders = $db->query('SELECT id, ma_don_hang_text, ma_nguoi_dung, trang_thai, phuong_thuc_thanh_toan, trang_thai_thanh_toan, ngay_tao FROM don_hang ORDER BY ngay_tao DESC LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($orders)) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Code</th><th>User</th><th>Status</th><th>Payment Method</th><th>Payment Status</th><th>Created</th></tr>";
        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td>{$order['id']}</td>";
            echo "<td>{$order['ma_don_hang_text']}</td>";
            echo "<td>{$order['ma_nguoi_dung']}</td>";
            echo "<td>{$order['trang_thai']}</td>";
            echo "<td>{$order['phuong_thuc_thanh_toan']}</td>";
            echo "<td>{$order['trang_thai_thanh_toan']}</td>";
            echo "<td>{$order['ngay_tao']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No orders found<br>";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
}
?>
