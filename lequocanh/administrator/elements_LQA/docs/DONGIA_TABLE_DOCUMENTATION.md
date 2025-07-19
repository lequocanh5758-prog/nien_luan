# Tài Liệu Bảng Đơn Giá

## Tổng Quan

Bảng đơn giá (`dongia`) là thành phần quan trọng trong hệ thống quản lý giá sản phẩm, cho phép thiết lập nhiều mức giá cho từng sản phẩm theo thời gian và điều kiện khác nhau.

## Cấu Trúc Bảng

### Bảng `dongia`
```sql
CREATE TABLE dongia (
    idDonGia INT AUTO_INCREMENT PRIMARY KEY,
    idHangHoa INT NOT NULL,
    giaBan DECIMAL(15,2) NOT NULL,
    ngayApDung DATE NOT NULL,
    ngayKetThuc DATE NOT NULL,
    dieuKien VARCHAR(255),
    ghiChu TEXT,
    apDung TINYINT(1) DEFAULT 0,
    FOREIGN KEY (idHangHoa) REFERENCES hanghoa(idhanghoa)
);
```

### Các Trường Quan Trọng

1. **`idDonGia`** - ID duy nhất của đơn giá
2. **`idHangHoa`** - ID sản phẩm (khóa ngoại)
3. **`giaBan`** - Giá bán (VNĐ)
4. **`ngayApDung`** - Ngày bắt đầu áp dụng
5. **`ngayKetThuc`** - Ngày kết thúc
6. **`dieuKien`** - Điều kiện áp dụng (tùy chọn)
7. **`ghiChu`** - Ghi chú thêm
8. **`apDung`** - Trạng thái áp dụng (0/1) - **QUAN TRỌNG!**

## Logic Hoạt Động

### 1. Quy Tắc "Chỉ 1 Đơn Giá Áp Dụng"
- Mỗi sản phẩm chỉ có **1 đơn giá** có `apDung = 1`
- Khi thiết lập đơn giá mới → Tự động đặt tất cả đơn giá cũ thành `apDung = 0`
- Đơn giá đang áp dụng → Cập nhật `giathamkhao` trong bảng `hanghoa`

### 2. Mối Quan Hệ Với Bảng `hanghoa`
```php
// Khi đơn giá được áp dụng
UPDATE hanghoa SET giathamkhao = [giaBan] WHERE idhanghoa = [idHangHoa]
```

### 3. Tích Hợp Với Logic Phiếu Nhập
- **Logic mới:** Phiếu nhập KHÔNG ghi đè đơn giá đã thiết lập
- **Bảo vệ:** Đơn giá thủ công được ưu tiên
- **Tự động:** Chỉ tạo đơn giá cho sản phẩm chưa có giá

## Các Tình Huống Sử Dụng

### 1. Thiết Lập Giá Mới
```php
$dongiaObj->DongiaAdd($idHangHoa, $giaBan, $ngayApDung, $ngayKetThuc, $dieuKien, $ghiChu);
```
**Kết quả:**
- Tất cả đơn giá cũ → `apDung = 0`
- Đơn giá mới → `apDung = 1`
- Cập nhật `giathamkhao` trong bảng `hanghoa`

### 2. Chuyển Đổi Đơn Giá
```php
$dongiaObj->DongiaUpdateStatus($idDonGia, true); // Áp dụng
$dongiaObj->DongiaUpdateStatus($idDonGia, false); // Ngừng áp dụng
```

### 3. Xóa Đơn Giá
```php
$dongiaObj->DongiaDelete($idDonGia);
```
**Logic thông minh:**
- Nếu xóa đơn giá đang áp dụng → Tự động tìm đơn giá mới nhất để áp dụng
- Cập nhật `giathamkhao` tương ứng

## Giao Diện Quản Lý

### 1. Trang Danh Sách (`dongiaView.php`)
- **Thống kê tổng quan:** Widget hiển thị số liệu
- **Bảng đơn giá:** Hiển thị tất cả đơn giá với trạng thái
- **Thao tác:** Áp dụng/Ngừng/Sửa/Xóa

### 2. Form Thêm Mới
- **Chọn sản phẩm:** Dropdown với giá hiện tại
- **Thiết lập giá:** Giá bán mới
- **Thời gian:** Ngày áp dụng và kết thúc
- **Tùy chọn:** Điều kiện và ghi chú

### 3. Trạng Thái Hiển Thị
- **🟢 Đang áp dụng:** Đơn giá hiện tại
- **🟡 Chưa áp dụng:** Đơn giá chờ
- **🔴 Đã hết hạn:** Đơn giá quá hạn

## Tính Năng Nâng Cao

### 1. Widget Thống Kê
- Tổng số đơn giá
- Số đơn giá đang áp dụng
- Số đơn giá hết hạn
- Giá trung bình
- Phân bố sản phẩm có/không có giá

### 2. Validation Thông Minh
- Kiểm tra ngày áp dụng < ngày kết thúc
- Validation giá bán > 0
- Cảnh báo khi thay đổi đơn giá đang áp dụng

### 3. Responsive Design
- Giao diện thân thiện mobile
- Animation mượt mà
- Highlight đơn giá đang áp dụng

## Tích Hợp Với Logic Mới

### 1. Bảo Vệ Khỏi Phiếu Nhập
```php
// Kiểm tra trước khi cập nhật từ phiếu nhập
$currentActivePrice = $dongiaObj->DongiaGetActiveByProduct($idHangHoa);
if ($currentActivePrice) {
    // Bảo vệ - không ghi đè
    log("Skipped price update - product has active price");
} else {
    // Tạo đơn giá mới
    $dongiaObj->DongiaAdd(...);
}
```

### 2. Tự Động Tạo Từ Phiếu Nhập
- Chỉ tạo cho sản phẩm chưa có đơn giá
- Áp dụng tỷ lệ lợi nhuận từ cấu hình
- Ghi chú nguồn gốc từ phiếu nhập

## Best Practices

### 1. Quản Lý Đơn Giá
- **Luôn kiểm tra** đơn giá đang áp dụng trước khi thay đổi
- **Backup** trước khi xóa đơn giá quan trọng
- **Thiết lập thời hạn** hợp lý cho đơn giá

### 2. Workflow Khuyến Nghị
1. Tạo đơn giá mới với thời gian trong tương lai
2. Test giá trên môi trường dev
3. Áp dụng đơn giá khi đến thời điểm
4. Theo dõi và điều chỉnh nếu cần

### 3. Monitoring
- Kiểm tra đơn giá hết hạn định kỳ
- Theo dõi sản phẩm chưa có giá
- Review giá trung bình theo danh mục

## Troubleshooting

### 1. Sản Phẩm Không Có Giá
**Nguyên nhân:**
- Chưa thiết lập đơn giá
- Đơn giá đã hết hạn
- Lỗi trong quá trình áp dụng

**Giải pháp:**
- Tạo đơn giá mới
- Kiểm tra ngày hết hạn
- Xem log hệ thống

### 2. Giá Không Cập Nhật
**Nguyên nhân:**
- Đơn giá chưa được áp dụng (`apDung = 0`)
- Lỗi sync giữa bảng `dongia` và `hanghoa`

**Giải pháp:**
- Kiểm tra trạng thái `apDung`
- Chạy lại `UpdateLatestPriceForProduct()`

### 3. Conflict Khi Duyệt Phiếu Nhập
**Nguyên nhân:**
- Logic cũ ghi đè đơn giá

**Giải pháp:**
- Sử dụng logic mới đã cập nhật
- Kiểm tra cấu hình `PriceLogicConfig`

## Kết Luận

Bảng đơn giá là trung tâm của hệ thống quản lý giá, với logic được thiết kế để:
- **Bảo vệ** đơn giá đã thiết lập
- **Linh hoạt** trong quản lý nhiều mức giá
- **Tự động hóa** các tác vụ phổ biến
- **Minh bạch** trong theo dõi và kiểm soát

Với các cải tiến mới, hệ thống đảm bảo tính nhất quán và an toàn cho dữ liệu giá sản phẩm.
