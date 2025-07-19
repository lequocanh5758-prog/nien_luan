<?php
/**
 * Database Setup Script
 * Thiết lập database cho hệ thống
 */

echo "=== DATABASE SETUP SCRIPT ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Cấu hình database
$configs = [
    [
        'name' => 'Docker MySQL',
        'host' => 'mysql',
        'port' => 3306,
        'username' => 'root',
        'password' => 'root'
    ],
    [
        'name' => 'Local MySQL',
        'host' => 'localhost', 
        'port' => 3306,
        'username' => 'root',
        'password' => 'pw'
    ]
];

$pdo = null;
$connectedConfig = null;

// Thử kết nối
foreach ($configs as $config) {
    echo "Trying to connect to {$config['name']}...\n";
    
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "✅ Connected to {$config['name']}\n";
        $connectedConfig = $config;
        break;
        
    } catch (Exception $e) {
        echo "❌ Failed to connect to {$config['name']}: " . $e->getMessage() . "\n";
    }
}

if (!$pdo) {
    die("❌ Could not connect to any database server\n");
}

// Tạo database nếu chưa có
$databases = ['trainingdb', 'sales_management'];

foreach ($databases as $dbname) {
    echo "\n--- Setting up database: $dbname ---\n";
    
    try {
        // Tạo database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Database '$dbname' created/exists\n";
        
        // Chọn database
        $pdo->exec("USE `$dbname`");
        
        // Tạo các bảng cần thiết
        createTables($pdo, $dbname);
        
        // Thêm dữ liệu mẫu
        insertSampleData($pdo, $dbname);
        
    } catch (Exception $e) {
        echo "❌ Error setting up database '$dbname': " . $e->getMessage() . "\n";
    }
}

// Cập nhật config.ini
updateConfigFile($connectedConfig);

echo "\n=== SETUP COMPLETE ===\n";

function createTables($pdo, $dbname) {
    echo "Creating tables for $dbname...\n";
    
    $tables = [
        'users' => "
            CREATE TABLE IF NOT EXISTS `users` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `username` varchar(50) NOT NULL,
                `password` varchar(255) NOT NULL,
                `email` varchar(100) DEFAULT NULL,
                `ten` varchar(100) DEFAULT NULL,
                `diachi` text,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        
        'loaihanghoa' => "
            CREATE TABLE IF NOT EXISTS `loaihanghoa` (
                `idloaihanghoa` int(11) NOT NULL AUTO_INCREMENT,
                `tenloaihanghoa` varchar(100) NOT NULL,
                `mota` text,
                PRIMARY KEY (`idloaihanghoa`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        
        'hanghoa' => "
            CREATE TABLE IF NOT EXISTS `hanghoa` (
                `idhanghoa` int(11) NOT NULL AUTO_INCREMENT,
                `tenhanghoa` varchar(200) NOT NULL,
                `giathamkhao` decimal(15,2) DEFAULT 0,
                `mota` text,
                `hinhanh` int(11) DEFAULT NULL,
                `idloaihanghoa` int(11) DEFAULT NULL,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`idhanghoa`),
                KEY `idx_loaihanghoa` (`idloaihanghoa`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        
        'dongia' => "
            CREATE TABLE IF NOT EXISTS `dongia` (
                `idDonGia` int(11) NOT NULL AUTO_INCREMENT,
                `idHangHoa` int(11) NOT NULL,
                `giaBan` decimal(15,2) NOT NULL,
                `ngayApDung` date NOT NULL,
                `ngayKetThuc` date NOT NULL,
                `dieuKien` varchar(255) DEFAULT NULL,
                `ghiChu` text,
                `apDung` tinyint(1) DEFAULT 0,
                PRIMARY KEY (`idDonGia`),
                KEY `idx_hanghoa` (`idHangHoa`),
                KEY `idx_apdung` (`apDung`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        
        'don_hang' => "
            CREATE TABLE IF NOT EXISTS `don_hang` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `ma_don_hang_text` varchar(50) NOT NULL,
                `ma_nguoi_dung` varchar(50) DEFAULT NULL,
                `dia_chi_giao_hang` text,
                `tong_tien` decimal(15,2) NOT NULL,
                `trang_thai` enum('pending','approved','cancelled') DEFAULT 'pending',
                `phuong_thuc_thanh_toan` varchar(50) DEFAULT 'bank_transfer',
                `trang_thai_thanh_toan` enum('pending','paid','failed') DEFAULT 'pending',
                `ngay_tao` timestamp DEFAULT CURRENT_TIMESTAMP,
                `ngay_cap_nhat` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `ma_don_hang_text` (`ma_don_hang_text`),
                KEY `idx_user` (`ma_nguoi_dung`),
                KEY `idx_status` (`trang_thai`),
                KEY `idx_payment_status` (`trang_thai_thanh_toan`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        
        'chi_tiet_don_hang' => "
            CREATE TABLE IF NOT EXISTS `chi_tiet_don_hang` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `ma_don_hang` int(11) NOT NULL,
                `ma_san_pham` int(11) NOT NULL,
                `so_luong` int(11) NOT NULL,
                `gia` decimal(15,2) NOT NULL,
                `ngay_tao` timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_order` (`ma_don_hang`),
                KEY `idx_product` (`ma_san_pham`),
                FOREIGN KEY (`ma_don_hang`) REFERENCES `don_hang`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        
        'tonkho' => "
            CREATE TABLE IF NOT EXISTS `tonkho` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `idHangHoa` int(11) NOT NULL,
                `soLuong` int(11) DEFAULT 0,
                `ngayCapNhat` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_product` (`idHangHoa`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        "
    ];
    
    foreach ($tables as $tableName => $sql) {
        try {
            $pdo->exec($sql);
            echo "✅ Table '$tableName' created/updated\n";
        } catch (Exception $e) {
            echo "❌ Error creating table '$tableName': " . $e->getMessage() . "\n";
        }
    }
}

function insertSampleData($pdo, $dbname) {
    echo "Inserting sample data for $dbname...\n";
    
    try {
        // Thêm loại hàng hóa
        $pdo->exec("INSERT IGNORE INTO loaihanghoa (idloaihanghoa, tenloaihanghoa, mota) VALUES 
            (1, 'Điện thoại', 'Các loại điện thoại thông minh'),
            (2, 'Laptop', 'Máy tính xách tay'),
            (3, 'Phụ kiện', 'Phụ kiện điện tử')");
        
        // Thêm sản phẩm mẫu
        $pdo->exec("INSERT IGNORE INTO hanghoa (idhanghoa, tenhanghoa, giathamkhao, mota, idloaihanghoa) VALUES 
            (1, 'iPhone 15 Pro Max', 30000000, 'Điện thoại iPhone 15 Pro Max 256GB', 1),
            (2, 'Samsung Galaxy S24', 25000000, 'Điện thoại Samsung Galaxy S24 Ultra', 1),
            (3, 'MacBook Pro M3', 45000000, 'MacBook Pro 14 inch với chip M3', 2),
            (4, 'Dell XPS 13', 35000000, 'Laptop Dell XPS 13 inch', 2),
            (5, 'AirPods Pro', 6000000, 'Tai nghe không dây AirPods Pro', 3)");
        
        // Thêm đơn giá mẫu
        $pdo->exec("INSERT IGNORE INTO dongia (idDonGia, idHangHoa, giaBan, ngayApDung, ngayKetThuc, ghiChu, apDung) VALUES 
            (1, 1, 30000000, '2024-01-01', '2024-12-31', 'Giá niêm yết iPhone 15 Pro Max', 1),
            (2, 2, 25000000, '2024-01-01', '2024-12-31', 'Giá niêm yết Samsung Galaxy S24', 1),
            (3, 3, 45000000, '2024-01-01', '2024-12-31', 'Giá niêm yết MacBook Pro M3', 1),
            (4, 4, 35000000, '2024-01-01', '2024-12-31', 'Giá niêm yết Dell XPS 13', 1),
            (5, 5, 6000000, '2024-01-01', '2024-12-31', 'Giá niêm yết AirPods Pro', 1)");
        
        // Thêm tồn kho mẫu
        $pdo->exec("INSERT IGNORE INTO tonkho (idHangHoa, soLuong) VALUES 
            (1, 50), (2, 30), (3, 20), (4, 25), (5, 100)");
        
        // Thêm user mẫu
        $hashedPassword = password_hash('123456', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT IGNORE INTO users (id, username, password, email, ten, diachi) VALUES 
            (1, 'admin', ?, 'admin@example.com', 'Administrator', 'Hà Nội, Việt Nam')")
            ->execute([$hashedPassword]);
        
        echo "✅ Sample data inserted\n";
        
        // Hiển thị thống kê
        $stats = [
            'loaihanghoa' => 'Loại hàng hóa',
            'hanghoa' => 'Sản phẩm', 
            'dongia' => 'Đơn giá',
            'tonkho' => 'Tồn kho',
            'users' => 'Người dùng'
        ];
        
        foreach ($stats as $table => $name) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $stmt->fetch()['count'];
            echo "- $name: $count records\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error inserting sample data: " . $e->getMessage() . "\n";
    }
}

function updateConfigFile($config) {
    echo "\nUpdating config.ini...\n";
    
    $configContent = "[section]\n";
    $configContent .= "; Updated by setup script\n";
    $configContent .= "servername = {$config['host']}\n";
    $configContent .= "port = {$config['port']}\n";
    $configContent .= "dbname = trainingdb\n";
    $configContent .= "username = {$config['username']}\n";
    $configContent .= "password = {$config['password']}\n\n";
    $configContent .= "[local]\n";
    $configContent .= "servername = localhost\n";
    $configContent .= "port = 3306\n";
    $configContent .= "dbname = trainingdb\n";
    $configContent .= "username = root\n";
    $configContent .= "password = pw\n";
    
    $configFile = 'lequocanh/administrator/elements_LQA/mod/config.ini';
    
    if (file_put_contents($configFile, $configContent)) {
        echo "✅ Config file updated: $configFile\n";
    } else {
        echo "❌ Failed to update config file\n";
    }
}
?>