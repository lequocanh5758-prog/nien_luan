<?php

class QueryCache {

    private static $instance = null;
    private $cacheDir;
    private $ttl;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct($cacheDir = 'cache/', $ttl = 3600) {
        $this->cacheDir = $cacheDir;
        $this->ttl = $ttl;

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    private function generateKey($query, $params) {
        return md5($query . serialize($params));
    }

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

    public function set($query, $params, $data) {
        $key = $this->generateKey($query, $params);
        $cacheFile = $this->cacheDir . $key;

        $content = serialize(['time' => time(), 'data' => $data]);
        file_put_contents($cacheFile, $content);
    }

    public function delete($query, $params = []) {
        $key = $this->generateKey($query, $params);
        $cacheFile = $this->cacheDir . $key;

        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }

    public function clearAll() {
        $files = glob($this->cacheDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    public function query($pdo, $sql, $params = [], $ttl = 300) {
        $cacheKey = $this->generateKey($sql, $params);
        
        $cached = $this->get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $i => $param) {
            $type = is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($i + 1, $param, $type);
        }
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        $this->set($cacheKey, [], $result);
        
        return $result;
    }
    
    public function queryOne($pdo, $sql, $params = [], $ttl = 300) {
        $cacheKey = $this->generateKey($sql, $params) . '_one';
        
        $cached = $this->get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $i => $param) {
            $type = is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($i + 1, $param, $type);
        }
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        
        $this->set($cacheKey, [], $result);
        
        return $result;
    }
}
?>