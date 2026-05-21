<?php
// config/oauth.php
return [
    'google' => [
        'enabled' => (bool)($_ENV['GOOGLE_OAUTH_ENABLED'] ?? false),
        'client_id' => $_ENV['GOOGLE_CLIENT_ID'] ?? '',
        'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
        'redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'] ?? '/auth/google/callback',
        'scopes' => ['email', 'profile'],
    ],
    'facebook' => [
        'enabled' => (bool)($_ENV['FACEBOOK_OAUTH_ENABLED'] ?? false),
        'client_id' => $_ENV['FACEBOOK_CLIENT_ID'] ?? '',
        'client_secret' => $_ENV['FACEBOOK_CLIENT_SECRET'] ?? '',
        'redirect_uri' => $_ENV['FACEBOOK_REDIRECT_URI'] ?? '/auth/facebook/callback',
        'scopes' => ['email', 'public_profile'],
    ],
];