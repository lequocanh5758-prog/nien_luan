<?php
/**
 * Test trang return để kiểm tra
 */

// Start session nếu chưa có
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Giả lập dữ liệu return từ MoMo
$_GET = [
    'partnerCode' => 'MOMO',
    'orderId' => 'ORDER_TEST_123',
    'requestId' => 'REQ_TEST_123',
    'amount' => '50000',
    'orderInfo' => 'Thanh toan don hang #ORDER_TEST_123',
    'transId' => '2758394756',
    'resultCode' => '0', // 0 = thành công
    'message' => 'Successful.',
    'extraData' => ''
];

// Giả lập user và giỏ hàng
$_SESSION['USER'] = 'khachhang';

// Fake cart data
$cartItems = [
    [
        'product_id' => 1,
        'tenhanghoa' => 'Nokia C32',
        'giathamkhao' => 2500000,
        'quantity' => 1,
        'hinhanh' => null
    ],
    [
        'product_id' => 2,
        'tenhanghoa' => 'Samsung Galaxy A14',
        'giathamkhao' => 3200000,
        'quantity' => 1,
        'hinhanh' => null
    ]
];

$totalAmount = 5700000;

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

$userId = $_SESSION['USER'];
$pendingOrder = ['shipping_address' => '123 Đường ABC, Quận 1, TP.HCM'];

// Xác định trạng thái thanh toán
$isSuccess = ($resultCode == '0');
$statusClass = $isSuccess ? 'success' : 'danger';
$statusIcon = $isSuccess ? 'fa-check-circle' : 'fa-times-circle';
$statusText = $isSuccess ? 'Thanh toán thành công!' : 'Thanh toán thất bại!';

echo "<h2>🧪 Test Return Page</h2>";
echo "<p><strong>Result Code:</strong> $resultCode</p>";
echo "<p><strong>Is Success:</strong> " . ($isSuccess ? 'YES' : 'NO') . "</p>";
echo "<p><strong>Status:</strong> $statusText</p>";
echo "<hr>";
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
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
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
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
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

            <?php if ($isSuccess && !empty($cartItems)): ?>
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
                            <p class="text-muted mb-0">Khách hàng: <strong><?php echo htmlspecialchars($userId); ?></strong></p>
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
                                $subtotal = $item['giathamkhao'] * $item['quantity'];
                            ?>
                            <tr>
                                <td>
                                    <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                        <i class="fas fa-mobile-alt text-muted"></i>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['tenhanghoa']); ?></strong>
                                </td>
                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                <td class="text-end"><?php echo number_format($item['giathamkhao'], 0, ',', '.'); ?> đ</td>
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
                <div class="transaction-info">
                    <h5><i class="fas fa-truck text-info"></i> Thông tin giao hàng</h5>
                    <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($pendingOrder['shipping_address']); ?></p>
                    <p class="text-muted mb-0">Đơn hàng sẽ được giao trong 2-3 ngày làm việc.</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Nút hành động -->
            <div class="invoice-section border-top">
                <div class="text-center">
                    <?php if ($isSuccess): ?>
                        <a href="index.php" class="btn btn-primary btn-return">
                            <i class="fas fa-shopping-bag me-2"></i>Tiếp tục mua hàng
                        </a>
                        <button onclick="window.print()" class="btn btn-outline-secondary ms-3">
                            <i class="fas fa-print me-2"></i>In hóa đơn
                        </button>
                    <?php else: ?>
                        <a href="administrator/elements_LQA/mgiohang/checkout.php" class="btn btn-primary btn-return">
                            <i class="fas fa-redo me-2"></i>Thử lại thanh toán
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary ms-3">
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
    .btn, .result-header { display: none !important; }
    body { background: white !important; }
    .result-card { box-shadow: none !important; }
</style>

</body>
</html>
