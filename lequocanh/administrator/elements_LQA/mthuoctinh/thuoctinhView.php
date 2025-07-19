<div class="admin-title">Quản lý thuộc tính</div>
<hr>
<?php
require './elements_LQA/mod/thuoctinhCls.php';

$lhobj = new ThuocTinh();
$list_lh = $lhobj->thuoctinhGetAll();
$l = count($list_lh);

// Hiển thị thông báo kết quả
if (isset($_GET['result'])) {
    if ($_GET['result'] == 'ok') {
        echo '<div class="alert alert-success">';
        if (isset($_GET['message'])) {
            echo '<strong>Thành công!</strong> ' . htmlspecialchars(urldecode($_GET['message']));
        } else {
            echo '<strong>Thành công!</strong> Thao tác đã được thực hiện.';
        }
        echo '</div>';
    } else if ($_GET['result'] == 'notok') {
        echo '<div class="alert alert-danger">';

        // Xử lý lỗi foreign key constraint
        if (isset($_GET['error_type']) && $_GET['error_type'] == 'foreign_key_constraint') {
            echo '<div class="foreign-key-error">';
            echo '<h4><i class="fas fa-exclamation-triangle"></i> Không thể xóa thuộc tính</h4>';

            if (isset($_GET['message'])) {
                echo '<p><strong>Lý do:</strong> ' . htmlspecialchars(urldecode($_GET['message'])) . '</p>';
            }

            if (isset($_GET['related_tables'])) {
                $relatedTables = json_decode(urldecode($_GET['related_tables']), true);
                if (!empty($relatedTables)) {
                    echo '<div class="related-data-info">';
                    echo '<h5>📋 Dữ liệu liên quan:</h5>';
                    echo '<ul>';
                    foreach ($relatedTables as $table) {
                        echo '<li>';
                        echo '<strong>' . htmlspecialchars($table['display_name']) . ':</strong> ';
                        echo htmlspecialchars($table['description']);
                        echo '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                }
            }

            if (isset($_GET['suggested_action'])) {
                echo '<div class="suggested-action">';
                echo '<h5>💡 Hướng dẫn khắc phục:</h5>';
                echo '<p>' . htmlspecialchars(urldecode($_GET['suggested_action'])) . '</p>';
                echo '</div>';
            }

            echo '<div class="action-steps">';
            echo '<h5>🔧 Các bước thực hiện:</h5>';
            echo '<ol>';
            echo '<li>Vào quản lý "Thuộc tính hàng hóa" và xóa tất cả thuộc tính sử dụng thuộc tính này</li>';
            echo '<li>Sau đó quay lại và thử xóa thuộc tính này lại</li>';
            echo '<li>Hoặc liên hệ quản trị viên để được hỗ trợ</li>';
            echo '</ol>';
            echo '</div>';
            echo '</div>';
        } else {
            // Xử lý các lỗi khác
            if (isset($_GET['message'])) {
                echo '<strong>Lỗi!</strong> ' . htmlspecialchars(urldecode($_GET['message']));
            } else {
                echo '<strong>Lỗi!</strong> Thao tác thất bại. Vui lòng thử lại.';
            }
        }
        echo '</div>';
    }
}
?>

<div class="admin-form">
    <h3>Thêm thuộc tính mới</h3>
    <form name="newthuoctinh" id="formaddthuoctinh" method="post"
        action='./elements_LQA/mthuoctinh/thuoctinhAct.php?reqact=addnew' enctype="multipart/form-data">
        <table>
            <tr>
                <td>Tên thuộc tính</td>
                <td><input type="text" name="tenThuocTinh" id="tenThuocTinh" required /></td>
            </tr>
            <tr>
                <td>Ghi Chú</td>
                <td><input type="text" name="ghiChu" /></td>
            </tr>
            <tr>
                <td>Hình ảnh</td>
                <td><input type="file" name="fileimage" required></td>
            </tr>
            <tr>
                <td><input type="submit" id="btnsubmit" value="Tạo mới" /></td>
                <td><input type="reset" value="Làm lại" /><b id="noteForm"></b></td>
            </tr>
        </table>
    </form>
</div>

<hr />
<div class="content_thuoctinh">
    <div class="admin-info">
        Tổng số thuộc tính: <b><?php echo $l; ?></b>
    </div>

    <table class="content-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên thuộc tính</th>
                <th>Ghi chú</th>
                <th>Hình ảnh</th>
                <th>Chức năng</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($l > 0) {
                foreach ($list_lh as $u) {
            ?>
                    <tr>
                        <td><?php echo htmlspecialchars($u->idThuocTinh); ?></td>
                        <td><?php echo htmlspecialchars($u->tenThuocTinh); ?></td>
                        <td><?php echo htmlspecialchars($u->ghiChu); ?></td>
                        <td align="center">
                            <img class="iconbutton" src="data:image/png;base64,<?php echo $u->hinhanh; ?>">
                        </td>
                        <td align="center">
                            <?php if (isset($_SESSION['ADMIN'])) { ?>
                                <a
                                    href="./elements_LQA/mthuoctinh/thuoctinhAct.php?reqact=deletethuoctinh&idThuocTinh=<?php echo $u->idThuocTinh; ?>">
                                    <img src="./elements_LQA/img_LQA/Delete.png" class="iconimg">
                                </a>
                            <?php } else { ?>
                                <img src="./elements_LQA/img_LQA/Delete.png" class="iconimg">
                            <?php } ?>
                            <img src="./elements_LQA/img_LQA/Update.png"
                                class="iconimg w_update_btn_open_tt"
                                data-id="<?php echo htmlspecialchars($u->idThuocTinh); ?>"
                                alt="Update">
                        </td>
                    </tr>
            <?php
                }
            }
            ?>
        </tbody>
    </table>
</div>

<!-- Container cho popup cập nhật thuộc tính -->
<div id="w_update_tt">
    <div id="w_close_btn_tt">×</div>
    <div id="w_update_form_tt"></div>
</div>

<style>
    .popup-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: none;
        z-index: 9990;
    }

    .iconimg {
        cursor: pointer;
        width: 24px;
        height: 24px;
    }

    /* Popup styles */
    #w_update_tt {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: #fff;
        border: 2px solid #3498db;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        border-radius: 8px;
        padding: 20px;
        z-index: 9999;
        display: none;
        width: 500px;
        max-height: 80vh;
        overflow-y: auto;
    }

    #w_close_btn_tt {
        position: absolute;
        top: 10px;
        right: 15px;
        background: #f44336;
        color: white;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        text-align: center;
        line-height: 30px;
        font-weight: bold;
        cursor: pointer;
        z-index: 10000;
        font-size: 18px;
    }

    #w_close_btn_tt:hover {
        background: #d32f2f;
    }

    /* Alert styles */
    .alert {
        padding: 15px;
        margin: 15px 0;
        border-radius: 5px;
        border: 1px solid transparent;
    }

    .alert-success {
        background-color: #d4edda;
        border-color: #c3e6cb;
        color: #155724;
    }

    .alert-danger {
        background-color: #f8d7da;
        border-color: #f5c6cb;
        color: #721c24;
    }

    /* Foreign key error styles */
    .foreign-key-error {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 20px;
        margin: 15px 0;
    }

    .foreign-key-error h4 {
        color: #856404;
        margin-bottom: 15px;
        font-size: 18px;
    }

    .foreign-key-error h5 {
        color: #856404;
        margin: 15px 0 10px 0;
        font-size: 14px;
        font-weight: bold;
    }

    .related-data-info {
        background: #f8f9fa;
        border-left: 4px solid #ffc107;
        padding: 15px;
        margin: 10px 0;
        border-radius: 4px;
    }

    .related-data-info ul {
        margin: 10px 0;
        padding-left: 20px;
    }

    .related-data-info li {
        margin: 8px 0;
        line-height: 1.4;
    }

    .suggested-action {
        background: #e7f3ff;
        border-left: 4px solid #007bff;
        padding: 15px;
        margin: 10px 0;
        border-radius: 4px;
    }

    .action-steps {
        background: #f0f9ff;
        border-left: 4px solid #17a2b8;
        padding: 15px;
        margin: 10px 0;
        border-radius: 4px;
    }

    .action-steps ol {
        margin: 10px 0;
        padding-left: 20px;
    }

    .action-steps li {
        margin: 8px 0;
        line-height: 1.4;
    }
</style>

<script>
    $(document).ready(function() {
        // Đảm bảo popup ẩn khi trang tải
        $("#w_update_tt").hide();

        // XÓA event handler cũ để tránh xung đột với jscript.js
        $(".w_update_btn_open_tt").off("click");

        // Xử lý sự kiện click nút update - sử dụng namespace để tránh xung đột
        $(".w_update_btn_open_tt").on("click.thuoctinhview", function(e) {
            e.preventDefault();
            e.stopPropagation();

            var id = $(this).data("id");
            console.log("=== thuoctinhView.php: Opening update form for ID:", id);

            if (!id) {
                alert("Không tìm thấy ID thuộc tính");
                return;
            }

            // Hiển thị popup
            console.log("thuoctinhView.php: Showing popup");
            $("#w_update_tt").show();

            // Load form update
            $("#w_update_form_tt").html('<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>');

            console.log("thuoctinhView.php: Making AJAX request");
            $.ajax({
                url: "./elements_LQA/mthuoctinh/thuoctinhUpdate.php",
                type: "POST",
                data: {
                    idThuocTinh: id
                },
                success: function(response) {
                    console.log("thuoctinhView.php: AJAX success");
                    $("#w_update_form_tt").html(response);
                },
                error: function(xhr, status, error) {
                    console.error("thuoctinhView.php: Error loading update form:", error);
                    console.error("thuoctinhView.php: Status:", status);
                    console.error("thuoctinhView.php: Response:", xhr.responseText);
                    $("#w_update_form_tt").html('<div style="color: red; padding: 20px;">Lỗi khi tải form cập nhật: ' + error + '</div>');
                }
            });
        });

        // Xử lý đóng popup
        $(document).on("click", "#w_close_btn_tt", function() {
            console.log("thuoctinhView.php: Close button clicked");
            $("#w_update_tt").hide();
        });

        // Đóng popup khi click bên ngoài
        $(document).on("click", "#w_update_tt", function(e) {
            if (e.target === this) {
                console.log("thuoctinhView.php: Clicked outside popup");
                $("#w_update_tt").hide();
            }
        });

        console.log("thuoctinhView.php: Event handlers initialized");
    });
</script>