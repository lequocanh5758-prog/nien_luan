<?php
/**
 * Script kiểm tra và sửa lỗi đường dẫn trong các file PHP
 * Giúp phát hiện và sửa lỗi "Not Found" do đường dẫn không chính xác
 */

echo "<h1>🔍 Kiểm Tra Đường Dẫn File</h1>";

// Danh sách thư mục cần kiểm tra
$directories = [
    __DIR__,
    __DIR__ . '/administrator',
    __DIR__ . '/administrator/elements_LQA',
    __DIR__ . '/payment'
];

// Danh sách các file test cần kiểm tra đặc biệt
$testFiles = [
    __DIR__ . '/test_notifications.php',
    __DIR__ . '/test_bank_payment.php',
    __DIR__ . '/test_momo_callback.php',
    __DIR__ . '/fix_notifications_and_history.php'
];

// Các pattern cần kiểm tra
$patterns = [
    '/require(_once)?\s+[\'"]([^\/][^"\']*)[\'"]/' => 'Đường dẫn tương đối không bắt đầu bằng "/"',
    '/include(_once)?\s+[\'"]([^\/][^"\']*)[\'"]/' => 'Đường dẫn tương đối không bắt đầu bằng "/"',
    '/header\([\'"]Location:\s+\.\.\//' => 'Redirect sử dụng ../ có thể gây lỗi',
    '/\$_SERVER\[\'DOCUMENT_ROOT\'\]/' => 'Sử dụng DOCUMENT_ROOT có thể không chính xác'
];

// Các file đã kiểm tra
$checkedFiles = [];
$issueFiles = [];

// Hàm kiểm tra một file
function checkFile($filePath) {
    global $patterns, $issueFiles;
    
    if (!file_exists($filePath) || !is_file($filePath)) {
        return false;
    }
    
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    if ($ext !== 'php') {
        return false;
    }
    
    $content = file_get_contents($filePath);
    $issues = [];
    
    foreach ($patterns as $pattern => $description) {
        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                $issues[] = [
                    'line' => $line,
                    'match' => $match[0],
                    'description' => $description
                ];
            }
        }
    }
    
    if (!empty($issues)) {
        $issueFiles[$filePath] = $issues;
        return true;
    }
    
    return false;
}

// Hàm quét thư mục
function scanDirectory($dir) {
    global $checkedFiles;
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            scanDirectory($path);
        } elseif (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            checkFile($path);
            $checkedFiles[] = $path;
        }
    }
}

// Kiểm tra các thư mục
echo "<h2>1. Quét thư mục</h2>";
foreach ($directories as $directory) {
    if (is_dir($directory)) {
        echo "Đang quét thư mục: " . htmlspecialchars($directory) . "<br>";
        scanDirectory($directory);
    } else {
        echo "⚠️ Thư mục không tồn tại: " . htmlspecialchars($directory) . "<br>";
    }
}

// Kiểm tra các file test
echo "<h2>2. Kiểm tra file test</h2>";
foreach ($testFiles as $file) {
    if (file_exists($file)) {
        echo "Đang kiểm tra file: " . htmlspecialchars($file) . "<br>";
        checkFile($file);
    } else {
        echo "⚠️ File không tồn tại: " . htmlspecialchars($file) . "<br>";
    }
}

// Hiển thị kết quả
echo "<h2>3. Kết quả kiểm tra</h2>";
echo "<p>Đã kiểm tra " . count($checkedFiles) . " file PHP.</p>";

if (empty($issueFiles)) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
    echo "<h3>✅ Không phát hiện vấn đề</h3>";
    echo "<p>Tất cả các file đều sử dụng đường dẫn đúng.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h3>⚠️ Phát hiện " . count($issueFiles) . " file có vấn đề</h3>";
    echo "</div>";
    
    foreach ($issueFiles as $file => $issues) {
        $relativePath = str_replace(__DIR__, '', $file);
        
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>📄 " . htmlspecialchars($relativePath) . "</h4>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Dòng</th><th>Mã</th><th>Vấn đề</th><th>Đề xuất sửa</th></tr>";
        
        foreach ($issues as $issue) {
            echo "<tr>";
            echo "<td>" . $issue['line'] . "</td>";
            echo "<td><code>" . htmlspecialchars($issue['match']) . "</code></td>";
            echo "<td>" . htmlspecialchars($issue['description']) . "</td>";
            
            // Đề xuất sửa
            $suggestion = "";
            if (strpos($issue['match'], 'require') !== false || strpos($issue['match'], 'include') !== false) {
                $suggestion = "Sử dụng đường dẫn tuyệt đối:<br><code>\$basePath = __DIR__ . '/path/to/';<br>require_once \$basePath . 'file.php';</code>";
            } elseif (strpos($issue['match'], 'Location') !== false) {
                $suggestion = "Sử dụng đường dẫn tuyệt đối:<br><code>header('Location: /path/to/file.php');</code>";
            } elseif (strpos($issue['match'], 'DOCUMENT_ROOT') !== false) {
                $suggestion = "Sử dụng __DIR__ thay thế:<br><code>\$basePath = __DIR__ . '/path/to/';</code>";
            }
            
            echo "<td>" . $suggestion . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
    }
    
    // Hướng dẫn sửa lỗi
    echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>🛠️ Hướng dẫn sửa lỗi</h3>";
    echo "<ol>";
    echo "<li><strong>Sử dụng __DIR__</strong>: Thay thế đường dẫn tương đối bằng đường dẫn tuyệt đối sử dụng __DIR__</li>";
    echo "<li><strong>Kiểm tra file tồn tại</strong>: Thêm kiểm tra file_exists() trước khi include</li>";
    echo "<li><strong>Sử dụng biến đường dẫn</strong>: Định nghĩa biến đường dẫn ở đầu file</li>";
    echo "</ol>";
    
    echo "<pre>";
    echo "// Ví dụ sửa lỗi\n";
    echo "// Thay vì:\n";
    echo "require_once 'path/to/file.php';\n\n";
    echo "// Sử dụng:\n";
    echo "\$basePath = __DIR__ . '/path/to/';\n";
    echo "require_once \$basePath . 'file.php';\n";
    echo "</pre>";
    echo "</div>";
}

// Hiển thị hướng dẫn phòng tránh
echo "<div style='background: #e8f4fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h2>4. Hướng dẫn phòng tránh lỗi</h2>";

echo "<h3>Nguyên tắc sử dụng đường dẫn</h3>";
echo "<ol>";
echo "<li><strong>Luôn sử dụng đường dẫn tuyệt đối</strong> với __DIR__ thay vì đường dẫn tương đối</li>";
echo "<li><strong>Định nghĩa biến đường dẫn</strong> ở đầu file để dễ quản lý</li>";
echo "<li><strong>Kiểm tra file tồn tại</strong> trước khi include</li>";
echo "<li><strong>Sử dụng autoload</strong> thay vì require nhiều file</li>";
echo "</ol>";

echo "<h3>Ví dụ mẫu</h3>";
echo "<pre>";
echo "// Đầu file\n";
echo "\$basePath = __DIR__ . '/administrator/elements_LQA/mod/';\n\n";
echo "// Kiểm tra file tồn tại\n";
echo "if (file_exists(\$basePath . 'database.php')) {\n";
echo "    require_once \$basePath . 'database.php';\n";
echo "} else {\n";
echo "    die('Không tìm thấy file database.php');\n";
echo "}\n\n";
echo "// Sử dụng đường dẫn tuyệt đối cho redirect\n";
echo "header('Location: ' . \$_SERVER['HTTP_HOST'] . '/lequocanh/index.php');\n";
echo "</pre>";
echo "</div>";

// Hiển thị link đến USER_GUIDELINES.md
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h2>5. Tài liệu tham khảo</h2>";
echo "<p>Xem thêm hướng dẫn chi tiết tại: <a href='USER_GUIDELINES.md'>USER_GUIDELINES.md</a></p>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h1 { color: #2c5aa0; text-align: center; }
h2 { color: #333; margin-top: 20px; }
h3 { color: #555; }
h4 { margin: 10px 0; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
a { color: #007cba; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
