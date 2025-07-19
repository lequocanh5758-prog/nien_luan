<?php
/**
 * MoMo Payment Configuration
 * Cấu hình thanh toán MoMo
 */

class MoMoConfig {
    // MoMo API Configuration
    const ENDPOINT = 'https://test-payment.momo.vn/v2/gateway/api/create'; // Test environment
    // const ENDPOINT = 'https://payment.momo.vn/v2/gateway/api/create'; // Production environment
    
    const QUERY_ENDPOINT = 'https://test-payment.momo.vn/v2/gateway/api/query'; // Test environment
    // const QUERY_ENDPOINT = 'https://payment.momo.vn/v2/gateway/api/query'; // Production environment
    
    const CONFIRM_ENDPOINT = 'https://test-payment.momo.vn/v2/gateway/api/confirm'; // Test environment
    // const CONFIRM_ENDPOINT = 'https://payment.momo.vn/v2/gateway/api/confirm'; // Production environment
    
    // Test credentials - Thay đổi thành thông tin thực tế khi deploy
    // Đây là thông tin test từ MoMo documentation
    const PARTNER_CODE = 'MOMO';
    const ACCESS_KEY = 'F8BBA842ECF85';
    const SECRET_KEY = 'K951B6PE1waDMi640xX08PD3vg6EkVlz';
    
    // Production credentials (uncomment when ready for production)
    // const PARTNER_CODE = 'YOUR_PARTNER_CODE';
    // const ACCESS_KEY = 'YOUR_ACCESS_KEY';
    // const SECRET_KEY = 'YOUR_SECRET_KEY';
    
    // Return URLs
    const RETURN_URL = 'http://localhost:8080/lequocanh/administrator/elements_LQA/mgiohang/momo_return.php';
    const NOTIFY_URL = 'http://localhost:8080/lequocanh/administrator/elements_LQA/mgiohang/momo_notify.php';
    
    // Request type
    const REQUEST_TYPE = 'payWithATM'; // payWithATM, payWithCC, captureWallet
    
    /**
     * Tạo chữ ký cho request MoMo
     */
    public static function createSignature($rawData) {
        return hash_hmac('sha256', $rawData, self::SECRET_KEY);
    }
    
    /**
     * Tạo request ID duy nhất
     */
    public static function generateRequestId() {
        return time() . '_' . uniqid();
    }
    
    /**
     * Tạo order ID duy nhất
     */
    public static function generateOrderId() {
        return 'ORDER_' . time() . '_' . rand(1000, 9999);
    }
    
    /**
     * Validate signature từ MoMo
     */
    public static function validateSignature($data, $signature) {
        $rawData = '';
        ksort($data);
        foreach ($data as $key => $value) {
            if ($key !== 'signature') {
                $rawData .= $key . '=' . $value . '&';
            }
        }
        $rawData = rtrim($rawData, '&');
        
        $expectedSignature = hash_hmac('sha256', $rawData, self::SECRET_KEY);
        return $signature === $expectedSignature;
    }
    
    /**
     * Log MoMo transaction
     */
    public static function logTransaction($type, $data) {
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
}

// Environment-specific configuration
if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production') {
    // Production configuration would go here
    // Update endpoints, credentials, and URLs for production
}
?>