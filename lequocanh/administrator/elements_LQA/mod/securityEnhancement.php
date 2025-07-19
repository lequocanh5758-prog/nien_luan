<?php
/**
 * Security Enhancement - Integrates CSRF protection and input validation
 * Priority: HIGH - Security improvement
 */

// Include required security components
require_once __DIR__ . '/csrfProtection.php';
require_once __DIR__ . '/inputValidator.php';

class SecurityEnhancement {
    
    /**
     * Initialize security for a page
     * 
     * @param bool $requireCSRF Whether to require CSRF protection
     * @param array $validationRules Input validation rules
     */
    public static function initializePage($requireCSRF = true, $validationRules = []) {
        // Start session safely
        if (class_exists('SessionManager')) {
            SessionManager::start();
        }
        
        // Initialize CSRF protection
        if ($requireCSRF) {
            self::initializeCSRF();
        }
        
        // Validate input if rules provided
        if (!empty($validationRules) && !empty($_POST)) {
            self::validateInput($_POST, $validationRules);
        }
        
        // Log security initialization
        if (class_exists('Logger')) {
            Logger::debug("Security initialized", [
                'csrf_enabled' => $requireCSRF,
                'validation_rules' => !empty($validationRules),
                'page' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);
        }
    }
    
    /**
     * Initialize CSRF protection
     */
    private static function initializeCSRF() {
        // Generate token for forms
        CSRFProtection::generateToken();
        
        // Validate token for POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                CSRFProtection::requireValidToken();
            } catch (Exception $e) {
                self::handleSecurityViolation('CSRF', $e->getMessage());
            }
        }
    }
    
    /**
     * Validate input data
     * 
     * @param array $data Input data
     * @param array $rules Validation rules
     */
    private static function validateInput($data, $rules) {
        $validator = new InputValidator();
        
        if (!$validator->validate($data, $rules)) {
            $errors = $validator->getErrors();
            
            if (class_exists('Logger')) {
                Logger::warning("Input validation failed", [
                    'errors' => $errors,
                    'data_keys' => array_keys($data)
                ]);
            }
            
            // Handle validation errors
            self::handleValidationErrors($errors);
        }
    }
    
    /**
     * Handle security violations
     * 
     * @param string $type Violation type
     * @param string $message Error message
     */
    private static function handleSecurityViolation($type, $message) {
        if (class_exists('Logger')) {
            Logger::error("Security violation detected", [
                'type' => $type,
                'message' => $message,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
            ]);
        }
        
        // Redirect to error page or show error
        if (class_exists('ResponseManager')) {
            ResponseManager::error('Security violation detected', null, 403);
        } else {
            http_response_code(403);
            die('Security violation detected');
        }
    }
    
    /**
     * Handle validation errors
     * 
     * @param array $errors Validation errors
     */
    private static function handleValidationErrors($errors) {
        if (class_exists('ResponseManager')) {
            ResponseManager::error('Validation failed', $errors, 400);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }
    }
    
    /**
     * Get security headers for forms
     * 
     * @return string HTML for security headers
     */
    public static function getFormSecurityHeaders() {
        $html = '';
        
        // CSRF token
        $html .= CSRFProtection::getHiddenField();
        
        // Meta tag for AJAX
        $html .= CSRFProtection::getMetaTag();
        
        return $html;
    }
    
    /**
     * Get JavaScript for AJAX security
     * 
     * @return string JavaScript code
     */
    public static function getAjaxSecurityScript() {
        return CSRFProtection::getAjaxScript();
    }
    
    /**
     * Sanitize and validate common form data
     * 
     * @param array $data Form data
     * @return array Sanitized data
     */
    public static function sanitizeFormData($data) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            // Skip CSRF token
            if ($key === CSRFProtection::TOKEN_NAME) {
                continue;
            }
            
            // Sanitize based on field type
            $sanitized[$key] = self::sanitizeByFieldName($key, $value);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize value based on field name
     * 
     * @param string $fieldName Field name
     * @param mixed $value Field value
     * @return mixed Sanitized value
     */
    private static function sanitizeByFieldName($fieldName, $value) {
        // Email fields
        if (strpos($fieldName, 'email') !== false) {
            return InputValidator::sanitizeValue($value, 'email');
        }
        
        // Phone fields
        if (strpos($fieldName, 'phone') !== false || strpos($fieldName, 'dienthoai') !== false) {
            return InputValidator::sanitizeValue($value, 'numeric');
        }
        
        // Price/money fields
        if (strpos($fieldName, 'gia') !== false || strpos($fieldName, 'price') !== false || 
            strpos($fieldName, 'tien') !== false || strpos($fieldName, 'money') !== false) {
            return InputValidator::sanitizeValue($value, 'float');
        }
        
        // Quantity fields
        if (strpos($fieldName, 'soluong') !== false || strpos($fieldName, 'quantity') !== false) {
            return InputValidator::sanitizeValue($value, 'integer');
        }
        
        // ID fields
        if (strpos($fieldName, 'id') !== false && is_numeric($value)) {
            return InputValidator::sanitizeValue($value, 'integer');
        }
        
        // Default string sanitization
        return InputValidator::sanitizeValue($value, 'string');
    }
    
    /**
     * Common validation rules for Vietnamese forms
     * 
     * @return array Common validation rules
     */
    public static function getCommonValidationRules() {
        return [
            'product_rules' => [
                'tenhanghoa' => 'required|min_length:2|max_length:255|no_script',
                'gia' => 'required|numeric|min_value:0',
                'soluong' => 'required|integer|min_value:0',
                'mota' => 'max_length:1000|no_script'
            ],
            'user_rules' => [
                'username' => 'required|min_length:3|max_length:50|alpha_numeric',
                'password' => 'required|min_length:6|max_length:255',
                'email' => 'required|email|max_length:255',
                'dienthoai' => 'phone|max_length:15'
            ],
            'order_rules' => [
                'diachi' => 'required|min_length:10|max_length:500|no_script',
                'ghichu' => 'max_length:500|no_script',
                'tongtien' => 'required|numeric|min_value:0'
            ]
        ];
    }
}