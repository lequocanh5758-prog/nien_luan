# Tài Liệu Logic Quản Lý Giá Sản Phẩm

## Tổng Quan

Hệ thống quản lý giá đã được cải tiến để tránh việc ghi đè giá sản phẩm một cách không mong muốn khi duyệt phiếu nhập. Logic mới cho phép quản trị viên kiểm soát chính xác cách thức cập nhật giá.

## Vấn Đề Trước Đây

### Logic Cũ:
1. Khi duyệt phiếu nhập → **TỰ ĐỘNG** cập nhật `giathamkhao` = `giaNhap`
2. Điều này **GHI ĐÈ** đơn giá đã được thiết lập thủ công
3. Không phân biệt giữa giá nhập (cost) và giá bán (selling price)

### Hậu Quả:
- Đơn giá bán được thiết lập cẩn thận bị mất
- Giá hiển thị cho khách hàng bị thay đổi không mong muốn
- Khó kiểm soát lợi nhuận

## Logic Mới

### 1. Cấu Hình Linh Hoạt
File: `config/price_logic_config.php`

```php
const AUTO_UPDATE_PRICE_ON_IMPORT = false;  // Không tự động cập nhật
const OVERRIDE_EXISTING_PRICE = false;     // Không ghi đè giá đã có
const CREATE_PRICE_FROM_IMPORT = true;     // Tạo đơn giá mới cho sản phẩm chưa có giá
const DEFAULT_PROFIT_MARGIN = 20;          // Lợi nhuận 20%
const AUTO_APPLY_PROFIT_MARGIN = true;     // Tự động tính lợi nhuận
```

### 2. Luồng Xử Lý Mới

#### Khi Duyệt Phiếu Nhập:

1. **Kiểm tra sản phẩm có đơn giá đang áp dụng không?**
   - Có → Bảo vệ giá hiện tại (không ghi đè)
   - Không → Tạo đơn giá mới

2. **Nếu tạo đơn giá mới:**
   - Tính giá bán = Giá nhập + Lợi nhuận
   - Tạo record trong bảng `dongia`
   - Cập nhật `giathamkhao` trong bảng `hanghoa`

3. **Ghi log chi tiết:**
   - Ghi lại mọi thao tác cập nhật giá
   - Thông báo khi bỏ qua cập nhật giá

## Các Tình Huống Thực Tế

### Tình Huống 1: Sản Phẩm Mới
- **Trạng thái:** Chưa có đơn giá nào
- **Hành động:** Tạo đơn giá mới từ phiếu nhập
- **Kết quả:** Giá bán = Giá nhập + 20% lợi nhuận

### Tình Huống 2: Sản Phẩm Đã Có Giá
- **Trạng thái:** Đã có đơn giá đang áp dụng
- **Hành động:** Bảo vệ giá hiện tại
- **Kết quả:** Giá bán không thay đổi

### Tình Huống 3: Muốn Cập Nhật Giá
- **Cách thực hiện:** Thay đổi cấu hình `OVERRIDE_EXISTING_PRICE = true`
- **Kết quả:** Cho phép ghi đè giá đã có

## Cách Sử Dụng

### 1. Truy Cập Trang Cấu Hình
- Đường dẫn: `index.php?req=price_config`
- Quyền: Chỉ admin mới truy cập được

### 2. Điều Chỉnh Cấu Hình
- **Tự động cập nhật giá:** Bật/tắt cập nhật giá khi duyệt phiếu nhập
- **Ghi đè giá đã có:** Cho phép ghi đè giá đã thiết lập
- **Tạo giá từ phiếu nhập:** Tự động tạo đơn giá cho sản phẩm mới
- **Tỷ lệ lợi nhuận:** Phần trăm lợi nhuận áp dụng

### 3. Theo Dõi Log
- File log: `elements_LQA/mprice_config/price_config_changes.log`
- Ghi lại mọi thay đổi cấu hình

## Lợi Ích

### 1. Bảo Vệ Dữ Liệu
- Đơn giá thủ công không bị ghi đè
- Kiểm soát chính xác việc cập nhật giá

### 2. Linh Hoạt
- Cấu hình theo nhu cầu cụ thể
- Dễ dàng thay đổi logic

### 3. Minh Bạch
- Log chi tiết mọi thao tác
- Dễ debug và kiểm tra

### 4. Tự Động Hóa Thông Minh
- Tự động tính lợi nhuận
- Chỉ tạo giá cho sản phẩm cần thiết

## Khuyến Nghị Sử Dụng

### Cấu Hình Khuyến Nghị:
```php
AUTO_UPDATE_PRICE_ON_IMPORT = false;    // An toàn
OVERRIDE_EXISTING_PRICE = false;        // Bảo vệ giá đã có
CREATE_PRICE_FROM_IMPORT = true;        // Tiện lợi cho sản phẩm mới
DEFAULT_PROFIT_MARGIN = 20;             // Tùy theo ngành hàng
AUTO_APPLY_PROFIT_MARGIN = true;        // Tự động tính toán
```

### Quy Trình Làm Việc:
1. Thiết lập đơn giá thủ công cho sản phẩm quan trọng
2. Để hệ thống tự động tạo giá cho sản phẩm mới
3. Kiểm tra log thường xuyên
4. Điều chỉnh cấu hình khi cần thiết

## Lưu Ý Quan Trọng

1. **Backup trước khi thay đổi cấu hình**
2. **Test trên môi trường dev trước**
3. **Kiểm tra log sau mỗi lần duyệt phiếu nhập**
4. **Đào tạo nhân viên về logic mới**

## Hỗ Trợ

Nếu có vấn đề, kiểm tra:
1. File log: `price_config_changes.log`
2. Error log của PHP
3. Log trong database về hoạt động cập nhật giá
