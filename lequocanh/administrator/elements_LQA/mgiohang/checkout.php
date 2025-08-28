<?php
// Use SessionManager for safe session handling
require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

// Start session safely
SessionManager::start();
require_once '../../elements_LQA/mod/giohangCls.php';
require_once '../../elements_LQA/mod/hanghoaCls.php';
require_once '../../elements_LQA/mod/mtonkhoCls.php';
require_once '../../elements_LQA/mod/database.php';

$giohang = new GioHang();

// Kiểm tra xem người dùng có thể sử dụng giỏ hàng không
if (!$giohang->canUseCart()) {
    if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
        // Lưu URL hiện tại để chuyển hướng lại sau khi đăng nhập
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ../../userLogin.php');
    } else {
        // Nếu là admin, chuyển hướng về trang quản trị
        header('Location: ../../index.php');
    }
    exit();
}

// Kiểm tra xem có dữ liệu sản phẩm được gửi từ form không
if (!isset($_POST['selected_products']) || empty($_POST['selected_products'])) {
    // Tạo fake data cho test nhanh qua ngrok
    if (isset($_GET['test']) && $_GET['test'] == '1') {
        $_POST['selected_products'] = json_encode([
            ['productId' => 1, 'quantity' => 1]
        ]);
    } else {
        // Nếu không có sản phẩm được chọn, chuyển hướng về trang giỏ hàng
        header('Location: giohangView.php');
        exit();
    }
}

// Lấy dữ liệu sản phẩm từ form
$selectedProducts = json_decode($_POST['selected_products'], true);

// Khởi tạo các đối tượng
$giohang = new GioHang();
$hanghoa = new hanghoa();
$tonkho = new MTonKho();

// Lấy thông tin người dùng nếu đã đăng nhập
$userAddress = '';
if (isset($_SESSION['USER'])) {
    require_once '../../elements_LQA/mod/userCls.php';
    $userObj = new user();
    $currentUser = $userObj->UserGetbyUsername($_SESSION['USER']);
    if ($currentUser && !empty($currentUser->diachi)) {
        $userAddress = $currentUser->diachi;
    }
}

// Lấy thông tin chi tiết của các sản phẩm đã chọn
$orderDetails = [];
$totalAmount = 0;

foreach ($selectedProducts as $product) {
    $productId = $product['productId'];
    $quantity = $product['quantity'];

    // Lấy thông tin sản phẩm
    $productInfo = $hanghoa->HanghoaGetbyId($productId);

    // Kiểm tra tồn kho
    $tonkhoInfo = $tonkho->getTonKhoByIdHangHoa($productId);

    if (!$productInfo) {
        // Nếu không tìm thấy sản phẩm, bỏ qua
        continue;
    }

    if (!$tonkhoInfo || $tonkhoInfo->soLuong < $quantity) {
        // Nếu không đủ hàng, hiển thị thông báo lỗi
        $_SESSION['checkout_error'] = 'Sản phẩm "' . $productInfo->tenhanghoa . '" không đủ số lượng trong kho.';
        header('Location: giohangView.php');
        exit();
    }

    // Lấy thông tin hình ảnh
    $hinhanh = $hanghoa->GetHinhAnhById($productInfo->hinhanh);
    $imageSrc = "";

    // Sử dụng placeholder image online để tránh lỗi 404
    $imageSrc = "https://via.placeholder.com/80x80/cccccc/666666?text=No+Image";

    // Tính tổng tiền cho sản phẩm
    $subtotal = $productInfo->giathamkhao * $quantity;
    $totalAmount += $subtotal;

    // Thêm vào danh sách sản phẩm đã chọn
    $orderDetails[] = [
        'id' => $productId,
        'name' => $productInfo->tenhanghoa,
        'price' => $productInfo->giathamkhao,
        'quantity' => $quantity,
        'subtotal' => $subtotal,
        'image' => $imageSrc
    ];
}

// Lưu thông tin đơn hàng vào session để sử dụng sau khi thanh toán
$_SESSION['order_details'] = $orderDetails;
$_SESSION['total_amount'] = $totalAmount;

// Lấy thông tin cấu hình thanh toán từ cơ sở dữ liệu
$db = Database::getInstance();
$conn = $db->getConnection();

// Kiểm tra xem bảng cau_hinh_thanh_toan đã tồn tại chưa
$checkTableSql = "SHOW TABLES LIKE 'cau_hinh_thanh_toan'";
$checkTableStmt = $conn->prepare($checkTableSql);
$checkTableStmt->execute();

$paymentConfig = [
    'ten_ngan_hang' => '',
    'so_tai_khoan' => '',
    'ten_tai_khoan' => ''
];

if ($checkTableStmt->rowCount() > 0) {
    // Bảng đã tồn tại, lấy thông tin cấu hình
    $configSql = "SELECT * FROM cau_hinh_thanh_toan LIMIT 1";
    $configStmt = $conn->prepare($configSql);
    $configStmt->execute();

    if ($configStmt->rowCount() > 0) {
        $config = $configStmt->fetch(PDO::FETCH_ASSOC);
        // Map tên cột mới sang tên cũ để tương thích với code hiển thị
        $paymentConfig = [
            'bank_name' => $config['ten_ngan_hang'],
            'account_number' => $config['so_tai_khoan'],
            'account_name' => $config['ten_tai_khoan']
        ];
    }
}

// Tạo mã đơn hàng ngẫu nhiên
$orderCode = 'ORDER' . time() . rand(1000, 9999);
$_SESSION['order_code'] = $orderCode;

// Tạo nội dung chuyển khoản
$transferContent = $orderCode;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../stylecss_LQA/mycss.css">
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 20px auto;
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
        }

        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .payment-methods {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .payment-method {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .payment-method.active {
            border-color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.05);
        }

        .payment-method img {
            height: 40px;
            margin-bottom: 10px;
        }

        .qr-container {
            text-align: center;
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            background-color: #f8f9fa;
        }

        .qr-code {
            max-width: 300px;
            margin: 0 auto;
        }

        .bank-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #e9ecef;
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <div class="checkout-container">
        <h2 class="mb-4">Thanh toán đơn hàng</h2>

        <!-- Thông tin địa chỉ giao hàng -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Địa chỉ giao hàng</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="shipping-address" class="form-label">Địa chỉ nhận hàng</label>
                    <textarea class="form-control" id="shipping-address" rows="3"
                        placeholder="Nhập địa chỉ giao hàng"><?php echo htmlspecialchars($userAddress); ?></textarea>
                    <div class="form-text">Vui lòng nhập địa chỉ đầy đủ để chúng tôi giao hàng đến bạn.</div>
                </div>
            </div>
        </div>

        <!-- Thông tin đơn hàng -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Thông tin đơn hàng</h5>
            </div>
            <div class="card-body">
                <p><strong>Mã đơn hàng:</strong> <?php echo $orderCode; ?></p>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Đơn giá</th>
                            <th>Số lượng</th>
                            <th>Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderDetails as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo $item['image']; ?>"
                                            alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-image me-3">
                                        <span><?php echo htmlspecialchars($item['name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo number_format($item['price'], 0, ',', '.'); ?> ₫</td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><?php echo number_format($item['subtotal'], 0, ',', '.'); ?> ₫</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Tổng tiền:</strong></td>
                            <td><strong><?php echo number_format($totalAmount, 0, ',', '.'); ?> ₫</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Phương thức thanh toán -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Phương thức thanh toán</h5>
            </div>
            <div class="card-body">
                <div class="payment-methods">
                    <div class="payment-method" id="momo-payment">
                        <img src="https://developers.momo.vn/v3/assets/images/square-logo.svg" alt="MoMo" style="height: 40px; margin-bottom: 10px;">
                        <h5>Thanh toán MoMo</h5>
                        <p class="text-muted">Thanh toán nhanh chóng và an toàn qua ví MoMo</p>
                    </div>
                    <div class="payment-method active" id="bank-transfer">
                        <i class="fas fa-university" style="font-size: 2rem; color: #0d6efd; margin-bottom: 10px;"></i>
                        <h5>Chuyển khoản ngân hàng</h5>
                        <p class="text-muted">Quét mã QR để thanh toán qua ứng dụng ngân hàng</p>
                    </div>
                    <div class="payment-method" id="cod-payment">
                        <i class="fas fa-truck" style="font-size: 2rem; color: #28a745; margin-bottom: 10px;"></i>
                        <h5>Thanh toán khi nhận hàng (COD)</h5>
                        <p class="text-muted">Thanh toán bằng tiền mặt khi nhận hàng</p>
                    </div>
                </div>

                <!-- Thông tin thanh toán MoMo -->
                <div class="qr-container" id="momo-payment-details" style="display: none;">
                    <h5>Thanh toán qua MoMo</h5>
                    <div class="text-center">
                        <img src="https://developers.momo.vn/v3/assets/images/logo.png" alt="MoMo" style="height: 60px; margin-bottom: 20px;">
                        <p>Bạn sẽ được chuyển hướng đến trang thanh toán MoMo để hoàn tất giao dịch.</p>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Lưu ý:</strong> Sau khi thanh toán thành công trên MoMo, bạn sẽ được tự động chuyển về trang xác nhận đơn hàng.
                        </div>
                    </div>
                </div>

                <!-- Thông tin thanh toán qua VietQR -->
                <div class="qr-container" id="bank-transfer-details">
                    <?php if (!empty($paymentConfig['account_number']) && !empty($paymentConfig['bank_name'])): ?>
                        <h5>Quét mã QR để thanh toán</h5>
                        <div class="qr-code">
                            <?php
                            // Tạo URL VietQR
                            $bankCode = ''; // Mã ngân hàng, cần cập nhật theo ngân hàng thực tế
                            $amount = $totalAmount;
                            $description = $transferContent;

                            // Xác định mã ngân hàng dựa trên tên ngân hàng
                            switch (strtoupper($paymentConfig['bank_name'])) {
                                case 'VIETCOMBANK':
                                case 'VCB':
                                    $bankCode = 'VCB';
                                    break;
                                case 'AGRIBANK':
                                    $bankCode = 'AGR';
                                    break;
                                case 'VIETINBANK':
                                case 'VIETTINBANK':
                                    $bankCode = 'ICB';
                                    break;
                                case 'BIDV':
                                    $bankCode = 'BIDV';
                                    break;
                                case 'TECHCOMBANK':
                                case 'TCB':
                                    $bankCode = 'TCB';
                                    break;
                                case 'MB':
                                case 'MBB':
                                    $bankCode = 'MB';
                                    break;
                                case 'ACB':
                                    $bankCode = 'ACB';
                                    break;
                                case 'TPB':
                                case 'TPBANK':
                                    $bankCode = 'TPB';
                                    break;
                                default:
                                    $bankCode = '';
                            }

                            // Tạo URL VietQR - Sử dụng mã ngân hàng mặc định nếu không xác định được
                            if (empty($bankCode)) {
                                $bankCode = 'TCB'; // Mặc định là Techcombank nếu không xác định được
                            }

                            // Đảm bảo các tham số được mã hóa đúng cách
                            $encodedAccountName = urlencode($paymentConfig['account_name']);
                            $encodedDescription = urlencode($description);

                            // Tạo URL VietQR
                            $vietQrUrl = "https://img.vietqr.io/image/{$bankCode}-{$paymentConfig['account_number']}-compact.png?amount={$amount}&addInfo={$encodedDescription}&accountName={$encodedAccountName}";

                            // Debug
                            error_log("VietQR URL: " . $vietQrUrl);
                            ?>
                            <img src="<?php echo $vietQrUrl; ?>" alt="QR Code" class="img-fluid">
                        </div>
                        <div class="bank-info mt-3">
                            <p><strong>Ngân hàng:</strong> <?php echo htmlspecialchars($paymentConfig['bank_name']); ?></p>
                            <p><strong>Số tài khoản:</strong>
                                <?php echo htmlspecialchars($paymentConfig['account_number']); ?></p>
                            <p><strong>Chủ tài khoản:</strong>
                                <?php echo htmlspecialchars($paymentConfig['account_name']); ?></p>
                            <p><strong>Nội dung chuyển khoản:</strong> <?php echo $transferContent; ?></p>
                        </div>
                        <div class="alert alert-info mt-3">
                            <p>Sau khi thanh toán, vui lòng nhấn nút "Xác nhận đã thanh toán" bên dưới.</p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <p>Chưa có thông tin tài khoản ngân hàng. Vui lòng liên hệ quản trị viên để cập nhật.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Thông tin thanh toán COD -->
                <div class="qr-container" id="cod-payment-details" style="display: none;">
                    <h5>Thanh toán khi nhận hàng (COD)</h5>
                    <div class="text-center">
                        <i class="fas fa-truck" style="font-size: 80px; color: #28a745; margin-bottom: 20px;"></i>
                        <p class="lead">Bạn sẽ thanh toán bằng tiền mặt khi nhận hàng</p>
                        <div class="alert alert-success">
                            <h6><i class="fas fa-check-circle me-2"></i>Ưu điểm của COD:</h6>
                            <ul class="list-unstyled mb-0">
                                <li><i class="fas fa-check me-2"></i>Không cần thanh toán trước</li>
                                <li><i class="fas fa-check me-2"></i>Kiểm tra hàng trước khi thanh toán</li>
                                <li><i class="fas fa-check me-2"></i>An toàn và tiện lợi</li>
                            </ul>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Lưu ý:</strong> Vui lòng chuẩn bị đủ tiền mặt khi nhận hàng.
                            Số tiền cần thanh toán: <strong><?php echo number_format($totalAmount, 0, ',', '.'); ?> đ</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Nút xác nhận thanh toán -->
        <div class="d-flex justify-content-between">
            <a href="giohangView.php" class="btn btn-secondary">Quay lại giỏ hàng</a>
            <button id="confirmPaymentBtn" class="btn btn-primary">Xác nhận đã thanh toán</button>
        </div>

        <!-- Thông báo đang xử lý -->
        <div id="processingPayment" class="mt-3 text-center" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Đang xử lý...</span>
            </div>
            <p class="mt-2">Đang xử lý thanh toán, vui lòng đợi...</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const confirmPaymentBtn = document.getElementById('confirmPaymentBtn');
            const processingPayment = document.getElementById('processingPayment');
            const momoPaymentMethod = document.getElementById('momo-payment');
            const bankTransferMethod = document.getElementById('bank-transfer');
            const codPaymentMethod = document.getElementById('cod-payment');
            const momoDetails = document.getElementById('momo-payment-details');
            const bankDetails = document.getElementById('bank-transfer-details');
            const codDetails = document.getElementById('cod-payment-details');

            let selectedPaymentMethod = 'bank-transfer'; // Mặc định

            // Xử lý chuyển đổi phương thức thanh toán
            momoPaymentMethod.addEventListener('click', function() {
                console.log('🚀 MoMo payment method clicked!');

                // Kiểm tra địa chỉ giao hàng
                const shippingAddress = document.getElementById('shipping-address').value.trim();
                if (!shippingAddress) {
                    alert('Vui lòng nhập địa chỉ giao hàng trước khi thanh toán!');
                    return;
                }

                // Chuyển sang MoMo và thanh toán luôn
                momoPaymentMethod.classList.add('active');
                bankTransferMethod.classList.remove('active');
                momoDetails.style.display = 'block';
                bankDetails.style.display = 'none';
                selectedPaymentMethod = 'momo';
                confirmPaymentBtn.textContent = 'Đang xử lý MoMo...';
                confirmPaymentBtn.disabled = true;

                // Thanh toán MoMo ngay lập tức
                processMoMoPayment(shippingAddress);
            });

            bankTransferMethod.addEventListener('click', function() {
                // Chuyển sang chuyển khoản
                bankTransferMethod.classList.add('active');
                momoPaymentMethod.classList.remove('active');
                codPaymentMethod.classList.remove('active');
                bankDetails.style.display = 'block';
                momoDetails.style.display = 'none';
                codDetails.style.display = 'none';
                selectedPaymentMethod = 'bank-transfer';
                confirmPaymentBtn.textContent = 'Xác nhận đã thanh toán';
            });

            codPaymentMethod.addEventListener('click', function() {
                // Chuyển sang COD
                codPaymentMethod.classList.add('active');
                momoPaymentMethod.classList.remove('active');
                bankTransferMethod.classList.remove('active');
                codDetails.style.display = 'block';
                momoDetails.style.display = 'none';
                bankDetails.style.display = 'none';
                selectedPaymentMethod = 'cod';
                confirmPaymentBtn.textContent = 'Xác nhận đặt hàng COD';
            });

            confirmPaymentBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('🔥 BUTTON CLICKED! Payment method:', selectedPaymentMethod);

                // Test ngay lập tức
                if (selectedPaymentMethod === 'momo') {
                    console.log('✅ MoMo payment selected!');
                    // Lấy địa chỉ giao hàng
                    const shippingAddress = document.getElementById('shipping-address').value.trim();
                    if (!shippingAddress) {
                        alert('Vui lòng nhập địa chỉ giao hàng!');
                        return;
                    }
                    // Xử lý thanh toán MoMo
                    processMoMoPayment(shippingAddress);
                    return;
                }

                if (selectedPaymentMethod === 'cod') {
                    console.log('✅ COD payment selected!');
                    // Lấy địa chỉ giao hàng
                    const shippingAddress = document.getElementById('shipping-address').value.trim();
                    if (!shippingAddress) {
                        alert('Vui lòng nhập địa chỉ giao hàng!');
                        return;
                    }
                    // Xử lý đặt hàng COD
                    processCODPayment(shippingAddress);
                    return;
                }

                // Lấy địa chỉ giao hàng
                const shippingAddress = document.getElementById('shipping-address').value.trim();

                // Kiểm tra địa chỉ giao hàng
                if (!shippingAddress) {
                    alert('Vui lòng nhập địa chỉ giao hàng');
                    return;
                }

                // Hiển thị thông báo đang xử lý
                confirmPaymentBtn.disabled = true;
                processingPayment.style.display = 'block';

                // Xử lý thanh toán chuyển khoản (logic cũ)
                processBankTransferPayment(shippingAddress);
            });

            function processMoMoPayment(shippingAddress) {
                console.log('🚀 processMoMoPayment called!');
                console.log('Shipping address:', shippingAddress);
                console.log('Order code:', '<?php echo $orderCode; ?>');
                console.log('Amount:', '<?php echo $totalAmount; ?>');

                // Tạo form data cho MoMo
                const formData = new FormData();
                formData.append('payment_method', 'momo');
                formData.append('order_code', '<?php echo $orderCode; ?>');
                formData.append('shipping_address', shippingAddress);
                formData.append('amount', '<?php echo $totalAmount; ?>');

                // Debug: Log URL được gọi
                const currentUrl = window.location.origin;
                const relativePath = './momo_payment.php';
                console.log('🌐 Current URL:', currentUrl);
                console.log('🔗 Relative API Path:', relativePath);
                console.log('🔗 Full URL:', window.location.href);
                
                // Gửi request đến MoMo payment handler (sử dụng đường dẫn tương đối)
                fetch('./momo_payment.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('MoMo Response:', data); // Debug log

                        if (data.success && data.payUrl) {
                            // Lưu thông tin đơn hàng vào session trước khi chuyển
                            sessionStorage.setItem('pendingOrder', JSON.stringify({
                                orderId: data.orderId,
                                amount: '<?php echo $totalAmount; ?>',
                                shipping_address: shippingAddress
                            }));

                            // Chuyển hướng đến trang thanh toán MoMo
                            console.log('Redirecting to MoMo:', data.payUrl);
                            window.location.href = data.payUrl;
                        } else {
                            console.error('MoMo Error:', data);
                            alert('Lỗi khi tạo thanh toán MoMo: ' + (data.message || 'Unknown error'));
                            confirmPaymentBtn.disabled = false;
                            processingPayment.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Lỗi MoMo:', error);
                        alert('Đã xảy ra lỗi khi xử lý thanh toán MoMo. Vui lòng thử lại.');
                        confirmPaymentBtn.disabled = false;
                        processingPayment.style.display = 'none';
                    });
            }

            function processBankTransferPayment(shippingAddress) {
                // Tạo form data cho chuyển khoản
                const formData = new FormData();
                formData.append('order_code', '<?php echo $orderCode; ?>');
                formData.append('shipping_address', shippingAddress);

                // Gửi request bằng fetch API (logic cũ)
                fetch('payment_confirm.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (response.redirected) {
                            window.location.href = response.url;
                        } else {
                            return response.text().then(text => {
                                if (text.includes('order_success.php')) {
                                    const match = text.match(/order_success\.php\?order_id=(\d+)/);
                                    if (match && match[1]) {
                                        window.location.href = 'order_success.php?order_id=' + match[1];
                                    } else {
                                        window.location.href = 'giohangView.php';
                                    }
                                } else {
                                    window.location.href = 'giohangView.php';
                                }
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Lỗi:', error);
                        alert('Đã xảy ra lỗi khi xử lý thanh toán. Vui lòng thử lại.');
                        confirmPaymentBtn.disabled = false;
                        processingPayment.style.display = 'none';
                    });
            }

            function processCODPayment(shippingAddress) {
                console.log('🚚 processCODPayment called!');
                console.log('Shipping address:', shippingAddress);

                // Hiển thị thông báo đang xử lý
                confirmPaymentBtn.disabled = true;
                processingPayment.style.display = 'block';

                // Tạo form data cho COD
                const formData = new FormData();
                formData.append('payment_method', 'cod');
                formData.append('order_code', '<?php echo $orderCode; ?>');
                formData.append('shipping_address', shippingAddress);

                // Gửi request
                fetch('payment_confirm.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (response.redirected) {
                            window.location.href = response.url;
                        } else {
                            return response.text().then(text => {
                                if (text.includes('order_success.php')) {
                                    const match = text.match(/order_success\.php\?order_id=(\d+)/);
                                    if (match && match[1]) {
                                        window.location.href = 'order_success.php?order_id=' + match[1];
                                    } else {
                                        window.location.href = 'giohangView.php';
                                    }
                                } else {
                                    window.location.href = 'giohangView.php';
                                }
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Lỗi COD:', error);
                        alert('Đã xảy ra lỗi khi xử lý đặt hàng COD. Vui lòng thử lại.');
                        confirmPaymentBtn.disabled = false;
                        processingPayment.style.display = 'none';
                    });
            }
        });
    </script>
</body>

</html>