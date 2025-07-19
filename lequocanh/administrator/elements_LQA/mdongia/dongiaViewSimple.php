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
.btn-apply { background: #28a745 !important; color: white !important; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
.btn-apply:hover { background: #218838 !important; }
.btn-active { background: #6c757d !important; color: white !important; padding: 8px 15px; border: none; border-radius: 4px; }
.price-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
.price-table th { background: #007bff; color: white; padding: 12px 8px; text-align: center; }
.price-table td { padding: 10px 8px; border-bottom: 1px solid #dee2e6; vertical-align: middle; }
.price-table tr:hover { background-color: rgba(0, 123, 255, 0.05); }
</style>

<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
    <h3>🏷️ Quản lý đơn giá - Có thể chuyển đổi giữa các giá cũ</h3>
    
    <form method="post" action='./elements_LQA/mdongia/dongiaAct.php?reqact=addnew'>
        <table>
            <tr>
                <td>Chọn hàng hóa:</td>
                <td>
                    <select name="idhanghoa" required style="width: 300px; padding: 5px;">
                        <option value="">-- Chọn hàng hóa --</option>
                        <?php foreach ($list_hh as $h): ?>
                            <option value="<?php echo $h->idhanghoa; ?>"><?php echo htmlspecialchars($h->tenhanghoa); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Giá bán:</td>
                <td><input type="number" name="giaban" required style="width: 200px; padding: 5px;" placeholder="VD: 100000"></td>
            </tr>
            <tr>
                <td>Ngày áp dụng:</td>
                <td><input type="date" name="ngayapdung" required value="<?php echo date('Y-m-d'); ?>" style="padding: 5px;"></td>
            </tr>
            <tr>
                <td>Ngày kết thúc:</td>
                <td><input type="date" name="ngayketthuc" required value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>" style="padding: 5px;"></td>
            </tr>
            <tr>
                <td>Ghi chú:</td>
                <td><input type="text" name="ghichu" style="width: 300px; padding: 5px;" placeholder="Ghi chú (tùy chọn)"></td>
            </tr>
            <tr>
                <td></td>
                <td><input type="submit" value="➕ Tạo đơn giá mới" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;"></td>
            </tr>
        </table>
    </form>
</div>

<hr>

<div>
    <h4>📋 Danh sách đơn giá (<?php echo $l; ?> đơn giá)</h4>
    
    <table class="price-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Sản phẩm</th>
                <th>Giá bán</th>
                <th>Thời gian áp dụng</th>
                <th>Trạng thái</th>
                <th>🎯 THAO TÁC</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($l > 0): ?>
                <?php foreach ($list_lh as $u): ?>
                    <?php 
                    $isActive = $u->apDung;
                    $isExpired = strtotime($u->ngayKetThuc) < time();
                    ?>
                    <tr style="<?php echo $isActive ? 'background-color: rgba(40, 167, 69, 0.1);' : ''; ?>">
                        <td><strong><?php echo $u->idDonGia; ?></strong></td>
                        <td>
                            <strong><?php echo htmlspecialchars($u->tenhanghoa); ?></strong><br>
                            <small>ID: <?php echo $u->idHangHoa; ?></small>
                        </td>
                        <td style="font-size: 1.1em; font-weight: bold; color: #28a745;">
                            <?php echo number_format($u->giaBan, 0, ',', '.'); ?> đ
                        </td>
                        <td>
                            <small>Từ: <?php echo date('d/m/Y', strtotime($u->ngayApDung)); ?></small><br>
                            <small>Đến: <?php echo date('d/m/Y', strtotime($u->ngayKetThuc)); ?></small>
                        </td>
                        <td>
                            <?php if ($isActive): ?>
                                <span style="background: #28a745; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.9em;">
                                    ✅ ĐANG ÁP DỤNG
                                </span>
                            <?php elseif ($isExpired): ?>
                                <span style="background: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.9em;">
                                    ⏰ ĐÃ HẾT HẠN
                                </span>
                            <?php else: ?>
                                <span style="background: #ffc107; color: #212529; padding: 4px 8px; border-radius: 4px; font-size: 0.9em;">
                                    ⏸️ CHƯA ÁP DỤNG
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$isExpired): ?>
                                <?php if (!$isActive): ?>
                                    <!-- NÚT ÁP DỤNG - TÍNH NĂNG CHÍNH -->
                                    <button onclick="applyPrice(<?php echo $u->idDonGia; ?>, '<?php echo htmlspecialchars($u->tenhanghoa); ?>', <?php echo $u->giaBan; ?>)" 
                                            class="btn-apply" title="Nhấn để áp dụng đơn giá này">
                                        🎯 ÁP DỤNG NGAY
                                    </button>
                                <?php else: ?>
                                    <span class="btn-active">✅ ĐANG DÙNG</span>
                                <?php endif; ?>
                                
                                <!-- NÚT XÓA -->
                                <button onclick="deletePrice(<?php echo $u->idDonGia; ?>)" 
                                        style="background: #dc3545; color: white; padding: 6px 10px; border: none; border-radius: 4px; cursor: pointer; margin-left: 5px;"
                                        title="Xóa đơn giá này">
                                    🗑️ XÓA
                                </button>
                            <?php else: ?>
                                <span style="color: #6c757d;">⏰ Đã hết hạn</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: #6c757d;">
                        📋 Chưa có đơn giá nào. Hãy tạo đơn giá đầu tiên!
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function applyPrice(idDonGia, tenSanPham, giaBan) {
    const giaFormatted = new Intl.NumberFormat('vi-VN').format(giaBan);
    
    if (confirm(`🎯 XÁC NHẬN ÁP DỤNG ĐỚN GIÁ\n\nSản phẩm: ${tenSanPham}\nGiá mới: ${giaFormatted}đ\n\n⚠️ LưU Ý: Đơn giá hiện tại sẽ bị thay thế!\n\nBạn có chắc chắn muốn áp dụng?`)) {
        
        // Hiển thị loading
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '⏳ Đang xử lý...';
        btn.disabled = true;
        
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
                alert('✅ THÀNH CÔNG!\n\n' + data.message + '\n\nTrang sẽ được tải lại để cập nhật.');
                location.reload();
            } else {
                alert('❌ LỖI!\n\n' + (data.message || 'Có lỗi xảy ra khi áp dụng đơn giá'));
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ LỖI KẾT NỐI!\n\nKhông thể kết nối đến server. Vui lòng thử lại.');
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
}

function deletePrice(idDonGia) {
    if (confirm('🗑️ XÁC NHẬN XÓA\n\nBạn có chắc chắn muốn xóa đơn giá này?\n\n⚠️ Hành động này không thể hoàn tác!')) {
        fetch('./elements_LQA/mdongia/dongiaAct.php?reqact=deletedongia', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'idDonGia=' + encodeURIComponent(idDonGia)
        })
        .then(response => response.text())
        .then(data => {
            alert('✅ Xóa đơn giá thành công!');
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Có lỗi xảy ra khi xóa đơn giá');
        });
    }
}
</script>