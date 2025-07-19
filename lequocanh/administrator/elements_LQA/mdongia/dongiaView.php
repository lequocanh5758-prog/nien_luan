<?php
require_once './elements_LQA/mod/dongiaCls.php';
require_once './elements_LQA/mod/hanghoaCls.php';

// Hiển thị thông báo nếu có
if (isset($_SESSION['dongia_message'])) {
    $message = $_SESSION['dongia_message'];
    $success = isset($_SESSION['dongia_success']) ? $_SESSION['dongia_success'] : false;
    $alertClass = $success ? 'alert-success' : 'alert-danger';
    echo '<div class="alert ' . $alertClass . '" role="alert">' . htmlspecialchars($message) . '</div>';
    unset($_SESSION['dongia_message']);
    unset($_SESSION['dongia_success']);
}

try {
    $lhobj = new Dongia();
    $list_lh = $lhobj->DongiaGetAll();
    $l = count($list_lh);
} catch (Exception $e) {
    $list_lh = [];
    $l = 0;
}

$hhobj = new Hanghoa();
$list_hh = $hhobj->HanghoaGetAll();
if (empty($list_hh)) {
    $list_hh = [];
}
?>

<style>
.alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
.alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
.alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
.btn-apply { 
    background: #28a745 !important; 
    color: white !important; 
    padding: 10px 20px; 
    border: none; 
    border-radius: 5px; 
    cursor: pointer; 
    font-weight: bold;
    font-size: 14px;
    margin: 2px;
}
.btn-apply:hover { background: #218838 !important; transform: translateY(-1px); }
.btn-active { 
    background: #6c757d !important; 
    color: white !important; 
    padding: 10px 20px; 
    border: none; 
    border-radius: 5px;
    font-weight: bold;
}
.btn-delete {
    background: #dc3545 !important;
    color: white !important;
    padding: 8px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin: 2px;
}
.btn-delete:hover { background: #c82333 !important; }
.price-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
.price-table th { background: #007bff; color: white; padding: 12px 8px; text-align: center; }
.price-table td { padding: 12px 8px; border-bottom: 1px solid #dee2e6; vertical-align: middle; }
.price-table tr:hover { background-color: rgba(0, 123, 255, 0.05); }
.form-section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
</style>

<div class="admin-title">
    <h2>🏷️ Quản lý đơn giá - Có thể chuyển đổi giữa các giá cũ</h2>
</div>

<div class="form-section">
    <h3>➕ Thêm đơn giá mới</h3>
    
    <form method="post" action='./elements_LQA/mdongia/dongiaAct.php?reqact=addnew'>
        <table>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Chọn hàng hóa:</td>
                <td style="padding: 8px;">
                    <select name="idhanghoa" required style="width: 300px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="">-- Chọn hàng hóa --</option>
                        <?php foreach ($list_hh as $h): ?>
                            <option value="<?php echo $h->idhanghoa; ?>"><?php echo htmlspecialchars($h->tenhanghoa); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Giá bán:</td>
                <td style="padding: 8px;">
                    <input type="number" name="giaban" required style="width: 200px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" placeholder="VD: 100000">
                </td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Ngày áp dụng:</td>
                <td style="padding: 8px;">
                    <input type="date" name="ngayapdung" required value="<?php echo date('Y-m-d'); ?>" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                </td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Ngày kết thúc:</td>
                <td style="padding: 8px;">
                    <input type="date" name="ngayketthuc" required value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                </td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Ghi chú:</td>
                <td style="padding: 8px;">
                    <input type="text" name="ghichu" style="width: 300px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" placeholder="Ghi chú (tùy chọn)">
                </td>
            </tr>
            <tr>
                <td></td>
                <td style="padding: 8px;">
                    <input type="submit" value="➕ TẠO ĐƠN GIÁ MỚI" style="background: #007bff; color: white; padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 16px;">
                </td>
            </tr>
        </table>
    </form>
</div>

<hr style="margin: 30px 0;">

<div>
    <h3>📋 Danh sách đơn giá (Tổng: <?php echo $l; ?> đơn giá)</h3>
    
    <?php if ($l > 0): ?>
        <table class="price-table">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th style="width: 200px;">SẢN PHẨM</th>
                    <th style="width: 120px;">GIÁ BÁN</th>
                    <th style="width: 150px;">THỜI GIAN ÁP DỤNG</th>
                    <th style="width: 100px;">TRẠNG THÁI</th>
                    <th style="width: 200px;">🎯 THAO TÁC</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list_lh as $u): ?>
                    <?php 
                    $isActive = $u->apDung;
                    $isExpired = strtotime($u->ngayKetThuc) < time();
                    $rowStyle = $isActive ? 'background-color: rgba(40, 167, 69, 0.1); border-left: 4px solid #28a745;' : '';
                    ?>
                    <tr style="<?php echo $rowStyle; ?>">
                        <td style="text-align: center;">
                            <strong style="font-size: 16px;"><?php echo $u->idDonGia; ?></strong>
                        </td>
                        <td>
                            <div>
                                <strong style="color: #007bff; font-size: 15px;"><?php echo htmlspecialchars($u->tenhanghoa); ?></strong><br>
                                <small style="color: #6c757d;">ID sản phẩm: <?php echo $u->idHangHoa; ?></small>
                            </div>
                        </td>
                        <td style="text-align: center;">
                            <div style="font-size: 18px; font-weight: bold; color: #28a745;">
                                <?php echo number_format($u->giaBan, 0, ',', '.'); ?>đ
                            </div>
                        </td>
                        <td style="text-align: center;">
                            <div>
                                <small><strong>Từ:</strong> <?php echo date('d/m/Y', strtotime($u->ngayApDung)); ?></small><br>
                                <small><strong>Đến:</strong> <?php echo date('d/m/Y', strtotime($u->ngayKetThuc)); ?></small>
                            </div>
                        </td>
                        <td style="text-align: center;">
                            <?php if ($isActive): ?>
                                <div style="background: #28a745; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block;">
                                    ✅ ĐANG ÁP DỤNG
                                </div>
                            <?php elseif ($isExpired): ?>
                                <div style="background: #dc3545; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block;">
                                    ⏰ ĐÃ HẾT HẠN
                                </div>
                            <?php else: ?>
                                <div style="background: #ffc107; color: #212529; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block;">
                                    ⏸️ CHƯA ÁP DỤNG
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <?php if (!$isExpired): ?>
                                <?php if (!$isActive): ?>
                                    <!-- NÚT ÁP DỤNG - TÍNH NĂNG CHÍNH -->
                                    <button onclick="applyPrice(<?php echo $u->idDonGia; ?>, '<?php echo htmlspecialchars($u->tenhanghoa); ?>', <?php echo $u->giaBan; ?>)" 
                                            class="btn-apply" 
                                            title="Nhấn để áp dụng đơn giá này"
                                            style="background: #28a745 !important; color: white !important; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 14px;">
                                        🎯 ÁP DỤNG NGAY
                                    </button>
                                <?php else: ?>
                                    <span class="btn-active" style="background: #6c757d !important; color: white !important; padding: 10px 20px; border: none; border-radius: 5px; font-weight: bold;">
                                        ✅ ĐANG DÙNG
                                    </span>
                                <?php endif; ?>
                                <br>
                                <!-- NÚT XÓA -->
                                <button onclick="deletePrice(<?php echo $u->idDonGia; ?>)" 
                                        class="btn-delete" 
                                        title="Xóa đơn giá này"
                                        style="background: #dc3545 !important; color: white !important; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; margin-top: 5px;">
                                    🗑️ XÓA
                                </button>
                            <?php else: ?>
                                <span style="color: #6c757d; font-style: italic;">⏰ Đã hết hạn</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="text-align: center; padding: 60px; background: #f8f9fa; border-radius: 8px; color: #6c757d;">
            <div style="font-size: 48px; margin-bottom: 20px;">📋</div>
            <h4>Chưa có đơn giá nào</h4>
            <p>Hãy tạo đơn giá đầu tiên bằng form ở trên!</p>
        </div>
    <?php endif; ?>
</div>

<script>
function applyPrice(idDonGia, tenSanPham, giaBan) {
    const giaFormatted = new Intl.NumberFormat('vi-VN').format(giaBan);
    
    const confirmMessage = `🎯 XÁC NHẬN ÁP DỤNG ĐƠN GIÁ

📦 Sản phẩm: ${tenSanPham}
💰 Giá mới: ${giaFormatted}đ

⚠️ LƯU Ý QUAN TRỌNG:
• Đơn giá hiện tại sẽ bị thay thế
• Giá tham khảo sẽ được cập nhật
• Có thể ảnh hưởng đến báo cáo doanh thu

❓ Bạn có chắc chắn muốn áp dụng đơn giá này không?`;
    
    if (confirm(confirmMessage)) {
        // Hiển thị loading
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '⏳ ĐANG XỬ LÝ...';
        btn.disabled = true;
        btn.style.background = '#6c757d !important';
        
        fetch('./elements_LQA/mdongia/dongiaSwitch.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'switch_price',
                idDonGia: idDonGia
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`✅ THÀNH CÔNG!

${data.message}

🔄 Trang sẽ được tải lại để cập nhật thông tin mới.`);
                location.reload();
            } else {
                alert(`❌ THẤT BẠI!

Lỗi: ${data.message || 'Có lỗi xảy ra khi áp dụng đơn giá'}

🔄 Vui lòng thử lại hoặc liên hệ quản trị viên.`);
                btn.innerHTML = originalText;
                btn.disabled = false;
                btn.style.background = '#28a745 !important';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(`❌ LỖI KẾT NỐI!

Không thể kết nối đến server.
Chi tiết lỗi: ${error.message}

🔄 Vui lòng kiểm tra kết nối mạng và thử lại.`);
            btn.innerHTML = originalText;
            btn.disabled = false;
            btn.style.background = '#28a745 !important';
        });
    }
}

function deletePrice(idDonGia) {
    const confirmMessage = `🗑️ XÁC NHẬN XÓA ĐƠN GIÁ

⚠️ CẢNH BÁO:
• Hành động này không thể hoàn tác
• Đơn giá sẽ bị xóa vĩnh viễn khỏi hệ thống
• Nếu đây là đơn giá đang áp dụng, hệ thống sẽ tự động chọn đơn giá khác

❓ Bạn có chắc chắn muốn xóa đơn giá này không?`;
    
    if (confirm(confirmMessage)) {
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '⏳ ĐANG XÓA...';
        btn.disabled = true;
        
        fetch('./elements_LQA/mdongia/dongiaAct.php?reqact=deletedongia', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'idDonGia=' + encodeURIComponent(idDonGia)
        })
        .then(response => response.text())
        .then(data => {
            alert('✅ XÓA THÀNH CÔNG!\n\nĐơn giá đã được xóa khỏi hệ thống.\n\n🔄 Trang sẽ được tải lại.');
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ LỖI!\n\nCó lỗi xảy ra khi xóa đơn giá.\n\n🔄 Vui lòng thử lại.');
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
}

// Thêm hiệu ứng hover cho các nút
document.addEventListener('DOMContentLoaded', function() {
    // Hiệu ứng hover cho nút áp dụng
    const applyButtons = document.querySelectorAll('.btn-apply');
    applyButtons.forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 4px 8px rgba(40, 167, 69, 0.3)';
        });
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
        });
    });
    
    // Hiệu ứng hover cho nút xóa
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-1px)';
            this.style.boxShadow = '0 2px 4px rgba(220, 53, 69, 0.3)';
        });
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
        });
    });
});
</script>