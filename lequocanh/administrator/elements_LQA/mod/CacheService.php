<?php

class CacheService {
    
    private $cacheDir;
    private $defaultTTL = 3600;
    
    public function __construct() {
        $this->cacheDir = __DIR__ . '/../../../../cache';
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public function get($key) {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $data = json_decode(file_get_contents($filename), true);
        
        if (!$data) {
            return null;
        }
        
        if (isset($data['expires_at']) && time() > $data['expires_at']) {
            $this->delete($key);
            return null;
        }
        
        return $data['value'] ?? null;
    }
    
    public function set($key, $value, $ttl = null) {
        $ttl = $ttl ?? $this->defaultTTL;
        $filename = $this->getCacheFilename($key);
        
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
            'created_at' => time()
        ];
        
        return file_put_contents($filename, json_encode($data)) !== false;
    }
    
    public function delete($key) {
        $filename = $this->getCacheFilename($key);
        
        if (file_exists($filename)) {
            return unlink($filename);
        }
        
        return true;
    }
    
    public function clear() {
        $files = glob($this->cacheDir . '/*.cache');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }

    public function remember($key, $callback, $ttl = null) {
        $cached = $this->get($key);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    private function getCacheFilename($key) {
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }
    
    public function getStats() {
        $files = glob($this->cacheDir . '/*.cache');
        $totalSize = 0;
        $totalFiles = 0;
        $expired = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                $totalFiles++;
                $totalSize += filesize($file);
                $data = json_decode(file_get_contents($file), true);
                if ($data && isset($data['expires_at']) && time() > $data['expires_at']) {
                    $expired++;
                }
            }
        }

        return [
            'total_files' => $totalFiles,
            'total_size' => $this->formatBytes($totalSize),
            'total_size_bytes' => $totalSize,
            'expired_files' => $expired
        ];
    }

    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    public function cleanExpired() {
        $files = glob($this->cacheDir . '/*.cache');
        $cleaned = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                $data = json_decode(file_get_contents($file), true);
                if ($data && isset($data['expires_at']) && time() > $data['expires_at']) {
                    unlink($file);
                    $cleaned++;
                }
            }
        }

        return $cleaned;
    }
}