<?php
// Test creating a notification manually
require_once './administrator/elements_LQA/mod/sessionManager.php';
require_once './administrator/elements_LQA/mod/database.php';
require_once './administrator/elements_LQA/mod/CustomerNotificationManager.php';

// Start session safely
SessionManager::start();

// Check if user is logged in
if (!isset($_SESSION['USER'])) {
    die("Please login first to test notifications.");
}

$username = $_SESSION['USER'];
$db = Database::getInstance()->getConnection();

// Get the most recent order for the user
$sql = "SELECT id FROM don_hang WHERE ma_nguoi_dung = ? ORDER BY ngay_tao DESC LIMIT 1";
$stmt = $db->prepare($sql);
$stmt->execute([$username]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("No orders found for user: $username");
}

$orderId = $order['id'];

// Create notification manager
$notificationManager = new CustomerNotificationManager();

// Test creating an order approved notification
echo "Creating test notification for order ID: $orderId, User: $username<br>";

$result = $notificationManager->notifyOrderApproved($orderId, $username);

if ($result) {
    echo "<h3 style='color: green;'>✅ Notification created successfully!</h3>";
    echo "<p>Go back to <a href='index.php'>index page</a> and check the notification bell.</p>";
} else {
    echo "<h3 style='color: red;'>❌ Failed to create notification</h3>";
}

// Show all notifications for the user
echo "<hr><h3>All notifications for user: $username</h3>";

$sql = "SELECT * FROM customer_notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute([$username]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($notifications)) {
    echo "<p>No notifications found.</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Order ID</th><th>Type</th><th>Title</th><th>Message</th><th>Is Read</th><th>Created</th></tr>";
    foreach ($notifications as $notif) {
        echo "<tr>";
        echo "<td>{$notif['id']}</td>";
        echo "<td>{$notif['order_id']}</td>";
        echo "<td>{$notif['type']}</td>";
        echo "<td>{$notif['title']}</td>";
        echo "<td>{$notif['message']}</td>";
        echo "<td>" . ($notif['is_read'] ? 'Yes' : 'No') . "</td>";
        echo "<td>{$notif['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Also check if the customer_notifications table exists
echo "<hr><h3>Database Check:</h3>";
$tables = $db->query("SHOW TABLES LIKE 'customer_notifications'")->fetchAll();
if (count($tables) > 0) {
    echo "<p style='color: green;'>✅ customer_notifications table exists</p>";
    
    // Show table structure
    echo "<h4>Table structure:</h4>";
    $columns = $db->query("DESCRIBE customer_notifications")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ customer_notifications table does NOT exist!</p>";
    echo "<p>Run the setup script to create the table.</p>";
}
?>
