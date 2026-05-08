<?php
while (ob_get_level()) {
    ob_end_clean();
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once '../../elements_LQA/mod/hanghoaCls.php';

$imageId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($imageId > 0) {
    $hanghoa = new hanghoa();
    $image = $hanghoa->GetHinhAnhById($imageId);

    if ($image && !empty($image->du_lieu)) {
        $data = $image->du_lieu;
        $mime = 'image/jpeg';
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $detected = $finfo->buffer($data);
            if ($detected && $detected !== 'application/octet-stream')
                $mime = $detected;
        }
        header("Content-Type: $mime");
        header("Content-Length: " . strlen($data));
        header("Cache-Control: public, max-age=86400");
        echo $data;
        exit;
    }
}

// Fallback to default
$default = '../../elements_LQA/img_LQA/no-image.png';
if (file_exists($default)) {
    header("Content-Type: image/png");
    readfile($default);
} else {
    header("Content-Type: image/png");
    $img = imagecreatetruecolor(50, 50);
    imagepng($img);
}
exit;
