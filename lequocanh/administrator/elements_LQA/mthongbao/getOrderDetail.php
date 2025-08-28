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
    // Lấy thông tin đơn hàng - sử dụng bảng don_hang
    $orderSql = "SELECT * FROM don_hang WHERE id = ? AND ma_nguoi_dung = ?";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->execute([$orderId, $userId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn hàng này']);
        exit();
    }
    
    // Đánh dấu đơn hàng đã đọc - sử dụng bảng don_hang
    $status = $order['trang_thai']; // Đổi tên field
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
        // Kiểm tra xem cột có tồn tại không
        $checkColumnSql = "SHOW COLUMNS FROM don_hang LIKE '$field'";
        $checkColumnStmt = $conn->prepare($checkColumnSql);
        $checkColumnStmt->execute();
        
        if ($checkColumnStmt->rowCount() > 0) {
            $updateReadSql = "UPDATE don_hang SET $field = 1 WHERE id = ? AND ma_nguoi_dung = ?";
            $updateReadStmt = $conn->prepare($updateReadSql);
            $updateReadStmt->execute([$orderId, $userId]);
        }
    }
    
    // Lấy danh sách sản phẩm trong đơn hàng - sử dụng bảng chi_tiet_don_hang
    $orderItemsSql = "SELECT oi.*, h.tenhanghoa, h.hinhanh 
                     FROM chi_tiet_don_hang oi
                     JOIN hanghoa h ON oi.ma_san_pham = h.idhanghoa
                     WHERE oi.ma_don_hang = ?";
    $orderItemsStmt = $conn->prepare($orderItemsSql);
    $orderItemsStmt->execute([$orderId]);
    $orderItems = $orderItemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Định dạng lại thông tin đơn hàng - sử dụng field tiếng Việt
    $formattedOrder = [
        'id' => $order['id'],
        'order_code' => $order['ma_don_hang_text'],
        'total_amount' => $order['tong_tien'],
        'status' => $order['trang_thai'],
        'status_text' => getStatusText($order['trang_thai']),
        'status_class' => getStatusClass($order['trang_thai']),
        'payment_method' => $order['phuong_thuc_thanh_toan'] == 'bank_transfer' ? 'Chuyển khoản ngân hàng' : $order['phuong_thuc_thanh_toan'],
        'created_at' => date('d/m/Y H:i', strtotime($order['ngay_tao'])),
        'updated_at' => isset($order['ngay_cap_nhat']) ? date('d/m/Y H:i', strtotime($order['ngay_cap_nhat'])) : '',
        'shipping_address' => $order['dia_chi_giao_hang'] ?? '',
        'items' => []
    ];
    
    // Định dạng lại thông tin sản phẩm - sử dụng field tiếng Việt
    foreach ($orderItems as $item) {
        $formattedOrder['items'][] = [
            'id' => $item['id'],
            'product_id' => $item['ma_san_pham'],
            'product_name' => $item['tenhanghoa'],
            'product_image' => $item['hinhanh'],
            'quantity' => $item['so_luong'],
            'price' => $item['gia'],
            'total' => $item['gia'] * $item['so_luong']
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
