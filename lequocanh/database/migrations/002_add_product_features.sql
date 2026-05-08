-- Migration 002: Add product feature columns
-- Adds is_featured, is_new, is_sale, sale_price, view_count etc.

ALTER TABLE hanghoa 
ADD COLUMN IF NOT EXISTS is_featured TINYINT(1) DEFAULT 0 COMMENT 'Sản phẩm nổi bật',
ADD COLUMN IF NOT EXISTS is_new TINYINT(1) DEFAULT 0 COMMENT 'Sản phẩm mới',
ADD COLUMN IF NOT EXISTS is_sale TINYINT(1) DEFAULT 0 COMMENT 'Đang khuyến mãi',
ADD COLUMN IF NOT EXISTS sale_price DECIMAL(15,2) NULL COMMENT 'Giá khuyến mãi',
ADD COLUMN IF NOT EXISTS sale_percent INT NULL COMMENT 'Phần trăm giảm giá',
ADD COLUMN IF NOT EXISTS sale_start_date DATETIME NULL COMMENT 'Ngày bắt đầu khuyến mãi',
ADD COLUMN IF NOT EXISTS sale_end_date DATETIME NULL COMMENT 'Ngày kết thúc khuyến mãi',
ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0 COMMENT 'Số lượt xem',
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Ngày tạo',
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Ngày cập nhật';

CREATE INDEX IF NOT EXISTS idx_featured ON hanghoa(is_featured);
CREATE INDEX IF NOT EXISTS idx_new ON hanghoa(is_new);
CREATE INDEX IF NOT EXISTS idx_sale ON hanghoa(is_sale);
CREATE INDEX IF NOT EXISTS idx_created_at ON hanghoa(created_at);
CREATE INDEX IF NOT EXISTS idx_view_count ON hanghoa(view_count);
