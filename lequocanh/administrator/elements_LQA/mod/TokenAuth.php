<?php

/**
 * Simple Token Authentication for notifications
 * Alternative to session-based auth for ngrok compatibility
 */
class TokenAuth
{
    private static $secretKey = 'lequocanh_notification_secret_2025';

    /**
     * Generate a simple token for user
     */
    public static function generateToken($username)
    {
        $payload = [
            'username' => $username,
            'timestamp' => time(),
            'expires' => time() + (24 * 60 * 60) // 24 hours
        ];

        $data = base64_encode(json_encode($payload));
        $signature = hash_hmac('sha256', $data, self::$secretKey);

        return $data . '.' . $signature;
    }

    /**
     * Verify and decode token
     */
    public static function verifyToken($token)
    {
        if (empty($token)) {
            return false;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return false;
        }

        list($data, $signature) = $parts;

        // Verify signature
        $expectedSignature = hash_hmac('sha256', $data, self::$secretKey);
        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }

        // Decode payload
        $payload = json_decode(base64_decode($data), true);
        if (!$payload) {
            return false;
        }

        // Check expiration
        if (isset($payload['expires']) && $payload['expires'] < time()) {
            return false;
        }

        return $payload;
    }

    /**
     * Get user from token in request
     */
    public static function getUserFromRequest()
    {
        // Try different sources for token
        $token = null;

        // 1. Authorization header
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'];
            if (strpos($auth, 'Bearer ') === 0) {
                $token = substr($auth, 7);
            }
        }

        // 2. X-Auth-Token header
        if (!$token && isset($_SERVER['HTTP_X_AUTH_TOKEN'])) {
            $token = $_SERVER['HTTP_X_AUTH_TOKEN'];
        }

        // 3. Query parameter
        if (!$token && isset($_GET['token'])) {
            $token = $_GET['token'];
        }

        // 4. POST parameter
        if (!$token && isset($_POST['token'])) {
            $token = $_POST['token'];
        }

        // 5. Cookie
        if (!$token && isset($_COOKIE['auth_token'])) {
            $token = $_COOKIE['auth_token'];
        }

        if ($token) {
            $payload = self::verifyToken($token);
            if ($payload && isset($payload['username'])) {
                return $payload['username'];
            }
        }

        return false;
    }

    /**
     * Set token in cookie
     */
    public static function setTokenCookie($username)
    {
        $token = self::generateToken($username);

        // Check if headers already sent
        if (headers_sent($file, $line)) {
            error_log("Cannot set cookie, headers already sent at $file:$line");
            return $token; // Return token anyway for manual setting
        }

        // Set cookie for 24 hours
        $expires = time() + (24 * 60 * 60);

        setcookie('auth_token', $token, [
            'expires' => $expires,
            'path' => '/',
            'domain' => '', // Empty for ngrok compatibility
            'secure' => false, // Allow HTTP for development
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        return $token;
    }

    /**
     * Clear token cookie
     */
    public static function clearTokenCookie()
    {
        setcookie('auth_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}
