<?php
/**
 * Admin Auth Check
 * Include this file at the top of every admin page to prevent unauthorized access.
 * Pages loaded via center.php already have auth checks, but direct URL access bypasses them.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$username = '';
if (isset($_SESSION['ADMIN'])) {
    $username = $_SESSION['ADMIN'];
} elseif (isset($_SESSION['USER'])) {
    $username = $_SESSION['USER'];
}

// Must be logged in
if (empty($username)) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>403 - Truy cập bị từ chối</title>
    <style>body{font-family:Arial,sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;background:#f5f5f5;margin:0}
    .box{text-align:center;background:white;padding:40px 60px;border-radius:12px;box-shadow:0 2px 20px rgba(0,0,0,0.1)}
    h1{color:#e74c3c;font-size:72px;margin:0}p{color:#666;font-size:18px}a{color:#3498db;text-decoration:none}
    </style></head><body><div class="box"><h1>403</h1><p>Bạn cần đăng nhập để truy cập trang này.</p>
    <p><a href="/lequocanh/administrator/userLogin.php">Đăng nhập ngay</a></p></div></body></html>';
    exit;
}

// Check if user is admin or staff
require_once __DIR__ . '/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$isAdmin = isset($_SESSION['ADMIN']) || $phanQuyen->isAdmin($username);
$isNhanVien = $isAdmin || $phanQuyen->isNhanVien($username);

if (!$isAdmin && !$isNhanVien) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>403 - Không có quyền</title>
    <style>body{font-family:Arial,sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;background:#f5f5f5;margin:0}
    .box{text-align:center;background:white;padding:40px 60px;border-radius:12px;box-shadow:0 2px 20px rgba(0,0,0,0.1)}
    h1{color:#e74c3c;font-size:72px;margin:0}p{color:#666;font-size:18px}a{color:#3498db;text-decoration:none}
    </style></head><body><div class="box"><h1>403</h1><p>Bạn không có quyền truy cập trang này.</p>
    <p><a href="../../index.php">Về trang chủ</a></p></div></body></html>';
    exit;
}
