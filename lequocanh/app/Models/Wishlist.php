<?php
/**
 * Wishlist Model - Quản lý sản phẩm yêu thích
 */

declare(strict_types=1);

namespace App\Models;

use Database;
use PDO;
use Exception;

class Wishlist
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Thêm sản phẩm vào yêu thích
     */
    public function add(string $userId, int $productId): bool
    {
        try {
            $sql = "INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$userId, $productId]);
        } catch (Exception $e) {
            error_log("Wishlist::add error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Xóa sản phẩm khỏi yêu thích
     */
    public function remove(string $userId, int $productId): bool
    {
        try {
            $sql = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$userId, $productId]);
        } catch (Exception $e) {
            error_log("Wishlist::remove error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Kiểm tra sản phẩm có trong yêu thích không
     */
    public function isWishlisted(string $userId, int $productId): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM wishlist WHERE user_id = ? AND product_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $productId]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Wishlist::isWishlisted error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lấy danh sách yêu thích của user
     */
    public function getByUser(string $userId): array
    {
        try {
            $sql = "SELECT w.product_id, w.created_at, h.tenhanghoa, h.giathamkhao, h.giakhuyenmai, h.hinhanh
                    FROM wishlist w
                    JOIN hanghoa h ON w.product_id = h.idhanghoa
                    WHERE w.user_id = ?
                    ORDER BY w.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Wishlist::getByUser error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Đếm số lượng yêu thích
     */
    public function count(string $userId): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM wishlist WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Wishlist::count error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Toggle yêu thích (thêm nếu chưa có, xóa nếu đã có)
     */
    public function toggle(string $userId, int $productId): array
    {
        if ($this->isWishlisted($userId, $productId)) {
            $this->remove($userId, $productId);
            return ['success' => true, 'action' => 'removed', 'message' => 'Đã xóa khỏi yêu thích'];
        } else {
            $this->add($userId, $productId);
            return ['success' => true, 'action' => 'added', 'message' => 'Đã thêm vào yêu thích'];
        }
    }
}