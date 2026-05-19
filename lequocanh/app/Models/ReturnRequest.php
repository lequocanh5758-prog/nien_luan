<?php
declare(strict_types=1);

namespace App\Models;

use Database;
use PDO;

class ReturnRequest
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create(int $orderId, string $userId, string $reason, string $type = 'return', ?string $images = null): bool
    {
        try {
            $sql = "INSERT INTO doi_tra (ma_don_hang, ma_nguoi_dung, ly_do, loai, hinh_anh) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$orderId, $userId, $reason, $type, $images]);
        } catch (\Exception $e) {
            error_log("ReturnRequest::create error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getByUser(string $userId): array
    {
        try {
            $sql = "SELECT dt.*, dh.ma_don_hang_text, dh.tong_tien 
                    FROM doi_tra dt
                    JOIN don_hang dh ON dt.ma_don_hang = dh.id
                    WHERE dt.ma_nguoi_dung = ?
                    ORDER BY dt.ngay_tao DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("ReturnRequest::getByUser error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getAll(): array
    {
        try {
            $sql = "SELECT dt.*, dh.ma_don_hang_text, dh.tong_tien, u.hoten as ten_khach_hang
                    FROM doi_tra dt
                    JOIN don_hang dh ON dt.ma_don_hang = dh.id
                    LEFT JOIN users u ON dt.ma_nguoi_dung = u.username
                    ORDER BY dt.ngay_tao DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("ReturnRequest::getAll error: " . $e->getMessage());
            return [];
        }
    }
    
    public function updateStatus(int $id, string $status, ?string $adminNote = null): bool
    {
        try {
            $sql = "UPDATE doi_tra SET trang_thai = ?, ghi_chu_admin = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$status, $adminNote, $id]);
        } catch (\Exception $e) {
            error_log("ReturnRequest::updateStatus error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getById(int $id): ?array
    {
        try {
            $sql = "SELECT dt.*, dh.ma_don_hang_text, dh.tong_tien, dh.dia_chi_giao_hang
                    FROM doi_tra dt
                    JOIN don_hang dh ON dt.ma_don_hang = dh.id
                    WHERE dt.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            error_log("ReturnRequest::getById error: " . $e->getMessage());
            return null;
        }
    }
}