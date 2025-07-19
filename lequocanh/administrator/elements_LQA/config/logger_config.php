<?php
/**
 * Logger Configuration - Environment-based logging levels
 * Priority: HIGH - Controls debug log pollution
 */

// Include the logger class
require_once __DIR__ . '/../mod/loggerCls.php';

// Detect environment
$environment = getenv('APP_ENV') ?: 'production';

// Check if we're in development mode
$isDevelopment = (
    $environment === 'development' || 
    $environment === 'dev' ||
    getenv('DEBUG') === 'true' ||
    (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)
);

// Configure logging based on environment
if ($isDevelopment) {
    // Development: Show all logs including debug
    if (class_exists('Logger')) {
        Logger::configure([
            'logLevel' => Logger::DEBUG,
            'enabled' => true
        ]);
    }
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // Production: Only warnings and errors
    if (class_exists('Logger')) {
        Logger::configure([
            'logLevel' => Logger::WARNING,
            'enabled' => true
        ]);
    }
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
}

// Log the configuration
if (class_exists('Logger')) {
    Logger::info('Logger configured', [
        'environment' => $environment,
        'development_mode' => $isDevelopment,
        'level' => $isDevelopment ? 'DEBUG' : 'WARNING'
    ]);
}