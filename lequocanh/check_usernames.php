<?php
// Check all usernames in database
require_once './administrator/elements_LQA/mod/sessionManager.php';
require_once './administrator/elements_LQA/mod/database.php';

// Start session safely
SessionManager::start();

echo "<h2>Check All Usernames in Database</h2>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // 1. Check all users in user table
    echo "<h3>All Users in 'user' table:</h3>";
    $userStmt = $conn->query("SELECT iduser, username, hoten FROM user ORDER BY username");
    $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total users: " . count($users) . "<br><br>";
    
    if (count($users) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Actions</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['iduser']}</td>";
            echo "<td><strong>{$user['username']}</strong></td>";
            echo "<td>{$user['hoten']}</td>";
            echo "<td>";
            echo "<a href='?set_user={$user['username']}'>Set as session user</a> | ";
            echo "<a href='?check_notifications={$user['username']}'>Check notifications</a>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
    
    // 2. Check notifications by user_id
    echo "<h3>Notifications grouped by user_id:</h3>";
    $notifStmt = $conn->query("SELECT user_id, COUNT(*) as count FROM customer_notifications GROUP BY user_id ORDER BY count DESC");
    $notifUsers = $notifStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($notifUsers) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>User ID</th><th>Notification Count</th><th>Actions</th></tr>";
        foreach ($notifUsers as $notifUser) {
            echo "<tr>";
            echo "<td><strong>{$notifUser['user_id']}</strong></td>";
            echo "<td>{$notifUser['count']}</td>";
            echo "<td>";
            echo "<a href='?set_user={$notifUser['user_id']}'>Set as session user</a> | ";
            echo "<a href='?check_notifications={$notifUser['user_id']}'>View notifications</a>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "No notifications found in database.<br><br>";
    }
    
    // Handle actions
    if (isset($_GET['set_user'])) {
        $_SESSION['USER'] = $_GET['set_user'];
        echo "<div style='background: lightgreen; padding: 10px; margin: 10px 0;'>";
        echo "Session USER set to: <strong>{$_SESSION['USER']}</strong>";
        echo "</div>";
    }
    
    if (isset($_GET['check_notifications'])) {
        $checkUser = $_GET['check_notifications'];
        echo "<h3>Notifications for user: $checkUser</h3>";
        
        $checkStmt = $conn->prepare("SELECT * FROM customer_notifications WHERE user_id = ? ORDER BY created_at DESC");
        $checkStmt->execute([$checkUser]);
        $userNotifications = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
        
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
            echo "</table>";
        } else {
            echo "No notifications found for this user.";
        }
    }
    
    // Current session info
    echo "<h3>Current Session:</h3>";
    echo "Session USER: " . ($_SESSION['USER'] ?? 'Not set') . "<br>";
    echo "Session ID: " . session_id() . "<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "<br><br>";
echo "<a href='index.php'>Go to Index</a> | ";
echo "<a href='test_notifications.php'>Test Notifications</a> | ";
echo "<a href='administrator/elements_LQA/mthongbao/getCustomerNotifications.php?action=list' target='_blank'>Test API</a>";
?>
