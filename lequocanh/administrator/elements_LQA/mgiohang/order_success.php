<?php
// Use SessionManager for safe session handling
require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

// Start session safely
SessionManager::start();

// Kiểm tra xem có thông báo thành công không
if (!isset($_SESSION['payment_success']) || !isset($_GET['order_id'])) {
    // Nếu không có thông báo thành công, chuyển hướng về trang giỏ hàng
    header('Location: giohangView.php');
    exit();
}

// Lấy ID đơn hàng
$orderId = $_GET['order_id'];

// Kết nối database để lấy thông tin đơn hàng
require_once '../mod/database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

// Lấy thông tin đơn hàng
$orderSql = "SELECT * FROM don_hang WHERE id = ?";
$orderStmt = $conn->prepare($orderSql);
$orderStmt->execute([$orderId]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

// Xóa thông báo thành công khỏi session
unset($_SESSION['payment_success']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng thành công</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../public_files/mycss.css">
    <style>
        .success-container {
            max-width: 800px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }

        .order-info {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
            text-align: left;
        }
    </style>
</head>

<body>
    <div class="success-container">
        <div class="success-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="currentColor" class="bi bi-check-circle-fill" viewBox="0 0 16 16">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
            </svg>
        </div>
        <h2 class="mb-3">Đặt hàng thành công!</h2>
        <p class="lead">Cảm ơn bạn đã đặt hàng. Chúng tôi đã nhận được thông tin thanh toán của bạn.</p>

        <div class="order-info">
            <h5>Thông tin đơn hàng:</h5>
            <p><strong>Mã đơn hàng:</strong> #<?php echo $orderId; ?></p>
            <p><strong>Mã tham chiếu:</strong> <?php echo $order['ma_don_hang_text']; ?></p>
            <p><strong>Tổng tiền:</strong> <?php echo number_format($order['tong_tien'], 0, ',', '.'); ?> đ</p>
            <p><strong>Địa chỉ giao hàng:</strong> <?php echo htmlspecialchars($order['dia_chi_giao_hang']); ?></p>
            <p><strong>Trạng thái:</strong> <?php echo $order['trang_thai'] == 'pending' ? 'Chờ xử lý' : $order['trang_thai']; ?></p>
            <p>Đơn hàng của bạn đang được xử lý. Chúng tôi sẽ liên hệ với bạn trong thời gian sớm nhất.</p>
        </div>

        <div class="mt-4">
            <a href="<?php echo isset($_SESSION['ADMIN']) ? '../../index.php' : '../../../index.php'; ?>" class="btn btn-primary">Tiếp tục mua hàng</a>
            <a href="../../index.php?req=don_hang" class="btn btn-success ms-2">Xem đơn hàng của tôi</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>