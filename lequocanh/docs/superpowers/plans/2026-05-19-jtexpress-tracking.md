# J&T Express Tracking Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Integrate J&T Express API for real-time order tracking, automated shipping labels, and delivery notifications.

**Architecture:** J&T Express REST API integration with webhook for status updates. Tracking data cached in Redis for performance.

**Tech Stack:** J&T Express API, PHP, MySQL, Redis

---

## File Structure

| File | Responsibility |
|------|----------------|
| `config/jtexpress.php` | J&T configuration |
| `app/Services/JTExpressService.php` | API integration |
| `app/Controllers/JTWebhookController.php` | Webhook handler |
| `api/jtexpress/webhook.php` | Webhook endpoint |
| `database/migrations/add_jt_tracking.php` | DB schema |

---

### Task 1: J&T API Registration

**Files:**
- Create: `config/jtexpress.php`
- Modify: `.env`

- [ ] **Step 1: Register J&T API access**

1. Contact J&T Express: https://www.jtexpress.vn/lien-he
2. Request API access for merchant
3. Receive: API Key, API Secret, Shop ID

- [ ] **Step 2: Create J&T config**

```php
<?php
// config/jtexpress.php
return [
    'api_url' => $_ENV['JT_API_URL'] ?? 'https://api.jtexpress.vn',
    'api_key' => $_ENV['JT_API_KEY'] ?? '',
    'api_secret' => $_ENV['JT_API_SECRET'] ?? '',
    'shop_id' => $_ENV['JT_SHOP_ID'] ?? '',
    'webhook_secret' => $_ENV['JT_WEBHOOK_SECRET'] ?? '',
    'webhook_url' => '/api/jtexpress/webhook',
    
    // Default sender info
    'sender' => [
        'name' => 'LQA Shop',
        'phone' => '0901234567',
        'address' => '123 Đường ABC',
        'ward' => 'Phường XYZ',
        'district' => 'Quận 1',
        'city' => 'TP.HCM',
    ],
    
    // Service types
    'services' => [
        'standard' => ['name' => 'Tiêu chuẩn', 'days' => '3-5'],
        'express' => ['name' => 'Nhanh', 'days' => '1-2'],
        'sameday' => ['name' => 'Trong ngày', 'days' => '1'],
    ],
];
```

- [ ] **Step 3: Add J&T variables to .env**

```bash
# .env
JT_API_URL=https://api.jtexpress.vn
JT_API_KEY=your_api_key
JT_API_SECRET=your_api_secret
JT_SHOP_ID=your_shop_id
JT_WEBHOOK_SECRET=your_webhook_secret
```

- [ ] **Step 4: Commit**

```bash
git add config/jtexpress.php .env
git commit -m "feat: add J&T Express configuration"
```

---

### Task 2: Database Migration

**Files:**
- Create: `database/migrations/2026_05_19_add_jt_tracking.php`

- [ ] **Step 1: Create migration**

```php
<?php
// database/migrations/2026_05_19_add_jt_tracking.php
class AddJTTracking
{
    public function up()
    {
        $db = \Database::getInstance()->getConnection();
        
        // Add tracking fields to don_hang table
        $db->exec("
            ALTER TABLE don_hang 
            ADD COLUMN tracking_number VARCHAR(50) NULL AFTER order_notes,
            ADD COLUMN shipping_carrier VARCHAR(50) DEFAULT 'jtexpress' AFTER tracking_number,
            ADD COLUMN shipping_label_url VARCHAR(500) NULL AFTER shipping_carrier,
            ADD COLUMN estimated_delivery DATE NULL AFTER shipping_label_url
        ");
        
        // Create tracking events table
        $db->exec("
            CREATE TABLE IF NOT EXISTS tracking_events (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                tracking_number VARCHAR(50) NOT NULL,
                status_code VARCHAR(50) NOT NULL,
                status_desc VARCHAR(255) NOT NULL,
                location VARCHAR(255) NULL,
                event_time DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_order_id (order_id),
                INDEX idx_tracking_number (tracking_number),
                INDEX idx_event_time (event_time)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Create webhook logs table
        $db->exec("
            CREATE TABLE IF NOT EXISTS webhook_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                provider VARCHAR(50) NOT NULL,
                payload TEXT NOT NULL,
                status VARCHAR(20) DEFAULT 'received',
                processed_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_provider (provider),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
    
    public function down()
    {
        $db = \Database::getInstance()->getConnection();
        
        $db->exec("ALTER TABLE don_hang DROP COLUMN tracking_number");
        $db->exec("ALTER TABLE don_hang DROP COLUMN shipping_carrier");
        $db->exec("ALTER TABLE don_hang DROP COLUMN shipping_label_url");
        $db->exec("ALTER TABLE don_hang DROP COLUMN estimated_delivery");
        $db->exec("DROP TABLE IF EXISTS tracking_events");
        $db->exec("DROP TABLE IF EXISTS webhook_logs");
    }
}
```

- [ ] **Step 2: Run migration**

```bash
php database/migrate.php
```

- [ ] **Step 3: Commit**

```bash
git add database/migrations/
git commit -m "feat: add J&T tracking database schema"
```

---

### Task 3: J&T Express Service

**Files:**
- Create: `app/Services/JTExpressService.php`
- Test: `tests/Unit/JTExpressServiceTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php
// tests/Unit/JTExpressServiceTest.php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\JTExpressService;

class JTExpressServiceTest extends TestCase
{
    public function testGetStatusIcon()
    {
        $service = new JTExpressService([]);
        
        $this->assertEquals('📦', $service->getStatusIcon('PICKUP'));
        $this->assertEquals('🚚', $service->getStatusIcon('IN_TRANSIT'));
        $this->assertEquals('✅', $service->getStatusIcon('DELIVERED'));
    }
    
    public function testFormatTrackingTimeline()
    {
        $service = new JTExpressService([]);
        
        $data = [
            'details' => [
                [
                    'updateTime' => '2026-05-19 10:00:00',
                    'statusDesc' => 'Picked up',
                    'statusCode' => 'PICKUP',
                    'location' => 'HCM Hub',
                ],
            ]
        ];
        
        $timeline = $service->formatTrackingTimeline($data);
        $this->assertCount(1, $timeline);
        $this->assertEquals('📦', $timeline[0]['icon']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
./vendor/bin/phpunit tests/Unit/JTExpressServiceTest.php -v
```

Expected: FAIL with "Class App\Services\JTExpressService not found"

- [ ] **Step 3: Write implementation**

```php
<?php
declare(strict_types=1);

namespace App\Services;

class JTExpressService
{
    private string $apiUrl;
    private string $apiKey;
    private string $apiSecret;
    private string $shopId;
    
    public function __construct(array $config)
    {
        $this->apiUrl = $config['api_url'] ?? 'https://api.jtexpress.vn';
        $this->apiKey = $config['api_key'] ?? '';
        $this->apiSecret = $config['api_secret'] ?? '';
        $this->shopId = $config['shop_id'] ?? '';
    }
    
    /**
     * Create shipping order
     */
    public function createOrder(array $orderData): array
    {
        $payload = [
            'shop_id' => $this->shopId,
            'order_no' => $orderData['order_code'],
            'sender' => $orderData['sender'],
            'receiver' => [
                'name' => $orderData['receiver_name'],
                'phone' => $orderData['receiver_phone'],
                'address' => $orderData['address'],
                'ward' => $orderData['ward'],
                'district' => $orderData['district'],
                'city' => $orderData['city'],
            ],
            'items' => $orderData['items'],
            'service_type' => $orderData['service_type'] ?? 'standard',
            'cod_amount' => $orderData['cod_amount'] ?? 0,
            'insurance_amount' => $orderData['insurance_amount'] ?? 0,
        ];
        
        $response = $this->apiCall('POST', '/api/order/create', $payload);
        
        return [
            'tracking_number' => $response['tracking_no'] ?? '',
            'label_url' => $response['label_url'] ?? '',
            'estimated_delivery' => $response['estimated_delivery'] ?? '',
        ];
    }
    
    /**
     * Track order
     */
    public function trackOrder(string $trackingNumber): array
    {
        $response = $this->apiCall('GET', "/api/tracking?tracking_no={$trackingNumber}");
        
        return $response;
    }
    
    /**
     * Get tracking timeline
     */
    public function getTrackingTimeline(string $trackingNumber): array
    {
        $data = $this->trackOrder($trackingNumber);
        
        return $this->formatTrackingTimeline($data);
    }
    
    /**
     * Format tracking timeline
     */
    public function formatTrackingTimeline(array $data): array
    {
        $timeline = [];
        
        foreach (($data['details'] ?? []) as $detail) {
            $timeline[] = [
                'time' => $detail['updateTime'],
                'status' => $detail['statusDesc'],
                'location' => $detail['location'] ?? '',
                'icon' => $this->getStatusIcon($detail['statusCode']),
                'completed' => $this->isCompletedStatus($detail['statusCode']),
            ];
        }
        
        return $timeline;
    }
    
    /**
     * Get status icon
     */
    public function getStatusIcon(string $statusCode): string
    {
        return match($statusCode) {
            'PICKUP' => '📦',
            'IN_TRANSIT' => '🚚',
            'OUT_FOR_DELIVERY' => '🛵',
            'DELIVERED' => '✅',
            'EXCEPTION' => '⚠️',
            'RETURNED' => '↩️',
            default => '📋',
        };
    }
    
    /**
     * Check if status is completed
     */
    private function isCompletedStatus(string $statusCode): bool
    {
        return in_array($statusCode, ['PICKUP', 'IN_TRANSIT', 'OUT_FOR_DELIVERY', 'DELIVERED']);
    }
    
    /**
     * Cancel order
     */
    public function cancelOrder(string $trackingNumber): bool
    {
        try {
            $this->apiCall('POST', '/api/order/cancel', [
                'tracking_no' => $trackingNumber,
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Make API call
     */
    private function apiCall(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->apiUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'API-Key: ' . $this->apiKey,
            'API-Secret: ' . $this->apiSecret,
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \RuntimeException("J&T API error: HTTP {$httpCode}");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Create from config
     */
    public static function fromConfig(): self
    {
        $config = require __DIR__ . '/../../config/jtexpress.php';
        return new self($config);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
./vendor/bin/phpunit tests/Unit/JTExpressServiceTest.php -v
```

Expected: OK (2 tests, 4 assertions)

- [ ] **Step 5: Commit**

```bash
git add app/Services/JTExpressService.php tests/Unit/JTExpressServiceTest.php
git commit -m "feat: add J&T Express service for shipping integration"
```

---

### Task 4: Webhook Handler

**Files:**
- Create: `api/jtexpress/webhook.php`
- Create: `app/Controllers/JTWebhookController.php`

- [ ] **Step 1: Create webhook handler**

```php
<?php
// api/jtexpress/webhook.php
require_once __DIR__ . '/../app/autoload.php';

use App\Controllers\JTWebhookController;

header('Content-Type: application/json');

$controller = new JTWebhookController();
$response = $controller->handle();

echo json_encode($response);
```

- [ ] **Step 2: Create webhook controller**

```php
<?php
declare(strict_types=1);

namespace App\Controllers;

class JTWebhookController
{
    public function handle(): array
    {
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);
        
        if (!$data) {
            return ['success' => false, 'message' => 'Invalid payload'];
        }
        
        // Log webhook
        $this->logWebhook($payload);
        
        // Verify signature
        if (!$this->verifySignature($data)) {
            return ['success' => false, 'message' => 'Invalid signature'];
        }
        
        // Process webhook
        try {
            $this->processWebhook($data);
            return ['success' => true, 'message' => 'Processed'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    private function processWebhook(array $data): void
    {
        $trackingNumber = $data['tracking_no'] ?? '';
        $statusCode = $data['status_code'] ?? '';
        $statusDesc = $data['status_desc'] ?? '';
        $location = $data['location'] ?? '';
        $eventTime = $data['event_time'] ?? date('Y-m-d H:i:s');
        
        if (empty($trackingNumber)) {
            throw new \InvalidArgumentException('Missing tracking number');
        }
        
        // Save tracking event
        $this->saveTrackingEvent($trackingNumber, $statusCode, $statusDesc, $location, $eventTime);
        
        // Update order status
        $this->updateOrderStatus($trackingNumber, $statusCode);
        
        // Send notification
        $this->sendNotification($trackingNumber, $statusCode, $statusDesc);
    }
    
    private function saveTrackingEvent(string $trackingNumber, string $statusCode, string $statusDesc, string $location, string $eventTime): void
    {
        $db = \Database::getInstance()->getConnection();
        
        // Get order ID
        $stmt = $db->prepare("SELECT id FROM don_hang WHERE tracking_number = ?");
        $stmt->execute([$trackingNumber]);
        $order = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$order) {
            return;
        }
        
        $stmt = $db->prepare("
            INSERT INTO tracking_events (order_id, tracking_number, status_code, status_desc, location, event_time)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$order['id'], $trackingNumber, $statusCode, $statusDesc, $location, $eventTime]);
    }
    
    private function updateOrderStatus(string $trackingNumber, string $statusCode): void
    {
        $db = \Database::getInstance()->getConnection();
        
        $newStatus = match($statusCode) {
            'PICKUP' => 'approved',
            'IN_TRANSIT' => 'delivered',
            'DELIVERED' => 'completed',
            default => null,
        };
        
        if ($newStatus) {
            $stmt = $db->prepare("UPDATE don_hang SET trang_thai = ? WHERE tracking_number = ?");
            $stmt->execute([$newStatus, $trackingNumber]);
        }
    }
    
    private function sendNotification(string $trackingNumber, string $statusCode, string $statusDesc): void
    {
        // Get order and user info
        $db = \Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT dh.*, u.email, u.hoten 
            FROM don_hang dh 
            JOIN users u ON dh.ma_nguoi_dung = u.username 
            WHERE dh.tracking_number = ?
        ");
        $stmt->execute([$trackingNumber]);
        $order = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$order) {
            return;
        }
        
        // Send email notification
        $emailService = new \App\Services\EmailService();
        $emailService->sendOrderStatusUpdate(
            $order['email'],
            $order['hoten'],
            $order,
            $statusCode
        );
    }
    
    private function verifySignature(array $data): bool
    {
        $config = require __DIR__ . '/../../config/jtexpress.php';
        $secret = $config['webhook_secret'] ?? '';
        
        if (empty($secret)) {
            return true; // Skip verification if no secret configured
        }
        
        $signature = $data['signature'] ?? '';
        $expectedSignature = hash_hmac('sha256', json_encode($data), $secret);
        
        return $signature === $expectedSignature;
    }
    
    private function logWebhook(string $payload): void
    {
        $db = \Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO webhook_logs (provider, payload, status)
            VALUES ('jtexpress', ?, 'received')
        ");
        $stmt->execute([$payload]);
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add api/jtexpress/ app/Controllers/JTWebhookController.php
git commit -m "feat: add J&T Express webhook handler"
```

---

### Task 5: Tracking Page

**Files:**
- Modify: `administrator/elements_LQA/mgiohang/order_tracking.php`

- [ ] **Step 1: Update order_tracking.php**

```php
// Add J&T tracking integration
<?php
use App\Services\JTExpressService;

$jtService = JTExpressService::fromConfig();
$timeline = [];

if (!empty($order['tracking_number'])) {
    try {
        $timeline = $jtService->getTrackingTimeline($order['tracking_number']);
    } catch (\Exception $e) {
        // Fallback to database timeline
        $timeline = $this->getTimelineFromDB($order['id']);
    }
}
?>

<!-- Tracking Timeline -->
<div class="tracking-timeline">
    <?php if (!empty($timeline)): ?>
        <?php foreach ($timeline as $step): ?>
        <div class="timeline-item <?= $step['completed'] ? 'completed' : '' ?>">
            <div class="timeline-icon"><?= $step['icon'] ?></div>
            <div class="timeline-content">
                <strong><?= htmlspecialchars($step['status']) ?></strong>
                <span class="text-muted"><?= $step['time'] ?></span>
                <?php if (!empty($step['location'])): ?>
                    <span class="text-muted"><?= htmlspecialchars($step['location']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            Chưa có thông tin vận chuyển. Đơn hàng đang được xử lý.
        </div>
    <?php endif; ?>
</div>
```

- [ ] **Step 2: Commit**

```bash
git add administrator/elements_LQA/mgiohang/order_tracking.php
git commit -m "feat: integrate J&T tracking into order tracking page"
```

---

## Verification

After implementation, verify:

```bash
# Test create shipping order
curl -X POST https://api.jtexpress.vn/api/order/create \
  -H "Content-Type: application/json" \
  -H "API-Key: your_key" \
  -d '{"order_no":"TEST001","receiver":...}'

# Test tracking
curl https://api.jtexpress.vn/api/tracking?tracking_no=JT123456789

# Test webhook
curl -X POST https://lqashop.com/api/jtexpress/webhook \
  -H "Content-Type: application/json" \
  -d '{"tracking_no":"JT123456789","status_code":"DELIVERED"}'
```

---

## Success Metrics

| Metric | Before | Target |
|--------|--------|--------|
| Customer Inquiries | 50/day | 20/day |
| Delivery Transparency | 0% | 100% |
| Customer Satisfaction | 70% | 90% |
