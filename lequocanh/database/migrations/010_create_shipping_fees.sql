-- Migration 010: Shipping fees configuration table

CREATE TABLE IF NOT EXISTS shipping_fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Tên cấu hình phí',
    province_id INT DEFAULT NULL COMMENT 'ID tỉnh/thành (NULL = tất cả)',
    district_id INT DEFAULT NULL COMMENT 'ID quận/huyện (NULL = tất cả)',
    shipping_method_id INT NOT NULL COMMENT 'Phương thức vận chuyển',
    base_fee DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Phí cơ bản',
    weight_from DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Trọng lượng từ (kg)',
    weight_to DECIMAL(10,2) DEFAULT NULL COMMENT 'Trọng lượng đến (kg)',
    fee_per_kg DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Phí mỗi kg vượt quá',
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
    INDEX idx_district (district_id),
    INDEX idx_active (is_active),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
