<?php
$dsn = "mysql:host=mysql;port=3306;dbname=sales_management;charset=utf8mb4";
$conn = new PDO($dsn, 'app_user', 'app_password');

// Featured products
$stmt = $conn->query("SELECT idhanghoa, tenhanghoa, hinhanh FROM hanghoa WHERE is_featured = 1 ORDER BY idhanghoa");
$featured = $stmt->fetchAll(PDO::FETCH_OBJ);

echo "=== FEATURED PRODUCTS ===\n";
foreach ($featured as $p) {
    $imgStatus = ($p->hinhanh > 0) ? "✅ ID:{$p->hinhanh}" : "❌ NO IMAGE";
    echo "ID:{$p->idhanghoa} | {$p->tenhanghoa} | $imgStatus\n";
}

// New products
$stmt = $conn->query("SELECT idhanghoa, tenhanghoa, hinhanh FROM hanghoa WHERE is_new = 1 ORDER BY idhanghoa");
$new = $stmt->fetchAll(PDO::FETCH_OBJ);

echo "\n=== NEW PRODUCTS ===\n";
foreach ($new as $p) {
    $imgStatus = ($p->hinhanh > 0) ? "✅ ID:{$p->hinhanh}" : "❌ NO IMAGE";
    echo "ID:{$p->idhanghoa} | {$p->tenhanghoa} | $imgStatus\n";
}

// Sale products
$stmt = $conn->query("SELECT idhanghoa, tenhanghoa, hinhanh FROM hanghoa WHERE is_sale = 1 ORDER BY idhanghoa");
$sale = $stmt->fetchAll(PDO::FETCH_OBJ);

echo "\n=== SALE PRODUCTS ===\n";
foreach ($sale as $p) {
    $imgStatus = ($p->hinhanh > 0) ? "✅ ID:{$p->hinhanh}" : "❌ NO IMAGE";
    echo "ID:{$p->idhanghoa} | {$p->tenhanghoa} | $imgStatus\n";
}
