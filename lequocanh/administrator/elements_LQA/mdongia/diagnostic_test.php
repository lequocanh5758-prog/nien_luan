<?php
/**
 * Diagnostic Test Script for Price Creation Issue
 * This script will help identify the root cause of price creation failure
 */

// Prevent any output before headers
ob_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Price Creation Diagnostic Test</h1>";
echo "<p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

$diagnosticResults = [];

// Test 1: Check if required files exist
echo "<h2>1️⃣ File Existence Check</h2>";
$requiredFiles = [
    '../mod/database.php',
    '../mod/dongiaCls.php',
    '../mod/sessionManager.php'
];

foreach ($requiredFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        echo "<p style='color: green;'>✅ {$file} - EXISTS</p>";
        $diagnosticResults['files'][$file] = 'EXISTS';
    } else {
        echo "<p style='color: red;'>❌ {$file} - MISSING</p>";
        $diagnosticResults['files'][$file] = 'MISSING';
    }
}

// Test 2: Database Connection
echo "<h2>2️⃣ Database Connection Test</h2>";
try {
    require_once __DIR__ . '/../mod/database.php';
    $db = Database::getInstance()->getConnection();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    $diagnosticResults['database']['connection'] = 'SUCCESS';
    
    // Test basic query
    $stmt = $db->query("SELECT 1 as test");
    $result = $stmt->fetch();
    if ($result['test'] == 1) {
        echo "<p style='color: green;'>✅ Database query test successful</p>";
        $diagnosticResults['database']['query'] = 'SUCCESS';
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    $diagnosticResults['database']['connection'] = 'FAILED: ' . $e->getMessage();
}

// Test 3: Check table structure
if (isset($db)) {
    echo "<h2>3️⃣ Table Structure Check</h2>";
    
    // Check dongia table
    try {
        $stmt = $db->query("DESCRIBE dongia");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ dongia table exists</p>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #007bff; color: white;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td><strong>{$col['Field']}</strong></td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        $diagnosticResults['tables']['dongia'] = 'EXISTS';
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ dongia table check failed: " . $e->getMessage() . "</p>";
        $diagnosticResults['tables']['dongia'] = 'FAILED: ' . $e->getMessage();
    }
    
    // Check hanghoa table
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM hanghoa");
        $result = $stmt->fetch();
        echo "<p style='color: green;'>✅ hanghoa table exists with {$result['count']} products</p>";
        $diagnosticResults['tables']['hanghoa'] = 'EXISTS with ' . $result['count'] . ' products';
        
        if ($result['count'] > 0) {
            // Get sample products
            $stmt = $db->query("SELECT idhanghoa, tenhanghoa, giathamkhao FROM hanghoa LIMIT 3");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<h4>Sample Products:</h4>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr style='background: #28a745; color: white;'><th>ID</th><th>Name</th><th>Reference Price</th></tr>";
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
        echo "<p style='color: red;'>❌ hanghoa table check failed: " . $e->getMessage() . "</p>";
        $diagnosticResults['tables']['hanghoa'] = 'FAILED: ' . $e->getMessage();
    }
}

// Test 4: Direct INSERT test
if (isset($db) && isset($products) && !empty($products)) {
    echo "<h2>4️⃣ Direct INSERT Test</h2>";
    $testProduct = $products[0];
    
    $testData = [
        $testProduct['idhanghoa'],  // idHangHoa
        150000,                     // giaBan
        date('Y-m-d'),             // ngayApDung
        date('Y-m-d', strtotime('+1 year')), // ngayKetThuc
        'Diagnostic test',         // dieuKien
        'Test from diagnostic script', // ghiChu
        1                          // apDung
    ];
    
    echo "<p><strong>Test Product:</strong> {$testProduct['tenhanghoa']} (ID: {$testProduct['idhanghoa']})</p>";
    echo "<p><strong>Test Data:</strong> " . implode(', ', $testData) . "</p>";
    
    $sql = "INSERT INTO dongia (idHangHoa, giaBan, ngayApDung, ngayKetThuc, dieuKien, ghiChu, apDung) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    try {
        $stmt = $db->prepare($sql);
        $result = $stmt->execute($testData);
        
        if ($result) {
            $insertId = $db->lastInsertId();
            echo "<p style='color: green; font-size: 18px;'><strong>✅ DIRECT INSERT SUCCESSFUL!</strong></p>";
            echo "<p><strong>Insert ID:</strong> $insertId</p>";
            $diagnosticResults['direct_insert'] = 'SUCCESS - ID: ' . $insertId;
            
            // Verify inserted data
            $checkStmt = $db->prepare("SELECT * FROM dongia WHERE idDonGia = ?");
            $checkStmt->execute([$insertId]);
            $insertedData = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<h4>Inserted Data Verification:</h4>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            foreach ($insertedData as $key => $value) {
                echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
            }
            echo "</table>";
            
            // Clean up test data
            $deleteStmt = $db->prepare("DELETE FROM dongia WHERE idDonGia = ?");
            $deleteStmt->execute([$insertId]);
            echo "<p style='color: orange;'>🗑️ Test data cleaned up</p>";
            
        } else {
            echo "<p style='color: red; font-size: 18px;'><strong>❌ DIRECT INSERT FAILED!</strong></p>";
            $errorInfo = $stmt->errorInfo();
            echo "<p style='color: red;'><strong>Error Details:</strong></p>";
            echo "<ul style='color: red;'>";
            echo "<li><strong>SQLSTATE:</strong> {$errorInfo[0]}</li>";
            echo "<li><strong>Driver Code:</strong> {$errorInfo[1]}</li>";
            echo "<li><strong>Message:</strong> {$errorInfo[2]}</li>";
            echo "</ul>";
            $diagnosticResults['direct_insert'] = 'FAILED: ' . $errorInfo[2];
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red; font-size: 18px;'><strong>❌ DIRECT INSERT EXCEPTION!</strong></p>";
        echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
        $diagnosticResults['direct_insert'] = 'EXCEPTION: ' . $e->getMessage();
    }
}

// Test 5: Dongia Class Test
if (isset($db) && isset($products) && !empty($products)) {
    echo "<h2>5️⃣ Dongia Class Test</h2>";
    
    try {
        require_once __DIR__ . '/../mod/dongiaCls.php';
        $dg = new Dongia();
        echo "<p style='color: green;'>✅ Dongia class loaded successfully</p>";
        
        $testProduct = $products[0];
        echo "<p><strong>Testing DongiaAdd method...</strong></p>";
        
        $result = $dg->DongiaAdd(
            $testProduct['idhanghoa'],
            160000,
            date('Y-m-d'),
            date('Y-m-d', strtotime('+1 year')),
            'Class method test',
            'Test with Dongia class'
        );
        
        if ($result) {
            echo "<p style='color: green; font-size: 18px;'><strong>✅ DONGIA CLASS METHOD SUCCESSFUL!</strong></p>";
            echo "<p><strong>Returned ID:</strong> $result</p>";
            $diagnosticResults['dongia_class'] = 'SUCCESS - ID: ' . $result;
            
            // Clean up test data
            $dg->DongiaDelete($result);
            echo "<p style='color: orange;'>🗑️ Test data cleaned up</p>";
        } else {
            echo "<p style='color: red; font-size: 18px;'><strong>❌ DONGIA CLASS METHOD FAILED!</strong></p>";
            echo "<p style='color: red;'>DongiaAdd() method returned FALSE</p>";
            $diagnosticResults['dongia_class'] = 'FAILED - Method returned FALSE';
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'><strong>❌ Exception loading Dongia class:</strong></p>";
        echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
        $diagnosticResults['dongia_class'] = 'EXCEPTION: ' . $e->getMessage();
    }
}

// Test 6: Form Submission Simulation
echo "<h2>6️⃣ Form Submission Simulation</h2>";
if (isset($products) && !empty($products)) {
    $testProduct = $products[0];
    
    // Simulate POST data
    $_POST = [
        'idhanghoa' => $testProduct['idhanghoa'],
        'giaban' => '170000',
        'ngayapdung' => date('Y-m-d'),
        'ngayketthuc' => date('Y-m-d', strtotime('+1 year')),
        'dieukien' => 'Form simulation test',
        'ghichu' => 'Simulated form submission'
    ];
    
    echo "<p><strong>Simulated POST data:</strong></p>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    // Test validation logic from dongiaAct.php
    $idHangHoa = isset($_POST['idhanghoa']) ? trim($_POST['idhanghoa']) : '';
    $giaBan = isset($_POST['giaban']) ? trim($_POST['giaban']) : '';
    $ngayApDung = isset($_POST['ngayapdung']) ? trim($_POST['ngayapdung']) : '';
    $ngayKetThuc = isset($_POST['ngayketthuc']) ? trim($_POST['ngayketthuc']) : '';
    
    echo "<p><strong>Validation Results:</strong></p>";
    
    // Check required fields
    if (empty($idHangHoa) || empty($giaBan) || empty($ngayApDung) || empty($ngayKetThuc)) {
        echo "<p style='color: red;'>❌ Validation failed - missing required fields</p>";
        $diagnosticResults['form_validation'] = 'FAILED - Missing required fields';
    } else {
        echo "<p style='color: green;'>✅ Required fields validation passed</p>";
        
        // Check price validation
        if (!is_numeric($giaBan) || floatval($giaBan) <= 0) {
            echo "<p style='color: red;'>❌ Price validation failed</p>";
            $diagnosticResults['form_validation'] = 'FAILED - Invalid price';
        } else {
            echo "<p style='color: green;'>✅ Price validation passed</p>";
            
            // Check date validation
            if (!DateTime::createFromFormat('Y-m-d', $ngayApDung) || !DateTime::createFromFormat('Y-m-d', $ngayKetThuc)) {
                echo "<p style='color: red;'>❌ Date format validation failed</p>";
                $diagnosticResults['form_validation'] = 'FAILED - Invalid date format';
            } else {
                echo "<p style='color: green;'>✅ Date format validation passed</p>";
                
                // Check date range
                if (strtotime($ngayApDung) >= strtotime($ngayKetThuc)) {
                    echo "<p style='color: red;'>❌ Date range validation failed</p>";
                    $diagnosticResults['form_validation'] = 'FAILED - Invalid date range';
                } else {
                    echo "<p style='color: green;'>✅ All form validation passed</p>";
                    $diagnosticResults['form_validation'] = 'SUCCESS - All validations passed';
                }
            }
        }
    }
}

// Summary
echo "<hr>";
echo "<h2>🎯 Diagnostic Summary</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";

foreach ($diagnosticResults as $category => $results) {
    echo "<h4>" . ucfirst(str_replace('_', ' ', $category)) . ":</h4>";
    if (is_array($results)) {
        foreach ($results as $test => $result) {
            $color = (strpos($result, 'SUCCESS') !== false || strpos($result, 'EXISTS') !== false) ? 'green' : 'red';
            echo "<p style='color: $color;'>• $test: $result</p>";
        }
    } else {
        $color = (strpos($results, 'SUCCESS') !== false) ? 'green' : 'red';
        echo "<p style='color: $color;'>• $results</p>";
    }
}

echo "</div>";

// Recommendations
echo "<h2>💡 Recommendations</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border-left: 4px solid #007bff;'>";

if (isset($diagnosticResults['direct_insert']) && strpos($diagnosticResults['direct_insert'], 'SUCCESS') !== false) {
    if (isset($diagnosticResults['dongia_class']) && strpos($diagnosticResults['dongia_class'], 'SUCCESS') !== false) {
        echo "<p style='color: green;'><strong>✅ GOOD NEWS:</strong> Both direct INSERT and Dongia class are working correctly!</p>";
        echo "<p>The issue might be in:</p>";
        echo "<ul>";
        echo "<li>Form submission handling (dongiaAct.php)</li>";
        echo "<li>Session management</li>";
        echo "<li>AJAX request processing</li>";
        echo "<li>User interface JavaScript</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'><strong>⚠️ PARTIAL ISSUE:</strong> Direct INSERT works but Dongia class fails</p>";
        echo "<p>Check the Dongia class implementation for bugs</p>";
    }
} else {
    echo "<p style='color: red;'><strong>❌ CRITICAL ISSUE:</strong> Database INSERT is failing</p>";
    echo "<p>Check:</p>";
    echo "<ul>";
    echo "<li>Database table structure and constraints</li>";
    echo "<li>Database permissions</li>";
    echo "<li>Data type compatibility</li>";
    echo "<li>Foreign key constraints</li>";
    echo "</ul>";
}

echo "</div>";

echo "<p><a href='../../../index.php?req=dongiaview' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>← Back to Price Management</a></p>";

// Clean up
unset($_POST);
ob_end_flush();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h1, h2, h3, h4 { color: #333; }
table { margin: 10px 0; }
th, td { padding: 8px 12px; text-align: left; }
pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>