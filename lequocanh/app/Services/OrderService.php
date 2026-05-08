<?php

require_once __DIR__ . '/../../administrator/elements_LQA/mod/database.php';
require_once __DIR__ . '/../../cache/QueryCache.php';

class OrderService
{
    private static $instance = null;
    private $db;
    private $cache;

    private function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->cache = QueryCache::getInstance();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getOrdersByUserId($userId, $limit = 20, $offset = 0)
    {
        $sql = "SELECT id, ma_don_hang_text, tong_tien, trang_thai, ngay_tao as ngay_dat_hang,
                       phuong_thuc_thanh_toan, shipping_method as phuong_thuc_van_chuyen,
                       trang_thai_thanh_toan as payment_status,
                       phi_van_chuyen, thue as thue_vat, coupon_discount as giam_gia
                FROM don_hang
                WHERE ma_nguoi_dung = ?
                ORDER BY ngay_tao DESC
                LIMIT ? OFFSET ?";

        return $this->cache->query($this->db, $sql, [$userId, $limit, $offset], 60);
    }

    public function getOrderById($orderId)
    {
        $sql = "SELECT id, ma_don_hang_text, ma_nguoi_dung, tong_tien, trang_thai,
                       ngay_tao as ngay_dat_hang, phuong_thuc_thanh_toan,
                       shipping_method as phuong_thuc_van_chuyen,
                       trang_thai_thanh_toan as payment_status,
                       phi_van_chuyen, thue as thue_vat, coupon_discount as giam_gia
                FROM don_hang
                WHERE id = ?";

        return $this->cache->queryOne($this->db, $sql, [$orderId], 60);
    }

    public function getOrderByCode($orderCode)
    {
        $sql = "SELECT id, ma_don_hang_text, ma_nguoi_dung, tong_tien, trang_thai,
                       ngay_tao as ngay_dat_hang, phuong_thuc_thanh_toan,
                       shipping_method as phuong_thuc_van_chuyen,
                       trang_thai_thanh_toan as payment_status,
                       phi_van_chuyen, thue as thue_vat, coupon_discount as giam_gia
                FROM don_hang
                WHERE ma_don_hang_text = ?";

        return $this->cache->queryOne($this->db, $sql, [$orderCode], 60);
    }

    public function getOrderDetails($orderId)
    {
        $sql = "SELECT ct.id, ct.ma_don_hang, ct.ma_san_pham as ma_hang_hoa, ct.so_luong, ct.gia,
                       h.tenhanghoa
                FROM chi_tiet_don_hang ct
                INNER JOIN hanghoa h ON ct.ma_san_pham = h.idhanghoa
                WHERE ct.ma_don_hang = ?";

        return $this->cache->query($this->db, $sql, [$orderId], 60);
    }

    public function getOrderCount($userId = null)
    {
        if ($userId) {
            $sql = "SELECT COUNT(*) as count FROM don_hang WHERE ma_nguoi_dung = ?";
            $result = $this->cache->queryOne($this->db, $sql, [$userId], 60);
        } else {
            $sql = "SELECT COUNT(*) as count FROM don_hang";
            $result = $this->cache->queryOne($this->db, $sql, [], 60);
        }
        return $result->count ?? 0;
    }

    public function getRecentOrders($userId = null, $limit = 5)
    {
        if ($userId) {
            $sql = "SELECT id, ma_don_hang_text, tong_tien, trang_thai, ngay_tao as ngay_dat_hang
                    FROM don_hang
                    WHERE ma_nguoi_dung = ?
                    ORDER BY ngay_tao DESC
                    LIMIT ?";
            return $this->cache->query($this->db, $sql, [$userId, $limit], 60);
        }

        $sql = "SELECT id, ma_don_hang_text, tong_tien, trang_thai, ngay_tao as ngay_dat_hang
                FROM don_hang
                ORDER BY ngay_tao DESC
                LIMIT ?";

        return $this->cache->query($this->db, $sql, [$limit], 60);
    }

    public function invalidateOrderCache($orderId = null)
    {
        $this->cache->invalidateProducts();
    }
}

if (!function_exists('getOrderService')) {
    function getOrderService()
    {
        return OrderService::getInstance();
    }
}
