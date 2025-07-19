<?php
/**
 * Intrusion Detector
 * 
 * Detects intrusion attempts based on unusual traffic patterns, multiple failed attempts,
 * and suspicious IP addresses.
 */
class IntrusionDetector {

    private static $failedLoginAttempts = [];
    private static $ipBlacklist = [];
    private static $thresholds = [
        'failed_login_per_ip' => 5, // Max failed logins from an IP before flagging
        'failed_login_time_window' => 300, // Time window in seconds for failed logins
    ];

    /**
     * Records a failed login attempt for an IP address.
     * @param string $ipAddress The IP address of the failed attempt.
     */
    public static function recordFailedLogin($ipAddress) {
        if (!isset(self::$failedLoginAttempts[$ipAddress])) {
            self::$failedLoginAttempts[$ipAddress] = [];
        }
        self::$failedLoginAttempts[$ipAddress][] = time();
        self::cleanOldFailedLogins($ipAddress);

        if (count(self::$failedLoginAttempts[$ipAddress]) > self::$thresholds['failed_login_per_ip']) {
            self::flagSuspiciousIp($ipAddress, 'Excessive failed login attempts');
        }
    }

    /**
     * Cleans up old failed login attempts outside the time window.
     * @param string $ipAddress
     */
    private static function cleanOldFailedLogins($ipAddress) {
        $currentTime = time();
        self::$failedLoginAttempts[$ipAddress] = array_filter(self::$failedLoginAttempts[$ipAddress], function($timestamp) use ($currentTime) {
            return ($currentTime - $timestamp) < self::$thresholds['failed_login_time_window'];
        });
    }

    /**
     * Adds an IP address to the suspicious blacklist.
     * @param string $ipAddress The IP address to flag.
     * @param string $reason The reason for flagging.
     */
    public static function flagSuspiciousIp($ipAddress, $reason) {
        if (!in_array($ipAddress, self::$ipBlacklist)) {
            self::$ipBlacklist[] = $ipAddress;
            SecurityMonitor::logEvent('Suspicious IP Detected', ['ip_address' => $ipAddress, 'reason' => $reason]);
        }
    }

    /**
     * Checks if an IP address is suspicious.
     * @param string $ipAddress
     * @return bool True if suspicious, false otherwise.
     */
    public static function isSuspiciousIp($ipAddress) {
        return in_array($ipAddress, self::$ipBlacklist);
    }

    /**
     * Gets the list of suspicious IP addresses.
     * @return array
     */
    public static function getSuspiciousIps() {
        return self::$ipBlacklist;
    }

    /**
     * Placeholder for analyzing unusual traffic patterns.
     * This would involve real-time traffic analysis, possibly with external tools.
     * @param array $trafficData Mock traffic data.
     * @return bool True if unusual pattern detected, false otherwise.
     */
    public static function analyzeTrafficPatterns($trafficData) {
        // Example: if traffic spikes significantly within a short period
        // For now, a mock check
        if (isset($trafficData['requests_per_second']) && $trafficData['requests_per_second'] > 100) {
            SecurityMonitor::logEvent('Unusual Traffic Pattern', ['description' => 'High requests per second', 'rate' => $trafficData['requests_per_second']]);
            return true;
        }
        return false;
    }

    /**
     * Clears all recorded failed login attempts and suspicious IPs.
     */
    public static function reset() {
        self::$failedLoginAttempts = [];
        self::$ipBlacklist = [];
    }
}

// Ensure SecurityMonitor is loaded for logging
require_once 'securityMonitor.php';
?>