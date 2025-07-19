<?php
/**
 * CSRF Protection - Prevents Cross-Site Request Forgery attacks
 * Priority: HIGH - Security enhancement
 */

class CSRFProtection {
    const TOKEN_NAME = 'csrf_token';
    const TOKEN_EXPIRY = 3600; // 1 hour
    
    /**
     * Generate CSRF token
     * 
     * @return string CSRF token
     */
    public static function generateToken() {
        // Ensure session is started
        if (class_exists('SessionManager')) {
            SessionManager::start();
        } else if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Generate new token if not exists or expired
        if (!isset($_SESSION[self::TOKEN_NAME]) || 
            !isset($_SESSION[self::TOKEN_NAME . '_time']) ||
            (time() - $_SESSION[self::TOKEN_NAME . '_time']) > self::TOKEN_EXPIRY) {
            
            $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(32));
            $_SESSION[self::TOKEN_NAME . '_time'] = time();
            
            if (class_exists('Logger')) {
                Logger::debug("CSRF token generated");
            }
        }
        
        return $_SESSION[self::TOKEN_NAME];
    }
    
    /**
     * Validate CSRF token
     * 
     * @param string $token Token to validate
     * @return bool True if token is valid
     */
    public static function validateToken($token) {
        // Ensure session is started
        if (class_exists('SessionManager')) {
            SessionManager::start();
        } else if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if token exists in session
        if (!isset($_SESSION[self::TOKEN_NAME]) || !isset($_SESSION[self::TOKEN_NAME . '_time'])) {
            if (class_exists('Logger')) {
                Logger::warning("CSRF token validation failed - no token in session");
            }
            return false;
        }
        
        // Check if token has expired
        if ((time() - $_SESSION[self::TOKEN_NAME . '_time']) > self::TOKEN_EXPIRY) {
            if (class_exists('Logger')) {
                Logger::warning("CSRF token validation failed - token expired");
            }
            return false;
        }
        
        // Validate token
        $isValid = hash_equals($_SESSION[self::TOKEN_NAME], $token);
        
        if (class_exists('Logger')) {
            if ($isValid) {
                Logger::debug("CSRF token validation successful");
            } else {
                Logger::warning("CSRF token validation failed - token mismatch");
            }
        }
        
        return $isValid;
    }
    
    /**
     * Get CSRF token hidden field for forms
     * 
     * @return string HTML for CSRF token hidden field
     */
    public static function getHiddenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="' . self::TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Get CSRF token meta tag for AJAX requests
     * 
     * @return string HTML meta tag for CSRF token
     */
    public static function getMetaTag() {
        $token = self::generateToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Validate CSRF token from request
     * 
     * @param array $request Request data ($_POST, $_GET, etc.)
     * @return bool True if token is valid
     */
    public static function validateRequest($request = null) {
        if ($request === null) {
            $request = $_REQUEST;
        }
        
        $token = $request[self::TOKEN_NAME] ?? '';
        return self::validateToken($token);
    }
    
    /**
     * Require valid CSRF token or throw exception
     * 
     * @param array $request Request data ($_POST, $_GET, etc.)
     * @throws Exception If CSRF token is invalid
     */
    public static function requireValidToken($request = null) {
        if (!self::validateRequest($request)) {
            if (class_exists('Logger')) {
                Logger::error("CSRF protection triggered - invalid token", [
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                    'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
                ]);
            }
            throw new Exception("CSRF token validation failed");
        }
    }
    
    /**
     * Get JavaScript code for AJAX CSRF protection
     * 
     * @return string JavaScript code
     */
    public static function getAjaxScript() {
        $token = self::generateToken();
        return "
        <script>
        // CSRF Protection for AJAX requests
        (function() {
            var csrfToken = '" . addslashes($token) . "';
            
            // Add CSRF token to all AJAX requests
            if (typeof jQuery !== 'undefined') {
                jQuery.ajaxSetup({
                    beforeSend: function(xhr, settings) {
                        if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type) && !this.crossDomain) {
                            xhr.setRequestHeader('X-CSRF-Token', csrfToken);
                        }
                    }
                });
            }
            
            // Add CSRF token to all forms
            document.addEventListener('DOMContentLoaded', function() {
                var forms = document.querySelectorAll('form');
                forms.forEach(function(form) {
                    if (form.method.toLowerCase() === 'post') {
                        var csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '" . self::TOKEN_NAME . "';
                        csrfInput.value = csrfToken;
                        form.appendChild(csrfInput);
                    }
                });
            });
        })();
        </script>";
    }
    
    /**
     * Regenerate CSRF token (useful after login/logout)
     */
    public static function regenerateToken() {
        // Ensure session is started
        if (class_exists('SessionManager')) {
            SessionManager::start();
        } else if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(32));
        $_SESSION[self::TOKEN_NAME . '_time'] = time();
        
        if (class_exists('Logger')) {
            Logger::info("CSRF token regenerated");
        }
    }
    
    /**
     * Clear CSRF token from session
     */
    public static function clearToken() {
        // Ensure session is started
        if (class_exists('SessionManager')) {
            SessionManager::start();
        } else if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        unset($_SESSION[self::TOKEN_NAME]);
        unset($_SESSION[self::TOKEN_NAME . '_time']);
        
        if (class_exists('Logger')) {
            Logger::debug("CSRF token cleared");
        }
    }
}