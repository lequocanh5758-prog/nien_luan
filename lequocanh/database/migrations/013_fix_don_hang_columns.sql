-- Migration 013: Fix missing columns in don_hang table

ALTER TABLE `don_hang` ADD COLUMN `ngay_giao_hang` TIMESTAMP NULL DEFAULT NULL AFTER `ngay_tao`;

ALTER TABLE `don_hang` ADD COLUMN `ngay_nhan_hang` TIMESTAMP NULL DEFAULT NULL AFTER `ngay_giao_hang`;

ALTER TABLE `don_hang` MODIFY COLUMN `trang_thai` enum('pending','approved','cancelled','delivered','completed') NOT NULL DEFAULT 'pending';
