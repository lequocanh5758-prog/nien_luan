<?php

/**
 * Trang return cho thanh toán MoMo từ giỏ hàng
 * Hiển thị hóa đơn chi tiết và kết quả thanh toán
 */

// Start session nếu chưa có với config phù hợp
if (session_status() == PHP_SESSION_NONE) {
    // Cấu hình session để tương thích với domain/subdomain
    ini_set('session.cookie_domain', '');
    ini_set('session.cookie_path', '/');
    ini_set('session.cookie_secure', false);
    ini_set('session.cookie_httponly', true);
    session_start();
}

require_once '../../../payment/MoMoPayment.php';
require_once '../mPDO.php';
require_once '../mod/mtonkhoCls.php';

// Lấy thông tin từ URL parameters
$partnerCode = $_GET['partnerCode'] ?? '';
$orderId = $_GET['orderId'] ?? '';
$requestId = $_GET['requestId'] ?? '';
$amount = $_GET['amount'] ?? '';
$orderInfo = $_GET['orderInfo'] ?? '';
$transId = $_GET['transId'] ?? '';
$resultCode = $_GET['resultCode'] ?? '';
$message = $_GET['message'] ?? '';
$extraData = $_GET['extraData'] ?? '';

// Log thông tin return
error_log('MoMo Cart Return: ' . json_encode($_GET));

// Debug: Hiển thị thông tin GET và SESSION để kiểm tra
echo "<!-- DEBUG: GET Parameters -->";
echo "<!-- " . json_encode($_GET, JSON_PRETTY_PRINT) . " -->";
echo "<!-- DEBUG: SESSION Data -->";
echo "<!-- " . json_encode($_SESSION, JSON_PRETTY_PRINT) . " -->";

// Ngăn auto-redirect bằng cách flush output
ob_start();
echo "<!-- Preventing auto-redirect -->";
ob_flush();
flush();

// Verify signature (bỏ qua cho test localhost)
$verifyResult = true; // Luôn true cho test
if (strpos($_SERVER['HTTP_HOST'], 'localhost') === false) {
    $momoPayment = new MoMoPayment();
    $verifyResult = $momoPayment->verifyCallback($_GET);
}

// Decode extraData để lấy thông tin đơn hàng
$orderData = null;
if ($extraData) {
    $orderData = json_decode($extraData, true);
}

// Lấy thông tin từ session
$pendingOrder = $_SESSION['pending_order'] ?? null;
$userId = null;
if (isset($_SESSION['USER'])) {
    $userId = is_object($_SESSION['USER']) ? $_SESSION['USER']->iduser : $_SESSION['USER'];
}

// Lấy thông tin sản phẩm đã chọn từ session (thay vì tất cả sản phẩm trong giỏ hàng)
$cartItems = [];
$totalAmount = 0;

// Ưu tiên lấy từ order_details trong session (chỉ sản phẩm được chọn)
if (isset($_SESSION['order_details']) && !empty($_SESSION['order_details'])) {
    $orderDetails = $_SESSION['order_details'];

    // Chuyển đổi format từ order_details sang cartItems để tương thích với giao diện
    foreach ($orderDetails as $item) {
        $cartItems[] = [
            'product_id' => $item['id'],
            'quantity' => $item['quantity'],
            'tenhanghoa' => $item['name'],
            'giathamkhao' => $item['price'],
            'hinhanh' => $item['image']
        ];
        $totalAmount += $item['price'] * $item['quantity'];
    }
} else if ($userId) {
    // Fallback: nếu không có order_details, lấy từ giỏ hàng (logic cũ)
    try {
        $pdo = new mPDO();
        $cartQuery = "SELECT g.product_id, g.quantity, h.tenhanghoa, h.giathamkhao, h.hinhanh
                      FROM tbl_giohang g
                      LEFT JOIN hanghoa h ON g.product_id = h.idhanghoa
                      WHERE g.user_id = ?";
        $cartItems = $pdo->executeS($cartQuery, [$userId], true);

        // Tính tổng tiền
        foreach ($cartItems as $item) {
            $totalAmount += $item['giathamkhao'] * $item['quantity'];
        }
    } catch (Exception $e) {
        error_log('Error getting cart items: ' . $e->getMessage());
    }
}

// Xác định trạng thái thanh toán
$isSuccess = ($resultCode == '0');
$statusClass = $isSuccess ? 'success' : 'danger';
$statusIcon = $isSuccess ? 'fa-check-circle' : 'fa-times-circle';
$statusText = $isSuccess ? 'Thanh toán thành công!' : 'Thanh toán thất bại!';

// Chỉ sử dụng cart_items từ pending_order nếu không có order_details
if ($pendingOrder && isset($pendingOrder['cart_items']) && empty($cartItems)) {
    $cartItems = $pendingOrder['cart_items'];
    $totalAmount = $pendingOrder['amount'];
}

// Fallback: Nếu vẫn không có cartItems, tạo item từ orderInfo
if (empty($cartItems) && $orderInfo && $amount) {
    $cartItems = [
        [
            'id' => 'unknown',
            'tenhanghoa' => $orderInfo,
            'quantity' => 1,
            'giathamkhao' => $amount,
            'hinhanh' => null,
            'thanhtien' => $amount
        ]
    ];
    $totalAmount = $amount;
}

// Nếu thanh toán thành công, xóa chỉ những sản phẩm đã thanh toán khỏi giỏ hàng và cập nhật tồn kho
if ($isSuccess && isset($_SESSION['order_details'])) {
    try {
        $pdo = new mPDO();
        $tonkho = new mtonkho();

        // Xóa từng sản phẩm đã thanh toán khỏi giỏ hàng và cập nhật tồn kho
        foreach ($_SESSION['order_details'] as $item) {
            // 1. Cập nhật tồn kho (giảm số lượng)
            $tonkhoInfo = $tonkho->getTonKhoByIdHangHoa($item['id']);
            if ($tonkhoInfo) {
                error_log("Tồn kho hiện tại của sản phẩm ID " . $item['id'] . ": " . $tonkhoInfo->soLuong);

                // Sử dụng hàm updateSoLuong với isIncrement = false để giảm số lượng
                $updateResult = $tonkho->updateSoLuong($item['id'], $item['quantity'], false);

                if ($updateResult) {
                    error_log("Đã cập nhật tồn kho thành công cho sản phẩm ID: " . $item['id'] . ", giảm: " . $item['quantity']);

                    // Kiểm tra lại tồn kho sau khi cập nhật
                    $updatedTonkhoInfo = $tonkho->getTonKhoByIdHangHoa($item['id']);
                    if ($updatedTonkhoInfo) {
                        error_log("Tồn kho sau khi cập nhật của sản phẩm ID " . $item['id'] . ": " . $updatedTonkhoInfo->soLuong);
                    }
                } else {
                    error_log("Cập nhật tồn kho thất bại cho sản phẩm ID: " . $item['id']);
                }
            } else {
                error_log("Không tìm thấy thông tin tồn kho cho sản phẩm ID: " . $item['id']);
            }

            // 2. Xóa khỏi giỏ hàng
            if ($userId) {
                // Nếu có user đăng nhập, xóa từ database
                $pdo->execute(
                    "DELETE FROM tbl_giohang WHERE user_id = ? AND product_id = ?",
                    [$userId, $item['id']]
                );
            } else {
                // Nếu không đăng nhập, xóa từ session cart
                if (isset($_SESSION['cart'])) {
                    foreach ($_SESSION['cart'] as $key => $cartItem) {
                        if ($cartItem['id'] == $item['id']) {
                            unset($_SESSION['cart'][$key]);
                            break;
                        }
                    }
                    // Reindex array
                    $_SESSION['cart'] = array_values($_SESSION['cart']);
                }
            }
        }

        // Xóa order_details sau khi xử lý xong
        unset($_SESSION['order_details']);

        // Debug: Log session cart after removal
        if (isset($_SESSION['cart'])) {
            error_log('Session cart after removal: ' . json_encode($_SESSION['cart']));
        }

        error_log('Paid products removed from cart and inventory updated. User: ' . ($userId ?? 'guest'));
    } catch (Exception $e) {
        error_log('Error processing payment success: ' . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isSuccess ? 'Thanh toán thành công' : 'Thanh toán thất bại'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }

        .result-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .result-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .result-header {
            padding: 30px;
            text-align: center;
            color: white;
        }

        .result-header.success {
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        .result-header.danger {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
        }

        .result-icon {
            font-size: 4rem;
            margin-bottom: 15px;
        }

        .invoice-section {
            padding: 30px;
        }

        .invoice-header {
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .invoice-table {
            margin-bottom: 25px;
        }

        .invoice-table th {
            background-color: #f8f9fa;
            border: none;
            font-weight: 600;
        }

        .invoice-table td {
            border: none;
            border-bottom: 1px solid #dee2e6;
            padding: 12px 8px;
        }

        .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .transaction-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .btn-return {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .btn-return:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }

        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="result-container">
            <div class="result-card">
                <!-- Header với trạng thái -->
                <div class="result-header <?php echo $statusClass; ?>">
                    <i class="fas <?php echo $statusIcon; ?> result-icon"></i>
                    <h2><?php echo $statusText; ?></h2>
                    <?php if ($isSuccess): ?>
                        <p class="mb-0">Cảm ơn bạn đã mua hàng tại cửa hàng chúng tôi!</p>
                    <?php else: ?>
                        <p class="mb-0"><?php echo htmlspecialchars($message); ?></p>
                    <?php endif; ?>
                </div>

                <!-- DEBUG: Check variables -->
                <!-- isSuccess: <?php echo $isSuccess ? 'true' : 'false'; ?> -->
                <!-- cartItems count: <?php echo count($cartItems); ?> -->
                <!-- cartItems: <?php echo json_encode($cartItems); ?> -->

                <?php if ($isSuccess): ?>
                    <!-- Hóa đơn chi tiết -->
                    <div class="invoice-section">
                        <div class="invoice-header">
                            <div class="row">
                                <div class="col-md-6">
                                    <h4><i class="fas fa-receipt text-primary"></i> Hóa đơn thanh toán</h4>
                                    <p class="text-muted mb-0">Mã đơn hàng: <strong><?php echo htmlspecialchars($orderId); ?></strong></p>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <p class="text-muted mb-1">Ngày: <?php echo date('d/m/Y H:i:s'); ?></p>
                                    <p class="text-muted mb-0">Khách hàng: <strong><?php echo htmlspecialchars($userId ?? 'Khách vãng lai'); ?></strong></p>
                                </div>
                            </div>
                        </div>

                        <!-- Thông tin giao dịch -->
                        <div class="transaction-info">
                            <h5><i class="fas fa-credit-card text-success"></i> Thông tin giao dịch</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Mã giao dịch MoMo:</strong> <?php echo htmlspecialchars($transId); ?></p>
                                    <p><strong>Phương thức:</strong> Ví MoMo</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Thời gian:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
                                    <p><strong>Trạng thái:</strong> <span class="text-success">Đã thanh toán</span></p>
                                </div>
                            </div>
                        </div>

                        <!-- Chi tiết sản phẩm -->
                        <h5><i class="fas fa-shopping-cart text-primary"></i> Chi tiết đơn hàng</h5>
                        <div class="table-responsive">
                            <table class="table invoice-table">
                                <thead>
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th>Tên sản phẩm</th>
                                        <th class="text-center">Số lượng</th>
                                        <th class="text-end">Đơn giá</th>
                                        <th class="text-end">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartItems as $item):
                                        $price = $item['giathamkhao'] ?? 0;
                                        $quantity = $item['quantity'] ?? 1;
                                        $subtotal = $price * $quantity;
                                    ?>
                                        <tr>
                                            <td>
                                                <?php if (isset($item['hinhanh']) && $item['hinhanh']): ?>
                                                    <img src="../../../administrator/elements_LQA/mhanghoa/displayImage.php?id=<?php echo $item['hinhanh']; ?>"
                                                        alt="<?php echo htmlspecialchars($item['tenhanghoa']); ?>"
                                                        class="product-image">
                                                <?php else: ?>
                                                    <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($item['tenhanghoa']); ?></strong>
                                            </td>
                                            <td class="text-center"><?php echo $quantity; ?></td>
                                            <td class="text-end"><?php echo number_format($price, 0, ',', '.'); ?> đ</td>
                                            <td class="text-end"><?php echo number_format($subtotal, 0, ',', '.'); ?> đ</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="total-row">
                                        <td colspan="4" class="text-end"><strong>Tổng cộng:</strong></td>
                                        <td class="text-end"><strong><?php echo number_format($totalAmount, 0, ',', '.'); ?> đ</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Địa chỉ giao hàng -->
                        <?php if ($pendingOrder && isset($pendingOrder['shipping_address'])): ?>
                            <div class="transaction-info">
                                <h5><i class="fas fa-truck text-info"></i> Thông tin giao hàng</h5>
                                <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($pendingOrder['shipping_address']); ?></p>
                                <p class="text-muted mb-0">Đơn hàng sẽ được giao trong 2-3 ngày làm việc.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Nút hành động -->
                <div class="invoice-section border-top">
                    <div class="text-center">
                        <?php if ($isSuccess): ?>
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Thanh toán hoàn tất!</strong> 
                            </div>
                            <a href="../../../index.php" class="btn btn-primary btn-return">
                                <i class="fas fa-shopping-bag me-2"></i>Tiếp tục mua hàng
                            </a>
                            <button onclick="window.print()" class="btn btn-outline-secondary ms-3">
                                <i class="fas fa-print me-2"></i>In hóa đơn
                            </button>
                        <?php else: ?>
                            <div class="alert alert-warning mb-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Thanh toán không thành công!</strong> Vui lòng thử lại hoặc liên hệ hỗ trợ.
                            </div>
                            <a href="checkout.php" class="btn btn-primary btn-return">
                                <i class="fas fa-redo me-2"></i>Thử lại thanh toán
                            </a>
                            <a href="../../../index.php" class="btn btn-outline-secondary ms-3">
                                <i class="fas fa-home me-2"></i>Về trang chủ
                            </a>
                        <?php endif; ?>


                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <style media="print">
        .btn,
        .result-header {
            display: none !important;
        }

        body {
            background: white !important;
        }

        .result-card {
            box-shadow: none !important;
        }
    </style>

</body>

</html>