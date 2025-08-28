<?php

/**
 * MoMo Payment Configuration
 * Cấu hình cho tích hợp thanh toán MoMo
 */

class MoMoConfig
{
    // Test environment credentials (sử dụng key công khai từ GitHub)
    const TEST_PARTNER_CODE = 'MOMO';
    const TEST_ACCESS_KEY = 'F8BBA842ECF85';
    const TEST_SECRET_KEY = 'K951B6PE1waDMi640xX08PD3vg6EkVlz';

    // Production environment (cần thay đổi khi deploy thực tế)
    const PROD_PARTNER_CODE = 'YOUR_PARTNER_CODE';
    const PROD_ACCESS_KEY = 'YOUR_ACCESS_KEY';
    const PROD_SECRET_KEY = 'YOUR_SECRET_KEY';

    // API URLs
    const TEST_ENDPOINT = 'https://test-payment.momo.vn/v2/gateway/api/create';
    const PROD_ENDPOINT = 'https://payment.momo.vn/v2/gateway/api/create';

    // Query API URLs
    const TEST_QUERY_ENDPOINT = 'https://test-payment.momo.vn/v2/gateway/api/query';
    const PROD_QUERY_ENDPOINT = 'https://payment.momo.vn/v2/gateway/api/query';

    // Environment setting
    const IS_PRODUCTION = false; // Đặt true khi deploy production

    /**
     * Lấy Partner Code theo environment
     */
    public static function getPartnerCode()
    {
        return self::IS_PRODUCTION ? self::PROD_PARTNER_CODE : self::TEST_PARTNER_CODE;
    }

    /**
     * Lấy Access Key theo environment
     */
    public static function getAccessKey()
    {
        return self::IS_PRODUCTION ? self::PROD_ACCESS_KEY : self::TEST_ACCESS_KEY;
    }

    /**
     * Lấy Secret Key theo environment
     */
    public static function getSecretKey()
    {
        return self::IS_PRODUCTION ? self::PROD_SECRET_KEY : self::TEST_SECRET_KEY;
    }

    /**
     * Lấy API Endpoint theo environment
     */
    public static function getEndpoint()
    {
        return self::IS_PRODUCTION ? self::PROD_ENDPOINT : self::TEST_ENDPOINT;
    }

    /**
     * Lấy Query API Endpoint theo environment
     */
    public static function getQueryEndpoint()
    {
        return self::IS_PRODUCTION ? self::PROD_QUERY_ENDPOINT : self::TEST_QUERY_ENDPOINT;
    }

    /**
     * Lấy base URL của website (cần cấu hình thủ công)
     */
    public static function getBaseUrl()
    {
        // Auto-detect current domain
        if (isset($_SERVER['HTTP_HOST'])) {
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            
            // Thêm header để bypass ngrok warning nếu là ngrok
            if (strpos($host, '.ngrok') !== false || strpos($host, '.ngrok-free.app') !== false) {
                if (!headers_sent()) {
                    header('ngrok-skip-browser-warning: true');
                }
            }
            
            return $protocol . '://' . $host;
        }
        
        // Fallback: Manual ngrok URL (cập nhật khi ngrok thay đổi)
        if (!headers_sent()) {
            header('ngrok-skip-browser-warning: true');
        }
        return 'https://35c18a86f8c6.ngrok-free.app';
    }



    /**
     * Lấy Return URL (trang người dùng sẽ được redirect sau khi thanh toán)
     */
    public static function getReturnUrl()
    {
        return self::getBaseUrl() . '/lequocanh/administrator/elements_LQA/mgiohang/momo_return.php';
    }

    /**
     * Lấy Notify URL (endpoint nhận thông báo từ MoMo)
     */
    public static function getNotifyUrl()
    {
        return self::getBaseUrl() . '/lequocanh/payment/notify.php';
    }
}
