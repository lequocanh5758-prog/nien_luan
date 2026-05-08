<?php
// Clear any previous output buffers to prevent corruption
while (ob_get_level()) {
    ob_end_clean();
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once("../mod/database.php");
require_once("../mod/hanghoaCls.php");

// Set error log relative to this file
ini_set('error_log', dirname(dirname(dirname(__FILE__))) . '/upload_errors.log');

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $imageId = (int) $_GET['id'];
    $hanghoa = new hanghoa();
    $image = $hanghoa->GetHinhAnhById($imageId);

    if ($image) {
        // Priority 1: Database BLOB
        if (!empty($image->du_lieu)) {
            $data = $image->du_lieu;

            // Try to detect mime type
            $mime = 'image/jpeg'; // Default
            if (class_exists('finfo')) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $detected = $finfo->buffer($data);
                if ($detected && $detected !== 'application/octet-stream') {
                    $mime = $detected;
                } else {
                    // Fallback to extension
                    $ext = strtolower(pathinfo($image->ten_file ?? '', PATHINFO_EXTENSION));
                    $map = ['png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
                    if (isset($map[$ext]))
                        $mime = $map[$ext];
                }
            }

            header("Content-Type: $mime");
            header("Content-Length: " . strlen($data));
            header("Cache-Control: public, max-age=86400");
            echo $data;
            exit;
        }

        // Priority 2: Filesystem fallback
        if (!empty($image->duong_dan)) {
            $cleanPath = $image->duong_dan;
            if (strpos($cleanPath, 'administrator/') === 0) {
                $cleanPath = substr($cleanPath, strlen('administrator/'));
            }

            $searchPaths = [
                '/var/www/html/administrator/' . $cleanPath,
                '/var/www/html/' . $cleanPath,
                __DIR__ . '/../../' . $cleanPath,
                __DIR__ . '/../../../' . $cleanPath
            ];

            foreach ($searchPaths as $path) {
                if (file_exists($path) && is_file($path)) {
                    $mime = 'image/jpeg';
                    if (class_exists('finfo')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime = finfo_file($finfo, $path);
                        finfo_close($finfo);
                    }
                    header("Content-Type: $mime");
                    header("Content-Length: " . filesize($path));
                    header("Cache-Control: public, max-age=86400");
                    readfile($path);
                    exit;
                }
            }
        }
    }
}

// Final fallback: No Image placeholder
$noImagePath = __DIR__ . '/../../img_LQA/no-image.png';
if (file_exists($noImagePath)) {
    header("Content-Type: image/png");
    header("Content-Length: " . filesize($noImagePath));
    readfile($noImagePath);
} else {
    header("Content-Type: image/png");
    $img = imagecreatetruecolor(100, 100);
    $bg = imagecolorallocate($img, 240, 240, 240);
    imagefilledrectangle($img, 0, 0, 100, 100, $bg);
    imagepng($img);
    imagedestroy($img);
}
exit;