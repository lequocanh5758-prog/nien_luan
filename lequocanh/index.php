<?php
/**
 * Main entry point for LQA Shop
 * Routes requests to appropriate pages
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load autoloader
require_once __DIR__ . '/app/autoload.php';

// Load helpers
require_once __DIR__ . '/includes/query_builder.php';
require_once __DIR__ . '/includes/advanced_cache.php';

// Route to product detail page
if (isset($_GET['reqHanghoa'])) {
    require_once __DIR__ . '/apart/viewHangHoa.php';
    exit;
}

// Load the main page (homepage)
require_once __DIR__ . '/apart/viewListLoaihang.php';
