<?php
/**
 * JWT Authentication Middleware
 * Phase 4 - Modern JWT implementation
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtAuthMiddleware {
    private $secret;
    
    public function __construct() {
        $this->secret = $_ENV['JWT_SECRET'] ?? 'default-secret-key';
    }
    
    public function handle() {
        $token = $this->extractToken();
        
        if (!$token) {
            $this->unauthorized('Token not provided');
        }
        
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            $_REQUEST['user'] = (array)$decoded;
            
        } catch (Exception $e) {
            $this->unauthorized('Invalid token: ' . $e->getMessage());
        }
    }
    
    private function extractToken() {
        $headers = getallheaders();
        
        // Check Authorization header
        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
                return $matches[1];
            }
        }
        
        // Check query parameter
        return $_GET['token'] ?? null;
    }
    
    private function unauthorized($message) {
        http_response_code(401);
        echo json_encode([
            'error' => 'Unauthorized',
            'message' => $message
        ]);
        exit;
    }
    
    public static function generateToken($payload, $expiry = 3600) {
        $secret = $_ENV['JWT_SECRET'] ?? 'default-secret-key';
        
        $payload['iat'] = time();
        $payload['exp'] = time() + $expiry;
        
        return JWT::encode($payload, $secret, 'HS256');
    }
}
