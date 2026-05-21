<?php
// config/cdn.php
return [
    'enabled' => (bool)($_ENV['CDN_ENABLED'] ?? false),
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