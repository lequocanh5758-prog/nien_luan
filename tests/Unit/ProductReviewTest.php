<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\ProductReview;

class ProductReviewTest extends TestCase
{
    // ─── Static Methods Exist ─────────────────────────────────────

    public function testGetAverageRatingMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductReview::class, 'getAverageRating'));
    }

    public function testGetReviewCountMethodExists(): void
    {
        $this->assertTrue(method_exists(ProductReview::class, 'getReviewCount'));
    }

    // ─── Method Signatures ────────────────────────────────────────

    public function testGetAverageRatingAcceptsIntParameter(): void
    {
        $reflection = new \ReflectionMethod(ProductReview::class, 'getAverageRating');
        $parameters = $reflection->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('idhanghoa', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());
    }

    public function testGetReviewCountAcceptsIntParameter(): void
    {
        $reflection = new \ReflectionMethod(ProductReview::class, 'getReviewCount');
        $parameters = $reflection->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('idhanghoa', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());
    }

    // ─── Return Types ─────────────────────────────────────────────

    public function testGetAverageRatingReturnsArray(): void
    {
        $reflection = new \ReflectionMethod(ProductReview::class, 'getAverageRating');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    public function testGetReviewCountReturnsInt(): void
    {
        $reflection = new \ReflectionMethod(ProductReview::class, 'getReviewCount');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('int', $returnType->getName());
    }
}
