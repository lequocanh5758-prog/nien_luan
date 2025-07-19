<?php
/**
 * Debug Script - Tìm lỗi thêm đơn giá
 */

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../mod/database.php';

SessionManager::start();

if (!isset($_SESSION['ADMIN'])) {
    die('❌ Unauthorized access');
}

echo "<h2>🔍 Debug Thêm Đơn Giá - Chi Tiết</h2>";

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Kiểm tra cấu trúc bảng dongia
    echo "<h3>1️⃣ Cấu trúc bảng 'dongia':</h3>";
    $sql = "DESCRIBE dongia";
    $stmt = $db->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #007bff; color: white;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Kiểm tra sản phẩm có sẵn
    echo "<h3>2️⃣ Kiểm tra sản phẩm:</h3>";
    $sql = "SELECT idhanghoa, tenhanghoa, giathamkhao FROM hanghoa LIMIT 3";
    $stmt = $db->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($products)) {
        echo "<p style='color: red;'>❌ <strong>KHÔNG CÓ SẢN PHẨM NÀO!</strong></p>";
        echo "<p>Cần thêm sản phẩm vào bảng 'hanghoa' trước khi tạo đơn giá.</p>";
    } else {
        echo "<p style='color: green;'>✅ Có " . count($products) . " sản phẩm</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr style='background: #28a745; color: white;'><th>ID</th><th>Tên sản phẩm</th><th>Giá tham khảo</th></tr>";
        foreach ($products as $p) {
            echo "<tr>";
            echo "<td>{$p['idhanghoa']}</td>";
            echo "<td>" . htmlspecialchars($p['tenhanghoa']) . "</td>";
            echo "<td>" . number_format($p['giathamkhao']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. Test INSERT trực tiếp
    if (!empty($products)) {
        $testProduct = $products[0];
        
        echo "<h3>3️⃣ Test INSERT trực tiếp:</h3>";
        echo "<p><strong>Sản phẩm test:</strong> {$testProduct['tenhanghoa']} (ID: {$testProduct['idhanghoa']})</p>";
        
        // Dữ liệu test
        $testData = [
            $testProduct['idhanghoa'],  // idHangHoa
            150000,                     // giaBan
            date('Y-m-d'),             // ngayApDung
            date('Y-m-d', strtotime('+1 year')), // ngayKetThuc
            'Debug test',              // dieuKien
            'Test từ debug script',    // ghiChu
            1                          // apDung
        ];
        
        echo "<h4>📝 Dữ liệu test:</h4>";
        echo "<ul>";
        echo "<li><strong>idHangHoa:</strong> {$testData[0]} (" . gettype($testData[0]) . ")</li>";
        echo "<li><strong>giaBan:</strong> {$testData[1]} (" . gettype($testData[1]) . ")</li>";
        echo "<li><strong>ngayApDung:</strong> {$testData[2]} (" . gettype($testData[2]) . ")</li>";
        echo "<li><strong>ngayKetThuc:</strong> {$testData[3]} (" . gettype($testData[3]) . ")</li>";
        echo "<li><strong>dieuKien:</strong> '{$testData[4]}' (" . gettype($testData[4]) . ")</li>";
        echo "<li><strong>ghiChu:</strong> '{$testData[5]}' (" . gettype($testData[5]) . ")</li>";
        echo "<li><strong>apDung:</strong> {$testData[6]} (" . gettype($testData[6]) . ")</li>";
        echo "</ul>";
        
        // Thực hiện INSERT
        $sql = "INSERT INTO dongia (idHangHoa, giaBan, ngayApDung, ngayKetThuc, dieuKien, ghiChu, apDung) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        echo "<h4>🔧 SQL Statement:</h4>";
        echo "<code style='background: #f8f9fa; padding: 10px; display: block;'>$sql</code>";
        
        try {
            $stmt = $db->prepare($sql);
            echo "<p style='color: green;'>✅ SQL prepare thành công</p>";
            
            $result = $stmt->execute($testData);
            
            if ($result) {
                $insertId = $db->lastInsertId();
                echo "<p style='color: green; font-size: 18px;'><strong>✅ INSERT THÀNH CÔNG!</strong></p>";
                echo "<p><strong>ID mới:</strong> $insertId</p>";
                
                // Kiểm tra dữ liệu vừa insert
                $checkSql = "SELECT * FROM dongia WHERE idDonGia = ?";
                $checkStmt = $db->prepare($checkSql);
                $checkStmt->execute([$insertId]);
                $insertedData = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                echo "<h4>📊 Dữ liệu đã insert:</h4>";
                echo "<table border='1' style='border-collapse: collapse;'>";
                foreach ($insertedData as $key => $value) {
                    echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
                }
                echo "</table>";
                
                // Xóa dữ liệu test
                $deleteSql = "DELETE FROM dongia WHERE idDonGia = ?";
                $deleteStmt = $db->prepare($deleteSql);
                $deleteStmt->execute([$insertId]);
                echo "<p style='color: orange;'>🗑️ Đã xóa dữ liệu test</p>";
                
            } else {
                echo "<p style='color: red; font-size: 18px;'><strong>❌ INSERT THẤT BẠI!</strong></p>";
                $errorInfo = $stmt->errorInfo();
                echo "<h4>🚨 Chi tiết lỗi:</h4>";
                echo "<ul style='color: red;'>";
                echo "<li><strong>SQLSTATE:</strong> {$errorInfo[0]}</li>";
                echo "<li><strong>Driver Error Code:</strong> {$errorInfo[1]}</li>";
                echo "<li><strong>Driver Error Message:</strong> {$errorInfo[2]}</li>";
                echo "</ul>";
            }
            
        } catch (PDOException $e) {
            echo "<p style='color: red; font-size: 18px;'><strong>❌ PDO EXCEPTION!</strong></p>";
            echo "<p style='color: red;'><strong>Message:</strong> " . $e->getMessage() . "</p>";
            echo "<p style='color: red;'><strong>Code:</strong> " . $e->getCode() . "</p>";
        }
    }
    
    // 4. Test với Dongia class
    echo "<hr><h3>4️⃣ Test với Dongia Class:</h3>";
    
    require_once __DIR__ . '/../mod/dongiaCls.php';
    
    try {
        $dg = new Dongia();
        echo "<p style='color: green;'>✅ Dongia class loaded thành công</p>";
        
        if (!empty($products)) {
            $testProduct = $products[0];
            
            echo "<p><strong>Test DongiaAdd method...</strong></p>";
            
            $result = $dg->DongiaAdd(
                $testProduct['idhanghoa'],
                160000,
                date('Y-m-d'),
                date('Y-m-d', strtotime('+1 year')),
                'Test class method',
                'Test với Dongia class'
            );
            
            if ($result) {
                echo "<p style='color: green; font-size: 18px;'><strong>✅ DONGIA CLASS THÀNH CÔNG!</strong></p>";
                echo "<p><strong>Returned ID:</strong> $result</p>";
                
                // Xóa dữ liệu test
                $dg->DongiaDelete($result);
                echo "<p style='color: orange;'>🗑️ Đã xóa dữ liệu test class</p>";
            } else {
                echo "<p style='color: red; font-size: 18px;'><strong>❌ DONGIA CLASS THẤT BẠI!</strong></p>";
                echo "<p style='color: red;'>Method DongiaAdd() trả về FALSE</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'><strong>❌ Exception khi load Dongia class:</strong></p>";
        echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    }
    
    // 5. Kiểm tra error logs
    echo "<hr><h3>5️⃣ Kiểm tra Error Logs:</h3>";
    
    // Đọc error log gần đây
    $errorLogPaths = [
        '/var/log/apache2/error.log',
        '/var/log/php_errors.log',
        'C:\\xampp\\apache\\logs\\error.log',
        'C:\\xampp\\php\\logs\\php_error_log'
    ];
    
    $foundLog = false;
    foreach ($errorLogPaths as $logPath) {
        if (file_exists($logPath) && is_readable($logPath)) {
            $foundLog = true;
            echo "<p style='color: green;'>✅ Tìm thấy error log: $logPath</p>";
            
            // Đọc 20 dòng cuối
            $lines = file($logPath);
            $lastLines = array_slice($lines, -20);
            
            echo "<h4>📄 20 dòng cuối của error log:</h4>";
            echo "<pre style='background: #f8f9fa; padding: 10px; max-height: 300px; overflow-y: auto; border: 1px solid #ccc;'>";
            foreach ($lastLines as $line) {
                if (strpos($line, 'DongiaAdd') !== false || strpos($line, 'dongia') !== false) {
                    echo "<span style='background: yellow;'>" . htmlspecialchars($line) . "</span>";
                } else {
                    echo htmlspecialchars($line);
                }
            }
            echo "</pre>";
            break;
        }
    }
    
    if (!$foundLog) {
        echo "<p style='color: orange;'>⚠️ Không tìm thấy error log file</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ CRITICAL ERROR:</strong></p>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<pre style='color: red;'>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h3>🎯 Kết Luận & Khuyến Nghị:</h3>";
echo "<div style='background: #e7f3ff; padding: 15px; border-left: 4px solid #007bff;'>";
echo "<p><strong>Dựa trên kết quả debug trên:</strong></p>";
echo "<ol>";
echo "<li>Nếu <strong>INSERT trực tiếp THÀNH CÔNG</strong> nhưng <strong>Dongia class THẤT BẠI</strong> → Vấn đề ở logic trong class</li>";
echo "<li>Nếu <strong>INSERT trực tiếp THẤT BẠI</strong> → Vấn đề ở database (constraint, permission, data type)</li>";
echo "<li>Nếu <strong>không có sản phẩm</strong> → Cần thêm sản phẩm vào bảng hanghoa trước</li>";
echo "<li>Kiểm tra <strong>error logs</strong> để xem chi tiết lỗi</li>";
echo "</ol>";
echo "</div>";

echo "<p><a href='../../../index.php?req=dongiaview' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>← Quay lại quản lý đơn giá</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h2, h3, h4 { color: #333; }
table { margin: 10px 0; }
th, td { padding: 8px 12px; text-align: left; }
code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>