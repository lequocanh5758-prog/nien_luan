<?php

/**
 * Endpoint nhận thông báo từ MoMo khi thanh toán thành công/thất bại
 * Đây là IPN (Instant Payment Notification) URL
 */

require_once 'MoMoPayment.php';

// Log tất cả request để debug
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'post_data' => $_POST,
    'raw_input' => file_get_contents('php://input')
];

error_log('MoMo Notify Request: ' . json_encode($logData));

// Chỉ chấp nhận POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
    exit;
}

try {
    // Lấy dữ liệu từ MoMo
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Nếu không có dữ liệu JSON, thử lấy từ $_POST
    if (!$data) {
        $data = $_POST;
    }
    
    if (empty($data)) {
        throw new Exception('No data received');
    }
    
    // Log dữ liệu nhận được
    error_log('MoMo Notify Data: ' . json_encode($data));
    
    // Tạo instance MoMoPayment để verify
    $momoPayment = new MoMoPayment();
    
    // Verify callback từ MoMo
    $verifyResult = $momoPayment->verifyCallback($data);
    
    if ($verifyResult['success']) {
        // Signature hợp lệ
        $resultCode = $verifyResult['resultCode'];
        $orderId = $verifyResult['orderId'];
        $transId = $verifyResult['transId'];
        $message = $verifyResult['message'];
        
        if ($resultCode == 0) {
            // Thanh toán thành công
            error_log("MoMo Payment Success: OrderID=$orderId, TransID=$transId");
            
            // Có thể thêm logic xử lý business ở đây
            // Ví dụ: cập nhật trạng thái đơn hàng, gửi email, etc.
            
            // Response thành công cho MoMo
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Payment processed successfully'
            ]);
            
        } else {
            // Thanh toán thất bại
            error_log("MoMo Payment Failed: OrderID=$orderId, ResultCode=$resultCode, Message=$message");
            
            // Response thành công cho MoMo (đã nhận được thông báo)
            http_response_code(200);
            echo json_encode([
                'status' => 'received',
                'message' => 'Payment failure notification received'
            ]);
        }
        
    } else {
        // Signature không hợp lệ
        error_log('MoMo Notify: Invalid signature');
        
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid signature'
        ]);
    }
    
} catch (Exception $e) {
    // Lỗi xử lý
    error_log('MoMo Notify Error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error'
    ]);
}

// Đảm bảo response là JSON
header('Content-Type: application/json');

?>
