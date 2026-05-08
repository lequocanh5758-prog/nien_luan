-- =====================================================
-- SHIPPING FEES TABLE
-- Bảng cấu hình phí vận chuyển
-- =====================================================

CREATE TABLE IF NOT EXISTS shipping_fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Tên cấu hình phí',
    province_id INT DEFAULT NULL COMMENT 'ID tỉnh/thành (NULL = tất cả)',
    district_id INT DEFAULT NULL COMMENT 'ID quận/huyện (NULL = tất cả)',
    shipping_method_id INT NOT NULL COMMENT 'Phương thức vận chuyển',
    base_fee DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Phí cơ bản',
    weight_from DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Trọng lượng từ (kg)',
    weight_to DECIMAL(10,2) DEFAULT NULL COMMENT 'Trọng lượng đến (kg, NULL = không giới hạn)',
    fee_per_kg DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Phí mỗi kg vượt quá',
    order_value_from DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Giá trị đơn hàng từ',
    order_value_to DECIMAL(12,2) DEFAULT NULL COMMENT 'Giá trị đơn hàng đến (NULL = không giới hạn)',
    min_order_free_ship DECIMAL(12,2) DEFAULT NULL COMMENT 'Đơn hàng tối thiểu để miễn ship',
    priority INT DEFAULT 0 COMMENT 'Độ ưu tiên (cao hơn = ưu tiên hơn)',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1=Kích hoạt, 0=Vô hiệu',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shipping_method_id) REFERENCES shipping_methods(id) ON DELETE CASCADE,
    INDEX idx_method (shipping_method_id),
    INDEX idx_province (province_id),
    INDEX idx_district (district_id),
    INDEX idx_active (is_active),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Cấu hình phí vận chuyển';

-- =====================================================
-- VIEW: v_shipping_fees_detail
-- =====================================================

DROP VIEW IF EXISTS v_shipping_fees_detail;

CREATE VIEW v_shipping_fees_detail AS
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
LEFT JOIN vietnam_districts vd ON sf.district_id = vd.district_id;

-- =====================================================
-- VIEW: v_shipping_methods_with_fees
-- =====================================================

DROP VIEW IF EXISTS v_shipping_methods_with_fees;

CREATE VIEW v_shipping_methods_with_fees AS
SELECT 
    sm.*,
    COUNT(sf.id) as fee_count,
    MIN(sf.base_fee) as min_base_fee,
    MAX(sf.base_fee) as max_base_fee
FROM shipping_methods sm
LEFT JOIN shipping_fees sf ON sm.id = sf.shipping_method_id AND sf.is_active = 1
GROUP BY sm.id;

-- =====================================================
-- INSERT DEFAULT SHIPPING FEES
-- =====================================================

-- Lấy ID của phương thức vận chuyển
SET @manual_method_id = (SELECT id FROM shipping_methods WHERE code = 'MANUAL' LIMIT 1);
SET @ghn_method_id = (SELECT id FROM shipping_methods WHERE code = 'GHN' LIMIT 1);

-- Nếu không có GHN, dùng MANUAL
SET @method_id = IFNULL(@ghn_method_id, @manual_method_id);

-- Phí vận chuyển nội thành TP.HCM
INSERT INTO shipping_fees (name, province_id, district_id, shipping_method_id, base_fee, weight_from, weight_to, fee_per_kg, order_value_from, order_value_to, min_order_free_ship, priority, is_active)
SELECT 'Nội thành TP.HCM - Tiêu chuẩn', NULL, NULL, @method_id, 25000, 0, 3, 5000, 0, NULL, 500000, 10, 1
WHERE @method_id IS NOT NULL
ON DUPLICATE KEY UPDATE base_fee = VALUES(base_fee);

-- Phí vận chuyển ngoại thành TP.HCM
INSERT INTO shipping_fees (name, province_id, district_id, shipping_method_id, base_fee, weight_from, weight_to, fee_per_kg, order_value_from, order_value_to, min_order_free_ship, priority, is_active)
SELECT 'Ngoại thành TP.HCM - Tiêu chuẩn', NULL, NULL, @method_id, 35000, 0, 3, 8000, 0, NULL, 500000, 5, 1
WHERE @method_id IS NOT NULL
ON DUPLICATE KEY UPDATE base_fee = VALUES(base_fee);

-- Phí vận chuyển tỉnh khác
INSERT INTO shipping_fees (name, province_id, district_id, shipping_method_id, base_fee, weight_from, weight_to, fee_per_kg, order_value_from, order_value_to, min_order_free_ship, priority, is_active)
SELECT 'Tỉnh khác - Tiêu chuẩn', NULL, NULL, @method_id, 45000, 0, 3, 10000, 0, NULL, 500000, 1, 1
WHERE @method_id IS NOT NULL
ON DUPLICATE KEY UPDATE base_fee = VALUES(base_fee);

-- Phí vận chuyển nhanh (cao hơn)
INSERT INTO shipping_fees (name, province_id, district_id, shipping_method_id, base_fee, weight_from, weight_to, fee_per_kg, order_value_from, order_value_to, min_order_free_ship, priority, is_active)
SELECT 'Nội thành TP.HCM - Nhanh', NULL, NULL, @method_id, 40000, 0, 3, 8000, 0, NULL, 1000000, 15, 1
WHERE @method_id IS NOT NULL
ON DUPLICATE KEY UPDATE base_fee = VALUES(base_fee);

-- Phí vận chuyển hàng nặng (>3kg)
INSERT INTO shipping_fees (name, province_id, district_id, shipping_method_id, base_fee, weight_from, weight_to, fee_per_kg, order_value_from, order_value_to, min_order_free_ship, priority, is_active)
SELECT 'Hàng nặng (>3kg)', NULL, NULL, @method_id, 25000, 3, NULL, 8000, 0, NULL, NULL, 3, 1
WHERE @method_id IS NOT NULL
ON DUPLICATE KEY UPDATE base_fee = VALUES(base_fee);

-- =====================================================
-- TẠO HÀM TÍNH PHÍ VẬN CHUYỂN (STORED FUNCTION)
-- =====================================================

DROP FUNCTION IF EXISTS calculate_shipping_fee;

DELIMITER //

CREATE FUNCTION calculate_shipping_fee(
    p_method_id INT,
    p_province_id INT,
    p_district_id INT,
    p_weight DECIMAL(10,2),
    p_order_value DECIMAL(12,2)
) RETURNS DECIMAL(12,2)
DETERMINISTIC
BEGIN
    DECLARE v_base_fee DECIMAL(12,2) DEFAULT 30000;
    DECLARE v_fee_per_kg DECIMAL(12,2) DEFAULT 8000;
    DECLARE v_weight_from DECIMAL(10,2) DEFAULT 0;
    DECLARE v_free_threshold DECIMAL(12,2) DEFAULT 0;
    DECLARE v_final_fee DECIMAL(12,2) DEFAULT 0;
    DECLARE v_extra_weight DECIMAL(10,2) DEFAULT 0;
    
    -- Tìm cấu hình phí phù hợp nhất
    SELECT sf.base_fee, sf.fee_per_kg, sf.weight_from, COALESCE(sf.min_order_free_ship, 0)
    INTO v_base_fee, v_fee_per_kg, v_weight_from, v_free_threshold
    FROM shipping_fees sf
    WHERE sf.shipping_method_id = p_method_id
      AND sf.is_active = 1
      AND (sf.province_id = p_province_id OR sf.province_id IS NULL)
      AND (sf.district_id = p_district_id OR sf.district_id IS NULL)
      AND sf.weight_from <= p_weight
      AND (sf.weight_to IS NULL OR sf.weight_to >= p_weight)
    ORDER BY sf.priority DESC, sf.province_id DESC, sf.district_id DESC
    LIMIT 1;
    
    -- Tính phí theo trọng lượng
    SET v_extra_weight = GREATEST(0, p_weight - v_weight_from);
    SET v_final_fee = v_base_fee + (v_extra_weight * v_fee_per_kg);
    
    -- Kiểm tra miễn ship
    IF v_free_threshold > 0 AND p_order_value >= v_free_threshold THEN
        SET v_final_fee = 0;
    END IF;
    
    RETURN v_final_fee;
END //

DELIMITER ;

-- =====================================================
-- HOÀN TẤT
-- =====================================================

SELECT 'Đã tạo xong hệ thống vận chuyển!' as message;
