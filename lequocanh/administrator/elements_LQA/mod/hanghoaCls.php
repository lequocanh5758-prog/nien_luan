<?php

// Xác định đường dẫn tới file database.php
$possible_paths = array(
    dirname(__FILE__) . '/database.php',                    // Cùng thư mục
    dirname(dirname(dirname(__FILE__))) . '/elements_LQA/mod/database.php',  // Từ thư mục administrator
    dirname(dirname(dirname(dirname(__FILE__)))) . '/administrator/elements_LQA/mod/database.php'  // Từ thư mục gốc
);

$database_file = null;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        $database_file = $path;
        break;
    }
}

if ($database_file === null) {
    die("Không thể tìm thấy file database.php");
}

require_once $database_file;

class hanghoa
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function HanghoaGetAll()
    {
        $sql = 'SELECT h.*,
                t.tenTH AS ten_thuonghieu,
                d.tenDonViTinh AS ten_donvitinh,
                n.tenNV AS ten_nhanvien
                FROM hanghoa h
                LEFT JOIN thuonghieu t ON h.idThuongHieu = t.idThuongHieu
                LEFT JOIN donvitinh d ON h.idDonViTinh = d.idDonViTinh
                LEFT JOIN nhanvien n ON h.idNhanVien = n.idNhanVien';
        $getAll = $this->db->prepare($sql);
        $getAll->setFetchMode(PDO::FETCH_OBJ);
        $getAll->execute();
        return $getAll->fetchAll();
    }

    public function HanghoaAdd($tenhanghoa, $mota, $giathamkhao, $id_hinhanh, $idloaihang, $idThuongHieu, $idDonViTinh, $idNhanVien, $ghichu = '')
    {
        // Tạo file log
        $log_file = dirname(__FILE__) . '/hanghoa_class_debug.log';

        try {
            // Ghi log chi tiết
            $log_data = date('Y-m-d H:i:s') . " - HanghoaAdd() được gọi với các tham số:\n";
            $log_data .= "tenhanghoa: $tenhanghoa\n";
            $log_data .= "mota: $mota\n";
            $log_data .= "giathamkhao: $giathamkhao\n";
            $log_data .= "id_hinhanh: " . ($id_hinhanh ?: "NULL") . "\n";
            $log_data .= "idloaihang: $idloaihang\n";
            $log_data .= "idThuongHieu: " . ($idThuongHieu ?: "NULL") . "\n";
            $log_data .= "idDonViTinh: " . ($idDonViTinh ?: "NULL") . "\n";
            $log_data .= "idNhanVien: " . ($idNhanVien ?: "NULL") . "\n";
            $log_data .= "ghichu: " . ($ghichu ?: "") . "\n";
            file_put_contents($log_file, $log_data, FILE_APPEND);

            // Convert empty strings to NULL for integer fields, but use 0 for id_hinhanh
            $id_hinhanh = ($id_hinhanh === '') ? 0 : $id_hinhanh; // Use 0 instead of NULL for id_hinhanh
            $idThuongHieu = ($idThuongHieu === '' || $idThuongHieu === 0 || $idThuongHieu === '0') ? null : $idThuongHieu;
            $idDonViTinh = ($idDonViTinh === '' || $idDonViTinh === 0 || $idDonViTinh === '0') ? null : $idDonViTinh;
            $idNhanVien = ($idNhanVien === '' || $idNhanVien === 0 || $idNhanVien === '0') ? null : $idNhanVien;

            // Kiểm tra kết nối database
            if (!$this->db || !($this->db instanceof PDO)) {
                $error_msg = "Lỗi: Không có kết nối database hợp lệ";
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - $error_msg\n", FILE_APPEND);
                return false;
            }

            // Kiểm tra các tham số bắt buộc
            if (empty($tenhanghoa) || empty($giathamkhao) || empty($idloaihang)) {
                $error_msg = "Lỗi: Thiếu thông tin bắt buộc (tên hàng hóa, giá tham khảo hoặc loại hàng)";
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - $error_msg\n", FILE_APPEND);
                return false;
            }

            // Kiểm tra cấu trúc bảng hanghoa
            try {
                $checkColumns = $this->db->query("SHOW COLUMNS FROM hanghoa");
                $columns = $checkColumns->fetchAll(PDO::FETCH_COLUMN);
                $log_columns = date('Y-m-d H:i:s') . " - Các cột trong bảng hanghoa: " . implode(", ", $columns) . "\n";
                file_put_contents($log_file, $log_columns, FILE_APPEND);
            } catch (Exception $e) {
                $log_column_error = date('Y-m-d H:i:s') . " - Lỗi khi kiểm tra cấu trúc bảng: " . $e->getMessage() . "\n";
                file_put_contents($log_file, $log_column_error, FILE_APPEND);
            }

            // Chuẩn bị câu lệnh SQL với tên cột chính xác
            // Thêm trường ghichu vào câu lệnh SQL
            $ghichu = ""; // Giá trị mặc định cho ghichu

            if (in_array('hinhanh', $columns)) {
                $sql = "INSERT INTO hanghoa (tenhanghoa, mota, giathamkhao, hinhanh, idloaihang, idThuongHieu, idDonViTinh, idNhanVien, ghichu) VALUES (?,?,?,?,?,?,?,?,?)";
                $data = array($tenhanghoa, $mota, $giathamkhao, $id_hinhanh, $idloaihang, $idThuongHieu, $idDonViTinh, $idNhanVien, $ghichu);
            } else {
                // Nếu tên cột là id_hinhanh thay vì hinhanh
                $sql = "INSERT INTO hanghoa (tenhanghoa, mota, giathamkhao, id_hinhanh, idloaihang, idThuongHieu, idDonViTinh, idNhanVien, ghichu) VALUES (?,?,?,?,?,?,?,?,?)";
                $data = array($tenhanghoa, $mota, $giathamkhao, $id_hinhanh, $idloaihang, $idThuongHieu, $idDonViTinh, $idNhanVien, $ghichu);
            }

            // Ghi log SQL và dữ liệu
            $log_sql = date('Y-m-d H:i:s') . " - SQL: $sql\n";
            $log_sql .= "Data: " . print_r($data, true) . "\n";
            file_put_contents($log_file, $log_sql, FILE_APPEND);

            // Thực thi câu lệnh SQL
            $add = $this->db->prepare($sql);
            $result = $add->execute($data);

            // Kiểm tra kết quả
            if ($result) {
                $rowCount = $add->rowCount();
                $lastId = $this->db->lastInsertId();
                $log_success = date('Y-m-d H:i:s') . " - Thêm hàng hóa thành công. Rows affected: $rowCount, Last Insert ID: $lastId\n";
                file_put_contents($log_file, $log_success, FILE_APPEND);

                // Thêm vào bảng tonkho nếu có
                try {
                    $checkTonkhoTable = $this->db->query("SHOW TABLES LIKE 'tonkho'");
                    if ($checkTonkhoTable->rowCount() > 0) {
                        $insertTonkho = "INSERT INTO tonkho (idhanghoa, soLuong, soLuongToiThieu, viTri) VALUES (?, 0, 0, NULL)";
                        $stmtTonkho = $this->db->prepare($insertTonkho);
                        $stmtTonkho->execute([$lastId]);
                        $log_tonkho = date('Y-m-d H:i:s') . " - Đã thêm vào bảng tonkho cho hàng hóa ID: $lastId\n";
                        file_put_contents($log_file, $log_tonkho, FILE_APPEND);
                    }
                } catch (Exception $tonkhoEx) {
                    $log_tonkho_error = date('Y-m-d H:i:s') . " - Lỗi khi thêm vào bảng tonkho: " . $tonkhoEx->getMessage() . "\n";
                    file_put_contents($log_file, $log_tonkho_error, FILE_APPEND);
                }

                return $lastId; // Trả về ID của hàng hóa mới thêm
            } else {
                $error_info = print_r($add->errorInfo(), true);
                $log_error = date('Y-m-d H:i:s') . " - Thêm hàng hóa thất bại. Error info: $error_info\n";
                file_put_contents($log_file, $log_error, FILE_APPEND);

                // Thử phương án thay thế nếu có lỗi với tên cột
                if (strpos($error_info, "Unknown column") !== false) {
                    // Kiểm tra cấu trúc bảng một lần nữa
                    $describeStmt = $this->db->query("DESCRIBE hanghoa");
                    $columns = $describeStmt->fetchAll(PDO::FETCH_COLUMN);

                    // Xác định tên cột hình ảnh chính xác
                    $imageColumn = in_array('hinhanh', $columns) ? 'hinhanh' : 'id_hinhanh';

                    // Thử lại với tên cột chính xác
                    $sql = "INSERT INTO hanghoa (tenhanghoa, mota, giathamkhao, $imageColumn, idloaihang, idThuongHieu, idDonViTinh, idNhanVien) VALUES (?,?,?,?,?,?,?,?)";
                    $add = $this->db->prepare($sql);
                    $result = $add->execute($data);

                    if ($result) {
                        $lastId = $this->db->lastInsertId();
                        $log_retry_success = date('Y-m-d H:i:s') . " - Thêm hàng hóa thành công sau khi thử lại. Last Insert ID: $lastId\n";
                        file_put_contents($log_file, $log_retry_success, FILE_APPEND);
                        return $lastId;
                    } else {
                        $retry_error_info = print_r($add->errorInfo(), true);
                        $log_retry_error = date('Y-m-d H:i:s') . " - Thêm hàng hóa thất bại sau khi thử lại. Error info: $retry_error_info\n";
                        file_put_contents($log_file, $log_retry_error, FILE_APPEND);
                    }
                }

                return false;
            }
        } catch (Exception $e) {
            $log_exception = date('Y-m-d H:i:s') . " - Exception: " . $e->getMessage() . "\n";
            $log_exception .= "Stack trace: " . $e->getTraceAsString() . "\n";
            file_put_contents($log_file, $log_exception, FILE_APPEND);
            return false;
        }
    }

    public function HanghoaDelete($idhanghoa)
    {
        try {
            // Kiểm tra các ràng buộc trước khi xóa
            $relatedData = $this->checkRelatedData($idhanghoa);

            if (!empty($relatedData)) {
                // Trả về thông tin về các bảng có liên quan
                return [
                    'success' => false,
                    'error_type' => 'foreign_key_constraint',
                    'message' => 'Không thể xóa hàng hóa vì còn dữ liệu liên quan',
                    'related_tables' => $relatedData
                ];
            }

            // Nếu không có ràng buộc, thực hiện xóa
            $sql = "DELETE from hanghoa where idhanghoa = ?";
            $data = array($idhanghoa);

            $del = $this->db->prepare($sql);
            $del->execute($data);

            $rowCount = $del->rowCount();

            return [
                'success' => true,
                'rows_affected' => $rowCount,
                'message' => $rowCount > 0 ? 'Xóa hàng hóa thành công' : 'Không tìm thấy hàng hóa để xóa'
            ];
        } catch (PDOException $e) {
            // Xử lý lỗi foreign key constraint
            if ($e->getCode() == '23000' && strpos($e->getMessage(), 'foreign key constraint') !== false) {
                // Phân tích thông báo lỗi để lấy tên bảng
                $errorMessage = $e->getMessage();
                $tableName = 'không xác định';

                if (preg_match('/`([^`]+)`\.`([^`]+)`/', $errorMessage, $matches)) {
                    $tableName = $matches[2]; // Tên bảng
                }

                return [
                    'success' => false,
                    'error_type' => 'foreign_key_constraint',
                    'message' => 'Không thể xóa hàng hóa vì còn dữ liệu liên quan trong bảng: ' . $tableName,
                    'technical_error' => $errorMessage,
                    'suggested_action' => $this->getSuggestedAction($tableName)
                ];
            }

            // Lỗi khác
            return [
                'success' => false,
                'error_type' => 'database_error',
                'message' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()
            ];
        }
    }

    public function HanghoaUpdate($tenhanghoa, $id_hinhanh, $mota, $giathamkhao, $idloaihang, $idThuongHieu, $idDonViTinh, $idNhanVien, $idhanghoa, $ghichu = '')
    {
        // Convert empty strings to NULL for integer fields, but use 0 for id_hinhanh
        $id_hinhanh = ($id_hinhanh === '') ? 0 : $id_hinhanh; // Use 0 instead of NULL for id_hinhanh
        $idThuongHieu = $idThuongHieu === '' ? null : $idThuongHieu;
        $idDonViTinh = $idDonViTinh === '' ? null : $idDonViTinh;
        $idNhanVien = $idNhanVien === '' ? null : $idNhanVien;

        $sql = "UPDATE hanghoa SET tenhanghoa=?, hinhanh=?, mota=?, giathamkhao=?, idloaihang=?, idThuongHieu=?, idDonViTinh=?, idNhanVien=?, ghichu=? WHERE idhanghoa =?";
        $data = array($tenhanghoa, $id_hinhanh, $mota, $giathamkhao, $idloaihang, $idThuongHieu, $idDonViTinh, $idNhanVien, $ghichu, $idhanghoa);

        $update = $this->db->prepare($sql);
        $update->execute($data);
        return $update->rowCount();
    }

    public function HanghoaGetbyId($idhanghoa)
    {
        $sql = 'select * from hanghoa where idhanghoa=?';
        $data = array($idhanghoa);

        $getOne = $this->db->prepare($sql);
        $getOne->setFetchMode(PDO::FETCH_OBJ);
        $getOne->execute($data);

        return $getOne->fetch();
    }

    public function HanghoaGetbyIdloaihang($idloaihang)
    {
        $sql = 'select * from hanghoa where idloaihang=?';
        $data = array($idloaihang);

        $getOne = $this->db->prepare($sql);
        $getOne->setFetchMode(PDO::FETCH_OBJ);
        $getOne->execute($data);

        return $getOne->fetchAll();
    }

    public function HanghoaUpdatePrice($idhanghoa, $giaban)
    {
        $sql = "UPDATE hanghoa SET giathamkhao = ? WHERE idhanghoa = ?";
        $data = array($giaban, $idhanghoa);

        $update = $this->db->prepare($sql);
        $update->execute($data);
        return $update->rowCount();
    }

    public function searchHanghoa($keyword)
    {
        try {
            // Ghi log để debug
            error_log("searchHanghoa - Bắt đầu tìm kiếm với từ khóa: " . $keyword);

            // Kiểm tra kết nối database
            if (!$this->db || !($this->db instanceof PDO)) {
                error_log("searchHanghoa - Lỗi: Không có kết nối database hợp lệ");
                return [];
            }

            // Kiểm tra bảng hanghoa có tồn tại không
            try {
                $checkTable = $this->db->query("SHOW TABLES LIKE 'hanghoa'");
                if ($checkTable->rowCount() == 0) {
                    error_log("searchHanghoa - Bảng hanghoa không tồn tại");
                    return [];
                }
            } catch (PDOException $e) {
                error_log("searchHanghoa - Lỗi khi kiểm tra bảng hanghoa: " . $e->getMessage());
                return [];
            }

            $select = "SELECT * FROM hanghoa
                       WHERE LOWER(tenhanghoa) LIKE LOWER(:keyword)
                       ORDER BY tenhanghoa ASC
                       LIMIT 10";
            $stmt = $this->db->prepare($select);
            $stmt->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);
            $stmt->execute();

            $results = $stmt->fetchAll(PDO::FETCH_OBJ);
            error_log("searchHanghoa - Tìm thấy " . count($results) . " kết quả");

            return $results;
        } catch (PDOException $e) {
            error_log("searchHanghoa - Lỗi: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Kiểm tra dữ liệu liên quan trước khi xóa hàng hóa
     */
    public function checkRelatedData($idhanghoa)
    {
        $relatedData = [];

        try {
            // Kiểm tra bảng tồn kho
            $sql = "SELECT COUNT(*) as count FROM tonkho WHERE idhanghoa = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa]);
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                $relatedData['tonkho'] = [
                    'table_name' => 'tonkho',
                    'display_name' => 'Tồn kho',
                    'count' => $count,
                    'description' => 'Hàng hóa này còn có ' . $count . ' bản ghi tồn kho'
                ];
            }

            // Kiểm tra bảng chi tiết hóa đơn
            $sql = "SELECT COUNT(*) as count FROM chitiethoadon WHERE idhanghoa = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa]);
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                $relatedData['chitiethoadon'] = [
                    'table_name' => 'chitiethoadon',
                    'display_name' => 'Chi tiết hóa đơn',
                    'count' => $count,
                    'description' => 'Hàng hóa này có trong ' . $count . ' hóa đơn'
                ];
            }

            // Kiểm tra bảng chi tiết phiếu nhập
            $sql = "SELECT COUNT(*) as count FROM chitietphieunhap WHERE idhanghoa = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa]);
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                $relatedData['chitietphieunhap'] = [
                    'table_name' => 'chitietphieunhap',
                    'display_name' => 'Chi tiết phiếu nhập',
                    'count' => $count,
                    'description' => 'Hàng hóa này có trong ' . $count . ' phiếu nhập'
                ];
            }

            // Kiểm tra bảng thuộc tính hàng hóa
            $sql = "SELECT COUNT(*) as count FROM thuoctinhhh WHERE idhanghoa = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa]);
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                $relatedData['thuoctinhhh'] = [
                    'table_name' => 'thuoctinhhh',
                    'display_name' => 'Thuộc tính hàng hóa',
                    'count' => $count,
                    'description' => 'Hàng hóa này có ' . $count . ' thuộc tính'
                ];
            }

            // Kiểm tra bảng đơn giá
            $sql = "SELECT COUNT(*) as count FROM dongia WHERE idhanghoa = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa]);
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                $relatedData['dongia'] = [
                    'table_name' => 'dongia',
                    'display_name' => 'Đơn giá',
                    'count' => $count,
                    'description' => 'Hàng hóa này có ' . $count . ' mức giá'
                ];
            }
        } catch (PDOException $e) {
            error_log("Error checking related data: " . $e->getMessage());
        }

        return $relatedData;
    }

    /**
     * Đưa ra gợi ý hành động dựa trên bảng có liên quan
     */
    private function getSuggestedAction($tableName)
    {
        $suggestions = [
            'tonkho' => 'Hãy xóa tất cả bản ghi tồn kho của hàng hóa này trước khi xóa hàng hóa.',
            'chitiethoadon' => 'Hàng hóa này đã được bán trong các hóa đơn. Không nên xóa để đảm bảo tính toàn vẹn dữ liệu.',
            'chitietphieunhap' => 'Hàng hóa này đã được nhập kho. Không nên xóa để đảm bảo tính toàn vẹn dữ liệu.',
            'thuoctinhhh' => 'Hãy xóa tất cả thuộc tính của hàng hóa này trước.',
            'dongia' => 'Hãy xóa tất cả đơn giá của hàng hóa này trước.'
        ];

        return isset($suggestions[$tableName]) ? $suggestions[$tableName] : 'Hãy xóa dữ liệu liên quan trước khi xóa hàng hóa này.';
    }

    public function CheckRelations($idhanghoa)
    {
        // Giữ lại method cũ để tương thích
        $relatedData = $this->checkRelatedData($idhanghoa);
        return array_keys($relatedData);
    }

    public function GetAllThuongHieu()
    {
        $sql = 'SELECT * FROM thuonghieu';
        $getAll = $this->db->prepare($sql);
        $getAll->setFetchMode(PDO::FETCH_OBJ);
        $getAll->execute();
        return $getAll->fetchAll();
    }

    public function GetAllDonViTinh()
    {
        $sql = 'SELECT * FROM donvitinh';
        $getAll = $this->db->prepare($sql);
        $getAll->setFetchMode(PDO::FETCH_OBJ);
        $getAll->execute();
        return $getAll->fetchAll();
    }

    public function GetAllNhanVien()
    {
        $sql = 'SELECT * FROM nhanvien';
        $getAll = $this->db->prepare($sql);
        $getAll->setFetchMode(PDO::FETCH_OBJ);
        $getAll->execute();
        return $getAll->fetchAll();
    }

    // Lấy thông tin thương hiệu theo ID
    public function GetThuongHieuById($idThuongHieu)
    {
        $sql = 'SELECT * FROM thuonghieu WHERE idThuongHieu = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idThuongHieu]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function GetAllHinhAnh()
    {
        try {
            $sql = 'SELECT h.*,
                    (SELECT COUNT(*) FROM hanghoa WHERE hinhanh = h.id) as usage_count
                    FROM hinhanh h
                    ORDER BY h.ngay_tao DESC';
            $getAll = $this->db->prepare($sql);
            $getAll->setFetchMode(PDO::FETCH_OBJ);
            $getAll->execute();
            return $getAll->fetchAll();
        } catch (Exception $e) {
            error_log("Error in GetAllHinhAnh: " . $e->getMessage());
            return array();
        }
    }

    public function GetHinhAnhById($id)
    {
        if (!$id) return null;

        try {
            error_log("GetHinhAnhById - Bắt đầu tìm hình ảnh với ID: " . $id);

            // Kiểm tra xem bảng hinhanh có tồn tại không
            try {
                $checkTable = $this->db->query("SHOW TABLES LIKE 'hinhanh'");
                if ($checkTable->rowCount() == 0) {
                    error_log("GetHinhAnhById - Bảng hinhanh không tồn tại");
                    return null;
                }
            } catch (PDOException $e) {
                error_log("GetHinhAnhById - Lỗi khi kiểm tra bảng hinhanh: " . $e->getMessage());
            }

            // Kiểm tra cấu trúc bảng hinhanh
            try {
                $columns = $this->db->query("SHOW COLUMNS FROM hinhanh");
                $columnNames = [];
                while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
                    $columnNames[] = $column['Field'];
                }
                error_log("GetHinhAnhById - Cấu trúc bảng hinhanh: " . implode(", ", $columnNames));
            } catch (PDOException $e) {
                error_log("GetHinhAnhById - Lỗi khi lấy cấu trúc bảng hinhanh: " . $e->getMessage());
            }

            $sql = 'SELECT * FROM hinhanh WHERE id = ?';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $hinhanh = $stmt->fetch(PDO::FETCH_OBJ);

            if ($hinhanh) {
                // Log đường dẫn để debug
                error_log("GetHinhAnhById - ID: " . $id . ", đường dẫn gốc: " . $hinhanh->duong_dan);
                error_log("GetHinhAnhById - Thông tin đầy đủ: " . print_r($hinhanh, true));

                // Xử lý đường dẫn hình ảnh
                if (strpos($hinhanh->duong_dan, 'data:image') === 0) {
                    // Nếu là base64, giữ nguyên
                    error_log("GetHinhAnhById - Đường dẫn là base64");
                    return $hinhanh;
                } else {
                    // Đường dẫn chính xác từ gốc web app
                    if (!empty($hinhanh->duong_dan)) {
                        // Chuẩn hóa đường dẫn
                        $hinhanh->duong_dan = str_replace('\\', '/', $hinhanh->duong_dan);

                        // Nếu đường dẫn chưa có "administrator/" ở đầu và bắt đầu bằng "uploads/"
                        if (
                            strpos($hinhanh->duong_dan, 'administrator/') !== 0 &&
                            strpos($hinhanh->duong_dan, 'uploads/') === 0
                        ) {
                            $hinhanh->duong_dan = 'administrator/' . $hinhanh->duong_dan;
                            error_log("GetHinhAnhById - Đường dẫn sau khi thêm tiền tố: " . $hinhanh->duong_dan);
                        }
                    }
                    error_log("GetHinhAnhById - Đường dẫn cuối cùng: " . $hinhanh->duong_dan);
                    return $hinhanh;
                }
            }
            error_log("GetHinhAnhById - Không tìm thấy hình ảnh với ID: " . $id);
            return null;
        } catch (PDOException $e) {
            error_log("Error in GetHinhAnhById: " . $e->getMessage());
            return null;
        }
    }

    // Phương thức tạo bảng hanghoa_hinhanh nếu chưa tồn tại
    public function CreateHanghoaHinhanhTable()
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS hanghoa_hinhanh (
                id INT AUTO_INCREMENT PRIMARY KEY,
                idhanghoa INT NOT NULL,
                idhinhanh INT NOT NULL,
                UNIQUE KEY (idhanghoa, idhinhanh)
            )";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error in CreateHanghoaHinhanhTable: " . $e->getMessage());
            return false;
        }
    }

    // Biến static để lưu trữ trạng thái kiểm tra cột file_hash
    private static $hasCheckedFileHashColumn = false;
    private static $fileHashColumnExists = false;

    public function ThemHinhAnh($ten_file, $loai_file, $duong_dan, $file_hash = null)
    {
        try {
            // Chỉ kiểm tra cột file_hash một lần trong suốt thời gian chạy ứng dụng
            if (!self::$hasCheckedFileHashColumn) {
                $checkColumnSql = "SHOW COLUMNS FROM hinhanh LIKE 'file_hash'";
                $checkColumnStmt = $this->db->prepare($checkColumnSql);
                $checkColumnStmt->execute();

                self::$fileHashColumnExists = ($checkColumnStmt->rowCount() > 0);
                self::$hasCheckedFileHashColumn = true;

                // Nếu chưa có cột file_hash, thêm cột này vào bảng
                if (!self::$fileHashColumnExists) {
                    $addColumnSql = "ALTER TABLE hinhanh ADD COLUMN file_hash VARCHAR(32) NULL";
                    $this->db->exec($addColumnSql);
                    self::$fileHashColumnExists = true;
                }
            }

            // Sử dụng prepared statement được tối ưu hóa
            if ($file_hash) {
                $sql = "INSERT INTO hinhanh (ten_file, loai_file, duong_dan, trang_thai, ngay_tao, file_hash)
                        VALUES (?, ?, ?, 0, CURRENT_TIMESTAMP, ?)";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([$ten_file, $loai_file, $duong_dan, $file_hash]);
            } else {
                $sql = "INSERT INTO hinhanh (ten_file, loai_file, duong_dan, trang_thai, ngay_tao)
                        VALUES (?, ?, ?, 0, CURRENT_TIMESTAMP)";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([$ten_file, $loai_file, $duong_dan]);
            }
        } catch (PDOException $e) {
            error_log("Error in ThemHinhAnh: " . $e->getMessage());
            return false;
        }
    }

    public function XoaHinhAnh($id)
    {
        try {
            // Xóa record trong database
            $sql = "DELETE FROM hinhanh WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error in XoaHinhAnh: " . $e->getMessage());
            return false;
        }
    }

    // Áp dụng hình ảnh cho sản phẩm
    public function ApplyImageToProduct($idhanghoa, $id_hinhanh)
    {
        try {
            // Đảm bảo kết nối đang hoạt động
            if (!$this->db || !($this->db instanceof PDO)) {
                error_log("Không có kết nối database hợp lệ");
                return false;
            }

            // Kiểm tra trạng thái transaction hiện tại
            try {
                // Bắt đầu giao dịch mới
                $this->db->beginTransaction();
            } catch (PDOException $e) {
                // Nếu có lỗi "There is no active transaction", thử commit trước khi bắt đầu mới
                if (strpos($e->getMessage(), 'There is no active transaction') !== false) {
                    error_log("Đang thử phục hồi transaction: " . $e->getMessage());
                    try {
                        // Thử commit transaction hiện tại nếu có
                        $this->db->commit();
                    } catch (Exception $ex) {
                        // Bỏ qua lỗi nếu không có transaction để commit
                    }
                    // Bắt đầu transaction mới
                    $this->db->beginTransaction();
                } else {
                    // Lỗi khác, ghi log và trả về false
                    error_log("Lỗi transaction: " . $e->getMessage());
                    return false;
                }
            }

            // Đảm bảo bảng hanghoa_hinhanh đã được tạo
            $this->CreateHanghoaHinhanhTable();

            // Cập nhật hình ảnh chính cho sản phẩm
            $sql = 'UPDATE hanghoa SET hinhanh = ? WHERE idhanghoa = ?';
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$id_hinhanh, $idhanghoa]);

            if (!$result) {
                throw new Exception("Không thể cập nhật hình ảnh chính");
            }

            // Thêm quan hệ vào bảng hanghoa_hinhanh nếu chưa tồn tại
            $checkSql = 'SELECT COUNT(*) FROM hanghoa_hinhanh WHERE idhanghoa = ? AND idhinhanh = ?';
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$idhanghoa, $id_hinhanh]);
            $exists = $checkStmt->fetchColumn() > 0;

            if (!$exists) {
                $insertSql = 'INSERT INTO hanghoa_hinhanh (idhanghoa, idhinhanh) VALUES (?, ?)';
                $insertStmt = $this->db->prepare($insertSql);
                $insertResult = $insertStmt->execute([$idhanghoa, $id_hinhanh]);

                if (!$insertResult) {
                    throw new Exception("Không thể thêm quan hệ hình ảnh");
                }
            }

            // Cập nhật trạng thái hình ảnh thành đang sử dụng
            $this->UpdateImageStatus($id_hinhanh);

            // Hoàn tất giao dịch
            $this->db->commit();

            return true;
        } catch (Exception $e) {
            // Rollback nếu có lỗi
            try {
                $this->db->rollBack();
            } catch (PDOException $rollbackException) {
                error_log("Lỗi khi rollback: " . $rollbackException->getMessage());
            }
            error_log("Error in ApplyImageToProduct: " . $e->getMessage());
            return false;
        }
    }

    public function GetProductsByImageId($imageId)
    {
        $sql = "SELECT idhanghoa, tenhanghoa FROM hanghoa WHERE hinhanh = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$imageId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function UpdateProductImages($oldImageId, $newImageId)
    {
        try {
            $sql = "UPDATE hanghoa SET hinhanh = ? WHERE hinhanh = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$newImageId, $oldImageId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function GetImagePath($id)
    {
        $sql = 'SELECT duong_dan FROM hinhanh WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result ? $result->duong_dan : null;
    }

    public function UpdateImageStatus($id)
    {
        $sql = 'UPDATE hinhanh SET trang_thai = 1 WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    // Tìm sản phẩm theo tên
    public function FindProductsByName($name)
    {
        $sql = 'SELECT * FROM hanghoa WHERE tenhanghoa LIKE ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(["%" . $name . "%"]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Lấy ID được insert cuối cùng
    public function GetLastInsertId()
    {
        return $this->db->lastInsertId();
    }

    public function FindProductsByExactName($productName)
    {
        try {
            $sql = "SELECT * FROM hanghoa WHERE tenhanghoa = :productName";
            $cmd = $this->db->prepare($sql);
            $cmd->bindValue(":productName", $productName);
            $cmd->execute();
            $result = $cmd->fetchAll(PDO::FETCH_OBJ);
            return $result;
        } catch (PDOException $e) {
            error_log("Error in FindProductsByExactName: " . $e->getMessage());
            return array();
        }
    }

    // Kiểm tra xem hình ảnh đã tồn tại chưa
    public function CheckImageExists($fileName)
    {
        try {
            $sql = "SELECT COUNT(*) FROM hinhanh WHERE ten_file = :fileName";
            $cmd = $this->db->prepare($sql);
            $cmd->bindValue(":fileName", $fileName);
            $cmd->execute();
            return $cmd->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error in CheckImageExists: " . $e->getMessage());
            return false;
        }
    }

    // Thêm phương thức kiểm tra trùng lặp ảnh bằng hash MD5
    public function CheckImageExistsByHash($fileHash)
    {
        try {
            // Sử dụng biến static đã kiểm tra từ hàm ThemHinhAnh
            if (!self::$hasCheckedFileHashColumn) {
                $checkColumnSql = "SHOW COLUMNS FROM hinhanh LIKE 'file_hash'";
                $checkColumnStmt = $this->db->prepare($checkColumnSql);
                $checkColumnStmt->execute();

                self::$fileHashColumnExists = ($checkColumnStmt->rowCount() > 0);
                self::$hasCheckedFileHashColumn = true;

                // Nếu chưa có cột file_hash, thêm cột này vào bảng
                if (!self::$fileHashColumnExists) {
                    $addColumnSql = "ALTER TABLE hinhanh ADD COLUMN file_hash VARCHAR(32) NULL";
                    $this->db->exec($addColumnSql);
                    self::$fileHashColumnExists = true;
                    return false; // Vì vừa thêm cột, chắc chắn chưa có dữ liệu
                }
            } else if (!self::$fileHashColumnExists) {
                return false; // Nếu đã kiểm tra trước đó và biết rằng cột không tồn tại
            }

            // Sử dụng prepared statement với tham số được bind trực tiếp
            $sql = "SELECT id FROM hinhanh WHERE file_hash = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fileHash]);
            $result = $stmt->fetch(PDO::FETCH_OBJ);

            return $result ? $result->id : false;
        } catch (PDOException $e) {
            error_log("Error in CheckImageExistsByHash: " . $e->getMessage());
            return false;
        }
    }

    // Đếm số lượng hình ảnh đã áp dụng cho từng sản phẩm
    public function CountImagesForProduct($idhanghoa)
    {
        try {
            $sql = "SELECT COUNT(*) FROM hanghoa_hinhanh WHERE idhanghoa = :idhanghoa";
            $cmd = $this->db->prepare($sql);
            $cmd->bindValue(":idhanghoa", $idhanghoa);
            $cmd->execute();
            return $cmd->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error in CountImagesForProduct: " . $e->getMessage());
            return 0;
        }
    }

    // Lấy tất cả thông tin về hình ảnh đã áp dụng cho một sản phẩm
    public function GetAllImagesForProduct($idhanghoa)
    {
        try {
            $sql = "SELECT h.* FROM hinhanh h
                    INNER JOIN hanghoa_hinhanh hh ON h.id = hh.idhinhanh
                    WHERE hh.idhanghoa = :idhanghoa";
            $cmd = $this->db->prepare($sql);
            $cmd->bindValue(":idhanghoa", $idhanghoa);
            $cmd->execute();
            return $cmd->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Error in GetAllImagesForProduct: " . $e->getMessage());
            return [];
        }
    }

    // Thêm phương thức để cập nhật hình ảnh của sản phẩm
    public function CapNhatHinhAnhSanPham($idhanghoa, $id_hinhanh_moi)
    {
        try {
            $sql = "UPDATE hanghoa SET hinhanh = ? WHERE idhanghoa = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id_hinhanh_moi, $idhanghoa]);
        } catch (Exception $e) {
            return false;
        }
    }

    // Kiểm tra hình ảnh không khớp tên với sản phẩm
    public function GetMismatchedProductImages()
    {
        try {
            $sql = "SELECT h.idhanghoa, h.tenhanghoa, ha.id, ha.ten_file
                   FROM hanghoa h
                   JOIN hinhanh ha ON h.hinhanh = ha.id
                   WHERE ha.ten_file NOT LIKE CONCAT('%', h.tenhanghoa, '%')
                   AND ha.ten_file NOT LIKE CONCAT('%', REPLACE(h.tenhanghoa, ' ', ''), '%')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Error in GetMismatchedProductImages: " . $e->getMessage());
            return [];
        }
    }

    // Kiểm tra và tìm hình ảnh bị thiếu
    public function FindMissingImages()
    {
        try {
            // Tìm các hình ảnh được tham chiếu trong bảng hanghoa nhưng không tồn tại trong bảng hinhanh
            $sql = "SELECT h.idhanghoa, h.tenhanghoa, h.hinhanh
                   FROM hanghoa h
                   LEFT JOIN hinhanh ha ON h.hinhanh = ha.id
                   WHERE h.hinhanh > 0 AND ha.id IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Error in FindMissingImages: " . $e->getMessage());
            return [];
        }
    }

    // Tìm hình ảnh có tên khớp chính xác với tên sản phẩm
    public function FindExactMatchImage($idhanghoa)
    {
        try {
            // Lấy thông tin sản phẩm
            $sql = "SELECT tenhanghoa FROM hanghoa WHERE idhanghoa = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idhanghoa]);
            $product = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$product) {
                return null;
            }

            // Lấy tất cả hình ảnh
            $sql = "SELECT * FROM hinhanh";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $images = $stmt->fetchAll(PDO::FETCH_OBJ);

            // Tìm hình ảnh khớp chính xác
            foreach ($images as $image) {
                if ($this->IsExactImageNameMatch($product->tenhanghoa, $image->ten_file)) {
                    return $image;
                }
            }

            return null;
        } catch (PDOException $e) {
            error_log("Error in FindExactMatchImage: " . $e->getMessage());
            return null;
        }
    }

    // Áp dụng tất cả hình ảnh khớp chính xác cho sản phẩm
    public function ApplyAllExactMatchImages()
    {
        try {
            $this->db->beginTransaction();

            // Lấy tất cả sản phẩm
            $sqlProducts = "SELECT idhanghoa, tenhanghoa FROM hanghoa";
            $stmtProducts = $this->db->prepare($sqlProducts);
            $stmtProducts->execute();
            $products = $stmtProducts->fetchAll(PDO::FETCH_OBJ);

            // Lấy tất cả hình ảnh
            $sqlImages = "SELECT id, ten_file FROM hinhanh";
            $stmtImages = $this->db->prepare($sqlImages);
            $stmtImages->execute();
            $images = $stmtImages->fetchAll(PDO::FETCH_OBJ);

            $matchesCount = 0;

            foreach ($products as $product) {
                foreach ($images as $image) {
                    if ($this->IsExactImageNameMatch($product->tenhanghoa, $image->ten_file)) {
                        // Cập nhật hình ảnh chính cho sản phẩm
                        $sqlUpdate = "UPDATE hanghoa SET hinhanh = ? WHERE idhanghoa = ?";
                        $stmtUpdate = $this->db->prepare($sqlUpdate);
                        $stmtUpdate->execute([$image->id, $product->idhanghoa]);

                        // Thêm liên kết vào bảng hanghoa_hinhanh
                        $checkSql = "SELECT COUNT(*) FROM hanghoa_hinhanh WHERE idhanghoa = ? AND idhinhanh =?";
                        $checkStmt = $this->db->prepare($checkSql);
                        $checkStmt->execute([$product->idhanghoa, $image->id]);
                        $exists = $checkStmt->fetchColumn() > 0;

                        if (!$exists) {
                            $insertSql = "INSERT INTO hanghoa_hinhanh (idhanghoa, idhinhanh) VALUES (?, ?)";
                            $insertStmt = $this->db->prepare($insertSql);
                            $insertStmt->execute([$product->idhanghoa, $image->id]);
                        }

                        $matchesCount++;
                        break; // Chỉ áp dụng hình ảnh đầu tiên khớp
                    }
                }
            }

            $this->db->commit();
            return $matchesCount;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error in ApplyAllExactMatchImages: " . $e->getMessage());
            return 0;
        }
    }

    // Gỡ bỏ hình ảnh khỏi sản phẩm
    public function RemoveImageFromProduct($idhanghoa)
    {
        try {
            // Log cho việc debug
            error_log("RemoveImageFromProduct - Bắt đầu gỡ bỏ hình ảnh cho sản phẩm ID: " . $idhanghoa);

            // Kiểm tra xem sản phẩm có tồn tại không
            $checkProduct = "SELECT hinhanh FROM hanghoa WHERE idhanghoa = ?";
            $stmtCheckProduct = $this->db->prepare($checkProduct);
            $stmtCheckProduct->execute([$idhanghoa]);
            $currentImageId = $stmtCheckProduct->fetchColumn();

            if ($currentImageId === false) {
                error_log("RemoveImageFromProduct - Sản phẩm không tồn tại: " . $idhanghoa);
                return false;
            }

            error_log("RemoveImageFromProduct - Hình ảnh hiện tại của sản phẩm: " . ($currentImageId ?: 'NULL'));

            // Bắt đầu giao dịch
            $this->db->beginTransaction();

            // Đặt hình ảnh về NULL cho sản phẩm
            $sqlUpdate = "UPDATE hanghoa SET hinhanh = NULL WHERE idhanghoa = ?";
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $result = $stmtUpdate->execute([$idhanghoa]);

            if (!$result) {
                error_log("RemoveImageFromProduct - Lỗi khi cập nhật sản phẩm: " . implode(", ", $stmtUpdate->errorInfo()));
                $this->db->rollBack();
                return false;
            }

            error_log("RemoveImageFromProduct - Đã cập nhật sản phẩm thành NULL");

            // Xóa quan hệ trong bảng hanghoa_hinhanh nếu có hình ảnh cũ
            if ($currentImageId) {
                try {
                    // Kiểm tra xem bảng hanghoa_hinhanh có tồn tại không
                    $checkTableSql = "SHOW TABLES LIKE 'hanghoa_hinhanh'";
                    $checkTableStmt = $this->db->prepare($checkTableSql);
                    $checkTableStmt->execute();
                    $tableExists = $checkTableStmt->rowCount() > 0;

                    if (!$tableExists) {
                        error_log("RemoveImageFromProduct - Bảng hanghoa_hinhanh chưa tồn tại, đang tạo mới");
                        $this->CreateHanghoaHinhanhTable();
                    }

                    $sqlDeleteRelation = "DELETE FROM hanghoa_hinhanh WHERE idhanghoa = ? AND idhinhanh = ?";
                    $stmtDeleteRelation = $this->db->prepare($sqlDeleteRelation);
                    $resultDelete = $stmtDeleteRelation->execute([$idhanghoa, $currentImageId]);

                    if (!$resultDelete) {
                        error_log("RemoveImageFromProduct - Lỗi khi xóa quan hệ: " . implode(", ", $stmtDeleteRelation->errorInfo()));
                    } else {
                        error_log("RemoveImageFromProduct - Đã xóa quan hệ thành công");
                    }
                } catch (Exception $e) {
                    error_log("RemoveImageFromProduct - Lỗi khi xử lý bảng hanghoa_hinhanh: " . $e->getMessage());
                    // Không rollback ở đây vì việc xóa quan hệ không quan trọng bằng việc cập nhật hình ảnh chính
                }
            }

            // Hoàn tất giao dịch
            $this->db->commit();
            error_log("RemoveImageFromProduct - Gỡ bỏ hình ảnh hoàn tất thành công");

            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error in RemoveImageFromProduct: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Unexpected error in RemoveImageFromProduct: " . $e->getMessage());
            return false;
        }
    }

    // Gỡ bỏ tất cả hình ảnh không khớp tên với sản phẩm
    public function RemoveAllMismatchedImages()
    {
        try {
            error_log("RemoveAllMismatchedImages - Bắt đầu gỡ bỏ tất cả hình ảnh không khớp");

            // Bắt đầu giao dịch
            $this->db->beginTransaction();

            // Lấy danh sách sản phẩm có hình ảnh không khớp
            $mismatched = $this->GetMismatchedProductImages();
            $count = 0;

            if (empty($mismatched)) {
                error_log("RemoveAllMismatchedImages - Không tìm thấy hình ảnh không khớp nào");
                $this->db->commit();
                return 0;
            }

            error_log("RemoveAllMismatchedImages - Tìm thấy " . count($mismatched) . " hình ảnh không khớp");

            foreach ($mismatched as $item) {
                error_log("RemoveAllMismatchedImages - Đang xử lý sản phẩm ID: " . $item->idhanghoa . ", Tên: " . $item->tenhanghoa . ", Hình ảnh ID: " . $item->id);

                // Đặt hình ảnh về NULL cho sản phẩm
                $sqlUpdate = "UPDATE hanghoa SET hinhanh = NULL WHERE idhanghoa = ?";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                $resultUpdate = $stmtUpdate->execute([$item->idhanghoa]);

                if (!$resultUpdate) {
                    error_log("RemoveAllMismatchedImages - Lỗi khi cập nhật sản phẩm ID " . $item->idhanghoa . ": " . implode(", ", $stmtUpdate->errorInfo()));
                    continue;
                }

                // Xóa quan hệ trong bảng hanghoa_hinhanh
                $sqlDeleteRelation = "DELETE FROM hanghoa_hinhanh WHERE idhanghoa = ? AND idhinhanh = ?";
                $stmtDeleteRelation = $this->db->prepare($sqlDeleteRelation);
                $resultDelete = $stmtDeleteRelation->execute([$item->idhanghoa, $item->id]);

                if (!$resultDelete) {
                    error_log("RemoveAllMismatchedImages - Lỗi khi xóa quan hệ cho sản phẩm ID " . $item->idhanghoa . ": " . implode(", ", $stmtDeleteRelation->errorInfo()));
                }

                $count++;
                error_log("RemoveAllMismatchedImages - Đã gỡ bỏ hình ảnh cho sản phẩm ID: " . $item->idhanghoa);
            }

            // Hoàn tất giao dịch
            $this->db->commit();

            error_log("RemoveAllMismatchedImages - Hoàn tất gỡ bỏ " . $count . " hình ảnh");
            return $count;
        } catch (PDOException $e) {
            // Rollback nếu có lỗi
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error in RemoveAllMismatchedImages: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            // Bắt các exception khác
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Unexpected error in RemoveAllMismatchedImages: " . $e->getMessage());
            return false;
        }
    }

    // Kiểm tra xem tên file hình ảnh có khớp chính xác với tên sản phẩm không (phân biệt hoa thường)
    public function IsExactImageNameMatch($tenhanghoa, $ten_file)
    {
        // Tách tên file không có phần mở rộng
        $imageNameWithoutExt = pathinfo($ten_file, PATHINFO_FILENAME);

        // So sánh chính xác giữa tên sản phẩm và tên file, chỉ loại bỏ khoảng trắng đầu/cuối
        // Giữ nguyên phân biệt chữ hoa/thường để so khớp tuyệt đối
        if (trim($tenhanghoa) === trim($imageNameWithoutExt)) {
            return true;
        }

        return false;
    }
}
