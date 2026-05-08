-- Migration 007: User addresses table

CREATE TABLE IF NOT EXISTS user_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    recipient_name VARCHAR(255) NOT NULL COMMENT 'Tên người nhận',
    phone VARCHAR(20) NOT NULL COMMENT 'Số điện thoại',
    province_id INT NOT NULL COMMENT 'ID tỉnh/thành',
    district_id INT NOT NULL COMMENT 'ID quận/huyện',
    ward_code VARCHAR(20) DEFAULT NULL COMMENT 'Mã phường/xã',
    address_detail VARCHAR(255) NOT NULL COMMENT 'Số nhà, tên đường',
    is_default TINYINT(1) DEFAULT 0 COMMENT '1=Địa chỉ mặc định',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(iduser) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_default (user_id, is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
