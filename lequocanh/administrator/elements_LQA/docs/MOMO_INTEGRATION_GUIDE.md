# Hướng Dẫn Tích Hợp Thanh Toán MoMo

## Tổng Quan

Hệ thống đã được tích hợp thanh toán MoMo hoàn chỉnh dựa trên official MoMo PHP SDK. Người dùng có thể thanh toán đơn hàng qua ví MoMo và nhận thông báo tự động khi thanh toán thành công.

## Cấu Trúc Files

### 1. Files Cấu Hình
- **`config/momo_config.php`** - Cấu hình API MoMo
- **`mod/momoPaymentCls.php`** - Class xử lý thanh toán MoMo

### 2. Files Xử Lý Thanh Toán
- **`mgiohang/momo_payment.php`** - Tạo yêu cầu thanh toán
- **`mgiohang/init_payment.php`** - Khởi tạo thanh toán (dựa trên MoMo SDK)
- **`mgiohang/query_transaction.php`** - Kiểm tra trạng thái giao dịch
- **`mgiohang/momo_notify.php`** - IPN Handler (nhận thông báo từ MoMo)
- **`mgiohang/momo_return.php`** - Xử lý khi user quay lại từ MoMo

### 3. Files Giao Diện
- **`mgiohang/checkout.php`** - Trang thanh toán (đã cập nhật với MoMo)

## Luồng Thanh Toán

### 1. Khởi Tạo Thanh Toán
```
User chọn MoMo → checkout.php → momo_payment.php → MoMo API → Redirect to MoMo
```

### 2. Xử Lý Kết Quả
```
MoMo → momo_return.php (user quay lại) + momo_notify.php (IPN) → Cập nhật database
```

## Cấu Hình

### 1. Thông Tin Test (Hiện Tại)
```php
const PARTNER_CODE = 'MOMO';
const ACCESS_KEY = 'F8BBA842ECF85';
const SECRET_KEY = 'K951B6PE1waDMi640xX08PD3vg6EkVlz';
```

### 2. URLs Callback
- **Return URL:** `http://localhost:8080/lequocanh/administrator/elements_LQA/mgiohang/momo_return.php`
- **IPN URL:** `http://localhost:8080/lequocanh/administrator/elements_LQA/mgiohang/momo_notify.php`

### 3. Cấu Hình Production
Khi deploy production, cần:
1. Đăng ký tài khoản MoMo Business
2. Lấy thông tin Partner Code, Access Key, Secret Key thực tế
3. Cập nhật URLs callback với domain thực tế
4. Thay đổi endpoint từ test sang production

## Tính Năng

### 1. Thanh Toán Đa Phương Thức
- ✅ Chuyển khoản ngân hàng (VietQR)
- ✅ Thanh toán MoMo
- 🔄 Có thể mở rộng thêm các phương thức khác

### 2. Xử Lý Thông Báo Tự Động
- ✅ IPN (Instant Payment Notification) từ MoMo
- ✅ Cập nhật trạng thái đơn hàng tự động
- ✅ Logging chi tiết các giao dịch

### 3. Bảo Mật
- ✅ Signature verification cho tất cả callback
- ✅ Validation dữ liệu đầu vào
- ✅ Session management an toàn

## Cách Sử Dụng

### 1. Cho Khách Hàng
1. Thêm sản phẩm vào giỏ hàng
2. Chọn sản phẩm và nhấn "Thanh toán"
3. Nhập địa chỉ giao hàng
4. Chọn "Thanh toán MoMo"
5. Nhấn "Thanh toán với MoMo"
6. Được chuyển hướng đến trang MoMo
7. Hoàn tất thanh toán trên MoMo
8. Tự động quay lại trang xác nhận đơn hàng

### 2. Cho Admin
- Xem log giao dịch tại: `logs/momo_transactions.log`
- Quản lý đơn hàng qua admin panel
- Kiểm tra trạng thái thanh toán trong database

## Testing

### 1. Test Environment
- Sử dụng MoMo Test API
- Không cần tài khoản MoMo thật
- Có thể test các trường hợp thành công/thất bại

### 2. Test Cases
1. **Thanh toán thành công**
   - Tạo đơn hàng → Chọn MoMo → Hoàn tất thanh toán
   - Kiểm tra: Trạng thái đơn hàng = 'paid', tồn kho giảm

2. **Thanh toán thất bại**
   - Tạo đơn hàng → Chọn MoMo → Hủy thanh toán
   - Kiểm tra: Trạng thái đơn hàng = 'failed'

3. **IPN Processing**
   - Kiểm tra log `momo_transactions.log`
   - Verify signature validation

## Troubleshooting

### 1. Lỗi Thường Gặp

#### "Failed to create MoMo payment request"
- **Nguyên nhân:** Lỗi kết nối API hoặc cấu hình sai
- **Giải pháp:** Kiểm tra internet, cấu hình API keys

#### "Dữ liệu trả về từ MoMo không hợp lệ"
- **Nguyên nhân:** Signature không khớp
- **Giải pháp:** Kiểm tra Secret Key, format dữ liệu

#### "Order information not found in session"
- **Nguyên nhân:** Session bị mất
- **Giải pháp:** Kiểm tra session configuration

### 2. Debug Tools

#### Kiểm tra Log
```bash
tail -f lequocanh/administrator/elements_LQA/logs/momo_transactions.log
```

#### Test API trực tiếp
```php
// Gọi query_transaction.php
curl -X POST http://localhost:8080/lequocanh/administrator/elements_LQA/mgiohang/query_transaction.php \
  -d "orderId=ORDER123"
```

## Database Schema

### Bảng `don_hang`
```sql
CREATE TABLE don_hang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_don_hang_text VARCHAR(50) NOT NULL,
    ma_nguoi_dung VARCHAR(50),
    dia_chi_giao_hang TEXT,
    tong_tien DECIMAL(15,2) NOT NULL,
    trang_thai ENUM('pending', 'approved', 'cancelled') DEFAULT 'pending',
    phuong_thuc_thanh_toan VARCHAR(50) DEFAULT 'momo',
    trang_thai_thanh_toan ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Bảng `chi_tiet_don_hang`
```sql
CREATE TABLE chi_tiet_don_hang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_don_hang INT NOT NULL,
    ma_san_pham INT NOT NULL,
    so_luong INT NOT NULL,
    gia DECIMAL(15,2) NOT NULL,
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ma_don_hang) REFERENCES don_hang(id) ON DELETE CASCADE
);
```

## Monitoring & Analytics

### 1. Transaction Logs
- Tất cả giao dịch được log chi tiết
- Bao gồm request/response từ MoMo API
- Timestamp và error tracking

### 2. Performance Metrics
- Thời gian xử lý thanh toán
- Tỷ lệ thành công/thất bại
- Phân tích lỗi phổ biến

## Security Best Practices

### 1. Đã Implement
- ✅ HMAC-SHA256 signature verification
- ✅ Input validation và sanitization
- ✅ Secure session management
- ✅ Error handling không expose sensitive data

### 2. Khuyến Nghị Thêm
- 🔄 Rate limiting cho API calls
- 🔄 IP whitelist cho IPN endpoint
- 🔄 SSL/TLS cho tất cả communications
- 🔄 Regular security audit

## Kết Luận

Tích hợp MoMo đã hoàn thiện với đầy đủ tính năng:
- ✅ Thanh toán an toàn và đáng tin cậy
- ✅ Xử lý thông báo tự động
- ✅ Logging và monitoring chi tiết
- ✅ Error handling robust
- ✅ Dễ dàng maintain và extend

Hệ thống sẵn sàng cho việc testing và deployment production.