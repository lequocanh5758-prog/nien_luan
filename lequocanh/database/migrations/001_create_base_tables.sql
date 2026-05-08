-- Migration 001: Core base tables
-- These are the foundational tables for the LQA Shop e-commerce system

-- Bảng loại hàng
CREATE TABLE IF NOT EXISTS loaihang (
    idloaihang INT AUTO_INCREMENT PRIMARY KEY,
    tenloaihang VARCHAR(255) NOT NULL,
    mota TEXT,
    hinhanh VARCHAR(255) DEFAULT NULL,
    noibat TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng đơn vị tính
CREATE TABLE IF NOT EXISTS donvitinh (
    idDonViTinh INT AUTO_INCREMENT PRIMARY KEY,
    tenDonViTinh VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng thương hiệu
CREATE TABLE IF NOT EXISTS thuonghieu (
    idThuongHieu INT AUTO_INCREMENT PRIMARY KEY,
    tenTH VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng user
CREATE TABLE IF NOT EXISTS user (
    iduser INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    hoten VARCHAR(255),
    email VARCHAR(255),
    sodienthoai VARCHAR(20),
    diachi TEXT,
    role ENUM('admin','user') DEFAULT 'user',
    trangthai TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng hàng hóa
CREATE TABLE IF NOT EXISTS hanghoa (
    idhanghoa INT AUTO_INCREMENT PRIMARY KEY,
    tenhanghoa VARCHAR(255) NOT NULL,
    mota TEXT,
    giathamkhao DECIMAL(15,2) DEFAULT 0,
    giakhuyenmai DECIMAL(15,2) DEFAULT NULL,
    hinhanh INT DEFAULT 0,
    idloaihang INT,
    idThuongHieu INT,
    idDonViTinh INT,
    idNhanVien INT,
    ghichu TEXT,
    noibat TINYINT(1) DEFAULT 0,
    FOREIGN KEY (idloaihang) REFERENCES loaihang(idloaihang) ON DELETE SET NULL,
    FOREIGN KEY (idThuongHieu) REFERENCES thuonghieu(idThuongHieu) ON DELETE SET NULL,
    FOREIGN KEY (idDonViTinh) REFERENCES donvitinh(idDonViTinh) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng hình ảnh
CREATE TABLE IF NOT EXISTS hinhanh (
    idhinhanh INT AUTO_INCREMENT PRIMARY KEY,
    tenhinhanh VARCHAR(255),
    duongdan VARCHAR(500),
    idhanghoa INT,
    FOREIGN KEY (idhanghoa) REFERENCES hanghoa(idhanghoa) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng nhân viên
CREATE TABLE IF NOT EXISTS nhanvien (
    idNhanVien INT AUTO_INCREMENT PRIMARY KEY,
    tenNV VARCHAR(255) NOT NULL,
    sodienthoai VARCHAR(20),
    email VARCHAR(255),
    diachi TEXT,
    iduser INT,
    FOREIGN KEY (iduser) REFERENCES user(iduser) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng nhà cung cấp
CREATE TABLE IF NOT EXISTS nhacungcap (
    idNhaCungCap INT AUTO_INCREMENT PRIMARY KEY,
    tenNhaCungCap VARCHAR(255) NOT NULL,
    diachi TEXT,
    sodienthoai VARCHAR(20),
    email VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng đơn giá
CREATE TABLE IF NOT EXISTS dongia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idhanghoa INT NOT NULL,
    gia DECIMAL(15,2) NOT NULL,
    ngayapdung DATE,
    ghichu TEXT,
    FOREIGN KEY (idhanghoa) REFERENCES hanghoa(idhanghoa) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng đơn hàng
CREATE TABLE IF NOT EXISTS don_hang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_don_hang VARCHAR(50) UNIQUE,
    user_id INT,
    ten_khach_hang VARCHAR(255),
    so_dien_thoai VARCHAR(20),
    dia_chi TEXT,
    tong_tien DECIMAL(15,2) DEFAULT 0,
    phuong_thuc_thanh_toan VARCHAR(50),
    trang_thai VARCHAR(50) DEFAULT 'pending',
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(iduser) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng chi tiết đơn hàng
CREATE TABLE IF NOT EXISTS chi_tiet_don_hang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_don_hang INT NOT NULL,
    ma_san_pham INT NOT NULL,
    so_luong INT NOT NULL,
    gia DECIMAL(15,2) NOT NULL,
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ma_don_hang) REFERENCES don_hang(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng hóa đơn
CREATE TABLE IF NOT EXISTS hoadon (
    idhoadon INT AUTO_INCREMENT PRIMARY KEY,
    iduser INT,
    ngaylap TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tongtien DECIMAL(15,2) DEFAULT 0,
    trangthai VARCHAR(50) DEFAULT 'pending',
    FOREIGN KEY (iduser) REFERENCES user(iduser) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng giỏ hàng
CREATE TABLE IF NOT EXISTS giohang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    iduser INT NOT NULL,
    idhanghoa INT NOT NULL,
    soluong INT DEFAULT 1,
    ngaythem TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (iduser) REFERENCES user(iduser) ON DELETE CASCADE,
    FOREIGN KEY (idhanghoa) REFERENCES hanghoa(idhanghoa) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng phiếu nhập
CREATE TABLE IF NOT EXISTS phieunhap (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idNhaCungCap INT,
    ngaynhap TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tongtien DECIMAL(15,2) DEFAULT 0,
    ghichu TEXT,
    FOREIGN KEY (idNhaCungCap) REFERENCES nhacungcap(idNhaCungCap) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng tồn kho
CREATE TABLE IF NOT EXISTS tonkho (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idhanghoa INT NOT NULL UNIQUE,
    soLuong INT DEFAULT 0,
    soLuongToiThieu INT DEFAULT 0,
    viTri VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (idhanghoa) REFERENCES hanghoa(idhanghoa) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng cấu hình thanh toán
CREATE TABLE IF NOT EXISTS cau_hinh_thanh_toan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_ngan_hang VARCHAR(100) NOT NULL,
    so_tai_khoan VARCHAR(50) NOT NULL,
    ten_tai_khoan VARCHAR(100) NOT NULL,
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
