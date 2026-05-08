<?php

require_once __DIR__ . '/../mod/database.php';

$db = Database::getInstance()->getConnection();

$results = [];

try {
    // Bảng shipping_fees
    $db->exec("CREATE TABLE IF NOT EXISTS shipping_fees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        province_id INT DEFAULT NULL,
        district_id INT DEFAULT NULL,
        shipping_method_id INT NOT NULL,
        base_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
        weight_from DECIMAL(10,2) NOT NULL DEFAULT 0,
        weight_to DECIMAL(10,2) DEFAULT NULL,
        fee_per_kg DECIMAL(12,2) NOT NULL DEFAULT 0,
        order_value_from DECIMAL(12,2) NOT NULL DEFAULT 0,
        order_value_to DECIMAL(12,2) DEFAULT NULL,
        min_order_free_ship DECIMAL(12,2) DEFAULT NULL,
        priority INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_method (shipping_method_id),
        INDEX idx_province (province_id),
        INDEX idx_district (district_id),
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $results[] = "✓ Đã tạo bảng shipping_fees";

    // View v_shipping_fees_detail
    $db->exec("DROP VIEW IF EXISTS v_shipping_fees_detail");
    $db->exec("CREATE VIEW v_shipping_fees_detail AS
        SELECT 
            sf.*,
            sm.name as method_name,
            sm.code as method_code,
            sm.is_active as method_is_active,
            vp.province_name,
            vd.district_name
        FROM shipping_fees sf
        LEFT JOIN shipping_methods sm ON sf.shipping_method_id = sm.id
        LEFT JOIN vietnam_provinces vp ON sf.province_id = vp.province_id
        LEFT JOIN vietnam_districts vd ON sf.district_id = vd.district_id");
    $results[] = "✓ Đã tạo view v_shipping_fees_detail";

    // View v_shipping_methods_with_fees
    $db->exec("DROP VIEW IF EXISTS v_shipping_methods_with_fees");
    $db->exec("CREATE VIEW v_shipping_methods_with_fees AS
        SELECT 
            sm.id,
            sm.code,
            sm.name,
            sm.description,
            sm.delivery_time,
            sm.price_multiplier,
            sm.is_active,
            sm.sort_order,
            sm.supports_tracking,
            sm.supports_cod,
            sm.created_at,
            sm.updated_at,
            COUNT(DISTINCT sf.id) as fee_config_count,
            MIN(sf.base_fee) as min_base_fee,
            MAX(sf.base_fee) as max_base_fee,
            MIN(sf.min_order_free_ship) as min_free_ship_threshold
        FROM shipping_methods sm
        LEFT JOIN shipping_fees sf ON sm.id = sf.shipping_method_id AND sf.is_active = 1
        GROUP BY sm.id, sm.code, sm.name, sm.description, sm.delivery_time, sm.price_multiplier, sm.is_active, sm.sort_order, sm.supports_tracking, sm.supports_cod, sm.created_at, sm.updated_at");
    $results[] = "✓ Đã tạo view v_shipping_methods_with_fees";

    // Kiểm tra shipping_methods có dữ liệu không
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM shipping_methods");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    if ($count == 0) {
        // Tạo phương thức vận chuyển mặc định
        $db->exec("INSERT INTO shipping_methods (code, name, is_active, sort_order, supports_tracking, supports_cod) 
                   VALUES ('GHN', 'Giao hàng nhanh (GHN)', 1, 1, 1, 1),
                          ('GHTK', 'Giao hàng tiết kiệm', 1, 2, 1, 1),
                          ('VNPOST', 'Vietnam Post', 1, 3, 1, 0)");
        $results[] = "✓ Đã tạo phương thức vận chuyển mặc định";
    }

    // Lấy ID phương thức
    $stmt = $db->query("SELECT id FROM shipping_methods WHERE code = 'GHN' LIMIT 1");
    $ghnMethod = $stmt->fetch(PDO::FETCH_ASSOC);
    $methodId = $ghnMethod ? $ghnMethod['id'] : null;

    if ($methodId) {
        // Kiểm tra đã có phí vận chuyển chưa
        $stmt = $db->query("SELECT COUNT(*) as cnt FROM shipping_fees WHERE shipping_method_id = $methodId");
        $feeCount = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        
        if ($feeCount == 0) {
            // Thêm phí vận chuyển mặc định
            $db->exec("INSERT INTO shipping_fees (name, shipping_method_id, base_fee, weight_from, fee_per_kg, min_order_free_ship, priority, is_active)
                       VALUES ('Nội thành TP.HCM - Tiêu chuẩn', $methodId, 25000, 0, 5000, 500000, 10, 1),
                              ('Ngoại thành TP.HCM', $methodId, 35000, 0, 8000, 500000, 5, 1),
                              ('Tỉnh khác - Tiêu chuẩn', $methodId, 45000, 0, 10000, 500000, 1, 1),
                              ('Nội thành - Nhanh', $methodId, 40000, 0, 8000, 1000000, 15, 1),
                              ('Hàng nặng (>3kg)', $methodId, 25000, 3, 8000, NULL, 3, 1)");
            $results[] = "✓ Đã thêm phí vận chuyển mặc định";
        } else {
            $results[] = "Đã có $feeCount cấu hình phí vận chuyển";
        }
    }

    $results[] = "";
    $results[] = "✓ Hoàn thành!";

} catch (Exception $e) {
    $results[] = "✗ Lỗi: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Setup Shipping Fees</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4>Setup Hệ thống Vận chuyển</h4>
            </div>
            <div class="card-body">
                <?php foreach ($results as $result): ?>
                    <p class="<?php echo strpos($result, '✗') === 0 ? 'text-danger' : 'text-success'; ?>">
                        <?php echo $result; ?>
                    </p>
                <?php endforeach; ?>
                
                <hr>
                <a href="../index.php" class="btn btn-primary">Về trang quản trị</a>
                <a href="shipping_config.php" class="btn btn-success">Quản lý vận chuyển</a>
            </div>
        </div>
    </div>
</body>
</html>
