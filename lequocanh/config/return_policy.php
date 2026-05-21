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
        ],
        'drop_off' => [
            'enabled' => true,
            'description' => 'Khách mang đến bưu cục gần nhất',
            'locations' => [
                ['name' => 'Bưu cục Quận 1', 'address' => '123 Nguyễn Huệ, Quận 1'],
                ['name' => 'Bưu cục Quận 3', 'address' => '456 Võ Văn Tần, Quận 3'],
                ['name' => 'Bưu cục Bình Thạnh', 'address' => '789 Xô Viết Nghệ Tĩnh, Bình Thạnh'],
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