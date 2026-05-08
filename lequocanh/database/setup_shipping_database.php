<?php
/**
 * Setup Shipping Database - Tạo tất cả bảng và VIEW cần thiết
 * Chạy file này 1 lần để setup database
 */

require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

$db = Database::getInstance()->getConnection();
$results = [];

try {
    // =============================================
    // 1. BẢNG shipping_methods - kiểm tra và thêm column thiếu
    // =============================================
    
    $checkTable = $db->query("SHOW TABLES LIKE 'shipping_methods'")->rowCount();
    if ($checkTable == 0) {
        $db->exec("CREATE TABLE shipping_methods (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(20) NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            description TEXT DEFAULT NULL,
            delivery_time VARCHAR(100) DEFAULT NULL,
            logo_url VARCHAR(255) DEFAULT NULL,
            api_endpoint VARCHAR(255) DEFAULT NULL,
            api_token TEXT DEFAULT NULL,
            shop_id VARCHAR(50) DEFAULT NULL,
            price_multiplier DECIMAL(5,2) DEFAULT 1.00,
            is_active TINYINT(1) DEFAULT 1,
            priority INT DEFAULT 100,
            sort_order INT DEFAULT 0,
            supports_tracking TINYINT(1) DEFAULT 1,
            supports_cod TINYINT(1) DEFAULT 1,
            config_json JSON DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_code (code),
            INDEX idx_active (is_active),
            INDEX idx_sort (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $results[] = "✓ Đã tạo bảng shipping_methods";
    } else {
        $results[] = "Bảng shipping_methods đã tồn tại";
    }
    
    // Kiểm tra và thêm các column thiếu
    $columns = $db->query("SHOW COLUMNS FROM shipping_methods")->fetchAll(PDO::FETCH_COLUMN);
    
    $neededColumns = [
        'description' => "TEXT DEFAULT NULL",
        'delivery_time' => "VARCHAR(100) DEFAULT NULL",
        'logo_url' => "VARCHAR(255) DEFAULT NULL",
        'api_endpoint' => "VARCHAR(255) DEFAULT NULL",
        'api_token' => "TEXT DEFAULT NULL",
        'shop_id' => "VARCHAR(50) DEFAULT NULL",
        'price_multiplier' => "DECIMAL(5,2) DEFAULT 1.00",
        'priority' => "INT DEFAULT 100",
        'sort_order' => "INT DEFAULT 0",
        'supports_tracking' => "TINYINT(1) DEFAULT 1",
        'supports_cod' => "TINYINT(1) DEFAULT 1",
        'config_json' => "JSON DEFAULT NULL"
    ];
    
    foreach ($neededColumns as $colName => $colDef) {
        if (!in_array($colName, $columns)) {
            $db->exec("ALTER TABLE shipping_methods ADD COLUMN $colName $colDef");
            $results[] = "✓ Đã thêm column $colName vào shipping_methods";
        }
    }

    // =============================================
    // 2. BẢNG shipping_fees
    // =============================================
    
    $checkTable = $db->query("SHOW TABLES LIKE 'shipping_fees'")->rowCount();
    if ($checkTable == 0) {
        $db->exec("CREATE TABLE shipping_fees (
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
            FOREIGN KEY (shipping_method_id) REFERENCES shipping_methods(id) ON DELETE CASCADE,
            INDEX idx_method (shipping_method_id),
            INDEX idx_province (province_id),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $results[] = "✓ Đã tạo bảng shipping_fees";
    } else {
        $results[] = "Bảng shipping_fees đã tồn tại";
    }

    // =============================================
    // 3. BẢNG vietnam_provinces
    // =============================================
    
    $checkTable = $db->query("SHOW TABLES LIKE 'vietnam_provinces'")->rowCount();
    if ($checkTable == 0) {
        $db->exec("CREATE TABLE vietnam_provinces (
            province_id INT PRIMARY KEY,
            province_name VARCHAR(100) NOT NULL,
            province_code VARCHAR(20) DEFAULT NULL,
            can_update_cod TINYINT(1) DEFAULT 1,
            status INT DEFAULT 1,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_name (province_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $results[] = "✓ Đã tạo bảng vietnam_provinces";
        
        // Insert dữ liệu tỉnh thành
        $db->exec("INSERT INTO vietnam_provinces (province_id, province_name, province_code, status) VALUES
            (1, 'Hà Nội', 'HN', 1), (2, 'Hồ Chí Minh', 'HCM', 1), (3, 'Đà Nẵng', 'DN', 1),
            (4, 'Hải Phòng', 'HP', 1), (5, 'Cần Thơ', 'CT', 1), (6, 'An Giang', 'AG', 1),
            (7, 'Bà Rịa - Vũng Tàu', 'VT', 1), (8, 'Bắc Giang', 'BG', 1), (9, 'Bắc Kạn', 'BK', 1),
            (10, 'Bạc Liêu', 'BL', 1), (11, 'Bắc Ninh', 'BN', 1), (12, 'Bến Tre', 'BT', 1),
            (13, 'Bình Định', 'BD', 1), (14, 'Bình Dương', 'BDG', 1), (15, 'Bình Phước', 'BP', 1),
            (16, 'Bình Thuận', 'BTH', 1), (17, 'Cà Mau', 'CM', 1), (18, 'Cao Bằng', 'CB', 1),
            (19, 'Đắk Lắk', 'DL', 1), (20, 'Đắk Nông', 'DNO', 1), (21, 'Điện Biên', 'DB', 1),
            (22, 'Đồng Nai', 'DNI', 1), (23, 'Đồng Tháp', 'DT', 1), (24, 'Gia Lai', 'GL', 1),
            (25, 'Hà Giang', 'HG', 1), (26, 'Hà Nam', 'HNA', 1), (27, 'Hà Tĩnh', 'HT', 1),
            (28, 'Hải Dương', 'HD', 1), (29, 'Hậu Giang', 'HGI', 1), (30, 'Hòa Bình', 'HB', 1),
            (31, 'Hưng Yên', 'HY', 1), (32, 'Khánh Hòa', 'KH', 1), (33, 'Kiên Giang', 'KG', 1),
            (34, 'Kon Tum', 'KT', 1), (35, 'Lai Châu', 'LC', 1), (36, 'Lâm Đồng', 'LD', 1),
            (37, 'Lạng Sơn', 'LS', 1), (38, 'Lào Cai', 'LCA', 1), (39, 'Long An', 'LA', 1),
            (40, 'Nam Định', 'ND', 1), (41, 'Nghệ An', 'NA', 1), (42, 'Ninh Bình', 'NB', 1),
            (43, 'Ninh Thuận', 'NT', 1), (44, 'Phú Thọ', 'PT', 1), (45, 'Phú Yên', 'PY', 1),
            (46, 'Quảng Bình', 'QB', 1), (47, 'Quảng Nam', 'QNA', 1), (48, 'Quảng Ngãi', 'QNG', 1),
            (49, 'Quảng Ninh', 'QNI', 1), (50, 'Quảng Trị', 'QT', 1), (51, 'Sóc Trăng', 'ST', 1),
            (52, 'Sơn La', 'SL', 1), (53, 'Tây Ninh', 'TN', 1), (54, 'Thái Bình', 'TB', 1),
            (55, 'Thái Nguyên', 'TNG', 1), (56, 'Thanh Hóa', 'TH', 1), (57, 'Thừa Thiên Huế', 'TTH', 1),
            (58, 'Tiền Giang', 'TG', 1), (59, 'Trà Vinh', 'TV', 1), (60, 'Tuyên Quang', 'TQ', 1),
            (61, 'Vĩnh Long', 'VL', 1), (62, 'Vĩnh Phúc', 'VP', 1), (63, 'Yên Bái', 'YB', 1)");
        $results[] = "✓ Đã thêm 63 tỉnh thành";
    } else {
        $results[] = "Bảng vietnam_provinces đã tồn tại";
    }

    // =============================================
    // 4. BẢNG vietnam_districts
    // =============================================
    
    $checkTable = $db->query("SHOW TABLES LIKE 'vietnam_districts'")->rowCount();
    if ($checkTable == 0) {
        $db->exec("CREATE TABLE vietnam_districts (
            district_id INT PRIMARY KEY,
            province_id INT NOT NULL,
            district_name VARCHAR(100) NOT NULL,
            district_code VARCHAR(20) DEFAULT NULL,
            can_update_cod TINYINT(1) DEFAULT 1,
            status INT DEFAULT 1,
            support_type INT DEFAULT 3,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (province_id) REFERENCES vietnam_provinces(province_id) ON DELETE CASCADE,
            INDEX idx_province (province_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $results[] = "✓ Đã tạo bảng vietnam_districts";
        
        // Insert quận huyện HCM
        $db->exec("INSERT INTO vietnam_districts (district_id, province_id, district_name, status) VALUES
            (101, 2, 'Quận 1', 1), (102, 2, 'Quận 2', 1), (103, 2, 'Quận 3', 1),
            (104, 2, 'Quận 4', 1), (105, 2, 'Quận 5', 1), (106, 2, 'Quận 6', 1),
            (107, 2, 'Quận 7', 1), (108, 2, 'Quận 8', 1), (109, 2, 'Quận 9', 1),
            (110, 2, 'Quận 10', 1), (111, 2, 'Quận 11', 1), (112, 2, 'Quận 12', 1),
            (113, 2, 'Bình Thạnh', 1), (114, 2, 'Tân Bình', 1), (115, 2, 'Tân Phú', 1),
            (116, 2, 'Phú Nhuận', 1), (117, 2, 'Gò Vấp', 1), (118, 2, 'Bình Tân', 1),
            (119, 2, 'Thủ Đức', 1)");
        $results[] = "✓ Đã thêm quận huyện HCM";
    } else {
        $results[] = "Bảng vietnam_districts đã tồn tại";
    }

    // =============================================
    // 5. VIEW v_shipping_methods_with_fees
    // =============================================
    
    $db->exec("DROP VIEW IF EXISTS v_shipping_methods_with_fees");
    $db->exec("CREATE VIEW v_shipping_methods_with_fees AS
        SELECT 
            sm.id, sm.code, sm.name, sm.description, sm.delivery_time,
            sm.logo_url, sm.price_multiplier, sm.is_active, sm.sort_order,
            sm.supports_tracking, sm.supports_cod, sm.created_at, sm.updated_at,
            COUNT(DISTINCT sf.id) as fee_config_count,
            MIN(sf.base_fee) as min_base_fee,
            MAX(sf.base_fee) as max_base_fee,
            MIN(sf.min_order_free_ship) as min_free_ship_threshold
        FROM shipping_methods sm
        LEFT JOIN shipping_fees sf ON sm.id = sf.shipping_method_id AND sf.is_active = 1
        GROUP BY sm.id, sm.code, sm.name, sm.description, sm.delivery_time,
                 sm.logo_url, sm.price_multiplier, sm.is_active, sm.sort_order,
                 sm.supports_tracking, sm.supports_cod, sm.created_at, sm.updated_at");
    $results[] = "✓ Đã tạo VIEW v_shipping_methods_with_fees";

    // =============================================
    // 6. VIEW v_shipping_fees_detail
    // =============================================
    
    $db->exec("DROP VIEW IF EXISTS v_shipping_fees_detail");
    $db->exec("CREATE VIEW v_shipping_fees_detail AS
        SELECT 
            sf.*,
            sm.name as shipping_method_name,
            sm.code as method_code,
            vp.province_name,
            vd.district_name
        FROM shipping_fees sf
        LEFT JOIN shipping_methods sm ON sf.shipping_method_id = sm.id
        LEFT JOIN vietnam_provinces vp ON sf.province_id = vp.province_id
        LEFT JOIN vietnam_districts vd ON sf.district_id = vd.district_id");
    $results[] = "✓ Đã tạo VIEW v_shipping_fees_detail";

    // =============================================
    // 7. Insert dữ liệu mặc định nếu chưa có
    // =============================================
    
    $methodCount = $db->query("SELECT COUNT(*) FROM shipping_methods")->fetchColumn();
    if ($methodCount == 0) {
        $db->exec("INSERT INTO shipping_methods (code, name, description, delivery_time, is_active, sort_order, supports_tracking, supports_cod)
            VALUES 
            ('GHN', 'Giao Hàng Nhanh', 'Giao hàng nhanh toàn quốc', '1-3 ngày', 1, 1, 1, 1),
            ('GHTK', 'Giao Hàng Tiết Kiệm', 'Giao hàng tiết kiệm', '2-5 ngày', 1, 2, 1, 1),
            ('VNPOST', 'Vietnam Post', 'Bưu điện Việt Nam', '3-7 ngày', 1, 3, 0, 1)");
        $results[] = "✓ Đã thêm 3 phương thức vận chuyển mặc định";
    }

    $feeCount = $db->query("SELECT COUNT(*) FROM shipping_fees")->fetchColumn();
    if ($feeCount == 0) {
        $ghnId = $db->query("SELECT id FROM shipping_methods WHERE code = 'GHN' LIMIT 1")->fetchColumn();
        if ($ghnId) {
            $db->exec("INSERT INTO shipping_fees (name, shipping_method_id, base_fee, weight_from, fee_per_kg, min_order_free_ship, priority, is_active)
                VALUES 
                ('Nội thành TP.HCM', $ghnId, 25000, 0, 5000, 500000, 10, 1),
                ('Ngoại thành TP.HCM', $ghnId, 35000, 0, 8000, 500000, 5, 1),
                ('Tỉnh khác', $ghnId, 45000, 0, 10000, 500000, 1, 1)");
            $results[] = "✓ Đã thêm cấu hình phí vận chuyển mặc định";
        }
    }

    // =============================================
    // 8. Kiểm tra provinces table
    // =============================================
    
    $checkProvinces = $db->query("SHOW TABLES LIKE 'provinces'")->rowCount();
    if ($checkProvinces == 0) {
        $db->exec("CREATE TABLE provinces (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            code VARCHAR(20) DEFAULT NULL,
            region VARCHAR(50) DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $db->exec("INSERT INTO provinces (id, name, code, is_active) VALUES
            (1, 'Hà Nội', 'HN', 1), (2, 'Hồ Chí Minh', 'HCM', 1), (3, 'Đà Nẵng', 'DN', 1),
            (4, 'Hải Phòng', 'HP', 1), (5, 'Cần Thơ', 'CT', 1), (6, 'An Giang', 'AG', 1),
            (7, 'Bà Rịa - Vũng Tàu', 'VT', 1), (8, 'Bắc Giang', 'BG', 1), (9, 'Bắc Kạn', 'BK', 1),
            (10, 'Bạc Liêu', 'BL', 1), (11, 'Bắc Ninh', 'BN', 1), (12, 'Bến Tre', 'BT', 1),
            (13, 'Bình Định', 'BD', 1), (14, 'Bình Dương', 'BDG', 1), (15, 'Bình Phước', 'BP', 1),
            (16, 'Bình Thuận', 'BTH', 1), (17, 'Cà Mau', 'CM', 1), (18, 'Cao Bằng', 'CB', 1),
            (19, 'Đắk Lắk', 'DL', 1), (20, 'Đắk Nông', 'DNO', 1), (21, 'Điện Biên', 'DB', 1),
            (22, 'Đồng Nai', 'DNI', 1), (23, 'Đồng Tháp', 'DT', 1), (24, 'Gia Lai', 'GL', 1),
            (25, 'Hà Giang', 'HG', 1), (26, 'Hà Nam', 'HNA', 1), (27, 'Hà Tĩnh', 'HT', 1),
            (28, 'Hải Dương', 'HD', 1), (29, 'Hậu Giang', 'HGI', 1), (30, 'Hòa Bình', 'HB', 1),
            (31, 'Hưng Yên', 'HY', 1), (32, 'Khánh Hòa', 'KH', 1), (33, 'Kiên Giang', 'KG', 1),
            (34, 'Kon Tum', 'KT', 1), (35, 'Lai Châu', 'LC', 1), (36, 'Lâm Đồng', 'LD', 1),
            (37, 'Lạng Sơn', 'LS', 1), (38, 'Lào Cai', 'LCA', 1), (39, 'Long An', 'LA', 1),
            (40, 'Nam Định', 'ND', 1), (41, 'Nghệ An', 'NA', 1), (42, 'Ninh Bình', 'NB', 1),
            (43, 'Ninh Thuận', 'NT', 1), (44, 'Phú Thọ', 'PT', 1), (45, 'Phú Yên', 'PY', 1),
            (46, 'Quảng Bình', 'QB', 1), (47, 'Quảng Nam', 'QNA', 1), (48, 'Quảng Ngãi', 'QNG', 1),
            (49, 'Quảng Ninh', 'QNI', 1), (50, 'Quảng Trị', 'QT', 1), (51, 'Sóc Trăng', 'ST', 1),
            (52, 'Sơn La', 'SL', 1), (53, 'Tây Ninh', 'TN', 1), (54, 'Thái Bình', 'TB', 1),
            (55, 'Thái Nguyên', 'TNG', 1), (56, 'Thanh Hóa', 'TH', 1), (57, 'Thừa Thiên Huế', 'TTH', 1),
            (58, 'Tiền Giang', 'TG', 1), (59, 'Trà Vinh', 'TV', 1), (60, 'Tuyên Quang', 'TQ', 1),
            (61, 'Vĩnh Long', 'VL', 1), (62, 'Vĩnh Phúc', 'VP', 1), (63, 'Yên Bái', 'YB', 1)");
        $results[] = "✓ Đã tạo bảng provinces và thêm 63 tỉnh thành";
    } else {
        $results[] = "Bảng provinces đã tồn tại";
    }

    $results[] = "";
    $results[] = "✅ SETUP HOÀN TẤT!";

} catch (Exception $e) {
    $results[] = "✗ LỖI: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Setup Shipping Database</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4>Setup Hệ thống Vận chuyển - Database</h4>
            </div>
            <div class="card-body">
                <?php foreach ($results as $r): ?>
                    <p class="<?php echo (strpos($r, '✗') === 0) ? 'text-danger' : 'text-success'; ?>">
                        <?php echo htmlspecialchars($r); ?>
                    </p>
                <?php endforeach; ?>
                <hr>
                <a href="administrator/index.php?req=shipping_config" class="btn btn-primary">Đến trang quản lý vận chuyển</a>
            </div>
        </div>
    </div>
</body>
</html>
