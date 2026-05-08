<?php
while (ob_get_level()) {
    ob_end_clean();
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../mod/database.php';

$db = Database::getInstance()->getConnection();

$type = $_GET['type'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0 || !in_array($type, ['banner', 'news', 'page'])) {
    http_response_code(400);
    echo 'Invalid request';
    exit;
}

$tableMap = [
    'banner' => ['table' => 'banners', 'image_col' => 'image_data', 'type_col' => 'image_type', 'fallback_col' => 'image_url'],
    'news'   => ['table' => 'news',    'image_col' => 'image_data', 'type_col' => 'image_type', 'fallback_col' => 'featured_image'],
    'page'   => ['table' => 'pages',   'image_col' => 'image_data', 'type_col' => 'image_type', 'fallback_col' => 'thumbnail'],
];

$config = $tableMap[$type];

try {
    $sql = "SELECT {$config['image_col']}, {$config['type_col']}, {$config['fallback_col']} FROM {$config['table']} WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && !empty($row[$config['image_col']])) {
        $data = $row[$config['image_col']];
        $mime = $row[$config['type_col']] ?? 'image/jpeg';

        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $detected = $finfo->buffer($data);
            if ($detected && $detected !== 'application/octet-stream') {
                $mime = $detected;
            }
        }

        header("Content-Type: $mime");
        header("Content-Length: " . strlen($data));
        header("Cache-Control: public, max-age=86400");
        echo $data;
        exit;
    }

    if ($row && !empty($row[$config['fallback_col']])) {
        $path = $row[$config['fallback_col']];
        $cleanPath = ltrim($path, '/');
        $searchPaths = [
            '/var/www/html/' . $cleanPath,
            $_SERVER['DOCUMENT_ROOT'] . '/' . $cleanPath,
            __DIR__ . '/../../../' . $cleanPath,
            __DIR__ . '/../../../../' . $cleanPath
        ];

        foreach ($searchPaths as $fullPath) {
            if (file_exists($fullPath) && is_file($fullPath)) {
                $mime = 'image/jpeg';
                if (class_exists('finfo')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $fullPath);
                    finfo_close($finfo);
                }
                header("Content-Type: $mime");
                header("Content-Length: " . filesize($fullPath));
                header("Cache-Control: public, max-age=86400");
                readfile($fullPath);
                exit;
            }
        }
    }
} catch (Exception $e) {
    error_log("displayImage error: " . $e->getMessage());
}

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
