<?php
/**
 * Application Constants
 * Centralized configuration to replace hard-coded values
 */

// Security Constants
define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD') ?: 'lequocanh');
define('VERIFY_PASSWORD', getenv('VERIFY_PASSWORD') ?: 'lequocanh');

// Session Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour
define('SESSION_NAME', 'LEQUOCANH_SESSION');

// Login Security
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('PASSWORD_MIN_LENGTH', 6);

// Business Logic
define('DEFAULT_PROFIT_MARGIN', 20); // 20%
define('MAX_CART_ITEMS', 100);
define('ORDER_TIMEOUT', 1800); // 30 minutes

// File Upload
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('UPLOAD_PATH', '/uploads/');

// Database
define('DB_CHARSET', 'utf8mb4');
define('DB_TIMEOUT', 30);

// Pagination
define('DEFAULT_PAGE_SIZE', 25);
define('MAX_PAGE_SIZE', 100);

// Logging
define('LOG_MAX_FILES', 30); // Keep 30 days of logs
define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB per file

// Environment Detection
define('IS_DEVELOPMENT', getenv('APP_ENV') === 'development');
define('IS_PRODUCTION', getenv('APP_ENV') === 'production' || getenv('APP_ENV') === false);

// Paths (relative to elements_LQA)
define('MOD_PATH', __DIR__ . '/../mod/');
define('VIEW_PATH', __DIR__ . '/../');
define('UPLOAD_DIR', __DIR__ . '/../../uploads/');
define('LOG_DIR', __DIR__ . '/../logs/');

// Create necessary directories
$dirs = [LOG_DIR, UPLOAD_DIR];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Error reporting based on environment
if (IS_DEVELOPMENT) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
}