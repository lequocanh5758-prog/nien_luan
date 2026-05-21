# CDN (Cloudflare) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Integrate Cloudflare CDN to reduce server load by 60-70% and improve page load time by 50%.

**Architecture:** Cloudflare will act as reverse proxy, caching static assets (images, CSS, JS) at edge locations worldwide. Origin server will serve dynamic content only.

**Tech Stack:** Cloudflare (Free/Pro plan), PHP, Apache/Nginx

---

## File Structure

| File | Responsibility |
|------|----------------|
| `config/cdn.php` | CDN configuration |
| `app/Services/CDNService.php` | CDN URL helper |
| `.htaccess` | Cache headers |
| `components/head.php` | CDN asset URLs |

---

### Task 1: Cloudflare Account Setup

**Files:**
- Create: `config/cdn.php`
- Modify: `.env`

- [ ] **Step 1: Create Cloudflare account**

1. Go to https://dash.cloudflare.com/sign-up
2. Enter email and password
3. Verify email

- [ ] **Step 2: Add domain to Cloudflare**

1. Click "Add a Site"
2. Enter domain: `lqashop.com`
3. Select plan: Free ($0/month)
4. Click "Add Site"

- [ ] **Step 3: Update nameservers**

At your domain registrar:
```
ns1.cloudflare.com
ns2.cloudflare.com
```

Wait 24-48 hours for propagation.

- [ ] **Step 4: Create CDN config file**

```php
<?php
// config/cdn.php
return [
    'enabled' => (bool)$_ENV['CDN_ENABLED'] ?? false,
    'provider' => 'cloudflare',
    'zone_id' => $_ENV['CLOUDFLARE_ZONE_ID'] ?? '',
    'api_token' => $_ENV['CLOUDFLARE_API_TOKEN'] ?? '',
    'cdn_url' => $_ENV['CDN_URL'] ?? '',
    'image_optimization' => true,
    'minify' => ['css', 'js', 'html'],
    'cache_ttl' => [
        'images' => 2592000,    // 30 days
        'css' => 86400,         // 1 day
        'js' => 86400,          // 1 day
        'html' => 3600,         // 1 hour
    ],
];
```

- [ ] **Step 5: Add CDN variables to .env**

```bash
# .env
CDN_ENABLED=true
CLOUDFLARE_ZONE_ID=your_zone_id_here
CLOUDFLARE_API_TOKEN=your_api_token_here
CDN_URL=https://cdn.lqashop.com
```

- [ ] **Step 6: Commit**

```bash
git add config/cdn.php .env
git commit -m "feat: add CDN configuration for Cloudflare"
```

---

### Task 2: CDN Service

**Files:**
- Create: `app/Services/CDNService.php`
- Test: `tests/Unit/CDNServiceTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php
// tests/Unit/CDNServiceTest.php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\CDNService;

class CDNServiceTest extends TestCase
{
    public function testUrlReturnsCDNPath()
    {
        $cdn = new CDNService('https://cdn.example.com');
        $this->assertEquals(
            'https://cdn.example.com/uploads/image.jpg',
            $cdn->url('/uploads/image.jpg')
        );
    }
    
    public function testImageReturnsOptimizedPath()
    {
        $cdn = new CDNService('https://cdn.example.com');
        $result = $cdn->image('/uploads/product.jpg', 800, 80);
        $this->assertStringContainsString('/cdn-cgi/image/', $result);
    }
    
    public function testDisabledReturnsOriginalPath()
    {
        $cdn = new CDNService('', false);
        $this->assertEquals(
            '/uploads/image.jpg',
            $cdn->url('/uploads/image.jpg')
        );
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
./vendor/bin/phpunit tests/Unit/CDNServiceTest.php -v
```

Expected: FAIL with "Class App\Services\CDNService not found"

- [ ] **Step 3: Write minimal implementation**

```php
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
        $config = require __DIR__ . '/../../config/cdn.php';
        
        return new self(
            $config['cdn_url'] ?? '',
            $config['enabled'] ?? false,
            $config['image_optimization'] ?? true
        );
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
./vendor/bin/phpunit tests/Unit/CDNServiceTest.php -v
```

Expected: OK (3 tests, 3 assertions)

- [ ] **Step 5: Commit**

```bash
git add app/Services/CDNService.php tests/Unit/CDNServiceTest.php
git commit -m "feat: add CDNService for Cloudflare integration"
```

---

### Task 3: Cache Headers

**Files:**
- Modify: `.htaccess`

- [ ] **Step 1: Add cache headers to .htaccess**

```apache
# ===================================
# CDN CACHE HEADERS
# ===================================
<IfModule mod_headers.c>
    # Images - 30 days
    <FilesMatch "\.(jpg|jpeg|png|gif|webp|ico|svg)$">
        Header set Cache-Control "public, max-age=2592000, immutable"
        Header set X-CDN-Cache "HIT"
    </FilesMatch>
    
    # CSS/JS - 1 day
    <FilesMatch "\.(css|js)$">
        Header set Cache-Control "public, max-age=86400"
    </FilesMatch>
    
    # Fonts - 30 days
    <FilesMatch "\.(woff|woff2|ttf|eot)$">
        Header set Cache-Control "public, max-age=2592000, immutable"
    </FilesMatch>
    
    # HTML - no cache (dynamic)
    <FilesMatch "\.html$">
        Header set Cache-Control "no-cache, must-revalidate"
    </FilesMatch>
    
    # API - no cache
    <FilesMatch "\.php$">
        Header set Cache-Control "no-cache, no-store, must-revalidate"
    </FilesMatch>
</IfModule>

# Enable browser caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/webp "access plus 1 month"
    ExpiresByType text/css "access plus 1 day"
    ExpiresByType application/javascript "access plus 1 day"
    ExpiresByType application/font-woff2 "access plus 1 month"
</IfModule>
```

- [ ] **Step 2: Commit**

```bash
git add .htaccess
git commit -m "perf: add cache headers for CDN optimization"
```

---

### Task 4: Update Image Paths

**Files:**
- Modify: `components/head.php`
- Modify: `apart/viewHangHoa.php`
- Modify: `administrator/elements_LQA/mhanghoa/displayImage.php`

- [ ] **Step 1: Update head.php to use CDN**

```php
<!-- Add CDN service initialization -->
<?php
$cdn = \App\Services\CDNService::fromConfig();
?>
```

- [ ] **Step 2: Update image paths in viewHangHoa.php**

```php
// Before
<img src="/lequocanh/administrator/elements_LQA/mhanghoa/displayImage.php?id=<?= $obj->hinhanh ?>">

// After
<img src="<?= $cdn->image('/lequocanh/administrator/elements_LQA/mhanghoa/displayImage.php?id=' . $obj->hinhanh, 800, 80) ?>">
```

- [ ] **Step 3: Commit**

```bash
git add components/head.php apart/viewHangHoa.php
git commit -m "perf: update image paths to use CDN"
```

---

### Task 5: Cloudflare Page Rules

**Files:**
- None (Cloudflare dashboard)

- [ ] **Step 1: Create Page Rule for images**

In Cloudflare Dashboard → Rules → Page Rules:

```
URL: *lqashop.com/uploads/*
Settings:
- Cache Level: Cache Everything
- Edge Cache TTL: 1 month
- Browser Cache TTL: 1 month
```

- [ ] **Step 2: Create Page Rule for static assets**

```
URL: *lqashop.com/public_files/*
Settings:
- Cache Level: Cache Everything
- Edge Cache TTL: 1 day
- Browser Cache TTL: 1 day
```

- [ ] **Step 3: Enable Auto Minify**

In Cloudflare Dashboard → Speed → Optimization:

- [x] Auto Minify CSS
- [x] Auto Minify JavaScript
- [x] Auto Minify HTML
- [x] Brotli

---

## Verification

After implementation, verify:

```bash
# Check CDN headers
curl -I https://cdn.lqashop.com/uploads/image.jpg

# Expected headers:
# CF-Cache-Status: HIT
# Cache-Control: public, max-age=2592000
# X-CDN-Cache: HIT

# Check page speed
# Use: https://pagespeed.web.dev/
# Target: Performance score > 90
```

---

## Success Metrics

| Metric | Before | Target |
|--------|--------|--------|
| Page Load Time | 3s | 1.5s |
| TTFB | 200ms | 50ms |
| Server Load | 100% | 30% |
| Bandwidth | 100% | 30% |
