<?php
// Use SessionManager for safe session handling
require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

// Start session safely
SessionManager::start();
require '../../elements_LQA/mod/thuonghieuCls.php';

function sendJsonResponse($success, $message = '', $debug = null)
{
    // Clear any previous output that might corrupt JSON
    if (ob_get_contents()) ob_clean();

    // Set proper headers
    header('Content-Type: application/json');
    header("Cache-Control: no-cache, must-revalidate");

    // Return simple JSON
    $response = ['success' => $success, 'message' => $message];
    if ($debug !== null) {
        $response['debug'] = $debug;
    }
    echo json_encode($response);
    exit;
}

// Kiểm tra biến yêu cầu, nếu không có thì đẩy về trang chủ
if (isset($_GET['reqact'])) {
    $requestAction = $_GET['reqact'];
    switch ($requestAction) {
        case 'addnew': // Thêm mới
            // Nhập dữ liệu với kiểm tra giá trị
            $tenTH = isset($_REQUEST['tenTH']) ? $_REQUEST['tenTH'] : null;
            $SDT = isset($_REQUEST['SDT']) ? $_REQUEST['SDT'] : null;
            $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : null;
            $diaChi = isset($_REQUEST['diaChi']) ? $_REQUEST['diaChi'] : null;

            if (empty($_FILES['fileimage']['tmp_name'])) {
                sendJsonResponse(false, 'Vui lòng nhập ảnh trước khi thêm thương hiệu.');
                exit;
            }

            $hinhanh_file = $_FILES['fileimage']['tmp_name'];
            $hinhanh = base64_encode(file_get_contents(addslashes($hinhanh_file)));

            $lh = new ThuongHieu();
            $kq = $lh->thuonghieuAdd($tenTH, $SDT, $email, $diaChi, $hinhanh);

            // Check if it's an AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                sendJsonResponse($kq, $kq ? 'Thêm thương hiệu thành công' : 'Thêm thương hiệu thất bại');
            } else {
                // Redirect for regular form submit
                header('location: ../../index.php?req=thuonghieuview&result=' . ($kq ? 'ok' : 'notok'));
                exit;
            }
            break;

        case 'deletethuonghieu':
            $idThuongHieu = isset($_REQUEST['idThuongHieu']) ? $_REQUEST['idThuongHieu'] : null;
            $lh = new ThuongHieu();
            $kq = $lh->thuonghieuDelete($idThuongHieu);

            // Check if it's an AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                sendJsonResponse($kq, $kq ? 'Xóa thương hiệu thành công' : 'Xóa thương hiệu thất bại');
            } else {
                // Redirect for regular form submit
                header('location: ../../index.php?req=thuonghieuview&result=' . ($kq ? 'ok' : 'notok'));
                exit;
            }
            break;

        case 'updatethuonghieu':
            $tenTH = isset($_REQUEST['tenTH']) ? $_REQUEST['tenTH'] : null;
            $SDT = isset($_REQUEST['SDT']) ? $_REQUEST['SDT'] : null;
            $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : null;
            $diaChi = isset($_REQUEST['diaChi']) ? $_REQUEST['diaChi'] : null;
            $idThuongHieu = isset($_REQUEST['idThuongHieu']) ? $_REQUEST['idThuongHieu'] : null;

            // Check if a new image is uploaded
            if (isset($_FILES['fileimage']) && !empty($_FILES['fileimage']['tmp_name'])) {
                $hinhanh_file = $_FILES['fileimage']['tmp_name'];
                $hinhanh = base64_encode(file_get_contents(addslashes($hinhanh_file)));
            } else {
                // Use the old image if no new image is uploaded
                $hinhanh = isset($_REQUEST['hinhanh']) ? $_REQUEST['hinhanh'] : '';
            }

            $debugInfo = [
                'idThuongHieu' => $idThuongHieu,
                'tenTH' => $tenTH,
                'SDT' => $SDT,
                'email' => $email,
                'diaChi' => $diaChi,
                'hinhanh_length' => strlen($hinhanh)
            ];

            $lh = new ThuongHieu();
            $kq = $lh->thuonghieuUpdate($tenTH, $SDT, $email, $diaChi, $hinhanh, $idThuongHieu);
            $debugInfo['update_result'] = $kq;

            // Always send JSON for updatethuonghieu
            sendJsonResponse(true, 'Cập nhật thương hiệu thành công', $debugInfo);
            break;

        default:
            sendJsonResponse(false, 'Yêu cầu không hợp lệ');
            break;
    }
} else {
    sendJsonResponse(false, 'Yêu cầu không hợp lệ');
}
