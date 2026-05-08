<?php
require_once __DIR__ . '/../mod/thuoctinhCls.php';

$debug = [];
$debug['POST'] = $_POST;
$debug['GET'] = $_GET;
$debug['REQUEST'] = $_REQUEST;

$idThuocTinh = $_POST['idThuocTinh'] ?? $_GET['idThuocTinh'] ?? $_REQUEST['idThuocTinh'] ?? $_REQUEST['id'] ?? $_GET['id'] ?? null;

if (!$idThuocTinh) {
    echo json_encode([
        'success' => false,
        'message' => "Không tìm thấy ID thuộc tính",
        'debug' => $debug
    ]);
    exit;
}

$thuocTinhObj = new ThuocTinh();
$item = $thuocTinhObj->thuoctinhGetbyId($idThuocTinh);

if (!$item) {
    echo json_encode([
        'success' => false,
        'message' => "Không tìm thấy thuộc tính với ID: " . htmlspecialchars($idThuocTinh),
        'debug' => $debug
    ]);
    exit;
}
?>

<div class="update-form">
    <button id="close-btn-tt" class="close-btn" type="button">×</button>
    <h3>Cập nhật thuộc tính</h3>
    <form name="updatethuoctinh" id="update-form-tt" method="post" enctype="multipart/form-data">
        <input type="hidden" name="idThuocTinh" value="<?php echo $item->idThuocTinh; ?>" />
        <input type="hidden" name="hinhanh" value="<?php echo $item->hinhanh; ?>" />

        <div class="form-group">
            <label>ID:</label>
            <div><?php echo htmlspecialchars($idThuocTinh); ?></div>
        </div>

        <div class="form-group">
            <label>Tên Thuộc Tính:</label>
            <input type="text" class="form-control" name="tenThuocTinh" value="<?php echo htmlspecialchars($item->tenThuocTinh); ?>" required />
        </div>

        <div class="form-group">
            <label>Ghi Chú:</label>
            <input type="text" class="form-control" name="ghiChu" value="<?php echo htmlspecialchars($item->ghiChu); ?>" />
        </div>

        <div class="form-group">
            <label>Hình ảnh hiện tại:</label>
            <?php if ($item->hinhanh): ?>
                <div class="image-preview">
                    <img src="data:image/png;base64,<?php echo $item->hinhanh; ?>" alt="Current image" class="img-thumbnail">
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Chọn hình ảnh mới (nếu muốn thay đổi):</label>
            <input type="file" class="form-control" name="fileimage" accept="image/*" />
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Cập nhật
            </button>
            <button type="button" id="cancel-btn-tt" class="btn btn-secondary">
                <i class="fas fa-times"></i> Hủy
            </button>
        </div>
    </form>
</div>

<style>
    .update-form { padding: 10px; }
    .update-form h3 { margin-top: 0; margin-bottom: 15px; color: #333; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 5px; }
    .form-control { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
    .image-preview { margin-top: 5px; }
    .image-preview img { max-width: 150px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px; }
    .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; margin-right: 8px; }
    .btn-primary { background: #007bff; color: white; }
    .btn-secondary { background: #6c757d; color: white; }
    .btn:hover { opacity: 0.9; }
    .close-btn { position: absolute; top: 10px; right: 10px; background: #dc3545; color: white; border: none; width: 30px; height: 30px; border-radius: 50%; font-size: 18px; cursor: pointer; line-height: 30px; text-align: center; }
</style>

<script>
(function() {
    var $form = $('#update-form-tt');
    var $popup = $('#w_update_tt');

    // Đóng popup
    $('#close-btn-tt, #cancel-btn-tt').on('click', function() {
        $popup.hide();
    });

    // Xử lý submit form
    $form.on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);

        $.ajax({
            url: './elements_LQA/mthuoctinh/thuoctinhAct.php?reqact=updatethuoctinh',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    alert(response.message || 'Cập nhật thành công!');
                    $popup.hide();
                    // Reload trang để cập nhật danh sách
                    window.location.reload();
                } else {
                    alert(response && response.message ? response.message : 'Cập nhật thất bại!');
                }
            },
            error: function(xhr, status, error) {
                console.error('Update error:', error);
                try {
                    var resp = JSON.parse(xhr.responseText);
                    alert(resp.message || 'Lỗi khi cập nhật!');
                } catch(e) {
                    alert('Lỗi kết nối hoặc server trả về dữ liệu không hợp lệ.');
                }
            }
        });
    });
})();
</script>
