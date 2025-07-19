<?php
/**
 * Simple Database Connection Test
 * Test kết nối database đơn giản
 */

echo "=== SIMPLE DATABASE CONNECTION TEST ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Test configurations
$configs = [
    [
        'name' => 'Docker MySQL (trainingdb)',
        'host' => 'mysql',
        'port' => 3306,
        'dbname' => 'trainingdb',
        'username' => 'root',
        'password' => 'root'
    ],
    [
        'name' => 'Docker MySQL (sales_management)', 
        'host' => 'mysql',
        'port' => 3306,
        'dbname' => 'sales_management',
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
        'name' => 'Local MySQL (no password)',
        'host' => 'localhost',
        'port' => 3306,
        'dbname' => 'trainingdb',
        'username' => 'root',
        'password' => ''
    ]
];

$connected = false;

foreach ($configs as $i => $config) {
    echo ($i + 1) . ". Testing: {$config['name']}\n";
    echo "   Host: {$config['host']}:{$config['port']}\n";
    echo "   Database: {$config['dbname']}\n";
    echo "   User: {$config['username']}\n";
    
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Test query
        $stmt = $pdo->query("SELECT 1 as test, DATABASE() as current_db, VERSION() as version");
        $result = $stmt->fetch();
        
        if ($result['test'] == 1) {
            echo "   ✅ SUCCESS!\n";
            echo "   Current DB: {$result['current_db']}\n";
            echo "   MySQL Version: {$result['version']}\n";
            
            // Check tables
            echo "   \n   Checking tables:\n";
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($tables)) {
                echo "   ⚠️  No tables found\n";
            } else {
                echo "   📋 Found " . count($tables) . " tables:\n";
                foreach ($tables as $table) {
                    // Count records
                    try {
                        $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                        $count = $countStmt->fetch()['count'];
                        echo "   - $table ($count records)\n";
                    } catch (Exception $e) {
                        echo "   - $table (error counting)\n";
                    }
                }
            }
            
            $connected = true;
            echo "\n   🎯 THIS CONNECTION WORKS!\n";
            break;
        }
        
    } catch (Exception $e) {
        echo "   ❌ FAILED: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat("-", 60) . "\n\n";
}

if (!$connected) {
    echo "❌ NO SUCCESSFUL CONNECTION FOUND!\n\n";
    echo "🔧 TROUBLESHOOTING STEPS:\n\n";
    
    echo "1. Check if Docker containers are running:\n";
    echo "   docker-compose ps\n\n";
    
    echo "2. Start Docker containers:\n";
    echo "   docker-compose up -d\n\n";
    
    echo "3. Check MySQL container logs:\n";
    echo "   docker-compose logs mysql\n\n";
    
    echo "4. Connect to MySQL container directly:\n";
    echo "   docker-compose exec mysql mysql -u root -proot\n\n";
    
    echo "5. Create database manually:\n";
    echo "   docker-compose exec mysql mysql -u root -proot -e \"CREATE DATABASE trainingdb;\"\n\n";
    
    echo "6. If using local MySQL, check if service is running:\n";
    echo "   - Windows: Check Services or XAMPP\n";
    echo "   - Mac: brew services start mysql\n";
    echo "   - Linux: sudo systemctl start mysql\n\n";
    
} else {
    echo "✅ DATABASE CONNECTION SUCCESSFUL!\n\n";
    echo "🚀 NEXT STEPS:\n";
    echo "1. Run setup_database.php to create tables\n";
    echo "2. Test the application\n";
    echo "3. Try MoMo payment integration\n\n";
}

echo "=== TEST COMPLETE ===\n";
?>