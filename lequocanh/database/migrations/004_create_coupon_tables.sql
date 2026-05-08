-- Migration 004: Coupon system tables

CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Mã coupon',
    name VARCHAR(255) NOT NULL COMMENT 'Tên mã giảm giá',
    description TEXT,
    discount_type ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
    discount_value DECIMAL(15,2) NOT NULL,
    max_discount DECIMAL(15,2) DEFAULT NULL,
    min_order_value DECIMAL(15,2) DEFAULT 0,
    usage_limit INT DEFAULT NULL,
    usage_count INT DEFAULT 0,
    usage_per_user INT DEFAULT 1,
    start_date DATETIME DEFAULT NULL,
    end_date DATETIME DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(50) DEFAULT NULL,
    INDEX idx_code (code),
    INDEX idx_active (is_active),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coupon_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    order_id INT NOT NULL,
    discount_amount DECIMAL(15,2) NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    INDEX idx_coupon (coupon_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
