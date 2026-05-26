<?php
require_once("../mod/database.php");
require_once __DIR__ . '/../../../app/autoload.php';

use App\Models\ProductImage;

if (!isset($_GET['idhanghoa']) || empty($_GET['idhanghoa'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu ID hàng hóa'
    ]);
    exit;
}

$idhanghoa = intval($_GET['idhanghoa']);

$images = ProductImage::getAllForProduct($idhanghoa);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'images' => $images
]);
