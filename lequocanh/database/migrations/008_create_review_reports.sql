-- Migration 008: Review reports table

CREATE TABLE IF NOT EXISTS review_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    reporter_id INT NOT NULL COMMENT 'User ID người báo cáo',
    reason VARCHAR(100) NOT NULL COMMENT 'Lý do báo cáo',
    description TEXT DEFAULT NULL COMMENT 'Mô tả chi tiết',
    status ENUM('pending','resolved','rejected') DEFAULT 'pending',
    admin_response TEXT DEFAULT NULL,
    resolved_by VARCHAR(50) DEFAULT NULL,
    resolved_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES product_reviews(id) ON DELETE CASCADE,
    INDEX idx_review (review_id),
    INDEX idx_reporter (reporter_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
