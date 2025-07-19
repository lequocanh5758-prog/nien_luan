<?php
/**
 * Auto Session Fix - Automatically fixes session_start() calls
 * 
 * This script is meant to be included at the very beginning of PHP files
 * to automatically replace direct session_start() calls with SessionManager.
 * 
 * Usage:
 * 1. Include this file at the top of your PHP file
 * 2. It will automatically handle session management safely
 */

// Check if SessionManager exists, if not include it
if (!class_exists('SessionManager')) {
    // Try multiple paths to find SessionManager
    $sessionManagerPaths = [
        __DIR__ . '/sessionManager.php',
        __DIR__ . '/../mod/sessionManager.php',
        __DIR__ . '/../../elements_LQA/mod/sessionManager.php',
        './elements_LQA/mod/sessionManager.php',
        '../mod/sessionManager.php'
    ];
    
    $foundSessionManager = false;
    foreach ($sessionManagerPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $foundSessionManager = true;
            break;
        }
    }
    
    if (!$foundSessionManager) {
        // Fallback to safe session handling if SessionManager not found
        if (function_exists('session_status') && session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

// Check if Logger exists, if not include it
if (!class_exists('Logger')) {
    // Try multiple paths to find Logger config
    $loggerConfigPaths = [
        __DIR__ . '/../config/logger_config.php',
        __DIR__ . '/../../elements_LQA/config/logger_config.php',
        './elements_LQA/config/logger_config.php',
        '../config/logger_config.php'
    ];
    
    $foundLoggerConfig = false;
    foreach ($loggerConfigPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $foundLoggerConfig = true;
            break;
        }
    }
}

// Start session safely using SessionManager if available
if (class_exists('SessionManager')) {
    SessionManager::start();
} else if (function_exists('session_status') && session_status() === PHP_SESSION_NONE) {
    // Fallback to direct session_start if SessionManager not available
    session_start();
}

/**
 * Safe redirect function to prevent "headers already sent" errors
 */
function safeRedirect($url, $statusCode = 302) {
    if (class_exists('ResponseManager')) {
        ResponseManager::redirect($url, $statusCode);
        return;
    }
    
    if (headers_sent($file, $line)) {
        echo "<script>window.location.href = '" . addslashes($url) . "';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=" . htmlspecialchars($url) . "'></noscript>";
        exit;
    }
    
    header("Location: $url", true, $statusCode);
    exit;
}

/**
 * Safe logging function that uses Logger if available, otherwise error_log
 */
function safeLog($message, $level = 'info', $context = []) {
    if (class_exists('Logger')) {
        switch ($level) {
            case 'debug':
                Logger::debug($message, $context);
                break;
            case 'info':
                Logger::info($message, $context);
                break;
            case 'warning':
                Logger::warning($message, $context);
                break;
            case 'error':
                Logger::error($message, $context);
                break;
            default:
                Logger::info($message, $context);
        }
    } else {
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        error_log("[$level] $message$contextStr");
    }
}