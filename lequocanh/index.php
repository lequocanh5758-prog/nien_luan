<?php

require_once __DIR__ . '/includes/performance_bootstrap.php';
require_once __DIR__ . '/config/local_config.php';
require_once __DIR__ . '/administrator/elements_LQA/mod/sessionManager.php';

// Start session BEFORE cache check so $_SESSION is available
SessionManager::start();

perf_init([
    'page_cache' => !isset($_SESSION['USER']) && !isset($_SESSION['ADMIN']),
    'page_cache_ttl' => 300,
    'html_minify' => true,
    'lazy_images' => true,
    'critical_css' => true,
    'debug_bar' => ($_ENV['APP_DEBUG'] ?? false)
])->start();

require_once __DIR__ . '/administrator/elements_LQA/config/logger_config.php';
require_once __DIR__ . '/includes/csrf_helper.php';
require_once __DIR__ . '/includes/query_builder.php';
require_once __DIR__ . '/includes/advanced_cache.php';

if (isset($_GET['clear_session']) && $_GET['clear_session'] == '1') {
    unset($_SESSION['pending_order']);
    header('Location: index.php');
    exit();
}

$showPaymentSuccess = false;
if (isset($_GET['payment_success']) && $_GET['payment_success'] == '1') {
    $showPaymentSuccess = true;
    error_log('User returned from successful payment - Session USER: ' . (isset($_SESSION['USER']) ? $_SESSION['USER'] : 'Not set'));
}

require_once __DIR__ . '/administrator/elements_LQA/mod/giohangCls.php';
require_once __DIR__ . '/administrator/elements_LQA/mod/database.php';
require_once __DIR__ . '/app/autoload.php';

use App\Services\UserService;

$giohang = new GioHang();
$cartItemCount = $giohang->getCartItemCount();

$isNhanVien = false;
if (isset($_SESSION['USER'])) {
    $username = $_SESSION['USER'];

    $isNhanVien = cache_remember('is_employee_' . $username, 300, function() use ($username) {
        return UserService::getInstance()->isEmployee(
            UserService::getInstance()->getUserByUsername($username)->iduser ?? 0
        );
    });
}
?>

<!DOCTYPE html>
<html lang="vi">

<?php require __DIR__ . '/components/head.php'; ?>

<body class="bg-light">
    <!-- Loading indicator -->
    <div class="page-loader" id="pageLoader"></div>

    <?php require __DIR__ . '/components/navbar.php'; ?>

    <!-- Payment Success Alert -->
    <?php if ($showPaymentSuccess): ?>
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Thanh toán thành công!</strong> Cảm ơn bạn đã mua hàng. Đơn hàng của bạn đang được xử lý.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Wishlist Section (chỉ hiển thị khi đăng nhập) -->
    <?php if (isset($_SESSION['USER'])): ?>
        <div class="container mt-4">
            <div class="wishlist-section" id="wishlistSection" style="display: none;">
                <div class="text-center py-3">
                    <i class="fas fa-spinner fa-spin"></i> Đang tải...
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="container py-4">
        <div class="row">
            <div class="col-12">
                <?php
                if (!isset($_GET['reqHanghoa'])) {
                    require __DIR__ . '/apart/viewListLoaihang.php';
                } else {
                    require __DIR__ . '/apart/viewHangHoa.php';
                }
                ?>
            </div>
        </div>
    </main>

    <!-- News & Promotions Section -->
    <?php require __DIR__ . '/apart/news_section.php'; ?>

    <?php require __DIR__ . '/components/footer.php'; ?>

    <?php require __DIR__ . '/components/scripts.php'; ?>

    <!-- CSRF Protection Helper -->
    <script src="public_files/js/csrf-helper.js" defer></script>

    <?php echo perf_footer(); ?>
</body>

</html>
<?php perf()->end(); ?>
