<?php

require_once __DIR__ . '/../mod/database.php';

$db = Database::getInstance()->getConnection();

$results = [];

try {
    // Kiểm tra collation của bảng user
    $collationQuery = $db->query("SHOW TABLE STATUS WHERE Name = 'user'");
    $tableStatus = $collationQuery->fetch(PDO::FETCH_ASSOC);
    $collation = $tableStatus['Collation'] ?? 'utf8mb4_general_ci';
    $results[] = "Collation hiện tại của bảng user: $collation";

    // Xóa view cũ nếu có
    $db->exec("DROP VIEW IF EXISTS v_support_tickets_list");

    // Xóa bảng cũ nếu có (để tạo lại với collation đúng)
    $db->exec("DROP TABLE IF EXISTS support_messages");
    $db->exec("DROP TABLE IF EXISTS support_tickets");
    $results[] = "✓ Đã xóa bảng cũ (nếu có)";

    // Bảng support_tickets
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$collation");
    $results[] = "✓ Đã tạo bảng support_tickets";

    // Bảng support_messages
    $db->exec("CREATE TABLE IF NOT EXISTS support_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        sender_id VARCHAR(50) NOT NULL,
        sender_type ENUM('user', 'admin') NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ticket (ticket_id),
        INDEX idx_sender (sender_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$collation");
    $results[] = "✓ Đã tạo bảng support_messages";
    
    // Thêm cột is_read nếu chưa có
    try {
        $db->exec("ALTER TABLE support_messages ADD COLUMN is_read TINYINT(1) DEFAULT 0");
        $results[] = "✓ Đã thêm cột is_read";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            $results[] = "Cột is_read đã tồn tại";
        }
    }

    // Tạo view
    $db->exec("CREATE VIEW v_support_tickets_list AS
        SELECT 
            t.*,
            u.hoten as user_name,
            u.dienthoai as user_phone,
            u.email as user_email,
            0 as unread_count,
            (SELECT COUNT(*) FROM support_messages sm WHERE sm.ticket_id = t.id) as message_count
        FROM support_tickets t
        LEFT JOIN user u ON t.user_id = u.username COLLATE $collation
    ");
    $results[] = "✓ Đã tạo view v_support_tickets_list";
    $results[] = "✓ Hoàn thành!";

} catch (Exception $e) {
    $results[] = "✗ Lỗi: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Setup Support Tables</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4>Setup Bảng Hỗ Trợ Khách Hàng</h4>
            </div>
            <div class="card-body">
                <?php foreach ($results as $result): ?>
                    <p class="<?php echo strpos($result, '✗') === 0 ? 'text-danger' : 'text-success'; ?>">
                        <?php echo $result; ?>
                    </p>
                <?php endforeach; ?>
                
                <hr>
                <a href="../index.php" class="btn btn-primary">Về trang quản trị</a>
                <a href="../../customer/support.php" class="btn btn-success">Đến trang hỗ trợ</a>
            </div>
        </div>
    </div>
</body>
</html>
