<?php
/**
 * Application Bootstrap
 * Phase 4 - Modern Architecture Foundation
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define constants
define('APP_ROOT', __DIR__);
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('APP_DEBUG', APP_ENV === 'development');

// Autoloader
spl_autoload_register(function ($class) {
    $paths = [
        APP_ROOT . '/lequocanh/administrator/elements_LQA/mod/',
        APP_ROOT . '/lequocanh/administrator/elements_LQA/monitoring/',
        APP_ROOT . '/lequocanh/api/v1/',
        APP_ROOT . '/lequocanh/api/middleware/',
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Initialize core services
try {
    // Session Management
    if (class_exists('SessionManager')) {
        SessionManager::start();
    }
    
    // Error Tracking
    if (class_exists('ErrorTracker')) {
        ErrorTracker::registerErrorHandler();
    }
    
    // Performance Monitoring
    if (class_exists('RealtimePerformanceMonitor')) {
        RealtimePerformanceMonitor::startOperation('Application');
    }
    
    // User Activity Tracking
    if (class_exists('UserActivityTracker')) {
        UserActivityTracker::init();
    }
    
} catch (Exception $e) {
    error_log("Bootstrap error: " . $e->getMessage());
    if (APP_DEBUG) {
        die("Bootstrap failed: " . $e->getMessage());
    }
}

// Register shutdown function
register_shutdown_function(function() {
    if (class_exists('RealtimePerformanceMonitor')) {
        RealtimePerformanceMonitor::endOperation('Application');
    }
});
