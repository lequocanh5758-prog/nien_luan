-- Migration 011: Add is_reviewed column to orders

ALTER TABLE don_hang 
ADD COLUMN IF NOT EXISTS is_reviewed TINYINT(1) DEFAULT 0 
COMMENT '1 when all products in order have been reviewed';

CREATE INDEX IF NOT EXISTS idx_don_hang_is_reviewed ON don_hang(is_reviewed);
