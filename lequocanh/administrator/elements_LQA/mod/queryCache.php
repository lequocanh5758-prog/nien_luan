<?php
/**
 * Query Cache
 * 
 * A simple file-based caching mechanism for database query results.
 */
class QueryCache {

    private $cacheDir;
    private $ttl; // Time to live in seconds

    /**
     * Constructor
     * @param string $cacheDir The directory to store cache files.
     * @param int $ttl The default time to live for cache files in seconds.
     */
    public function __construct($cacheDir = 'cache/', $ttl = 3600) {
        $this->cacheDir = $cacheDir;
        $this->ttl = $ttl;

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Generates a unique cache key for a given query and parameters.
     * @param string $query The SQL query.
     * @param array $params The parameters for the query.
     * @return string The cache key.
     */
    private function generateKey($query, $params) {
        return md5($query . serialize($params));
    }

    /**
     * Retrieves a cached result.
     * @param string $query The SQL query.
     * @param array $params The parameters for the query.
     * @return mixed The cached data or false if not found or expired.
     */
    public function get($query, $params = []) {
        $key = $this->generateKey($query, $params);
        $cacheFile = $this->cacheDir . $key;

        if (file_exists($cacheFile)) {
            $content = file_get_contents($cacheFile);
            $data = unserialize($content);

            if (time() - $data['time'] < $this->ttl) {
                return $data['data'];
            }
        }

        return false;
    }

    /**
     * Caches a result.
     * @param string $query The SQL query.
     * @param array $params The parameters for the query.
     * @param mixed $data The data to cache.
     */
    public function set($query, $params = [], $data) {
        $key = $this->generateKey($query, $params);
        $cacheFile = $this->cacheDir . $key;

        $content = serialize(['time' => time(), 'data' => $data]);
        file_put_contents($cacheFile, $content);
    }

    /**
     * Deletes a specific cache entry.
     * @param string $query The SQL query.
     * @param array $params The parameters for the query.
     */
    public function delete($query, $params = []) {
        $key = $this->generateKey($query, $params);
        $cacheFile = $this->cacheDir . $key;

        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }

    /**
     * Clears the entire query cache.
     */
    public function clearAll() {
        $files = glob($this->cacheDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
?>