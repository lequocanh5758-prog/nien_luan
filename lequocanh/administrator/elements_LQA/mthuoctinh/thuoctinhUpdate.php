<?php
require_once __DIR__ . '/../mod/thuoctinhCls.php';

// Debugging - show all input
$debug = [];
$debug['POST'] = $_POST;
$debug['GET'] = $_GET;
$debug['REQUEST'] = $_REQUEST;

// Try to get ID from various sources - support both jscript.js and thuoctinhView.php
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
    <form name="updatethuoctinh" id="update-form" method="post">
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
            <input type="file" class="form-control" name="fileimage" accept="image/*">
        </div>

        <div class="form-actions">
            <input type="submit" id="btnsubmit" value="Cập nhật" class="btn-update" />
            <div id="noteForm" style="margin-top: 10px;"></div>
        </div>
    </form>
</div>

<style>
    .update-form {
        max-width: 100%;
        margin: 0;
        padding: 0;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .form-group input[type="text"],
    .form-group input[type="file"],
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .image-preview {
        margin: 10px 0;
    }

    .image-preview img {
        max-width: 100px;
        max-height: 100px;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 3px;
    }

    .form-actions {
        margin-top: 20px;
        text-align: center;
    }

    .btn-update {
        padding: 10px 20px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .btn-update:hover {
        background-color: #0056b3;
    }

    #noteForm {
        display: block;
        margin-top: 10px;
        color: #666;
    }
</style>

<script>
    $(document).ready(function() {
        // Close button event
        $("#close-btn-tt").on("click", function() {
            // Hide the popup
            $("#w_update_tt").hide();
        });

        // Xử lý submit form - sử dụng off() để tránh duplicate handlers
        $("#update-form").off("submit").on("submit", function(e) {
            e.preventDefault();

            console.log("=== FORM SUBMIT STARTED ===");

            // Hiển thị trạng thái đang xử lý
            $('#noteForm').html('<span style="color:blue">Đang xử lý...</span>');

            console.log("Form submitted");

            var formData = new FormData(this);

            // Debug: log form data
            console.log("Form data entries:");
            for (var pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

            console.log("About to send AJAX request...");

            $.ajax({
                url: "./elements_LQA/mthuoctinh/thuoctinhAct.php?reqact=updatethuoctinh",
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    console.log("=== AJAX SUCCESS ===");
                    console.log("Raw response:", response);
                    console.log("Response type:", typeof response);

                    // Kiểm tra nếu response là string, thử parse JSON
                    if (typeof response === 'string') {
                        console.log("Response is string, trying to parse JSON...");
                        try {
                            response = JSON.parse(response);
                            console.log("Parsed JSON:", response);
                        } catch (e) {
                            console.error("Failed to parse JSON response:", response);
                            console.error("Parse error:", e);
                            $('#noteForm').html('<span style="color:red">Lỗi: Server trả về dữ liệu không hợp lệ</span>');
                            return;
                        }
                    }

                    if (response && response.success) {
                        console.log("Update successful!");
                        $('#noteForm').html('<span style="color:green">Cập nhật thành công!</span>');

                        // Đóng popup sau 1 giây và reload trang
                        setTimeout(function() {
                            console.log("Closing popup and reloading...");
                            $("#w_update_tt").hide();
                            window.location.reload(true);
                        }, 1000);
                    } else {
                        console.log("Update failed:", response);
                        $('#noteForm').html('<span style="color:red">Lỗi: ' + (response && response.message ? response.message : 'Không thể cập nhật') + '</span>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("=== AJAX ERROR ===");
                    console.error("Error:", error);
                    console.error("Status:", status);
                    console.error("XHR Status Code:", xhr.status);
                    console.error("Response Text:", xhr.responseText);
                    console.error("Ready State:", xhr.readyState);

                    var errorMsg = 'Lỗi kết nối đến máy chủ';
                    if (xhr.status === 404) {
                        errorMsg = 'Không tìm thấy file xử lý (404)';
                        console.error("File not found - check path: ./elements_LQA/mthuoctinh/thuoctinhAct.php");
                    } else if (xhr.status === 500) {
                        errorMsg = 'Lỗi máy chủ (500): ' + (xhr.responseText || 'Không có thông tin chi tiết');
                        console.error("Server error - check PHP logs");
                    } else if (xhr.status === 0) {
                        errorMsg = 'Không thể kết nối đến máy chủ. Kiểm tra đường dẫn file.';
                        console.error("Network error or CORS issue");
                    } else if (xhr.responseText) {
                        errorMsg = 'Lỗi: ' + xhr.responseText.substring(0, 200);
                        console.error("Server returned error response");
                    }

                    $('#noteForm').html('<span style="color:red">' + errorMsg + '</span>');
                    console.error("=== END AJAX ERROR ===");
                }
            });
        });
    });
</script>