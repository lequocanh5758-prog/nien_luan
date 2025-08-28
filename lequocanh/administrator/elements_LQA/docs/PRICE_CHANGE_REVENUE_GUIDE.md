# Hướng dẫn Xử lý Thay đổi Giá và Báo cáo Doanh thu

## Tổng quan
Hệ thống của bạn đã được thiết kế đúng để xử lý thay đổi giá theo thời gian. Dưới đây là cách hoạt động và các khuyến nghị.

## Cấu trúc Hiện tại

### 1. Bảng `dongia` (Bảng giá)
- Lưu trữ lịch sử giá của sản phẩm
- Cho phép nhiều mức giá cho một sản phẩm với thời gian áp dụng khác nhau
- Trường `apDung` xác định giá nào đang được áp dụng

### 2. Bảng `chi_tiet_don_hang` (Chi tiết đơn hàng)
- **Quan trọng**: Lưu trữ giá thực tế (`gia`) tại thời điểm mua hàng
- Đây là điểm then chốt để báo cáo doanh thu chính xác

### 3. Bảng `don_hang` (Đơn hàng)
- Lưu trữ tổng tiền (`tong_tien`) của đơn hàng
- Được sử dụng cho báo cáo doanh thu tổng quan

## Quy trình Xử lý Giá

### Khi Tạo Đơn hàng Mới:
1. Lấy giá hiện tại từ bảng `dongia` (WHERE apDung = 1)
2. Lưu giá này vào `chi_tiet_don_hang.gia`
3. Tính tổng tiền và lưu vào `don_hang.tong_tien`

### Khi Thay đổi Giá:
1. Thêm record mới vào bảng `dongia` với giá mới
2. Cập nhật `apDung = 0` cho tất cả giá cũ của sản phẩm
3. Set `apDung = 1` cho giá mới
4. **Không ảnh hưởng** đến các đơn hàng đã tạo

## Báo cáo Doanh thu

### Nguyên tắc Vàng:
- **Luôn sử dụng giá đã lưu trong `chi_tiet_don_hang`** cho báo cáo
- **Không bao giờ** tính toán lại dựa trên giá hiện tại

### Các loại Báo cáo:

#### 1. Báo cáo Doanh thu Tổng quan
```sql
SELECT 
    DATE(ngay_tao) as ngay,
    SUM(tong_tien) as doanh_thu,
    COUNT(*) as so_don_hang
FROM don_hang
WHERE trang_thai = 'approved'
    AND DATE(ngay_tao) BETWEEN :start_date AND :end_date
GROUP BY DATE(ngay_tao)
```

#### 2. Báo cáo Doanh thu theo Sản phẩm
```sql
SELECT 
    h.tenhanghoa,
    SUM(ct.gia * ct.so_luong) as doanh_thu,
    SUM(ct.so_luong) as so_luong_ban
FROM chi_tiet_don_hang ct
INNER JOIN don_hang dh ON ct.ma_don_hang = dh.id
INNER JOIN hanghoa h ON ct.ma_san_pham = h.idhanghoa
WHERE dh.trang_thai = 'approved'
    AND DATE(dh.ngay_tao) BETWEEN :start_date AND :end_date
GROUP BY h.idhanghoa
```

#### 3. Báo cáo So sánh Giá
```sql
-- Xem sự thay đổi giá của sản phẩm theo thời gian
SELECT 
    h.tenhanghoa,
    dg.giaBan,
    dg.ngayApDung,
    dg.ngayKetThuc,
    dg.apDung
FROM dongia dg
INNER JOIN hanghoa h ON dg.idHangHoa = h.idhanghoa
WHERE h.idhanghoa = :product_id
ORDER BY dg.ngayApDung DESC
```

## Khuyến nghị Bổ sung

### 1. Thêm Trigger hoặc Stored Procedure
Tạo trigger để tự động cập nhật `tong_tien` khi thêm/sửa chi tiết đơn hàng:

```sql
DELIMITER //
CREATE TRIGGER update_order_total 
AFTER INSERT ON chi_tiet_don_hang
FOR EACH ROW
BEGIN
    UPDATE don_hang 
    SET tong_tien = (
        SELECT SUM(gia * so_luong) 
        FROM chi_tiet_don_hang 
        WHERE ma_don_hang = NEW.ma_don_hang
    )
    WHERE id = NEW.ma_don_hang;
END//
DELIMITER ;
```

### 2. Thêm Index cho Performance
```sql
-- Index cho báo cáo doanh thu
CREATE INDEX idx_donhang_ngay_trangthai ON don_hang(ngay_tao, trang_thai);
CREATE INDEX idx_chitiet_madonhang ON chi_tiet_don_hang(ma_don_hang);
```

### 3. Tạo View cho Báo cáo
```sql
CREATE VIEW v_doanh_thu_san_pham AS
SELECT 
    h.idhanghoa,
    h.tenhanghoa,
    DATE(dh.ngay_tao) as ngay_ban,
    ct.gia as gia_ban_thuc_te,
    ct.so_luong,
    (ct.gia * ct.so_luong) as thanh_tien
FROM chi_tiet_don_hang ct
INNER JOIN don_hang dh ON ct.ma_don_hang = dh.id
INNER JOIN hanghoa h ON ct.ma_san_pham = h.idhanghoa
WHERE dh.trang_thai = 'approved';
```

## Lưu ý Quan trọng

1. **Không bao giờ** cập nhật giá trong các đơn hàng đã tạo
2. **Luôn** lưu giá tại thời điểm giao dịch
3. **Kiểm tra** tính toàn vẹn dữ liệu định kỳ
4. **Backup** trước khi thực hiện thay đổi giá hàng loạt

## Ví dụ Code PHP

### Khi tạo đơn hàng:
```php
// Lấy giá hiện tại
$sql = "SELECT giaBan FROM dongia 
        WHERE idHangHoa = ? AND apDung = 1 
        ORDER BY ngayApDung DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute([$productId]);
$currentPrice = $stmt->fetchColumn();

// Lưu vào chi tiết đơn hàng
$sql = "INSERT INTO chi_tiet_don_hang 
        (ma_don_hang, ma_san_pham, so_luong, gia) 
        VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->execute([$orderId, $productId, $quantity, $currentPrice]);
```

### Khi lấy báo cáo:
```php
public function getRevenueByDateRange($startDate, $endDate) {
    $sql = "SELECT 
                DATE(dh.ngay_tao) as date,
                SUM(dh.tong_tien) as revenue,
                COUNT(DISTINCT dh.id) as order_count,
                SUM(ct.so_luong) as total_items
            FROM don_hang dh
            LEFT JOIN chi_tiet_don_hang ct ON dh.id = ct.ma_don_hang
            WHERE dh.trang_thai = 'approved'
                AND DATE(dh.ngay_tao) BETWEEN ? AND ?
            GROUP BY DATE(dh.ngay_tao)
            ORDER BY date";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

## Kết luận

Hệ thống của bạn đã được thiết kế đúng để xử lý thay đổi giá. Điều quan trọng là:
- Giá trong `chi_tiet_don_hang` phản ánh giá thực tế tại thời điểm mua
- Báo cáo doanh thu sử dụng dữ liệu lịch sử, không phải giá hiện tại
- Thay đổi giá trong tương lai không ảnh hưởng đến doanh thu đã ghi nhận

Điều này đảm bảo tính chính xác của báo cáo tài chính và tuân thủ các nguyên tắc kế toán.
