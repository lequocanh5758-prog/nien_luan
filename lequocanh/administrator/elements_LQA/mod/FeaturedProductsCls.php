<?php

/**
 * Admin methods for managing featured/new/sale products.
 * Read methods (getFeaturedProducts, etc.) are in App\Models\Product
 */

require_once __DIR__ . '/database.php';

class FeaturedProducts
{
    private $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?: Database::getInstance()->getConnection();
    }

    public function setFeatured($idhanghoa, $is_featured = 1)
    {
        $sql = "UPDATE hanghoa SET is_featured = ? WHERE idhanghoa = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$is_featured, $idhanghoa]);
    }

    public function setNew($idhanghoa, $is_new = 1)
    {
        $sql = "UPDATE hanghoa SET is_new = ? WHERE idhanghoa = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$is_new, $idhanghoa]);
    }

    public function setSale($idhanghoa, $sale_price, $sale_end_date = null)
    {
        $sql = "UPDATE hanghoa 
                SET is_sale = 1, 
                    sale_price = ?,
                    sale_start_date = NOW(),
                    sale_end_date = ?
                WHERE idhanghoa = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$sale_price, $sale_end_date, $idhanghoa]);
    }

    public function removeSale($idhanghoa)
    {
        $sql = "UPDATE hanghoa 
                SET is_sale = 0, 
                    sale_price = NULL,
                    sale_start_date = NULL,
                    sale_end_date = NULL
                WHERE idhanghoa = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$idhanghoa]);
    }

    public function incrementViewCount($idhanghoa)
    {
        $sql = "UPDATE hanghoa SET view_count = view_count + 1 WHERE idhanghoa = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$idhanghoa]);
    }
}
