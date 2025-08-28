<?php
// Simple API test
require_once './administrator/elements_LQA/mod/sessionManager.php';
require_once './administrator/elements_LQA/mod/CustomerNotificationManager.php';

// Start session safely
SessionManager::start();

// Set test user
$testUser = 'khaochang';
$_SESSION['USER'] = $testUser;

echo "<h2>Simple API Test</h2>";
echo "<h3>Session USER: " . ($_SESSION['USER'] ?? 'Not set') . "</h3>";

try {
    $notificationManager = new CustomerNotificationManager();
    
    // Test getUserNotifications directly
    echo "<h3>Test getUserNotifications directly:</h3>";
    $notifications = $notificationManager->getUserNotifications($testUser, 10);
    echo "Result count: " . count($notifications) . "<br>";
    
    if (count($notifications) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Title</th><th>Message</th><th>Read</th></tr>";
        foreach ($notifications as $notif) {
            echo "<tr>";
            echo "<td>{$notif['id']}</td>";
            echo "<td>{$notif['title']}</td>";
            echo "<td>{$notif['message']}</td>";
            echo "<td>" . ($notif['is_read'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No notifications found.<br>";
    }
    
    // Test getUnreadCount
    echo "<h3>Test getUnreadCount:</h3>";
    $unreadCount = $notificationManager->getUnreadCount($testUser);
    echo "Unread count: $unreadCount<br>";
    
    // Test API endpoint manually
    echo "<h3>Manual API Test:</h3>";
    
    // Simulate API call
    $userId = $_SESSION['USER'] ?? '';
    if (!empty($userId)) {
        $notifications = $notificationManager->getUserNotifications($userId, 20);
        $unreadCount = $notificationManager->getUnreadCount($userId);
        
        $formattedNotifications = [];
        foreach ($notifications as $notification) {
            $formattedNotifications[] = [
                'id' => $notification['id'],
                'order_id' => $notification['order_id'],
                'type' => $notification['type'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'is_read' => (bool)$notification['is_read'],
                'created_at' => date('d/m/Y H:i', strtotime($notification['created_at'])),
                'icon' => 'bell',
                'color' => 'secondary'
            ];
        }
        
        $response = [
            'success' => true,
            'notifications' => $formattedNotifications,
            'unread_count' => $unreadCount,
            'total' => count($formattedNotifications)
        ];
        
        echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    } else {
        echo "No user in session<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='index.php'>Go to Index</a>";
?>
