<?php
require_once __DIR__ . '/../mod/auth_check.php';
require_once __DIR__ . '/../../../includes/csrf_helper.php';
require_once __DIR__ . '/../mod/loaihangCls.php';

$debug = [];
$debug['POST'] = $_POST;
$debug['GET'] = $_GET;
$debug['REQUEST'] = $_REQUEST;

$idloaihang = isset($_POST['idloaihang']) ? $_POST['idloaihang'] : (isset($_GET['idloaihang']) ? $_GET['idloaihang'] : (isset($_REQUEST['idloaihang']) ? $_REQUEST['idloaihang'] : null));

$debug['ID detected'] = $idloaihang;

if (isset($_GET['debug']) || isset($_POST['debug'])) {
    echo "<pre>";
    print_r($debug);
    echo "</pre>";
}

if (!$idloaihang) {
    echo json_encode([
        'success' => false,
        'message' => "Không tìm thấy ID loại hàng",
        'debug' => $debug
    ]);
    exit;
}

$lhobj = new loaihang();
$getLhUpdate = $lhobj->LoaihangGetbyId($idloaihang);

if (!$getLhUpdate) {
    echo json_encode([
        'success' => false,
        'message' => "Không tìm thấy loại hàng với ID: " . htmlspecialchars($idloaihang),
        'debug' => $debug
    ]);
    exit;
}
?>

<div class="update-form">
    <h3>Cập nhật loại hàng</h3>
    <form name="updateloaihang" id="formupdatelh" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="idloaihang" value="<?php echo $getLhUpdate->idloaihang; ?>" />
        <input type="hidden" name="hinhanh" value="<?php echo $getLhUpdate->hinhanh; ?>" />

        <div class="form-group">
            <label>ID:</label>
            <div><?php echo htmlspecialchars($idloaihang); ?></div>
        </div>

        <div class="form-group">
            <label>Tên loại hàng:</label>
            <input type="text" name="tenloaihang" value="<?php echo htmlspecialchars($getLhUpdate->tenloaihang); ?>" required />
        </div>

        <div class="form-group">
            <label>Mô tả:</label>
            <input type="text" name="mota" value="<?php echo htmlspecialchars($getLhUpdate->mota); ?>" />
        </div>

        <div class="form-group">
            <label>Hình ảnh hiện tại:</label>
            <div class="current-image">
                <img width="150" src="data:image/png;base64,<?php echo $getLhUpdate->hinhanh ?>" alt="Current image">
            </div>
            <label>Chọn hình ảnh mới (nếu muốn thay đổi):</label>
            <input type="file" name="fileimage" id="fileimage" accept="image/*" class="form-control">
        </div>

        <div class="form-group" style="text-align: center; margin-top: 20px;">
            <button type="submit" class="btn btn-primary" id="btn-submit-lh">
                <i class="fas fa-save"></i> Lưu cập nhật
            </button>
        </div>
    </form>
</div>

<style>
    .update-form .form-group {
        margin-bottom: 15px;
    }
    .update-form .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    .update-form .form-group input[type="text"] {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }
    .update-form .form-group input[type="file"] {
        padding: 6px 0;
    }
    .update-form .current-image img {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 4px;
        background: #fff;
    }
    .update-form .btn-primary {
        background-color: #007bff;
        color: #fff;
        border: none;
        padding: 10px 30px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 15px;
    }
    .update-form .btn-primary:hover {
        background-color: #0056b3;
    }
</style>