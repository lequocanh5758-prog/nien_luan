-- Migration 003: Shipping system tables

CREATE TABLE IF NOT EXISTS shipping_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE COMMENT 'Provider code: GHN, GHTK, VNPOST',
    name VARCHAR(100) NOT NULL COMMENT 'Display name',
    logo_url VARCHAR(255) DEFAULT NULL,
    api_endpoint VARCHAR(255) DEFAULT NULL,
    api_token TEXT DEFAULT NULL,
    shop_id VARCHAR(50) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    priority INT DEFAULT 100,
    supports_tracking TINYINT(1) DEFAULT 1,
    supports_cod TINYINT(1) DEFAULT 1,
    config_json JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_active (is_active),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shipping_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    method_id INT NOT NULL,
    from_province_id INT DEFAULT NULL,
    from_province_name VARCHAR(100) DEFAULT NULL,
    to_province_id INT DEFAULT NULL,
    to_province_name VARCHAR(100) DEFAULT NULL,
    to_district_id INT DEFAULT NULL,
    base_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
    per_km_fee DECIMAL(12,2) NOT NULL DEFAULT 5000,
    min_fee DECIMAL(12,2) NOT NULL DEFAULT 15000,
    max_fee DECIMAL(12,2) DEFAULT NULL,
    estimated_days INT DEFAULT 3,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (method_id) REFERENCES shipping_methods(id) ON DELETE CASCADE,
    INDEX idx_method (method_id),
    INDEX idx_from_province (from_province_id),
    INDEX idx_to_province (to_province_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vietnam_provinces (
    province_id INT PRIMARY KEY,
    province_name VARCHAR(100) NOT NULL,
    province_type VARCHAR(50) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vietnam_districts (
    district_id INT PRIMARY KEY,
    province_id INT NOT NULL,
    district_name VARCHAR(100) NOT NULL,
    district_type VARCHAR(50) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (province_id) REFERENCES vietnam_provinces(province_id),
    INDEX idx_province (province_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vietnam_wards (
    ward_code VARCHAR(20) PRIMARY KEY,
    district_id INT NOT NULL,
    ward_name VARCHAR(100) NOT NULL,
    ward_type VARCHAR(50) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (district_id) REFERENCES vietnam_districts(district_id),
    INDEX idx_district (district_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
