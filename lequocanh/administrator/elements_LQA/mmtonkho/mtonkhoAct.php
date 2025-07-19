<?php
// Use SessionManager for safe session handling
require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

// Start session safely
SessionManager::start();
require_once '../mod/mtonkhoCls.php';

$tonkho = new MTonKho();

if (isset($_GET['reqact'])) {
    $reqact = $_GET['reqact'];

    switch ($reqact) {
        case 'update':
            // Cập nhật thông tin tồn kho
            if (isset($_POST['idTonKho']) && isset($_POST['soLuong']) && isset($_POST['soLuongToiThieu'])) {
                $idTonKho = $_POST['idTonKho'];
                $soLuong = $_POST['soLuong'];
                $soLuongToiThieu = $_POST['soLuongToiThieu'];
                $viTri = isset($_POST['viTri']) ? $_POST['viTri'] : '';

                // Lấy thông tin tồn kho hiện tại để giữ nguyên số lượng
                $currentTonKho = $tonkho->getTonKhoById($idTonKho);
                if ($currentTonKho) {
                    // Sử dụng số lượng hiện tại thay vì số lượng từ form
                    $soLuong = $currentTonKho->soLuong;
                }

                $result = $tonkho->updateTonKho($idTonKho, $soLuong, $soLuongToiThieu, $viTri);

                if ($result) {
                    header("Location: ../../index.php?req=mtonkho&result=success");
                } else {
                    header("Location: ../../index.php?req=mtonkho&result=fail");
                }
            } else {
                header("Location: ../../index.php?req=mtonkho&result=fail");
            }
            break;

        default:
            header("Location: ../../index.php?req=mtonkho");
            break;
    }
} else {
    header("Location: ../../index.php?req=mtonkho");
}
