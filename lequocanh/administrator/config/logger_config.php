<?php
/**
 * Cấu hình cho hệ thống logging
 */

// Định nghĩa các mức độ log
// Logger::DEBUG = 1
// Logger::INFO = 2
// Logger::WARNING = 3
// Logger::ERROR = 4
// Logger::CRITICAL = 5

// Cấu hình logger
$loggerConfig = [
    // Mức độ log tối thiểu (mặc định: INFO)
    // Đặt thành DEBUG để ghi tất cả logs, ERROR để chỉ ghi lỗi nghiêm trọng
    'logLevel' => 2, // INFO
    
    // File log (mặc định: logs/application.log)
    'logFile' => __DIR__ . '/../../logs/application.log',
    
    // Có ghi vào file không
    'logToFile' => true,
    
    // Có ghi vào error_log của PHP không
    'logToErrorLog' => true,
];

// Áp dụng cấu hình nếu Logger đã được include
if (class_exists('Logger')) {
    Logger::configure($loggerConfig);
}