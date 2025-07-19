<?php
class BaoCao
{
    private $conn;

    /**
     * Lấy kết nối cơ sở dữ liệu
     * @return PDO Đối tượng kết nối PDO
     */
    public function getConnection()
    {
        return $this->conn;
    }

    public function __construct()
    {
        // Kết nối đến cơ sở dữ liệu
        require_once(__DIR__ . '/../mod/database.php');
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }

    /**
     * Lấy doanh thu theo ngày
     * @param string $date Ngày cần lấy doanh thu (format: Y-m-d)
     * @return float Tổng doanh thu
     */
    public function getDoanhThuNgay($date)
    {
        try {
            $sql = "SELECT COALESCE(SUM(tong_tien), 0) as doanh_thu
                    FROM don_hang
                    WHERE DATE(ngay_tao) = :date
                    AND trang_thai = 'approved'
                    AND trang_thai_thanh_toan = 'paid'";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['date' => $date]);
            return $stmt->fetch(PDO::FETCH_ASSOC)['doanh_thu'];
        } catch (PDOException $e) {
            error_log("Lỗi khi lấy doanh thu ngày: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Lấy doanh thu theo tháng
     * @param int $month Tháng cần lấy doanh thu (1-12)
     * @param int $year Năm cần lấy doanh thu
     * @return float Tổng doanh thu
     */
    public function getDoanhThuThang($month, $year)
    {
        try {
            $sql = "SELECT COALESCE(SUM(tong_tien), 0) as doanh_thu
                    FROM don_hang
                    WHERE MONTH(ngay_tao) = :month
                    AND YEAR(ngay_tao) = :year
                    AND trang_thai = 'approved'
                    AND trang_thai_thanh_toan = 'paid'";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['month' => $month, 'year' => $year]);
            return $stmt->fetch(PDO::FETCH_ASSOC)['doanh_thu'];
        } catch (PDOException $e) {
            error_log("Lỗi khi lấy doanh thu tháng: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Lấy doanh thu theo năm
     * @param int $year Năm cần lấy doanh thu
     * @return float Tổng doanh thu
     */
    public function getDoanhThuNam($year)
    {
        try {
            // Kiểm tra năm hợp lệ
            $year = intval($year);
            if ($year < 2000 || $year > 2100) {
                error_log("Năm không hợp lệ: $year");
                return 0;
            }

            $sql = "SELECT COALESCE(SUM(tong_tien), 0) as doanh_thu,
                           COUNT(*) as so_don_hang
                    FROM don_hang
                    WHERE YEAR(ngay_tao) = :year
                    AND trang_thai = 'approved'";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['year' => $year]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Lấy doanh thu năm $year: " . $result['doanh_thu'] . " đ, " . $result['so_don_hang'] . " đơn hàng");
            return floatval($result['doanh_thu']);
        } catch (PDOException $e) {
            error_log("Lỗi khi lấy doanh thu năm: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Lấy doanh thu theo khoảng thời gian
     * @param string $startDate Ngày bắt đầu (nhiều định dạng được hỗ trợ)
     * @param string $endDate Ngày kết thúc (nhiều định dạng được hỗ trợ)
     * @return float Tổng doanh thu
     */
    public function getDoanhThuTheoKhoangThoiGian($startDate, $endDate)
    {
        try {
            // Chuẩn hóa định dạng ngày
            $startDateFormatted = $this->isValidDate($startDate);
            $endDateFormatted = $this->isValidDate($endDate);

            if (!$startDateFormatted || !$endDateFormatted) {
                error_log("Định dạng ngày không hợp lệ: startDate=$startDate, endDate=$endDate");
                return 0;
            }

            // Đảm bảo startDate <= endDate
            if (strtotime($startDateFormatted) > strtotime($endDateFormatted)) {
                $temp = $startDateFormatted;
                $startDateFormatted = $endDateFormatted;
                $endDateFormatted = $temp;
                error_log("Đã đổi vị trí startDate và endDate vì startDate > endDate");
            }

            $sql = "SELECT COALESCE(SUM(tong_tien), 0) as doanh_thu
                    FROM don_hang
                    WHERE DATE(ngay_tao) BETWEEN :startDate AND :endDate
                    AND trang_thai = 'approved'";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['startDate' => $startDateFormatted, 'endDate' => $endDateFormatted]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC)['doanh_thu'];
            error_log("Tổng doanh thu từ $startDateFormatted đến $endDateFormatted: $result");
            return floatval($result);
        } catch (PDOException $e) {
            error_log("Lỗi khi lấy doanh thu theo khoảng thời gian: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Lấy danh sách doanh thu theo ngày trong khoảng thời gian
     * @param string $startDate Ngày bắt đầu (nhiều định dạng được hỗ trợ)
     * @param string $endDate Ngày kết thúc (nhiều định dạng được hỗ trợ)
     * @return array Danh sách doanh thu theo ngày
     */
    public function getDoanhThuTheoNgayTrongKhoang($startDate = null, $endDate = null)
    {
        try {
            $params = [];
            $whereClauses = ["trang_thai = 'approved'"];

            if ($startDate && $endDate) {
                $startDateFormatted = $this->isValidDate($startDate);
                $endDateFormatted = $this->isValidDate($endDate);

                if ($startDateFormatted && $endDateFormatted) {
                    if (strtotime($startDateFormatted) > strtotime($endDateFormatted)) {
                        list($startDateFormatted, $endDateFormatted) = [$endDateFormatted, $startDateFormatted];
                    }
                    $whereClauses[] = "DATE(ngay_tao) BETWEEN :startDate AND :endDate";
                    $params[':startDate'] = $startDateFormatted;
                    $params[':endDate'] = $endDateFormatted;
                }
            }

            $whereSql = "WHERE " . implode(" AND ", $whereClauses);

            // Truy vấn dữ liệu
            $sql = "SELECT DATE(ngay_tao) as ngay,
                           COALESCE(SUM(tong_tien), 0) as doanh_thu,
                           COUNT(*) as so_don_hang
                    FROM don_hang
                    $whereSql
                    GROUP BY DATE(ngay_tao)
                    ORDER BY ngay";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            // Lấy kết quả
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Lấy doanh thu theo ngày: " . count($result) . " bản ghi");

            return $result;
        } catch (PDOException $e) {
            error_log("Lỗi khi lấy danh sách doanh thu theo ngày: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Kiểm tra định dạng ngày hợp lệ và chuẩn hóa về định dạng Y-m-d
     * @param string $date Ngày cần kiểm tra
     * @return string|false Ngày đã chuẩn hóa hoặc false nếu không hợp lệ
     */
    private function isValidDate($date)
    {
        if (!$date) {
            error_log("Ngày trống");
            return false;
        }

        // Xử lý trường hợp đặc biệt: ngày có định dạng không chuẩn
        // Ví dụ: 19/4/2025 -> 19/04/2025
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];
            $formattedDate = "$day/$month/$year";
            $date = $formattedDate;
        }

        // Thử với định dạng Y-m-d (YYYY-MM-DD)
        $format = 'Y-m-d';
        $d = DateTime::createFromFormat($format, $date);
        if ($d && $d->format($format) === $date) {
            return $date;
        }

        // Thử với định dạng d/m/Y (DD/MM/YYYY)
        $format = 'd/m/Y';
        $d = DateTime::createFromFormat($format, $date);
        if ($d && $d->format($format) === $date) {
            return $d->format('Y-m-d');
        }

        // Thử với định dạng m/d/Y (MM/DD/YYYY)
        $format = 'm/d/Y';
        $d = DateTime::createFromFormat($format, $date);
        if ($d && $d->format($format) === $date) {
            return $d->format('Y-m-d');
        }

        // Thử với định dạng d-m-Y (DD-MM-YYYY)
        $format = 'd-m-Y';
        $d = DateTime::createFromFormat($format, $date);
        if ($d && $d->format($format) === $date) {
            return $d->format('Y-m-d');
        }

        // Thử với định dạng tùy chỉnh cho trường hợp đặc biệt
        // Ví dụ: 19/4/2025 (không có số 0 đứng trước)
        $format = 'j/n/Y';
        $d = DateTime::createFromFormat($format, $date);
        if ($d) {
            $formatted = $d->format('j/n/Y');
            if ($formatted === $date) {
                return $d->format('Y-m-d');
            }
        }

        // Ghi log lỗi
        error_log("Định dạng ngày không hợp lệ: $date");
        return false;
    }

    /**
     * Lấy danh sách doanh thu theo tháng trong năm
     * @param int $year Năm cần lấy doanh thu
     * @return array Danh sách doanh thu theo tháng
     */
    public function getDoanhThuTheoThangTrongNam($year)
    {
        try {
            // Kiểm tra năm hợp lệ
            $year = intval($year);
            if ($year < 2000 || $year > 2100) {
                error_log("Năm không hợp lệ: $year");
                return [];
            }

            $sql = "SELECT MONTH(ngay_tao) as thang,
                           COALESCE(SUM(tong_tien), 0) as doanh_thu,
                           COUNT(*) as so_don_hang
                    FROM don_hang
                    WHERE YEAR(ngay_tao) = :year
                    AND trang_thai = 'approved'
                    GROUP BY MONTH(ngay_tao)
                    ORDER BY thang";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['year' => $year]);

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Lấy doanh thu theo tháng trong năm $year: " . count($result) . " bản ghi");
            return $result;
        } catch (PDOException $e) {
            error_log("Lỗi khi lấy danh sách doanh thu theo tháng: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy danh sách sản phẩm bán chạy
     * @param string $startDate Ngày bắt đầu (nhiều định dạng được hỗ trợ)
     * @param string $endDate Ngày kết thúc (nhiều định dạng được hỗ trợ)
     * @param int $limit Số lượng sản phẩm tối đa
     * @return array Danh sách sản phẩm bán chạy
     */
    public function getSanPhamBanChay($startDate, $endDate, $limit = 10)
    {
        try {
            // Ghi log các tham số đầu vào
            error_log("getSanPhamBanChay called with startDate: $startDate, endDate: $endDate, limit: $limit");

            // Chuẩn hóa định dạng ngày
            $startDateFormatted = $this->isValidDate($startDate);
            $endDateFormatted = $this->isValidDate($endDate);

            // Ghi log ngày đã được chuẩn hóa
            error_log("Formatted dates: startDate=$startDateFormatted, endDate=$endDateFormatted");

            if (!$startDateFormatted || !$endDateFormatted) {
                error_log("Định dạng ngày không hợp lệ: startDate=$startDate, endDate=$endDate");
                return [];
            }

            // Đảm bảo startDate <= endDate
            if (strtotime($startDateFormatted) > strtotime($endDateFormatted)) {
                $temp = $startDateFormatted;
                $startDateFormatted = $endDateFormatted;
                $endDateFormatted = $temp;
                error_log("Đã đổi vị trí startDate và endDate vì startDate > endDate");
            }

            $sql = "SELECT h.idhanghoa, h.tenhanghoa, h.hinhanh,
                           SUM(oi.so_luong) as so_luong_ban,
                           SUM(oi.gia * oi.so_luong) as doanh_thu,
                           COUNT(DISTINCT o.id) as so_don_hang
                    FROM don_hang o
                    JOIN chi_tiet_don_hang oi ON o.id = oi.ma_don_hang
                    JOIN hanghoa h ON oi.ma_san_pham = h.idhanghoa
                    WHERE DATE(o.ngay_tao) BETWEEN :startDate AND :endDate
                    AND o.trang_thai = 'approved'
                    GROUP BY h.idhanghoa
                    ORDER BY so_luong_ban DESC
                    LIMIT :limit";
            
            // Ghi log câu truy vấn SQL
            error_log("SQL query: $sql");

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':startDate', $startDateFormatted, PDO::PARAM_STR);
            $stmt->bindParam(':endDate', $endDateFormatted, PDO::PARAM_STR);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Ghi log số lượng kết quả
            error_log("Lấy sản phẩm bán chạy từ $startDateFormatted đến $endDateFormatted: " . count($result) . " sản phẩm");
            
            // Ghi log kết quả nếu có
            if (count($result) > 0) {
                error_log("Query result: " . print_r($result, true));
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Lỗi khi lấy danh sách sản phẩm bán chạy: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Tính lợi nhuận từ sản phẩm đã bán
     * @param string $startDate Ngày bắt đầu (nhiều định dạng được hỗ trợ)
     * @param string $endDate Ngày kết thúc (nhiều định dạng được hỗ trợ)
     * @return array Thông tin lợi nhuận
     */
    public function getLoiNhuan($startDate, $endDate)
    {
        try {
            // Chuẩn hóa định dạng ngày
            $startDateFormatted = $this->isValidDate($startDate);
            $endDateFormatted = $this->isValidDate($endDate);

            if (!$startDateFormatted || !$endDateFormatted) {
                error_log("Định dạng ngày không hợp lệ: startDate=$startDate, endDate=$endDate");
                return [
                    'doanh_thu' => 0,
                    'gia_von' => 0,
                    'loi_nhuan' => 0,
                    'ti_le_loi_nhuan' => 0
                ];
            }

            // Đảm bảo startDate <= endDate
            if (strtotime($startDateFormatted) > strtotime($endDateFormatted)) {
                $temp = $startDateFormatted;
                $startDateFormatted = $endDateFormatted;
                $endDateFormatted = $temp;
                error_log("Đã đổi vị trí startDate và endDate vì startDate > endDate");
            }

            // Lấy tổng doanh thu
            $doanhThu = $this->getDoanhThuTheoKhoangThoiGian($startDateFormatted, $endDateFormatted);

            // Lấy tổng giá vốn (giá nhập) của sản phẩm đã bán
            $sql = "SELECT COALESCE(SUM(h.giathamkhao * oi.so_luong), 0) as tong_gia_von
                    FROM don_hang o
                    JOIN chi_tiet_don_hang oi ON o.id = oi.ma_don_hang
                    JOIN hanghoa h ON oi.ma_san_pham = h.idhanghoa
                    WHERE DATE(o.ngay_tao) BETWEEN :startDate AND :endDate
                    AND o.trang_thai = 'approved'";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['startDate' => $startDateFormatted, 'endDate' => $endDateFormatted]);
            $giaVon = floatval($stmt->fetch(PDO::FETCH_ASSOC)['tong_gia_von']);

            // Tính lợi nhuận
            $loiNhuan = $doanhThu - $giaVon;

            return [
                'doanh_thu' => $doanhThu,
                'gia_von' => $giaVon,
                'loi_nhuan' => $loiNhuan,
                'ti_le_loi_nhuan' => ($doanhThu > 0) ? ($loiNhuan / $doanhThu * 100) : 0
            ];
        } catch (PDOException $e) {
            error_log("Lỗi khi tính lợi nhuận: " . $e->getMessage());
            return [
                'doanh_thu' => 0,
                'gia_von' => 0,
                'loi_nhuan' => 0,
                'ti_le_loi_nhuan' => 0
            ];
        }
    }

    /**
     * Lấy lợi nhuận theo sản phẩm
     * @param string $startDate Ngày bắt đầu (nhiều định dạng được hỗ trợ)
     * @param string $endDate Ngày kết thúc (nhiều định dạng được hỗ trợ)
     * @param int $limit Số lượng sản phẩm tối đa
     * @return array Danh sách lợi nhuận theo sản phẩm
     */
    public function getLoiNhuanTheoSanPham($startDate, $endDate, $limit = 10)
    {
        try {
            // Chuẩn hóa định dạng ngày
            $startDateFormatted = $this->isValidDate($startDate);
            $endDateFormatted = $this->isValidDate($endDate);

            if (!$startDateFormatted || !$endDateFormatted) {
                error_log("Định dạng ngày không hợp lệ: startDate=$startDate, endDate=$endDate");
                return [];
            }

            // Đảm bảo startDate <= endDate
            if (strtotime($startDateFormatted) > strtotime($endDateFormatted)) {
                $temp = $startDateFormatted;
                $startDateFormatted = $endDateFormatted;
                $endDateFormatted = $temp;
                error_log("Đã đổi vị trí startDate và endDate vì startDate > endDate");
            }

            $sql = "SELECT h.idhanghoa, h.tenhanghoa, h.hinhanh,
                           SUM(oi.so_luong) as so_luong_ban,
                           SUM(oi.gia * oi.so_luong) as doanh_thu,
                           SUM(h.giathamkhao * oi.so_luong) as gia_von,
                           SUM(oi.gia * oi.so_luong) - SUM(h.giathamkhao * oi.so_luong) as loi_nhuan,
                           (SUM(oi.gia * oi.so_luong) - SUM(h.giathamkhao * oi.so_luong)) / SUM(oi.gia * oi.so_luong) * 100 as ti_le_loi_nhuan
                    FROM don_hang o
                    JOIN chi_tiet_don_hang oi ON o.id = oi.ma_don_hang
                    JOIN hanghoa h ON oi.ma_san_pham = h.idhanghoa
                    WHERE DATE(o.ngay_tao) BETWEEN :startDate AND :endDate
                    AND o.trang_thai = 'approved'
                    GROUP BY h.idhanghoa
                    ORDER BY loi_nhuan DESC
                    LIMIT :limit";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':startDate', $startDateFormatted, PDO::PARAM_STR);
            $stmt->bindParam(':endDate', $endDateFormatted, PDO::PARAM_STR);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Lỗi khi lấy lợi nhuận theo sản phẩm: " . $e->getMessage());
            return [];
        }
    }
    /**
     * Lấy thống kê đơn hàng theo trạng thái
     * @param string $startDate Ngày bắt đầu (nhiều định dạng được hỗ trợ)
     * @param string $endDate Ngày kết thúc (nhiều định dạng được hỗ trợ)
     * @return array Thống kê đơn hàng theo trạng thái
     */
    public function getThongKeTheoTrangThai($startDate, $endDate)
    {
        try {
            // Chuẩn hóa định dạng ngày
            $startDateFormatted = $this->isValidDate($startDate);
            $endDateFormatted = $this->isValidDate($endDate);

            if (!$startDateFormatted || !$endDateFormatted) {
                error_log("Định dạng ngày không hợp lệ: startDate=$startDate, endDate=$endDate");
                return [];
            }

            // Đảm bảo startDate <= endDate
            if (strtotime($startDateFormatted) > strtotime($endDateFormatted)) {
                $temp = $startDateFormatted;
                $startDateFormatted = $endDateFormatted;
                $endDateFormatted = $temp;
                error_log("Đã đổi vị trí startDate và endDate vì startDate > endDate");
            }

            $sql = "SELECT trang_thai, COUNT(*) as so_luong, SUM(tong_tien) as tong_tien
                    FROM don_hang
                    WHERE DATE(ngay_tao) BETWEEN :startDate AND :endDate
                    GROUP BY trang_thai";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['startDate' => $startDateFormatted, 'endDate' => $endDateFormatted]);

            $result = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $trang_thai = $row['trang_thai'];
                $statusText = '';

                switch ($trang_thai) {
                    case 'pending':
                        $statusText = 'Chờ xử lý';
                        break;
                    case 'approved':
                        $statusText = 'Đã duyệt';
                        break;
                    case 'cancelled':
                        $statusText = 'Đã hủy';
                        break;
                    default:
                        $statusText = $trang_thai;
                        break;
                }

                $result[] = [
                    'trang_thai' => $trang_thai,
                    'status_text' => $statusText,
                    'so_luong' => $row['so_luong'],
                    'tong_tien' => $row['tong_tien']
                ];
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Lỗi khi lấy thống kê theo trạng thái: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy thống kê đơn hàng theo ngày
     * @param string $startDate Ngày bắt đầu (nhiều định dạng được hỗ trợ)
     * @param string $endDate Ngày kết thúc (nhiều định dạng được hỗ trợ)
     * @return array Thống kê đơn hàng theo ngày
     */
    public function getThongKeTheoNgay($startDate, $endDate)
    {
        try {
            // Chuẩn hóa định dạng ngày
            $startDateFormatted = $this->isValidDate($startDate);
            $endDateFormatted = $this->isValidDate($endDate);

            if (!$startDateFormatted || !$endDateFormatted) {
                error_log("Định dạng ngày không hợp lệ: startDate=$startDate, endDate=$endDate");
                return [];
            }

            // Đảm bảo startDate <= endDate
            if (strtotime($startDateFormatted) > strtotime($endDateFormatted)) {
                $temp = $startDateFormatted;
                $startDateFormatted = $endDateFormatted;
                $endDateFormatted = $temp;
                error_log("Đã đổi vị trí startDate và endDate vì startDate > endDate");
            }

            $sql = "SELECT DATE(ngay_tao) as ngay,
                           COUNT(*) as tong_don,
                           SUM(CASE WHEN trang_thai = 'approved' THEN 1 ELSE 0 END) as don_duyet,
                           SUM(CASE WHEN trang_thai = 'cancelled' THEN 1 ELSE 0 END) as don_huy,
                           SUM(CASE WHEN trang_thai = 'pending' THEN 1 ELSE 0 END) as don_cho,
                           SUM(tong_tien) as tong_tien,
                           SUM(CASE WHEN trang_thai = 'approved' THEN tong_tien ELSE 0 END) as tien_duyet
                    FROM don_hang
                    WHERE DATE(ngay_tao) BETWEEN :startDate AND :endDate
                    GROUP BY DATE(ngay_tao)
                    ORDER BY ngay";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['startDate' => $startDateFormatted, 'endDate' => $endDateFormatted]);

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Lấy thống kê theo ngày từ $startDateFormatted đến $endDateFormatted: " . count($result) . " bản ghi");
            return $result;
        } catch (PDOException $e) {
            error_log("Lỗi khi lấy thống kê theo ngày: " . $e->getMessage());
            return [];
        }
    }
}
