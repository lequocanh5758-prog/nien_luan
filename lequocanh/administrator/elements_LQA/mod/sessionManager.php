<?php

/**
 * Session Manager Class
 * Provides safe session management preventing "headers already sent" errors
 */
class SessionManager
{
    private static $started = false;

    /**
     * Safely start session if not already started
     */
    public static function start()
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }

        if (headers_sent($file, $line)) {
            if (class_exists('Logger')) {
                Logger::warning('Cannot start session, headers already sent', [
                    'file' => $file,
                    'line' => $line
                ]);
            }
            return false;
        }

        try {
            // Configure session for ngrok compatibility
            ini_set('session.cookie_domain', '');
            ini_set('session.cookie_secure', '0'); // Allow HTTP for development
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.use_strict_mode', '1');

            $result = session_start();
            self::$started = $result;
            return $result;
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::error('Session start failed', ['error' => $e->getMessage()]);
            }
            return false;
        }
    }

    /**
     * Check if session is started
     */
    public static function isStarted()
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Safely destroy session
     */
    public static function destroy()
    {
        if (self::isStarted()) {
            session_destroy();
            self::$started = false;
        }
    }

    /**
     * Set session value
     */
    public static function set($key, $value)
    {
        if (self::start()) {
            $_SESSION[$key] = $value;
            return true;
        }
        return false;
    }

    /**
     * Get session value
     */
    public static function get($key, $default = null)
    {
        if (self::start()) {
            return $_SESSION[$key] ?? $default;
        }
        return $default;
    }

    /**
     * Check if session key exists
     */
    public static function has($key)
    {
        if (self::start()) {
            return isset($_SESSION[$key]);
        }
        return false;
    }

    /**
     * Remove session key
     */
    public static function remove($key)
    {
        if (self::start()) {
            unset($_SESSION[$key]);
            return true;
        }
        return false;
    }

    /**
     * Get all session data
     */
    public static function all()
    {
        if (self::start()) {
            return $_SESSION;
        }
        return [];
    }

    /**
     * Clear all session data
     */
    public static function clear()
    {
        if (self::start()) {
            $_SESSION = [];
            return true;
        }
        return false;
    }

    /**
     * Regenerate session ID for security
     */
    public static function regenerate($deleteOld = true)
    {
        if (self::isStarted()) {
            return session_regenerate_id($deleteOld);
        }
        return false;
    }
}
