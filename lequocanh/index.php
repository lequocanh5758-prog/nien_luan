<?php
// Use SessionManager for safe session handling
require_once __DIR__ . '/administrator/elements_LQA/mod/sessionManager.php';
require_once __DIR__ . '/administrator/elements_LQA/config/logger_config.php';

// Start session safely
SessionManager::start();

// Xóa session pending_order nếu user quay lại từ trang thanh toán thành công
if (isset($_GET['clear_session']) && $_GET['clear_session'] == '1') {
    unset($_SESSION['pending_order']);
    // Redirect để xóa parameter khỏi URL
    header('Location: index.php');
    exit();
}

require_once './administrator/elements_LQA/mod/giohangCls.php';
require_once './administrator/elements_LQA/mod/database.php';

$giohang = new GioHang();
$cartItemCount = $giohang->getCartItemCount();

// Kiểm tra xem người dùng có phải là nhân viên không
$isNhanVien = false;
if (isset($_SESSION['USER'])) {
    $username = $_SESSION['USER'];
    $db = Database::getInstance()->getConnection();

    // Kiểm tra user id
    $stmt = $db->prepare("SELECT iduser FROM user WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_OBJ);

    if ($user) {
        // Kiểm tra xem có trong bảng nhân viên không
        $stmt = $db->prepare("SELECT COUNT(*) FROM nhanvien WHERE iduser = ?");
        $stmt->execute([$user->iduser]);
        $isNhanVien = $stmt->fetchColumn() > 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="public_files/mycss.css">
    <link rel="stylesheet" href="public_files/notification.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Cửa Hàng Điện Thoại</title>

</head>

<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-mobile-alt me-2"></i>
                Cửa Hàng Điện Thoại
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="search-container mx-auto">
                    <form class="d-flex" action="./search.php" method="GET" id="searchForm">
                        <input class="form-control me-2" type="search" placeholder="Tìm kiếm sản phẩm..."
                            aria-label="Search" name="query" id="searchInput">
                        <button class="btn btn-light" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    <div id="searchResults"></div>
                </div>

                <div class="ms-auto d-flex align-items-center">
                    <?php if (isset($_SESSION['USER'])): ?>
                        <div class="dropdown me-2">
                            <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user me-2"></i>
                                <?php
                                // Lấy tên người dùng từ database
                                $username = $_SESSION['USER'];
                                $db = Database::getInstance()->getConnection();
                                $stmt = $db->prepare("SELECT hoten FROM user WHERE username = ?");
                                $stmt->execute([$username]);
                                $user = $stmt->fetch(PDO::FETCH_OBJ);
                                echo $user ? $user->hoten : $username;
                                ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="./administrator/elements_LQA/mUser/userProfile.php">
                                        <i class="fas fa-user-circle me-2"></i>Thông tin tài khoản
                                    </a></li>
                                <?php if ($isNhanVien): ?>
                                    <li><a class="dropdown-item" href="./administrator/index.php">
                                            <i class="fas fa-user-cog me-2"></i>Đến trang quản trị
                                        </a></li>
                                <?php endif; ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item"
                                        href="./administrator/elements_LQA/mUser/userAct.php?reqact=userlogout">
                                        <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                                    </a></li>
                            </ul>
                        </div>
                    <?php elseif (isset($_SESSION['ADMIN'])): ?>
                        <div class="dropdown me-2">
                            <button class="btn btn-light dropdown-toggle" type="button" id="adminDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-shield me-2"></i>
                                Quản trị viên
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                <li><a class="dropdown-item" href="./administrator/index.php">
                                        <i class="fas fa-tachometer-alt me-2"></i>Bảng điều khiển
                                    </a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item"
                                        href="./administrator/elements_LQA/mUser/userAct.php?reqact=userlogout">
                                        <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                                    </a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="./administrator/userLogin.php" class="btn btn-light me-2">
                            <i class="fas fa-user me-2"></i>
                            Đăng nhập
                        </a>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['USER'])): ?>
                        <!-- Icon thông báo đơn hàng với dropdown -->
                        <div class="position-relative me-2">
                            <button class="btn btn-light position-relative notification-btn">
                                <i class="fas fa-bell"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge" style="display: none;">
                                    0
                                </span>
                            </button>

                            <!-- Dropdown thông báo -->
                            <div class="notification-dropdown">
                                <div class="notification-header">
                                    <h6>Thông báo</h6>
                                    <div class="notification-actions-header">
                                        <button class="mark-all-read">Đánh dấu tất cả đã đọc</button>
                                        <button class="delete-read-notifications">Xóa thông báo đã đọc</button>
                                    </div>
                                </div>
                                <ul class="notification-list">
                                    <!-- Nội dung thông báo sẽ được thêm bằng JavaScript -->
                                    <li class="notification-empty">
                                        <i class="fas fa-spinner fa-spin"></i>
                                        <p>Đang tải thông báo...</p>
                                    </li>
                                </ul>
                                <div class="notification-footer">
                                    <a href="./administrator/index.php?req=don_hang">Đơn hàng của tôi</a>
                                    <a href="./administrator/index.php?req=lichsumuahang">Lịch sử mua hàng</a>
                                </div>
                            </div>

                            <!-- Modal chi tiết đơn hàng -->
                            <div class="order-detail-modal">
                                <div class="order-detail-content">
                                    <span class="order-detail-close">&times;</span>
                                    <div class="order-detail-header">
                                        <h3>Chi tiết đơn hàng #<span id="order-id"></span></h3>
                                        <span class="order-status" id="order-status"></span>
                                    </div>
                                    <div class="order-detail-info">
                                        <div class="order-detail-info-col">
                                            <div class="order-detail-info-item">
                                                <strong>Mã đơn hàng</strong>
                                                <div id="order-code"></div>
                                            </div>
                                            <div class="order-detail-info-item">
                                                <strong>Ngày đặt</strong>
                                                <div id="order-date"></div>
                                            </div>
                                            <div class="order-detail-info-item">
                                                <strong>Phương thức thanh toán</strong>
                                                <div id="order-payment-method"></div>
                                            </div>
                                        </div>
                                        <div class="order-detail-info-col">
                                            <div class="order-detail-info-item">
                                                <strong>Địa chỉ giao hàng</strong>
                                                <div id="order-address" class="order-detail-address"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="order-detail-items">
                                        <h4>Sản phẩm</h4>
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th width="60">Hình ảnh</th>
                                                    <th>Sản phẩm</th>
                                                    <th width="100">Đơn giá</th>
                                                    <th width="80">Số lượng</th>
                                                    <th width="120">Thành tiền</th>
                                                </tr>
                                            </thead>
                                            <tbody id="order-items">
                                                <!-- Danh sách sản phẩm sẽ được thêm bằng JavaScript -->
                                            </tbody>
                                        </table>
                                        <div class="order-detail-total">
                                            Tổng tiền: <span id="order-total"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Icon giỏ hàng -->
                        <a href="./administrator/elements_LQA/mgiohang/giohangView.php"
                            class="btn btn-light position-relative">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $cartItemCount; ?>
                            </span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Category Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <?php require './apart/menuLoaihang.php'; ?>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container py-4">
        <div class="row">
            <div class="col-12">
                <?php
                if (!isset($_GET['reqHanghoa'])) {
                    require './apart/viewListLoaihang.php';
                } else {
                    require './apart/viewHangHoa.php';
                }
                ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer mt-auto py-5">
        <div class="container">
            <div class="row gy-4">
                <div class="col-lg-3 col-md-6">
                    <h5 class="text-white mb-3">Về chúng tôi</h5>
                    <p class="small text-muted">
                        Cửa hàng điện thoại uy tín hàng đầu Việt Nam. Chuyên cung cấp các sản phẩm chính hãng với chất
                        lượng tốt nhất và dịch vụ chăm sóc khách hàng 24/7.
                    </p>
                    <div class="social-icons mt-4">
                        <a href="#" title="Facebook"><i class="fab fa-facebook"></i></a>
                        <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" title="Youtube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h5 class="text-white mb-3">Thông tin hữu ích</h5>
                    <ul class="list-unstyled text-muted">
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Hướng dẫn mua hàng</a></li>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Chính sách bảo hành</a>
                        </li>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Chính sách đổi trả</a></li>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Chính sách vận chuyển</a>
                        </li>
                        <li class="mb-2"><a href="#" class="text-muted text-decoration-none">Điều khoản dịch vụ</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h5 class="text-white mb-3">Liên hệ</h5>
                    <ul class="list-unstyled text-muted">
                        <li class="mb-3">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            123 Đường ABC, Phường XYZ, Quận 1, TP.HCM
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-phone me-2"></i>
                            Hotline: 1900 xxxx
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-envelope me-2"></i>
                            Email: support@example.com
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-clock me-2"></i>
                            Giờ làm việc: 8:00 - 22:00
                        </li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h5 class="text-white mb-3">Đăng ký nhận tin</h5>
                    <p class="small text-muted">Đăng ký để nhận thông tin về sản phẩm mới và khuyến mãi</p>
                    <form class="mb-3">
                        <div class="input-group">
                            <input class="form-control" type="email" placeholder="Email của bạn"
                                style="border-radius: 20px 0 0 20px;">
                            <button class="btn btn-primary" type="submit" style="border-radius: 0 20px 20px 0;">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                    <div class="mt-4">
                        <img src="path/to/payment-methods.png" alt="Phương thức thanh toán" class="img-fluid"
                            style="max-height: 30px;">
                    </div>
                </div>
            </div>

            <hr class="text-muted my-4">

            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="small text-muted mb-0">
                        &copy; <?php echo date('Y'); ?> Cửa Hàng Điện Thoại. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <img src="path/to/verified-badge.png" alt="Chứng nhận" class="img-fluid" style="max-height: 40px;">
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="administrator/js_LQA/jscript.js"></script>
    <script src="public_files/search.js"></script>

    <!-- Script xử lý thông báo -->
    <?php if (isset($_SESSION['USER'])): ?>
        <script src="public_files/notification.js"></script>
    <?php endif; ?>
</body>

</html>