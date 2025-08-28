<?php

/**
 * Script sửa lỗi thông báo và tạo trang lịch sử mua hàng
 */

// Đảm bảo đường dẫn đúng
$basePath = __DIR__ . '/administrator/elements_LQA/mod/';
require_once $basePath . 'database.php';

echo "<h1>🔧 Sửa Lỗi Thông Báo và Tạo Lịch Sử Mua Hàng</h1>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h2>1. Kiểm tra và tạo bảng thông báo</h2>";

    // 1. Tạo bảng customer_notifications nếu chưa có
    $createNotificationsTable = "
        CREATE TABLE IF NOT EXISTS customer_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(50) NOT NULL,
            order_id INT NOT NULL,
            type ENUM('order_approved', 'order_cancelled', 'order_shipped', 'order_delivered', 'payment_confirmed') NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            read_at TIMESTAMP NULL,
            
            INDEX idx_user_id (user_id),
            INDEX idx_order_id (order_id),
            INDEX idx_is_read (is_read),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    $conn->exec($createNotificationsTable);
    echo "✅ Bảng customer_notifications đã sẵn sàng<br>";

    // 2. Kiểm tra dữ liệu thông báo hiện tại
    $checkNotificationsSql = "SELECT COUNT(*) as count FROM customer_notifications";
    $checkStmt = $conn->prepare($checkNotificationsSql);
    $checkStmt->execute();
    $notificationCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];

    echo "📊 Hiện có {$notificationCount} thông báo trong hệ thống<br>";
    echo "</div>";

    echo "<div style='background: #e8f4fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h2>2. Test hệ thống thông báo</h2>";

    // 3. Test tạo thông báo cho đơn hàng đã duyệt gần đây
    require_once $basePath . 'CustomerNotificationManager.php';
    $notificationManager = new CustomerNotificationManager();

    // Lấy đơn hàng đã duyệt gần đây
    $recentOrdersSql = "SELECT id, ma_nguoi_dung, ma_don_hang_text, tong_tien 
                       FROM don_hang 
                       WHERE trang_thai = 'approved' 
                       AND ma_nguoi_dung IS NOT NULL 
                       ORDER BY ngay_cap_nhat DESC 
                       LIMIT 5";

    $recentStmt = $conn->prepare($recentOrdersSql);
    $recentStmt->execute();
    $recentOrders = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Đơn hàng đã duyệt gần đây:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Mã đơn hàng</th><th>Khách hàng</th><th>Tổng tiền</th><th>Thao tác</th></tr>";

    foreach ($recentOrders as $order) {
        echo "<tr>";
        echo "<td>{$order['id']}</td>";
        echo "<td>{$order['ma_don_hang_text']}</td>";
        echo "<td>{$order['ma_nguoi_dung']}</td>";
        echo "<td>" . number_format($order['tong_tien']) . " VND</td>";

        // Kiểm tra xem đã có thông báo chưa
        $checkNotifSql = "SELECT COUNT(*) as count FROM customer_notifications 
                         WHERE order_id = ? AND type = 'order_approved'";
        $checkNotifStmt = $conn->prepare($checkNotifSql);
        $checkNotifStmt->execute([$order['id']]);
        $hasNotification = $checkNotifStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

        if (!$hasNotification) {
            // Tạo thông báo cho đơn hàng này
            $result = $notificationManager->notifyOrderApproved($order['id'], $order['ma_nguoi_dung']);
            echo "<td>" . ($result ? "✅ Đã tạo thông báo" : "❌ Lỗi tạo thông báo") . "</td>";
        } else {
            echo "<td>✅ Đã có thông báo</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";

    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h2>3. Tạo trang lịch sử mua hàng cho khách hàng</h2>";

    // 4. Tạo file lịch sử mua hàng cho khách hàng
    $historyPageContent = '<?php
/**
 * Trang lịch sử mua hàng cho khách hàng
 */

// Kiểm tra đăng nhập
if (!isset($_SESSION[\'USER\'])) {
    header(\'Location: userLogin.php\');
    exit();
}

require_once \'administrator/elements_LQA/mod/database.php\';
require_once \'administrator/elements_LQA/mod/CustomerNotificationManager.php\';

$db = Database::getInstance();
$conn = $db->getConnection();
$username = $_SESSION[\'USER\'];

// Lấy thông tin khách hàng
$userSql = "SELECT * FROM user WHERE username = ?";
$userStmt = $conn->prepare($userSql);
$userStmt->execute([$username]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

// Lấy lịch sử đơn hàng
$ordersSql = "SELECT * FROM don_hang 
              WHERE ma_nguoi_dung = ? 
              ORDER BY ngay_tao DESC";
$ordersStmt = $conn->prepare($ordersSql);
$ordersStmt->execute([$username]);
$orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy thông báo chưa đọc
$notificationManager = new CustomerNotificationManager();
$unreadCount = $notificationManager->getUnreadCount($username);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Sử Mua Hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-history"></i> Lịch Sử Mua Hàng</h2>
                    <div>
                        <span class="badge bg-info me-2">
                            <i class="fas fa-bell"></i> <?php echo $unreadCount; ?> thông báo mới
                        </span>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-home"></i> Trang chủ
                        </a>
                    </div>
                </div>
                
                <?php if (empty($orders)): ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                        <h4>Chưa có đơn hàng nào</h4>
                        <p>Bạn chưa có đơn hàng nào. Hãy bắt đầu mua sắm ngay!</p>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-shopping-bag"></i> Mua sắm ngay
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($orders as $order): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">
                                            <i class="fas fa-receipt"></i> 
                                            <?php echo $order[\'ma_don_hang_text\']; ?>
                                        </h6>
                                        <?php
                                        $statusClass = [
                                            \'pending\' => \'warning\',
                                            \'approved\' => \'success\',
                                            \'cancelled\' => \'danger\'
                                        ];
                                        $statusText = [
                                            \'pending\' => \'Chờ xác nhận\',
                                            \'approved\' => \'Đã duyệt\',
                                            \'cancelled\' => \'Đã hủy\'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass[$order[\'trang_thai\']] ?? \'secondary\'; ?>">
                                            <?php echo $statusText[$order[\'trang_thai\']] ?? $order[\'trang_thai\']; ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="text-muted">Ngày đặt:</small><br>
                                                <strong><?php echo date(\'d/m/Y H:i\', strtotime($order[\'ngay_tao\'])); ?></strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Tổng tiền:</small><br>
                                                <strong class="text-primary">
                                                    <?php echo number_format($order[\'tong_tien\'], 0, \',\', \'.\'); ?> ₫
                                                </strong>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($order[\'phuong_thuc_thanh_toan\'])): ?>
                                            <div class="mt-2">
                                                <small class="text-muted">Phương thức thanh toán:</small><br>
                                                <?php
                                                $paymentMethods = [
                                                    \'momo\' => \'<i class="fas fa-mobile-alt"></i> MoMo\',
                                                    \'bank_transfer\' => \'<i class="fas fa-university"></i> Chuyển khoản\',
                                                    \'cod\' => \'<i class="fas fa-money-bill-wave"></i> COD\'
                                                ];
                                                echo $paymentMethods[$order[\'phuong_thuc_thanh_toan\']] ?? $order[\'phuong_thuc_thanh_toan\'];
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="mt-3">
                                            <a href="administrator/index.php?req=don_hang&action=view&id=<?php echo $order[\'id\']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> Xem chi tiết
                                            </a>
                                            
                                            <?php if ($order[\'trang_thai\'] == \'pending\'): ?>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="cancelOrder(<?php echo $order[\'id\']; ?>)">
                                                    <i class="fas fa-times"></i> Hủy đơn
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function cancelOrder(orderId) {
            if (confirm(\'Bạn có chắc chắn muốn hủy đơn hàng này?\')) {
                window.location.href = \'administrator/index.php?req=don_hang&action=cancel&id=\' + orderId;
            }
        }
    </script>
</body>
</html>';

    // Lưu file lịch sử mua hàng
    file_put_contents('lichsu_muahang.php', $historyPageContent);
    echo "✅ Đã tạo file lichsu_muahang.php<br>";
    echo "</div>";

    echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h2>4. Thống kê thông báo</h2>";

    // 5. Thống kê thông báo
    $statsSql = "SELECT 
                    cn.type,
                    COUNT(*) as count,
                    SUM(CASE WHEN cn.is_read = 0 THEN 1 ELSE 0 END) as unread_count
                 FROM customer_notifications cn
                 GROUP BY cn.type
                 ORDER BY count DESC";

    $statsStmt = $conn->prepare($statsSql);
    $statsStmt->execute();
    $stats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($stats)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Loại thông báo</th><th>Tổng số</th><th>Chưa đọc</th></tr>";

        foreach ($stats as $stat) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($stat['type']) . "</td>";
            echo "<td>" . $stat['count'] . "</td>";
            echo "<td>" . $stat['unread_count'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Chưa có thông báo nào trong hệ thống.</p>";
    }
    echo "</div>";

    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h2>✅ Hoàn tất!</h2>";
    echo "<h3>Đã thực hiện:</h3>";
    echo "<ul>";
    echo "<li>✅ Kiểm tra và tạo bảng thông báo</li>";
    echo "<li>✅ Tạo thông báo cho đơn hàng đã duyệt</li>";
    echo "<li>✅ Tạo trang lịch sử mua hàng</li>";
    echo "<li>✅ Thống kê hệ thống thông báo</li>";
    echo "</ul>";

    echo "<h3>Liên kết:</h3>";
    echo "<p><a href='lichsu_muahang.php' target='_blank'>📋 Xem trang lịch sử mua hàng</a></p>";
    echo "<p><a href='administrator/index.php?req=don_hang' target='_blank'>🛒 Quản lý đơn hàng</a></p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>❌ Lỗi</h3>";
    echo "<p style='color: red;'>Lỗi: " . $e->getMessage() . "</p>";
    echo "</div>";
    error_log("Fix notifications error: " . $e->getMessage());
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        line-height: 1.6;
    }

    h1 {
        color: #2c5aa0;
        text-align: center;
    }

    h2 {
        color: #333;
        margin-top: 0;
    }

    h3 {
        color: #555;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin: 10px 0;
    }

    th,
    td {
        padding: 8px;
        text-align: left;
        border: 1px solid #ddd;
    }

    th {
        background-color: #f2f2f2;
    }

    a {
        color: #007cba;
        text-decoration: none;
    }

    a:hover {
        text-decoration: underline;
    }

    ul {
        margin: 10px 0;
        padding-left: 20px;
    }
</style>