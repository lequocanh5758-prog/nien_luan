# E-Commerce Development Plan - Phase 2

**Date:** 2026-05-19
**Author:** Development Team
**Status:** Approved

---

## Overview

Phase 2 focuses on **Security**, **Performance**, and **Shipping** improvements for the e-commerce platform.

### Features to Implement

| # | Feature | Priority | Timeline |
|---|---------|----------|----------|
| 1 | CDN (Cloudflare) | 🔴 High | Week 1 |
| 2 | OAuth2 (Google/Facebook) | 🔴 High | Week 2-3 |
| 3 | J&T Express Tracking | 🟡 Medium | Week 3-4 |
| 4 | Auto Return System | 🟡 Medium | Week 4-5 |

---

## 1. CDN (Cloudflare)

### Goals
- Reduce server load by 60-70%
- Improve page load time by 50%
- Enable global content delivery

### Implementation

**Step 1: Cloudflare Setup**
```
1. Create Cloudflare account
2. Add domain to Cloudflare
3. Update nameservers at domain registrar
4. Wait for DNS propagation (24-48 hours)
```

**Step 2: Configuration**
```php
// config/cdn.php
return [
    'enabled' => true,
    'provider' => 'cloudflare',
    'zone_id' => $_ENV['CLOUDFLARE_ZONE_ID'],
    'api_key' => $_ENV['CLOUDFLARE_API_KEY'],
    'cdn_url' => 'https://cdn.lqashop.com',
    'image_optimization' => true,
    'minify' => ['css', 'js', 'html'],
];
```

**Step 3: Image URL Helper**
```php
// app/Services/CDNService.php
class CDNService {
    public static function url(string $path): string {
        $cdnUrl = config('cdn.cdn_url');
        return $cdnUrl . '/' . ltrim($path, '/');
    }
    
    public static function image(string $path): string {
        // Auto-optimize images via Cloudflare Images
        return self::url('/cdn-cgi/image/width=auto,quality=80/' . $path);
    }
}
```

**Step 4: Update Image Paths**
```php
// Before
<img src="/lequocanh/uploads/product.jpg">

// After
<img src="<?= CDNService::image('/lequocanh/uploads/product.jpg') ?>">
```

### Expected Results
- TTFB: 200ms → 50ms
- Page Load: 3s → 1.5s
- Bandwidth: -70%

---

## 2. OAuth2 (Google/Facebook)

### Goals
- Increase user registration by 30-40%
- Simplify login process
- Reduce password fatigue

### Implementation

**Step 1: Google OAuth Setup**
```
1. Go to console.cloud.google.com
2. Create new project
3. Enable Google+ API
4. Create OAuth 2.0 credentials
5. Add redirect URI: https://lqashop.com/auth/google/callback
```

**Step 2: Facebook OAuth Setup**
```
1. Go to developers.facebook.com
2. Create new app
3. Add Facebook Login product
4. Add redirect URI: https://lqashop.com/auth/facebook/callback
```

**Step 3: OAuth Configuration**
```php
// config/oauth.php
return [
    'google' => [
        'client_id' => $_ENV['GOOGLE_CLIENT_ID'],
        'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'],
        'redirect_uri' => '/auth/google/callback',
        'scopes' => ['email', 'profile'],
    ],
    'facebook' => [
        'client_id' => $_ENV['FACEBOOK_CLIENT_ID'],
        'client_secret' => $_ENV['FACEBOOK_CLIENT_SECRET'],
        'redirect_uri' => '/auth/facebook/callback',
        'scopes' => ['email', 'public_profile'],
    ],
];
```

**Step 4: OAuth Service**
```php
// app/Services/OAuthService.php
class OAuthService {
    public function getGoogleAuthUrl(): string {
        $params = http_build_query([
            'client_id' => config('oauth.google.client_id'),
            'redirect_uri' => config('oauth.google.redirect_uri'),
            'response_type' => 'code',
            'scope' => implode(' ', config('oauth.google.scopes')),
            'access_type' => 'offline',
        ]);
        
        return "https://accounts.google.com/o/oauth2/auth?{$params}";
    }
    
    public function handleGoogleCallback(string $code): ?User {
        // Exchange code for access token
        // Get user info from Google
        // Create or update user in database
        // Return user object
    }
    
    public function getFacebookAuthUrl(): string {
        // Similar to Google
    }
    
    public function handleFacebookCallback(string $code): ?User {
        // Similar to Google
    }
}
```

**Step 5: Database Schema**
```sql
-- Add OAuth fields to users table
ALTER TABLE users ADD COLUMN google_id VARCHAR(50) NULL;
ALTER TABLE users ADD COLUMN facebook_id VARCHAR(50) NULL;
ALTER TABLE users ADD COLUMN avatar_url VARCHAR(500) NULL;
ALTER TABLE users ADD COLUMN auth_provider ENUM('local', 'google', 'facebook') DEFAULT 'local';

-- Create index
CREATE INDEX idx_users_google_id ON users(google_id);
CREATE INDEX idx_users_facebook_id ON users(facebook_id);
```

**Step 6: Login Buttons**
```html
<!-- Login Page -->
<div class="social-login">
    <a href="/auth/google" class="btn btn-google">
        <img src="/images/google-icon.png"> Đăng nhập với Google
    </a>
    <a href="/auth/facebook" class="btn btn-facebook">
        <img src="/images/facebook-icon.png"> Đăng nhập với Facebook
    </a>
</div>
```

### Expected Results
- Registration: +30-40%
- Login conversion: +20%
- Password reset requests: -50%

---

## 3. J&T Express Tracking

### Goals
- Real-time order tracking
- Automated shipping label generation
- Delivery status notifications

### Implementation

**Step 1: J&T API Registration**
```
1. Contact J&T Express for API access
2. Get API credentials (API Key, API Secret)
3. Register webhook URL for status updates
```

**Step 2: J&T Configuration**
```php
// config/jtexpress.php
return [
    'api_url' => 'https://api.jtexpress.vn',
    'api_key' => $_ENV['JT_API_KEY'],
    'api_secret' => $_ENV['JT_API_SECRET'],
    'shop_id' => $_ENV['JT_SHOP_ID'],
    'webhook_url' => '/api/jtexpress/webhook',
];
```

**Step 3: J&T Express Service**
```php
// app/Services/JTExpressService.php
class JTExpressService {
    private $apiUrl;
    private $apiKey;
    
    public function createOrder(array $orderData): array {
        // Create shipping order via J&T API
        // Return tracking number and label
    }
    
    public function trackOrder(string $trackingNumber): array {
        // Get tracking status from J&T API
        // Return timeline array
    }
    
    public function cancelOrder(string $trackingNumber): bool {
        // Cancel shipping order
    }
    
    public function getTrackingTimeline(string $trackingNumber): array {
        $response = $this->trackOrder($trackingNumber);
        
        return array_map(function($status) {
            return [
                'time' => $status['updateTime'],
                'status' => $status['statusDesc'],
                'location' => $status['location'],
                'icon' => $this->getStatusIcon($status['statusCode']),
            ];
        }, $response['details']);
    }
    
    private function getStatusIcon(string $statusCode): string {
        return match($statusCode) {
            'PICKUP' => '📦',
            'IN_TRANSIT' => '🚚',
            'OUT_FOR_DELIVERY' => '🛵',
            'DELIVERED' => '✅',
            'EXCEPTION' => '⚠️',
            default => '📋',
        };
    }
}
```

**Step 4: Webhook Handler**
```php
// api/jtexpress/webhook.php
class JTWebhookHandler {
    public function handle(array $data): void {
        $trackingNumber = $data['trackingNo'];
        $statusCode = $data['statusCode'];
        $statusDesc = $data['statusDesc'];
        
        // Update order status in database
        $this->updateOrderStatus($trackingNumber, $statusCode);
        
        // Send notification to customer
        $this->sendNotification($trackingNumber, $statusDesc);
        
        // Log webhook
        $this->logWebhook($data);
    }
}
```

**Step 5: Tracking Page**
```php
<!-- order_tracking.php -->
<div class="tracking-timeline">
    <?php foreach ($timeline as $step): ?>
    <div class="timeline-item <?= $step['completed'] ? 'completed' : '' ?>">
        <div class="timeline-icon"><?= $step['icon'] ?></div>
        <div class="timeline-content">
            <strong><?= $step['status'] ?></strong>
            <span class="text-muted"><?= $step['time'] ?></span>
            <span class="text-muted"><?= $step['location'] ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>
```

### Expected Results
- Customer inquiries: -60%
- Delivery transparency: +100%
- Customer satisfaction: +40%

---

## 4. Auto Return System

### Goals
- Automate return process
- Optimize return method selection
- Reduce manual processing

### Implementation

**Step 1: Return Policy Configuration**
```php
// config/return_policy.php
return [
    'return_window_days' => 7,
    'eligible_statuses' => ['completed'],
    'auto_approve_threshold' => 500000, // VND
    'methods' => [
        'self_ship' => [
            'enabled' => true,
            'max_distance' => 50, // km
        ],
        'pickup' => [
            'enabled' => true,
            'fee' => 30000, // VND
        ],
        'drop_off' => [
            'enabled' => true,
            'locations' => ['J&T Post Office'],
        ],
    ],
];
```

**Step 2: Return Decision Engine**
```php
// app/Services/ReturnDecisionEngine.php
class ReturnDecisionEngine {
    public function decide(array $returnRequest): array {
        $factors = $this->analyzeFactors($returnRequest);
        
        return match(true) {
            $factors['is_near_drop_off'] => [
                'method' => 'drop_off',
                'reason' => 'Gần bưu cục J&T',
                'estimated_time' => '1-2 ngày',
                'cost' => 0,
            ],
            $factors['is_high_value'] => [
                'method' => 'pickup',
                'reason' => 'Đơn hàng giá trị cao, hỗ trợ lấy hàng tận nơi',
                'estimated_time' => '1-3 ngày',
                'cost' => 0,
            ],
            $factors['customer_preferred'] => [
                'method' => $returnRequest['preferred_method'],
                'reason' => 'Theo yêu cầu khách hàng',
                'estimated_time' => '2-5 ngày',
                'cost' => $this->calculateCost($returnRequest['preferred_method']),
            ],
            default => [
                'method' => 'self_ship',
                'reason' => 'Phương án mặc định',
                'estimated_time' => '3-7 ngày',
                'cost' => 0,
            ],
        };
    }
    
    private function analyzeFactors(array $request): array {
        return [
            'is_near_drop_off' => $this->checkNearDropOff($request['address']),
            'is_high_value' => $request['order_total'] > 1000000,
            'customer_preferred' => !empty($request['preferred_method']),
            'is_bulk_return' => $request['item_count'] > 3,
        ];
    }
}
```

**Step 3: Return Automation Flow**
```
Customer requests return
    ↓
System checks eligibility (7-day window, order status)
    ↓
Auto-approve if < threshold (500,000 VND)
    ↓
ReturnDecisionEngine selects optimal method
    ↓
Create J&T pickup order (if method = pickup)
    ↓
Send notification to customer with instructions
    ↓
Track return shipment
    ↓
Refund when item received
```

**Step 4: Return Status Notifications**
```php
// Email templates for return status
'pending_approval' => 'Yêu cầu đổi trả đang được xem xét',
'approved' => 'Yêu cầu đổi trả đã được chấpận',
'pickup_scheduled' => 'Đã lên lịch lấy hàng trả',
'in_transit' => 'Hàng trả đang được vận chuyển',
'received' => 'Đã nhận hàng trả',
'refunded' => 'Đã hoàn tiền',
```

### Expected Results
- Manual processing: -80%
- Return processing time: 7 days → 3 days
- Customer satisfaction: +50%

---

## Implementation Timeline

```
Week 1: CDN (Cloudflare)
├── Day 1-2: Setup Cloudflare account
├── Day 3-4: Configure CDN settings
└── Day 5: Test and optimize

Week 2-3: OAuth2
├── Day 1-2: Google OAuth setup
├── Day 3-4: Facebook OAuth setup
├── Day 5-7: Database migration + testing
└── Day 8-10: UI integration

Week 3-4: J&T Express Tracking
├── Day 1-2: API integration
├── Day 3-4: Webhook handler
├── Day 5-7: Tracking page UI
└── Day 8-10: Testing + optimization

Week 4-5: Auto Return System
├── Day 1-2: Return policy configuration
├── Day 3-4: Decision engine
├── Day 5-7: Automation flow
└── Day 8-10: Testing + deployment
```

---

## Success Metrics

| Metric | Current | Target |
|--------|---------|--------|
| Page Load Time | 3s | 1.5s |
| Server Load | 100% | 30% |
| User Registration | 100/month | 140/month |
| Customer Inquiries | 50/day | 20/day |
| Return Processing | 7 days | 3 days |

---

## Dependencies

- Cloudflare account (free tier available)
- Google Cloud Console access
- Facebook Developer account
- J&T Express API credentials
- Redis server (already configured)

---

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| CDN downtime | High | Fallback to origin server |
| OAuth security | High | Use official libraries, validate tokens |
| J&T API changes | Medium | Abstract API layer, monitor changes |
| Return abuse | Medium | Fraud detection, manual review for high-value |

---

## Approval

- [ ] Design reviewed
- [ ] Timeline approved
- [ ] Resources allocated
- [ ] Ready to implement
