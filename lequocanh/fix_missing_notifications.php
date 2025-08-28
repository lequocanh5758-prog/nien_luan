<?php
// Fix missing notifications for existing orders
require_once './administrator/elements_LQA/mod/sessionManager.php';
require_once './administrator/elements_LQA/mod/database.php';
require_once './administrator/elements_LQA/mod/CustomerNotificationManager.php';

// Start session safely
SessionManager::start();

// Check if admin
if (!isset($_SESSION['ADMIN']) && !isset($_SESSION['USER'])) {
    die("Please login first");
}

echo "<h2>Fix Missing Notifications</h2>";

try {
    $db = Database::getInstance()->getConnection();
    $notificationManager = new CustomerNotificationManager();
    
    // Ensure customer_notifications table exists
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
    echo "✅ Notifications table ensured to exist<br><br>";
    
    // Get recent orders that might not have notifications
    $sql = "SELECT o.*, 
                   (SELECT COUNT(*) FROM customer_notifications n WHERE n.order_id = o.id) as notification_count
            FROM don_hang o 
            WHERE o.ma_nguoi_dung IS NOT NULL 
            ORDER BY o.ngay_tao DESC 
            LIMIT 20";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Recent Orders Analysis:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Order ID</th><th>Code</th><th>User</th><th>Status</th><th>Payment Method</th><th>Payment Status</th><th>Notifications</th><th>Action</th></tr>";
    
    $fixedCount = 0;
    
    foreach ($orders as $order) {
        echo "<tr>";
        echo "<td>{$order['id']}</td>";
        echo "<td>{$order['ma_don_hang_text']}</td>";
        echo "<td>{$order['ma_nguoi_dung']}</td>";
        echo "<td>{$order['trang_thai']}</td>";
        echo "<td>{$order['phuong_thuc_thanh_toan']}</td>";
        echo "<td>{$order['trang_thai_thanh_toan']}</td>";
        echo "<td>{$order['notification_count']}</td>";
        echo "<td>";
        
        if ($order['notification_count'] == 0) {
            // No notifications exist for this order - create them
            $userId = $order['ma_nguoi_dung'];
            $orderId = $order['id'];
            $orderCode = $order['ma_don_hang_text'];
            $paymentMethod = $order['phuong_thuc_thanh_toan'];
            $orderStatus = $order['trang_thai'];
            $paymentStatus = $order['trang_thai_thanh_toan'];
            
            echo "Creating notifications...";
            
            // Create order creation notification
            if ($paymentMethod == 'cod') {
                $title = "📦 Đơn hàng COD đã được tạo";
                $message = "Đơn hàng #{$orderCode} đã được tạo thành công. Bạn sẽ thanh toán khi nhận hàng.";
                $result1 = $notificationManager->createNotification($userId, $title, $message, 'order_created', $orderId);
            } elseif ($paymentMethod == 'bank_transfer') {
                $title = "🏦 Đơn hàng chờ thanh toán";
                $message = "Đơn hàng #{$orderCode} đã được tạo. Vui lòng chuyển khoản để hoàn tất đơn hàng.";
                $result1 = $notificationManager->createNotification($userId, $title, $message, 'payment_pending', $orderId);
            } else {
                $title = "📦 Đơn hàng đã được tạo";
                $message = "Đơn hàng #{$orderCode} đã được tạo thành công với phương thức thanh toán: $paymentMethod";
                $result1 = $notificationManager->createNotification($userId, $title, $message, 'order_created', $orderId);
            }
            
            // Create payment confirmation notification if paid
            if ($paymentStatus == 'completed' || $paymentStatus == 'paid') {
                $title = "💰 Thanh toán đã được xác nhận";
                $message = "Thanh toán cho đơn hàng #{$orderCode} đã được xác nhận thành công.";
                $result2 = $notificationManager->createNotification($userId, $title, $message, 'payment_confirmed', $orderId);
            }
            
            // Create order approval notification if approved
            if ($orderStatus == 'approved') {
                $title = "✅ Đơn hàng đã được duyệt";
                $message = "Đơn hàng #{$orderCode} đã được duyệt và đang được chuẩn bị giao hàng.";
                $result3 = $notificationManager->createNotification($userId, $title, $message, 'order_approved', $orderId);
            }
            
            // Create order cancellation notification if cancelled
            if ($orderStatus == 'cancelled') {
                $title = "❌ Đơn hàng đã bị hủy";
                $message = "Đơn hàng #{$orderCode} đã bị hủy. Nếu bạn đã thanh toán, chúng tôi sẽ hoàn tiền.";
                $result4 = $notificationManager->createNotification($userId, $title, $message, 'order_cancelled', $orderId);
            }
            
            echo " ✅ Fixed";
            $fixedCount++;
        } else {
            echo "Has notifications";
        }
        
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "<br><strong>Fixed notifications for $fixedCount orders</strong><br>";
    
    // Show summary of all notifications
    echo "<h3>Current Notification Summary:</h3>";
    $summarySql = "SELECT user_id, type, COUNT(*) as count FROM customer_notifications GROUP BY user_id, type ORDER BY user_id, type";
    $summaryStmt = $db->prepare($summarySql);
    $summaryStmt->execute();
    $summary = $summaryStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($summary)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>User</th><th>Type</th><th>Count</th></tr>";
        foreach ($summary as $row) {
            echo "<tr><td>{$row['user_id']}</td><td>{$row['type']}</td><td>{$row['count']}</td></tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<a href='index.php'>Go to homepage</a> | ";
echo "<a href='test_notification_simple.php'>Test notifications</a>";
?>
