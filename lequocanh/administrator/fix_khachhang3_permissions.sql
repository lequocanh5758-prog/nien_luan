-- ============================================
-- Script kiểm tra và sửa quyền cho khachhang3
-- ============================================

-- BƯỚC 1: Tìm thông tin user
SELECT '=== BƯỚC 1: Thông tin user khachhang3 ===' AS step;
SELECT iduser, username FROM user WHERE username = 'khachhang3';

-- BƯỚC 2: Tìm nhân viên liên kết
SELECT '=== BƯỚC 2: Nhân viên liên kết ===' AS step;
SELECT nv.idNhanVien, nv.iduser, u.username 
FROM nhanvien nv
INNER JOIN user u ON nv.iduser = u.iduser
WHERE u.username = 'khachhang3';

-- BƯỚC 3: Xem các module hiện tại được gán
SELECT '=== BƯỚC 3: Các module hiện tại được gán ===' AS step;
SELECT ph.maPhanHe, ph.tenPhanHe
FROM NhanVien_PhanHeQuanLy nvph
JOIN PhanHeQuanLy ph ON nvph.idPhanHe = ph.idPhanHe
JOIN nhanvien nv ON nvph.idNhanVien = nv.idNhanVien
JOIN user u ON nv.iduser = u.iduser
WHERE u.username = 'khachhang3'
ORDER BY ph.maPhanHe;

-- BƯỚC 4: Kiểm tra module don_hang có tồn tại không
SELECT '=== BƯỚC 4: Module don_hang trong hệ thống ===' AS step;
SELECT idPhanHe, maPhanHe, tenPhanHe FROM PhanHeQuanLy WHERE maPhanHe = 'don_hang';

-- BƯỚC 5: KIỂM TRA - Nếu chưa có don_hang, thêm vào
-- (Bỏ comment dòng dưới nếu muốn tự động thêm)
-- INSERT IGNORE INTO NhanVien_PhanHeQuanLy (idNhanVien, idPhanHe)
-- SELECT nv.idNhanVien, ph.idPhanHe
-- FROM nhanvien nv
-- JOIN user u ON nv.iduser = u.iduser
-- JOIN PhanHeQuanLy ph ON ph.maPhanHe = 'don_hang'
-- WHERE u.username = 'khachhang3';

-- BƯỚC 6: XÁC NHẬN - Kiểm tra lại sau khi thêm
SELECT '=== BƯỚC 6: Xác nhận quyền sau khi sửa ===' AS step;
SELECT 
    u.username,
    ph.maPhanHe,
    ph.tenPhanHe,
    CASE 
        WHEN COUNT(nvph.id) > 0 THEN 'CÓ QUYỀN'
        ELSE 'KHÔNG CÓ QUYỀN'
    END AS status
FROM user u
JOIN nhanvien nv ON u.iduser = nv.iduser
CROSS JOIN PhanHeQuanLy ph
LEFT JOIN NhanVien_PhanHeQuanLy nvph ON nvph.idNhanVien = nv.idNhanVien AND nvph.idPhanHe = ph.idPhanHe
WHERE u.username = 'khachhang3'
  AND ph.maPhanHe IN ('dongiaview', 'adminGiohangView', 'don_hang')
GROUP BY u.username, ph.maPhanHe, ph.tenPhanHe
ORDER BY ph.maPhanHe;
