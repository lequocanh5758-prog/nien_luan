# Hướng dẫn khắc phục vấn đề "orders table not found"

## 🔍 **Nguyên nhân**

Trong hệ thống của bạn có sự không nhất quán về tên bảng:
- **Hệ thống chính**: Sử dụng bảng `don_hang` và `chi_tiet_don_hang` (tiếng Việt)
- **Một số module**: Tìm kiếm bảng `orders` và `order_items` (tiếng Anh)

## 🚨 **Lỗi cụ thể**

```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'trainingdb.orders' doesn't exist
```

## ⚡ **Giải pháp nhanh**

### Bước 1: Chạy script khắc phục
1. Truy cập: `http://your-domain/lequocanh/administrator/fix_orders_table.php`
2. Đăng nhập với quyền admin
3. Chọn **"Tạo VIEW 'orders' cho bảng 'don_hang' (Khuyến nghị)"**
4. Click vào nút để thực hiện

### Bước 2: Kiểm tra kết quả
Sau khi chạy script, hệ thống sẽ:
- Tạo VIEW `orders` để map từ bảng `don_hang`
- Tạo VIEW `order_items` để map từ bảng `chi_tiet_don_hang` 
- Thêm các cột thông báo nếu cần

## 📋 **Các tệp đã được sửa**

### 1. `getOrderDetail.php`
- ✅ Đã sửa để sử dụng bảng `don_hang` thay vì `orders`
- ✅ Cập nhật tên fields: `status` → `trang_thai`, `user_id` → `ma_nguoi_dung`

### 2. `khachhangCls.php`
- ✅ Đã sửa để ưu tiên sử dụng bảng `don_hang`
- ✅ Fallback về bảng `orders` nếu cần thiết

### 3. `orders.php`
- ✅ Đã được thiết kế để tự động tạo bảng nếu chưa có

## 🔧 **Các giải pháp khác**

### Giải pháp 1: VIEW (Khuyến nghị)
```sql
-- Tạo view orders
CREATE VIEW orders AS 
SELECT 
    id,
    ma_don_hang_text as order_code,
    ma_nguoi_dung as user_id,
    dia_chi_giao_hang as shipping_address,
    tong_tien as total_amount,
    trang_thai as status,
    phuong_thuc_thanh_toan as payment_method,
    trang_thai_thanh_toan as payment_status,
    ngay_tao as created_at,
    ngay_cap_nhat as updated_at
FROM don_hang;

-- Tạo view order_items
CREATE VIEW order_items AS 
SELECT 
    id,
    ma_don_hang as order_id,
    ma_san_pham as product_id,
    so_luong as quantity,
    gia as price,
    ngay_tao as created_at
FROM chi_tiet_don_hang;
```

### Giải pháp 2: Tạo bảng mới (Không khuyến nghị)
- Tạo bảng `orders` và `order_items` mới
- Migrate dữ liệu từ `don_hang` và `chi_tiet_don_hang`

### Giải pháp 3: Thêm cột thông báo
```sql
-- Thêm cột cho notification system
ALTER TABLE don_hang ADD COLUMN pending_read TINYINT(1) DEFAULT 0;
ALTER TABLE don_hang ADD COLUMN approved_read TINYINT(1) DEFAULT 0;
ALTER TABLE don_hang ADD COLUMN cancelled_read TINYINT(1) DEFAULT 0;
```

## 🔍 **Kiểm tra trạng thái**

### Kiểm tra bảng tồn tại:
```sql
SHOW TABLES LIKE 'don_hang';
SHOW TABLES LIKE 'orders';
SHOW TABLES LIKE 'chi_tiet_don_hang';
SHOW TABLES LIKE 'order_items';
```

### Kiểm tra VIEW:
```sql
SELECT * FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = 'trainingdb';
```

### Kiểm tra dữ liệu:
```sql
-- Kiểm tra đơn hàng
SELECT COUNT(*) FROM don_hang;
SELECT * FROM don_hang LIMIT 5;

-- Kiểm tra chi tiết đơn hàng  
SELECT COUNT(*) FROM chi_tiet_don_hang;
```

## 📝 **Cấu trúc bảng chuẩn**

### Bảng `don_hang`:
```sql
id INT AUTO_INCREMENT PRIMARY KEY,
ma_don_hang_text VARCHAR(50) NOT NULL,
ma_nguoi_dung VARCHAR(50),
dia_chi_giao_hang TEXT,
tong_tien DECIMAL(15,2) NOT NULL DEFAULT 0,
trang_thai ENUM('pending', 'approved', 'cancelled') NOT NULL DEFAULT 'pending',
phuong_thuc_thanh_toan VARCHAR(50) NOT NULL DEFAULT 'bank_transfer',
trang_thai_thanh_toan ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ngay_cap_nhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
pending_read TINYINT(1) DEFAULT 0,
approved_read TINYINT(1) DEFAULT 0,
cancelled_read TINYINT(1) DEFAULT 0
```

### Bảng `chi_tiet_don_hang`:
```sql
id INT AUTO_INCREMENT PRIMARY KEY,
ma_don_hang INT NOT NULL,
ma_san_pham INT NOT NULL,
so_luong INT NOT NULL DEFAULT 1,
gia DECIMAL(15,2) NOT NULL DEFAULT 0,
ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (ma_don_hang) REFERENCES don_hang(id) ON DELETE CASCADE,
FOREIGN KEY (ma_san_pham) REFERENCES hanghoa(idhanghoa) ON DELETE RESTRICT
```

## 🚨 **Xử lý sự cố**

### Lỗi: "Access denied"
- Đảm bảo đăng nhập với quyền admin
- Kiểm tra quyền MySQL CREATE VIEW

### Lỗi: "View already exists"
- Xóa view cũ trước: `DROP VIEW IF EXISTS orders;`
- Chạy lại script

### Lỗi: "Foreign key constraint"
- Kiểm tra dữ liệu trong bảng `chi_tiet_don_hang`
- Đảm bảo `ma_don_hang` và `ma_san_pham` hợp lệ

## 📞 **Hỗ trợ**

Nếu vẫn gặp vấn đề:
1. Chụp màn hình lỗi
2. Chạy file `debug_chart.php` để kiểm tra database
3. Gửi kết quả SQL: `SHOW TABLES;`
4. Cung cấp thông tin môi trường (XAMPP/WAMP version, PHP version)

---
**Lưu ý**: Backup database trước khi thực hiện bất kỳ thay đổi nào!
