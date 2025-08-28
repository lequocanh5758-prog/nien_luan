<?php
// Script khắc phục vấn đề bảng orders/don_hang
session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['ADMIN'])) {
    die('Bạn cần quyền admin để chạy script này!');
}

require_once './elements_LQA/mod/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "<h2>Khắc phục vấn đề bảng orders/don_hang</h2>";

try {
    // Kiểm tra các bảng tồn tại
    $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Bảng hiện có trong database:</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    $hasOrders = in_array('orders', $tables);
    $hasDonHang = in_array('don_hang', $tables);
    $hasOrderItems = in_array('order_items', $tables);
    $hasChiTietDonHang = in_array('chi_tiet_don_hang', $tables);
    
    echo "<h3>Trạng thái bảng:</h3>";
    echo "<ul>";
    echo "<li>Bảng 'orders': " . ($hasOrders ? "✅ Có" : "❌ Không") . "</li>";
    echo "<li>Bảng 'don_hang': " . ($hasDonHang ? "✅ Có" : "❌ Không") . "</li>";
    echo "<li>Bảng 'order_items': " . ($hasOrderItems ? "✅ Có" : "❌ Không") . "</li>";
    echo "<li>Bảng 'chi_tiet_don_hang': " . ($hasChiTietDonHang ? "✅ Có" : "❌ Không") . "</li>";
    echo "</ul>";
    
    // Tùy chọn khắc phục
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_orders_alias':
                // Tạo VIEW orders để alias cho don_hang
                if (!$hasOrders && $hasDonHang) {
                    $createViewSQL = "CREATE VIEW orders AS 
                        SELECT 
                            id,
                            ma_don_hang_text as order_code,
                            ma_nguoi_dung as user_id,
                            dia_chi_giao_hang as shipping_address,
                            tong_tien as total_amount,
                            trang_thai as status,
                            phuong_thuc_thanh_toan as payment_method,
                            trang_thai_thanh_toan as payment_status,
                            ngay_tao as created_at,
                            ngay_cap_nhat as updated_at
                        FROM don_hang";
                    
                    $conn->exec($createViewSQL);
                    echo "<div class='success'>✅ Đã tạo VIEW 'orders' cho bảng 'don_hang'</div>";
                }
                
                // Tạo VIEW order_items để alias cho chi_tiet_don_hang
                if (!$hasOrderItems && $hasChiTietDonHang) {
                    $createOrderItemsViewSQL = "CREATE VIEW order_items AS 
                        SELECT 
                            id,
                            ma_don_hang as order_id,
                            ma_san_pham as product_id,
                            so_luong as quantity,
                            gia as price,
                            ngay_tao as created_at
                        FROM chi_tiet_don_hang";
                    
                    $conn->exec($createOrderItemsViewSQL);
                    echo "<div class='success'>✅ Đã tạo VIEW 'order_items' cho bảng 'chi_tiet_don_hang'</div>";
                }
                break;
                
            case 'add_notification_columns':
                // Thêm cột thông báo vào bảng don_hang
                if ($hasDonHang) {
                    $columns = $conn->query("SHOW COLUMNS FROM don_hang")->fetchAll(PDO::FETCH_COLUMN);
                    
                    $notificationCols = [
                        'pending_read' => "ALTER TABLE don_hang ADD COLUMN pending_read TINYINT(1) DEFAULT 0",
                        'approved_read' => "ALTER TABLE don_hang ADD COLUMN approved_read TINYINT(1) DEFAULT 0", 
                        'cancelled_read' => "ALTER TABLE don_hang ADD COLUMN cancelled_read TINYINT(1) DEFAULT 0"
                    ];
                    
                    foreach ($notificationCols as $colName => $sql) {
                        if (!in_array($colName, $columns)) {
                            try {
                                $conn->exec($sql);
                                echo "<div class='success'>✅ Đã thêm cột '$colName' vào bảng don_hang</div>";
                            } catch (Exception $e) {
                                echo "<div class='error'>❌ Lỗi thêm cột '$colName': " . $e->getMessage() . "</div>";
                            }
                        }
                    }
                }
                break;
                
            case 'create_orders_table':
                // Tạo bảng orders thực sự nếu cần thiết
                if (!$hasOrders) {
                    $createOrdersSQL = "CREATE TABLE orders (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        order_code VARCHAR(50) NOT NULL,
                        user_id VARCHAR(50),
                        shipping_address TEXT,
                        total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
                        status ENUM('pending', 'approved', 'cancelled') NOT NULL DEFAULT 'pending',
                        payment_method VARCHAR(50) NOT NULL DEFAULT 'bank_transfer',
                        payment_status ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        pending_read TINYINT(1) DEFAULT 0,
                        approved_read TINYINT(1) DEFAULT 0,
                        cancelled_read TINYINT(1) DEFAULT 0
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                    
                    $conn->exec($createOrdersSQL);
                    echo "<div class='success'>✅ Đã tạo bảng 'orders'</div>";
                }
                
                if (!$hasOrderItems) {
                    $createOrderItemsSQL = "CREATE TABLE order_items (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        order_id INT NOT NULL,
                        product_id INT NOT NULL,
                        quantity INT NOT NULL DEFAULT 1,
                        price DECIMAL(15,2) NOT NULL DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                        FOREIGN KEY (product_id) REFERENCES hanghoa(idhanghoa) ON DELETE RESTRICT
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                    
                    $conn->exec($createOrderItemsSQL);
                    echo "<div class='success'>✅ Đã tạo bảng 'order_items'</div>";
                }
                break;
        }
        
        echo "<p><a href='fix_orders_table.php'>Làm mới trang</a></p>";
        
    } else {
        // Hiển thị form lựa chọn
        echo "<h3>Chọn giải pháp:</h3>";
        echo "<form method='post'>";
        
        if (!$hasOrders && $hasDonHang) {
            echo "<div>";
            echo "<button type='submit' name='action' value='create_orders_alias' class='btn-primary'>";
            echo "Tạo VIEW 'orders' cho bảng 'don_hang' (Khuyến nghị)";
            echo "</button>";
            echo "<p><small>Tạo view để map từ don_hang sang orders mà không duplicate data</small></p>";
            echo "</div>";
        }
        
        if ($hasDonHang) {
            echo "<div>";
            echo "<button type='submit' name='action' value='add_notification_columns' class='btn-secondary'>";
            echo "Thêm cột thông báo vào bảng don_hang";
            echo "</button>";
            echo "<p><small>Thêm các cột pending_read, approved_read, cancelled_read</small></p>";
            echo "</div>";
        }
        
        if (!$hasOrders) {
            echo "<div>";
            echo "<button type='submit' name='action' value='create_orders_table' class='btn-warning'>";
            echo "Tạo bảng orders mới (Không khuyến nghị nếu đã có don_hang)";
            echo "</button>";
            echo "<p><small>Tạo bảng orders hoàn toàn mới</small></p>";
            echo "</div>";
        }
        
        echo "</form>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>Lỗi: " . $e->getMessage() . "</div>";
}

echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .btn-primary { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
    .btn-secondary { background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
    .btn-warning { background: #ffc107; color: black; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
    button { display: block; margin: 10px 0; }
</style>";
?>
