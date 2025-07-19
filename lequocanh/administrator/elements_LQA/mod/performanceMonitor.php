<?php
/**
 * Performance Monitor
 * 
 * A simple tool to monitor and log the performance of database queries and other operations.
 */
class PerformanceMonitor {

    private static $logs = [];
    private static $startTime;

    /**
     * Starts the timer for a specific operation.
     */
    public static function start() {
        self::$startTime = microtime(true);
    }

    /**
     * Stops the timer and logs the execution time of an operation.
     * @param string $operationName A descriptive name for the operation being timed (e.g., a specific SQL query).
     */
    public static function log($operationName) {
        if (self::$startTime === null) {
            return; // Timer was not started
        }

        $endTime = microtime(true);
        $executionTime = $endTime - self::$startTime;

        self::$logs[] = [
            'operation' => $operationName,
            'execution_time' => $executionTime,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        // Reset the start time for the next operation
        self::$startTime = null;
    }

    /**
     * Retrieves all performance logs.
     * @return array The array of performance logs.
     */
    public static function getLogs() {
        return self::$logs;
    }

    /**
     * Retrieves queries that exceed a certain execution time threshold.
     * @param float $threshold The execution time threshold in seconds.
     * @return array An array of slow operations.
     */
    public static function getSlowOperations($threshold = 1.0) {
        $slowOperations = [];
        foreach (self::$logs as $log) {
            if ($log['execution_time'] > $threshold) {
                $slowOperations[] = $log;
            }
        }
        return $slowOperations;
    }

    /**
     * Saves the performance logs to a file.
     * @param string $filePath The path to the log file.
     */
    public static function saveLogsToFile($filePath = 'logs/performance.log') {
        $logContent = "";
        foreach (self::$logs as $log) {
            $logContent .= sprintf(
                "[%s] Operation: %s | Execution Time: %.4f seconds\n",
                $log['timestamp'],
                $log['operation'],
                $log['execution_time']
            );
        }

        // Ensure the directory exists
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($filePath, $logContent, FILE_APPEND);
    }
}
?>