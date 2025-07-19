<?php
/**
 * User Activity Tracker
 * 
 * Tracks user sessions, page views, and provides basic user behavior analysis.
 */
class UserActivityTracker {

    private static $activities = [];

    /**
     * Initializes user activity tracking for the current session.
     */
    public static function init() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_activity'])) {
            $_SESSION['user_activity'] = [];
        }
        self::$activities = &$_SESSION['user_activity'];

        // Log session start
        if (!isset($_SESSION['session_start_time'])) {
            $_SESSION['session_start_time'] = time();
            self::logActivity('Session Start', ['user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown']);
        }
    }

    /**
     * Logs a user activity.
     * @param string $action The action performed by the user (e.g., "Page View", "Login", "Add to Cart").
     * @param array $details Additional details about the activity.
     */
    public static function logActivity($action, $details = []) {
        $activity = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $_SESSION['user_id'] ?? 'Guest', // Assuming user_id is set in session upon login
            'action' => $action,
            'page' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'details' => $details
        ];
        self::$activities[] = $activity;

        // Optionally, save to a persistent storage (database or file)
        // file_put_contents('logs/user_activity.log', json_encode($activity) . "\n", FILE_APPEND);
    }

    /**
     * Gets all logged activities for the current session.
     * @return array
     */
    public static function getSessionActivities() {
        return self::$activities;
    }

    /**
     * Gets the current session duration.
     * @return int Duration in seconds.
     */
    public static function getSessionDuration() {
        if (isset($_SESSION['session_start_time'])) {
            return time() - $_SESSION['session_start_time'];
        }
        return 0;
    }

    /**
     * Clears all activities for the current session.
     */
    public static function clearSessionActivities() {
        self::$activities = [];
        $_SESSION['user_activity'] = [];
    }

    /**
     * Placeholder for analyzing user behavior.
     * In a real application, this would involve more complex logic,
     * possibly querying a database of logged activities.
     * @return array
     */
    public static function analyzeUserBehavior() {
        // Example: Count page views
        $pageViews = 0;
        foreach (self::$activities as $activity) {
            if ($activity['action'] === 'Page View') {
                $pageViews++;
            }
        }
        return ['page_views_in_session' => $pageViews];
    }
}
?>