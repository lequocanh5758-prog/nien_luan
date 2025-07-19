<?php
/**
 * MoMo Init Payment
 * Dựa trên official MoMo PHP SDK - init_payment.php
 */

header('Content-type: application/json');

// MoMo API configuration
$endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";

$partnerCode = 'MOMO';
$accessKey = 'F8BBA842ECF85';
$secretKey = 'K951B6PE1waDMi640xX08PD3vg6EkVlz';

// Get order information from POST data
$orderInfo = $_POST['orderInfo'] ?? "Thanh toán qua MoMo";
$amount = $_POST['amount'] ?? "10000";
$orderId = $_POST['orderId'] ?? time() . "";
$redirectUrl = $_POST['redirectUrl'] ?? "http://localhost:8080/lequocanh/administrator/elements_LQA/mgiohang/momo_return.php";
$ipnUrl = $_POST['ipnUrl'] ?? "http://localhost:8080/lequocanh/administrator/elements_LQA/mgiohang/momo_notify.php";
$extraData = $_POST['extraData'] ?? "";

$requestId = time() . "";
$requestType = "payWithATM";

// Create raw signature string
$rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&ipnUrl=" . $ipnUrl . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&partnerCode=" . $partnerCode . "&redirectUrl=" . $redirectUrl . "&requestId=" . $requestId . "&requestType=" . $requestType;
$signature = hash_hmac("sha256", $rawHash, $secretKey);

$data = array(
    'partnerCode' => $partnerCode,
    'partnerName' => "Test",
    "storeId" => "MomoTestStore",
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

$result = execPostRequest($endpoint, json_encode($data));
$jsonResult = json_decode($result, true);  // decode json

// Log the transaction
logMoMoTransaction('INIT_PAYMENT', [
    'request' => $data,
    'response' => $jsonResult
]);

// Return result
echo json_encode($jsonResult);

function execPostRequest($url, $data)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data))
    );
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    //execute post
    $result = curl_exec($ch);
    //close connection
    curl_close($ch);
    return $result;
}

function logMoMoTransaction($type, $data) {
    $logFile = __DIR__ . '/../logs/momo_transactions.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'type' => $type,
        'data' => $data
    ];
    
    file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
}
?>