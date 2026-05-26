# 📸 Hướng Dẫn Quản Lý Hình Ảnh

## 🗂️ Nơi Lưu Trữ Hình Ảnh

### 1. Thư Mục Chính
```
lequocanh/administrator/uploads/
```

**Trong Docker:**
```
/var/www/html/administrator/uploads/
```

**Trên Windows (local):**
```
D:/PHP_WS/lequocanh/administrator/uploads/
```

### 2. Cấu Trúc Thư Mục
```
administrator/uploads/
├── [timestamp]_[tên_file].png
├── [timestamp]_[tên_file].jpg
└── ...
```

**Ví dụ:**
- `6803c20a9f57f_OPPO Find X6 Pro.png`
- `682898855d852_iPhone 13.png`

## 📤 Cách Upload Hình Ảnh

### Truy Cập Trang Quản Lý
```
http://localhost/lequocanh/administrator/
→ Menu: Hình ảnh → Quản lý hình ảnh
```

### Các Bước Upload

1. **Chọn File**
   - Click vào "Chọn hình ảnh"
   - Có thể chọn nhiều file cùng lúc
   - Định dạng: JPG, PNG, GIF
   - Kích thước tối đa: 5MB/file

2. **Tự Động Khớp Sản Phẩm**
   - ✅ Bật: Hệ thống tự động khớp tên file với tên sản phẩm
   - Ví dụ: "iPhone 15 Pro.png" → Sản phẩm "iPhone 15 Pro"

3. **Xử Lý Ảnh Trùng Lặp**
   - Nếu sản phẩm đã có ảnh, hệ thống sẽ hỏi:
     - **Sử dụng ảnh mới**: Thay thế ảnh cũ
     - **Giữ ảnh hiện tại**: Bỏ qua ảnh mới

## 🔒 Bảo Mật Upload

### Kiểm Tra Tự Động
- ✅ Định dạng file (MIME type)
- ✅ Phần mở rộng file
- ✅ Kích thước file
- ✅ Nội dung file (magic bytes)
- ✅ Tên file an toàn (sanitize)

### File Được Phép
```php
Định dạng: JPG, JPEG, PNG, GIF
Kích thước: Tối đa 5MB
MIME types: image/jpeg, image/png, image/gif
```

## 🗑️ Xóa Hình Ảnh

### Xóa Từng Ảnh
- Click nút 🗑️ ở cột "Thao tác"
- Chỉ xóa được ảnh **không được sử dụng**

### Xóa Nhiều Ảnh
1. Chọn checkbox các ảnh muốn xóa
2. Click "Xóa đã chọn"
3. Xác nhận

### Ảnh Đang Sử Dụng
- Có icon 🔒
- Không thể xóa
- Phải gỡ khỏi sản phẩm trước

## 🔧 Cấu Hình

### Quyền Thư Mục (Linux/Docker)
```bash
chmod 777 administrator/uploads/
```

### Quyền Thư Mục (Windows)
- Chuột phải → Properties → Security
- Cho phép "Full Control" cho user hiện tại

### Kiểm Tra Quyền
Hệ thống tự động kiểm tra và hiển thị cảnh báo nếu:
- ❌ Thư mục không tồn tại
- ❌ Không có quyền ghi
- ❌ Không thể tạo thư mục

## 📊 Thông Tin Hình Ảnh

### Hiển Thị Trong Bảng
- **ID**: Mã định danh duy nhất
- **Hình ảnh**: Preview thumbnail
- **Tên file**: Tên gốc của file
- **Kích thước**: Dung lượng file (KB)
- **Ngày tải lên**: Thời gian upload
- **Thao tác**: Nút xóa (nếu có thể)

### Trạng Thái
- 🟢 **Có thể xóa**: Ảnh không được sử dụng
- 🔴 **Đang sử dụng**: Ảnh đang gắn với sản phẩm

## 🔗 API Endpoints

### Upload Ảnh
```
POST /lequocanh/administrator/elements_LQA/mhinhanh/hinhanhAct.php?reqact=addnew
Content-Type: multipart/form-data

fileHinhanh[]: [file]
auto_match: 1
```

### Xóa Ảnh
```
POST /lequocanh/administrator/elements_LQA/mhinhanh/hinhanhAct.php?reqact=delete
Content-Type: application/x-www-form-urlencoded

id: [image_id]
```

### Xóa Nhiều Ảnh
```
POST /lequocanh/administrator/elements_LQA/mhinhanh/hinhanhAct.php?reqact=delete_multiple
Content-Type: application/json

{
  "ids": [1, 2, 3]
}
```

### Xử Lý Ảnh Trùng
```
POST /lequocanh/administrator/elements_LQA/mhinhanh/hinhanhAct.php?reqact=resolve_duplicate
Content-Type: application/x-www-form-urlencoded

index: 0
action: use_new | use_existing
```

## 🐛 Xử Lý Lỗi

### Lỗi Thường Gặp

**1. "Không thể upload ảnh"**
- ✅ Kiểm tra quyền thư mục
- ✅ Kiểm tra dung lượng file
- ✅ Kiểm tra định dạng file

**2. "Ảnh không hiển thị"**
- ✅ Kiểm tra đường dẫn file
- ✅ Xóa cache trình duyệt
- ✅ Kiểm tra file có tồn tại

**3. "Không thể xóa ảnh"**
- ✅ Ảnh đang được sử dụng bởi sản phẩm
- ✅ Gỡ ảnh khỏi sản phẩm trước

## 📝 Ghi Chú

### Tên File
- Tự động thêm timestamp để tránh trùng lặp
- Loại bỏ ký tự đặc biệt
- Giữ nguyên tên gốc (sau timestamp)

### Hiệu Suất
- Ảnh được cache bởi trình duyệt
- Sử dụng lazy loading cho danh sách lớn
- Thumbnail tự động resize

### Backup
- Nên backup thư mục `uploads/` định kỳ
- Sử dụng Docker volume để bảo toàn dữ liệu

## 🔍 Debug

### Kiểm Tra Upload
```php
// File: lequocanh/administrator/uploads/upload_errors.log
// Xem log lỗi upload
```

### Kiểm Tra Quyền
```bash
# Linux/Docker
ls -la administrator/uploads/

# Windows
dir administrator\uploads\
```

### Test Upload
1. Truy cập trang quản lý hình ảnh
2. Upload 1 ảnh test
3. Kiểm tra thư mục `uploads/`
4. Xem log nếu có lỗi
