<?php
// Debug MoMo Payment - Simple Test
session_start();

// Bật hiển thị lỗi
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>🔍 MoMo Debug Test</h2>";

// Test 1: Kiểm tra session
echo "<h3>1. Session Test</h3>";
if (session_status() == PHP_SESSION_ACTIVE) {
    echo "✅ Session hoạt động<br>";
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        echo "✅ Cart có dữ liệu: " . count($_SESSION['cart']) . " items<br>";
    } else {
        echo "⚠️ Cart trống<br>";
    }
} else {
    echo "❌ Session không hoạt động<br>";
}

// Test 2: Kiểm tra file paths
echo "<h3>2. File Path Test</h3>";
$files = [
    '../../../payment/MoMoPayment.php',
    '../../../config/payment_config.php',
    '../../../payment/momo_process.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file tồn tại<br>";
    } else {
        echo "❌ $file KHÔNG tồn tại<br>";
    }
}

// Test 3: Test JavaScript
echo "<h3>3. JavaScript Test</h3>";
?>
<button onclick="testJS()" style="padding: 10px; background: #007bff; color: white; border: none; cursor: pointer;">
    Test JavaScript
</button>
<div id="result" style="margin-top: 10px; padding: 10px; background: #f8f9fa; border: 1px solid #ddd;"></div>

<script>
function testJS() {
    alert('🚀 JavaScript hoạt động!');
    document.getElementById('result').innerHTML = '✅ JavaScript OK! Thời gian: ' + new Date().toLocaleString();
    
    // Test fetch API
    console.log('Testing fetch...');
    fetch('debug_momo.php?test=ajax', {
        method: 'GET'
    })
    .then(response => response.text())
    .then(data => {
        console.log('✅ AJAX thành công:', data);
        document.getElementById('result').innerHTML += '<br>✅ AJAX Response OK';
    })
    .catch(error => {
        console.error('❌ AJAX lỗi:', error);
        document.getElementById('result').innerHTML += '<br>❌ AJAX Error: ' + error;
    });
}
</script>

<?php
// Test 4: AJAX response
if (isset($_GET['test']) && $_GET['test'] === 'ajax') {
    echo "AJAX_OK_" . time();
    exit;
}

// Test 5: Thông tin server
echo "<h3>4. Server Info</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Current Script: " . $_SERVER['SCRIPT_NAME'] . "<br>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; }
h3 { color: #666; margin-top: 20px; }
</style>
