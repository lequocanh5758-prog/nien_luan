<?php
/**
 * Auto Require Fix - Automatically handles path resolution for require/include
 * 
 * This script provides helper functions to safely include files with multiple fallback paths.
 * It's meant to be included in files that need to require other files with path resolution.
 * 
 * Usage:
 * 1. Include this file at the top of your PHP file
 * 2. Use safeRequire('filename') instead of require_once 'path/to/filename'
 */

/**
 * Safely require a file with multiple fallback paths
 * 
 * @param string $filename Filename without path (e.g., 'userCls.php')
 * @param string $type Type of file ('mod', 'config', etc.)
 * @param bool $once Whether to use require_once (default: true)
 * @return bool True if file was found and included
 */
function safeRequire($filename, $type = 'mod', $once = true) {
    // Check if PathResolver exists
    if (class_exists('PathResolver')) {
        try {
            if ($type === 'mod') {
                // Remove .php extension if present
                $className = (substr($filename, -4) === '.php') ? substr($filename, 0, -4) : $filename;
                PathResolver::requireClass($className);
                return true;
            } else {
                PathResolver::requireFile("$type/$filename");
                return true;
            }
        } catch (Exception $e) {
            // Fall through to manual path resolution
        }
    }
    
    // Define possible paths based on file type
    $paths = [];
    
    switch ($type) {
        case 'mod':
            $paths = [
                "../mod/$filename",
                "../../elements_LQA/mod/$filename",
                "./elements_LQA/mod/$filename",
                __DIR__ . "/$filename",
                __DIR__ . "/../mod/$filename"
            ];
            break;
        case 'config':
            $paths = [
                "../config/$filename",
                "../../elements_LQA/config/$filename",
                "./elements_LQA/config/$filename",
                __DIR__ . "/../config/$filename"
            ];
            break;
        default:
            $paths = [
                "../$type/$filename",
                "../../elements_LQA/$type/$filename",
                "./elements_LQA/$type/$filename",
                __DIR__ . "/../$type/$filename"
            ];
    }
    
    // Try each path
    foreach ($paths as $path) {
        if (file_exists($path)) {
            if ($once) {
                require_once $path;
            } else {
                require $path;
            }
            return true;
        }
    }
    
    // If we get here, file wasn't found
    $errorMsg = "Cannot find file: $filename of type $type";
    if (class_exists('Logger')) {
        Logger::error($errorMsg);
    } else {
        error_log($errorMsg);
    }
    
    return false;
}

/**
 * Safely include a file with multiple fallback paths
 * 
 * @param string $filename Filename without path (e.g., 'header.php')
 * @param string $type Type of file ('includes', 'templates', etc.)
 * @param bool $once Whether to use include_once (default: true)
 * @return bool True if file was found and included
 */
function safeInclude($filename, $type = 'includes', $once = true) {
    return safeRequire($filename, $type, $once);
}

/**
 * Get the correct path to a file with multiple fallback paths
 * 
 * @param string $filename Filename without path (e.g., 'config.ini')
 * @param string $type Type of file ('config', 'data', etc.)
 * @return string|false Path to file if found, false otherwise
 */
function safePath($filename, $type = 'config') {
    // Check if PathResolver exists
    if (class_exists('PathResolver')) {
        try {
            return PathResolver::getPath("$type/$filename");
        } catch (Exception $e) {
            // Fall through to manual path resolution
        }
    }
    
    // Define possible paths based on file type
    $paths = [];
    
    switch ($type) {
        case 'config':
            $paths = [
                "../config/$filename",
                "../../elements_LQA/config/$filename",
                "./elements_LQA/config/$filename",
                __DIR__ . "/../config/$filename"
            ];
            break;
        default:
            $paths = [
                "../$type/$filename",
                "../../elements_LQA/$type/$filename",
                "./elements_LQA/$type/$filename",
                __DIR__ . "/../$type/$filename"
            ];
    }
    
    // Try each path
    foreach ($paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    return false;
}