<?php
/**
 * Quick Debug - Test thêm đơn giá trực tiếp
 */

// Bắt đầu output buffering để tránh headers already sent
ob_start();

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../mod/database.php';

SessionManager::start();

if (!isset($_SESSION['ADMIN'])) {
    die('Unauthorized');
}

echo "<h2>🧪 Quick Debug - Thêm Đơn Giá</h2>";

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Lấy sản phẩm đầu tiên
    $sql = "SELECT idhanghoa, tenhanghoa FROM hanghoa LIMIT 1";
    $stmt = $db->query($sql);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        die("❌ Không có sản phẩm nào");
    }
    
    echo "<p>✅ Sản phẩm test: {$product['tenhanghoa']} (ID: {$product['idhanghoa']})</p>";
    
    // 2. Test insert trực tiếp
    $testData = [
        $product['idhanghoa'],  // idHangHoa
        120000,                 // giaBan
        date('Y-m-d'),         // ngayApDung
        date('Y-m-d', strtotime('+1 year')), // ngayKetThuc
        'Test debug',          // dieuKien
        'Debug script test',   // ghiChu
        1                      // apDung
    ];
    
    $sql = "INSERT INTO dongia (idHangHoa, giaBan, ngayApDung, ngayKetThuc, dieuKien, ghiChu, apDung) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    echo "<h3>SQL & Data:</h3>";
    echo "<p><strong>SQL:</strong> $sql</p>";
    echo "<p><strong>Data:</strong> " . implode(', ', $testData) . "</p>";
    
    $stmt = $db->prepare($sql);
    $result = $stmt->execute($testData);
    
    if ($result) {
        $insertId = $db->lastInsertId();
        echo "<p style='color: green;'>✅ <strong>THÀNH CÔNG!</strong> Insert ID: $insertId</p>";
        
        // Kiểm tra dữ liệu
        $checkSql = "SELECT * FROM dongia WHERE idDonGia = ?";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([$insertId]);
        $data = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>Dữ liệu đã insert:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        foreach ($data as $key => $value) {
            echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
        }
        echo "</table>";
        
        // Xóa test data
        $deleteSql = "DELETE FROM dongia WHERE idDonGia = ?";
        $deleteStmt = $db->prepare($deleteSql);
        $deleteStmt->execute([$insertId]);
        echo "<p style='color: orange;'>🗑️ Đã xóa dữ liệu test</p>";
        
    } else {
        echo "<p style='color: red;'>❌ <strong>THẤT BẠI!</strong></p>";
        $errorInfo = $stmt->errorInfo();
        echo "<h3>Chi tiết lỗi:</h3>";
        echo "<ul>";
        echo "<li><strong>SQLSTATE:</strong> {$errorInfo[0]}</li>";
        echo "<li><strong>Driver Code:</strong> {$errorInfo[1]}</li>";
        echo "<li><strong>Message:</strong> {$errorInfo[2]}</li>";
        echo "</ul>";
    }
    
    // 3. Test với Dongia class
    echo "<hr><h3>Test với Dongia Class:</h3>";
    
    require_once __DIR__ . '/../mod/dongiaCls.php';
    
    $dg = new Dongia();
    echo "<p>✅ Dongia class loaded</p>";
    
    $result2 = $dg->DongiaAdd(
        $product['idhanghoa'],
        130000,
        date('Y-m-d'),
        date('Y-m-d', strtotime('+1 year')),
        'Test class',
        'Test với class'
    );
    
    if ($result2) {
        echo "<p style='color: green;'>✅ <strong>Class method THÀNH CÔNG!</strong> ID: $result2</p>";
        
        // Xóa test data
        $dg->DongiaDelete($result2);
        echo "<p style='color: orange;'>🗑️ Đã xóa dữ liệu test class</p>";
    } else {
        echo "<p style='color: red;'>❌ <strong>Class method THẤT BẠI!</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>EXCEPTION:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='../../../index.php?req=dongiaview'>← Quay lại</a></p>";

// Kết thúc output buffering
ob_end_flush();
?>