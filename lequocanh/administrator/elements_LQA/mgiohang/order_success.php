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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        <?php
        // Tạo thông báo theo phương thức thanh toán
        $paymentMethod = $order['phuong_thuc_thanh_toan'] ?? 'bank_transfer';
        $paymentStatus = $order['trang_thai_thanh_toan'] ?? 'pending';

        switch ($paymentMethod) {
            case 'momo':
                if ($paymentStatus == 'paid') {
                    echo '<p class="lead text-success"><i class="fas fa-check-circle me-2"></i>Thanh toán MoMo thành công! Đơn hàng của bạn đã được xác nhận.</p>';
                    $statusMessage = 'Đơn hàng đã được thanh toán và đang được chuẩn bị.';
                } else {
                    echo '<p class="lead text-warning"><i class="fas fa-clock me-2"></i>Đang chờ xác nhận thanh toán MoMo.</p>';
                    $statusMessage = 'Vui lòng hoàn tất thanh toán để xử lý đơn hàng.';
                }
                break;
            case 'bank_transfer':
                echo '<p class="lead text-info"><i class="fas fa-university me-2"></i>Cảm ơn bạn đã đặt hàng! Vui lòng chuyển khoản để hoàn tất đơn hàng.</p>';
                $statusMessage = 'Đơn hàng sẽ được xử lý sau khi chúng tôi xác nhận thanh toán.';
                break;
            case 'cod':
                echo '<p class="lead text-primary"><i class="fas fa-truck me-2"></i>Đơn hàng COD đã được xác nhận!</p>';
                $statusMessage = 'Bạn sẽ thanh toán khi nhận hàng. Chúng tôi sẽ liên hệ sớm nhất.';
                break;
            default:
                echo '<p class="lead">Cảm ơn bạn đã đặt hàng. Chúng tôi đã nhận được thông tin đơn hàng của bạn.</p>';
                $statusMessage = 'Đơn hàng của bạn đang được xử lý.';
        }
        ?>

        <div class="order-info">
            <h5>Thông tin đơn hàng:</h5>
            <p><strong>Mã đơn hàng:</strong> #<?php echo $orderId; ?></p>
            <p><strong>Mã tham chiếu:</strong> <?php echo $order['ma_don_hang_text']; ?></p>
            <p><strong>Tổng tiền:</strong> <?php echo number_format($order['tong_tien'], 0, ',', '.'); ?> đ</p>
            <p><strong>Phương thức thanh toán:</strong>
                <?php
                switch ($paymentMethod) {
                    case 'momo':
                        echo '<span class="badge bg-primary">MoMo</span>';
                        break;
                    case 'bank_transfer':
                        echo '<span class="badge bg-info">Chuyển khoản</span>';
                        break;
                    case 'cod':
                        echo '<span class="badge bg-success">COD</span>';
                        break;
                    default:
                        echo '<span class="badge bg-secondary">Khác</span>';
                }
                ?>
            </p>
            <p><strong>Trạng thái thanh toán:</strong>
                <?php
                switch ($paymentStatus) {
                    case 'paid':
                        echo '<span class="badge bg-success">Đã thanh toán</span>';
                        break;
                    case 'pending':
                        echo '<span class="badge bg-warning">Chờ thanh toán</span>';
                        break;
                    case 'failed':
                        echo '<span class="badge bg-danger">Thất bại</span>';
                        break;
                    default:
                        echo '<span class="badge bg-secondary">Không xác định</span>';
                }
                ?>
            </p>
            <p><strong>Địa chỉ giao hàng:</strong> <?php echo htmlspecialchars($order['dia_chi_giao_hang']); ?></p>
            <p><strong>Trạng thái đơn hàng:</strong>
                <?php
                switch ($order['trang_thai']) {
                    case 'pending':
                        echo '<span class="badge bg-warning">Chờ xử lý</span>';
                        break;
                    case 'approved':
                        echo '<span class="badge bg-success">Đã duyệt</span>';
                        break;
                    case 'cancelled':
                        echo '<span class="badge bg-danger">Đã hủy</span>';
                        break;
                    default:
                        echo '<span class="badge bg-secondary">' . $order['trang_thai'] . '</span>';
                }
                ?>
            </p>

            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle me-2"></i>
                <?php echo $statusMessage; ?>
            </div>

            <?php if ($paymentMethod == 'bank_transfer' && $paymentStatus == 'pending'): ?>
                <div class="alert alert-warning mt-3">
                    <h6><i class="fas fa-university me-2"></i>Thông tin chuyển khoản:</h6>
                    <p class="mb-1"><strong>Ngân hàng:</strong> Vietcombank</p>
                    <p class="mb-1"><strong>Số tài khoản:</strong> 1234567890</p>
                    <p class="mb-1"><strong>Chủ tài khoản:</strong> Cửa Hàng Điện Thoại</p>
                    <p class="mb-1"><strong>Nội dung:</strong> <?php echo $order['ma_don_hang_text']; ?></p>
                    <p class="mb-0"><strong>Số tiền:</strong> <?php echo number_format($order['tong_tien'], 0, ',', '.'); ?> đ</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="mt-4">
            <a href="<?php echo isset($_SESSION['ADMIN']) ? '../../index.php' : '../../../index.php'; ?>" class="btn btn-primary">Tiếp tục mua hàng</a>
            <a href="../../index.php?req=don_hang" class="btn btn-success ms-2">Xem đơn hàng của tôi</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Add notification system -->
    <div style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
        <?php include __DIR__ . '/../mthongbao/customer_notification_widget.php'; ?>
    </div>
    
    <!-- Toast notification for real-time updates -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="orderStatusToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-info-circle me-2 text-primary"></i>
                <strong class="me-auto">Cập nhật đơn hàng</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toastMessage">
                <!-- Message will be inserted here -->
            </div>
        </div>
    </div>
    
    <script>
    // Check for order status updates
    let lastStatus = '<?php echo $order['trang_thai']; ?>';
    let lastPaymentStatus = '<?php echo $order['trang_thai_thanh_toan']; ?>';
    
    function checkOrderStatus() {
        fetch('check_order_status.php?order_id=<?php echo $orderId; ?>')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Check if status changed
                    if (data.order_status !== lastStatus || data.payment_status !== lastPaymentStatus) {
                        // Show toast notification
                        let message = '';
                        
                        if (data.order_status === 'approved' && lastStatus !== 'approved') {
                            message = '✅ Đơn hàng của bạn đã được duyệt!';
                        } else if (data.payment_status === 'paid' && lastPaymentStatus !== 'paid') {
                            message = '💰 Thanh toán của bạn đã được xác nhận!';
                        } else if (data.order_status === 'cancelled') {
                            message = '❌ Đơn hàng của bạn đã bị hủy.';
                        }
                        
                        if (message) {
                            document.getElementById('toastMessage').textContent = message;
                            const toast = new bootstrap.Toast(document.getElementById('orderStatusToast'));
                            toast.show();
                            
                            // Reload page after 2 seconds to show updated status
                            setTimeout(() => {
                                location.reload();
                            }, 2000);
                        }
                        
                        lastStatus = data.order_status;
                        lastPaymentStatus = data.payment_status;
                    }
                }
            })
            .catch(error => console.error('Error checking order status:', error));
    }
    
    // Check every 5 seconds
    setInterval(checkOrderStatus, 5000);
    </script>
</body>

</html>
