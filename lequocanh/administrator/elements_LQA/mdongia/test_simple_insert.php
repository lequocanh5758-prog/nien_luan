<?php
/**
 * Test đơn giản để thêm đơn giá
 */

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../mod/database.php';

// Start session safely
SessionManager::start();

// Check admin access
if (!isset($_SESSION['ADMIN'])) {
    die('Unauthorized access');
}

echo "<h2>🧪 Test Thêm Đơn Giá Đơn Giản</h2>";

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Lấy một sản phẩm để test
    echo "<h3>1. Lấy sản phẩm để test:</h3>";
    $sql = "SELECT idhanghoa, tenhanghoa, giathamkhao FROM hanghoa LIMIT 1";
    $stmt = $db->query($sql);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        die("❌ Không tìm thấy sản phẩm nào trong bảng hanghoa");
    }
    
    echo "<p>✅ Sản phẩm: {$product['tenhanghoa']} (ID: {$product['idhanghoa']}, Giá hiện tại: " . number_format($product['giathamkhao']) . ")</p>";
    
    // 2. Chuẩn bị dữ liệu test
    $testData = [
        'idHangHoa' => $product['idhanghoa'],
        'giaBan' => 150000,
        'ngayApDung' => date('Y-m-d'),
        'ngayKetThuc' => date('Y-m-d', strtotime('+1 year')),
        'dieuKien' => 'Test condition',
        'ghiChu' => 'Test từ debug script',
        'apDung' => 1
    ];
    
    echo "<h3>2. Dữ liệu test:</h3>";
    echo "<pre>" . print_r($testData, true) . "</pre>";
    
    // 3. Thực hiện insert
    echo "<h3>3. Thực hiện insert:</h3>";
    
    $sql = "INSERT INTO dongia (idHangHoa, giaBan, ngayApDung, ngayKetThuc, dieuKien, ghiChu, apDung) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    echo "<p><strong>SQL:</strong> $sql</p>";
    
    $stmt = $db->prepare($sql);
    $params = array_values($testData);
    
    echo "<p><strong>Parameters:</strong></p>";
    echo "<pre>" . print_r($params, true) . "</pre>";
    
    $result = $stmt->execute($params);
    
    if ($result) {
        $insertId = $db->lastInsertId();
        echo "<p style='color: green;'>✅ <strong>THÀNH CÔNG!</strong> Đã thêm đơn giá với ID: $insertId</p>";
        
        // 4. Kiểm tra dữ liệu vừa insert
        echo "<h3>4. Kiểm tra dữ liệu vừa insert:</h3>";
        $checkSql = "SELECT * FROM dongia WHERE idDonGia = ?";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([$insertId]);
        $insertedData = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($insertedData) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            foreach ($insertedData as $key => $value) {
                echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
            }
            echo "</table>";
        }
        
        // 5. Xóa dữ liệu test
        echo "<h3>5. Dọn dẹp:</h3>";
        $deleteSql = "DELETE FROM dongia WHERE idDonGia = ?";
        $deleteStmt = $db->prepare($deleteSql);
        $deleteResult = $deleteStmt->execute([$insertId]);
        
        if ($deleteResult) {
            echo "<p style='color: orange;'>🗑️ Đã xóa dữ liệu test</p>";
        } else {
            echo "<p style='color: red;'>❌ Không thể xóa dữ liệu test</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ <strong>THẤT BẠI!</strong></p>";
        $errorInfo = $stmt->errorInfo();
        echo "<p><strong>Error Info:</strong></p>";
        echo "<pre>" . print_r($errorInfo, true) . "</pre>";
        
        // Kiểm tra chi tiết lỗi
        echo "<h3>Chi tiết lỗi:</h3>";
        echo "<p>SQLSTATE: {$errorInfo[0]}</p>";
        echo "<p>Driver Error Code: {$errorInfo[1]}</p>";
        echo "<p>Driver Error Message: {$errorInfo[2]}</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>EXCEPTION:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Stack Trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='../../../index.php?req=dongiaview'>← Quay lại quản lý đơn giá</a></p>";
echo "<p><a href='debug_dongia_table.php'>🔍 Xem debug table structure</a></p>";
?>