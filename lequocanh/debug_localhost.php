<?php
// Debug localhost session and notifications
require_once './administrator/elements_LQA/mod/sessionManager.php';
require_once './administrator/elements_LQA/mod/CustomerNotificationManager.php';

// Start session safely
SessionManager::start();

// Set user to khachhang
$_SESSION['USER'] = 'khachhang';

echo "<h2>Debug Localhost - User: khachhang</h2>";

echo "<h3>Session Info:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session USER: " . ($_SESSION['USER'] ?? 'Not set') . "<br>";
echo "Server: " . $_SERVER['HTTP_HOST'] . "<br>";

try {
    $notificationManager = new CustomerNotificationManager();
    
    // Test direct method calls
    echo "<h3>Direct Method Calls:</h3>";
    
    $notifications = $notificationManager->getUserNotifications('khachhang', 10);
    echo "getUserNotifications count: " . count($notifications) . "<br>";
    
    $unreadCount = $notificationManager->getUnreadCount('khachhang');
    echo "getUnreadCount: $unreadCount<br>";
    
    if (count($notifications) > 0) {
        echo "<h4>Sample notifications:</h4>";
        echo "<ul>";
        foreach (array_slice($notifications, 0, 3) as $notif) {
            echo "<li>{$notif['title']} - " . ($notif['is_read'] ? 'Read' : 'Unread') . "</li>";
        }
        echo "</ul>";
    }
    
    // Test API simulation
    echo "<h3>API Simulation:</h3>";
    
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
        
        echo "API Response preview:<br>";
        echo "Success: " . ($response['success'] ? 'true' : 'false') . "<br>";
        echo "Unread count: " . $response['unread_count'] . "<br>";
        echo "Total notifications: " . $response['total'] . "<br>";
        
        if ($response['total'] > 0) {
            echo "<h4>First 3 notifications:</h4>";
            echo "<ul>";
            foreach (array_slice($response['notifications'], 0, 3) as $notif) {
                echo "<li>{$notif['title']} - " . ($notif['is_read'] ? 'Read' : 'Unread') . "</li>";
            }
            echo "</ul>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>Test Links:</h3>";
echo "<a href='administrator/elements_LQA/mthongbao/getCustomerNotifications.php?action=list' target='_blank'>Test API Direct</a><br>";
echo "<a href='index.php'>Go to Index</a><br>";

// JavaScript test
echo "<h3>JavaScript Test:</h3>";
echo "<button onclick='testNotificationAPI()'>Test Notification API</button>";
echo "<div id='jsResult'></div>";

echo "<script>
function testNotificationAPI() {
    console.log('Testing notification API...');
    
    fetch('./administrator/elements_LQA/mthongbao/getCustomerNotifications.php?action=list', {
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('API Response:', data);
        document.getElementById('jsResult').innerHTML = 
            '<h4>JavaScript API Result:</h4>' +
            '<p>Success: ' + data.success + '</p>' +
            '<p>Unread count: ' + (data.unread_count || 0) + '</p>' +
            '<p>Total notifications: ' + (data.total || 0) + '</p>' +
            '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
    })
    .catch(error => {
        console.error('API Error:', error);
        document.getElementById('jsResult').innerHTML = 
            '<h4>JavaScript API Error:</h4>' +
            '<p style=\"color: red;\">' + error.message + '</p>';
    });
}
</script>";
?>
