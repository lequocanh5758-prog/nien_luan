-- Migration 012: Optimize rating query indexes

ALTER TABLE product_reviews 
ADD INDEX IF NOT EXISTS idx_product_rating (idhanghoa, rating);

ALTER TABLE product_reviews 
ADD INDEX IF NOT EXISTS idx_created_at (created_at);

ALTER TABLE product_reviews 
ADD INDEX IF NOT EXISTS idx_status (is_approved);

ALTER TABLE product_reviews 
ADD INDEX IF NOT EXISTS idx_product_status_rating (idhanghoa, is_approved, rating);
