# 📘 Hướng Dẫn Sử Dụng Hệ Thống

## 🔍 Phòng Tránh Lỗi Phổ Biến

### 1️⃣ Lỗi Đường Dẫn File (Not Found)

#### Nguyên Nhân
- Đường dẫn tương đối không chính xác
- Thư mục làm việc khác với thư mục chứa file
- Thiếu các file cần thiết

#### Cách Phòng Tránh
- **Sử dụng đường dẫn tuyệt đối**:
  ```php
  $basePath = __DIR__ . '/đường/dẫn/đến/thư/mục/';
  require_once $basePath . 'tên_file.php';
  ```
- **Kiểm tra file tồn tại trước khi include**:
  ```php
  $filePath = __DIR__ . '/đường/dẫn/file.php';
  if (file_exists($filePath)) {
      require_once $filePath;
  } else {
      die("Không tìm thấy file: $filePath");
  }
  ```
- **Sử dụng autoload** thay vì require nhiều file

### 2️⃣ Lỗi Kết Nối Database

#### Nguyên Nhân
- Thông tin kết nối không chính xác
- Database chưa được tạo
- Bảng chưa được tạo

#### Cách Phòng Tránh
- **Kiểm tra kết nối trước khi sử dụng**:
  ```php
  try {
      $db = Database::getInstance();
      $conn = $db->getConnection();
      
      // Test kết nối
      $testQuery = $conn->query("SELECT 1");
      if (!$testQuery) {
          throw new Exception("Kết nối database không hoạt động");
      }
  } catch (Exception $e) {
      die("Lỗi kết nối: " . $e->getMessage());
  }
  ```
- **Kiểm tra bảng tồn tại**:
  ```php
  $checkTableSql = "SHOW TABLES LIKE 'tên_bảng'";
  $checkStmt = $conn->prepare($checkTableSql);
  $checkStmt->execute();
  if ($checkStmt->rowCount() == 0) {
      // Bảng chưa tồn tại, tạo bảng
  }
  ```

### 3️⃣ Lỗi Ngrok

#### Nguyên Nhân
- URL ngrok hết hạn (mặc định 2 giờ)
- Cấu hình webhook không đúng
- Firewall chặn kết nối

#### Cách Phòng Tránh
- **Sử dụng ngrok authtoken** để có session dài hơn:
  ```bash
  ngrok authtoken YOUR_AUTH_TOKEN
  ngrok http 80
  ```
- **Cập nhật URL webhook** mỗi khi khởi động lại ngrok:
  ```php
  // Lưu URL ngrok vào file cấu hình
  $ngrokUrl = "https://xxxx.ngrok-free.app";
  file_put_contents('config/ngrok_url.txt', $ngrokUrl);
  ```
- **Kiểm tra kết nối ngrok**:
  ```php
  $ngrokUrl = file_get_contents('config/ngrok_url.txt');
  $ch = curl_init($ngrokUrl);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  
  if ($httpCode != 200) {
      die("Ngrok không hoạt động. Vui lòng khởi động lại.");
  }
  ```

### 4️⃣ Lỗi Phân Quyền

#### Nguyên Nhân
- Session hết hạn
- Không có quyền truy cập
- Thiếu thông tin xác thực

#### Cách Phòng Tránh
- **Kiểm tra session trước khi xử lý**:
  ```php
  session_start();
  if (!isset($_SESSION['USER'])) {
      header('Location: userLogin.php');
      exit();
  }
  ```
- **Kiểm tra quyền truy cập**:
  ```php
  if (!$phanQuyen->checkAccess('module_name', $username)) {
      die("Bạn không có quyền truy cập trang này");
  }
  ```

## 🛠️ Quy Trình Phát Triển

### 1. Phát Triển Tính Năng Mới

#### Bước 1: Tạo Branch Mới
```bash
git checkout -b feature/ten-tinh-nang
```

#### Bước 2: Tạo File Test
- Tạo file test riêng cho tính năng
- Đặt trong thư mục `tests/`
- Sử dụng đường dẫn tuyệt đối

#### Bước 3: Phát Triển Tính Năng
- Tuân thủ cấu trúc MVC
- Tách biệt logic và giao diện
- Sử dụng prepared statements

#### Bước 4: Test Tính Năng
- Test trên môi trường local
- Test với nhiều dữ liệu khác nhau
- Kiểm tra lỗi và xử lý ngoại lệ

#### Bước 5: Commit và Push
```bash
git add .
git commit -m "Thêm tính năng XYZ"
git push origin feature/ten-tinh-nang
```

### 2. Triển Khai Lên Server

#### Bước 1: Backup Database
```bash
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

#### Bước 2: Cập Nhật Code
```bash
git pull origin main
```

#### Bước 3: Cập Nhật Database
- Chạy script migration
- Kiểm tra cấu trúc bảng

#### Bước 4: Kiểm Tra Hoạt Động
- Test các tính năng chính
- Kiểm tra log lỗi

## 📊 Monitoring và Debug

### 1. Kiểm Tra Log

#### PHP Error Log
```bash
tail -f /var/log/apache2/error.log
```

#### Custom Log
```php
error_log("Debug: " . json_encode($data));
```

### 2. Debug Database

#### Kiểm Tra Query
```php
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
} catch (PDOException $e) {
    error_log("SQL Error: " . $e->getMessage() . " | Query: " . $sql);
    throw $e;
}
```

#### Kiểm Tra Dữ Liệu
```php
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
error_log("Data: " . json_encode($data));
```

### 3. Debug Webhook

#### Ghi Log Request
```php
$logData = [
    'headers' => getallheaders(),
    'get' => $_GET,
    'post' => $_POST,
    'raw' => file_get_contents('php://input')
];
error_log("Webhook Data: " . json_encode($logData));
```

#### Test Webhook Locally
```bash
# Sử dụng curl để test webhook
curl -X POST http://localhost/webhook.php \
  -H "Content-Type: application/json" \
  -d '{"key":"value"}'
```

## 🔒 Bảo Mật

### 1. Xử Lý Input

#### Sanitize Input
```php
$input = filter_input(INPUT_POST, 'field', FILTER_SANITIZE_STRING);
```

#### Validate Input
```php
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Email không hợp lệ");
}
```

### 2. Bảo Vệ Database

#### Sử Dụng Prepared Statements
```php
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
```

#### Kiểm Tra Quyền Truy Cập
```php
if ($user['role'] !== 'admin') {
    die("Không có quyền truy cập");
}
```

### 3. Bảo Vệ Session

#### Regenerate Session ID
```php
session_start();
if (!isset($_SESSION['last_regenerated']) || 
    time() - $_SESSION['last_regenerated'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regenerated'] = time();
}
```

#### Timeout Session
```php
if (isset($_SESSION['last_activity']) && 
    time() - $_SESSION['last_activity'] > 1800) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}
$_SESSION['last_activity'] = time();
```

## 📱 Responsive Design

### 1. Sử Dụng Bootstrap

```html
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
```

### 2. Kiểm Tra Responsive

- Sử dụng Chrome DevTools (F12)
- Test trên nhiều thiết bị khác nhau
- Sử dụng media queries

```css
@media (max-width: 768px) {
    .container {
        padding: 10px;
    }
}
```

## 🔄 Cập Nhật Hệ Thống

### 1. Cập Nhật Database

```php
// Kiểm tra và thêm cột mới
$checkColumnSql = "SHOW COLUMNS FROM table_name LIKE 'column_name'";
$checkStmt = $conn->prepare($checkColumnSql);
$checkStmt->execute();

if ($checkStmt->rowCount() == 0) {
    $addColumnSql = "ALTER TABLE table_name ADD COLUMN column_name VARCHAR(255)";
    $conn->exec($addColumnSql);
}
```

### 2. Cập Nhật File

- Backup file trước khi sửa đổi
- Sử dụng version control (git)
- Kiểm tra syntax trước khi deploy

```bash
# Kiểm tra syntax PHP
php -l file.php
```

---

## 📋 Checklist Trước Khi Deploy

- [ ] Backup database
- [ ] Kiểm tra syntax tất cả file PHP
- [ ] Test tất cả tính năng chính
- [ ] Kiểm tra responsive trên mobile
- [ ] Cập nhật cấu hình (URL, API keys)
- [ ] Kiểm tra log lỗi
- [ ] Xóa code debug và comment không cần thiết
- [ ] Kiểm tra bảo mật (SQL injection, XSS)
- [ ] Tối ưu hóa performance

---

**Lưu ý**: Hướng dẫn này được cập nhật thường xuyên. Vui lòng kiểm tra phiên bản mới nhất trước khi sử dụng.
