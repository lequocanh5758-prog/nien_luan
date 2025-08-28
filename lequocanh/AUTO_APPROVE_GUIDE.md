# 🚀 Hướng Dẫn Hệ Thống Tự Động Duyệt Đơn Hàng

## Tổng Quan
Hệ thống tự động duyệt đơn hàng cho phép tự động duyệt các đơn hàng đã được thanh toán qua:
- 💳 **MoMo Wallet**
- 🏦 **Chuyển khoản ngân hàng**

## Cách Hoạt Động

### 1. Thanh Toán MoMo
```
Khách hàng thanh toán → MoMo gửi webhook → notify.php → Tự động duyệt đơn hàng
```

### 2. Thanh Toán Ngân Hàng
```
Khách hàng chuyển khoản → Ngân hàng gửi webhook → bank_notify.php → Tự động duyệt đơn hàng
```

## Files Quan Trọng

### 🔧 Core Files
- `administrator/elements_LQA/mod/AutoOrderProcessor.php` - Xử lý tự động duyệt
- `payment/notify.php` - Webhook MoMo
- `payment/bank_notify.php` - Webhook ngân hàng

### ⚙️ Configuration Files
- `setup_complete_auto_approve.php` - Script thiết lập
- `administrator/elements_LQA/setup_auto_approve_payment.php` - Cấu hình chi tiết

### 🧪 Test Files
- `test_bank_payment.php` - Test thanh toán ngân hàng
- `test_momo_callback.php` - Test thanh toán MoMo

## Thiết Lập

### Bước 1: Chạy Script Thiết Lập
```
http://your-domain.com/lequocanh/setup_complete_auto_approve.php
```

### Bước 2: Cấu Hình Webhook URLs

#### MoMo:
- **Notify URL**: `http://your-domain.com/lequocanh/payment/notify.php`
- **Return URL**: `http://your-domain.com/lequocanh/payment/return.php`

#### Ngân Hàng:
- **Notify URL**: `http://your-domain.com/lequocanh/payment/bank_notify.php`

### Bước 3: Cấu Hình Cron Job (Tùy chọn)
```bash
*/5 * * * * /usr/bin/php /path/to/lequocanh/administrator/elements_LQA/cron/auto_process_orders.php
```

## Cấu Hình Database

### Bảng `system_config`
| Key | Value | Mô tả |
|-----|-------|-------|
| `auto_approve_paid_orders` | `1` | Bật/tắt tự động duyệt |
| `auto_approve_momo` | `1` | Tự động duyệt MoMo |
| `auto_approve_bank_transfer` | `1` | Tự động duyệt ngân hàng |

### Cột `auto_approved` trong bảng `don_hang`
- `0`: Duyệt thủ công
- `1`: Duyệt tự động

## Test Hệ Thống

### Test MoMo:
```
http://your-domain.com/lequocanh/test_momo_callback.php
```

### Test Ngân Hàng:
```
http://your-domain.com/lequocanh/test_bank_payment.php
```

## Quy Trình Tự Động

### 1. Khi Nhận Webhook Thanh Toán:
1. Verify signature (nếu có)
2. Cập nhật `trang_thai_thanh_toan = 'completed'`
3. Gọi `AutoOrderProcessor::approveSpecificOrder()`
4. Gửi thông báo cho khách hàng
5. Log kết quả

### 2. Cron Job (Backup):
- Chạy mỗi 5 phút
- Tìm đơn hàng `trang_thai = 'pending'` và `trang_thai_thanh_toan = 'completed'`
- Tự động duyệt các đơn hàng này

## Monitoring & Logs

### Log Files:
- PHP error log: Ghi lại tất cả hoạt động
- `payment/momo_notify.log`: Log riêng cho MoMo

### Kiểm Tra Trạng Thái:
```sql
SELECT trang_thai, trang_thai_thanh_toan, phuong_thuc_thanh_toan, auto_approved, COUNT(*) 
FROM don_hang 
GROUP BY trang_thai, trang_thai_thanh_toan, phuong_thuc_thanh_toan, auto_approved;
```

## Troubleshooting

### Đơn Hàng Không Được Duyệt Tự Động:

1. **Kiểm tra cấu hình:**
   ```sql
   SELECT * FROM system_config WHERE config_key LIKE 'auto_approve%';
   ```

2. **Kiểm tra webhook:**
   - Xem log PHP error
   - Test với script test

3. **Kiểm tra trạng thái đơn hàng:**
   ```sql
   SELECT * FROM don_hang WHERE ma_don_hang_text = 'ORDER_ID';
   ```

### Lỗi Thường Gặp:

1. **Headers already sent**: Kiểm tra output trước khi gọi webhook
2. **Database connection**: Kiểm tra kết nối database
3. **Invalid signature**: Kiểm tra secret key và cách tạo signature

## Bảo Mật

### Webhook Security:
- Verify signature từ MoMo/Ngân hàng
- Chỉ accept request từ IP whitelist
- Log tất cả request để audit

### Database Security:
- Sử dụng prepared statements
- Validate input data
- Transaction rollback khi có lỗi

## Tùy Chỉnh

### Thêm Phương Thức Thanh Toán Mới:
1. Tạo file webhook mới (ví dụ: `zalopay_notify.php`)
2. Implement logic tương tự `bank_notify.php`
3. Cập nhật cấu hình trong `system_config`

### Thay Đổi Logic Duyệt:
- Chỉnh sửa `AutoOrderProcessor::approveSpecificOrder()`
- Thêm điều kiện kiểm tra bổ sung
- Customize thông báo khách hàng

## Support

Nếu có vấn đề, kiểm tra:
1. PHP error logs
2. Database logs
3. Webhook response codes
4. Test scripts

---

**Lưu ý**: Đảm bảo backup database trước khi thay đổi cấu hình quan trọng.
