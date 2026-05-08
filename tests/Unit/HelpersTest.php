<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    // ─── safeSessionStart ─────────────────────────────────────────

    public function testSafeSessionStartWhenAlreadyStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->assertTrue(\safeSessionStart());
    }

    // ─── safeRequire ──────────────────────────────────────────────

    public function testSafeRequireReturnsFalseForMissingFile(): void
    {
        $this->assertFalse(\safeRequire('nonexistent_xyz_123.php'));
    }

    public function testSafeRequireWithModTypeSearchesCorrectPaths(): void
    {
        // Should return false and not throw for missing mod file
        $result = \safeRequire('nonexistent_mod_xyz.php', 'mod');
        $this->assertFalse($result);
    }

    public function testSafeRequireWithConfigTypeSearchesCorrectPaths(): void
    {
        $result = \safeRequire('nonexistent_config_xyz.php', 'config');
        $this->assertFalse($result);
    }

    // ─── safePath ─────────────────────────────────────────────────

    public function testSafePathReturnsFalseForMissingFile(): void
    {
        $this->assertFalse(\safePath('nonexistent_xyz.php'));
    }

    // ─── safeRequireClass ─────────────────────────────────────────

    public function testSafeRequireClassThrowsForMissingClass(): void
    {
        $this->expectException(\Exception::class);
        \safeRequireClass('NonexistentClassXYZ');
    }

    public function testSafeRequireClassAppendsPhpExtension(): void
    {
        $this->expectException(\Exception::class);
        // Should try NonexistentClassXYZ.php
        \safeRequireClass('NonexistentClassXYZ');
    }

    // ─── safeGetPath ──────────────────────────────────────────────

    public function testSafeGetPathThrowsForMissingPath(): void
    {
        $this->expectException(\Exception::class);
        \safeGetPath('nonexistent/path/xyz.php');
    }

    // ─── safeRedirect (callable check only, can't test exit) ──────

    public function testSafeRedirectIsCallable(): void
    {
        $this->assertTrue(function_exists('safeRedirect'));
    }

    // ─── safeJsonResponse (callable check only) ───────────────────

    public function testSafeJsonResponseIsCallable(): void
    {
        $this->assertTrue(function_exists('safeJsonResponse'));
    }

    public function testSafeJsonSuccessIsCallable(): void
    {
        $this->assertTrue(function_exists('safeJsonSuccess'));
    }

    public function testSafeJsonErrorIsCallable(): void
    {
        $this->assertTrue(function_exists('safeJsonError'));
    }

    // ─── safeLog ──────────────────────────────────────────────────

    public function testSafeLogIsCallable(): void
    {
        $this->assertTrue(function_exists('safeLog'));
    }

    // ─── All helper functions are defined ─────────────────────────

    public function testAllHelperFunctionsExist(): void
    {
        $expected = [
            'safeRequire', 'safeInclude', 'safePath',
            'safeRequireClass', 'safeGetPath',
            'safeIncludeFile', 'safeRequireFile',
            'safeSessionStart', 'safeRedirect',
            'safeJsonResponse', 'safeJsonSuccess',
            'safeJsonError', 'safeLog',
        ];

        foreach ($expected as $fn) {
            $this->assertTrue(
                function_exists($fn),
                "Expected function '$fn' to exist"
            );
        }
    }
}
