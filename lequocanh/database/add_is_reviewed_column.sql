-- Add is_reviewed column to don_hang table
-- This tracks whether all products in an order have been reviewed

ALTER TABLE don_hang 
ADD COLUMN IF NOT EXISTS is_reviewed TINYINT(1) DEFAULT 0 
COMMENT '1 when all products in order have been reviewed';

-- Add index for querying reviewed orders
CREATE INDEX IF NOT EXISTS idx_don_hang_is_reviewed ON don_hang(is_reviewed);
