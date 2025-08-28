<?php
// Debug notifications database
require_once './administrator/elements_LQA/mod/sessionManager.php';
require_once './administrator/elements_LQA/mod/database.php';

// Start session safely
SessionManager::start();

echo "<h2>Debug Notifications Database</h2>";

// Set test user
$testUser = 'khaochang';
$_SESSION['USER'] = $testUser;

echo "<h3>Current Session USER: " . ($_SESSION['USER'] ?? 'Not set') . "</h3>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // 1. Check all notifications in database
    echo "<h3>All Notifications in Database:</h3>";
    $allNotifications = $conn->query("SELECT * FROM customer_notifications ORDER BY created_at DESC");
    $notifications = $allNotifications->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total notifications in database: " . count($notifications) . "<br><br>";
    
    if (count($notifications) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Order ID</th><th>Type</th><th>Title</th><th>Read</th><th>Created</th></tr>";
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
        echo "</table><br>";
    }
    
    // 2. Check notifications for specific user
    echo "<h3>Notifications for user '$testUser':</h3>";
    $userStmt = $conn->prepare("SELECT * FROM customer_notifications WHERE user_id = ? ORDER BY created_at DESC");
    $userStmt->execute([$testUser]);
    $userNotifications = $userStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Notifications for user '$testUser': " . count($userNotifications) . "<br><br>";
    
    if (count($userNotifications) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Order ID</th><th>Type</th><th>Title</th><th>Read</th><th>Created</th></tr>";
        foreach ($userNotifications as $notif) {
            echo "<tr>";
            echo "<td>{$notif['id']}</td>";
            echo "<td>{$notif['order_id']}</td>";
            echo "<td>{$notif['type']}</td>";
            echo "<td>{$notif['title']}</td>";
            echo "<td>" . ($notif['is_read'] ? 'Yes' : 'No') . "</td>";
            echo "<td>{$notif['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
    
    // 3. Count unread notifications
    $unreadStmt = $conn->prepare("SELECT COUNT(*) as count FROM customer_notifications WHERE user_id = ? AND is_read = 0");
    $unreadStmt->execute([$testUser]);
    $unreadCount = $unreadStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<h3>Unread count for user '$testUser': $unreadCount</h3>";
    
    // 4. Test the API directly
    echo "<h3>Test API Response:</h3>";
    echo "<a href='./administrator/elements_LQA/mthongbao/getCustomerNotifications.php?action=list' target='_blank'>Test API</a><br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "<br><a href='index.php'>Go to Index</a>";
?>
