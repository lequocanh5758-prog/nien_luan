<?php
/**
 * Path Resolver Helper - Utility functions for path resolution
 * Priority: HIGH - Fixes path resolution inconsistencies
 */

/**
 * Safely require a class file with multiple path fallbacks
 * 
 * @param string $className The name of the class file without .php extension
 * @param array $additionalPaths Additional paths to check
 * @return bool True if file was found and included
 * @throws Exception If file cannot be found
 */
function safeRequireClass($className, $additionalPaths = []) {
    // Add .php extension if not provided
    $filename = (substr($className, -4) === '.php') ? $className : $className . '.php';
    
    // Default paths to check
    $basePaths = [
        '../mod/',
        './elements_LQA/mod/',
        './administrator/elements_LQA/mod/',
        '../../elements_LQA/mod/',
        __DIR__ . '/'
    ];
    
    // Add additional paths if provided
    if (!empty($additionalPaths)) {
        $basePaths = array_merge($basePaths, $additionalPaths);
    }
    
    // Try with various relative paths
    foreach ($basePaths as $basePath) {
        $fullPath = $basePath . basename($filename);
        if (file_exists($fullPath)) {
            require_once $fullPath;
            if (class_exists('Logger')) {
                Logger::debug("Successfully loaded class file", ['class' => $className, 'path' => $fullPath]);
            }
            return true;
        }
        
        // Try with __DIR__ combined with relative path
        $dirRelativePath = __DIR__ . '/' . $basePath . basename($filename);
        if (file_exists($dirRelativePath)) {
            require_once $dirRelativePath;
            if (class_exists('Logger')) {
                Logger::debug("Successfully loaded class file", ['class' => $className, 'path' => $dirRelativePath]);
            }
            return true;
        }
    }
    
    // If we get here, the file wasn't found
    $errorMsg = "Cannot find class file: $filename. Tried multiple paths.";
    if (class_exists('Logger')) {
        Logger::error($errorMsg);
    } else {
        error_log($errorMsg);
    }
    
    throw new Exception($errorMsg);
}

/**
 * Get the correct path for a file with multiple fallbacks
 * 
 * @param string $relativePath The relative path to resolve
 * @param array $additionalPaths Additional paths to check
 * @return string The correct path to the file
 * @throws Exception If file cannot be found
 */
function safeGetPath($relativePath, $additionalPaths = []) {
    // Define search paths in order of preference
    $searchPaths = [
        $relativePath,
        './elements_LQA/' . $relativePath,
        './administrator/elements_LQA/' . $relativePath,
        '../' . $relativePath,
        '../../' . $relativePath,
        __DIR__ . '/../' . $relativePath
    ];
    
    // Add additional paths if provided
    if (!empty($additionalPaths)) {
        $searchPaths = array_merge($searchPaths, $additionalPaths);
    }
    
    // Try each path
    foreach ($searchPaths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    // If we get here, the file wasn't found
    $errorMsg = "Cannot find file: $relativePath. Tried multiple paths.";
    if (class_exists('Logger')) {
        Logger::error($errorMsg);
    } else {
        error_log($errorMsg);
    }
    
    throw new Exception($errorMsg);
}

/**
 * Include a file with proper path resolution
 * 
 * @param string $relativePath The relative path to include
 * @param bool $once Whether to use include_once (default: true)
 * @param array $additionalPaths Additional paths to check
 * @return bool True if file was included
 * @throws Exception If file cannot be found
 */
function safeIncludeFile($relativePath, $once = true, $additionalPaths = []) {
    $path = safeGetPath($relativePath, $additionalPaths);
    
    if ($once) {
        include_once $path;
    } else {
        include $path;
    }
    
    return true;
}

/**
 * Require a file with proper path resolution
 * 
 * @param string $relativePath The relative path to require
 * @param bool $once Whether to use require_once (default: true)
 * @param array $additionalPaths Additional paths to check
 * @return bool True if file was required
 * @throws Exception If file cannot be found
 */
function safeRequireFile($relativePath, $once = true, $additionalPaths = []) {
    $path = safeGetPath($relativePath, $additionalPaths);
    
    if ($once) {
        require_once $path;
    } else {
        require $path;
    }
    
    return true;
}

/**
 * Safe session start that prevents "headers already sent" errors
 * 
 * @return bool True if session started successfully
 */
function safeSessionStart() {
    if (session_status() === PHP_SESSION_NONE) {
        // Check if headers already sent
        if (headers_sent($file, $line)) {
            if (class_exists('Logger')) {
                Logger::warning('Cannot start session - headers already sent', [
                    'file' => $file,
                    'line' => $line
                ]);
            } else {
                error_log("Cannot start session - headers already sent in $file on line $line");
            }
            return false;
        }
        
        try {
            session_start();
            return true;
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::error('Failed to start session', ['error' => $e->getMessage()]);
            } else {
                error_log('Failed to start session: ' . $e->getMessage());
            }
            return false;
        }
    }
    
    return true;
}

/**
 * Safe redirect that handles headers already sent
 * 
 * @param string $url URL to redirect to
 * @param int $statusCode HTTP status code
 * @return void
 */
function safeRedirect($url, $statusCode = 302) {
    // Clean the URL
    $url = filter_var($url, FILTER_SANITIZE_URL);
    
    if (headers_sent($file, $line)) {
        if (class_exists('Logger')) {
            Logger::warning('Headers already sent, using JavaScript redirect', [
                'url' => $url,
                'file' => $file,
                'line' => $line
            ]);
        } else {
            error_log("Headers already sent in $file on line $line, using JavaScript redirect to $url");
        }
        
        // Fallback to JavaScript redirect
        echo "<script type='text/javascript'>";
        echo "window.location.href = '" . addslashes($url) . "';";
        echo "</script>";
        echo "<noscript>";
        echo "<meta http-equiv='refresh' content='0;url=" . htmlspecialchars($url) . "'>";
        echo "</noscript>";
        echo "<p>Redirecting to <a href='" . htmlspecialchars($url) . "'>" . htmlspecialchars($url) . "</a>...</p>";
        exit();
    }
    
    try {
        http_response_code($statusCode);
        header("Location: $url");
        exit();
    } catch (Exception $e) {
        if (class_exists('Logger')) {
            Logger::error('Failed to send redirect', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
        } else {
            error_log('Failed to send redirect: ' . $e->getMessage());
        }
        
        // Fallback
        echo "<script>window.location.href = '" . addslashes($url) . "';</script>";
        exit();
    }
}

/**
 * Safe JSON response
 * 
 * @param mixed $data Data to encode as JSON
 * @param int $statusCode HTTP status code
 * @return bool False if headers already sent
 */
function safeJsonResponse($data, $statusCode = 200) {
    if (headers_sent($file, $line)) {
        if (class_exists('Logger')) {
            Logger::error('Cannot send JSON response - headers already sent', [
                'file' => $file,
                'line' => $line
            ]);
        } else {
            error_log("Cannot send JSON response - headers already sent in $file on line $line");
        }
        return false;
    }
    
    try {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    } catch (Exception $e) {
        if (class_exists('Logger')) {
            Logger::error('Failed to send JSON response', [
                'error' => $e->getMessage()
            ]);
        } else {
            error_log('Failed to send JSON response: ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * Safe success JSON response
 * 
 * @param string $message Success message
 * @param mixed $data Additional data
 * @param int $statusCode HTTP status code
 * @return bool Result of safeJsonResponse() call
 */
function safeJsonSuccess($message = 'Success', $data = null, $statusCode = 200) {
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    return safeJsonResponse($response, $statusCode);
}

/**
 * Safe error JSON response
 * 
 * @param string $message Error message
 * @param mixed $data Additional data
 * @param int $statusCode HTTP status code
 * @return bool Result of safeJsonResponse() call
 */
function safeJsonError($message = 'Error', $data = null, $statusCode = 400) {
    $response = ['success' => false, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    return safeJsonResponse($response, $statusCode);
}