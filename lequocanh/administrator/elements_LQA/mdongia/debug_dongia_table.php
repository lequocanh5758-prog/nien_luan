<?php
/**
 * Debug script để kiểm tra cấu trúc bảng dongia
 */

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../mod/database.php';

// Start session safely
SessionManager::start();

// Check admin access
if (!isset($_SESSION['ADMIN'])) {
    die('Unauthorized access');
}

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>🔍 Debug Bảng Đơn Giá</h2>";
    
    // 1. Kiểm tra cấu trúc bảng dongia
    echo "<h3>1. Cấu trúc bảng 'dongia':</h3>";
    $sql = "DESCRIBE dongia";
    $stmt = $db->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Kiểm tra dữ liệu mẫu
    echo "<h3>2. Dữ liệu hiện tại (5 records đầu):</h3>";
    $sql = "SELECT * FROM dongia ORDER BY idDonGia DESC LIMIT 5";
    $stmt = $db->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($data)) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr>";
        foreach (array_keys($data[0]) as $header) {
            echo "<th>$header</th>";
        }
        echo "</tr>";
        
        foreach ($data as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Không có dữ liệu trong bảng dongia</p>";
    }
    
    // 3. Test insert đơn giản
    echo "<h3>3. Test Insert:</h3>";
    
    // Lấy một sản phẩm để test
    $sql = "SELECT idhanghoa, tenhanghoa FROM hanghoa LIMIT 1";
    $stmt = $db->query($sql);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo "<p>Sản phẩm test: {$product['tenhanghoa']} (ID: {$product['idhanghoa']})</p>";
        
        // Thử insert một record test
        $testSql = "INSERT INTO dongia (idHangHoa, giaBan, ngayApDung, ngayKetThuc, dieuKien, ghiChu, apDung) 
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $testData = [
            $product['idhanghoa'],
            100000,
            date('Y-m-d'),
            date('Y-m-d', strtotime('+1 year')),
            'Test',
            'Debug test',
            0
        ];
        
        echo "<p>SQL: $testSql</p>";
        echo "<p>Data: " . print_r($testData, true) . "</p>";
        
        try {
            $stmt = $db->prepare($testSql);
            $result = $stmt->execute($testData);
            
            if ($result) {
                $insertId = $db->lastInsertId();
                echo "<p style='color: green;'>✅ Insert thành công! ID: $insertId</p>";
                
                // Xóa record test
                $deleteSql = "DELETE FROM dongia WHERE idDonGia = ?";
                $deleteStmt = $db->prepare($deleteSql);
                $deleteStmt->execute([$insertId]);
                echo "<p>🗑️ Đã xóa record test</p>";
            } else {
                echo "<p style='color: red;'>❌ Insert thất bại</p>";
                $errorInfo = $stmt->errorInfo();
                echo "<p>Error: " . print_r($errorInfo, true) . "</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Không tìm thấy sản phẩm để test</p>";
    }
    
    // 4. Kiểm tra bảng hanghoa
    echo "<h3>4. Kiểm tra bảng hanghoa:</h3>";
    $sql = "SELECT COUNT(*) as count FROM hanghoa";
    $stmt = $db->query($sql);
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Số sản phẩm trong bảng hanghoa: {$count['count']}</p>";
    
    if ($count['count'] > 0) {
        $sql = "SELECT idhanghoa, tenhanghoa, giathamkhao FROM hanghoa LIMIT 3";
        $stmt = $db->query($sql);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Tên</th><th>Giá tham khảo</th></tr>";
        foreach ($products as $p) {
            echo "<tr>";
            echo "<td>{$p['idhanghoa']}</td>";
            echo "<td>" . htmlspecialchars($p['tenhanghoa']) . "</td>";
            echo "<td>" . number_format($p['giathamkhao']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Lỗi: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}

echo "<hr>";
echo "<p><a href='../../../index.php?req=dongiaview'>← Quay lại quản lý đơn giá</a></p>";
?>