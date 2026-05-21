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
                'ward' => $orderData['ward'] ?? '',
                'district' => $orderData['district'] ?? '',
                'city' => $orderData['city'] ?? '',
            ],
            'items' => $orderData['items'] ?? [],
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
        $configPath = __DIR__ . '/../../config/jtexpress.php';
        
        if (!file_exists($configPath)) {
            return new self([]);
        }
        
        $config = require $configPath;
        return new self($config);
    }
    
    /**
     * Check if J&T is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->shopId);
    }
}