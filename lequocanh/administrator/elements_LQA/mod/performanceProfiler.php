<?php
/**
 * Performance Profiler
 * 
 * A tool for detailed profiling of application performance, tracking execution times
 * of different code blocks and functions.
 */
class PerformanceProfiler {

    private static $markers = [];
    private static $profiles = [];

    /**
     * Starts a profiling marker.
     * @param string $name A unique name for the profiling marker.
     */
    public static function start($name) {
        self::$markers[$name] = microtime(true);
    }

    /**
     * Stops a profiling marker and records the duration.
     * @param string $name The name of the profiling marker to stop.
     */
    public static function stop($name) {
        if (!isset(self::$markers[$name])) {
            return; // Marker not started
        }

        $endTime = microtime(true);
        $duration = $endTime - self::$markers[$name];

        if (!isset(self::$profiles[$name])) {
            self::$profiles[$name] = [
                'total_duration' => 0,
                'calls' => 0,
                'min_duration' => PHP_INT_MAX,
                'max_duration' => 0,
            ];
        }

        self::$profiles[$name]['total_duration'] += $duration;
        self::$profiles[$name]['calls']++;
        self::$profiles[$name]['min_duration'] = min(self::$profiles[$name]['min_duration'], $duration);
        self::$profiles[$name]['max_duration'] = max(self::$profiles[$name]['max_duration'], $duration);

        unset(self::$markers[$name]); // Remove marker after stopping
    }

    /**
     * Retrieves all recorded profiles.
     * @return array An associative array of profiling data.
     */
    public static function getProfiles() {
        // Calculate average duration for each profile
        foreach (self::$profiles as $name => $data) {
            if ($data['calls'] > 0) {
                self::$profiles[$name]['average_duration'] = $data['total_duration'] / $data['calls'];
            } else {
                self::$profiles[$name]['average_duration'] = 0;
            }
        }
        return self::$profiles;
    }

    /**
     * Resets all profiling data.
     */
    public static function reset() {
        self::$markers = [];
        self::$profiles = [];
    }

    /**
     * Saves profiling data to a log file.
     * @param string $filePath The path to the log file.
     */
    public static function saveToFile($filePath = 'logs/profiler.log') {
        $logContent = "Profiling Report - " . date('Y-m-d H:i:s') . "\n";
        $logContent .= str_repeat('=', 40) . "\n";

        $profiles = self::getProfiles();
        foreach ($profiles as $name => $data) {
            $logContent .= sprintf(
                "Operation: %s | Calls: %d | Total: %.4f s | Avg: %.4f s | Min: %.4f s | Max: %.4f s\n",
                $name,
                $data['calls'],
                $data['total_duration'],
                $data['average_duration'],
                $data['min_duration'],
                $data['max_duration']
            );
        }
        $logContent .= str_repeat('=', 40) . "\n\n";

        // Ensure the directory exists
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($filePath, $logContent, FILE_APPEND);
    }
}
?>