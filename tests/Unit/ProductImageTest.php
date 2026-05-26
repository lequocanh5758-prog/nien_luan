<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\ProductImage;

class ProductImageTest extends TestCase
{
    // ─── Static Methods Exist ─────────────────────────────────────

    public function testGetByIdMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'getById'));
    }

    public function testGetAllMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'getAll'));
    }

    public function testCreateMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'create'));
    }

    public function testDeleteMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'delete'));
    }

    public function testGetPathMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'getPath'));
    }

    public function testUpdateStatusMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'updateStatus'));
    }

    public function testGetLastInsertIdMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'getLastInsertId'));
    }

    // ─── Relation Methods ─────────────────────────────────────────

    public function testEnsureRelationTableMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'ensureRelationTable'));
    }

    public function testApplyToProductMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'applyToProduct'));
    }

    public function testRemoveFromProductMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'removeFromProduct'));
    }

    public function testGetProductsByImageIdMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'getProductsByImageId'));
    }

    public function testUpdateProductImagesMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'updateProductImages'));
    }

    public function testUpdateProductImageMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'updateProductImage'));
    }

    public function testGetAllForProductMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'getAllForProduct'));
    }

    public function testCountForProductMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'countForProduct'));
    }

    // ─── Lookup Methods ───────────────────────────────────────────

    public function testExistsByFileNameMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'existsByFileName'));
    }

    public function testExistsByHashMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'existsByHash'));
    }

    public function testFindProductsByExactNameMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'findProductsByExactName'));
    }

    public function testFindProductsByNameMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'findProductsByName'));
    }

    public function testIsExactImageNameMatchMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'isExactImageNameMatch'));
    }

    // ─── Diagnostic Methods ───────────────────────────────────────

    public function testGetMismatchedProductImagesMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'getMismatchedProductImages'));
    }

    public function testFindMissingImagesMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'findMissingImages'));
    }

    public function testFindExactMatchImageMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'findExactMatchImage'));
    }

    public function testRemoveAllMismatchedImagesMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductImage::class, 'removeAllMismatchedImages'));
    }

    // ─── Image Name Match Logic ───────────────────────────────────

    public function testIsExactImageNameMatchReturnsTrueForMatchingName(): void
    {
        $result = ProductImage::isExactImageNameMatch('iPhone 15 Pro', 'iPhone 15 Pro Max.jpg');
        $this->assertTrue($result);
    }

    public function testIsExactImageNameMatchReturnsFalseForNonMatchingName(): void
    {
        $result = ProductImage::isExactImageNameMatch('iPhone 15 Pro', 'Samsung Galaxy S24.jpg');
        $this->assertFalse($result);
    }

    public function testIsExactImageNameMatchCaseInsensitive(): void
    {
        $result = ProductImage::isExactImageNameMatch('iphone 15 pro', 'IPHONE 15 PRO MAX.jpg');
        $this->assertTrue($result);
    }
}
