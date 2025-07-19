<?php
/**
 * Logger Class
 * Hệ thống logging tập trung cho ứng dụng
 */
class Logger {
    // Log levels
    const DEBUG = 1;
    const INFO = 2;
    const WARNING = 3;
    const ERROR = 4;
    const CRITICAL = 5;

    // Cấu hình mặc định
    private static $logLevel = self::INFO; // Mặc định chỉ log từ INFO trở lên
    private static $logFile = null;
    private static $logToFile = true;
    private static $logToErrorLog = true;
    private static $enabled = true;
    private static $instance = null;

    /**
     * Khởi tạo Logger
     */
    private function __construct() {
        // Đặt file log mặc định
        if (self::$logFile === null) {
            self::$logFile = __DIR__ . '/../../../logs/application.log';
            
            // Tạo thư mục logs nếu chưa tồn tại
            $logDir = dirname(self::$logFile);
            if (!file_exists($logDir)) {
                // Suppress errors as it might fail due to permissions
                @mkdir($logDir, 0755, true);
            }

            // Check if logging to file is feasible
            if (!is_dir($logDir) || !is_writable($logDir)) {
                self::$logToFile = false;
                // Log a single error to the default PHP error log to notify the admin
                error_log("Logger Error: Log directory '{$logDir}' is not writable. File logging is disabled.");
            }
        }
    }

    /**
     * Lấy instance của Logger (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Logger();
        }
        return self::$instance;
    }

    /**
     * Cấu hình logger
     * @param array $config Mảng cấu hình
     */
    public static function configure($config = []) {
        if (isset($config['logLevel'])) {
            self::$logLevel = $config['logLevel'];
        }
        
        if (isset($config['logFile'])) {
            self::$logFile = $config['logFile'];
        }
        
        if (isset($config['logToFile'])) {
            self::$logToFile = $config['logToFile'];
        }
        
        if (isset($config['logToErrorLog'])) {
            self::$logToErrorLog = $config['logToErrorLog'];
        }
        
        if (isset($config['enabled'])) {
            self::$enabled = $config['enabled'];
        }
    }

    /**
     * Đặt log level
     * @param int $level Log level
     */
    public static function setLevel($level) {
        self::$logLevel = $level;
    }

    /**
     * Lấy log level hiện tại
     * @return int Log level
     */
    public static function getLevel() {
        return self::$logLevel;
    }

    /**
     * Bật/tắt logging
     * @param bool $enabled True để bật, false để tắt
     */
    public static function setEnabled($enabled) {
        self::$enabled = $enabled;
    }

    /**
     * Kiểm tra xem logging có được bật không
     * @return bool
     */
    public static function isEnabled() {
        return self::$enabled;
    }

    /**
     * Log debug message
     * @param string $message Thông điệp
     * @param array $context Dữ liệu bổ sung
     */
    public static function debug($message, $context = []) {
        self::log(self::DEBUG, $message, $context);
    }

    /**
     * Log info message
     * @param string $message Thông điệp
     * @param array $context Dữ liệu bổ sung
     */
    public static function info($message, $context = []) {
        self::log(self::INFO, $message, $context);
    }

    /**
     * Log warning message
     * @param string $message Thông điệp
     * @param array $context Dữ liệu bổ sung
     */
    public static function warning($message, $context = []) {
        self::log(self::WARNING, $message, $context);
    }

    /**
     * Log error message
     * @param string $message Thông điệp
     * @param array $context Dữ liệu bổ sung
     */
    public static function error($message, $context = []) {
        self::log(self::ERROR, $message, $context);
    }

    /**
     * Log critical message
     * @param string $message Thông điệp
     * @param array $context Dữ liệu bổ sung
     */
    public static function critical($message, $context = []) {
        self::log(self::CRITICAL, $message, $context);
    }

    /**
     * Log message
     * @param int $level Log level
     * @param string $message Thông điệp
     * @param array $context Dữ liệu bổ sung
     */
    private static function log($level, $message, $context = []) {
        // Kiểm tra xem logging có được bật không
        if (!self::$enabled) {
            return;
        }
        
        // Kiểm tra log level
        if ($level < self::$logLevel) {
            return;
        }

        // Khởi tạo instance nếu cần
        self::getInstance();

        // Lấy tên level
        $levelName = self::getLevelName($level);
        
        // Lấy thông tin về file và dòng gọi log
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = isset($backtrace[1]) ? $backtrace[1] : $backtrace[0];
        $file = isset($caller['file']) ? basename($caller['file']) : 'unknown';
        $line = isset($caller['line']) ? $caller['line'] : 0;
        
        // Format message
        $timestamp = date('Y-m-d H:i:s');
        $contextString = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        $logMessage = "[$timestamp] [$levelName] [$file:$line] $message$contextString";
        
        // Log to file
        if (self::$logToFile) {
            self::logToFile($logMessage);
        }
        
        // Log to error_log
        if (self::$logToErrorLog) {
            error_log($logMessage);
        }
    }

    /**
     * Ghi log vào file
     * @param string $message Thông điệp
     */
    private static function logToFile($message) {
        try {
            file_put_contents(self::$logFile, $message . PHP_EOL, FILE_APPEND);
        } catch (Exception $e) {
            error_log("Không thể ghi vào file log: " . $e->getMessage());
        }
    }

    /**
     * Lấy tên của log level
     * @param int $level Log level
     * @return string Tên level
     */
    private static function getLevelName($level) {
        switch ($level) {
            case self::DEBUG:
                return 'DEBUG';
            case self::INFO:
                return 'INFO';
            case self::WARNING:
                return 'WARNING';
            case self::ERROR:
                return 'ERROR';
            case self::CRITICAL:
                return 'CRITICAL';
            default:
                return 'UNKNOWN';
        }
    }
}