<?php
// Simple notification test
require_once './administrator/elements_LQA/mod/sessionManager.php';
require_once './administrator/elements_LQA/mod/database.php';

// Start session safely
SessionManager::start();

if (!isset($_SESSION['USER'])) {
    die("Please login first");
}

$username = $_SESSION['USER'];
echo "<h2>Testing Notifications for User: $username</h2>";

try {
    $db = Database::getInstance()->getConnection();
    
    // First ensure customer_notifications table exists
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
    echo "✅ Table ensured to exist<br>";
    
    // Create a test notification manually
    $sql = "INSERT INTO customer_notifications (user_id, order_id, type, title, message, is_read, created_at) 
            VALUES (?, ?, ?, ?, ?, 0, NOW())";
    $stmt = $db->prepare($sql);
    
    $testResult = $stmt->execute([
        $username,
        999, // test order ID
        'test',
        '🧪 Test Notification',
        'This is a test notification created at ' . date('Y-m-d H:i:s') . '. If you see this, the notification system is working!'
    ]);
    
    if ($testResult) {
        echo "✅ Test notification created successfully!<br>";
        
        // Check if it was inserted
        $checkSql = "SELECT * FROM customer_notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([$username]);
        $notification = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($notification) {
            echo "✅ Notification verified in database:<br>";
            echo "<pre>" . print_r($notification, true) . "</pre>";
        } else {
            echo "❌ Notification not found in database<br>";
        }
        
        // Test the notification manager
        require_once './administrator/elements_LQA/mod/CustomerNotificationManager.php';
        $notificationManager = new CustomerNotificationManager();
        
        echo "<h3>Testing CustomerNotificationManager</h3>";
        
        $managerResult = $notificationManager->createNotification(
            $username,
            '🎯 Manager Test',
            'This notification was created using CustomerNotificationManager at ' . date('Y-m-d H:i:s'),
            'manager_test',
            888
        );
        
        echo "Manager test result: " . ($managerResult ? "✅ SUCCESS" : "❌ FAILED") . "<br>";
        
        // Get unread count
        $unreadCount = $notificationManager->getUnreadCount($username);
        echo "Unread notifications: $unreadCount<br>";
        
        // Get all notifications
        $allNotifications = $notificationManager->getUserNotifications($username, 10);
        echo "Total notifications retrieved: " . count($allNotifications) . "<br>";
        
        if (!empty($allNotifications)) {
            echo "<h3>Recent Notifications:</h3>";
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Title</th><th>Type</th><th>Read</th><th>Created</th></tr>";
            foreach ($allNotifications as $notif) {
                echo "<tr>";
                echo "<td>{$notif['id']}</td>";
                echo "<td>{$notif['title']}</td>";
                echo "<td>{$notif['type']}</td>";
                echo "<td>" . ($notif['is_read'] ? 'Yes' : 'No') . "</td>";
                echo "<td>{$notif['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "❌ Failed to create test notification<br>";
    }
    
    echo "<hr>";
    echo "<h3>Now test the notification API:</h3>";
    echo "<button onclick='testAPI()'>Test API</button>";
    echo "<div id='apiResult'></div>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<a href='index.php'>Go back to homepage and check notification bell</a>";
?>

<script>
function testAPI() {
    const resultDiv = document.getElementById('apiResult');
    resultDiv.innerHTML = 'Testing API...';
    
    const baseUrl = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
    const apiUrl = baseUrl + '/administrator/elements_LQA/mthongbao/getCustomerNotifications.php?action=list';
    
    fetch(apiUrl, {
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = `
                <h4>✅ API Test Successful!</h4>
                <p>Unread Count: ${data.unread_count}</p>
                <p>Total Notifications: ${data.total}</p>
                <pre>${JSON.stringify(data, null, 2)}</pre>
            `;
        } else {
            resultDiv.innerHTML = `
                <h4>❌ API Test Failed!</h4>
                <p>Error: ${data.error}</p>
            `;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `
            <h4>❌ API Test Error!</h4>
            <p>${error.message}</p>
        `;
    });
}
</script>
