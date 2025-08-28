<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['ADMIN'])) {
    header('Location: ./userLogin.php');
    exit();
}

// Tắt hiển thị lỗi
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

require_once './elements_LQA/mod/database.php';
require_once './elements_LQA/mod/hanghoaCls.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Kiểm tra kết nối cơ sở dữ liệu
if (!$conn) {
    die('<div class="alert alert-danger">Không thể kết nối đến cơ sở dữ liệu.</div>');
}

// Kiểm tra ID đơn hàng
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('<div class="alert alert-danger">Không tìm thấy đơn hàng.</div>');
}

$orderId = (int)$_GET['id'];

// Lấy thông tin đơn hàng
$orderSql = "SELECT * FROM orders WHERE id = ?";
$orderStmt = $conn->prepare($orderSql);
$orderStmt->execute([$orderId]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('<div class="alert alert-danger">Không tìm thấy đơn hàng.</div>');
}

// Lấy thông tin chi tiết đơn hàng
$orderItemsSql = "SELECT oi.*, h.tenhanghoa 
                 FROM order_items oi
                 JOIN hanghoa h ON oi.product_id = h.idhanghoa
                 WHERE oi.order_id = ?";
$orderItemsStmt = $conn->prepare($orderItemsSql);
$orderItemsStmt->execute([$orderId]);
$orderItems = $orderItemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy thông tin khách hàng nếu có
$customerName = "Khách vãng lai";
$customerPhone = "";
$customerEmail = "";

if (isset($order['user_id']) && !empty($order['user_id'])) {
    $userSql = "SELECT * FROM user WHERE username = ?";
    $userStmt = $conn->prepare($userSql);
    $userStmt->execute([$order['user_id']]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $customerName = $user['hoten'] ?? $order['user_id'];
        $customerPhone = $user['dienthoai'] ?? "";
        $customerEmail = $user['email'] ?? "";
    }
}

// Lấy thông tin cửa hàng
$shopName = "Cửa hàng điện thoại";
$shopAddress = "Địa chỉ cửa hàng";
$shopPhone = "Số điện thoại cửa hàng";
$shopEmail = "Email cửa hàng";

// Định dạng ngày tháng
$orderDate = date('d/m/Y H:i', strtotime($order['created_at']));

// Tính tổng tiền
$totalAmount = 0;
foreach ($orderItems as $item) {
    $totalAmount += $item['price'] * $item['quantity'];
}

// Định dạng trạng thái đơn hàng
$orderStatus = "";
switch ($order['status']) {
    case 'pending':
        $orderStatus = "Chờ xác nhận";
        break;
    case 'approved':
        $orderStatus = "Đã duyệt";
        break;
    case 'cancelled':
        $orderStatus = "Đã hủy";
        break;
    default:
        $orderStatus = "Không xác định";
}

// Định dạng phương thức thanh toán
$paymentMethod = $order['payment_method'] == 'bank_transfer' ? 'Chuyển khoản ngân hàng' : $order['payment_method'];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn #<?php echo $order['id']; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .invoice-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .invoice-header h1 {
            color: #4a4a4a;
            margin-bottom: 5px;
        }

        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .invoice-details-col {
            flex: 1;
        }

        .invoice-details-col h3 {
            margin-top: 0;
            color: #4a4a4a;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }

        .invoice-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .invoice-items th,
        .invoice-items td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .invoice-items th {
            background-color: #f5f5f5;
        }

        .invoice-total {
            text-align: right;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }

        .invoice-total h3 {
            margin: 0;
            color: #4a4a4a;
        }

        .invoice-footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }

        .print-button {
            text-align: center;
            margin-top: 20px;
        }

        .print-button button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .print-button button:hover {
            background-color: #45a049;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
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

        @media print {
            .print-button {
                display: none;
            }

            body {
                padding: 0;
                margin: 0;
            }

            .invoice-container {
                box-shadow: none;
                border: none;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <h1><?php echo $shopName; ?></h1>
            <p><?php echo $shopAddress; ?></p>
            <p>Điện thoại: <?php echo $shopPhone; ?> | Email: <?php echo $shopEmail; ?></p>
            <h2>HÓA ĐƠN BÁN HÀNG</h2>
            <p>Mã đơn hàng: <?php echo $order['order_code']; ?></p>
        </div>

        <div class="invoice-details">
            <div class="invoice-details-col">
                <h3>Thông tin khách hàng</h3>
                <p><strong>Tên khách hàng:</strong> <?php echo htmlspecialchars($customerName); ?></p>
                <?php if (!empty($customerPhone)): ?>
                    <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($customerPhone); ?></p>
                <?php endif; ?>
                <?php if (!empty($customerEmail)): ?>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($customerEmail); ?></p>
                <?php endif; ?>
                <?php if (isset($order['shipping_address']) && !empty($order['shipping_address'])): ?>
                    <p><strong>Địa chỉ giao hàng:</strong><br><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                <?php endif; ?>
            </div>

            <div class="invoice-details-col">
                <h3>Thông tin đơn hàng</h3>
                <p><strong>Ngày đặt hàng:</strong> <?php echo $orderDate; ?></p>
                <p><strong>Trạng thái:</strong>
                    <span class="status-badge status-<?php echo $order['status']; ?>"><?php echo $orderStatus; ?></span>
                </p>
                <p><strong>Phương thức thanh toán:</strong> <?php echo $paymentMethod; ?></p>
            </div>
        </div>

        <table class="invoice-items">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Sản phẩm</th>
                    <th>Đơn giá</th>
                    <th>Số lượng</th>
                    <th>Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $index => $item): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($item['tenhanghoa']); ?></td>
                        <td><?php echo number_format($item['price'], 0, ',', '.'); ?> ₫</td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?> ₫</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="invoice-total">
            <h3>Tổng tiền: <?php echo number_format($order['total_amount'], 0, ',', '.'); ?> ₫</h3>
        </div>

        <div class="invoice-footer">
            <p>Cảm ơn quý khách đã mua hàng tại <?php echo $shopName; ?>!</p>
            <p>Mọi thắc mắc xin vui lòng liên hệ: <?php echo $shopPhone; ?></p>
        </div>
    </div>

    <div class="print-button">
        <button onclick="window.print()">In hóa đơn</button>
        <button onclick="window.close()">Đóng</button>
    </div>
</body>

</html>