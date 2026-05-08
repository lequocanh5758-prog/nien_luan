<?php

require_once __DIR__ . '/database.php';

$db = Database::getInstance()->getConnection();

try {
    echo "=== Tạo bảng Hỗ trợ khách hàng ===\n\n";

    // Bảng support_tickets
    echo "1. Tạo bảng support_tickets...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS support_tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_number VARCHAR(50) UNIQUE NOT NULL,
        user_id VARCHAR(50) NOT NULL,
        subject VARCHAR(500) NOT NULL,
        category ENUM('order_issue', 'product_question', 'review_report', 'other') DEFAULT 'other',
        related_review_id INT NULL,
        related_order_id INT NULL,
        status ENUM('open', 'in_progress', 'waiting_user', 'resolved', 'closed') DEFAULT 'open',
        assigned_to VARCHAR(50) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_status (status),
        INDEX idx_ticket_number (ticket_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "   ✓ Đã tạo bảng support_tickets\n";

    // Bảng support_messages
    echo "2. Tạo bảng support_messages...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS support_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        sender_id VARCHAR(50) NOT NULL,
        sender_type ENUM('user', 'admin') NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ticket (ticket_id),
        INDEX idx_sender (sender_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "   ✓ Đã tạo bảng support_messages\n";

    // Tạo view
    echo "3. Tạo view v_support_tickets_list...\n";
    $db->exec("DROP VIEW IF EXISTS v_support_tickets_list");
    $db->exec("CREATE VIEW v_support_tickets_list AS
        SELECT 
            t.*,
            u.hoten as user_name,
            u.dienthoai as user_phone,
            u.email as user_email,
            COALESCE(
                (SELECT COUNT(*) FROM support_messages sm 
                 WHERE sm.ticket_id = t.id 
                 AND sm.created_at > COALESCE(
                    (SELECT MAX(sm2.created_at) FROM support_messages sm2 
                     WHERE sm2.ticket_id = t.id AND sm2.sender_type = 'admin'),
                    '1970-01-01'
                 )
                 AND sm.sender_type = 'user'
                ), 0
            ) as unread_count,
            (SELECT COUNT(*) FROM support_messages sm WHERE sm.ticket_id = t.id) as message_count
        FROM support_tickets t
        LEFT JOIN user u ON t.user_id = u.username
    ");
    echo "   ✓ Đã tạo view v_support_tickets_list\n";

    echo "\n=== Hoàn thành! ===\n";

} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
}
