<?php

require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Kiểm tra đồng bộ Admin vs Checkout - Phương thức vận chuyển</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 15px 0; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
    th { background-color: #667eea; color: white; }
    .alert { padding: 15px; margin: 15px 0; border-radius: 5px; }
    .alert-success { background: #d4edda; color: #155724; }
    .alert-danger { background: #f8d7da; color: #721c24; }
    .alert-warning { background: #fff3cd; color: #856404; }
    .alert-info { background: #d1ecf1; color: #0c5460; }
    .match { background: #d4edda; }
    .mismatch { background: #f8d7da; }
</style>";

// 1. Kiểm tra/Viết lại VIEW
echo "<h3>1. Kiểm tra/Viết lại VIEW</h3>";
try {
    $stmt = $db->query("SHOW CREATE VIEW v_shipping_methods_with_fees");
    $viewDef = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($viewDef) {
        $createView = $viewDef['Create View'];
        $hasFeeConfigCount = stripos($createView, 'fee_config_count') !== false;
        
        if (!$hasFeeConfigCount) {
            echo "<div class='alert alert-warning'>VIEW thiếu column <code>fee_config_count</code>. Đang viết lại...</div>";
            
            $db->exec("DROP VIEW IF EXISTS v_shipping_methods_with_fees");
            $db->exec("CREATE VIEW v_shipping_methods_with_fees AS
                SELECT 
                    sm.id, sm.code, sm.name, sm.description, sm.delivery_time,
                    sm.price_multiplier, sm.is_active, sm.sort_order,
                    sm.supports_tracking, sm.supports_cod, sm.created_at, sm.updated_at,
                    COUNT(DISTINCT sf.id) as fee_config_count,
                    MIN(sf.base_fee) as min_base_fee,
                    MAX(sf.base_fee) as max_base_fee,
                    MIN(sf.min_order_free_ship) as min_free_ship_threshold
                FROM shipping_methods sm
                LEFT JOIN shipping_fees sf ON sm.id = sf.shipping_method_id AND sf.is_active = 1
                GROUP BY sm.id, sm.code, sm.name, sm.description, sm.delivery_time, sm.price_multiplier, sm.is_active, sm.sort_order, sm.supports_tracking, sm.supports_cod, sm.created_at, sm.updated_at");
            
            echo "<div class='alert alert-success'>✓ VIEW đã được viết lại thành công!</div>";
        } else {
            echo "<div class='alert alert-success'>✓ VIEW đã có column <code>fee_config_count</code></div>";
        }
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Lỗi VIEW: " . $e->getMessage() . "</div>";
    
    $db->exec("DROP VIEW IF EXISTS v_shipping_methods_with_fees");
    $db->exec("CREATE VIEW v_shipping_methods_with_fees AS
        SELECT 
            sm.id, sm.code, sm.name, sm.description, sm.delivery_time,
            sm.price_multiplier, sm.is_active, sm.sort_order,
            sm.supports_tracking, sm.supports_cod, sm.created_at, sm.updated_at,
            COUNT(DISTINCT sf.id) as fee_config_count,
            MIN(sf.base_fee) as min_base_fee,
            MAX(sf.base_fee) as max_base_fee,
            MIN(sf.min_order_free_ship) as min_free_ship_threshold
        FROM shipping_methods sm
        LEFT JOIN shipping_fees sf ON sm.id = sf.shipping_method_id AND sf.is_active = 1
        GROUP BY sm.id, sm.code, sm.name, sm.description, sm.delivery_time, sm.price_multiplier, sm.is_active, sm.sort_order, sm.supports_tracking, sm.supports_cod, sm.created_at, sm.updated_at");
    
    echo "<div class='alert alert-success'>✓ VIEW đã được tạo mới!</div>";
}

// 2. Fix duplicates
echo "<h3>2. Kiểm tra/Sửa duplicate shipping methods</h3>";
$stmt = $db->query("SELECT code, COUNT(*) as cnt, GROUP_CONCAT(id ORDER BY id) as ids FROM shipping_methods GROUP BY code HAVING cnt > 1");
$codeDuplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($codeDuplicates) > 0) {
    echo "<div class='alert alert-warning'>Tìm thấy " . count($codeDuplicates) . " nhóm duplicate:</div>";
    foreach ($codeDuplicates as $dup) {
        $ids = explode(',', $dup['ids']);
        $keepId = $ids[0];
        $removeIds = array_slice($ids, 1);
        
        echo "<p>Code <code>{$dup['code']}</code>: giữ ID {$keepId}, xóa: " . implode(', ', $removeIds) . "</p>";
        
        foreach ($removeIds as $removeId) {
            $db->prepare("DELETE FROM shipping_fees WHERE shipping_method_id = ?")->execute([$removeId]);
            $db->prepare("DELETE FROM shipping_methods WHERE id = ?")->execute([$removeId]);
        }
    }
    echo "<div class='alert alert-success'>✓ Đã xóa duplicate records</div>";
} else {
    echo "<div class='alert alert-success'>✓ Không có duplicate</div>";
}

// 3. Admin query (giống shipping_config.php)
echo "<h3>3. Admin - Danh sách phương thức vận chuyển</h3>";
$adminMethods = $db->query("
    SELECT id, code, name, description, delivery_time, is_active, sort_order, fee_config_count, min_base_fee, min_free_ship_threshold
    FROM v_shipping_methods_with_fees 
    WHERE is_active = 1 
    ORDER BY sort_order ASC, id ASC
")->fetchAll(PDO::FETCH_ASSOC);

echo "<table>";
echo "<tr><th>ID</th><th>Code</th><th>Tên</th><th>Mô tả</th><th>Thời gian</th><th>Số cấu hình phí</th><th>Phí cơ bản</th><th>Miễn phí từ</th></tr>";
foreach ($adminMethods as $m) {
    $feeCount = $m['fee_config_count'] ?? 0;
    $baseFee = $m['min_base_fee'] ?? 0;
    $freeThreshold = $m['min_free_ship_threshold'] ?? 0;
    
    $feeInfo = '';
    if ($feeCount == 0) {
        $feeInfo = "<span style='color:red;'>Chưa cấu hình</span>";
    } elseif ($baseFee == 0) {
        $feeInfo = "<span style='color:green;'><strong>Miễn phí</strong></span>";
    } else {
        $feeInfo = number_format($baseFee, 0, ',', '.') . "₫";
        if ($freeThreshold > 0) {
            $feeInfo .= " → <span style='color:green;'>Miễn phí ≥ " . number_format($freeThreshold, 0, ',', '.') . "₫</span>";
        }
    }
    
    echo "<tr>";
    echo "<td>{$m['id']}</td>";
    echo "<td><code>{$m['code']}</code></td>";
    echo "<td><strong>{$m['name']}</strong></td>";
    echo "<td>{$m['description']}</td>";
    echo "<td>{$m['delivery_time']}</td>";
    echo "<td>{$feeCount}</td>";
    echo "<td>{$feeInfo}</td>";
    echo "<td>" . ($freeThreshold > 0 ? number_format($freeThreshold, 0, ',', '.') . "₫" : "-") . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "<p>Tổng: " . count($adminMethods) . " phương thức (admin hiển thị)</p>";

// 4. Checkout query (giống shipping_method_selector_v2.php)
echo "<h3>4. Checkout - Danh sách phương thức vận chuyển</h3>";
$checkoutMethods = $db->query("
    SELECT id, code, name, description, delivery_time, sort_order
    FROM shipping_methods 
    WHERE is_active = 1 
    ORDER BY sort_order ASC, id ASC
")->fetchAll(PDO::FETCH_ASSOC);

echo "<table>";
echo "<tr><th>ID</th><th>Code</th><th>Tên</th><th>Mô tả</th><th>Thời gian</th></tr>";
foreach ($checkoutMethods as $m) {
    echo "<tr>";
    echo "<td>{$m['id']}</td>";
    echo "<td><code>{$m['code']}</code></td>";
    echo "<td><strong>{$m['name']}</strong></td>";
    echo "<td>{$m['description']}</td>";
    echo "<td>{$m['delivery_time']}</td>";
    echo "</tr>";
}
echo "</table>";
echo "<p>Tổng: " . count($checkoutMethods) . " phương thức (checkout hiển thị)</p>";

// 5. So sánh
echo "<h3>5. So sánh Admin vs Checkout</h3>";
$adminCodes = array_column($adminMethods, 'code');
$checkoutCodes = array_column($checkoutMethods, 'code');

sort($adminCodes);
sort($checkoutCodes);

if ($adminCodes === $checkoutCodes && count($adminMethods) === count($checkoutMethods)) {
    echo "<div class='alert alert-success'><strong>✓ Admin và Checkout hiển thị ĐỒNG BỘ!</strong><br>";
    echo "Số phương thức: " . count($adminMethods) . "<br>";
    echo "Danh sách: " . implode(', ', $adminCodes) . "</div>";
} else {
    echo "<div class='alert alert-danger'><strong>✗ Admin và Checkout KHÔNG đồng bộ!</strong><br>";
    echo "Admin (" . count($adminMethods) . "): " . implode(', ', $adminCodes) . "<br>";
    echo "Checkout (" . count($checkoutMethods) . "): " . implode(', ', $checkoutCodes) . "</div>";
}

// 6. Kiểm tra cấu hình phí
echo "<h3>6. Kiểm tra cấu hình phí vận chuyển</h3>";
$feeStmt = $db->query("
    SELECT sf.id, sf.name, sf.base_fee, sf.fee_per_kg, sf.min_order_free_ship, sf.is_active,
           sm.code as method_code, sm.name as method_name
    FROM shipping_fees sf
    LEFT JOIN shipping_methods sm ON sf.shipping_method_id = sm.id
    ORDER BY sm.code, sf.priority DESC
");
$fees = $feeStmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table>";
echo "<tr><th>Phương thức</th><th>Tên cấu hình</th><th>Phí cơ bản</th><th>Phí/kg</th><th>Miễn phí từ</th><th>Trạng thái</th></tr>";
foreach ($fees as $f) {
    $status = $f['is_active'] ? "<span style='color:green;'>Hoạt động</span>" : "<span style='color:red;'>Tắt</span>";
    echo "<tr>";
    echo "<td>{$f['method_code']} - {$f['method_name']}</td>";
    echo "<td>{$f['name']}</td>";
    echo "<td>" . number_format($f['base_fee'], 0, ',', '.') . "₫</td>";
    echo "<td>" . number_format($f['fee_per_kg'], 0, ',', '.') . "₫</td>";
    echo "<td>" . ($f['min_order_free_ship'] ? number_format($f['min_order_free_ship'], 0, ',', '.') . "₫" : "-") . "</td>";
    echo "<td>{$status}</td>";
    echo "</tr>";
}
echo "</table>";
echo "<p>Tổng: " . count($fees) . " cấu hình phí</p>";

// 7. Tất cả shipping methods (kể cả inactive)
echo "<h3>7. Tất cả shipping methods (bao gồm inactive)</h3>";
$allMethods = $db->query("SELECT id, code, name, is_active, sort_order FROM shipping_methods ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

echo "<table>";
echo "<tr><th>ID</th><th>Code</th><th>Tên</th><th>Trạng thái</th><th>Thứ tự</th></tr>";
foreach ($allMethods as $m) {
    $status = $m['is_active'] ? "<span style='color:green;'>Hoạt động</span>" : "<span style='color:red;'>Tắt</span>";
    echo "<tr><td>{$m['id']}</td><td>{$m['code']}</td><td>{$m['name']}</td><td>{$status}</td><td>{$m['sort_order']}</td></tr>";
}
echo "</table>";
echo "<p>Tổng: " . count($allMethods) . " phương thức (" . count(array_filter($allMethods, fn($m) => $m['is_active'])) . " hoạt động)</p>";
