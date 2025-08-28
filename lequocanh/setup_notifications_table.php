<?php
// Setup script to create customer_notifications table
require_once './administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

try {
    // Check if table exists
    $checkTable = $db->query("SHOW TABLES LIKE 'customer_notifications'");
    if ($checkTable->rowCount() == 0) {
        // Create the table
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS customer_notifications (
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
        
        $db->exec($createTableSQL);
        echo "<h3 style='color: green;'>✅ customer_notifications table created successfully!</h3>";
    } else {
        echo "<h3 style='color: blue;'>ℹ️ customer_notifications table already exists.</h3>";
    }
    
    // Show table structure
    echo "<h4>Table structure:</h4>";
    $columns = $db->query("DESCRIBE customer_notifications")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<p><a href='index.php'>Go back to homepage</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error: " . $e->getMessage() . "</h3>";
}
?>
