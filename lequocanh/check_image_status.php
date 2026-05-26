<?php
$dsn = "mysql:host=mysql;port=3306;dbname=sales_management;charset=utf8mb4";
$conn = new PDO($dsn, 'app_user', 'app_password');

// Check all images
$stmt = $conn->query("SELECT h.idhanghoa, h.tenhanghoa, h.hinhanh, 
                       LENGTH(i.du_lieu) as size, i.loai_file,
                       CASE 
                         WHEN i.du_lieu IS NULL THEN 'NO_DATA'
                         WHEN LENGTH(i.du_lieu) < 100 THEN 'TINY'
                         WHEN LENGTH(i.du_lieu) < 1000 THEN 'SMALL'
                         WHEN i.du_lieu NOT REGEXP '^.\\xFF.\\xD8' AND i.loai_file = 'image/jpeg' THEN 'INVALID_JPEG'
                         ELSE 'OK'
                       END as status
                       FROM hanghoa h 
                       LEFT JOIN hinhanh i ON h.hinhanh = i.id 
                       ORDER BY h.idhanghoa");

echo "=== ALL PRODUCTS IMAGE STATUS ===\n\n";
$issues = [];
while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
    $size = $row->size ? round($row->size/1024, 1) : 0;
    $status = $row->status ?? 'NO_IMAGE';
    
    if ($status !== 'OK') {
        $issues[] = $row;
        echo "❌ ID:{$row->idhanghoa} | {$row->tenhanghoa} | {$size}KB | $status\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Total issues: " . count($issues) . "\n";
