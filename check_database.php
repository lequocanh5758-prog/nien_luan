<?php
/**
 * Database Connection Checker
 * Kiểm tra kết nối database qua Docker
 */

echo "=== DATABASE CONNECTION CHECKER ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Cấu hình database từ Docker
$dockerConfigs = [
    [
        'name' => 'Docker MySQL (sales_management)',
        'host' => 'mysql',
        'port' => 3306,
        'dbname' => 'sales_management',
        'username' => 'root',
        'password' => 'root'
    ],
    [
        'name' => 'Docker MySQL (trainingdb)',
        'host' => 'mysql', 
        'port' => 3306,
        'dbname' => 'trainingdb',
        'username' => 'root',
        'password' => 'root'
    ],
    [
        'name' => 'Local MySQL (trainingdb)',
        'host' => 'localhost',
        'port' => 3306,
        'dbname' => 'trainingdb', 
        'username' => 'root',
        'password' => 'pw'
    ],
    [
        'name' => 'Local MySQL (sales_management)',
        'host' => 'localhost',
        'port' => 3306,
        'dbname' => 'sales_management',
        'username' => 'root', 
        'password' => 'pw'
    ]
];

$connectedConfig = null;

foreach ($dockerConfigs as $config) {
    echo "Testing: {$config['name']}\n";
    echo "Host: {$config['host']}:{$config['port']}\n";
    echo "Database: {$config['dbname']}\n";
    echo "User: {$config['username']}\n";
    
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Test connection
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        
        if ($result['test'] == 1) {
            echo "✅ SUCCESS: Connected successfully!\n";
            $connectedConfig = $config;
            
            // Get database info
            $stmt = $pdo->query("SELECT DATABASE() as current_db");
            $dbInfo = $stmt->fetch();
            echo "Current Database: {$dbInfo['current_db']}\n";
            
            // List all databases
            $stmt = $pdo->query("SHOW DATABASES");
            $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "Available Databases: " . implode(', ', $databases) . "\n";
            
            // Check if required tables exist
            echo "\n--- Checking Tables ---\n";
            checkTables($pdo);
            
            break;
        }
        
    } catch (Exception $e) {
        echo "❌ FAILED: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

if (!$connectedConfig) {
    echo "❌ Could not connect to any database configuration\n";
    echo "\n=== TROUBLESHOOTING STEPS ===\n";
    echo "1. Check if Docker containers are running:\n";
    echo "   docker-compose ps\n\n";
    echo "2. Start Docker containers:\n";
    echo "   docker-compose up -d\n\n";
    echo "3. Check MySQL container logs:\n";
    echo "   docker-compose logs mysql\n\n";
    echo "4. Connect to MySQL container:\n";
    echo "   docker-compose exec mysql mysql -u root -p\n\n";
} else {
    echo "\n=== RECOMMENDED CONFIGURATION ===\n";
    echo "Update config.ini with:\n";
    echo "[section]\n";
    echo "servername = {$connectedConfig['host']}\n";
    echo "port = {$connectedConfig['port']}\n";
    echo "dbname = {$connectedConfig['dbname']}\n";
    echo "username = {$connectedConfig['username']}\n";
    echo "password = {$connectedConfig['password']}\n";
}

function checkTables($pdo) {
    $requiredTables = [
        'hanghoa' => 'Sản phẩm',
        'dongia' => 'Đơn giá', 
        'don_hang' => 'Đơn hàng',
        'chi_tiet_don_hang' => 'Chi tiết đơn hàng',
        'users' => 'Người dùng',
        'tonkho' => 'Tồn kho'
    ];
    
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "Existing tables: " . count($existingTables) . "\n";
        
        foreach ($requiredTables as $table => $description) {
            if (in_array($table, $existingTables)) {
                // Get row count
                $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                $count = $countStmt->fetch()['count'];
                echo "✅ $table ($description): $count records\n";
                
                // Show table structure for important tables
                if (in_array($table, ['dongia', 'don_hang', 'hanghoa'])) {
                    echo "   Structure:\n";
                    $descStmt = $pdo->query("DESCRIBE `$table`");
                    $columns = $descStmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($columns as $col) {
                        echo "   - {$col['Field']}: {$col['Type']}\n";
                    }
                }
            } else {
                echo "❌ $table ($description): Missing\n";
            }
        }
        
        // Check for sample data
        echo "\n--- Sample Data Check ---\n";
        if (in_array('hanghoa', $existingTables)) {
            $stmt = $pdo->query("SELECT idhanghoa, tenhanghoa, giathamkhao FROM hanghoa LIMIT 3");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($products)) {
                echo "Sample products:\n";
                foreach ($products as $product) {
                    echo "- ID: {$product['idhanghoa']}, Name: {$product['tenhanghoa']}, Price: " . number_format($product['giathamkhao']) . "\n";
                }
            } else {
                echo "⚠️ No sample products found\n";
            }
        }
        
    } catch (Exception $e) {
        echo "Error checking tables: " . $e->getMessage() . "\n";
    }
}

echo "\n=== CHECK COMPLETE ===\n";
?>