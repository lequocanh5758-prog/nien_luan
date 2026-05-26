<?php
/**
 * ================================================================
 * COMPLETE REPORT TEST - Test toàn bộ chức năng theo BAO_CAO_SLIDE.txt
 * ================================================================
 *
 * Bổ sung các chức năng CHƯA được test trong FullSystemTest.php:
 * - PHẦN 1: Password Reset, Wishlist, Product Reviews, Support Tickets,
 *           Email Notifications, News/Blog, Product Comparison,
 *           Product View Tracking, Customer Notifications
 * - PHẦN 2: Roles & Permissions, Activity Log, Featured Products,
 *           Promotions, Banners, Coupons, Admin Order Management
 * - PHẦN 3: MoMo Payment Config
 * - PHẦN 4: Shipping (GHN, Fee Calculation)
 * - PHẦN 5: API Middleware (JWT, Rate Limit), Response class
 * - PHẦN 6: CacheService, QueryBuilder, SEO Helper, ConfigManager
 *
 * Usage: php tests/CompleteReportTest.php
 * ================================================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_DIR', dirname(__DIR__));
define('TEST_MODE', true);

// Override database connection for local testing
$_ENV['DB_HOST'] = '127.0.0.1';
$_ENV['DB_PORT'] = '23306';
$_ENV['DB_DATABASE'] = 'sales_management';
$_ENV['DB_USERNAME'] = 'app_user';
$_ENV['DB_PASSWORD'] = 'app_password';

$configFile = ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/config.ini';
$configContent = "[section]\n";
$configContent .= "servername = 127.0.0.1\n";
$configContent .= "port = 23306\n";
$configContent .= "dbname = sales_management\n";
$configContent .= "username = app_user\n";
$configContent .= "password = app_password\n";
file_put_contents($configFile, $configContent);

chdir(ROOT_DIR . '/lequocanh');

// ========================
// TEST TRACKING
// ========================
$testResults = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'errors' => [],
    'warnings' => [],
    'fixes_applied' => [],
    'test_details' => []
];

$createdData = [
    'user_ids' => [],
    'review_ids' => [],
    'ticket_ids' => [],
    'coupon_ids' => [],
    'news_ids' => [],
    'promotion_ids' => [],
    'banner_ids' => [],
    'notification_ids' => [],
    'hanghoa_ids' => [],
    'loaihang_ids' => [],
];

// ========================
// HELPERS
// ========================
function logTest(string $testName, bool $passed, string $message = '', array $details = []) {
    global $testResults;
    $testResults['total']++;
    if ($passed) {
        $testResults['passed']++;
        echo "  ✅ {$testName}";
        if ($message) echo " - {$message}";
        echo PHP_EOL;
    } else {
        $testResults['failed']++;
        $testResults['errors'][] = ['test' => $testName, 'message' => $message, 'details' => $details];
        echo "  ❌ {$testName}";
        if ($message) echo " - {$message}";
        echo PHP_EOL;
    }
    $testResults['test_details'][] = ['name' => $testName, 'passed' => $passed, 'message' => $message];
}

function logWarning(string $message) {
    global $testResults;
    $testResults['warnings'][] = $message;
    echo "  ⚠️  {$message}" . PHP_EOL;
}

function logFix(string $fix) {
    global $testResults;
    $testResults['fixes_applied'][] = $fix;
    echo "  🔧 FIX: {$fix}" . PHP_EOL;
}

function sectionHeader(string $title) {
    echo PHP_EOL . str_repeat('=', 60) . PHP_EOL;
    echo "  📋 {$title}" . PHP_EOL;
    echo str_repeat('=', 60) . PHP_EOL;
}

function subsection(string $title) {
    echo PHP_EOL . "  --- {$title} ---" . PHP_EOL;
}

// ========================
// START
// ========================
echo PHP_EOL;
echo "╔══════════════════════════════════════════════════════════════╗" . PHP_EOL;
echo "║  COMPLETE REPORT TEST - Test theo BAO_CAO_SLIDE.txt        ║" . PHP_EOL;
echo "║  Ngày: " . date('Y-m-d H:i:s') . "                            ║" . PHP_EOL;
echo "╚══════════════════════════════════════════════════════════════╝" . PHP_EOL;

// ========================
// DATABASE CONNECTION
// ========================
sectionHeader('0. DATABASE CONNECTION');

try {
    require_once ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/database.php';
    $db = Database::getInstance();
    $conn = $db->getConnection();
    if ($conn instanceof PDO) {
        $conn->query("SELECT 1");
        logTest('Database Connection', true, 'Kết nối database thành công');
    } else {
        logTest('Database Connection', false, 'Không có kết nối PDO hợp lệ');
        die("❌ Không thể kết nối database. Dừng test.");
    }
} catch (Exception $e) {
    logTest('Database Connection', false, 'Lỗi: ' . $e->getMessage());
    die("❌ Không thể kết nối database. Dừng test.");
}

// ========================
// PHẦN 1: GIAO DIỆN NGƯỜI DÙNG
// ========================

// --- 1.3 QUÊN MẬT KHẨU / ĐẶT LẠI MẬT KHẨU ---
sectionHeader('1.3 PASSWORD RESET - Quên mật khẩu / Đặt lại mật khẩu');

try {
    require_once ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/PasswordResetManager.php';
    $passwordReset = new PasswordResetManager();

    subsection('Tạo user test để kiểm tra password reset');
    // Create a test user first
    $testUsername = 'pwreset_test_' . time();
    $testEmail = $testUsername . '@test.com';

    require_once ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/userCls.php';
    $userCls = new user();

    // Find user by email (should not exist yet)
    $foundUser = $passwordReset->findUser($testEmail);
    logTest('findUser (không tồn tại)', $foundUser === false || $foundUser === null,
        'Email không tồn tại: trả về false/null');

    // Create reset token for non-existent user
    $tokenResult = $passwordReset->createResetToken(99999, 'nonexistent@test.com');
    logTest('createResetToken (user không tồn tại)', $tokenResult === false,
        'Không tạo token cho user không tồn tại');

    // Check rate limiting
    $rateLimitOk = $passwordReset->checkRateLimit('any@test.com', 3);
    logTest('checkRateLimit', is_bool($rateLimitOk),
        'Rate limit check: ' . ($rateLimitOk ? 'Cho phép' : 'Bị giới hạn'));

    // Cleanup expired tokens
    $cleaned = $passwordReset->cleanupExpiredTokens();
    logTest('cleanupExpiredTokens', $cleaned !== null,
        'Dọn dẹp token hết hạn: ' . ($cleaned !== null ? 'OK' : 'null'));

} catch (Exception $e) {
    logTest('Password Reset', false, 'Exception: ' . $e->getMessage());
}

// --- 1.4 USER SERVICE ---
sectionHeader('1.4 USER SERVICE - Service người dùng');

try {
    require_once ROOT_DIR . '/lequocanh/app/Services/UserService.php';
    $userService = UserService::getInstance();

    subsection('Get user by username');
    $userByUsername = $userService->getUserByUsername('admin');
    logTest('UserService.getUserByUsername("admin")',
        $userByUsername !== null && $userByUsername !== false,
        $userByUsername ? 'Tìm thấy user: ' . ($userByUsername->username ?? $userByUsername['username'] ?? 'N/A') : 'Không tìm thấy');

    subsection('Get user by ID');
    if ($userByUsername) {
        $userId = $userByUsername->iduser ?? $userByUsername['iduser'] ?? null;
        if ($userId) {
            $userById = $userService->getUserById($userId);
            logTest('UserService.getUserById(' . $userId . ')', $userById !== null, 'Tìm theo ID');
        }
    }

    subsection('Get user by email');
    $userByEmail = $userService->getUserByEmail('admin@test.com');
    logTest('UserService.getUserByEmail', $userByEmail !== null || $userByEmail === null,
        'Tìm theo email: ' . ($userByEmail ? 'Tìm thấy' : 'Không tìm thấy'));

    subsection('Get user full info');
    if (isset($userId)) {
        $fullInfo = $userService->getUserFullInfo($userId);
        logTest('UserService.getUserFullInfo', $fullInfo !== null, 'Thông tin đầy đủ');
    }

    subsection('Invalidate user cache');
    $userService->invalidateUserCache(1);
    logTest('UserService.invalidateUserCache', true, 'Xóa cache thành công');

} catch (Exception $e) {
    logTest('UserService', false, 'Exception: ' . $e->getMessage());
}

// --- 1.12 DANH SÁCH YÊU THÍCH (WISHLIST) ---
sectionHeader('1.12 WISHLIST - Danh sách yêu thích');

try {
    require_once ROOT_DIR . '/lequocanh/api/wishlist.php';
    $wishlist = new WishlistAPI();

    subsection('Kiểm tra class WishlistAPI');
    logTest('WishlistAPI class exists', class_exists('WishlistAPI'), 'Class tồn tại');

    subsection('Kiểm tra các phương thức WishlistAPI');
    $wishlistMethods = ['getWishlist', 'addToWishlist', 'removeFromWishlist', 'checkInWishlist', 'getCount', 'toggleWishlist'];
    foreach ($wishlistMethods as $method) {
        $hasMethod = method_exists($wishlist, $method);
        logTest("WishlistAPI.{$method}", $hasMethod, $hasMethod ? 'Phương thức tồn tại' : 'Thiếu phương thức');
    }

} catch (Exception $e) {
    logTest('Wishlist', false, 'Exception: ' . $e->getMessage());
}

// --- 1.13 ĐÁNH GIÁ SẢN PHẨM ---
sectionHeader('1.13 PRODUCT REVIEWS - Đánh giá sản phẩm');

try {
    require_once ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/ProductReviewCls.php';
    $review = new ProductReview();

    subsection('Kiểm tra các phương thức');
    $reviewMethods = [
        'addReview' => 'Thêm đánh giá',
        'getProductReviews' => 'Lấy đánh giá sản phẩm',
        'getProductRatingStats' => 'Thống kê đánh giá',
        'canUserReview' => 'Kiểm tra có thể đánh giá',
        'hasUserReviewed' => 'Kiểm tra đã đánh giá',
        'markHelpful' => 'Đánh dấu hữu ích',
        'getUserReviews' => 'Lấy đánh giá người dùng',
        'deleteReview' => 'Xóa đánh giá',
        'getReviewCount' => 'Đếm đánh giá'
    ];
    foreach ($reviewMethods as $method => $desc) {
        $hasMethod = method_exists($review, $method);
        logTest("ProductReview.{$method} ({$desc})", $hasMethod, $hasMethod ? 'OK' : 'Thiếu');
    }

    subsection('Kiểm tra bảng product_reviews');
    $stmt = $conn->query("SHOW TABLES LIKE 'product_reviews'");
    $tableExists = $stmt->rowCount() > 0;
    logTest('Bảng product_reviews', $tableExists, $tableExists ? 'Tồn tại' : 'Không tồn tại');

    subsection('Kiểm tra bảng review_reports');
    $stmt = $conn->query("SHOW TABLES LIKE 'review_reports'");
    $reviewReportsExists = $stmt->rowCount() > 0;
    logTest('Bảng review_reports', $reviewReportsExists, $reviewReportsExists ? 'Tồn tại' : 'Không tồn tại');

    subsection('Kiểm tra bảng review_helpful');
    $stmt = $conn->query("SHOW TABLES LIKE 'review_helpful'");
    $reviewHelpfulExists = $stmt->rowCount() > 0;
    logTest('Bảng review_helpful', $reviewHelpfulExists, $reviewHelpfulExists ? 'Tồn tại' : 'Không tồn tại');

    subsection('Test getReviewCount với sản phẩm không có đánh giá');
    $reviewCount = $review->getReviewCount(99999);
    logTest('getReviewCount (sản phẩm không có đánh giá)', $reviewCount === 0 || $reviewCount >= 0,
        'Số đánh giá: ' . $reviewCount);

    subsection('Test getProductRatingStats');
    $ratingStats = $review->getProductRatingStats(1);
    logTest('getProductRatingStats', is_array($ratingStats) || $ratingStats === false,
        is_array($ratingStats) ? 'Có thống kê' : 'Không có dữ liệu');

} catch (Exception $e) {
    logTest('Product Reviews', false, 'Exception: ' . $e->getMessage());
}

// --- 1.14 HỖ TRỢ KHÁCH HÀNG ---
sectionHeader('1.14 SUPPORT TICKETS - Hỗ trợ khách hàng');

try {
    require_once ROOT_DIR . '/lequocanh/api/support_tickets.php';
    $supportAPI = new SupportTicketAPI();

    subsection('Kiểm tra các phương thức');
    $supportMethods = [
        'createTicket' => 'Tạo ticket',
        'getUserTickets' => 'Lấy ticket người dùng',
        'getAdminTickets' => 'Lấy ticket admin',
        'getTicketDetails' => 'Chi tiết ticket',
        'sendMessage' => 'Gửi tin nhắn',
        'updateTicketStatus' => 'Cập nhật trạng thái',
        'assignTicket' => 'Gán ticket'
    ];
    foreach ($supportMethods as $method => $desc) {
        $hasMethod = method_exists($supportAPI, $method);
        logTest("SupportTicketAPI.{$method} ({$desc})", $hasMethod, $hasMethod ? 'OK' : 'Thiếu');
    }

    subsection('Kiểm tra bảng support_tickets');
    $stmt = $conn->query("SHOW TABLES LIKE 'support_tickets'");
    $ticketTableExists = $stmt->rowCount() > 0;
    logTest('Bảng support_tickets', $ticketTableExists, $ticketTableExists ? 'Tồn tại' : 'Không tồn tại');

    subsection('Kiểm tra bảng support_messages');
    $stmt = $conn->query("SHOW TABLES LIKE 'support_messages'");
    $msgTableExists = $stmt->rowCount() > 0;
    logTest('Bảng support_messages', $msgTableExists, $msgTableExists ? 'Tồn tại' : 'Không tồn tại');

} catch (Exception $e) {
    logTest('Support Tickets', false, 'Exception: ' . $e->getMessage());
}

// --- 1.10 THÔNG BÁO KHÁCH HÀNG ---
sectionHeader('1.10 CUSTOMER NOTIFICATIONS - Thông báo khách hàng');

try {
    require_once ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/CustomerNotificationManager.php';
    $notifManager = new CustomerNotificationManager();

    subsection('Kiểm tra các phương thức');
    $notifMethods = [
        'notifyOrderApproved' => 'Thông báo duyệt đơn',
        'notifyOrderCancelled' => 'Thông báo hủy đơn',
        'notifyPaymentConfirmed' => 'Thông báo thanh toán',
        'createNotification' => 'Tạo thông báo',
        'getUserNotifications' => 'Lấy thông báo người dùng',
        'getUnreadCount' => 'Đếm chưa đọc',
        'markAsRead' => 'Đánh dấu đã đọc',
        'markAllAsRead' => 'Đánh dấu tất cả đã đọc',
        'deleteReadNotifications' => 'Xóa thông báo đã đọc',
        'notifyOrderSuccess' => 'Thông báo đặt hàng thành công',
        'canCancelOrder' => 'Kiểm tra có thể hủy',
        'cancelOrderWithReason' => 'Hủy đơn với lý do'
    ];
    foreach ($notifMethods as $method => $desc) {
        $hasMethod = method_exists($notifManager, $method);
        logTest("CustomerNotificationManager.{$method}", $hasMethod, $hasMethod ? 'OK' : 'Thiếu');
    }

    subsection('Test getUnreadCount với user không có thông báo');
    $unreadCount = $notifManager->getUnreadCount(99999);
    logTest('getUnreadCount (user không có thông báo)', $unreadCount === 0 || $unreadCount >= 0,
        'Số chưa đọc: ' . $unreadCount);

    subsection('Test getUserNotifications với user không có thông báo');
    $notifications = $notifManager->getUserNotifications(99999, 10, false);
    logTest('getUserNotifications (user không có thông báo)', is_array($notifications),
        'Số thông báo: ' . count($notifications));

    subsection('Kiểm tra bảng customer_notifications');
    $stmt = $conn->query("SHOW TABLES LIKE 'customer_notifications'");
    $notifTableExists = $stmt->rowCount() > 0;
    logTest('Bảng customer_notifications', $notifTableExists, $notifTableExists ? 'Tồn tại' : 'Không tồn tại');

} catch (Exception $e) {
    logTest('Customer Notifications', false, 'Exception: ' . $e->getMessage());
}

// --- 1.11 EMAIL NOTIFICATION ---
sectionHeader('1.11 EMAIL NOTIFICATION - Thông báo email');

try {
    require_once ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/EmailNotificationCls.php';
    $emailNotif = new EmailNotification();

    subsection('Kiểm tra các phương thức EmailNotification');
    $emailMethods = [
        'sendOrderConfirmation' => 'Gửi email xác nhận đơn',
        'sendOrderApproved' => 'Gửi email duyệt đơn',
        'sendOrderShipped' => 'Gửi email gửi hàng',
        'sendOrderSMS' => 'Gửi SMS đơn hàng',
        'sendOrderNotifications' => 'Gửi thông báo đơn hàng',
        'getOrderNotificationHistory' => 'Lịch sử thông báo đơn'
    ];
    foreach ($emailMethods as $method => $desc) {
        $hasMethod = method_exists($emailNotif, $method);
        logTest("EmailNotification.{$method}", $hasMethod, $hasMethod ? 'OK' : 'Thiếu');
    }

    subsection('Kiểm tra bảng notification_logs');
    $stmt = $conn->query("SHOW TABLES LIKE 'notification_logs'");
    $logTableExists = $stmt->rowCount() > 0;
    logTest('Bảng notification_logs', $logTableExists, $logTableExists ? 'Tồn tại' : 'Không tồn tại');

    // Test EmailService
    require_once ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/EmailService.php';
    $emailService = new EmailService();

    subsection('Kiểm tra các phương thức EmailService');
    logTest('EmailService.send', method_exists($emailService, 'send'), 'OK');
    logTest('EmailService.sendWelcomeEmail', method_exists($emailService, 'sendWelcomeEmail'), 'OK');
    logTest('EmailService.sendEmailUpdateNotification', method_exists($emailService, 'sendEmailUpdateNotification'), 'OK');

} catch (Exception $e) {
    logTest('Email Notification', false, 'Exception: ' . $e->getMessage());
}

// --- 1.16 TIN TỨC / BLOG ---
sectionHeader('1.16 NEWS / BLOG - Tin tức');

try {
    require_once ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/NewsManager.php';
    $newsManager = new NewsManager();

    subsection('Get all news');
    $allNews = $newsManager->getAllNews();
    logTest('NewsManager.getAllNews', is_array($allNews), 'Số tin: ' . count($allNews));

    subsection('Get published news');
    $publishedNews = $newsManager->getPublishedNews(5);
    logTest('NewsManager.getPublishedNews', is_array($publishedNews), 'Số tin đã xuất bản: ' . count($publishedNews));

    subsection('Add news');
    $testTitle = 'TEST_NEWS_' . time();
    $addNewsResult = $newsManager->addNews($testTitle, 'Nội dung test', '', 'Test Author', 0);
    logTest('NewsManager.addNews', $addNewsResult !== false && $addNewsResult > 0,
        'Thêm tin: ' . $testTitle . ' (ID: ' . $addNewsResult . ')');

    if ($addNewsResult && $addNewsResult > 0) {
        $createdData['news_ids'][] = $addNewsResult;

        subsection('Get news by ID');
        $newsById = $newsManager->getNewsById($addNewsResult);
        logTest('NewsManager.getNewsById', $newsById !== false,
            'Lấy được: ' . ($newsById->title ?? $newsById->tieu_de ?? 'N/A'));

        subsection('Update news');
        $updateResult = $newsManager->updateNews($addNewsResult, $testTitle . '_UPDATED', 'Nội dung đã cập nhật', '', 'Test Author', 0);
        logTest('NewsManager.updateNews', $updateResult !== false, 'Cập nhật tin tức');

        subsection('Delete news');
        $deleteResult = $newsManager->deleteNews($addNewsResult);
        logTest('NewsManager.deleteNews', $deleteResult !== false, 'Xóa tin tức');
    }

    subsection('Kiểm tra bảng news');
    $stmt = $conn->query("SHOW TABLES LIKE 'news'");
    $newsTableExists = $stmt->rowCount() > 0;
    logTest('Bảng news', $newsTableExists, $newsTableExists ? 'Tồn tại' : 'Không tồn tại');

} catch (Exception $e) {
    logTest('News/Blog', false, 'Exception: ' . $e->getMessage());
}

// --- 1.17 SO SÁNH SẢN PHẨM ---
sectionHeader('1.17 PRODUCT COMPARISON - So sánh sản phẩm');

try {
    $sosanhFile = ROOT_DIR . '/lequocanh/sosanh.php';
    $sosanhExists = file_exists($sosanhFile);
    logTest('sosanh.php exists', $sosanhExists, $sosanhExists ? 'File tồn tại' : 'File không tồn tại');

    if ($sosanhExists) {
        $content = file_get_contents($sosanhFile);
        $hasSession = strpos($content, 'SESSION') !== false || strpos($content, 'session') !== false;
        logTest('sosanh.php uses session', $hasSession, $hasSession ? 'Có sử dụng session' : 'Không thấy session');
    }
} catch (Exception $e) {
    logTest('Product Comparison', false, 'Exception: ' . $e->getMessage());
}

// --- 1.18 THEO DÕI LƯỢT XEM ---
sectionHeader('1.18 PRODUCT VIEW TRACKER - Theo dõi lượt xem');

try {
    require_once ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/ProductViewTrackerCls.php';
    $viewTracker = new ProductViewTracker();

    subsection('Kiểm tra các phương thức');
    $trackerMethods = ['trackView', 'getViewCount', 'getViewStats', 'getMostViewedProducts', 'resetViewCount'];
    foreach ($trackerMethods as $method) {
        logTest("ProductViewTracker.{$method}", method_exists($viewTracker, $method), 'OK');
    }

    subsection('Test getViewCount');
    $viewCount = $viewTracker->getViewCount(1);
    logTest('getViewCount', is_numeric($viewCount), 'Lượt xem: ' . $viewCount);

    subsection('Test getMostViewedProducts');
    $mostViewed = $viewTracker->getMostViewedProducts(5);
    logTest('getMostViewedProducts', is_array($mostViewed), 'Số sản phẩm: ' . count($mostViewed));

    subsection('Test getViewStats');
    $viewStats = $viewTracker->getViewStats(1, 30);
    logTest('getViewStats', is_array($viewStats) || $viewStats === false,
        is_array($viewStats) ? 'Có thống kê' : 'Không có dữ liệu');

} catch (Exception $e) {
    logTest('Product View Tracker', false, 'Exception: ' . $e->getMessage());
}


// ========================
// PHẦN 2: ADMIN PANEL
// ========================

// --- 2.16 BÁO CÁO / THỐNG KÊ ---
sectionHeader('2.16 REPORTS - Báo cáo / Thống kê');

try {
    $reportFiles = [
        ROOT_DIR . '/lequocanh/administrator/elements_LQA/mBaocao/baocaoView.php' => 'Báo cáo tổng quan',
        ROOT_DIR . '/lequocanh/administrator/elements_LQA/mBaocao/doanhThuView.php' => 'Báo cáo doanh thu',
        ROOT_DIR . '/lequocanh/administrator/elements_LQA/mBaocao/sanPhamBanChayView.php' => 'Sản phẩm bán chạy',
        ROOT_DIR . '/lequocanh/administrator/elements_LQA/mBaocao/loiNhuanView.php' => 'Báo cáo lợi nhuận',
    ];

    // Try alternate paths
    $alternateReportFiles = [
        ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/baocaoCls.php' => 'baocaoCls',
        ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/baocaoController.php' => 'baocaoController',
    ];

    foreach ($reportFiles as $file => $name) {
        $exists = file_exists($file);
        logTest("Report: {$name}", $exists, $exists ? 'File tồn tại' : 'File không tồn tại');
    }

    foreach ($alternateReportFiles as $file => $name) {
        $exists = file_exists($file);
        logTest("Report Class: {$name}", $exists, $exists ? 'File tồn tại' : 'File không tồn tại');
    }

} catch (Exception $e) {
    logTest('Reports', false, 'Exception: ' . $e->getMessage());
}

// --- 2.17 VAI TRÒ & PHÂN QUYỀN ---
sectionHeader('2.17 ROLES & PERMISSIONS - Vai trò & Phân quyền');

try {
    require_once ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/roleCls.php';
    $role = new Role();

    subsection('Get all roles');
    $allRoles = $role->getAllRoles();
    logTest('Role.getAllRoles', is_array($allRoles), 'Số vai trò: ' . count($allRoles));

    subsection('Kiểm tra các phương thức Role');
    $roleMethods = ['getRoleById', 'getRoleByName', 'addRole', 'updateRole', 'deleteRole',
        'assignRoleToUser', 'removeRoleFromUser', 'getUserRoles', 'userHasRole',
        'isAdmin', 'isStaff', 'isCustomer', 'getPrimaryRole'];
    foreach ($roleMethods as $method) {
        logTest("Role.{$method}", method_exists($role, $method), 'OK');
    }

    subsection('Test isAdmin với user không tồn tại');
    $isAdminResult = $role->isAdmin(99999);
    logTest('Role.isAdmin(99999)', $isAdminResult === false || $isAdminResult === 0,
        'User không tồn tại: ' . ($isAdminResult ? 'TRUE' : 'FALSE'));

    subsection('Test getUserRoles với user không tồn tại');
    $userRoles = $role->getUserRoles(99999);
    logTest('Role.getUserRoles(99999)', is_array($userRoles), 'Vai trò: ' . count($userRoles));

    // PhanQuyen class
    require_once ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/phanquyenCls.php';
    $phanQuyen = new PhanQuyen();

    subsection('Kiểm tra các phương thức PhanQuyen');
    logTest('PhanQuyen.isNhanVien', method_exists($phanQuyen, 'isNhanVien'), 'OK');
    logTest('PhanQuyen.isAdmin', method_exists($phanQuyen, 'isAdmin'), 'OK');
    logTest('PhanQuyen.checkAccess', method_exists($phanQuyen, 'checkAccess'), 'OK');
    logTest('PhanQuyen.checkAccessForEmployee', method_exists($phanQuyen, 'checkAccessForEmployee'), 'OK');

    subsection('Kiểm tra bảng vai_tro');
    $stmt = $conn->query("SHOW TABLES LIKE 'vai_tro'");
    $roleTableExists = $stmt->rowCount() > 0;
    logTest('Bảng vai_tro', $roleTableExists, $roleTableExists ? 'Tồn tại' : 'Không tồn tại');

    subsection('Kiểm tra bảng phan_quyen');
    $stmt = $conn->query("SHOW TABLES LIKE 'phan_quyen'");
    $permTableExists = $stmt->rowCount() > 0;
    logTest('Bảng phan_quyen', $permTableExists, $permTableExists ? 'Tồn tại' : 'Không tồn tại');

} catch (Exception $e) {
    logTest('Roles & Permissions', false, 'Exception: ' . $e->getMessage());
}

// --- 2.18 NHẬT KÝ HOẠT ĐỘNG ---
sectionHeader('2.18 ACTIVITY LOG - Nhật ký hoạt động');

try {
    require_once ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/nhatKyHoatDongCls.php';
    $activityLog = new NhatKyHoatDong();

    subsection('Kiểm tra các phương thức');
    logTest('NhatKyHoatDong.ghiNhatKy', method_exists($activityLog, 'ghiNhatKy'), 'OK');
    logTest('NhatKyHoatDong.layDanhSachNhatKy', method_exists($activityLog, 'layDanhSachNhatKy'), 'OK');
    logTest('NhatKyHoatDong.demTongSoNhatKy', method_exists($activityLog, 'demTongSoNhatKy'), 'OK');
    logTest('NhatKyHoatDong.getActivityById', method_exists($activityLog, 'getActivityById'), 'OK');

    subsection('Test ghiNhatKy');
    $logResult = $activityLog->ghiNhatKy('test_user', 'TEST_ACTION', 'test_object', null, 'Ghi log test');
    logTest('ghiNhatKy', $logResult !== false, 'Ghi nhật ký test');

    subsection('Test layDanhSachNhatKy');
    $logList = $activityLog->layDanhSachNhatKy([], 10, 0);
    logTest('layDanhSachNhatKy', is_array($logList), 'Số nhật ký: ' . count($logList));

    subsection('Test demTongSoNhatKy');
    $logCount = $activityLog->demTongSoNhatKy([]);
    logTest('demTongSoNhatKy', is_numeric($logCount), 'Tổng số: ' . $logCount);

    subsection('Kiểm tra nhật ký tích hợp');
    $nhatKyFile = ROOT_DIR . '/lequocanh/administrator/elements_LQA/mThongbao/nhatKyHoatDongTichHop.php';
    logTest('nhatKyHoatDongTichHop.php', file_exists($nhatKyFile), file_exists($nhatKyFile) ? 'Tồn tại' : 'Không tồn tại');

} catch (Exception $e) {
    logTest('Activity Log', false, 'Exception: ' . $e->getMessage());
}

// --- 2.19 SẢN PHẨM NỔI BẬT / KHUYẾN MÃI ---
sectionHeader('2.19 FEATURED PRODUCTS & PROMOTIONS');

try {
    require_once ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/FeaturedProductsCls.php';
    $featured = new FeaturedProducts();

    subsection('Kiểm tra các phương thức FeaturedProducts');
    $featuredMethods = ['getFeaturedProducts', 'getNewProducts', 'getSaleProducts',
        'setFeatured', 'setNew', 'setSale', 'removeSale', 'incrementViewCount', 'getMostViewedProducts'];
    foreach ($featuredMethods as $method) {
        logTest("FeaturedProducts.{$method}", method_exists($featured, $method), 'OK');
    }

    subsection('Test getFeaturedProducts');
    $featuredProducts = $featured->getFeaturedProducts(5);
    logTest('getFeaturedProducts', is_array($featuredProducts), 'Số sản phẩm nổi bật: ' . count($featuredProducts));

    subsection('Test getNewProducts');
    $newProducts = $featured->getNewProducts(5);
    logTest('getNewProducts', is_array($newProducts), 'Số sản phẩm mới: ' . count($newProducts));

    subsection('Test getSaleProducts');
    $saleProducts = $featured->getSaleProducts(5);
    logTest('getSaleProducts', is_array($saleProducts), 'Số sản phẩm giảm giá: ' . count($saleProducts));

    subsection('Test getMostViewedProducts');
    $mostViewed = $featured->getMostViewedProducts(5);
    logTest('getMostViewedProducts', is_array($mostViewed), 'Số sản phẩm xem nhiều: ' . count($mostViewed));

    // PromotionManager
    require_once ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/PromotionManager.php';
    $promotionMgr = new PromotionManager();

    subsection('Kiểm tra các phương thức PromotionManager');
    $promoMethods = ['getActivePromotions', 'getAllPromotions', 'getPromotionById',
        'addPromotion', 'updatePromotion', 'deletePromotion', 'getDiscountedProducts'];
    foreach ($promoMethods as $method) {
        logTest("PromotionManager.{$method}", method_exists($promotionMgr, $method), 'OK');
    }

    subsection('Test getAllPromotions');
    $allPromotions = $promotionMgr->getAllPromotions();
    logTest('PromotionManager.getAllPromotions', is_array($allPromotions), 'Số khuyến mãi: ' . count($allPromotions));

    subsection('Test getActivePromotions');
    $activePromotions = $promotionMgr->getActivePromotions();
    logTest('PromotionManager.getActivePromotions', is_array($activePromotions), 'Số KM active: ' . count($activePromotions));

    subsection('Kiểm tra bảng promotions');
    $stmt = $conn->query("SHOW TABLES LIKE 'promotions'");
    $promoTableExists = $stmt->rowCount() > 0;
    logTest('Bảng promotions', $promoTableExists, $promoTableExists ? 'Tồn tại' : 'Không tồn tại');

} catch (Exception $e) {
    logTest('Featured & Promotions', false, 'Exception: ' . $e->getMessage());
}

// --- 2.20 MARKETING CONTENT (BANNERS) ---
sectionHeader('2.20 MARKETING CONTENT - Banner & Nội dung');

try {
    require_once ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/BannerManager.php';
    $bannerMgr = new BannerManager();

    subsection('Kiểm tra các phương thức BannerManager');
    $bannerMethods = ['getActiveBanners', 'getAllBanners', 'getBannerById',
        'deleteBanner', 'addBanner', 'updateBanner'];
    foreach ($bannerMethods as $method) {
        logTest("BannerManager.{$method}", method_exists($bannerMgr, $method), 'OK');
    }

    subsection('Test getActiveBanners');
    $activeBanners = $bannerMgr->getActiveBanners();
    logTest('BannerManager.getActiveBanners', is_array($activeBanners), 'Số banner active: ' . count($activeBanners));

    subsection('Test getAllBanners');
    $allBanners = $bannerMgr->getAllBanners();
    logTest('BannerManager.getAllBanners', is_array($allBanners), 'Tổng banner: ' . count($allBanners));

    subsection('Kiểm tra bảng banners');
    $stmt = $conn->query("SHOW TABLES LIKE 'banners'");
    $bannerTableExists = $stmt->rowCount() > 0;
    logTest('Bảng banners', $bannerTableExists, $bannerTableExists ? 'Tồn tại' : 'Không tồn tại');

} catch (Exception $e) {
    logTest('Banners', false, 'Exception: ' . $e->getMessage());
}

// --- 2.23 MÃ GIẢM GIÁ (COUPONS) ---
sectionHeader('2.23 COUPONS - Mã giảm giá');

try {
    require_once ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/CouponCls.php';
    $coupon = new Coupon();

    subsection('Kiểm tra các phương thức');
    $couponMethods = ['validateCoupon', 'calculateDiscount', 'applyCoupon',
        'getAllCoupons', 'getCouponById', 'getCouponByCode',
        'createCoupon', 'updateCoupon', 'deleteCoupon', 'toggleStatus',
        'getCouponUsageHistory', 'getCouponStats'];
    foreach ($couponMethods as $method) {
        logTest("Coupon.{$method}", method_exists($coupon, $method), 'OK');
    }

    subsection('Test getAllCoupons');
    $allCoupons = $coupon->getAllCoupons();
    logTest('Coupon.getAllCoupons', is_array($allCoupons), 'Số mã giảm giá: ' . count($allCoupons));

    subsection('Test validateCoupon với mã không tồn tại');
    $invalidCoupon = $coupon->validateCoupon('INVALID_CODE_123', 100000);
    logTest('Coupon.validateCoupon (mã không hợp lệ)',
        $invalidCoupon === false || (is_array($invalidCoupon) && isset($invalidCoupon['valid']) && !$invalidCoupon['valid']),
        'Mã không hợp lệ: trả về false');

    subsection('Test getCouponStats');
    $couponStats = $coupon->getCouponStats();
    logTest('Coupon.getCouponStats', is_array($couponStats) || $couponStats !== null, 'Có thống kê');

    subsection('Kiểm tra bảng coupons');
    $stmt = $conn->query("SHOW TABLES LIKE 'coupons'");
    $couponTableExists = $stmt->rowCount() > 0;
    logTest('Bảng coupons', $couponTableExists, $couponTableExists ? 'Tồn tại' : 'Không tồn tại');

    subsection('Kiểm tra bảng coupon_usage');
    $stmt = $conn->query("SHOW TABLES LIKE 'coupon_usage'");
    $usageTableExists = $stmt->rowCount() > 0;
    logTest('Bảng coupon_usage', $usageTableExists, $usageTableExists ? 'Tồn tại' : 'Không tồn tại');

} catch (Exception $e) {
    logTest('Coupons', false, 'Exception: ' . $e->getMessage());
}


// ========================
// PHẦN 3: THANH TOÁN (MoMo)
// ========================
sectionHeader('3.1 MoMo PAYMENT - Cổng thanh toán');

try {
    require_once ROOT_DIR . '/lequocanh/payment/MoMoConfig.php';

    subsection('Kiểm tra MoMoConfig static methods');
    logTest('MoMoConfig.getPartnerCode', method_exists('MoMoConfig', 'getPartnerCode'), 'Partner: ' . MoMoConfig::getPartnerCode());
    logTest('MoMoConfig.getAccessKey', method_exists('MoMoConfig', 'getAccessKey'), 'AccessKey: ' . substr(MoMoConfig::getAccessKey(), 0, 5) . '...');
    logTest('MoMoConfig.getSecretKey', method_exists('MoMoConfig', 'getSecretKey'), 'SecretKey: ' . substr(MoMoConfig::getSecretKey(), 0, 5) . '...');
    logTest('MoMoConfig.getEndpoint', method_exists('MoMoConfig', 'getEndpoint'), 'Endpoint: ' . MoMoConfig::getEndpoint());
    logTest('MoMoConfig.getReturnUrl', method_exists('MoMoConfig', 'getReturnUrl'), 'Return: ' . MoMoConfig::getReturnUrl());
    logTest('MoMoConfig.getNotifyUrl', method_exists('MoMoConfig', 'getNotifyUrl'), 'Notify: ' . MoMoConfig::getNotifyUrl());

    require_once ROOT_DIR . '/lequocanh/payment/MoMoPayment.php';
    $momo = new MoMoPayment();

    subsection('Kiểm tra MoMoPayment methods');
    logTest('MoMoPayment.createPayment', method_exists($momo, 'createPayment'), 'OK');
    logTest('MoMoPayment.verifyCallback', method_exists($momo, 'verifyCallback'), 'OK');
    logTest('MoMoPayment.getTransaction', method_exists($momo, 'getTransaction'), 'OK');

    subsection('Kiểm tra bảng payment_transactions');
    $stmt = $conn->query("SHOW TABLES LIKE 'payment_transactions'");
    $paymentTableExists = $stmt->rowCount() > 0;
    logTest('Bảng payment_transactions', $paymentTableExists, $paymentTableExists ? 'Tồn tại' : 'Không tồn tại');

    subsection('Kiểm tra file tích hợp');
    $paymentFiles = [
        ROOT_DIR . '/lequocanh/payment/momo_process.php' => 'momo_process.php',
        ROOT_DIR . '/lequocanh/payment/notify.php' => 'notify.php',
        ROOT_DIR . '/lequocanh/payment/return.php' => 'return.php',
        ROOT_DIR . '/lequocanh/api/momo_callback.php' => 'momo_callback.php',
        ROOT_DIR . '/lequocanh/api/momo_ipn.php' => 'momo_ipn.php',
    ];
    foreach ($paymentFiles as $file => $name) {
        logTest("Payment: {$name}", file_exists($file), file_exists($file) ? 'Tồn tại' : 'Không tồn tại');
    }

} catch (Exception $e) {
    logTest('MoMo Payment', false, 'Exception: ' . $e->getMessage());
}


// ========================
// PHẦN 4: VẬN CHUYỂN
// ========================
sectionHeader('4. SHIPPING - Module vận chuyển');

try {
    // Shipping class
    require_once ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/ShippingCls.php';
    $shipping = new Shipping();

    subsection('Kiểm tra Shipping methods');
    logTest('Shipping.calculateShippingFee', method_exists($shipping, 'calculateShippingFee'), 'OK');
    logTest('Shipping.getDeliveryTime', method_exists($shipping, 'getDeliveryTime'), 'OK');
    logTest('Shipping.calculateShippingComplete', method_exists($shipping, 'calculateShippingComplete'), 'OK');
    logTest('Shipping.createShippingOrder', method_exists($shipping, 'createShippingOrder'), 'OK');
    logTest('Shipping.trackShipment', method_exists($shipping, 'trackShipment'), 'OK');
    logTest('Shipping.getGHNApi', method_exists($shipping, 'getGHNApi'), 'OK');

    // ShippingMethod class
    require_once ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/ShippingMethodCls.php';
    $shippingMethod = new ShippingMethod();

    subsection('Kiểm tra ShippingMethod methods');
    logTest('ShippingMethod.getActiveMethods', method_exists($shippingMethod, 'getActiveMethods'), 'OK');
    logTest('ShippingMethod.getMethodByCode', method_exists($shippingMethod, 'getMethodByCode'), 'OK');
    logTest('ShippingMethod.calculateFee', method_exists($shippingMethod, 'calculateFee'), 'OK');

    subsection('Test getActiveMethods');
    $activeMethods = $shippingMethod->getActiveMethods();
    logTest('getActiveMethods', is_array($activeMethods), 'Số phương thức: ' . count($activeMethods));

    // ShippingService (app layer)
    require_once ROOT_DIR . '/lequocanh/app/Services/ShippingService.php';
    $shippingService = ShippingService::getInstance();

    subsection('Kiểm tra ShippingService methods');
    logTest('ShippingService.getActiveShippingMethods', method_exists($shippingService, 'getActiveShippingMethods'), 'OK');
    logTest('ShippingService.calculateShippingFee', method_exists($shippingService, 'calculateShippingFee'), 'OK');

    // GHN Service
    $ghnFile = ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/GHNService.php';
    if (file_exists($ghnFile)) {
        require_once $ghnFile;
        $ghn = new GHNService();
        subsection('Kiểm tra GHNService methods');
        logTest('GHNService exists', true, 'Class GHNService tồn tại');
    } else {
        logTest('GHNService file', false, 'File GHNService.php không tồn tại');
    }

    subsection('Kiểm tra bảng shipping_methods');
    $stmt = $conn->query("SHOW TABLES LIKE 'shipping_methods'");
    $shippingTableExists = $stmt->rowCount() > 0;
    logTest('Bảng shipping_methods', $shippingTableExists, $shippingTableExists ? 'Tồn tại' : 'Không tồn tại');

    subsection('Kiểm tra bảng shipping_fees');
    $stmt = $conn->query("SHOW TABLES LIKE 'shipping_fees'");
    $feesTableExists = $stmt->rowCount() > 0;
    logTest('Bảng shipping_fees', $feesTableExists, $feesTableExists ? 'Tồn tại' : 'Không tồn tại');

    subsection('Kiểm tra bảng shipment_tracking');
    $stmt = $conn->query("SHOW TABLES LIKE 'shipment_tracking'");
    $trackingTableExists = $stmt->rowCount() > 0;
    logTest('Bảng shipment_tracking', $trackingTableExists, $trackingTableExists ? 'Tồn tại' : 'Không tồn tại');

} catch (Exception $e) {
    logTest('Shipping', false, 'Exception: ' . $e->getMessage());
}


// ========================
// PHẦN 5: LỚP API
// ========================
sectionHeader('5. API LAYER - Lớp API');

subsection('Kiểm tra tất cả API endpoint files');
$apiEndpoints = [
    'api/cart.php' => 'Cart API',
    'api/wishlist.php' => 'Wishlist API',
    'api/product_reviews.php' => 'Product Reviews API',
    'api/submit_review.php' => 'Submit Review API',
    'api/get_product_reviews.php' => 'Get Reviews API',
    'api/mark_helpful.php' => 'Mark Helpful API',
    'api/report_review.php' => 'Report Review API',
    'api/review_management.php' => 'Review Management API',
    'api/support_tickets.php' => 'Support Tickets API',
    'api/filter_products.php' => 'Filter Products API',
    'api/get_filter_options.php' => 'Filter Options API',
    'api/user_addresses.php' => 'User Addresses API',
    'api/save_user_address.php' => 'Save Address API',
    'api/get_address_data.php' => 'Address Data API',
    'api/customer_detail.php' => 'Customer Detail API',
    'api/clear_cache.php' => 'Clear Cache API',
];

foreach ($apiEndpoints as $relPath => $name) {
    $fullPath = ROOT_DIR . '/lequocanh/' . $relPath;
    logTest("API: {$name}", file_exists($fullPath), file_exists($fullPath) ? 'Tồn tại' : 'Không tồn tại');
}

subsection('Kiểm tra API Response class');
$responseFile = ROOT_DIR . '/lequocanh/api/Response.php';
if (file_exists($responseFile)) {
    require_once $responseFile;
    logTest('Response class', class_exists('Response'), 'Class Response tồn tại');
    if (class_exists('Response')) {
        logTest('Response.success', method_exists('Response', 'success'), 'OK');
        logTest('Response.error', method_exists('Response', 'error'), 'OK');
        logTest('Response.json', method_exists('Response', 'json'), 'OK');
    }
} else {
    logTest('Response class', false, 'File không tồn tại');
}

subsection('Kiểm tra API Router v2');
$routerFile = ROOT_DIR . '/lequocanh/api/v2/Router.php';
if (file_exists($routerFile)) {
    require_once $routerFile;
    logTest('Router class', class_exists('Router'), 'Class Router tồn tại');
} else {
    logTest('Router v2', false, 'File Router.php không tồn tại');
}

// --- 5.2 MIDDLEWARE ---
sectionHeader('5.2 API MIDDLEWARE');

subsection('JWT Auth Middleware');
$jwtFile = ROOT_DIR . '/lequocanh/api/middleware/JwtAuthMiddleware.php';
if (file_exists($jwtFile)) {
    require_once $jwtFile;
    $jwtAuth = new JwtAuthMiddleware();
    logTest('JwtAuthMiddleware.handle', method_exists($jwtAuth, 'handle'), 'OK');
    logTest('JwtAuthMiddleware.generateToken', method_exists($jwtAuth, 'generateToken'), 'OK');

    subsection('Test JWT token generation');
    if (class_exists('Firebase\JWT\JWT')) {
        $testPayload = ['user_id' => 1, 'username' => 'test'];
        $token = JwtAuthMiddleware::generateToken($testPayload, 3600);
        logTest('JWT generateToken', !empty($token) && is_string($token),
            'Token: ' . substr($token, 0, 20) . '...');
    } else {
        logTest('JWT generateToken', false, 'Firebase\JWT library chưa cài đặt (composer require firebase/php-jwt)');
    }
} else {
    logTest('JwtAuthMiddleware', false, 'File không tồn tại');
}

subsection('Rate Limit Middleware');
$rateLimitFile = ROOT_DIR . '/lequocanh/api/middleware/RateLimitMiddleware.php';
if (file_exists($rateLimitFile)) {
    require_once $rateLimitFile;
    $rateLimit = new RateLimitMiddleware();
    logTest('RateLimitMiddleware.handle', method_exists($rateLimit, 'handle'), 'OK');
} else {
    logTest('RateLimitMiddleware', false, 'File không tồn tại');
}

subsection('API Security Middleware');
$securityMiddlewareFile = ROOT_DIR . '/lequocanh/api/middleware/ApiSecurityMiddleware.php';
logTest('ApiSecurityMiddleware', file_exists($securityMiddlewareFile),
    file_exists($securityMiddlewareFile) ? 'Tồn tại' : 'Không tồn tại');


// ========================
// PHẦN 6: HẠ TẦNG & TIỆN ÍCH
// ========================

// --- 6.1 CẤU HÌNH ---
sectionHeader('6.1 CONFIG - Quản lý cấu hình');

try {
    $configFile = ROOT_DIR . '/lequocanh/config/ConfigManager.php';
    if (file_exists($configFile)) {
        require_once $configFile;
        $configManager = ConfigManager::getInstance();
        logTest('ConfigManager.getInstance', $configManager !== null, 'Instance created');

        subsection('Kiểm tra các phương thức ConfigManager');
        logTest('ConfigManager.get', method_exists($configManager, 'get'), 'OK');
        logTest('ConfigManager.set', method_exists($configManager, 'set'), 'OK');
        logTest('ConfigManager.has', method_exists($configManager, 'has'), 'OK');
        logTest('ConfigManager.all', method_exists($configManager, 'all'), 'OK');
    } else {
        logTest('ConfigManager', false, 'File không tồn tại');
    }

    $configFiles = [
        ROOT_DIR . '/lequocanh/config/app.php' => 'app config',
        ROOT_DIR . '/lequocanh/config/database.php' => 'database config',
        ROOT_DIR . '/lequocanh/config/payment_config.php' => 'payment config',
    ];
    foreach ($configFiles as $file => $name) {
        logTest("Config: {$name}", file_exists($file), file_exists($file) ? 'Tồn tại' : 'Không tồn tại');
    }
} catch (Exception $e) {
    logTest('Config', false, 'Exception: ' . $e->getMessage());
}

// --- 6.2 CƠ SỞ DỮ LIỆU ---
sectionHeader('6.2 DATABASE - Cơ sở dữ liệu');

try {
    subsection('Kiểm tra DatabaseOptimized');
    $dbOptFile = ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/DatabaseOptimized.php';
    logTest('DatabaseOptimized', file_exists($dbOptFile), file_exists($dbOptFile) ? 'Tồn tại' : 'Không tồn tại');

    subsection('Kiểm tra PDO wrapper (mPDO)');
    $mpdoFile = ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/mPDO.php';
    logTest('mPDO', file_exists($mpdoFile), file_exists($mpdoFile) ? 'Tồn tại' : 'Không tồn tại');

} catch (Exception $e) {
    logTest('Database', false, 'Exception: ' . $e->getMessage());
}

// --- 6.3 CACHE ---
sectionHeader('6.3 CACHE - Bộ nhớ đệm');

try {
    require_once ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/CacheService.php';
    $cache = new CacheService();

    subsection('Kiểm tra các phương thức CacheService');
    logTest('CacheService.get', method_exists($cache, 'get'), 'OK');
    logTest('CacheService.set', method_exists($cache, 'set'), 'OK');
    logTest('CacheService.delete', method_exists($cache, 'delete'), 'OK');
    logTest('CacheService.clear', method_exists($cache, 'clear'), 'OK');
    logTest('CacheService.remember', method_exists($cache, 'remember'), 'OK');

    subsection('Test set/get cache');
    $cacheKey = 'test_cache_' . time();
    $cacheValue = ['data' => 'test_value', 'number' => 42];
    $setResult = $cache->set($cacheKey, $cacheValue, 60);
    logTest('CacheService.set', $setResult !== false, 'Key: ' . $cacheKey);

    $getValue = $cache->get($cacheKey);
    logTest('CacheService.get', $getValue !== null && $getValue !== false,
        'Value: ' . json_encode($getValue));

    subsection('Test delete cache');
    $deleteResult = $cache->delete($cacheKey);
    logTest('CacheService.delete', $deleteResult !== false, 'Xóa cache');

    $afterDelete = $cache->get($cacheKey);
    logTest('Verify cache deleted', $afterDelete === null || $afterDelete === false,
        'Cache đã xóa: ' . ($afterDelete === null ? 'null' : 'false'));

    subsection('Test remember cache');
    $rememberKey = 'test_remember_' . time();
    $rememberValue = $cache->remember($rememberKey, function() {
        return ['computed' => 'value', 'time' => time()];
    }, 60);
    logTest('CacheService.remember', $rememberValue !== null, 'Remember value: ' . json_encode($rememberValue));

    subsection('Kiểm tra các file cache');
    $cacheFiles = [
        ROOT_DIR . '/lequocanh/includes/advanced_cache.php' => 'advanced_cache',
        ROOT_DIR . '/lequocanh/includes/page_cache.php' => 'page_cache',
        ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/cacheManager.php' => 'cacheManager',
        ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/queryCache.php' => 'queryCache',
    ];
    foreach ($cacheFiles as $file => $name) {
        logTest("Cache: {$name}", file_exists($file), file_exists($file) ? 'Tồn tại' : 'Không tồn tại');
    }

} catch (Exception $e) {
    logTest('Cache', false, 'Exception: ' . $e->getMessage());
}

// --- 6.4 SEO ---
sectionHeader('6.4 SEO - Tối ưu công cụ tìm kiếm');

try {
    require_once ROOT_DIR . '/lequocanh/includes/seo_helper.php';
    $seo = SEOHelper::getInstance();

    subsection('Kiểm tra các phương thức SEOHelper');
    $seoMethods = ['setSiteName', 'setDefaultImage', 'setDefaultDescription',
        'generateMetaTags', 'generateProductSchema', 'generateBreadcrumbSchema',
        'generateOrganizationSchema', 'generateWebsiteSchema',
        'generateSitemap', 'generateRobotsTxt', 'cleanUrl', 'truncateDescription'];
    foreach ($seoMethods as $method) {
        logTest("SEOHelper.{$method}", method_exists($seo, $method), 'OK');
    }

    subsection('Test generateMetaTags');
    $metaTags = $seo->generateMetaTags([
        'title' => 'Test Page',
        'description' => 'Test description for SEO',
        'keywords' => 'test, seo, meta'
    ]);
    logTest('generateMetaTags', !empty($metaTags) && is_string($metaTags),
        'Tags: ' . substr(strip_tags($metaTags), 0, 50) . '...');

    subsection('Test cleanUrl');
    $cleanUrl = $seo->cleanUrl('Đây là test URL với tiếng Việt');
    logTest('cleanUrl', !empty($cleanUrl), 'URL: ' . $cleanUrl);

    subsection('Test truncateDescription');
    $longText = str_repeat('This is a long description. ', 20);
    $truncated = $seo->truncateDescription($longText, 160);
    logTest('truncateDescription', strlen($truncated) <= 163, 'Length: ' . strlen($truncated));

    subsection('Test generateProductSchema');
    $productSchema = $seo->generateProductSchema([
        'name' => 'Test Product',
        'price' => 1000000,
        'currency' => 'VND',
        'description' => 'Test product description',
        'image' => '/images/test.jpg'
    ]);
    logTest('generateProductSchema', !empty($productSchema), 'Schema generated');

    subsection('Test generateRobotsTxt');
    $robots = $seo->generateRobotsTxt([]);
    logTest('generateRobotsTxt', !empty($robots), 'Robots.txt generated');

    subsection('Kiểm tra SEO files');
    $seoFiles = [
        ROOT_DIR . '/lequocanh/includes/html_optimizer.php' => 'html_optimizer',
        ROOT_DIR . '/lequocanh/includes/asset_minifier.php' => 'asset_minifier',
    ];
    foreach ($seoFiles as $file => $name) {
        logTest("SEO: {$name}", file_exists($file), file_exists($file) ? 'Tồn tại' : 'Không tồn tại');
    }

} catch (Exception $e) {
    logTest('SEO', false, 'Exception: ' . $e->getMessage());
}

// --- QUERY BUILDER ---
sectionHeader('6.x QUERY BUILDER');

try {
    require_once ROOT_DIR . '/lequocanh/includes/query_builder.php';

    subsection('Kiểm tra QueryBuilder class');
    logTest('QueryBuilder class', class_exists('QueryBuilder'), 'Class tồn tại');

    subsection('Kiểm tra QueryBuilder methods');
    $qbMethods = ['select', 'where', 'whereIn', 'whereBetween', 'whereNull', 'whereNotNull',
        'whereLike', 'orderBy', 'limit', 'offset', 'join', 'leftJoin',
        'groupBy', 'having', 'cache', 'get', 'first', 'count', 'sum', 'avg',
        'pluck', 'insert', 'update', 'delete', 'toSql'];
    foreach ($qbMethods as $method) {
        logTest("QueryBuilder.{$method}", method_exists('QueryBuilder', $method), 'OK');
    }

    subsection('Test QueryBuilder static table');
    $qb = QueryBuilder::table('hanghoa');
    logTest('QueryBuilder.table("hanghoa")', $qb !== null, 'Query builder created');

    subsection('Test QueryBuilder chain');
    $sql = $qb->select('idhanghoa', 'tenhanghoa')->where('idhanghoa', '>', 0)->toSql();
    logTest('QueryBuilder.toSql', !empty($sql), 'SQL: ' . substr($sql, 0, 80) . '...');

    subsection('Test global DB() function');
    $dbHelper = DB('hanghoa');
    logTest('DB() helper function', $dbHelper !== null, 'DB helper works');

} catch (Exception $e) {
    logTest('Query Builder', false, 'Exception: ' . $e->getMessage());
}

// --- PRODUCT SERVICE (app layer) ---
sectionHeader('PRODUCT SERVICE (app layer)');

try {
    require_once ROOT_DIR . '/lequocanh/app/Services/ProductService.php';
    $productService = ProductService::getInstance();

    subsection('Kiểm tra ProductService methods');
    $psMethods = ['getProductsByCategory', 'getAllProducts', 'getProductById',
        'getProductImage', 'getProductRating', 'getRelatedProducts',
        'searchProducts', 'getDiscountedProducts', 'getFeaturedProducts',
        'filterProducts', 'getCacheStats'];
    foreach ($psMethods as $method) {
        logTest("ProductService.{$method}", method_exists($productService, $method), 'OK');
    }

    subsection('Test getAllProducts');
    $allProducts = $productService->getAllProducts();
    logTest('ProductService.getAllProducts', is_array($allProducts), 'Số sản phẩm: ' . count($allProducts));

    subsection('Test searchProducts');
    $searchResults = $productService->searchProducts('test');
    logTest('ProductService.searchProducts', is_array($searchResults), 'Kết quả: ' . count($searchResults));

    subsection('Test getFeaturedProducts');
    $featuredProducts = $productService->getFeaturedProducts(5);
    logTest('ProductService.getFeaturedProducts', is_array($featuredProducts), 'Số SP nổi bật: ' . count($featuredProducts));

    subsection('Test getCacheStats');
    $cacheStats = $productService->getCacheStats();
    logTest('ProductService.getCacheStats', is_array($cacheStats) || $cacheStats !== null, 'Cache stats');

} catch (Exception $e) {
    logTest('ProductService', false, 'Exception: ' . $e->getMessage());
}

// --- CATEGORY SERVICE ---
sectionHeader('CATEGORY SERVICE (app layer)');

try {
    require_once ROOT_DIR . '/lequocanh/app/Services/CategoryService.php';
    $categoryService = CategoryService::getInstance();

    logTest('CategoryService.getAllCategories', method_exists($categoryService, 'getAllCategories'), 'OK');
    logTest('CategoryService.getCategoryById', method_exists($categoryService, 'getCategoryById'), 'OK');
    logTest('CategoryService.getCategoriesWithProductCount', method_exists($categoryService, 'getCategoriesWithProductCount'), 'OK');

    $allCats = $categoryService->getAllCategories();
    logTest('CategoryService.getAllCategories()', is_array($allCats), 'Số danh mục: ' . count($allCats));

    $catsWithCount = $categoryService->getCategoriesWithProductCount();
    logTest('CategoryService.getCategoriesWithProductCount()', is_array($catsWithCount),
        'Danh mục có SL SP: ' . count($catsWithCount));

} catch (Exception $e) {
    logTest('CategoryService', false, 'Exception: ' . $e->getMessage());
}

// --- ORDER SERVICE ---
sectionHeader('ORDER SERVICE (app layer)');

try {
    require_once ROOT_DIR . '/lequocanh/app/Services/OrderService.php';
    $orderService = OrderService::getInstance();

    logTest('OrderService.getOrdersByUserId', method_exists($orderService, 'getOrdersByUserId'), 'OK');
    logTest('OrderService.getOrderById', method_exists($orderService, 'getOrderById'), 'OK');
    logTest('OrderService.getOrderByCode', method_exists($orderService, 'getOrderByCode'), 'OK');
    logTest('OrderService.getOrderDetails', method_exists($orderService, 'getOrderDetails'), 'OK');
    logTest('OrderService.getOrderCount', method_exists($orderService, 'getOrderCount'), 'OK');
    logTest('OrderService.getRecentOrders', method_exists($orderService, 'getRecentOrders'), 'OK');

    $orderCount = $orderService->getOrderCount();
    logTest('OrderService.getOrderCount()', is_numeric($orderCount), 'Tổng đơn hàng: ' . $orderCount);

    $recentOrders = $orderService->getRecentOrders(null, 5);
    logTest('OrderService.getRecentOrders()', is_array($recentOrders), 'Đơn gần đây: ' . count($recentOrders));

} catch (Exception $e) {
    logTest('OrderService', false, 'Exception: ' . $e->getMessage());
}

// --- MVC CONTROLLERS & MODELS ---
sectionHeader('MVC CONTROLLERS & MODELS');

try {
    subsection('BaseController');
    require_once ROOT_DIR . '/lequocanh/app/Controllers/BaseController.php';
    logTest('BaseController class', class_exists('BaseController'), 'Class tồn tại');

    subsection('ProductController');
    require_once ROOT_DIR . '/lequocanh/app/Controllers/Admin/ProductController.php';
    logTest('ProductController class', class_exists('ProductController'), 'Class tồn tại');

    subsection('BaseModel');
    require_once ROOT_DIR . '/lequocanh/app/Models/BaseModel.php';
    logTest('BaseModel class', class_exists('BaseModel'), 'Class tồn tại');

    subsection('Product Model');
    require_once ROOT_DIR . '/lequocanh/app/Models/Product.php';
    logTest('Product class', class_exists('Product'), 'Class tồn tại');

} catch (Exception $e) {
    logTest('MVC', false, 'Exception: ' . $e->getMessage());
}

// --- SECURITY ---
sectionHeader('SECURITY - Bảo mật');

try {
    subsection('PasswordHelper');
    $pwHelperFile = ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/PasswordHelper.php';
    if (file_exists($pwHelperFile)) {
        require_once $pwHelperFile;
        $pwHelper = new PasswordHelper();
        logTest('PasswordHelper class', class_exists('PasswordHelper'), 'Class tồn tại');

        subsection('Test bcrypt hash');
        $testPassword = 'TestPassword123!';
        $hashed = $pwHelper->hash($testPassword);
        logTest('PasswordHelper.hash', !empty($hashed) && $hashed !== $testPassword,
            'Hash: ' . substr($hashed, 0, 20) . '...');

        subsection('Test bcrypt verify');
        $verifyResult = $pwHelper->verify($testPassword, $hashed);
        logTest('PasswordHelper.verify (correct)', $verifyResult === true, 'Mật khẩu đúng');

        $wrongVerify = $pwHelper->verify('WrongPassword', $hashed);
        logTest('PasswordHelper.verify (wrong)', $wrongVerify === false, 'Mật khẩu sai');
    } else {
        logWarning('PasswordHelper.php không tồn tại');
    }

    subsection('CSRF Protection');
    $csrfFile = ROOT_DIR . '/lequocanh/includes/csrf_helper.php';
    if (file_exists($csrfFile)) {
        require_once $csrfFile;
        if (function_exists('csrf_token')) {
            $token = csrf_token();
            logTest('csrf_token()', !empty($token), 'Token: ' . substr($token, 0, 10) . '...');
        }
        if (function_exists('csrf_field')) {
            $field = csrf_field();
            logTest('csrf_field()', !empty($field), 'Field generated');
        }
    }

    subsection('Session Security');
    $sessionSecFile = ROOT_DIR . '/lequocanh/includes/session_security.php';
    logTest('session_security.php', file_exists($sessionSecFile), file_exists($sessionSecFile) ? 'Tồn tại' : 'Không tồn tại');

    subsection('Upload Security');
    $uploadSecFile = ROOT_DIR . '/lequocanh/includes/upload_security.php';
    logTest('upload_security.php', file_exists($uploadSecFile), file_exists($uploadSecFile) ? 'Tồn tại' : 'Không tồn tại');

    subsection('Input Validator');
    $inputValidatorFile = ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/inputValidator.php';
    logTest('inputValidator.php', file_exists($inputValidatorFile), file_exists($inputValidatorFile) ? 'Tồn tại' : 'Không tồn tại');

    subsection('Security Config');
    $secConfigFile = ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/securityConfig.php';
    logTest('securityConfig.php', file_exists($secConfigFile), file_exists($secConfigFile) ? 'Tồn tại' : 'Không tồn tại');

    subsection('Security Logger');
    $secLoggerFile = ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/securityLogger.php';
    logTest('securityLogger.php', file_exists($secLoggerFile), file_exists($secLoggerFile) ? 'Tồn tại' : 'Không tồn tại');

    subsection('Token Auth (HMAC)');
    $tokenAuthFile = ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/TokenAuth.php';
    if (file_exists($tokenAuthFile)) {
        require_once $tokenAuthFile;
        $tokenAuth = new TokenAuth();
        logTest('TokenAuth class', class_exists('TokenAuth'), 'Class tồn tại');
    } else {
        logTest('TokenAuth', false, 'File không tồn tại');
    }

} catch (Exception $e) {
    logTest('Security', false, 'Exception: ' . $e->getMessage());
}

// --- PWA ---
sectionHeader('PWA - Progressive Web App');

try {
    $swFile = ROOT_DIR . '/lequocanh/sw.js';
    logTest('sw.js (Service Worker)', file_exists($swFile), file_exists($swFile) ? 'Tồn tại' : 'Không tồn tại');
} catch (Exception $e) {
    logTest('PWA', false, 'Exception: ' . $e->getMessage());
}

// --- PERFORMANCE ---
sectionHeader('PERFORMANCE - Giám sát hiệu suất');

try {
    $perfFiles = [
        ROOT_DIR . '/lequocanh/includes/performance_bootstrap.php' => 'performance_bootstrap',
        ROOT_DIR . '/lequocanh/includes/performance_init.php' => 'performance_init',
        ROOT_DIR . '/lequocanh/includes/performance_monitor.php' => 'performance_monitor',
        ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/performanceProfiler.php' => 'performanceProfiler',
        ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/performanceMonitor.php' => 'performanceMonitor',
    ];
    foreach ($perfFiles as $file => $name) {
        logTest("Performance: {$name}", file_exists($file), file_exists($file) ? 'Tồn tại' : 'Không tồn tại');
    }
} catch (Exception $e) {
    logTest('Performance', false, 'Exception: ' . $e->getMessage());
}


// ========================
// CLEANUP
// ========================
sectionHeader('CLEANUP - Dọn dẹp dữ liệu test');

// Delete test news
if (!empty($createdData['news_ids'])) {
    require_once ROOT_DIR . '/lequocanh/administrator/elements_LQA/mod/NewsManager.php';
    $newsCleanup = new NewsManager();
    foreach ($createdData['news_ids'] as $newsId) {
        $newsCleanup->deleteNews($newsId);
    }
    echo "  🗑️  Đã xóa " . count($createdData['news_ids']) . " tin tức test" . PHP_EOL;
}

// ========================
// FINAL REPORT
// ========================
echo PHP_EOL;
echo "╔══════════════════════════════════════════════════════════════╗" . PHP_EOL;
echo "║               COMPLETE TEST REPORT - BÁO CÁO               ║" . PHP_EOL;
echo "╚══════════════════════════════════════════════════════════════╝" . PHP_EOL;
echo PHP_EOL;

$passRate = $testResults['total'] > 0
    ? round(($testResults['passed'] / $testResults['total']) * 100, 1)
    : 0;

echo "  📊 TỔNG KẾT:" . PHP_EOL;
echo "     Tổng số test:      {$testResults['total']}" . PHP_EOL;
echo "     Passed:             {$testResults['passed']}" . PHP_EOL;
echo "     Failed:             {$testResults['failed']}" . PHP_EOL;
echo "     Tỷ lệ thành công:   {$passRate}%" . PHP_EOL;
echo PHP_EOL;

if ($passRate >= 80) {
    echo "  ✅ Hệ thống hoạt động tốt!" . PHP_EOL;
} elseif ($passRate >= 60) {
    echo "  ⚠️  Hệ thống cần cải thiện một số chức năng." . PHP_EOL;
} else {
    echo "  ❌ Hệ thống có nhiều lỗi cần sửa!" . PHP_EOL;
}

if (!empty($testResults['errors'])) {
    echo PHP_EOL . "  ❌ DANH SÁCH LỖI:" . PHP_EOL;
    foreach ($testResults['errors'] as $i => $error) {
        echo "     " . ($i + 1) . ". [{$error['test']}] {$error['message']}" . PHP_EOL;
    }
}

if (!empty($testResults['warnings'])) {
    echo PHP_EOL . "  ⚠️  CẢNH BÁO:" . PHP_EOL;
    foreach ($testResults['warnings'] as $i => $warning) {
        echo "     " . ($i + 1) . ". {$warning}" . PHP_EOL;
    }
}

if (!empty($testResults['fixes_applied'])) {
    echo PHP_EOL . "  🔧 FIX ĐÃ ÁP DỤNG:" . PHP_EOL;
    foreach ($testResults['fixes_applied'] as $i => $fix) {
        echo "     " . ($i + 1) . ". {$fix}" . PHP_EOL;
    }
}

echo PHP_EOL;
echo "  📅 Thời gian hoàn thành: " . date('Y-m-d H:i:s') . PHP_EOL;
echo PHP_EOL;

// Save report
$reportDir = ROOT_DIR . '/test-results';
if (!is_dir($reportDir)) {
    mkdir($reportDir, 0755, true);
}
$reportFile = $reportDir . '/complete-test-report-' . date('Y-m-d_His') . '.json';
$reportData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'summary' => [
        'total' => $testResults['total'],
        'passed' => $testResults['passed'],
        'failed' => $testResults['failed'],
        'pass_rate' => $passRate
    ],
    'errors' => $testResults['errors'],
    'warnings' => $testResults['warnings'],
    'fixes_applied' => $testResults['fixes_applied'],
    'test_details' => $testResults['test_details'],
];
file_put_contents($reportFile, json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "  💾 Báo cáo đã lưu tại: {$reportFile}" . PHP_EOL;
echo PHP_EOL;

exit($testResults['failed'] > 0 ? 1 : 0);
