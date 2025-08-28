<?php
// Test notification system
require_once 'administrator/elements_LQA/mod/sessionManager.php';
require_once 'administrator/elements_LQA/mod/CustomerNotificationManager.php';
require_once 'administrator/elements_LQA/mPDO.php';

// Start session
SessionManager::start();

// Test user
$testUser = 'khaochang'; // Replace with actual username

echo "<h2>Notification System Test</h2>";

// 1. Check if customer_notifications table exists
try {
    $pdo = new mPDO();
    $result = $pdo->executeS("SHOW TABLES LIKE 'customer_notifications'");
    if ($result) {
        echo "✅ Table 'customer_notifications' exists<br>";
    } else {
        echo "❌ Table 'customer_notifications' does not exist<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking table: " . $e->getMessage() . "<br>";
}

// 2. Check notifications for user
try {
    $notificationManager = new CustomerNotificationManager();

    // Get notifications
    $notifications = $notificationManager->getUserNotifications($testUser, 10);
    $unreadCount = $notificationManager->getUnreadCount($testUser);

    echo "<br><h3>Notifications for user: $testUser</h3>";
    echo "Total notifications: " . count($notifications) . "<br>";
    echo "Unread count: $unreadCount<br><br>";

    if (count($notifications) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Order ID</th><th>Type</th><th>Title</th><th>Message</th><th>Read</th><th>Created</th></tr>";
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
    } else {
        echo "No notifications found.<br>";
    }
} catch (Exception $e) {
    echo "❌ Error getting notifications: " . $e->getMessage() . "<br>";
}

// 3. Check recent orders
echo "<br><h3>Recent Orders</h3>";
try {
    $pdo = new mPDO();
    $orders = $pdo->executeS("SELECT * FROM don_hang WHERE ma_nguoi_dung = ? ORDER BY ngay_tao DESC LIMIT 5", [$testUser], true);

    if ($orders) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Order Code</th><th>Status</th><th>Payment Status</th><th>Total</th><th>Date</th></tr>";
        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td>{$order['id']}</td>";
            echo "<td>{$order['ma_don_hang_text']}</td>";
            echo "<td>{$order['trang_thai']}</td>";
            echo "<td>{$order['trang_thai_thanh_toan']}</td>";
            echo "<td>" . number_format($order['tong_tien']) . " VND</td>";
            echo "<td>{$order['ngay_dat']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No orders found.<br>";
    }
} catch (Exception $e) {
    echo "❌ Error getting orders: " . $e->getMessage() . "<br>";
}

// 4. Test creating a notification
echo "<br><h3>Test Creating Notification</h3>";
if (isset($_GET['create_test'])) {
    try {
        // Find a recent order
        $pdo = new mPDO();
        $order = $pdo->executeS("SELECT * FROM don_hang WHERE ma_nguoi_dung = ? ORDER BY ngay_tao DESC LIMIT 1", [$testUser], true);

        if ($order && count($order) > 0) {
            $orderId = $order[0]['id'];
            $success = $notificationManager->notifyPaymentConfirmed($orderId, $testUser);

            if ($success) {
                echo "✅ Test notification created successfully!<br>";
                echo "<script>setTimeout(() => location.reload(), 2000);</script>";
            } else {
                echo "❌ Failed to create test notification<br>";
            }
        } else {
            echo "❌ No orders found to create test notification<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error creating test notification: " . $e->getMessage() . "<br>";
    }
} else {
    echo '<a href="?create_test=1">Click here to create a test notification</a><br>';
}

// 5. Check API endpoint
echo "<br><h3>API Endpoint Test</h3>";
echo "API URL: /administrator/elements_LQA/mthongbao/getCustomerNotifications.php<br>";

// Set test session
$_SESSION['USER'] = $testUser;
echo "Session USER set to: " . $_SESSION['USER'] . "<br>";

echo '<br><a href="index.php">Go to Homepage</a>';
