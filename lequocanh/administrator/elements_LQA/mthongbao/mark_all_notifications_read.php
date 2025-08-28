<?php
require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../mod/CustomerNotificationManager.php';

// Start session safely
SessionManager::start();

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['USER'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['USER'];

$notificationManager = new CustomerNotificationManager();
$result = $notificationManager->markAllAsRead($userId);

echo json_encode(['success' => $result]);
?>
