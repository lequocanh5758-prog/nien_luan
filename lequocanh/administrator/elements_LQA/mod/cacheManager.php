<?php
require_once 'queryCache.php';

/**
 * Cache Manager
 * 
 * A comprehensive cache manager that handles different caching strategies,
 * including data caching, file-based caching, and template caching.
 */
class CacheManager {

    private $queryCache;
    private $fileCacheDir;
    private $templateCacheDir;
    private $ttl;

    /**
     * Constructor
     * @param int $ttl Default time to live for all cache types.
     */
    public function __construct($ttl = 3600) {
        $this->ttl = $ttl;
        
        // Initialize QueryCache
        $this->queryCache = new QueryCache('cache/query/', $ttl);

        // Setup directories for other cache types
        $this->fileCacheDir = 'cache/files/';
        $this->templateCacheDir = 'cache/templates/';

        if (!is_dir($this->fileCacheDir)) {
            mkdir($this->fileCacheDir, 0755, true);
        }
        if (!is_dir($this->templateCacheDir)) {
            mkdir($this->templateCacheDir, 0755, true);
        }
    }

    /**
     * Get the QueryCache instance.
     * @return QueryCache
     */
    public function getQueryCache() {
        return $this->queryCache;
    }

    /**
     * Caches generic data.
     * @param string $key A unique key for the data.
     * @param mixed $data The data to be cached.
     * @param int|null $ttl Specific TTL for this item.
     */
    public function setData($key, $data, $ttl = null) {
        $cacheFile = $this->fileCacheDir . md5($key);
        $lifetime = $ttl ?? $this->ttl;
        $content = serialize(['time' => time(), 'ttl' => $lifetime, 'data' => $data]);
        file_put_contents($cacheFile, $content);
    }

    /**
     * Retrieves generic cached data.
     * @param string $key The key for the data.
     * @return mixed The cached data or false if not found or expired.
     */
    public function getData($key) {
        $cacheFile = $this->fileCacheDir . md5($key);

        if (file_exists($cacheFile)) {
            $content = file_get_contents($cacheFile);
            $data = unserialize($content);

            if (time() - $data['time'] < $data['ttl']) {
                return $data['data'];
            }
        }
        return false;
    }
    
    /**
     * Caches template output.
     * @param string $templatePath Path to the template file.
     * @param string $output The rendered output of the template.
     */
    public function setTemplateCache($templatePath, $output) {
        $cacheFile = $this->templateCacheDir . md5($templatePath);
        file_put_contents($cacheFile, $output);
    }

    /**
     * Retrieves cached template output if it's still valid.
     * @param string $templatePath Path to the template file.
     * @return string|false The cached output or false.
     */
    public function getTemplateCache($templatePath) {
        $cacheFile = $this->templateCacheDir . md5($templatePath);

        // Invalidate cache if the original template file has been modified
        if (file_exists($cacheFile) && filemtime($cacheFile) > filemtime($templatePath)) {
            return file_get_contents($cacheFile);
        }

        return false;
    }

    /**
     * Clears all types of cache.
     */
    public function clearAllCaches() {
        $this->queryCache->clearAll();
        $this->clearDirectory($this->fileCacheDir);
        $this->clearDirectory($this->templateCacheDir);
    }

    private function clearDirectory($dir) {
        $files = glob($dir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
?>