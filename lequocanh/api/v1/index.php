<?php
// Main API entry point for version 1
header("Content-Type: application/json");

// Include necessary files
require_once __DIR__ . '/../Response.php'; // Assuming Response.php is in lequocanh/api/

// Get the requested URI and method
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove /lequocanh/api/v1 prefix from the URI
$basePath = '/lequocanh/api/v1';
if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

// Basic routing (very simplified for demonstration)
switch ($requestUri) {
    case '/products':
        if ($requestMethod === 'GET') {
            // Placeholder for product listing
            Response::json(['message' => 'List of products'], 200);
        } else {
            Response::json(['message' => 'Method Not Allowed'], 405);
        }
        break;
    case '/users':
        if ($requestMethod === 'GET') {
            // Placeholder for user listing
            Response::json(['message' => 'List of users'], 200);
        } else if ($requestMethod === 'POST') {
            // Placeholder for creating a user
            Response::json(['message' => 'User created'], 201);
        } else {
            Response::json(['message' => 'Method Not Allowed'], 405);
        }
        break;
    default:
        Response::json(['message' => 'Not Found'], 404);
        break;
}
?>