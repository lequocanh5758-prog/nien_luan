<?php
// Use SessionManager for safe session handling
require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

// Start session safely
SessionManager::start();
require_once '../mod/database.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['ADMIN'])) {
    header('Location: ../../userLogin.php');
    exit();
}

// Thêm CSS để trang trông đẹp hơn
echo '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Thông Báo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .alert {
            margin-bottom: 10px;
        }
        .btn-back {
            margin-top: 20px;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        .status-pending {
            background-color: #ffc107;
        }
        .status-approved {
            background-color: #28a745;
        }
        .status-cancelled {
            background-color: #dc3545;
        }
        .read-status {
            font-weight: bold;
        }
        .read-1 {
            color: #28a745;
        }
        .read-0 {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Debug Thông Báo</h2>';

// Kết nối database
$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Kiểm tra xem bảng orders có tồn tại không
    $checkTableSql = "SHOW TABLES LIKE 'orders'";
    $checkTableStmt = $conn->prepare($checkTableSql);
    $checkTableStmt->execute();
    
    if ($checkTableStmt->rowCount() == 0) {
        echo "<div class='alert alert-danger'>Bảng orders không tồn tại.</div>";
    } else {
        // Kiểm tra cấu trúc bảng orders
        $columnsQuery = "SHOW COLUMNS FROM orders";
        $columnsStmt = $conn->prepare($columnsQuery);
        $columnsStmt->execute();
        $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h3>Cấu trúc bảng orders</h3>";
        echo "<div class='table-responsive'>";
        echo "<table class='table table-striped table-bordered'>";
        echo "<thead><tr><th>Tên cột</th></tr></thead>";
        echo "<tbody>";
        foreach ($columns as $column) {
            echo "<tr><td>$column</td></tr>";
        }
        echo "</tbody></table></div>";
        
        // Kiểm tra xem các cột thông báo có tồn tại không
        $notificationColumns = ['pending_read', 'approved_read', 'cancelled_read'];
        $missingColumns = [];
        
        foreach ($notificationColumns as $column) {
            if (!in_array($column, $columns)) {
                $missingColumns[] = $column;
            }
        }
        
        if (!empty($missingColumns)) {
            echo "<div class='alert alert-warning'>Các cột thông báo sau đang bị thiếu: " . implode(', ', $missingColumns) . "</div>";
            
            // Thêm nút để thêm các cột thiếu
            echo "<form method='post' action='updateOrdersTable.php'>";
            echo "<button type='submit' class='btn btn-primary'>Thêm các cột thiếu</button>";
            echo "</form>";
        } else {
            echo "<div class='alert alert-success'>Tất cả các cột thông báo đã tồn tại.</div>";
        }
        
        // Hiển thị dữ liệu trong bảng orders
        $ordersSql = "SELECT id, order_code, user_id, status, created_at, updated_at, pending_read, approved_read, cancelled_read FROM orders ORDER BY id DESC LIMIT 20";
        $ordersStmt = $conn->prepare($ordersSql);
        $ordersStmt->execute();
        $orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($orders) > 0) {
            echo "<h3>Dữ liệu trong bảng orders (20 bản ghi mới nhất)</h3>";
            echo "<div class='table-responsive'>";
            echo "<table class='table table-striped table-bordered'>";
            echo "<thead><tr>
                <th>ID</th>
                <th>Mã đơn hàng</th>
                <th>Người dùng</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
                <th>Cập nhật</th>
                <th>pending_read</th>
                <th>approved_read</th>
                <th>cancelled_read</th>
                <th>Hành động</th>
            </tr></thead>";
            echo "<tbody>";
            
            foreach ($orders as $order) {
                $statusClass = '';
                switch ($order['status']) {
                    case 'pending':
                        $statusClass = 'status-pending';
                        break;
                    case 'approved':
                        $statusClass = 'status-approved';
                        break;
                    case 'cancelled':
                        $statusClass = 'status-cancelled';
                        break;
                }
                
                echo "<tr>";
                echo "<td>{$order['id']}</td>";
                echo "<td>{$order['order_code']}</td>";
                echo "<td>{$order['user_id']}</td>";
                echo "<td><span class='status-badge $statusClass'>{$order['status']}</span></td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($order['created_at'])) . "</td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($order['updated_at'])) . "</td>";
                echo "<td class='read-status read-{$order['pending_read']}'>{$order['pending_read']}</td>";
                echo "<td class='read-status read-{$order['approved_read']}'>{$order['approved_read']}</td>";
                echo "<td class='read-status read-{$order['cancelled_read']}'>{$order['cancelled_read']}</td>";
                echo "<td>
                    <form method='post' style='display:inline;'>
                        <input type='hidden' name='order_id' value='{$order['id']}'>
                        <input type='hidden' name='action' value='reset_notification'>
                        <button type='submit' class='btn btn-sm btn-warning'>Reset thông báo</button>
                    </form>
                </td>";
                echo "</tr>";
            }
            
            echo "</tbody></table></div>";
        } else {
            echo "<div class='alert alert-info'>Không có dữ liệu trong bảng orders.</div>";
        }
    }
    
    // Xử lý reset thông báo
    if (isset($_POST['action']) && $_POST['action'] === 'reset_notification' && isset($_POST['order_id'])) {
        $orderId = intval($_POST['order_id']);
        
        // Reset các cột thông báo về 0
        $resetSql = "UPDATE orders SET pending_read = 0, approved_read = 0, cancelled_read = 0 WHERE id = ?";
        $resetStmt = $conn->prepare($resetSql);
        $resetStmt->execute([$orderId]);
        
        echo "<div class='alert alert-success'>Đã reset thông báo cho đơn hàng #$orderId.</div>";
        echo "<script>setTimeout(function() { window.location.reload(); }, 1500);</script>";
    }
    
    echo "<div class='btn-back'>
        <a href='../../index.php?req=orders' class='btn btn-primary'>Quay lại trang quản lý đơn hàng</a>
        <a href='../../index.php' class='btn btn-secondary'>Quay lại trang chủ</a>
    </div>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Lỗi: " . $e->getMessage() . "</div>";
    echo "<div class='btn-back'>
        <a href='../../index.php' class='btn btn-secondary'>Quay lại trang chủ</a>
    </div>";
}

echo "</div></body></html>";
?>
