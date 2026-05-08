<?php
require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';
$db = Database::getInstance()->getConnection();

$results = [];

try {
    // Tạo bảng vietnam_wards nếu chưa có
    $checkWards = $db->query("SHOW TABLES LIKE 'vietnam_wards'")->rowCount();
    if ($checkWards == 0) {
        $db->exec("CREATE TABLE vietnam_wards (
            ward_code VARCHAR(20) PRIMARY KEY,
            district_id INT NOT NULL,
            ward_name VARCHAR(100) NOT NULL,
            status INT DEFAULT 1,
            INDEX idx_district (district_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $results[] = "✓ Đã tạo bảng vietnam_wards";
    } else {
        $results[] = "Bảng vietnam_wards đã tồn tại";
    }
    // Long An (province_id = 39)
    $db->exec("INSERT IGNORE INTO vietnam_districts (district_id, province_id, district_name, status) VALUES
        (3901, 39, 'Thành phố Tân An', 1),
        (3902, 39, 'Thị xã Kiến Tường', 1),
        (3903, 39, 'Huyện Bến Lức', 1),
        (3904, 39, 'Huyện Cần Đước', 1),
        (3905, 39, 'Huyện Cần Giuộc', 1),
        (3906, 39, 'Huyện Châu Thành', 1),
        (3907, 39, 'Huyện Đức Hòa', 1),
        (3908, 39, 'Huyện Đức Huệ', 1),
        (3909, 39, 'Huyện Mộc Hóa', 1),
        (3910, 39, 'Huyện Tân Hưng', 1),
        (3911, 39, 'Huyện Tân Thạnh', 1),
        (3912, 39, 'Huyện Tân Trụ', 1),
        (3913, 39, 'Huyện Thạnh Hóa', 1),
        (3914, 39, 'Huyện Thủ Thừa', 1),
        (3915, 39, 'Huyện Vĩnh Hưng', 1)");
    $results[] = "✓ Đã thêm 15 quận/huyện Long An";

    // Hà Nội (province_id = 1)
    $db->exec("INSERT IGNORE INTO vietnam_districts (district_id, province_id, district_name, status) VALUES
        (101, 1, 'Quận Ba Đình', 1), (102, 1, 'Quận Hoàn Kiếm', 1), (103, 1, 'Quận Tây Hồ', 1),
        (104, 1, 'Quận Long Biên', 1), (105, 1, 'Quận Cầu Giấy', 1), (106, 1, 'Quận Đống Đa', 1),
        (107, 1, 'Quận Hai Bà Trưng', 1), (108, 1, 'Quận Hoàng Mai', 1), (109, 1, 'Quận Thanh Xuân', 1),
        (110, 1, 'Huyện Đông Anh', 1), (111, 1, 'Huyện Gia Lâm', 1), (112, 1, 'Huyện Thanh Trì', 1)");
    $results[] = "✓ Đã thêm quận/huyện Hà Nội";

    // Đà Nẵng (province_id = 3)
    $db->exec("INSERT IGNORE INTO vietnam_districts (district_id, province_id, district_name, status) VALUES
        (301, 3, 'Quận Hải Châu', 1), (302, 3, 'Quận Thanh Khê', 1), (303, 3, 'Quận Sơn Trà', 1),
        (304, 3, 'Quận Ngũ Hành Sơn', 1), (305, 3, 'Quận Liên Chiểu', 1), (306, 3, 'Quận Cẩm Lệ', 1),
        (307, 3, 'Huyện Hòa Vang', 1)");
    $results[] = "✓ Đã thêm quận/huyện Đà Nẵng";

    // Bình Dương (province_id = 14)
    $db->exec("INSERT IGNORE INTO vietnam_districts (district_id, province_id, district_name, status) VALUES
        (1401, 14, 'Thành phố Thủ Dầu Một', 1), (1402, 14, 'Thị xã Bến Cát', 1),
        (1403, 14, 'Thị xã Dĩ An', 1), (1404, 14, 'Thị xã Tân Uyên', 1),
        (1405, 14, 'Thị xã Thuận An', 1), (1406, 14, 'Huyện Bàu Bàng', 1),
        (1407, 14, 'Huyện Bắc Tân Uyên', 1), (1408, 14, 'Huyện Dầu Tiếng', 1),
        (1409, 14, 'Huyện Phú Giáo', 1)");
    $results[] = "✓ Đã thêm quận/huyện Bình Dương";

    // Đồng Nai (province_id = 22)
    $db->exec("INSERT IGNORE INTO vietnam_districts (district_id, province_id, district_name, status) VALUES
        (2201, 22, 'Thành phố Biên Hòa', 1), (2202, 22, 'Thành phố Long Khánh', 1),
        (2203, 22, 'Huyện Cẩm Mỹ', 1), (2204, 22, 'Huyện Định Quán', 1),
        (2205, 22, 'Huyện Long Thành', 1), (2206, 22, 'Huyện Nhơn Trạch', 1),
        (2207, 22, 'Huyện Tân Phú', 1), (2208, 22, 'Huyện Thống Nhất', 1),
        (2209, 22, 'Huyện Trảng Bom', 1), (2210, 22, 'Huyện Vĩnh Cửu', 1),
        (2211, 22, 'Huyện Xuân Lộc', 1)");
    $results[] = "✓ Đã thêm quận/huyện Đồng Nai";

    // Cần Thơ (province_id = 5)
    $db->exec("INSERT IGNORE INTO vietnam_districts (district_id, province_id, district_name, status) VALUES
        (501, 5, 'Quận Ninh Kiều', 1), (502, 5, 'Quận Bình Thủy', 1),
        (503, 5, 'Quận Cái Răng', 1), (504, 5, 'Quận Ô Môn', 1),
        (505, 5, 'Quận Thốt Nốt', 1), (506, 5, 'Huyện Cờ Đỏ', 1),
        (507, 5, 'Huyện Phong Điền', 1), (508, 5, 'Huyện Thới Lai', 1),
        (509, 5, 'Huyện Vĩnh Thạnh', 1)");
    $results[] = "✓ Đã thêm quận/huyện Cần Thơ";

    // Wards cho Long An - TP Tân An (district_id = 3901)
    $db->exec("INSERT IGNORE INTO vietnam_wards (ward_code, district_id, ward_name, status) VALUES
        ('LA0101', 3901, 'Phường 1', 1), ('LA0102', 3901, 'Phường 2', 1),
        ('LA0103', 3901, 'Phường 3', 1), ('LA0104', 3901, 'Phường 4', 1),
        ('LA0105', 3901, 'Phường 5', 1), ('LA0106', 3901, 'Phường 6', 1),
        ('LA0107', 3901, 'Phường 7', 1), ('LA0108', 3901, 'Phường Khánh Hậu', 1),
        ('LA0109', 3901, 'Xã An Vĩnh Ngãi', 1), ('LA0110', 3901, 'Xã Bình Tâm', 1),
        ('LA0111', 3901, 'Xã Hướng Thọ Phú', 1), ('LA0112', 3901, 'Xã Lợi Bình Nhơn', 1),
        ('LA0113', 3901, 'Xã Nhơn Thạnh Trung', 1)");
    $results[] = "✓ Đã thêm phường/xã TP Tân An";

    // Wards cho Huyện Bến Lức (district_id = 3903)
    $db->exec("INSERT IGNORE INTO vietnam_wards (ward_code, district_id, ward_name, status) VALUES
        ('LA0301', 3903, 'Thị trấn Bến Lức', 1), ('LA0302', 3903, 'Xã An Thạnh', 1),
        ('LA0303', 3903, 'Xã Bình Đức', 1), ('LA0304', 3903, 'Xã Lương Bình', 1),
        ('LA0305', 3903, 'Xã Lương Hòa', 1), ('LA0306', 3903, 'Xã Mỹ Yên', 1),
        ('LA0307', 3903, 'Xã Nhựt Chánh', 1), ('LA0308', 3903, 'Xã Phước Lợi', 1),
        ('LA0309', 3903, 'Xã Tân Bửu', 1), ('LA0310', 3903, 'Xã Tân Hòa', 1),
        ('LA0311', 3903, 'Xã Thạnh Đức', 1), ('LA0312', 3903, 'Xã Thạnh Hòa', 1),
        ('LA0313', 3903, 'Xã Thạnh Lợi', 1), ('LA0314', 3903, 'Xã Thuận Đạo', 1)");
    $results[] = "✓ Đã thêm phường/xã Bến Lức";

    // Wards cho Huyện Cần Đước (district_id = 3904)
    $db->exec("INSERT IGNORE INTO vietnam_wards (ward_code, district_id, ward_name, status) VALUES
        ('LA0401', 3904, 'Thị trấn Cần Đước', 1), ('LA0402', 3904, 'Xã Long Hựu Đông', 1),
        ('LA0403', 3904, 'Xã Long Hựu Tây', 1), ('LA0404', 3904, 'Xã Long Khê', 1),
        ('LA0405', 3904, 'Xã Long Trạch', 1), ('LA0406', 3904, 'Xã Mỹ Lệ', 1),
        ('LA0407', 3904, 'Xã Phước Đông', 1), ('LA0408', 3904, 'Xã Phước Vân', 1),
        ('LA0409', 3904, 'Xã Tân Ân', 1), ('LA0410', 3904, 'Xã Tân Chánh', 1),
        ('LA0411', 3904, 'Xã Tân Lân', 1), ('LA0412', 3904, 'Xã Tân Trạch', 1)");
    $results[] = "✓ Đã thêm phường/xã Cần Đước";

    // Wards cho Huyện Đức Hòa (district_id = 3907)
    $db->exec("INSERT IGNORE INTO vietnam_wards (ward_code, district_id, ward_name, status) VALUES
        ('LA0701', 3907, 'Thị trấn Đức Hòa', 1), ('LA0702', 3907, 'Xã An Ninh Đông', 1),
        ('LA0703', 3907, 'Xã An Ninh Tây', 1), ('LA0704', 3907, 'Xã Đức Hòa Đông', 1),
        ('LA0705', 3907, 'Xã Đức Hòa Hạ', 1), ('LA0706', 3907, 'Xã Đức Hòa Thượng', 1),
        ('LA0707', 3907, 'Xã Đức Lập Hạ', 1), ('LA0708', 3907, 'Xã Đức Lập Thượng', 1),
        ('LA0709', 3907, 'Xã Hậu Nghĩa', 1), ('LA0710', 3907, 'Xã Hiệp Hòa', 1),
        ('LA0711', 3907, 'Xã Hòa Khánh Đông', 1), ('LA0712', 3907, 'Xã Hòa Khánh Nam', 1),
        ('LA0713', 3907, 'Xã Hòa Khánh Tây', 1), ('LA0714', 3907, 'Xã Lộc Giang', 1),
        ('LA0715', 3907, 'Xã Mỹ Hạnh Bắc', 1), ('LA0716', 3907, 'Xã Mỹ Hạnh Nam', 1)");
    $results[] = "✓ Đã thêm phường/xã Đức Hòa";

    // Wards cho các district còn lại của Long An (mỗi district 3-5 wards)
    $otherDistricts = [3902, 3905, 3906, 3908, 3909, 3910, 3911, 3912, 3913, 3914, 3915];
    foreach ($otherDistricts as $distId) {
        $stmt = $db->prepare("SELECT district_name FROM vietnam_districts WHERE district_id = ?");
        $stmt->execute([$distId]);
        $distName = $stmt->fetchColumn();
        if ($distName) {
            $wardCode1 = "LA{$distId}01";
            $wardCode2 = "LA{$distId}02";
            $wardCode3 = "LA{$distId}03";
            $db->exec("INSERT IGNORE INTO vietnam_wards (ward_code, district_id, ward_name, status) VALUES
                ('$wardCode1', $distId, 'Thị trấn/Phường Trung tâm', 1),
                ('$wardCode2', $distId, 'Xã/Xã 1', 1),
                ('$wardCode3', $distId, 'Xã/Xã 2', 1)");
        }
    }
    $results[] = "✓ Đã thêm phường/xã cho các huyện còn lại Long An";

    // Kiểm tra Long An
    $stmt = $db->prepare("SELECT COUNT(*) FROM vietnam_districts WHERE province_id = 39");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    $results[] = "Long An hiện có {$count} quận/huyện";
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM vietnam_wards WHERE district_id BETWEEN 3901 AND 3915");
    $stmt->execute();
    $wardCount = $stmt->fetchColumn();
    $results[] = "Long An hiện có {$wardCount} phường/xã";

    $results[] = "✅ HOÀN TẤT!";

} catch (Exception $e) {
    $results[] = "✗ LỖI: " . $e->getMessage();
}

foreach ($results as $r) {
    echo $r . "\n";
}
