<?php
// Use SessionManager for safe session handling
require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

// Start session safely
SessionManager::start();

// Xác định đường dẫn đến file database.php
$paths = [
    '../mod/database.php',
    './elements_LQA/mod/database.php',
    './administrator/elements_LQA/mod/database.php'
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
    echo json_encode(['success' => false, 'message' => 'Không thể tải file database.php']);
    exit();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['USER'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit();
}

// Kiểm tra tham số đầu vào
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ']);
    exit();
}

$orderId = intval($_GET['id']);
$userId = $_SESSION['USER'];

// Kết nối database
$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Lấy thông tin đơn hàng
    $orderSql = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->execute([$orderId, $userId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn hàng này']);
        exit();
    }
    
    // Đánh dấu đơn hàng đã đọc
    $status = $order['status'];
    $field = '';
    
    switch ($status) {
        case 'pending':
            $field = 'pending_read';
            break;
        case 'approved':
            $field = 'approved_read';
            break;
        case 'cancelled':
            $field = 'cancelled_read';
            break;
    }
    
    if (!empty($field)) {
        $updateReadSql = "UPDATE orders SET $field = 1 WHERE id = ? AND user_id = ?";
        $updateReadStmt = $conn->prepare($updateReadSql);
        $updateReadStmt->execute([$orderId, $userId]);
    }
    
    // Lấy danh sách sản phẩm trong đơn hàng
    $orderItemsSql = "SELECT oi.*, h.tenhanghoa, h.hinhanh 
                     FROM order_items oi
                     JOIN hanghoa h ON oi.product_id = h.idhanghoa
                     WHERE oi.order_id = ?";
    $orderItemsStmt = $conn->prepare($orderItemsSql);
    $orderItemsStmt->execute([$orderId]);
    $orderItems = $orderItemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Định dạng lại thông tin đơn hàng
    $formattedOrder = [
        'id' => $order['id'],
        'order_code' => $order['order_code'],
        'total_amount' => $order['total_amount'],
        'status' => $order['status'],
        'status_text' => getStatusText($order['status']),
        'status_class' => getStatusClass($order['status']),
        'payment_method' => $order['payment_method'] == 'bank_transfer' ? 'Chuyển khoản ngân hàng' : $order['payment_method'],
        'created_at' => date('d/m/Y H:i', strtotime($order['created_at'])),
        'updated_at' => date('d/m/Y H:i', strtotime($order['updated_at'])),
        'shipping_address' => $order['shipping_address'] ?? '',
        'items' => []
    ];
    
    // Định dạng lại thông tin sản phẩm
    foreach ($orderItems as $item) {
        $formattedOrder['items'][] = [
            'id' => $item['id'],
            'product_id' => $item['product_id'],
            'product_name' => $item['tenhanghoa'],
            'product_image' => $item['hinhanh'],
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'total' => $item['price'] * $item['quantity']
        ];
    }
    
    // Trả về kết quả
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'order' => $formattedOrder
    ]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy thông tin đơn hàng: ' . $e->getMessage()]);
}

// Hàm lấy text trạng thái
function getStatusText($status) {
    switch ($status) {
        case 'pending':
            return 'Đang chờ xử lý';
        case 'approved':
            return 'Đã duyệt';
        case 'cancelled':
            return 'Đã hủy';
        default:
            return 'Không xác định';
    }
}

// Hàm lấy class CSS cho trạng thái
function getStatusClass($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'approved':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}
