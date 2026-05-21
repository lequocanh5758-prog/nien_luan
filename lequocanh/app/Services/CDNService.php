<?php
declare(strict_types=1);

namespace App\Services;

class CDNService
{
    private string $cdnUrl;
    private bool $enabled;
    private bool $imageOptimization;
    
    public function __construct(
        string $cdnUrl = '',
        bool $enabled = true,
        bool $imageOptimization = true
    ) {
        $this->cdnUrl = rtrim($cdnUrl, '/');
        $this->enabled = $enabled;
        $this->imageOptimization = $imageOptimization;
    }
    
    /**
     * Get CDN URL for asset
     */
    public function url(string $path): string
    {
        if (!$this->enabled || empty($this->cdnUrl)) {
            return $path;
        }
        
        return $this->cdnUrl . '/' . ltrim($path, '/');
    }
    
    /**
     * Get optimized image URL via Cloudflare Images
     */
    public function image(
        string $path,
        int $width = 0,
        int $quality = 80,
        string $format = 'auto'
    ): string {
        if (!$this->enabled || !$this->imageOptimization) {
            return $this->url($path);
        }
        
        $params = [];
        if ($width > 0) {
            $params[] = "width={$width}";
        }
        $params[] = "quality={$quality}";
        $params[] = "format={$format}";
        
        $paramString = implode(',', $params);
        
        return $this->cdnUrl . '/cdn-cgi/image/' . $paramString . $path;
    }
    
    /**
     * Create from config
     */
    public static function fromConfig(): self
    {
        $configPath = __DIR__ . '/../../config/cdn.php';
        
        if (!file_exists($configPath)) {
            return new self('', false, false);
        }
        
        $config = require $configPath;
        
        return new self(
            $config['cdn_url'] ?? '',
            $config['enabled'] ?? false,
            $config['image_optimization'] ?? true
        );
    }
    
    /**
     * Check if CDN is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled && !empty($this->cdnUrl);
    }
    
    /**
     * Get CDN URL
     */
    public function getCdnUrl(): string
    {
        return $this->cdnUrl;
    }
}