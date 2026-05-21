<?php
// routes/auth.php
// OAuth routes for Google and Facebook login

return [
    'GET /auth/google' => ['App\Controllers\AuthController', 'googleRedirect'],
    'GET /auth/google/callback' => ['App\Controllers\AuthController', 'googleCallback'],
    'GET /auth/facebook' => ['App\Controllers\AuthController', 'facebookRedirect'],
    'GET /auth/facebook/callback' => ['App\Controllers\AuthController', 'facebookCallback'],
    'GET /auth/logout' => ['App\Controllers\AuthController', 'logout'],
];