<?php
require_once __DIR__ . '/../administrator/elements_LQA/mod/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    $check = $db->query("SHOW COLUMNS FROM don_hang LIKE 'is_reviewed'")->fetch();
    
    if ($check) {
        echo "✓ Cột 'is_reviewed' đã tồn tại.\n";
    } else {
        $db->exec("ALTER TABLE don_hang ADD COLUMN is_reviewed TINYINT(1) DEFAULT 0 COMMENT '1 when all products in order have been reviewed'");
        echo "✓ Đã thêm cột 'is_reviewed'.\n";
    }
    
    $indexCheck = $db->query("SHOW INDEX FROM don_hang WHERE Key_name = 'idx_don_hang_is_reviewed'")->fetch();
    
    if (!$indexCheck) {
        $db->exec("CREATE INDEX idx_don_hang_is_reviewed ON don_hang(is_reviewed)");
        echo "✓ Đã tạo index.\n";
    }
    
    echo "\n✅ Migration hoàn tất!\n";
    
} catch (PDOException $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}
