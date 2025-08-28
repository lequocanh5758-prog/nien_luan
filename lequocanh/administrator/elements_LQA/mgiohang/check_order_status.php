<?php
require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../mod/database.php';

// Start session safely
SessionManager::start();

// Set JSON response header
header('Content-Type: application/json');

// Check if order ID is provided
if (!isset($_GET['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'No order ID provided']);
    exit;
}

$orderId = intval($_GET['order_id']);

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Get order status
    $sql = "SELECT trang_thai, trang_thai_thanh_toan, ma_nguoi_dung 
            FROM don_hang 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    // Check if user is authorized to view this order
    if (isset($_SESSION['USER']) && $order['ma_nguoi_dung'] != $_SESSION['USER']) {
        // Check if user is admin
        if (!isset($_SESSION['ADMIN'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
    }
    
    echo json_encode([
        'success' => true,
        'order_status' => $order['trang_thai'],
        'payment_status' => $order['trang_thai_thanh_toan']
    ]);
    
} catch (Exception $e) {
    error_log("Error checking order status: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
