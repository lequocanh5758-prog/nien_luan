<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../../../app/autoload.php';

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductReview;

/**
 * hanghoa - Backward-compatible wrapper class.
 *
 * Delegates to App\Models\Product, ProductImage, and ProductReview.
 * Keeps exact same method signatures as the original hanghoaCls.php
 * so all 33 files using "new hanghoa()" continue to work without changes.
 *
 * Usage: $hanghoa = new hanghoa();
 *        $hanghoa->HanghoaGetAll();        // delegates to Product::getAllWithPricing()
 *        $hanghoa->GetHinhAnhById($id);    // delegates to ProductImage::getById()
 *        $hanghoa->getAverageRating($id);  // delegates to ProductReview::getAverageRating()
 */

class hanghoa
{

    /**
     * Constructor kept for backward compat (PDO param unused, models use singleton).
     */
    public function __construct(?PDO $db = null)
    {
        // No-op. Models use Database::getInstance() internally.
    }

    // ═══════════════════════════════════════════
    //  PRODUCT CRUD → Product model
    // ═══════════════════════════════════════════

    public function HanghoaGetAll()
    {
        return Product::getAllWithPricing();
    }

    public function HanghoaGetbyId($idhanghoa)
    {
        return Product::getById((int)$idhanghoa);
    }

    public function HanghoaGetbyIdloaihang($idloaihang)
    {
        return Product::getByCategoryWithPricing((int)$idloaihang);
    }

    public function HanghoaAdd($tenhanghoa, $mota, $giathamkhao, $id_hinhanh, $idloaihang, $idThuongHieu, $idDonViTinh, $idNhanVien, $ghichu = '')
    {
        return Product::addProduct($tenhanghoa, $mota, $giathamkhao, $id_hinhanh, $idloaihang, $idThuongHieu, $idDonViTinh, $idNhanVien, $ghichu);
    }

    public function HanghoaUpdate($tenhanghoa, $id_hinhanh, $mota, $giathamkhao, $idloaihang, $idThuongHieu, $idDonViTinh, $idNhanVien, $idhanghoa, $ghichu = '')
    {
        return Product::updateProduct($tenhanghoa, $id_hinhanh, $mota, $giathamkhao, $idloaihang, $idThuongHieu, $idDonViTinh, $idNhanVien, (int)$idhanghoa, $ghichu);
    }

    public function HanghoaDelete($idhanghoa)
    {
        return Product::deleteProduct((int)$idhanghoa);
    }

    public function HanghoaUpdatePrice($idhanghoa, $giaban)
    {
        return Product::updatePrice((int)$idhanghoa, $giaban);
    }

    // ═══════════════════════════════════════════
    //  SEARCH & FILTER → Product model
    // ═══════════════════════════════════════════

    public function searchHanghoa($keyword)
    {
        return Product::searchProducts($keyword);
    }

    public function filterProducts($filters)
    {
        return Product::filterProducts($filters);
    }

    public function getLastFilterDebug()
    {
        return [];
    }

    public function getFilterOptions($idloaihang = null)
    {
        return Product::getFilterOptions($idloaihang);
    }

    public function getRelatedProducts($idhanghoa, $limit = 6)
    {
        return Product::getRelatedProducts((int)$idhanghoa, $limit);
    }

    // ═══════════════════════════════════════════
    //  STATUS & STOCK → Product model
    // ═══════════════════════════════════════════

    public function getProductStatus($idhanghoa)
    {
        return Product::getProductStatus((int)$idhanghoa);
    }

    public function getProductStatusValue($idhanghoa)
    {
        return Product::getProductStatusValue((int)$idhanghoa);
    }

    public function getProductQuantity($idhanghoa)
    {
        return Product::getProductQuantity((int)$idhanghoa);
    }

    public function updateProductStatus($idhanghoa, $status)
    {
        return Product::updateProductStatus((int)$idhanghoa, (int)$status);
    }

    public function getProductsByStatus($status)
    {
        return Product::getProductsByStatus((int)$status);
    }

    public function getDiscontinuedProducts()
    {
        return Product::getDiscontinuedProducts();
    }

    public function getOutOfStockProducts()
    {
        return Product::getOutOfStockProducts();
    }

    public function getStatusCssClass($displayStatus)
    {
        return Product::getStatusCssClass($displayStatus);
    }

    public function getStatusColor($displayStatus)
    {
        return Product::getStatusColor($displayStatus);
    }

    public function getTonKho($idhanghoa)
    {
        return Product::getTonKho((int)$idhanghoa);
    }

    // ═══════════════════════════════════════════
    //  REFERENCES → Product model
    // ═══════════════════════════════════════════

    public function GetAllThuongHieu()
    {
        return Product::getAllThuongHieu();
    }

    public function GetAllDonViTinh()
    {
        return Product::getAllDonViTinh();
    }

    public function GetAllNhanVien()
    {
        return Product::getAllNhanVien();
    }

    public function GetThuongHieuById($idThuongHieu)
    {
        return Product::getThuongHieuById((int)$idThuongHieu);
    }

    // ═══════════════════════════════════════════
    //  RELATED DATA CHECK → Product model
    // ═══════════════════════════════════════════

    public function checkRelatedData($idhanghoa)
    {
        return Product::checkRelatedData((int)$idhanghoa);
    }

    public function CheckRelations($idhanghoa)
    {
        return !empty(Product::checkRelatedData((int)$idhanghoa));
    }

    // ═══════════════════════════════════════════
    //  IMAGE METHODS → ProductImage model
    // ═══════════════════════════════════════════

    public function GetHinhAnhById($id)
    {
        return ProductImage::getById((int)$id);
    }

    public function GetAllHinhAnh()
    {
        return ProductImage::getAll();
    }

    public function ThemHinhAnh($ten_file, $loai_file, $duong_dan, $file_hash = null, $binary_data = null)
    {
        return ProductImage::create($ten_file, $loai_file, $duong_dan, $file_hash, $binary_data);
    }

    public function XoaHinhAnh($id)
    {
        return ProductImage::delete((int)$id);
    }

    public function GetImagePath($id)
    {
        return ProductImage::getPath((int)$id);
    }

    public function UpdateImageStatus($id)
    {
        return ProductImage::updateStatus((int)$id);
    }

    public function GetLastInsertId()
    {
        return ProductImage::getLastInsertId();
    }

    public function CreateHanghoaHinhanhTable()
    {
        return ProductImage::ensureRelationTable();
    }

    public function ApplyImageToProduct($idhanghoa, $id_hinhanh)
    {
        return ProductImage::applyToProduct((int)$idhanghoa, (int)$id_hinhanh);
    }

    public function RemoveImageFromProduct($idhanghoa)
    {
        return ProductImage::removeFromProduct((int)$idhanghoa);
    }

    public function GetProductsByImageId($imageId)
    {
        return ProductImage::getProductsByImageId((int)$imageId);
    }

    public function UpdateProductImages($oldImageId, $newImageId)
    {
        return ProductImage::updateProductImages((int)$oldImageId, (int)$newImageId);
    }

    public function CapNhatHinhAnhSanPham($idhanghoa, $id_hinhanh_moi)
    {
        return ProductImage::updateProductImage((int)$idhanghoa, (int)$id_hinhanh_moi);
    }

    public function GetAllImagesForProduct($idhanghoa)
    {
        return ProductImage::getAllForProduct((int)$idhanghoa);
    }

    public function CountImagesForProduct($idhanghoa)
    {
        return ProductImage::countForProduct((int)$idhanghoa);
    }

    public function CheckImageExists($fileName)
    {
        return ProductImage::existsByFileName($fileName);
    }

    public function CheckImageExistsByHash($fileHash)
    {
        return ProductImage::existsByHash($fileHash);
    }

    public function FindProductsByExactName($productName)
    {
        return ProductImage::findProductsByExactName($productName);
    }

    public function FindProductsByName($name)
    {
        return ProductImage::findProductsByName($name);
    }

    public function IsExactImageNameMatch($tenhanghoa, $ten_file)
    {
        return ProductImage::isExactImageNameMatch($tenhanghoa, $ten_file);
    }

    public function GetMismatchedProductImages()
    {
        return ProductImage::getMismatchedProductImages();
    }

    public function FindMissingImages()
    {
        return ProductImage::findMissingImages();
    }

    public function FindExactMatchImage($idhanghoa)
    {
        return ProductImage::findExactMatchImage((int)$idhanghoa);
    }

    public function ApplyAllExactMatchImages()
    {
        $applied = 0;
        $products = Product::getAllWithPricing();

        foreach ($products as $product) {
            if (empty($product->hinhanh) || $product->hinhanh == 0) {
                $match = ProductImage::findExactMatchImage((int)$product->idhanghoa);
                if ($match && ProductImage::applyToProduct((int)$product->idhanghoa, (int)$match->id)) {
                    $applied++;
                }
            }
        }

        return $applied;
    }

    public function RemoveAllMismatchedImages()
    {
        return ProductImage::removeAllMismatchedImages();
    }

    // ═══════════════════════════════════════════
    //  REVIEW/RATING → ProductReview model
    // ═══════════════════════════════════════════

    public function getAverageRating($idhanghoa)
    {
        return ProductReview::getAverageRating((int)$idhanghoa);
    }

    public function getReviewCount($idhanghoa)
    {
        return ProductReview::getReviewCount((int)$idhanghoa);
    }
}
