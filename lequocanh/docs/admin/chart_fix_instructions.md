# Hướng dẫn khắc phục sự cố biểu đồ doanh thu

## Các bước để kiểm tra và sửa lỗi biểu đồ không hiển thị

### 1. Kiểm tra lỗi JavaScript trong Browser Console
1. Mở trình duyệt và vào trang báo cáo doanh thu
2. Nhấn F12 để mở Developer Tools
3. Chuyển sang tab Console
4. Kiểm tra có lỗi JavaScript nào không
5. Tìm các thông báo debug bắt đầu với "Chart labels:" và "Chart data:"

### 2. Sử dụng file debug để kiểm tra
1. Truy cập URL: `your_domain/lequocanh/administrator/debug_chart.php`
2. File này sẽ hiển thị:
   - Trạng thái kết nối database
   - Số lượng đơn hàng trong DB
   - Dữ liệu chart được tạo ra
   - Test render biểu đồ trực tiếp

### 3. Các nguyên nhân có thể gây ra lỗi

#### A. Chart.js library không tải được
**Triệu chứng:** Console hiện lỗi "Chart is not defined"
**Giải pháp:**
- Kiểm tra kết nối internet
- Thay CDN Chart.js bằng link khác:
```html
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
```

#### B. Không có dữ liệu trong database
**Triệu chứng:** Console hiện "Chart data: []" (mảng trống)
**Giải pháp:**
- Kiểm tra bảng `don_hang` có dữ liệu không
- Đảm bảo có đơn hàng với `trang_thai = 'approved'`
- Chạy SQL test:
```sql
SELECT COUNT(*) FROM don_hang WHERE trang_thai = 'approved';
SELECT DATE(ngay_tao) as ngay, SUM(tong_tien) as doanh_thu 
FROM don_hang 
WHERE trang_thai = 'approved' 
GROUP BY DATE(ngay_tao) 
ORDER BY ngay DESC 
LIMIT 10;
```

#### C. Canvas element không tồn tại
**Triệu chứng:** Console hiện "Canvas element not found!"
**Giải pháp:**
- Đảm bảo HTML có thẻ: `<canvas id="revenueChart"></canvas>`
- Kiểm tra CSS không ẩn element

#### D. Database connection lỗi
**Triệu chứng:** Trang báo lỗi PHP hoặc không load được
**Giải pháp:**
- Kiểm tra file `elements_LQA/mod/config.ini`
- Đảm bảo MySQL service đang chạy
- Test connection bằng debug_chart.php

### 4. Các cải tiến đã thực hiện

#### A. Thêm error handling
- Try-catch cho việc tạo chart
- Kiểm tra canvas element trước khi sử dụng
- Validate dữ liệu trước khi xử lý

#### B. Debug logging
- Log dữ liệu chart ra console
- Hiển thị thông báo lỗi chi tiết
- Fallback với dữ liệu giả nếu không có dữ liệu thật

#### C. Data validation
- Kiểm tra array trước khi map
- Chuyển đổi an toàn sang số với parseFloat
- Xử lý trường hợp dữ liệu null/undefined

### 5. Nếu vẫn không hoạt động

#### Giải pháp tạm thời - Dữ liệu giả
Thêm vào đầu file doanhThuView.php (sau dòng 170):
```php
// Test data fallback
if (empty($chartData) || array_sum($chartData) == 0) {
    $chartLabels = ['20/08/2025', '21/08/2025', '22/08/2025', '23/08/2025', '24/08/2025'];
    $chartData = [94000, 0, 11750, 0, 0];
}
```

#### Debug bằng alert
Thêm vào JavaScript (sau console.log):
```javascript
alert('Chart Labels: ' + JSON.stringify(<?php echo json_encode($chartLabels); ?>));
alert('Chart Data: ' + JSON.stringify(<?php echo json_encode($chartData); ?>));
```

### 6. Cách tạo dữ liệu test

Nếu bảng don_hang trống, chạy SQL này để tạo dữ liệu test:
```sql
INSERT INTO don_hang (ngay_tao, tong_tien, trang_thai) VALUES
(DATE_SUB(NOW(), INTERVAL 1 DAY), 94000, 'approved'),
(DATE_SUB(NOW(), INTERVAL 2 DAYS), 50000, 'approved'),
(DATE_SUB(NOW(), INTERVAL 3 DAYS), 75000, 'approved'),
(DATE_SUB(NOW(), INTERVAL 4 DAYS), 120000, 'approved'),
(NOW(), 85000, 'approved');
```

### 7. Liên hệ hỗ trợ

Nếu sau khi làm theo hướng dẫn mà vẫn không được, hãy:
1. Chụp màn hình Console (F12)
2. Chụp màn hình kết quả debug_chart.php
3. Gửi thông tin về database structure
4. Gửi thông tin về môi trường (XAMPP/WAMP, PHP version, etc.)

---

**Lưu ý:** Backup code trước khi thay đổi để có thể restore nếu cần.
