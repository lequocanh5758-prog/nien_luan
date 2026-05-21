<?php
declare(strict_types=1);

namespace App\Services;

class RateLimiter
{
    private static ?RateLimiter $instance = null;
    private int $maxRequests;
    private int $windowSeconds;
    
    private function __construct(int $maxRequests = 60, int $windowSeconds = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Check if request is allowed
     */
    public function isAllowed(string $identifier): bool
    {
        $key = "rate_limit_{$identifier}";
        $current = $this->getFromCache($key);
        
        if ($current === null) {
            $this->setCache($key, 1, $this->windowSeconds);
            return true;
        }
        
        if ($current >= $this->maxRequests) {
            return false;
        }
        
        $this->incrementCache($key);
        return true;
    }
    
    /**
     * Get remaining requests
     */
    public function getRemaining(string $identifier): int
    {
        $key = "rate_limit_{$identifier}";
        $current = $this->getFromCache($key);
        
        return max(0, $this->maxRequests - ($current ?? 0));
    }
    
    /**
     * Get reset time
     */
    public function getResetTime(string $identifier): int
    {
        $key = "rate_limit_{$identifier}";
        return $this->getCacheTTL($key);
    }
    
    /**
     * Get client identifier
     */
    public function getClientIdentifier(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return md5($ip . $userAgent);
    }
    
    /**
     * Apply rate limiting to current request
     */
    public function apply(): bool
    {
        $identifier = $this->getClientIdentifier();
        
        if (!$this->isAllowed($identifier)) {
            http_response_code(429);
            header('Retry-After: ' . $this->getResetTime($identifier));
            echo json_encode([
                'success' => false,
                'message' => 'Too many requests. Please try again later.'
            ]);
            return false;
        }
        
        // Add rate limit headers
        header('X-RateLimit-Limit: ' . $this->maxRequests);
        header('X-RateLimit-Remaining: ' . $this->getRemaining($identifier));
        
        return true;
    }
    
    private function getFromCache(string $key): ?int
    {
        if (class_exists('App\Services\CacheManager')) {
            return CacheManager::getInstance()->get($key);
        }
        
        // Fallback to session
        return $_SESSION[$key] ?? null;
    }
    
    private function setCache(string $key, int $value, int $ttl): void
    {
        if (class_exists('App\Services\CacheManager')) {
            CacheManager::getInstance()->set($key, $value, $ttl);
        } else {
            $_SESSION[$key] = $value;
        }
    }
    
    private function incrementCache(string $key): void
    {
        if (class_exists('App\Services\CacheManager')) {
            $current = CacheManager::getInstance()->get($key) ?? 0;
            CacheManager::getInstance()->set($key, $current + 1, $this->windowSeconds);
        } else {
            $_SESSION[$key] = ($_SESSION[$key] ?? 0) + 1;
        }
    }
    
    private function getCacheTTL(string $key): int
    {
        return $this->windowSeconds;
    }
}