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