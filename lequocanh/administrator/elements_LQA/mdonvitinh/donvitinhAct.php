<?php
// Use SessionManager for safe session handling
require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

// Start session safely
SessionManager::start();
require '../../elements_LQA/mod/donvitinhCls.php';
require_once '../../elements_LQA/mod/database.php';

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

// Nếu có biến yêu cầu đúng tên biến thì vào, nếu không đẩy về index.php ngăn truy cập mục đích không rõ ràng
if (isset($_GET['reqact'])) {
    $requestAction = $_GET['reqact'];
    switch ($requestAction) {
        case 'addnew': // Thêm mới
            // Nhập dữ liệu
            $tenDonViTinh = isset($_REQUEST['tenDonViTinh']) ? $_REQUEST['tenDonViTinh'] : null;
            $moTa = isset($_REQUEST['moTa']) ? $_REQUEST['moTa'] : null;
            $ghiChu = isset($_REQUEST['ghiChu']) ? $_REQUEST['ghiChu'] : null;

            $lh = new DonViTinh();
            $kq = $lh->donvitinhAdd($tenDonViTinh, $moTa, $ghiChu); // Cập nhật tham số cho phù hợp

            // Check if it's an AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                sendJsonResponse($kq, $kq ? 'Thêm đơn vị tính thành công' : 'Thêm đơn vị tính thất bại');
            } else {
                // Redirect for regular form submit
                header('location: ../../index.php?req=donvitinhview&result=' . ($kq ? 'ok' : 'notok'));
            }
            break;

        case 'deletedonvitinh':
            $iddonvitinh = $_REQUEST['iddonvitinh'];
            $lh = new DonViTinh();
            $kq = $lh->donvitinhDelete($iddonvitinh);

            // Check if it's an AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                sendJsonResponse($kq, $kq ? 'Xóa đơn vị tính thành công' : 'Xóa đơn vị tính thất bại');
            } else {
                // Redirect for regular form submit
                header('location: ../../index.php?req=donvitinhview&result=' . ($kq ? 'ok' : 'notok'));
            }
            break;

        case 'updatedonvitinh':
            $idDonViTinh = isset($_REQUEST['idDonViTinh']) ? $_REQUEST['idDonViTinh'] : null;
            $tenDonViTinh = isset($_REQUEST['tenDonViTinh']) ? $_REQUEST['tenDonViTinh'] : null;
            $moTa = isset($_REQUEST['moTa']) ? $_REQUEST['moTa'] : null;
            $ghiChu = isset($_REQUEST['ghiChu']) ? $_REQUEST['ghiChu'] : null;

            if ($idDonViTinh) {
                $debugInfo = [
                    'idDonViTinh' => $idDonViTinh,
                    'tenDonViTinh' => $tenDonViTinh,
                    'moTa' => $moTa,
                    'ghiChu' => $ghiChu
                ];

                // Debug query
                try {
                    $db = Database::getInstance()->getConnection();
                    $stmt = $db->prepare("SELECT * FROM donvitinh WHERE idDonViTinh = ? LIMIT 1");
                    $stmt->execute([$idDonViTinh]);
                    $beforeUpdate = $stmt->fetch(PDO::FETCH_ASSOC);
                    $debugInfo['before_update'] = $beforeUpdate;

                    // Kiểm tra cấu trúc bảng
                    $tableInfo = $db->query("DESCRIBE donvitinh")->fetchAll(PDO::FETCH_ASSOC);
                    $debugInfo['table_structure'] = $tableInfo;
                } catch (Exception $e) {
                    $debugInfo['db_error'] = $e->getMessage();
                }

                $lh = new DonViTinh();
                $kq = $lh->donvitinhUpdate($tenDonViTinh, $moTa, $ghiChu, $idDonViTinh);
                $debugInfo['update_result'] = $kq;

                // Check after update
                try {
                    $stmt = $db->prepare("SELECT * FROM donvitinh WHERE idDonViTinh = ? LIMIT 1");
                    $stmt->execute([$idDonViTinh]);
                    $afterUpdate = $stmt->fetch(PDO::FETCH_ASSOC);
                    $debugInfo['after_update'] = $afterUpdate;
                } catch (Exception $e) {
                    $debugInfo['after_update_error'] = $e->getMessage();
                }

                // Always send JSON for updatedonvitinh
                sendJsonResponse(true, 'Cập nhật đơn vị tính thành công', $debugInfo);
            } else {
                sendJsonResponse(false, 'Không tìm thấy ID đơn vị tính');
            }
            break;

        default:
            sendJsonResponse(false, 'Yêu cầu không hợp lệ');
            break;
    }
} else {
    sendJsonResponse(false, 'Yêu cầu không hợp lệ');
}
