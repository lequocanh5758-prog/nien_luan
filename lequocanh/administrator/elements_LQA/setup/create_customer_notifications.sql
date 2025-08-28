-- Create customer notifications table
CREATE TABLE IF NOT EXISTS customer_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(50),
    order_id INT,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_order_id (order_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create order cancel reasons table if not exists
CREATE TABLE IF NOT EXISTS order_cancel_reasons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    user_id VARCHAR(50),
    reason_code VARCHAR(50),
    reason_text VARCHAR(255),
    custom_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Note: Check and add these columns manually if they don't exist
-- ALTER TABLE don_hang ADD COLUMN pending_read TINYINT(1) DEFAULT 0;
-- ALTER TABLE don_hang ADD COLUMN approved_read TINYINT(1) DEFAULT 0;
-- ALTER TABLE don_hang ADD COLUMN cancelled_read TINYINT(1) DEFAULT 0;
