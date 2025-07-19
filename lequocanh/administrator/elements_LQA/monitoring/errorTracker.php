<?php
/**
 * Error Tracker
 * 
 * Provides real-time error tracking, error rate monitoring, and error categorization.
 */
class ErrorTracker {

    private static $errors = [];
    private static $errorCounts = [];

    /**
     * Registers a custom error handler to capture PHP errors.
     */
    public static function registerErrorHandler() {
        set_error_handler([__CLASS__, 'errorHandler']);
        register_shutdown_function([__CLASS__, 'fatalErrorShutdownHandler']);
    }

    /**
     * Custom error handler function.
     * @param int $errno The level of the error raised.
     * @param string $errstr The error message.
     * @param string $errfile The filename that the error was raised in.
     * @param int $errline The line number the error was raised at.
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline) {
        // Ignore suppressed errors (with @)
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $errorType = self::getErrorType($errno);
        $errorMessage = "[$errorType] $errstr in $errfile on line $errline";
        
        self::logError($errorMessage, $errorType, [
            'errno' => $errno,
            'file' => $errfile,
            'line' => $errline
        ]);

        // Prevent PHP's default error handler from executing
        return true;
    }

    /**
     * Handles fatal errors that are not caught by set_error_handler.
     */
    public static function fatalErrorShutdownHandler() {
        $lastError = error_get_last();
        if ($lastError && ($lastError['type'] === E_ERROR || $lastError['type'] === E_PARSE || $lastError['type'] === E_CORE_ERROR || $lastError['type'] === E_COMPILE_ERROR)) {
            $errorType = self::getErrorType($lastError['type']);
            $errorMessage = "[$errorType] {$lastError['message']} in {$lastError['file']} on line {$lastError['line']}";
            
            self::logError($errorMessage, $errorType, [
                'errno' => $lastError['type'],
                'file' => $lastError['file'],
                'line' => $lastError['line']
            ]);
        }
    }

    /**
     * Logs an error.
     * @param string $message The error message.
     * @param string $type The type of error (e.g., "Error", "Warning").
     * @param array $context Additional context for the error.
     */
    public static function logError($message, $type = 'Unknown', $context = []) {
        self::$errors[] = [
            'message' => $message,
            'type' => $type,
            'timestamp' => date('Y-m-d H:i:s'),
            'context' => $context
        ];

        // Increment error count for categorization
        if (!isset(self::$errorCounts[$type])) {
            self::$errorCounts[$type] = 0;
        }
        self::$errorCounts[$type]++;

        // Optionally, save to a file or send to an external service
        // file_put_contents('logs/errors.log', "$message\n", FILE_APPEND);
    }

    /**
     * Returns all logged errors.
     * @return array
     */
    public static function getErrors() {
        return self::$errors;
    }

    /**
     * Returns error counts by type.
     * @return array
     */
    public static function getErrorCounts() {
        return self::$errorCounts;
    }

    /**
     * Clears all logged errors and counts.
     */
    public static function clearErrors() {
        self::$errors = [];
        self::$errorCounts = [];
    }

    /**
     * Gets the string representation of an error type.
     * @param int $type The error constant (e.g., E_ERROR).
     * @return string
     */
    private static function getErrorType($type) {
        switch ($type) {
            case E_ERROR: return 'Error';
            case E_WARNING: return 'Warning';
            case E_PARSE: return 'Parsing Error';
            case E_NOTICE: return 'Notice';
            case E_CORE_ERROR: return 'Core Error';
            case E_CORE_WARNING: return 'Core Warning';
            case E_COMPILE_ERROR: return 'Compile Error';
            case E_COMPILE_WARNING: return 'Compile Warning';
            case E_USER_ERROR: return 'User Error';
            case E_USER_WARNING: return 'User Warning';
            case E_USER_NOTICE: return 'User Notice';
            case E_STRICT: return 'Strict';
            case E_RECOVERABLE_ERROR: return 'Recoverable Error';
            case E_DEPRECATED: return 'Deprecated';
            case E_USER_DEPRECATED: return 'User Deprecated';
            default: return 'Unknown Error';
        }
    }
}
?>