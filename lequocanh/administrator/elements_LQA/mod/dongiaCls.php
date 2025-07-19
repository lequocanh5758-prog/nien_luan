<?php
$s = '../../elements_LQA/mod/database.php';
if (file_exists($s)) {
    $f = $s;
} else {
    $f = './elements_LQA/mod/database.php';
    if (!file_exists($f)) {
        $f = './administrator/elements_LQA/mod/database.php';
    }
}
require_once $f;

class Dongia
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // Lấy tất cả các đơn giá
    public function DongiaGetAll()
    {
        try {
            $sql = 'SELECT d.*, h.tenhanghoa
                   FROM dongia d
                   LEFT JOIN hanghoa h ON d.idHangHoa = h.idhanghoa
                   ORDER BY d.idHangHoa, d.apDung DESC, d.ngayApDung DESC';
            $getAll = $this->db->prepare($sql);
            $getAll->setFetchMode(PDO::FETCH_OBJ);
            $getAll->execute();
            return $getAll->fetchAll();
        } catch (PDOException $e) {
            error_log("DongiaGetAll Error: " . $e->getMessage());
            return [];
        }
    }

    // Thêm đơn giá mới với logic cải tiến
    public function DongiaAdd($idHangHoa, $giaBan, $ngayApDung, $ngayKetThuc, $dieuKien = '', $ghiChu = '', $autoApply = true)
    {
        try {
            error_log("DongiaAdd: Starting with params - idHangHoa: $idHangHoa, giaBan: $giaBan, ngayApDung: $ngayApDung, ngayKetThuc: $ngayKetThuc");

            // Tạm thời bỏ qua kiểm tra trùng lặp để debug
            // if ($this->checkDuplicatePrice($idHangHoa, $giaBan, $ngayApDung, $ngayKetThuc)) {
            //     error_log("DongiaAdd: Duplicate price detected");
            //     return false;
            // }

            // Nếu autoApply = true, đặt tất cả các đơn giá hiện tại thành không áp dụng
            if ($autoApply) {
                error_log("DongiaAdd: Setting all existing prices to false for product $idHangHoa");
                $this->DongiaSetAllToFalse($idHangHoa);
            }

            // Thêm đơn giá mới
            $sql = "INSERT INTO dongia (idHangHoa, giaBan, ngayApDung, ngayKetThuc, dieuKien, ghiChu, apDung)
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
            $data = array($idHangHoa, $giaBan, $ngayApDung, $ngayKetThuc, $dieuKien, $ghiChu, $autoApply ? 1 : 0);

            error_log("DongiaAdd: Executing SQL: $sql");
            error_log("DongiaAdd: With data: " . print_r($data, true));

            $add = $this->db->prepare($sql);
            $result = $add->execute($data);

            if (!$result) {
                $errorInfo = $add->errorInfo();
                error_log("DongiaAdd: SQL execution failed - " . print_r($errorInfo, true));
                return false;
            }

            $insertId = $this->db->lastInsertId();
            error_log("DongiaAdd: Insert successful, ID: $insertId");

            // Chỉ cập nhật giá tham khảo nếu đơn giá này được áp dụng
            if ($autoApply) {
                error_log("DongiaAdd: Updating reference price for product $idHangHoa");
                $this->HanghoaUpdatePrice($idHangHoa, $giaBan);
                
                // Tạm thời bỏ qua log để tránh lỗi
                // $this->logPriceChange($idHangHoa, $giaBan, 'Thêm đơn giá mới', $insertId);
            }

            return $insertId;
        } catch (PDOException $e) {
            error_log("DongiaAdd Error: " . $e->getMessage());
            error_log("DongiaAdd Error Stack: " . $e->getTraceAsString());
            return false;
        }
    }

    // Xóa đơn giá theo ID
    public function DongiaDelete($idDonGia)
    {
        try {
            // Lấy thông tin đơn giá trước khi xóa
            $dongia = $this->DongiaGetbyId($idDonGia);
            if (!$dongia) {
                return false;
            }

            $sql = "DELETE FROM dongia WHERE idDonGia = ?";
            $data = array($idDonGia);

            $del = $this->db->prepare($sql);
            $result = $del->execute($data);

            // Nếu đơn giá đang được áp dụng, tìm đơn giá mới nhất để áp dụng
            if ($dongia->apDung) {
                $this->UpdateLatestPriceForProduct($dongia->idHangHoa);
            }

            return $result;
        } catch (PDOException $e) {
            error_log("DongiaDelete Error: " . $e->getMessage());
            return false;
        }
    }

    // Cập nhật thông tin đơn giá
    public function DongiaUpdate($idDonGia, $idHangHoa, $giaBan, $ngayApDung, $ngayKetThuc, $dieuKien = '', $ghiChu = '')
    {
        try {
            $sql = "UPDATE dongia
                   SET idHangHoa = ?, giaBan = ?, ngayApDung = ?, ngayKetThuc = ?, dieuKien = ?, ghiChu = ?
                   WHERE idDonGia = ?";
            $data = array($idHangHoa, $giaBan, $ngayApDung, $ngayKetThuc, $dieuKien, $ghiChu, $idDonGia);

            $update = $this->db->prepare($sql);
            $result = $update->execute($data);

            // Nếu đơn giá đang được áp dụng, cập nhật giá tham khảo trong bảng hanghoa
            $dongia = $this->DongiaGetbyId($idDonGia);
            if ($dongia && $dongia->apDung) {
                $this->HanghoaUpdatePrice($idHangHoa, $giaBan);
            }

            return $result;
        } catch (PDOException $e) {
            error_log("DongiaUpdate Error: " . $e->getMessage());
            return false;
        }
    }

    // Lấy thông tin đơn giá theo ID
    public function DongiaGetbyId($idDonGia)
    {
        try {
            $sql = 'SELECT d.*, h.tenhanghoa
                   FROM dongia d
                   LEFT JOIN hanghoa h ON d.idHangHoa = h.idhanghoa
                   WHERE d.idDonGia = ?';
            $data = array($idDonGia);

            $getOne = $this->db->prepare($sql);
            $getOne->setFetchMode(PDO::FETCH_OBJ);
            $getOne->execute($data);

            return $getOne->fetch();
        } catch (PDOException $e) {
            error_log("DongiaGetbyId Error: " . $e->getMessage());
            return false;
        }
    }

    // Lấy đơn giá theo ID hàng hóa
    public function DongiaGetbyIdHanghoa($idHangHoa)
    {
        try {
            $sql = 'SELECT d.*, h.tenhanghoa
                   FROM dongia d
                   LEFT JOIN hanghoa h ON d.idHangHoa = h.idhanghoa
                   WHERE d.idHangHoa = ?
                   ORDER BY d.apDung DESC, d.ngayApDung DESC';
            $data = array($idHangHoa);

            $getAll = $this->db->prepare($sql);
            $getAll->setFetchMode(PDO::FETCH_OBJ);
            $getAll->execute($data);

            return $getAll->fetchAll();
        } catch (PDOException $e) {
            error_log("DongiaGetbyIdHanghoa Error: " . $e->getMessage());
            return [];
        }
    }

    // Đặt tất cả đơn giá của một sản phẩm thành không áp dụng
    public function DongiaSetAllToFalse($idHangHoa)
    {
        try {
            $sql = "UPDATE dongia SET apDung = 0 WHERE idHangHoa = ?";
            $data = array($idHangHoa);

            $update = $this->db->prepare($sql);
            return $update->execute($data);
        } catch (PDOException $e) {
            error_log("DongiaSetAllToFalse Error: " . $e->getMessage());
            return false;
        }
    }

    // Cập nhật trạng thái áp dụng của đơn giá
    public function DongiaUpdateStatus($idDonGia, $apDung)
    {
        try {
            $sql = "UPDATE dongia SET apDung = ? WHERE idDonGia = ?";
            $data = array($apDung ? 1 : 0, $idDonGia);

            $update = $this->db->prepare($sql);
            return $update->execute($data);
        } catch (PDOException $e) {
            error_log("DongiaUpdateStatus Error: " . $e->getMessage());
            return false;
        }
    }

    // Cập nhật giá tham khảo trong bảng hanghoa
    public function HanghoaUpdatePrice($idHangHoa, $giaBan)
    {
        try {
            $sql = "UPDATE hanghoa SET giathamkhao = ? WHERE idhanghoa = ?";
            $data = array($giaBan, $idHangHoa);

            $update = $this->db->prepare($sql);
            return $update->execute($data);
        } catch (PDOException $e) {
            error_log("HanghoaUpdatePrice Error: " . $e->getMessage());
            return false;
        }
    }

    // Cập nhật đơn giá mới nhất cho sản phẩm
    public function UpdateLatestPriceForProduct($idHangHoa)
    {
        try {
            // Tìm đơn giá mới nhất
            $sql = "SELECT * FROM dongia WHERE idHangHoa = ? ORDER BY ngayApDung DESC LIMIT 1";
            $data = array($idHangHoa);

            $getLatest = $this->db->prepare($sql);
            $getLatest->setFetchMode(PDO::FETCH_OBJ);
            $getLatest->execute($data);

            $latestPrice = $getLatest->fetch();

            if ($latestPrice) {
                // Đặt đơn giá mới nhất thành đang áp dụng
                $this->DongiaUpdateStatus($latestPrice->idDonGia, true);

                // Cập nhật giá tham khảo trong bảng hanghoa
                $this->HanghoaUpdatePrice($idHangHoa, $latestPrice->giaBan);

                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("UpdateLatestPriceForProduct Error: " . $e->getMessage());
            return false;
        }
    }

    // Lấy đơn giá đang áp dụng cho sản phẩm
    public function DongiaGetActiveByProduct($idHangHoa)
    {
        try {
            $sql = "SELECT * FROM dongia WHERE idHangHoa = ? AND apDung = 1 LIMIT 1";
            $data = array($idHangHoa);

            $getActive = $this->db->prepare($sql);
            $getActive->setFetchMode(PDO::FETCH_OBJ);
            $getActive->execute($data);

            return $getActive->fetch();
        } catch (PDOException $e) {
            error_log("DongiaGetActiveByProduct Error: " . $e->getMessage());
            return false;
        }
    }

    // Kiểm tra đơn giá trùng lặp
    private function checkDuplicatePrice($idHangHoa, $giaBan, $ngayApDung, $ngayKetThuc)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM dongia 
                   WHERE idHangHoa = ? AND giaBan = ? 
                   AND ((ngayApDung <= ? AND ngayKetThuc >= ?) 
                   OR (ngayApDung <= ? AND ngayKetThuc >= ?))";
            $data = array($idHangHoa, $giaBan, $ngayApDung, $ngayApDung, $ngayKetThuc, $ngayKetThuc);

            $check = $this->db->prepare($sql);
            $check->execute($data);
            $result = $check->fetch(PDO::FETCH_ASSOC);

            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("checkDuplicatePrice Error: " . $e->getMessage());
            return false;
        }
    }

    // Ghi log lịch sử thay đổi giá
    private function logPriceChange($idHangHoa, $giaBan, $action, $idDonGia = null)
    {
        try {
            // Tạo bảng price_history nếu chưa có
            $this->createPriceHistoryTable();

            $sql = "INSERT INTO price_history (idHangHoa, giaBan, action_type, idDonGia, created_at, user_id) 
                   VALUES (?, ?, ?, ?, NOW(), ?)";
            $userId = $_SESSION['ADMIN']['id'] ?? $_SESSION['user_id'] ?? 0;
            $data = array($idHangHoa, $giaBan, $action, $idDonGia, $userId);

            $log = $this->db->prepare($sql);
            return $log->execute($data);
        } catch (PDOException $e) {
            error_log("logPriceChange Error: " . $e->getMessage());
            return false;
        }
    }

    // Tạo bảng lịch sử giá
    private function createPriceHistoryTable()
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS price_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                idHangHoa INT NOT NULL,
                giaBan DECIMAL(15,2) NOT NULL,
                action_type VARCHAR(100) NOT NULL,
                idDonGia INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                user_id INT DEFAULT 0,
                INDEX idx_hanghoa (idHangHoa),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

            $this->db->exec($sql);
            return true;
        } catch (PDOException $e) {
            error_log("createPriceHistoryTable Error: " . $e->getMessage());
            return false;
        }
    }

    // Chuyển đổi đơn giá áp dụng (switch between prices)
    public function DongiaSwitchActive($idDonGia)
    {
        try {
            // Lấy thông tin đơn giá
            $dongia = $this->DongiaGetbyId($idDonGia);
            if (!$dongia) {
                return false;
            }

            // Đặt tất cả đơn giá khác thành không áp dụng
            $this->DongiaSetAllToFalse($dongia->idHangHoa);

            // Đặt đơn giá này thành áp dụng
            $result = $this->DongiaUpdateStatus($idDonGia, true);

            if ($result) {
                // Cập nhật giá tham khảo
                $this->HanghoaUpdatePrice($dongia->idHangHoa, $dongia->giaBan);
                
                // Ghi log
                $this->logPriceChange($dongia->idHangHoa, $dongia->giaBan, 'Chuyển đổi đơn giá áp dụng', $idDonGia);
            }

            return $result;
        } catch (PDOException $e) {
            error_log("DongiaSwitchActive Error: " . $e->getMessage());
            return false;
        }
    }

    // Lấy lịch sử giá của sản phẩm
    public function getPriceHistory($idHangHoa, $limit = 20)
    {
        try {
            $sql = "SELECT ph.*, h.tenhanghoa, d.ngayApDung, d.ngayKetThuc
                   FROM price_history ph
                   LEFT JOIN hanghoa h ON ph.idHangHoa = h.idhanghoa
                   LEFT JOIN dongia d ON ph.idDonGia = d.idDonGia
                   WHERE ph.idHangHoa = ?
                   ORDER BY ph.created_at DESC
                   LIMIT ?";
            $data = array($idHangHoa, $limit);

            $getHistory = $this->db->prepare($sql);
            $getHistory->setFetchMode(PDO::FETCH_OBJ);
            $getHistory->execute($data);

            return $getHistory->fetchAll();
        } catch (PDOException $e) {
            error_log("getPriceHistory Error: " . $e->getMessage());
            return [];
        }
    }

    // Kiểm tra tác động của việc thay đổi giá đến báo cáo
    public function checkPriceImpact($idHangHoa, $newPrice)
    {
        try {
            $impact = [
                'affected_orders' => 0,
                'revenue_difference' => 0,
                'recent_transactions' => []
            ];

            // Kiểm tra các đơn hàng gần đây (30 ngày)
            $sql = "SELECT COUNT(*) as count, SUM(soluong * dongia) as total_revenue
                   FROM chitietdonhang cd
                   JOIN donhang d ON cd.iddonhang = d.iddonhang
                   WHERE cd.idhanghoa = ? AND d.ngaydat >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            
            $check = $this->db->prepare($sql);
            $check->execute([$idHangHoa]);
            $result = $check->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                $impact['affected_orders'] = $result['count'];
                $currentRevenue = $result['total_revenue'] ?? 0;
                
                // Tính toán sự khác biệt doanh thu nếu áp dụng giá mới
                $sql2 = "SELECT SUM(soluong) as total_quantity
                        FROM chitietdonhang cd
                        JOIN donhang d ON cd.iddonhang = d.iddonhang
                        WHERE cd.idhanghoa = ? AND d.ngaydat >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                
                $check2 = $this->db->prepare($sql2);
                $check2->execute([$idHangHoa]);
                $result2 = $check2->fetch(PDO::FETCH_ASSOC);
                
                if ($result2 && $result2['total_quantity']) {
                    $newRevenue = $result2['total_quantity'] * $newPrice;
                    $impact['revenue_difference'] = $newRevenue - $currentRevenue;
                }
            }

            return $impact;
        } catch (PDOException $e) {
            error_log("checkPriceImpact Error: " . $e->getMessage());
            return ['affected_orders' => 0, 'revenue_difference' => 0, 'recent_transactions' => []];
        }
    }

    // Lấy thống kê đơn giá
    public function getPriceStatistics($idHangHoa = null)
    {
        try {
            $stats = [];
            
            if ($idHangHoa) {
                // Thống kê cho một sản phẩm
                $sql = "SELECT 
                           COUNT(*) as total_prices,
                           MIN(giaBan) as min_price,
                           MAX(giaBan) as max_price,
                           AVG(giaBan) as avg_price,
                           COUNT(CASE WHEN apDung = 1 THEN 1 END) as active_prices
                       FROM dongia WHERE idHangHoa = ?";
                $data = [$idHangHoa];
            } else {
                // Thống kê tổng thể
                $sql = "SELECT 
                           COUNT(*) as total_prices,
                           COUNT(DISTINCT idHangHoa) as total_products,
                           MIN(giaBan) as min_price,
                           MAX(giaBan) as max_price,
                           AVG(giaBan) as avg_price,
                           COUNT(CASE WHEN apDung = 1 THEN 1 END) as active_prices
                       FROM dongia";
                $data = [];
            }

            $getStats = $this->db->prepare($sql);
            $getStats->execute($data);
            $stats = $getStats->fetch(PDO::FETCH_ASSOC);

            return $stats;
        } catch (PDOException $e) {
            error_log("getPriceStatistics Error: " . $e->getMessage());
            return [];
        }
    }
}
