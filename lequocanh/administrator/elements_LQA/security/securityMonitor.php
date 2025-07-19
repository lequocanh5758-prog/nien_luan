<?php
/**
 * Security Monitor
 * 
 * Provides comprehensive security monitoring for failed login attempts,
 * CSRF attack attempts, suspicious user activity, and file access monitoring.
 */
class SecurityMonitor {

    private static $securityEvents = [];

    /**
     * Logs a security event.
     * @param string $eventType The type of security event (e.g., "Failed Login", "CSRF Attempt", "Suspicious Activity").
     * @param array $details Additional details about the event.
     */
    public static function logEvent($eventType, $details = []) {
        $event = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event_type' => $eventType,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'details' => $details
        ];
        self::$securityEvents[] = $event;

        // Optionally, save to a persistent storage (database or file)
        file_put_contents('logs/security_events.log', json_encode($event) . "\n", FILE_APPEND);
    }

    /**
     * Checks and logs a failed login attempt.
     * @param string $username The username that failed to log in.
     */
    public static function logFailedLoginAttempt($username) {
        self::logEvent('Failed Login', ['username' => $username]);
    }

    /**
     * Checks and logs a CSRF attack attempt.
     * @param string $referer The HTTP Referer header.
     * @param string $tokenProvided The CSRF token provided in the request.
     * @param string $tokenExpected The expected CSRF token.
     */
    public static function logCsrfAttempt($referer, $tokenProvided, $tokenExpected) {
        self::logEvent('CSRF Attack Attempt', [
            'referer' => $referer,
            'token_provided' => $tokenProvided,
            'token_expected' => $tokenExpected
        ]);
    }

    /**
     * Logs suspicious user activity.
     * @param string $activityDescription A description of the suspicious activity.
     * @param int|string $userId The ID of the user, if available.
     */
    public static function logSuspiciousActivity($activityDescription, $userId = 'Unknown') {
        self::logEvent('Suspicious User Activity', [
            'description' => $activityDescription,
            'user_id' => $userId
        ]);
    }

    /**
     * Placeholder for monitoring file access.
     * This would typically involve filesystem watches or auditing tools.
     * @param string $filePath The path to the file being accessed.
     * @param string $accessType The type of access (e.g., "read", "write", "delete").
     * @param int|string $userId The ID of the user performing the access.
     */
    public static function logFileAccess($filePath, $accessType, $userId = 'Unknown') {
        self::logEvent('File Access', [
            'file_path' => $filePath,
            'access_type' => $accessType,
            'user_id' => $userId
        ]);
    }

    /**
     * Retrieves all logged security events.
     * @return array
     */
    public static function getSecurityEvents() {
        return self::$securityEvents;
    }

    /**
     * Clears all logged security events.
     */
    public static function clearEvents() {
        self::$securityEvents = [];
    }
}
?>