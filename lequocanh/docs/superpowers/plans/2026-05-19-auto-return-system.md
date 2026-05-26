# Auto Return System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Automate return process with intelligent method selection (self-ship, pickup, drop-off) to reduce manual processing by 80%.

**Architecture:** Return Decision Engine analyzes factors (distance, value, customer preference) to select optimal return method. Integration with J&T Express for pickup scheduling.

**Tech Stack:** PHP, MySQL, J&T Express API, Redis

---

## File Structure

| File                                           | Responsibility              |
| ---------------------------------------------- | --------------------------- |
| `config/return_policy.php`                     | Return policy configuration |
| `app/Services/ReturnDecisionEngine.php`        | Decision logic              |
| `app/Services/ReturnAutomationService.php`     | Automation flow             |
| `app/Controllers/ReturnController.php`         | Return endpoints            |
| `database/migrations/expand_returns_table.php` | DB schema                   |

---

### Task 1: Return Policy Configuration

**Files:**

- Create: `config/return_policy.php`

- [ ] **Step 1: Create return policy config**

```php
<?php
// config/return_policy.php
return [
    // Return eligibility
    'return_window_days' => 7,
    'eligible_statuses' => ['completed'],
    'eligible_payment_methods' => ['bank_transfer', 'momo', 'cod'],

    // Auto-approve settings
    'auto_approve' => [
        'enabled' => true,
        'max_amount' => 500000, // VND - auto approve if order < 500k
        'max_items' => 3,
    ],

    // Return methods
    'methods' => [
        'self_ship' => [
            'enabled' => true,
            'description' => 'Khách hàng tự gửi hàng trả',
            'max_distance' => 50, // km
            'refund_shipping' => false,
        ],
        'pickup' => [
            'enabled' => true,
            'description' => 'Shop gửi đơn vị vận chuyển đến lấy hàng',
            'fee' => 0, // Free for orders > 500k
            'fee_for_low_value' => 30000, // 30k for orders < 500k
            'carrier' => 'jtexpress',
        ],
        'drop_off' => [
            'enabled' => true,
            'description' => 'Khách mang đến bưu cục gần nhất',
            'locations' => [
                ['name' => 'J&T Express - Quận 1', 'address' => '123 Nguyễn Huệ'],
                ['name' => 'J&T Express - Quận 3', 'address' => '456 Võ Văn Tần'],
                ['name' => 'J&T Express - Bình Thạnh', 'address' => '789 Xô Viết Nghệ Tĩnh'],
            ],
        ],
    ],

    // Refund settings
    'refund' => [
        'method' => 'original', // Refund to original payment method
        'processing_days' => 3,
        'partial_refund_allowed' => true,
    ],

    // Decision weights
    'decision_weights' => [
        'distance' => 0.3,
        'order_value' => 0.3,
        'customer_preference' => 0.2,
        'item_count' => 0.2,
    ],
];
```

- [ ] **Step 2: Commit**

```bash
git add config/return_policy.php
git commit -m "feat: add return policy configuration"
```

---

### Task 2: Database Migration

**Files:**

- Create: `database/migrations/2026_05_19_expand_returns_table.php`

- [ ] **Step 1: Create migration**

```php
<?php
// database/migrations/2026_05_19_expand_returns_table.php
class ExpandReturnsTable
{
    public function up()
    {
        $db = \Database::getInstance()->getConnection();

        // Expand doi_tra table
        $db->exec("
            ALTER TABLE doi_tra
            ADD COLUMN return_method ENUM('self_ship', 'pickup', 'drop_off') NULL AFTER loai,
            ADD COLUMN pickup_tracking_number VARCHAR(50) NULL AFTER return_method,
            ADD COLUMN pickup_scheduled_date DATE NULL AFTER pickup_tracking_number,
            ADD COLUMN drop_off_location VARCHAR(255) NULL AFTER pickup_scheduled_date,
            ADD COLUMN refund_amount DECIMAL(15,2) NULL AFTER drop_off_location,
            ADD COLUMN refund_method VARCHAR(50) NULL AFTER refund_amount,
            ADD COLUMN refund_status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending' AFTER refund_method,
            ADD COLUMN refund_date DATETIME NULL AFTER refund_status,
            ADD COLUMN decision_factors TEXT NULL AFTER refund_date,
            ADD COLUMN auto_approved TINYINT(1) DEFAULT 0 AFTER decision_factors
        ");

        // Create return items table
        $db->exec("
            CREATE TABLE IF NOT EXISTS return_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                return_id INT NOT NULL,
                order_item_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                reason VARCHAR(255) NULL,
                condition ENUM('new', 'used', 'damaged') DEFAULT 'new',
                refund_amount DECIMAL(15,2) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_return_id (return_id),
                INDEX idx_order_item_id (order_item_id),
                INDEX idx_product_id (product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function down()
    {
        $db = \Database::getInstance()->getConnection();

        $db->exec("ALTER TABLE doi_tra DROP COLUMN return_method");
        $db->exec("ALTER TABLE doi_tra DROP COLUMN pickup_tracking_number");
        $db->exec("ALTER TABLE doi_tra DROP COLUMN pickup_scheduled_date");
        $db->exec("ALTER TABLE doi_tra DROP COLUMN drop_off_location");
        $db->exec("ALTER TABLE doi_tra DROP COLUMN refund_amount");
        $db->exec("ALTER TABLE doi_tra DROP COLUMN refund_method");
        $db->exec("ALTER TABLE doi_tra DROP COLUMN refund_status");
        $db->exec("ALTER TABLE doi_tra DROP COLUMN refund_date");
        $db->exec("ALTER TABLE doi_tra DROP COLUMN decision_factors");
        $db->exec("ALTER TABLE doi_tra DROP COLUMN auto_approved");
        $db->exec("DROP TABLE IF EXISTS return_items");
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
git commit -m "feat: expand returns table for auto return system"
```

---

### Task 3: Return Decision Engine

**Files:**

- Create: `app/Services/ReturnDecisionEngine.php`
- Test: `tests/Unit/ReturnDecisionEngineTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php
// tests/Unit/ReturnDecisionEngineTest.php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\ReturnDecisionEngine;

class ReturnDecisionEngineTest extends TestCase
{
    public function testDecideForHighValueOrder()
    {
        $engine = new ReturnDecisionEngine([
            'methods' => [
                'pickup' => ['enabled' => true, 'fee' => 0],
                'self_ship' => ['enabled' => true],
                'drop_off' => ['enabled' => true],
            ],
            'decision_weights' => [
                'distance' => 0.3,
                'order_value' => 0.3,
                'customer_preference' => 0.2,
                'item_count' => 0.2,
            ],
        ]);

        $request = [
            'order_total' => 2000000,
            'item_count' => 2,
            'address' => '123 Nguyễn Huệ, Quận 1, TP.HCM',
            'preferred_method' => null,
        ];

        $decision = $engine->decide($request);

        $this->assertEquals('pickup', $decision['method']);
        $this->assertEquals(0, $decision['cost']);
    }

    public function testDecideForLowValueOrder()
    {
        $engine = new ReturnDecisionEngine([
            'methods' => [
                'pickup' => ['enabled' => true, 'fee' => 0, 'fee_for_low_value' => 30000],
                'self_ship' => ['enabled' => true],
                'drop_off' => ['enabled' => true],
            ],
            'decision_weights' => [
                'distance' => 0.3,
                'order_value' => 0.3,
                'customer_preference' => 0.2,
                'item_count' => 0.2,
            ],
        ]);

        $request = [
            'order_total' => 200000,
            'item_count' => 1,
            'address' => '456 Lê Lợi, Quận 1, TP.HCM',
            'preferred_method' => 'self_ship',
        ];

        $decision = $engine->decide($request);

        $this->assertEquals('self_ship', $decision['method']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
./vendor/bin/phpunit tests/Unit/ReturnDecisionEngineTest.php -v
```

Expected: FAIL with "Class App\Services\ReturnDecisionEngine not found"

- [ ] **Step 3: Write implementation**

```php
<?php
declare(strict_types=1);

namespace App\Services;

class ReturnDecisionEngine
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Decide optimal return method
     */
    public function decide(array $request): array
    {
        $factors = $this->analyzeFactors($request);

        // Calculate scores for each method
        $scores = [];

        foreach ($this->config['methods'] as $method => $settings) {
            if (!$settings['enabled']) {
                continue;
            }

            $scores[$method] = $this->calculateMethodScore($method, $settings, $factors);
        }

        // Select method with highest score
        arsort($scores);
        $selectedMethod = array_key_first($scores);

        // Build decision
        return $this->buildDecision($selectedMethod, $this->config['methods'][$selectedMethod], $factors);
    }

    /**
     * Analyze factors
     */
    private function analyzeFactors(array $request): array
    {
        return [
            'is_high_value' => $request['order_total'] > 1000000,
            'is_near_drop_off' => $this->checkNearDropOff($request['address']),
            'customer_preferred' => !empty($request['preferred_method']),
            'preferred_method' => $request['preferred_method'] ?? null,
            'item_count' => $request['item_count'] ?? 1,
            'order_total' => $request['order_total'],
        ];
    }

    /**
     * Calculate score for method
     */
    private function calculateMethodScore(string $method, array $settings, array $factors): float
    {
        $weights = $this->config['decision_weights'];
        $score = 0;

        // Distance factor
        if ($method === 'drop_off' && $factors['is_near_drop_off']) {
            $score += $weights['distance'] * 100;
        } elseif ($method === 'pickup') {
            $score += $weights['distance'] * 80;
        } else {
            $score += $weights['distance'] * 50;
        }

        // Order value factor
        if ($method === 'pickup' && $factors['is_high_value']) {
            $score += $weights['order_value'] * 100;
        } elseif ($method === 'self_ship' && !$factors['is_high_value']) {
            $score += $weights['order_value'] * 70;
        } else {
            $score += $weights['order_value'] * 50;
        }

        // Customer preference factor
        if ($factors['customer_preferred'] && $factors['preferred_method'] === $method) {
            $score += $weights['customer_preference'] * 100;
        } else {
            $score += $weights['customer_preference'] * 30;
        }

        // Item count factor
        if ($method === 'pickup' && $factors['item_count'] > 2) {
            $score += $weights['item_count'] * 100;
        } elseif ($method === 'drop_off' && $factors['item_count'] <= 2) {
            $score += $weights['item_count'] * 80;
        } else {
            $score += $weights['item_count'] * 50;
        }

        return $score;
    }

    /**
     * Build decision
     */
    private function buildDecision(string $method, array $settings, array $factors): array
    {
        $decision = [
            'method' => $method,
            'reason' => $this->getReason($method, $factors),
            'estimated_time' => $this->getEstimatedTime($method),
            'cost' => $this->calculateCost($method, $settings, $factors),
        ];

        // Add method-specific details
        if ($method === 'drop_off') {
            $decision['locations'] = $settings['locations'] ?? [];
        }

        if ($method === 'pickup') {
            $decision['pickup_date'] = $this->calculatePickupDate();
        }

        return $decision;
    }

    /**
     * Get reason for method selection
     */
    private function getReason(string $method, array $factors): string
    {
        return match($method) {
            'pickup' => $factors['is_high_value']
                ? 'Đơn hàng giá trị cao, hỗ trợ lấy hàng tận nơi miễn phí'
                : 'Phương án lấy hàng tận nơi',
            'drop_off' => $factors['is_near_drop_off']
                ? 'Gần bưu cục J&T Express, tiện lợi cho khách hàng'
                : 'Khách hàng có thể mang đến bưu cục',
            'self_ship' => 'Khách hàng tự gửi hàng trả',
            default => 'Phương án đổi trả',
        };
    }

    /**
     * Get estimated time
     */
    private function getEstimatedTime(string $method): string
    {
        return match($method) {
            'pickup' => '1-3 ngày',
            'drop_off' => '1-2 ngày',
            'self_ship' => '3-7 ngày',
            default => '3-5 ngày',
        };
    }

    /**
     * Calculate cost
     */
    private function calculateCost(string $method, array $settings, array $factors): float
    {
        if ($method === 'self_ship') {
            return 0; // Customer pays
        }

        if ($method === 'pickup') {
            return $factors['is_high_value'] ? 0 : ($settings['fee_for_low_value'] ?? 30000);
        }

        if ($method === 'drop_off') {
            return 0; // Free
        }

        return 0;
    }

    /**
     * Check if address is near drop-off location
     */
    private function checkNearDropOff(string $address): bool
    {
        // Simple keyword check - in production, use geocoding API
        $nearbyKeywords = ['Quận 1', 'Quận 3', 'Bình Thạnh', 'Phú Nhuận'];

        foreach ($nearbyKeywords as $keyword) {
            if (stripos($address, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate pickup date
     */
    private function calculatePickupDate(): string
    {
        $date = new \DateTime();
        $date->modify('+1 day');

        // Skip weekends
        while ($date->format('N') >= 6) {
            $date->modify('+1 day');
        }

        return $date->format('Y-m-d');
    }

    /**
     * Create from config
     */
    public static function fromConfig(): self
    {
        $config = require __DIR__ . '/../../config/return_policy.php';
        return new self($config);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
./vendor/bin/phpunit tests/Unit/ReturnDecisionEngineTest.php -v
```

Expected: OK (2 tests, 4 assertions)

- [ ] **Step 5: Commit**

```bash
git add app/Services/ReturnDecisionEngine.php tests/Unit/ReturnDecisionEngineTest.php
git commit -m "feat: add ReturnDecisionEngine for automated return processing"
```

---

### Task 4: Return Automation Service

**Files:**

- Create: `app/Services/ReturnAutomationService.php`

- [ ] **Step 1: Create automation service**

```php
<?php
declare(strict_types=1);

namespace App\Services;

class ReturnAutomationService
{
    private ReturnDecisionEngine $decisionEngine;
    private JTExpressService $jtService;

    public function __construct()
    {
        $this->decisionEngine = ReturnDecisionEngine::fromConfig();
        $this->jtService = JTExpressService::fromConfig();
    }

    /**
     * Process return request automatically
     */
    public function processReturn(array $returnRequest): array
    {
        // 1. Check eligibility
        $eligibility = $this->checkEligibility($returnRequest);
        if (!$eligibility['eligible']) {
            return ['success' => false, 'message' => $eligibility['reason']];
        }

        // 2. Auto-approve if eligible
        $autoApproved = $this->shouldAutoApprove($returnRequest);

        // 3. Decide return method
        $decision = $this->decisionEngine->decide($returnRequest);

        // 4. Execute return method
        $result = $this->executeReturnMethod($decision, $returnRequest);

        // 5. Save to database
        $returnId = $this->saveReturn($returnRequest, $decision, $autoApproved);

        // 6. Send notifications
        $this->sendNotifications($returnRequest, $decision, $autoApproved);

        return [
            'success' => true,
            'return_id' => $returnId,
            'auto_approved' => $autoApproved,
            'method' => $decision['method'],
            'reason' => $decision['reason'],
            'estimated_time' => $decision['estimated_time'],
            'cost' => $decision['cost'],
            'pickup_date' => $decision['pickup_date'] ?? null,
            'drop_off_locations' => $decision['locations'] ?? [],
        ];
    }

    /**
     * Check return eligibility
     */
    private function checkEligibility(array $request): array
    {
        $config = require __DIR__ . '/../../config/return_policy.php';

        // Check order status
        if (!in_array($request['order_status'], $config['eligible_statuses'])) {
            return ['eligible' => false, 'reason' => 'Đơn hàng không đủ điều kiện đổi trả'];
        }

        // Check return window
        $orderDate = new \DateTime($request['order_date']);
        $now = new \DateTime();
        $diff = $now->diff($orderDate)->days;

        if ($diff > $config['return_window_days']) {
            return ['eligible' => false, 'reason' => 'Đã quá thời hạn đổi trả (' . $config['return_window_days'] . ' ngày)'];
        }

        // Check payment method
        if (!in_array($request['payment_method'], $config['eligible_payment_methods'])) {
            return ['eligible' => false, 'reason' => 'Phương thức thanh toán không hỗ trợ đổi trả'];
        }

        return ['eligible' => true, 'reason' => ''];
    }

    /**
     * Check if should auto-approve
     */
    private function shouldAutoApprove(array $request): bool
    {
        $config = require __DIR__ . '/../../config/return_policy.php';

        if (!$config['auto_approve']['enabled']) {
            return false;
        }

        return $request['order_total'] <= $config['auto_approve']['max_amount']
            && $request['item_count'] <= $config['auto_approve']['max_items'];
    }

    /**
     * Execute return method
     */
    private function executeReturnMethod(array $decision, array $request): array
    {
        switch ($decision['method']) {
            case 'pickup':
                return $this->schedulePickup($request, $decision);

            case 'drop_off':
                return $this->provideDropOffInfo($decision);

            case 'self_ship':
                return $this->provideSelfShipInfo($request);

            default:
                return ['success' => false, 'message' => 'Invalid return method'];
        }
    }

    /**
     * Schedule pickup with J&T
     */
    private function schedulePickup(array $request, array $decision): array
    {
        try {
            $result = $this->jtService->createOrder([
                'order_code' => 'RETURN_' . $request['order_id'],
                'sender' => [
                    'name' => $request['customer_name'],
                    'phone' => $request['customer_phone'],
                    'address' => $request['address'],
                ],
                'receiver' => require(__DIR__ . '/../../config/jtexpress.php')['sender'],
                'items' => [['name' => 'Return item', 'quantity' => 1]],
                'service_type' => 'standard',
            ]);

            return [
                'success' => true,
                'tracking_number' => $result['tracking_number'],
                'pickup_date' => $decision['pickup_date'],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to schedule pickup: ' . $e->getMessage()];
        }
    }

    /**
     * Provide drop-off info
     */
    private function provideDropOffInfo(array $decision): array
    {
        return [
            'success' => true,
            'locations' => $decision['locations'],
        ];
    }

    /**
     * Provide self-ship info
     */
    private function provideSelfShipInfo(array $request): array
    {
        return [
            'success' => true,
            'shipping_address' => require(__DIR__ . '/../../config/jtexpress.php')['sender']['address'],
            'instructions' => 'Vui lòng gửi hàng đến địa chỉ trên và ghi mã đơn hàng #' . $request['order_id'],
        ];
    }

    /**
     * Save return to database
     */
    private function saveReturn(array $request, array $decision, bool $autoApproved): int
    {
        $db = \Database::getInstance()->getConnection();

        $stmt = $db->prepare("
            INSERT INTO doi_tra (ma_don_hang, ma_nguoi_dung, ly_do, loai, return_method, trang_thai, auto_approved, decision_factors)
            VALUES (?, ?, ?, 'return', ?, ?, ?, ?)
        ");

        $status = $autoApproved ? 'approved' : 'pending';
        $factors = json_encode($decision);

        $stmt->execute([
            $request['order_id'],
            $request['user_id'],
            $request['reason'],
            $decision['method'],
            $status,
            $autoApproved ? 1 : 0,
            $factors,
        ]);

        return (int)$db->lastInsertId();
    }

    /**
     * Send notifications
     */
    private function sendNotifications(array $request, array $decision, bool $autoApproved): void
    {
        $emailService = new EmailService();

        $subject = $autoApproved
            ? 'Yêu cầu đổi trả đã được chấp nhận'
            : 'Yêu cầu đổi trả đang được xem xét';

        // Build email content based on method
        $content = $this->buildReturnEmailContent($request, $decision, $autoApproved);

        // Send email (implementation depends on email service)
        // $emailService->send($request['email'], $subject, $content);
    }

    /**
     * Build return email content
     */
    private function buildReturnEmailContent(array $request, array $decision, bool $autoApproved): string
    {
        $methodText = match($decision['method']) {
            'pickup' => 'Chúng tôi sẽ gửi đơn vị vận chuyển đến lấy hàng vào ngày ' . ($decision['pickup_date'] ?? ''),
            'drop_off' => 'Vui lòng mang hàng đến bưu cục J&T Express gần nhất',
            'self_ship' => 'Vui lòng tự gửi hàng trả đến địa chỉ của chúng tôi',
            default => '',
        };

        return "
            <h2>Yêu cầu đổi trả đơn hàng #{$request['order_id']}</h2>
            <p><strong>Phương thức:</strong> {$methodText}</p>
            <p><strong>Thời gian ước tính:</strong> {$decision['estimated_time']}</p>
            <p><strong>Phí vận chuyển:</strong> " . number_format($decision['cost']) . " VNĐ</p>
            <p><strong>Lý do:</strong> {$decision['reason']}</p>
        ";
    }

    /**
     * Create from config
     */
    public static function fromConfig(): self
    {
        return new self();
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Services/ReturnAutomationService.php
git commit -m "feat: add ReturnAutomationService for automated return processing"
```

---

### Task 5: Return API Endpoint

**Files:**

- Create: `api/return/request.php`

- [ ] **Step 1: Create return API**

```php
<?php
// api/return/request.php
require_once __DIR__ . '/../../app/autoload.php';

use App\Services\ReturnAutomationService;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check authentication
if (!isset($_SESSION['USER'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

// Validate required fields
$required = ['order_id', 'reason'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: {$field}"]);
        exit;
    }
}

try {
    $service = ReturnAutomationService::fromConfig();

    $request = [
        'order_id' => $data['order_id'],
        'user_id' => $_SESSION['USER'],
        'reason' => $data['reason'],
        'order_status' => $data['order_status'] ?? 'completed',
        'order_date' => $data['order_date'] ?? date('Y-m-d'),
        'order_total' => $data['order_total'] ?? 0,
        'item_count' => $data['item_count'] ?? 1,
        'payment_method' => $data['payment_method'] ?? 'bank_transfer',
        'customer_name' => $data['customer_name'] ?? '',
        'customer_phone' => $data['customer_phone'] ?? '',
        'address' => $data['address'] ?? '',
        'email' => $data['email'] ?? '',
    ];

    $result = $service->processReturn($request);

    echo json_encode($result);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    error_log('Return API error: ' . $e->getMessage());
}
```

- [ ] **Step 2: Commit**

```bash
git add api/return/
git commit -m "feat: add return request API endpoint"
```

---

## Verification

After implementation, verify:

```bash
# Test return request
curl -X POST https://lqashop.com/api/return/request \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": 123,
    "reason": "Sản phẩm bị lỗi",
    "order_status": "completed",
    "order_total": 1500000,
    "item_count": 2
  }'

# Expected response:
{
  "success": true,
  "return_id": 456,
  "auto_approved": false,
  "method": "pickup",
  "reason": "Đơn hàng giá trị cao, hỗ trợ lấy hàng tận nơi miễn phí",
  "estimated_time": "1-3 ngày",
  "cost": 0,
  "pickup_date": "2026-05-20"
}
```

---

## Success Metrics

| Metric                 | Before | Target |
| ---------------------- | ------ | ------ |
| Manual Processing      | 100%   | 20%    |
| Return Processing Time | 7 days | 3 days |
| Customer Satisfaction  | 70%    | 90%    |
| Return Errors          | 15%    | 5%     |
