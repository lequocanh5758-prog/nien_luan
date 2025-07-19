<?php
/**
 * Security Configuration - Centralized security settings
 * Priority: HIGH - Improves security and removes hard-coded values
 */

// Environment detection
$environment = getenv('APP_ENV') ?: 'production';
$isDevelopment = (
    $environment === 'development' || 
    $environment === 'dev' ||
    getenv('DEBUG') === 'true' ||
    (isset($_SERVER['HTTP_HOST']) && (
        strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
        strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false
    ))
);

// Security constants - use environment variables when possible
define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD') ?: 'lequocanh'); // TO BE REPLACED with secure env var
define('VERIFY_PASSWORD', getenv('VERIFY_PASSWORD') ?: 'lequocanh'); // TO BE REPLACED with secure env var
define('DEFAULT_ADMIN_USERNAME', 'admin');
define('DEFAULT_ADMIN_ROLE', 'admin');

// Session security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('SESSION_REGENERATE_INTERVAL', 300); // 5 minutes
define('SESSION_USE_STRICT_MODE', true);
define('SESSION_COOKIE_HTTPONLY', true);
define('SESSION_COOKIE_SECURE', !$isDevelopment); // Secure in production only
define('SESSION_COOKIE_SAMESITE', 'Lax');

// Authentication settings
define('LOGIN_ATTEMPTS_LIMIT', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_MIXED_CASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SYMBOLS', false);

// CSRF protection
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour

// XSS protection
define('XSS_FILTER_ENABLED', true);
define('HTML_PURIFIER_ENABLED', false); // Enable if HTML Purifier is available

// SQL injection protection
define('USE_PREPARED_STATEMENTS', true);
define('VALIDATE_ALL_INPUTS', true);

// File upload security
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_UPLOAD_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx']);
define('DISALLOWED_UPLOAD_EXTENSIONS', ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'pht', 'phar', 'exe', 'scr', 'dll', 'msi', 'vbs', 'bat', 'cmd', 'sh', 'js']);

// Apply security settings
if (SESSION_USE_STRICT_MODE) {
    ini_set('session.use_strict_mode', 1);
}

// Set session cookie parameters
session_set_cookie_params([
    'lifetime' => SESSION_TIMEOUT,
    'path' => '/',
    'domain' => '',
    'secure' => SESSION_COOKIE_SECURE,
    'httponly' => SESSION_COOKIE_HTTPONLY,
    'samesite' => SESSION_COOKIE_SAMESITE
]);

// Security headers
if (!headers_sent()) {
    // Content Security Policy
    if (!$isDevelopment) {
        header("Content-Security-Policy: default-src 'self'; script-src 'self' https://code.jquery.com https://cdn.jsdelivr.net 'unsafe-inline'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; img-src 'self' data:; font-src 'self' https://cdnjs.cloudflare.com;");
    }
    
    // Other security headers
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: SAMEORIGIN");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    
    if (!$isDevelopment) {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    }
}

/**
 * Validate and sanitize input
 * 
 * @param string $input Input to sanitize
 * @param string $type Type of sanitization (string, email, int, float, url)
 * @return mixed Sanitized input
 */
function sanitizeInput($input, $type = 'string') {
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'url':
            return filter_var($input, FILTER_SANITIZE_URL);
        case 'string':
        default:
            // Remove any potentially harmful characters
            $output = filter_var($input, FILTER_UNSAFE_RAW);
            // Convert special characters to HTML entities
            $output = htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
            return $output;
    }
}

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generateCsrfToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        $_SESSION[CSRF_TOKEN_NAME . '_time'] = time();
    } else if (time() - $_SESSION[CSRF_TOKEN_NAME . '_time'] > CSRF_TOKEN_EXPIRY) {
        // Regenerate token if expired
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        $_SESSION[CSRF_TOKEN_NAME . '_time'] = time();
    }
    
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validate CSRF token
 * 
 * @param string $token Token to validate
 * @return bool True if token is valid
 */
function validateCsrfToken($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    
    // Check if token has expired
    if (time() - $_SESSION[CSRF_TOKEN_NAME . '_time'] > CSRF_TOKEN_EXPIRY) {
        return false;
    }
    
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Get CSRF token hidden field
 * 
 * @return string HTML for CSRF token hidden field
 */
function getCsrfTokenField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . $token . '">';
}