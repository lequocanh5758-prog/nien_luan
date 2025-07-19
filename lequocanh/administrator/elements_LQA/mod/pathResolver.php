<?php
/**
 * Path Resolver - Standardizes file inclusion across the application
 * Priority: HIGH - Fixes path resolution inconsistencies
 */

// Include constants if not already included
if (!defined('BASE_PATH')) {
    $constantsPaths = [
        __DIR__ . '/../config/constants.php',
        '../config/constants.php',
        '../../elements_LQA/config/constants.php',
        './administrator/elements_LQA/config/constants.php'
    ];
    
    $constantsLoaded = false;
    foreach ($constantsPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $constantsLoaded = true;
            break;
        }
    }
    
    if (!$constantsLoaded) {
        // Fallback definitions if constants.php not found
        define('BASE_PATH', realpath(__DIR__ . '/../../../../'));
        define('ADMIN_PATH', BASE_PATH . '/administrator');
        define('ELEMENTS_PATH', ADMIN_PATH . '/elements_LQA');
        define('MOD_PATH', ELEMENTS_PATH . '/mod');
    }
}

class PathResolver {
    // Common base paths to try when including files
    private static $basePaths = [
        '../mod/',
        './elements_LQA/mod/',
        './administrator/elements_LQA/mod/',
        '../../elements_LQA/mod/'
    ];
    
    /**
     * Safely require a class file with multiple path fallbacks
     * 
     * @param string $className The name of the class file without .php extension
     * @return bool True if file was found and included
     * @throws Exception If file cannot be found
     */
    public static function requireClass($className) {
        // Add .php extension if not provided
        $filename = (substr($className, -4) === '.php') ? $className : $className . '.php';
        
        // Try absolute path first if MOD_PATH is defined
        if (defined('MOD_PATH')) {
            $absolutePath = MOD_PATH . '/' . basename($filename);
            if (file_exists($absolutePath)) {
                require_once $absolutePath;
                return true;
            }
        }
        
        // Try with __DIR__ based paths
        $dirPath = __DIR__ . '/' . basename($filename);
        if (file_exists($dirPath)) {
            require_once $dirPath;
            return true;
        }
        
        // Try with various relative paths
        foreach (self::$basePaths as $basePath) {
            $fullPath = $basePath . basename($filename);
            if (file_exists($fullPath)) {
                require_once $fullPath;
                return true;
            }
            
            // Try with __DIR__ combined with relative path
            $dirRelativePath = __DIR__ . '/' . $basePath . basename($filename);
            if (file_exists($dirRelativePath)) {
                require_once $dirRelativePath;
                return true;
            }
        }
        
        // If we get here, the file wasn't found
        $errorMsg = "Cannot find class file: $filename. Tried paths: " . 
                    implode(', ', array_merge(
                        [defined('MOD_PATH') ? MOD_PATH . '/' . basename($filename) : 'MOD_PATH not defined'],
                        [__DIR__ . '/' . basename($filename)],
                        array_map(function($p) use ($filename) { return $p . basename($filename); }, self::$basePaths)
                    ));
                    
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
     * @return string The correct path to the file
     * @throws Exception If file cannot be found
     */
    public static function getPath($relativePath) {
        // Define search paths in order of preference
        $searchPaths = [
            $relativePath,
            './elements_LQA/' . $relativePath,
            './administrator/elements_LQA/' . $relativePath,
            '../' . $relativePath,
            '../../' . $relativePath,
            __DIR__ . '/../' . $relativePath,
            defined('BASE_PATH') ? BASE_PATH . '/' . $relativePath : null,
            defined('ELEMENTS_PATH') ? ELEMENTS_PATH . '/' . $relativePath : null
        ];
        
        // Filter out null paths
        $searchPaths = array_filter($searchPaths);
        
        // Try each path
        foreach ($searchPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        // If we get here, the file wasn't found
        $errorMsg = "Cannot find file: $relativePath. Tried paths: " . implode(', ', $searchPaths);
        
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
     * @return bool True if file was included
     * @throws Exception If file cannot be found
     */
    public static function includeFile($relativePath, $once = true) {
        $path = self::getPath($relativePath);
        
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
     * @return bool True if file was required
     * @throws Exception If file cannot be found
     */
    public static function requireFile($relativePath, $once = true) {
        $path = self::getPath($relativePath);
        
        if ($once) {
            require_once $path;
        } else {
            require $path;
        }
        
        return true;
    }
}