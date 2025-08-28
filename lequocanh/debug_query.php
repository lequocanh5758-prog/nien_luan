<?php
// Debug query directly
require_once './administrator/elements_LQA/mod/database.php';

echo "<h2>Debug Query Directly</h2>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $userId = 'khachhang';
    $limit = 20;
    
    echo "<h3>Test Query Step by Step:</h3>";
    
    // 1. Test basic query
    echo "<h4>1. Basic query without LIMIT:</h4>";
    $sql1 = "SELECT * FROM customer_notifications WHERE user_id = ? ORDER BY created_at DESC";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->execute([$userId]);
    $result1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    
    echo "SQL: $sql1<br>";
    echo "Params: " . json_encode([$userId]) . "<br>";
    echo "Result count: " . count($result1) . "<br><br>";
    
    if (count($result1) > 0) {
        echo "Sample results:<br>";
        foreach (array_slice($result1, 0, 3) as $row) {
            echo "- ID: {$row['id']}, Title: {$row['title']}, Read: " . ($row['is_read'] ? 'Yes' : 'No') . "<br>";
        }
        echo "<br>";
    }
    
    // 2. Test query with LIMIT
    echo "<h4>2. Query with LIMIT:</h4>";
    $sql2 = "SELECT * FROM customer_notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->execute([$userId, $limit]);
    $result2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    echo "SQL: $sql2<br>";
    echo "Params: " . json_encode([$userId, $limit]) . "<br>";
    echo "Result count: " . count($result2) . "<br><br>";
    
    if (count($result2) > 0) {
        echo "Sample results:<br>";
        foreach (array_slice($result2, 0, 3) as $row) {
            echo "- ID: {$row['id']}, Title: {$row['title']}, Read: " . ($row['is_read'] ? 'Yes' : 'No') . "<br>";
        }
        echo "<br>";
    }
    
    // 3. Test CustomerNotificationManager directly
    echo "<h4>3. Test CustomerNotificationManager:</h4>";
    require_once './administrator/elements_LQA/mod/CustomerNotificationManager.php';
    
    $manager = new CustomerNotificationManager();
    $notifications = $manager->getUserNotifications($userId, $limit);
    $unreadCount = $manager->getUnreadCount($userId);
    
    echo "getUserNotifications count: " . count($notifications) . "<br>";
    echo "getUnreadCount: $unreadCount<br><br>";
    
    if (count($notifications) > 0) {
        echo "Sample notifications from manager:<br>";
        foreach (array_slice($notifications, 0, 3) as $notif) {
            echo "- ID: {$notif['id']}, Title: {$notif['title']}, Read: " . ($notif['is_read'] ? 'Yes' : 'No') . "<br>";
        }
        echo "<br>";
    }
    
    // 4. Test different LIMIT values
    echo "<h4>4. Test different LIMIT values:</h4>";
    foreach ([5, 10, 20, 50] as $testLimit) {
        $testNotifications = $manager->getUserNotifications($userId, $testLimit);
        echo "LIMIT $testLimit: " . count($testNotifications) . " results<br>";
    }
    
    // 5. Check data types
    echo "<h4>5. Check parameter data types:</h4>";
    echo "userId type: " . gettype($userId) . " (value: '$userId')<br>";
    echo "limit type: " . gettype($limit) . " (value: $limit)<br>";
    
    // 6. Test with explicit integer casting
    echo "<h4>6. Test with explicit integer casting:</h4>";
    $sql3 = "SELECT * FROM customer_notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
    $stmt3 = $conn->prepare($sql3);
    $stmt3->execute([$userId, (int)$limit]);
    $result3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    
    echo "With (int) cast - Result count: " . count($result3) . "<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<br><a href='set_token_clean.php'>Back to Token Test</a>";
?>
