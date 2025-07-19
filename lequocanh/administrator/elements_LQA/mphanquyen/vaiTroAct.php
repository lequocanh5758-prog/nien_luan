<?php
// Bật hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Khởi tạo session nếu chưa có
if (session_status() == PHP_SESSION_NONE) {
    // Use SessionManager for safe session handling
    require_once __DIR__ . '/../mod/sessionManager.php';
    require_once __DIR__ . '/../config/logger_config.php';

    // Start session safely
    SessionManager::start();
}

// Kiểm tra quyền truy cập
require_once '../mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if (!isset($_SESSION['ADMIN']) && !$phanQuyen->isNhanVien($username)) {
    header('Location: ../../index.php');
    exit;
}

// Kết nối đến lớp Role
require_once '../mod/roleCls.php';
$roleObj = new Role();

// Xử lý các hành động
$reqact = isset($_GET['reqact']) ? $_GET['reqact'] : '';

switch ($reqact) {
    // Thêm vai trò mới
    case 'addnew':
        $role_name = isset($_POST['role_name']) ? trim($_POST['role_name']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';

        // Kiểm tra xem vai trò đã tồn tại chưa
        $existingRole = $roleObj->getRoleByName($role_name);
        if ($existingRole) {
            header('Location: ../../index.php?req=vaiTroView&result=exists');
            exit;
        }

        // Thêm vai trò mới
        $result = $roleObj->addRole($role_name, $description);

        if ($result) {
            header('Location: ../../index.php?req=vaiTroView&result=ok');
        } else {
            header('Location: ../../index.php?req=vaiTroView&result=failed');
        }
        break;

    // Cập nhật vai trò
    case 'update':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $role_name = isset($_POST['role_name']) ? trim($_POST['role_name']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';

        // Kiểm tra xem vai trò có tồn tại không
        $existingRole = $roleObj->getRoleById($id);
        if (!$existingRole) {
            header('Location: ../../index.php?req=vaiTroView&result=failed');
            exit;
        }

        // Kiểm tra xem tên vai trò mới đã tồn tại chưa (nếu đã thay đổi)
        if ($existingRole->ten_vai_tro != $role_name) {
            $checkRole = $roleObj->getRoleByName($role_name);
            if ($checkRole) {
                header('Location: ../../index.php?req=vaiTroView&result=exists');
                exit;
            }
        }

        // Không cho phép chỉnh sửa các vai trò mặc định
        if (in_array($existingRole->ten_vai_tro, ['admin', 'staff', 'customer']) && $existingRole->ten_vai_tro != $role_name) {
            header('Location: ../../index.php?req=vaiTroView&result=failed');
            exit;
        }

        // Cập nhật vai trò
        $result = $roleObj->updateRole($id, $role_name, $description);

        if ($result) {
            header('Location: ../../index.php?req=vaiTroView&result=ok');
        } else {
            header('Location: ../../index.php?req=vaiTroView&result=failed');
        }
        break;

    // Xóa vai trò
    case 'delete':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        // Kiểm tra xem vai trò có tồn tại không
        $existingRole = $roleObj->getRoleById($id);
        if (!$existingRole) {
            header('Location: ../../index.php?req=vaiTroView&result=failed');
            exit;
        }

        // Không cho phép xóa các vai trò mặc định
        if (in_array($existingRole->ten_vai_tro, ['admin', 'staff', 'customer'])) {
            header('Location: ../../index.php?req=vaiTroView&result=failed');
            exit;
        }

        // Xóa vai trò
        $result = $roleObj->deleteRole($id);

        if ($result) {
            header('Location: ../../index.php?req=vaiTroView&result=ok');
        } else {
            header('Location: ../../index.php?req=vaiTroView&result=in_use');
        }
        break;

    // Mặc định chuyển về trang quản lý vai trò
    default:
        header('Location: ../../index.php?req=vaiTroView');
        break;
}
