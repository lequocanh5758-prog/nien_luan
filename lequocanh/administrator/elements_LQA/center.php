<?php
require_once './elements_LQA/mod/phanquyenCls.php';

// Khởi tạo đối tượng PhanQuyen
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if (isset($_GET['req'])) {
    $request = $_GET['req'];

    // Log để debug
    error_log("Center.php - Yêu cầu truy cập module: $request, Username: $username");

    // Kiểm tra quyền truy cập
    if (!$phanQuyen->checkAccess($request, $username)) {
        echo '<div class="alert alert-danger">
                <strong>Lỗi:</strong> Bạn không có quyền truy cập vào chức năng này.
                <p>Module yêu cầu: ' . htmlspecialchars($request) . '</p>
                <p>Tài khoản: ' . htmlspecialchars($username) . '</p>
              </div>';
        error_log("Center.php - Từ chối quyền truy cập cho module: $request, Username: $username");
        exit;
    }

    error_log("Center.php - Cho phép truy cập module: $request, Username: $username");

    switch ($request) {
        case 'userview':
            require './elements_LQA/mUser/userView.php';
            break;
        case 'updateuser':
            require './elements_LQA/mUser/userUpdate.php';
            break;
        case 'userupdate':
            require './elements_LQA/mUser/userUpdate.php';
            break;

        case 'userUpdateProfile':
            require './elements_LQA/mUser/userUpdateProfile.php';
            break;
        case 'loaihangview':
            require './elements_LQA/mLoaihang/loaihangView.php';
            break;
        case 'hanghoaview':
            require './elements_LQA/mhanghoa/hanghoaView.php';
            break;
        case 'dongiaview': // Đảm bảo rằng trường hợp này được xử lý
            require './elements_LQA/mdongia/dongiaView.php';
            break;
        case 'thuonghieuview':
            require './elements_LQA/mthuonghieu/thuonghieuView.php';
            break;
        case 'donvitinhview':
            require './elements_LQA/mdonvitinh/donvitinhView.php';
            break;
        case 'nhanvienview':
            require './elements_LQA/mnhanvien/nhanvienView.php';
            break;
        case 'thuoctinhview':
            require './elements_LQA/mthuoctinh/thuoctinhView.php';
            break;
        case 'thuoctinhhhview':
            require './elements_LQA/mthuoctinhhh/thuoctinhhhView.php';
            break;
        case 'adminGiohangView':
            require './elements_LQA/mgiohang/adminGiohangView.php';
            break;
        case 'hinhanhview':
            require './elements_LQA/mhinhanh/hinhanhView.php';
            break;
        case 'nhacungcapview':
            require './elements_LQA/mnhacungcap/nhacungcapView.php';
            break;
        case 'mphieunhap':
            require './elements_LQA/mmphieunhap/mphieunhapView.php';
            break;
        case 'mphieunhapedit':
            require './elements_LQA/mmphieunhap/mphieunhapEdit.php';
            break;
        case 'mchitietphieunhap':
            require './elements_LQA/mmphieunhap/mchitietphieunhapView.php';
            break;
        case 'mchitietphieunhapedit':
            require './elements_LQA/mmphieunhap/mchitietphieunhapEdit.php';
            break;
        case 'mtonkho':
            require './elements_LQA/mmtonkho/mtonkhoView.php';
            break;
        case 'mtonkhoedit':
            require './elements_LQA/mmtonkho/mtonkhoEdit.php';
            break;
        case 'mphieunhapfixtonkho':
            require './elements_LQA/mmphieunhap/mphieunhapFixTonKho.php';
            break;
        case 'payment_config':
            require './elements_LQA/madmin/payment_config.php';
            break;
        case 'cau_hinh_thanh_toan':
            require './elements_LQA/madmin/payment_config.php';
            break;
        case 'orders':
            require './elements_LQA/madmin/orders.php';
            break;
        case 'don_hang':
            require './elements_LQA/madmin/orders.php';
            break;

        case 'khachhangview':
            require './elements_LQA/mkhachhang/khachhangController.php';
            break;

        case 'lichsumuahang':
            require './elements_LQA/mkhachhang/lichsumuahangController.php';
            break;

        case 'baocaoview':
            require './elements_LQA/mbaocao/baocaoView.php';
            break;

        case 'doanhThuView':
            require './elements_LQA/mbaocao/doanhThuView.php';
            break;

        case 'sanPhamBanChayView':
            require './elements_LQA/mbaocao/sanPhamBanChayView.php';
            break;

        case 'loiNhuanView':
            require './elements_LQA/mbaocao/loiNhuanView.php';
            break;

        case 'roleview':
            require './elements_LQA/mrole/roleView.php';
            break;

        case 'vaiTroView':
            require './elements_LQA/mphanquyen/vaiTroView.php';
            break;

        case 'nguoiDungVaiTroView':
            require './elements_LQA/mphanquyen/nguoiDungVaiTroView.php';
            break;

        case 'danhSachVaiTroView':
            require './elements_LQA/mphanquyen/danhSachVaiTroView.php';
            break;



        case 'nhatKyHoatDongTichHop':
            require './elements_LQA/mnhatkyhoatdong/nhatKyHoatDongTichHop.php';
            break;

        case 'thongKeNhanVienCaiThien':
            require './elements_LQA/mnhatkyhoatdong/thongKeNhanVienCaiThien.php';
            break;
    }
} else {
    require './elements_LQA/default.php';
}
