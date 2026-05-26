<?php

require_once __DIR__ . '/lequocanh/config/ConfigManager.php';

$config = ConfigManager::getInstance();

$legacyConfig = $config->getLegacyConfig();

if (!defined('BASE_URL')) {
    define('BASE_URL', $config->getBaseUrl());
}
if (!defined('LOCAL_DEV')) {
    define('LOCAL_DEV', $config->get('app.environment') === 'development' || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false);
}

// Không còn cần force redirect - hệ thống tự động phát hiện môi trường

if ($config->get('app.app.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

if (!defined('APP_ROOT')) {
    define('APP_ROOT', __DIR__);
}
if (!defined('APP_ENV')) {
    define('APP_ENV', $config->get('app.app.environment', 'production'));
}
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', $config->get('app.app.debug', false));
}

require_once __DIR__ . '/security.php';

require_once __DIR__ . '/performance.php';

spl_autoload_register(function ($class) {
    $paths = [

        APP_ROOT . '/lequocanh/app/Controllers/',
        APP_ROOT . '/lequocanh/app/Controllers/Admin/',
        APP_ROOT . '/lequocanh/app/Models/',
        APP_ROOT . '/lequocanh/app/Services/',

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

try {

    if (class_exists('SessionManager')) {
        SessionManager::start();
    }

    if (class_exists('ErrorTracker')) {
        ErrorTracker::registerErrorHandler();
    }

    if (class_exists('RealtimePerformanceMonitor')) {
        RealtimePerformanceMonitor::startOperation('Application');
    }

    if (class_exists('UserActivityTracker')) {
        UserActivityTracker::init();
    }
} catch (Exception $e) {
    error_log("Bootstrap error: " . $e->getMessage());
    if (APP_DEBUG) {
        die("Bootstrap failed: " . $e->getMessage());
    }
}

register_shutdown_function(function () {
    if (class_exists('RealtimePerformanceMonitor')) {
        RealtimePerformanceMonitor::endOperation('Application');
    }
});