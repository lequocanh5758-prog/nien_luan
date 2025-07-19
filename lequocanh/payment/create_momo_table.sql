-- Tạo bảng để lưu trữ thông tin giao dịch MoMo
CREATE TABLE IF NOT EXISTS momo_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(100) NOT NULL UNIQUE,
    request_id VARCHAR(100) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    order_info TEXT,
    status ENUM('PENDING', 'SUCCESS', 'FAILED', 'CANCELLED') DEFAULT 'PENDING',
    trans_id VARCHAR(100) NULL,
    message TEXT NULL,
    extra_data TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_order_id (order_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm comment cho bảng
ALTER TABLE momo_transactions COMMENT = 'Bảng lưu trữ thông tin giao dịch thanh toán MoMo';

-- Thêm comment cho các cột
ALTER TABLE momo_transactions 
MODIFY COLUMN id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID tự tăng',
MODIFY COLUMN order_id VARCHAR(100) NOT NULL UNIQUE COMMENT 'Mã đơn hàng duy nhất',
MODIFY COLUMN request_id VARCHAR(100) NOT NULL COMMENT 'Mã request tới MoMo',
MODIFY COLUMN amount DECIMAL(15,2) NOT NULL COMMENT 'Số tiền thanh toán (VND)',
MODIFY COLUMN order_info TEXT COMMENT 'Thông tin đơn hàng',
MODIFY COLUMN status ENUM('PENDING', 'SUCCESS', 'FAILED', 'CANCELLED') DEFAULT 'PENDING' COMMENT 'Trạng thái giao dịch',
MODIFY COLUMN trans_id VARCHAR(100) NULL COMMENT 'Mã giao dịch từ MoMo',
MODIFY COLUMN message TEXT NULL COMMENT 'Thông báo từ MoMo',
MODIFY COLUMN extra_data TEXT NULL COMMENT 'Dữ liệu bổ sung',
MODIFY COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
MODIFY COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật';
