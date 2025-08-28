<?php
// Test customer notifications
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

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Customer Notifications</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-5'>
    <h2>Test Customer Notifications</h2>
    <p>Current User: <strong>$username</strong></p>
    <hr>";

try {
    // Check customer_notifications table
    echo "<h3>1. Customer Notifications Table:</h3>";
    $sql = "SELECT * FROM customer_notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
    $stmt = $db->prepare($sql);
    $stmt->execute([$username]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($notifications)) {
        echo "<div class='alert alert-warning'>No notifications found for user: $username</div>";
    } else {
        echo "<table class='table table-striped'>";
        echo "<thead><tr><th>ID</th><th>Order ID</th><th>Type</th><th>Title</th><th>Message</th><th>Is Read</th><th>Created</th></tr></thead>";
        echo "<tbody>";
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
        echo "</tbody></table>";
    }
    
    // Check recent orders
    echo "<h3>2. Recent Orders for User:</h3>";
    $sql = "SELECT id, ma_don_hang_text, ma_nguoi_dung, trang_thai, tong_tien, ngay_tao 
            FROM don_hang 
            WHERE ma_nguoi_dung = ? 
            ORDER BY ngay_tao DESC 
            LIMIT 10";
    $stmt = $db->prepare($sql);
    $stmt->execute([$username]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orders)) {
        echo "<div class='alert alert-warning'>No orders found for user: $username</div>";
    } else {
        echo "<table class='table table-striped'>";
        echo "<thead><tr><th>ID</th><th>Order Code</th><th>Username</th><th>Status</th><th>Total</th><th>Created</th></tr></thead>";
        echo "<tbody>";
        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td>{$order['id']}</td>";
            echo "<td>{$order['ma_don_hang_text']}</td>";
            echo "<td>{$order['ma_nguoi_dung']}</td>";
            echo "<td>{$order['trang_thai']}</td>";
            echo "<td>" . number_format($order['tong_tien'], 0, ',', '.') . " đ</td>";
            echo "<td>{$order['ngay_tao']}</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    }
    
    // Test creating a notification
    echo "<h3>3. Test Create Notification:</h3>";
    if (isset($_GET['test_create'])) {
        $notificationManager = new CustomerNotificationManager();
        $result = $notificationManager->createNotification(
            $username,
            'Test Notification',
            'This is a test notification created at ' . date('Y-m-d H:i:s'),
            'test',
            null
        );
        
        if ($result) {
            echo "<div class='alert alert-success'>Test notification created successfully!</div>";
        } else {
            echo "<div class='alert alert-danger'>Failed to create test notification</div>";
        }
    } else {
        echo "<a href='?test_create=1' class='btn btn-primary'>Create Test Notification</a>";
    }
    
    // Check if notification endpoints are accessible
    echo "<h3>4. Test API Endpoints:</h3>";
    echo "<p>Testing notification API endpoint...</p>";
    
    // Use cURL to test the API
    $apiUrl = 'http://localhost/lequocanh/administrator/elements_LQA/mthongbao/getCustomerNotifications.php?action=count';
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['success'])) {
            echo "<div class='alert alert-success'>API is working! Unread count: " . ($data['unread_count'] ?? 0) . "</div>";
        } else {
            echo "<div class='alert alert-warning'>API returned unexpected response: " . htmlspecialchars($response) . "</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>API returned HTTP code: $httpCode</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}

echo "</div></body></html>";
?>
