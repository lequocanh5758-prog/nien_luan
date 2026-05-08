-- Migration 009: Add product status column

ALTER TABLE hanghoa 
ADD COLUMN IF NOT EXISTS trangthai ENUM('dang_ban', 'ngung_ban', 'het_hang') 
DEFAULT 'dang_ban' 
COMMENT 'Trạng thái sản phẩm: dang_ban=Đang bán, ngung_ban=Ngừng bán, het_hang=Hết hàng'
AFTER noibat;

CREATE INDEX IF NOT EXISTS idx_hanghoa_trangthai ON hanghoa(trangthai);

UPDATE hanghoa SET trangthai = 'dang_ban' WHERE trangthai IS NULL;

CREATE TABLE IF NOT EXISTS hanghoa_trangthai_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idhanghoa INT NOT NULL,
    trangthai_cu ENUM('dang_ban', 'ngung_ban', 'het_hang'),
    trangthai_moi ENUM('dang_ban', 'ngung_ban', 'het_hang') NOT NULL,
    ly_do VARCHAR(255),
    nguoi_thay_doi INT,
    ngay_thay_doi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idhanghoa) REFERENCES hanghoa(idhanghoa) ON DELETE CASCADE,
    INDEX idx_idhanghoa (idhanghoa),
    INDEX idx_ngay_thay_doi (ngay_thay_doi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
