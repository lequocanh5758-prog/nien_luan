# 📋 Hướng Dẫn Sử Dụng - Khách Hàng

## 🔔 Hệ Thống Thông Báo

### Các Loại Thông Báo
- **✅ Đơn hàng được duyệt**: Khi admin duyệt đơn hàng của bạn
- **💰 Thanh toán xác nhận**: Khi thanh toán được xác nhận thành công
- **❌ Đơn hàng bị hủy**: Khi đơn hàng bị hủy (bởi admin hoặc khách hàng)
- **🚚 Đơn hàng được giao**: Khi đơn hàng được giao thành công

### Cách Xem Thông Báo
1. Đăng nhập vào tài khoản
2. Nhìn vào góc phải trên cùng - biểu tượng chuông 🔔
3. Số đỏ hiển thị số thông báo chưa đọc
4. Click vào chuông để xem danh sách thông báo

## 📋 Lịch Sử Mua Hàng

### Truy Cập Lịch Sử
- **URL**: `administrator/index.php?req=lichsumuahang`
- **Menu**: Thông qua menu điều hướng trong hệ thống

### Thông Tin Hiển Thị
- **Mã đơn hàng**: Mã duy nhất của đơn hàng
- **Ngày đặt**: Thời gian tạo đơn hàng
- **Tổng tiền**: Giá trị đơn hàng
- **Trạng thái**: 
  - 🟡 **Chờ xác nhận**: Đơn hàng mới, chưa được duyệt
  - 🟢 **Đã duyệt**: Đơn hàng đã được xác nhận
  - 🔴 **Đã hủy**: Đơn hàng đã bị hủy
- **Phương thức thanh toán**:
  - 📱 **MoMo**: Thanh toán qua ví MoMo
  - 🏦 **Chuyển khoản**: Chuyển khoản ngân hàng
  - 💵 **COD**: Thanh toán khi nhận hàng

### Tính Năng
- **Xem chi tiết**: Click "Chi tiết" để xem thông tin đầy đủ
- **Hủy đơn hàng**: Chỉ có thể hủy đơn hàng ở trạng thái "Chờ xác nhận"
- **Phân trang**: Hiển thị 10 đơn hàng mỗi trang

## 💳 Thanh Toán Tự Động

### MoMo Wallet
1. Chọn phương thức thanh toán MoMo
2. Quét mã QR hoặc nhập thông tin
3. Xác nhận thanh toán trong app MoMo
4. **Đơn hàng sẽ được duyệt tự động** sau khi thanh toán thành công
5. Nhận thông báo xác nhận

### Chuyển Khoản Ngân Hàng
1. Chọn phương thức chuyển khoản
2. Thực hiện chuyển khoản theo thông tin được cung cấp
3. **Đơn hàng sẽ được duyệt tự động** khi ngân hàng xác nhận
4. Nhận thông báo xác nhận

### COD (Cash on Delivery)
1. Chọn phương thức COD
2. Đơn hàng cần được admin duyệt thủ công
3. Thanh toán khi nhận hàng

## 🔄 Quy Trình Đặt Hàng

### Bước 1: Đặt Hàng
1. Chọn sản phẩm và thêm vào giỏ hàng
2. Điền thông tin giao hàng
3. Chọn phương thức thanh toán
4. Xác nhận đơn hàng

### Bước 2: Thanh Toán (nếu không phải COD)
1. Thực hiện thanh toán theo phương thức đã chọn
2. Chờ xác nhận thanh toán

### Bước 3: Xử Lý Đơn Hàng
- **Thanh toán online**: Tự động duyệt ngay lập tức
- **COD**: Chờ admin duyệt thủ công

### Bước 4: Nhận Thông Báo
1. Thông báo xác nhận thanh toán
2. Thông báo đơn hàng được duyệt
3. Thông báo giao hàng (nếu có)

## 📱 Tính Năng Mobile

### Responsive Design
- Giao diện tự động điều chỉnh theo màn hình
- Tối ưu cho điện thoại và tablet
- Touch-friendly buttons và navigation

### Thông Báo Real-time
- Cập nhật thông báo tự động
- Không cần refresh trang
- Badge hiển thị số thông báo mới

## 🛡️ Bảo Mật

### Thông Tin Cá Nhân
- Chỉ hiển thị đơn hàng của chính khách hàng
- Không thể xem đơn hàng của người khác
- Session timeout tự động

### Thanh Toán
- Sử dụng gateway thanh toán chính thức
- Mã hóa thông tin giao dịch
- Xác thực signature từ nhà cung cấp

## 🆘 Hỗ Trợ

### Vấn Đề Thường Gặp

**Q: Tại sao đơn hàng chưa được duyệt?**
A: 
- Đơn COD cần admin duyệt thủ công
- Đơn thanh toán online sẽ tự động duyệt sau khi thanh toán thành công

**Q: Làm sao để hủy đơn hàng?**
A:
- Vào lịch sử mua hàng
- Click "Hủy" ở đơn hàng có trạng thái "Chờ xác nhận"
- Xác nhận hủy đơn

**Q: Tại sao không nhận được thông báo?**
A:
- Kiểm tra đăng nhập
- Refresh trang để cập nhật thông báo
- Liên hệ admin nếu vẫn có vấn đề

**Q: Thanh toán thành công nhưng đơn hàng chưa được duyệt?**
A:
- Đợi vài phút để hệ thống xử lý
- Kiểm tra lại trạng thái đơn hàng
- Liên hệ admin nếu quá 15 phút

### Liên Hệ Hỗ Trợ
- **Email**: support@yourstore.com
- **Hotline**: 1900-xxxx
- **Giờ hỗ trợ**: 8:00 - 22:00 hàng ngày

---

**Lưu ý**: Hệ thống được cập nhật thường xuyên để cải thiện trải nghiệm khách hàng. Vui lòng kiểm tra hướng dẫn này định kỳ để có thông tin mới nhất.
