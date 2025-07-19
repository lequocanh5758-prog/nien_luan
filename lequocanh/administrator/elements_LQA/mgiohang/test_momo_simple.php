<?php
// MoMo Payment Integration
session_start();
header('Content-Type: application/json');

// Nhận dữ liệu từ frontend
$input = json_decode(file_get_contents('php://input'), true);

// Lưu thông tin đơn hàng vào session để hiển thị hóa đơn
$_SESSION['pending_order'] = [
    'amount' => $input['amount'] ?? 100000,
    'orderInfo' => $input['orderInfo'] ?? "Thanh toán đơn hàng",
    'shipping_address' => $input['shippingAddress'] ?? "Địa chỉ giao hàng",
    'timestamp' => time()
];

// MoMo Test Config
$partnerCode = "MOMO";
$accessKey = "F8BBA842ECF85";
$secretKey = "K951B6PE1waDMi640xX08PD3vg6EkVlz";
$endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";

// Tạo order info
$orderId = "ORDER_" . time();
$amount = $input['amount'] ?? 100000;
$orderInfo = $input['orderInfo'] ?? "Thanh toán đơn hàng";
$redirectUrl = "https://ba543590fcd8.ngrok-free.app/administrator/elements_LQA/mgiohang/test_return.php";
$ipnUrl = "https://ba543590fcd8.ngrok-free.app/administrator/elements_LQA/mgiohang/momo_ipn.php";
$requestId = time() . "";
$requestType = "captureWallet";
$extraData = "";

// Tạo signature
$rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&ipnUrl=" . $ipnUrl . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&partnerCode=" . $partnerCode . "&redirectUrl=" . $redirectUrl . "&requestId=" . $requestId . "&requestType=" . $requestType;
$signature = hash_hmac("sha256", $rawHash, $secretKey);

// Dữ liệu gửi đến MoMo
$data = array(
    'partnerCode' => $partnerCode,
    'partnerName' => "Test",
    'storeId' => "MomoTestStore",
    'requestId' => $requestId,
    'amount' => $amount,
    'orderId' => $orderId,
    'orderInfo' => $orderInfo,
    'redirectUrl' => $redirectUrl,
    'ipnUrl' => $ipnUrl,
    'lang' => 'vi',
    'extraData' => $extraData,
    'requestType' => $requestType,
    'signature' => $signature
);

// Gửi request đến MoMo
$result = execPostRequest($endpoint, json_encode($data));
$jsonResult = json_decode($result, true);

// Trả về kết quả
if (isset($jsonResult['payUrl'])) {
    echo json_encode([
        'success' => true,
        'payUrl' => $jsonResult['payUrl'],
        'message' => 'Tạo thanh toán MoMo thành công!'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi tạo thanh toán MoMo: ' . ($jsonResult['message'] ?? 'Unknown error'),
        'debug' => $jsonResult
    ]);
}

function execPostRequest($url, $data)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        )
    );
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
