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
            'is_high_value' => ($request['order_total'] ?? 0) > 1000000,
            'is_near_drop_off' => $this->checkNearDropOff($request['address'] ?? ''),
            'customer_preferred' => !empty($request['preferred_method']),
            'preferred_method' => $request['preferred_method'] ?? null,
            'item_count' => $request['item_count'] ?? 1,
            'order_total' => $request['order_total'] ?? 0,
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
                ? 'Gần bưu cục, tiện lợi cho khách hàng'
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
        $configPath = __DIR__ . '/../../config/return_policy.php';
        
        if (!file_exists($configPath)) {
            return new self([
                'methods' => [],
                'decision_weights' => [
                    'distance' => 0.3,
                    'order_value' => 0.3,
                    'customer_preference' => 0.2,
                    'item_count' => 0.2,
                ],
            ]);
        }
        
        $config = require $configPath;
        return new self($config);
    }
}