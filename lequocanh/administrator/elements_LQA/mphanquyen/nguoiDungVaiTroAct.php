<?php
// Tắt hiển thị lỗi để tránh headers already sent
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
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

if (!isset($_SESSION['ADMIN'])) {
    header('Location: ../../index.php');
    exit;
}

// Kết nối đến lớp Role
require_once '../mod/roleCls.php';
$roleObj = new Role();

// Kết nối đến lớp User
require_once '../mod/userCls.php';
$userObj = new user();

// Kết nối đến lớp NhanVien
require_once '../mod/nhanvienCls.php';
$nhanVienObj = new NhanVien();

// Xử lý các hành động
$reqact = isset($_GET['reqact']) ? $_GET['reqact'] : '';

switch ($reqact) {
    // Gán vai trò cho người dùng
    case 'assign':
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $role_id = isset($_POST['role_id']) ? intval($_POST['role_id']) : 0;

        // Kiểm tra dữ liệu đầu vào
        if ($user_id <= 0 || $role_id <= 0) {
            header('Location: ../../index.php?req=nguoiDungVaiTroView&result=failed');
            exit;
        }

        // Kiểm tra xem người dùng có tồn tại không
        $user = $userObj->UserGetbyId($user_id);
        if (!$user) {
            header('Location: ../../index.php?req=nguoiDungVaiTroView&result=failed');
            exit;
        }

        // Kiểm tra xem vai trò có tồn tại không
        $role = $roleObj->getRoleById($role_id);
        if (!$role) {
            header('Location: ../../index.php?req=nguoiDungVaiTroView&result=failed');
            exit;
        }

        // Gán vai trò cho người dùng
        $result = $roleObj->assignRoleToUser($user_id, $role_id);

        if ($result) {
            // Nếu vai trò là "staff" (nhân viên), tự động thêm vào bảng nhân viên
            if (($role->ten_vai_tro ?? '') === 'staff') {
                // Kiểm tra xem người dùng đã có trong bảng nhân viên chưa
                $existingStaff = false;
                $allStaff = $nhanVienObj->nhanvienGetAll();

                foreach ($allStaff as $staff) {
                    if ($staff->iduser == $user_id) {
                        $existingStaff = true;
                        break;
                    }
                }

                // Nếu chưa có, thêm vào bảng nhân viên
                if (!$existingStaff) {
                    // Lấy thông tin người dùng
                    $userData = $userObj->UserGetbyId($user_id);

                    // Thêm vào bảng nhân viên với thông tin cơ bản
                    $tenNV = $userData->hoten;
                    $SDT = $userData->dienthoai;
                    $email = $userData->email ?? '';
                    $luongCB = 0; // Mức lương cơ bản mặc định
                    $phuCap = 0;  // Phụ cấp mặc định
                    $chucVu = 'Nhân viên mới'; // Chức vụ mặc định

                    $nhanVienObj->nhanvienAdd($tenNV, $SDT, $email, $luongCB, $phuCap, $chucVu, $user_id);
                }
            }

            header('Location: ../../index.php?req=nguoiDungVaiTroView&user_id=' . $user_id . '&result=ok');
        } else {
            header('Location: ../../index.php?req=nguoiDungVaiTroView&user_id=' . $user_id . '&result=failed');
        }
        break;

    // Xóa vai trò của người dùng
    case 'remove':
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        $role_id = isset($_GET['role_id']) ? intval($_GET['role_id']) : 0;

        // Kiểm tra dữ liệu đầu vào
        if ($user_id <= 0 || $role_id <= 0) {
            header('Location: ../../index.php?req=nguoiDungVaiTroView&result=failed');
            exit;
        }

        // Kiểm tra xem người dùng có tồn tại không
        $user = $userObj->UserGetbyId($user_id);
        if (!$user) {
            header('Location: ../../index.php?req=nguoiDungVaiTroView&result=failed');
            exit;
        }

        // Kiểm tra xem vai trò có tồn tại không
        $role = $roleObj->getRoleById($role_id);
        if (!$role) {
            header('Location: ../../index.php?req=nguoiDungVaiTroView&result=failed');
            exit;
        }

        // Không cho phép xóa vai trò admin của tài khoản admin
        if ($user->username == 'admin' && ($role->ten_vai_tro ?? '') == 'admin') {
            header('Location: ../../index.php?req=nguoiDungVaiTroView&user_id=' . $user_id . '&result=failed');
            exit;
        }

        // Xóa vai trò của người dùng
        $result = $roleObj->removeRoleFromUser($user_id, $role_id);

        if ($result) {
            // Nếu vai trò bị xóa là "staff" (nhân viên), kiểm tra xem người dùng còn vai trò staff không
            if (($role->ten_vai_tro ?? '') === 'staff') {
                // Kiểm tra xem người dùng còn vai trò staff nào khác không
                $userRoles = $roleObj->getUserRoles($user_id);
                $hasStaffRole = false;

                foreach ($userRoles as $userRole) {
                    if (($userRole->ten_vai_tro ?? '') === 'staff') {
                        $hasStaffRole = true;
                        break;
                    }
                }

                // Nếu không còn vai trò staff nào, xóa khỏi bảng nhân viên
                if (!$hasStaffRole) {
                    // Tìm nhân viên có iduser tương ứng
                    $allStaff = $nhanVienObj->nhanvienGetAll();

                    foreach ($allStaff as $staff) {
                        if ($staff->iduser == $user_id) {
                            // Xóa nhân viên
                            $nhanVienObj->nhanvienDelete($staff->idNhanVien);
                            break;
                        }
                    }
                }
            }

            header('Location: ../../index.php?req=nguoiDungVaiTroView&user_id=' . $user_id . '&result=ok');
        } else {
            header('Location: ../../index.php?req=nguoiDungVaiTroView&user_id=' . $user_id . '&result=failed');
        }
        break;

    // Mặc định chuyển về trang quản lý vai trò người dùng
    default:
        header('Location: ../../index.php?req=nguoiDungVaiTroView');
        break;
}
