<?php
/**
 * Real-time Performance Monitor
 * 
 * This class provides methods for collecting real-time performance metrics
 * such as response time, memory usage, and CPU usage.
 */
class RealtimePerformanceMonitor {

    private static $metrics = [];

    /**
     * Records the start time of an operation.
     * @param string $operationName
     */
    public static function startOperation($operationName) {
        self::$metrics[$operationName]['start_time'] = microtime(true);
    }

    /**
     * Records the end time of an operation and calculates its duration.
     * @param string $operationName
     */
    public static function endOperation($operationName) {
        if (!isset(self::$metrics[$operationName]['start_time'])) {
            return;
        }
        self::$metrics[$operationName]['end_time'] = microtime(true);
        self::$metrics[$operationName]['duration'] = self::$metrics[$operationName]['end_time'] - self::$metrics[$operationName]['start_time'];
    }

    /**
     * Gets the duration of a specific operation.
     * @param string $operationName
     * @return float|null
     */
    public static function getOperationDuration($operationName) {
        return self::$metrics[$operationName]['duration'] ?? null;
    }

    /**
     * Gets current memory usage.
     * @return int Memory usage in bytes.
     */
    public static function getMemoryUsage() {
        return memory_get_usage();
    }

    /**
     * Gets peak memory usage.
     * @return int Peak memory usage in bytes.
     */
    public static function getPeakMemoryUsage() {
        return memory_get_peak_usage();
    }

    /**
     * Placeholder for getting CPU usage.
     * Actual CPU usage monitoring in PHP is complex and often requires external tools or parsing system logs.
     * @return float CPU usage percentage.
     */
    public static function getCpuUsage() {
        // This is a placeholder. Real CPU usage would require more sophisticated methods,
        // often involving reading /proc/stat on Linux or WMI on Windows.
        return rand(0, 100); // Mock data
    }

    /**
     * Retrieves all collected real-time metrics.
     * @return array
     */
    public static function getAllMetrics() {
        return self::$metrics;
    }

    /**
     * Resets all collected metrics.
     */
    public static function resetMetrics() {
        self::$metrics = [];
    }

    /**
     * Formats bytes into a human-readable string (e.g., KB, MB).
     * @param int $bytes
     * @return string
     */
    public static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
?>