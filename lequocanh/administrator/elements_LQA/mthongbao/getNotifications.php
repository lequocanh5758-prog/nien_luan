<?php
// Use SessionManager for safe session handling
require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

// Start session safely
SessionManager::start();

// Xác định đường dẫn đến file thongbaoCls.php
$paths = [
    '../mthongbao/thongbaoCls.php',
    './elements_LQA/mthongbao/thongbaoCls.php',
    './administrator/elements_LQA/mthongbao/thongbaoCls.php'
];

$loaded = false;
foreach ($paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Không thể tải file thongbaoCls.php']);
    exit();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['USER'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit();
}

$userId = $_SESSION['USER'];
$thongbao = new ThongBao();

// Xử lý đánh dấu đã đọc
if (isset($_POST['mark_read']) && isset($_POST['order_id']) && isset($_POST['status'])) {
    $orderId = intval($_POST['order_id']);
    $status = $_POST['status'];

    // Truyền thêm userId để đảm bảo chỉ đánh dấu đã đọc thông báo của người dùng hiện tại
    $result = $thongbao->markNotificationAsRead($orderId, $status, $userId);

    header('Content-Type: application/json');
    echo json_encode(['success' => $result]);
    exit();
}

// Xử lý đánh dấu tất cả đã đọc
if (isset($_POST['mark_all_read'])) {
    $result = $thongbao->markAllNotificationsAsRead($userId);

    header('Content-Type: application/json');
    echo json_encode(['success' => $result]);
    exit();
}

// Xử lý xóa thông báo đã đọc
if (isset($_POST['delete_read_notifications'])) {
    $result = $thongbao->deleteReadNotifications($userId);

    header('Content-Type: application/json');
    echo json_encode(['success' => $result]);
    exit();
}

// Xử lý xóa một thông báo cụ thể
if (isset($_POST['delete_notification']) && isset($_POST['order_id'])) {
    $orderId = intval($_POST['order_id']);
    $result = $thongbao->deleteNotification($orderId, $userId);

    header('Content-Type: application/json');
    echo json_encode(['success' => $result]);
    exit();
}

// Lấy danh sách thông báo
$notifications = $thongbao->getUserNotifications($userId);
$unreadCount = $thongbao->getUnreadNotificationCount($userId);

// Định dạng lại thông báo để hiển thị
$formattedNotifications = [];
foreach ($notifications as $notification) {
    $status = '';
    $icon = '';
    $color = '';

    switch ($notification['status']) {
        case 'pending':
            $status = 'Đang chờ xử lý';
            $icon = 'clock';
            $color = 'warning';
            break;
        case 'approved':
            $status = 'Đã duyệt';
            $icon = 'check-circle';
            $color = 'success';
            break;
        case 'cancelled':
            $status = 'Đã hủy';
            $icon = 'times-circle';
            $color = 'danger';
            break;
        default:
            $status = 'Không xác định';
            $icon = 'question-circle';
            $color = 'secondary';
    }

    $formattedNotifications[] = [
        'id' => $notification['id'],
        'order_code' => $notification['order_code'],
        'status' => $notification['status'],
        'status_text' => $status,
        'icon' => $icon,
        'color' => $color,
        'total_amount' => $notification['total_amount'],
        'created_at' => date('d/m/Y H:i', strtotime($notification['created_at'])),
        'updated_at' => date('d/m/Y H:i', strtotime($notification['updated_at'])),
        'is_read' => (bool)$notification['is_read']
    ];
}

// Trả về kết quả
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'unread_count' => $unreadCount,
    'notifications' => $formattedNotifications
]);
?>
