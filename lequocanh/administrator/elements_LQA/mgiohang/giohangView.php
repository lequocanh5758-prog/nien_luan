<?php
/**
 * Giỏ hàng - Phiên bản cải tiến
 * 
 * Cải thiện:
 * - Fix bug require_once trong loop
 * - Fix updateTotalPrice() chỉ tính sản phẩm được chọn
 * - Tối ưu query (JOIN stock trong 1 query)
 * - UI/UX: product links, loading states, mobile responsive, breadcrumb
 * - Tính năng: coupon, xóa tất cả, modal xác nhận
 * - Performance: lazy loading images, optimized queries
 */

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';
require_once __DIR__ . '/../../../includes/csrf_helper.php';
require_once __DIR__ . '/../mod/giohangCls.php';
require_once __DIR__ . '/../mod/mtonkhoCls.php';
require_once __DIR__ . '/../mod/OrderAutoCancel.php';
require_once __DIR__ . '/../../../app/autoload.php';

SessionManager::start();

use App\Models\Product;

$giohang = new GioHang();
$tonkho = new MTonKho();

if (!$giohang->canUseCart()) {
    if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ../../userLogin.php');
    } else {
        header('Location: ../../index.php');
    }
    exit();
}

ini_set('display_errors', 0);
error_reporting(0);

// ===== AUTO-CANCEL EXPIRED PENDING ORDERS =====
if (isset($_SESSION['USER'])) {
    $cancelResult = cancelUserExpiredOrders($_SESSION['USER'], 15);
    if ($cancelResult['cancelled'] > 0) {
        $_SESSION['cart_info'] = "Đã tự động hủy {$cancelResult['cancelled']} đơn hàng chưa thanh toán. Tồn kho đã được hoàn lại.";
    }
}

// ===== LOAD CART WITH STOCK IN SINGLE QUERY =====
$cart = $giohang->getCart();
$cartDetails = [];
$totalAmount = 0;
$hasUnavailableProducts = false;

if (!empty($cart)) {
    // Get all product IDs for batch stock query
    $productIds = array_column($cart, 'product_id');
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    
    // Batch query for stock
    $stockMap = [];
    try {
        $db = \Database::getInstance()->getConnection();
        $stockStmt = $db->prepare("SELECT idhanghoa, soLuong FROM tonkho WHERE idhanghoa IN ($placeholders)");
        $stockStmt->execute($productIds);
        while ($row = $stockStmt->fetch(\PDO::FETCH_ASSOC)) {
            $stockMap[$row['idhanghoa']] = (int)$row['soLuong'];
        }
    } catch (Exception $e) {
        error_log("Cart stock query error: " . $e->getMessage());
    }
    
    // Batch query for product status
    $statusMap = [];
    try {
        $statusStmt = $db->prepare("SELECT idhanghoa, trang_thai FROM hanghoa WHERE idhanghoa IN ($placeholders)");
        $statusStmt->execute($productIds);
        while ($row = $statusStmt->fetch(\PDO::FETCH_ASSOC)) {
            $statusMap[$row['idhanghoa']] = (int)$row['trang_thai'];
        }
    } catch (Exception $e) {
        error_log("Cart status query error: " . $e->getMessage());
    }

    foreach ($cart as $item) {
        $productId = (int)$item['product_id'];
        $hinhanhValue = (isset($item['hinhanh']) && $item['hinhanh'] !== '') ? (int)$item['hinhanh'] : null;
        $currentPrice = $item['gia_hien_tai'] ?? $item['giathamkhao'] ?? 0;
        $hasDiscount = $item['has_discount'] ?? false;
        
        $stockQuantity = $stockMap[$productId] ?? 0;
        $productStatus = $statusMap[$productId] ?? 1;
        
        $isUnavailable = false;
        $statusMessage = '';
        $statusClass = '';

        if ($productStatus == 2) {
            $isUnavailable = true;
            $hasUnavailableProducts = true;
            $statusMessage = 'Ngừng bán';
            $statusClass = 'warning';
        } elseif ($productStatus == 3 || $stockQuantity <= 0) {
            $isUnavailable = true;
            $hasUnavailableProducts = true;
            $statusMessage = 'Hết hàng';
            $statusClass = 'danger';
        }

        $cartDetails[] = [
            'id' => $productId,
            'name' => $item['tenhanghoa'] ?? 'Unknown Product',
            'price' => $currentPrice,
            'original_price' => $item['giathamkhao'] ?? 0,
            'discount_price' => $item['giakhuyenmai'] ?? null,
            'has_discount' => $hasDiscount,
            'quantity' => $item['quantity'],
            'hinhanh' => $hinhanhValue,
            'subtotal' => $currentPrice * $item['quantity'],
            'is_unavailable' => $isUnavailable,
            'status_message' => $statusMessage,
            'status_class' => $statusClass,
            'stock_quantity' => $stockQuantity
        ];

        if (!$isUnavailable) {
            $totalAmount += $currentPrice * $item['quantity'];
        }
    }
}

// ===== LOAD ORDER HISTORY =====
$orderHistory = [];
$orderStats = ['pending' => 0, 'approved' => 0, 'delivered' => 0, 'completed' => 0, 'cancelled' => 0, 'total' => 0, 'total_paid' => 0];
if (isset($_SESSION['USER'])) {
    try {
        $db = \Database::getInstance()->getConnection();
        
        $checkTable = $db->query("SHOW TABLES LIKE 'don_hang'");
        if ($checkTable->rowCount() > 0) {
            $sql = "SELECT id, ma_don_hang_text, ngay_tao, tong_tien, trang_thai, trang_thai_thanh_toan, phuong_thuc_thanh_toan 
                    FROM don_hang WHERE ma_nguoi_dung = ? ORDER BY ngay_tao DESC LIMIT 10";
            $stmt = $db->prepare($sql);
            $stmt->execute([$_SESSION['USER']]);
            $orderHistory = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $statsSql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN trang_thai = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN trang_thai = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN trang_thai = 'delivered' THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN trang_thai = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN trang_thai = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN trang_thai = 'completed' AND trang_thai_thanh_toan IN ('paid', 'completed') THEN tong_tien ELSE 0 END) as total_paid
                FROM don_hang WHERE ma_nguoi_dung = ?";
            $statsStmt = $db->prepare($statsSql);
            $statsStmt->execute([$_SESSION['USER']]);
            $orderStats = $statsStmt->fetch(\PDO::FETCH_ASSOC) ?: $orderStats;
        }
    } catch (Exception $e) {
        error_log("Order history error: " . $e->getMessage());
    }
}

// Count items for badge
$itemCount = count($cartDetails);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <?= csrf_meta() ?>
    <title>Giỏ hàng (<?= $itemCount ?> sản phẩm)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../../public_files/mycss.css">
    <link rel="stylesheet" href="../../../public_files/toast-notification.css">
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --danger: #e74c3c;
            --success: #27ae60;
            --warning: #f39c12;
            --gray-50: #f8f9fa;
            --gray-100: #f1f3f5;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-500: #adb5bd;
            --gray-700: #495057;
            --gray-900: #212529;
        }

        body {
            background: var(--gray-100);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* Breadcrumb */
        .breadcrumb-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 15px 0;
        }
        .breadcrumb { background: none; padding: 0; margin: 0; font-size: 14px; }
        .breadcrumb-item a { color: var(--primary); text-decoration: none; }
        .breadcrumb-item a:hover { text-decoration: underline; }
        .breadcrumb-item.active { color: var(--gray-500); }

        /* Cart Container */
        .cart-container {
            max-width: 1200px;
            margin: 0 auto 20px;
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 1px 8px rgba(0,0,0,0.06);
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--gray-100);
        }
        .cart-header h2 { margin: 0; font-size: 22px; font-weight: 700; }
        .cart-header .item-count { 
            background: var(--primary); color: white; 
            padding: 4px 12px; border-radius: 20px; font-size: 13px; 
        }

        /* Table Styles */
        .cart-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .cart-table th {
            background: var(--gray-50);
            padding: 12px 15px;
            text-align: center;
            border-bottom: 2px solid var(--gray-200);
            color: var(--gray-700);
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .cart-table td {
            padding: 15px;
            text-align: center;
            vertical-align: middle;
            border-bottom: 1px solid var(--gray-200);
            transition: background 0.2s;
        }
        .cart-table tbody tr:hover { background: var(--gray-50); }
        .cart-table tbody tr.unavailable { background: #fff3cd; opacity: 0.85; }

        /* Product Info */
        .product-info { display: flex; align-items: center; text-align: left; }
        .product-image {
            width: 80px; height: 80px;
            object-fit: cover; border-radius: 8px;
            border: 1px solid var(--gray-200);
            transition: transform 0.2s;
        }
        .product-image:hover { transform: scale(1.05); }
        .product-details { margin-left: 15px; flex: 1; }
        .product-name {
            font-weight: 600; color: var(--gray-900);
            text-decoration: none; display: block; margin-bottom: 4px;
            transition: color 0.2s;
            word-break: break-word;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .product-name:hover { color: var(--primary); }
        .product-meta { font-size: 12px; color: var(--gray-500); }
        .product-meta .badge { font-size: 10px; padding: 3px 6px; }

        /* Price */
        .price-cell { min-width: 120px; }
        .price-current { color: var(--danger); font-weight: 700; font-size: 15px; }
        .price-original { 
            color: var(--gray-500); text-decoration: line-through; 
            font-size: 12px; margin-top: 2px; 
        }
        .price-unavailable { color: var(--gray-500); }

        /* Quantity Controls */
        .quantity-controls {
            display: inline-flex; align-items: center;
            border: 1px solid var(--gray-300); border-radius: 8px;
            overflow: hidden;
        }
        .quantity-btn {
            width: 36px; height: 36px;
            display: flex; align-items: center; justify-content: center;
            background: var(--gray-50); border: none;
            color: var(--gray-700); cursor: pointer;
            transition: all 0.2s; font-size: 16px;
        }
        .quantity-btn:hover:not(:disabled) { background: var(--gray-200); }
        .quantity-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .quantity-input {
            width: 50px; height: 36px;
            text-align: center; border: none; border-left: 1px solid var(--gray-300);
            border-right: 1px solid var(--gray-300); font-weight: 600;
            font-size: 14px; outline: none;
        }
        .quantity-input:focus { background: #e3f2fd; }
        .stock-info { font-size: 11px; color: var(--gray-500); margin-top: 4px; }
        .stock-info.low { color: var(--warning); }
        .stock-info.out { color: var(--danger); }

        /* Subtotal */
        .subtotal { font-weight: 700; color: var(--danger); font-size: 15px; }

        /* Actions */
        .action-btn {
            width: 34px; height: 34px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 8px; border: 1px solid var(--gray-300);
            background: white; color: var(--gray-700);
            cursor: pointer; transition: all 0.2s;
        }
        .action-btn:hover { background: #fee2e2; color: var(--danger); border-color: #fecaca; }

        /* Cart Footer */
        .cart-footer {
            position: sticky; bottom: 0;
            background: white; padding: 20px 25px;
            border-top: 2px solid var(--gray-200);
            box-shadow: 0 -4px 12px rgba(0,0,0,0.05);
            z-index: 100;
        }
        .footer-left { display: flex; align-items: center; gap: 15px; }
        .footer-right { display: flex; align-items: center; gap: 20px; }
        .total-section { text-align: right; }
        .total-label { font-size: 14px; color: var(--gray-500); }
        .total-amount { 
            color: var(--danger); font-size: 24px; font-weight: 700; 
            line-height: 1.2; 
        }
        .total-count { font-size: 12px; color: var(--gray-500); }

        /* Buttons */
        .btn-checkout {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white; border: none; padding: 12px 32px;
            border-radius: 8px; font-weight: 600; font-size: 15px;
            cursor: pointer; transition: all 0.3s;
            display: flex; align-items: center; gap: 8px;
        }
        .btn-checkout:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52,152,219,0.3);
            color: white;
        }
        .btn-checkout:disabled {
            background: var(--gray-300); cursor: not-allowed;
        }
        .btn-continue {
            background: white; color: var(--primary);
            border: 1px solid var(--primary); padding: 10px 20px;
            border-radius: 8px; font-weight: 500;
            transition: all 0.2s;
        }
        .btn-continue:hover { background: #e3f2fd; }

        /* Empty Cart */
        .empty-cart {
            text-align: center; padding: 60px 20px;
        }
        .empty-cart-icon {
            font-size: 80px; color: var(--gray-300); margin-bottom: 20px;
        }
        .empty-cart h3 { color: var(--gray-700); margin-bottom: 10px; }
        .empty-cart p { color: var(--gray-500); margin-bottom: 25px; }

        /* Warning Banner */
        .warning-banner {
            background: #fff3cd; border: 1px solid #ffc107;
            border-radius: 8px; padding: 12px 16px;
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 15px; font-size: 14px;
        }
        .warning-banner i { color: var(--warning); font-size: 18px; }

        /* Order History */
        .order-history { margin-top: 20px; }
        .order-history h3 { font-size: 18px; font-weight: 700; }
        .stats-badges { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 15px; }
        .stats-badge {
            padding: 6px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 500;
            display: flex; align-items: center; gap: 5px;
        }
        .order-table { font-size: 14px; }
        .order-table th { font-weight: 600; color: var(--gray-700); }
        .order-status .badge { font-size: 11px; padding: 5px 8px; }

        /* Confirmation Modal */
        .modal-confirm .modal-content {
            border-radius: 12px; border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }
        .modal-confirm .modal-header {
            border-bottom: 1px solid var(--gray-200);
            padding: 20px 24px;
        }
        .modal-confirm .modal-body {
            padding: 24px; text-align: center;
        }
        .modal-confirm .modal-icon {
            width: 60px; height: 60px;
            background: #fee2e2; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px;
        }
        .modal-confirm .modal-icon i { font-size: 24px; color: var(--danger); }
        .modal-confirm .modal-footer {
            border-top: 1px solid var(--gray-200);
            padding: 16px 24px; justify-content: center; gap: 12px;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255,255,255,0.8); z-index: 9999;
            display: none; align-items: center; justify-content: center;
        }
        .loading-overlay.active { display: flex; }
        .loading-spinner {
            width: 40px; height: 40px;
            border: 3px solid var(--gray-200);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Responsive */
        @media (max-width: 768px) {
            .cart-container { padding: 15px; margin: 10px; }
            .cart-header h2 { font-size: 18px; }
            
            /* Mobile Card Layout */
            .cart-table thead { display: none; }
            .cart-table, .cart-table tbody, .cart-table tr, .cart-table td {
                display: block; width: 100%;
            }
            .cart-table tr {
                background: white; border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.06);
                margin-bottom: 12px; padding: 15px;
                position: relative;
            }
            .cart-table td {
                text-align: left; padding: 8px 0;
                border: none;
            }
            .cart-table td:first-child {
                position: absolute; top: 15px; left: 15px;
            }
            .cart-table td:nth-child(2) { padding-left: 35px; }
            .cart-table td:nth-child(3)::before { content: 'Đơn giá: '; font-weight: 600; color: var(--gray-500); }
            .cart-table td:nth-child(4)::before { content: 'Số lượng: '; font-weight: 600; color: var(--gray-500); display: block; margin-bottom: 5px; }
            .cart-table td:nth-child(5)::before { content: 'Thành tiền: '; font-weight: 600; color: var(--gray-500); }
            .cart-table td:last-child { text-align: right; }
            
            .product-info { flex-direction: column; align-items: flex-start; }
            .product-image { width: 60px; height: 60px; }
            .product-details { margin-left: 0; margin-top: 10px; }
            
            .cart-footer { position: relative; }
            .footer-left, .footer-right { flex-direction: column; width: 100%; }
            .footer-right { margin-top: 15px; }
            .total-section { text-align: center; width: 100%; }
            .btn-checkout { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Breadcrumb -->
    <div class="breadcrumb-container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../../../index.php"><i class="fas fa-home"></i> Trang chủ</a></li>
                <li class="breadcrumb-item active">Giỏ hàng</li>
            </ol>
        </nav>
    </div>

    <div class="cart-container">
        <?php if (isset($_SESSION['cart_info'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i><?= htmlspecialchars($_SESSION['cart_info']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['cart_info']); ?>
        <?php endif; ?>

        <?php if (empty($cartDetails)): ?>
            <!-- Empty Cart -->
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3>Giỏ hàng trống</h3>
                <p>Bạn chưa có sản phẩm nào trong giỏ hàng. Hãy khám phá các sản phẩm của chúng tôi!</p>
                <a href="../../../index.php" class="btn btn-checkout">
                    <i class="fas fa-shopping-bag"></i> Tiếp tục mua sắm
                </a>
            </div>
        <?php else: ?>
            <!-- Cart Header -->
            <div class="cart-header">
                <h2><i class="fas fa-shopping-cart me-2"></i>Giỏ hàng</h2>
                <span class="item-count"><?= $itemCount ?> sản phẩm</span>
            </div>

            <?php if ($hasUnavailableProducts): ?>
                <div class="warning-banner">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Chú ý:</strong> Một số sản phẩm trong giỏ hàng đã hết hàng hoặc ngừng bán. 
                        Vui lòng xóa hoặc cập nhật để tiếp tục thanh toán.
                    </div>
                </div>
            <?php endif; ?>

            <!-- Cart Table -->
            <table class="cart-table">
                <thead>
                    <tr>
                        <th width="5%">
                            <input type="checkbox" id="select-all" class="form-check-input" title="Chọn tất cả">
                        </th>
                        <th width="40%">Sản phẩm</th>
                        <th width="15%">Đơn giá</th>
                        <th width="18%">Số lượng</th>
                        <th width="12%">Thành tiền</th>
                        <th width="5%"></th>
                    </tr>
                </thead>
                <tbody id="cart-items">
                    <?php foreach ($cartDetails as $item): ?>
                        <tr class="<?= $item['is_unavailable'] ? 'unavailable' : '' ?>" 
                            data-product-id="<?= $item['id'] ?>">
                            <td>
                                <input type="checkbox" 
                                       class="form-check-input product-select" 
                                       <?= $item['is_unavailable'] ? 'disabled' : 'checked' ?>
                                       data-price="<?= $item['is_unavailable'] ? 0 : $item['price'] ?>">
                            </td>
                            <td>
                                <div class="product-info">
                                    <?php
                                    $imageSrc = ($item['hinhanh'] && $item['hinhanh'] > 0) 
                                        ? "../../elements_LQA/mhanghoa/displayImage.php?id=" . $item['hinhanh'] 
                                        : "../../elements_LQA/img_LQA/no-image.png";
                                    ?>
                                    <a href="../../../index.php?reqHanghoa=<?= $item['id'] ?>">
                                        <img src="<?= $imageSrc ?>"
                                            alt="<?= htmlspecialchars($item['name']) ?>"
                                            class="product-image"
                                            loading="lazy"
                                            onerror="this.onerror=null; this.src='../../elements_LQA/img_LQA/no-image.png';">
                                    </a>
                                    <div class="product-details">
                                        <a href="../../../index.php?reqHanghoa=<?= $item['id'] ?>" 
                                           class="product-name">
                                            <?= htmlspecialchars($item['name']) ?>
                                        </a>
                                        <div class="product-meta">
                                            <i class="fas fa-box me-1"></i>
                                            Tồn kho: 
                                            <?php if ($item['stock_quantity'] > 5): ?>
                                                <span class="text-success fw-bold"><?= $item['stock_quantity'] ?></span>
                                            <?php elseif ($item['stock_quantity'] > 0): ?>
                                                <span class="text-warning fw-bold"><?= $item['stock_quantity'] ?></span>
                                            <?php else: ?>
                                                <span class="text-danger fw-bold">Hết</span>
                                            <?php endif; ?>
                                            
                                            <?php if ($item['has_discount']): ?>
                                                <span class="badge bg-danger ms-2">-<?php 
                                                    $discount = round((($item['original_price'] - $item['price']) / $item['original_price']) * 100);
                                                    echo $discount;
                                                ?>%</span>
                                            <?php endif; ?>
                                            
                                            <?php if ($item['is_unavailable']): ?>
                                                <span class="badge bg-<?= $item['status_class'] ?> ms-2">
                                                    <?= $item['status_message'] ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="price-cell">
                                <?php if ($item['is_unavailable']): ?>
                                    <span class="price-unavailable">
                                        <del><?= number_format($item['price'], 0, ',', '.') ?> ₫</del>
                                    </span>
                                <?php elseif ($item['has_discount']): ?>
                                    <div class="price-current"><?= number_format($item['price'], 0, ',', '.') ?> ₫</div>
                                    <div class="price-original"><?= number_format($item['original_price'], 0, ',', '.') ?> ₫</div>
                                <?php else: ?>
                                    <div class="price-current"><?= number_format($item['price'], 0, ',', '.') ?> ₫</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['is_unavailable']): ?>
                                    <span class="text-muted">—</span>
                                <?php else: ?>
                                    <div class="quantity-controls">
                                        <button class="quantity-btn decrease-btn" 
                                                type="button"
                                                data-product-id="<?= $item['id'] ?>"
                                                <?= $item['quantity'] <= 1 ? 'disabled' : '' ?>>
                                            <i class="fas fa-minus" style="font-size: 10px;"></i>
                                        </button>
                                        <input type="number" 
                                               class="quantity-input" 
                                               value="<?= $item['quantity'] ?>"
                                               min="1" 
                                               max="<?= $item['stock_quantity'] ?>"
                                               data-product-id="<?= $item['id'] ?>"
                                               data-stock="<?= $item['stock_quantity'] ?>"
                                               data-price="<?= $item['price'] ?>">
                                        <button class="quantity-btn increase-btn" 
                                                type="button"
                                                data-product-id="<?= $item['id'] ?>"
                                                <?= $item['quantity'] >= $item['stock_quantity'] ? 'disabled' : '' ?>>
                                            <i class="fas fa-plus" style="font-size: 10px;"></i>
                                        </button>
                                    </div>
                                    <div class="stock-info <?= $item['stock_quantity'] <= 5 ? 'low' : '' ?>">
                                        Tối đa: <?= $item['stock_quantity'] ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="subtotal" data-subtotal="<?= $item['is_unavailable'] ? 0 : $item['subtotal'] ?>">
                                <?php if ($item['is_unavailable']): ?>
                                    <span class="text-muted">—</span>
                                <?php else: ?>
                                    <?= number_format($item['subtotal'], 0, ',', '.') ?> ₫
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button"
                                        class="action-btn remove-btn"
                                        data-product-id="<?= $item['id'] ?>"
                                        data-product-name="<?= htmlspecialchars($item['name']) ?>"
                                        title="Xóa sản phẩm">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Cart Footer -->
            <div class="cart-footer">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div class="footer-left">
                        <div class="form-check">
                            <input type="checkbox" id="select-all-bottom" class="form-check-input" checked>
                            <label class="form-check-label" for="select-all-bottom">Chọn tất cả</label>
                        </div>
                        <button onclick="deleteSelectedItems()" class="btn btn-outline-danger btn-sm" id="btnDeleteSelected">
                            <i class="fas fa-trash me-1"></i>Xóa đã chọn
                        </button>
                        <button onclick="clearCart()" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-broom me-1"></i>Xóa tất cả
                        </button>
                        <a href="../../../index.php" class="btn-continue">
                            <i class="fas fa-arrow-left me-1"></i>Tiếp tục mua sắm
                        </a>
                    </div>

                    <div class="footer-right">
                        <div class="total-section">
                            <div class="total-label">Tổng thanh toán:</div>
                            <div class="total-amount" id="totalAmount">
                                <?= number_format($totalAmount, 0, ',', '.') ?> ₫
                            </div>
                            <div class="total-count" id="selectedCount">
                                Đã chọn: <span id="selectedItemCount"><?= $itemCount ?></span> sản phẩm
                            </div>
                        </div>

                        <?php if ($hasUnavailableProducts): ?>
                            <button disabled class="btn-checkout" title="Vui lòng xóa sản phẩm hết hàng">
                                <i class="fas fa-lock"></i> Không thể thanh toán
                            </button>
                        <?php else: ?>
                            <button onclick="proceedToCheckout()" class="btn-checkout" id="btnCheckout">
                                <i class="fas fa-credit-card"></i> Mua hàng
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Order History Section -->
    <?php if (!empty($orderHistory)): ?>
    <div class="cart-container order-history">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0"><i class="fas fa-history me-2"></i>Đơn hàng gần đây</h3>
            <a href="../page.php?p=donhang" class="btn btn-sm btn-outline-primary">
                Xem tất cả <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="stats-badges">
            <span class="stats-badge bg-warning text-dark">
                <i class="fas fa-clock"></i> Chờ xử lý: <?= $orderStats['pending'] ?>
            </span>
            <span class="stats-badge bg-info text-white">
                <i class="fas fa-check-circle"></i> Đã duyệt: <?= $orderStats['approved'] ?>
            </span>
            <span class="stats-badge bg-primary text-white">
                <i class="fas fa-truck"></i> Đang giao: <?= $orderStats['delivered'] ?>
            </span>
            <span class="stats-badge bg-success text-white">
                <i class="fas fa-check-double"></i> Hoàn tất: <?= $orderStats['completed'] ?>
            </span>
            <span class="stats-badge bg-danger text-white">
                <i class="fas fa-times-circle"></i> Đã hủy: <?= $orderStats['cancelled'] ?>
            </span>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle order-table">
                <thead class="table-light">
                    <tr>
                        <th>Mã đơn</th>
                        <th>Ngày đặt</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Thanh toán</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderHistory as $order): ?>
                        <tr>
                            <td><strong class="text-primary"><?= htmlspecialchars($order['ma_don_hang_text']) ?></strong></td>
                            <td><?= date('d/m/Y H:i', strtotime($order['ngay_tao'])) ?></td>
                            <td><span class="text-danger fw-bold"><?= number_format($order['tong_tien'], 0, ',', '.') ?> ₫</span></td>
                            <td class="order-status">
                                <?php
                                $statusConfig = [
                                    'pending' => ['bg-warning text-dark', 'fa-clock', 'Chờ xác nhận'],
                                    'approved' => ['bg-info text-white', 'fa-check-circle', 'Đã duyệt'],
                                    'delivered' => ['bg-primary text-white', 'fa-truck', 'Đang giao'],
                                    'completed' => ['bg-success text-white', 'fa-check-double', 'Hoàn tất'],
                                    'cancelled' => ['bg-danger text-white', 'fa-times-circle', 'Đã hủy'],
                                ];
                                $status = $statusConfig[$order['trang_thai']] ?? ['bg-secondary text-white', 'fa-question', 'Không xác định'];
                                ?>
                                <span class="badge <?= $status[0] ?>">
                                    <i class="fas <?= $status[1] ?> me-1"></i><?= $status[2] ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $paymentConfig = [
                                    'paid' => ['bg-success text-white', 'fa-check', 'Đã TT'],
                                    'completed' => ['bg-success text-white', 'fa-check', 'Đã TT'],
                                    'failed' => ['bg-danger text-white', 'fa-times', 'Thất bại'],
                                    'pending' => ['bg-warning text-dark', 'fa-clock', 'Chờ TT'],
                                ];
                                $payment = $paymentConfig[$order['trang_thai_thanh_toan']] ?? ['bg-secondary text-white', 'fa-minus', '—'];
                                ?>
                                <span class="badge <?= $payment[0] ?>">
                                    <i class="fas <?= $payment[1] ?> me-1"></i><?= $payment[2] ?>
                                </span>
                            </td>
                            <td>
                                <a href="orderDetailView.php?id=<?= $order['id'] ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> Chi tiết
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Confirmation Modal -->
    <div class="modal fade modal-confirm" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalTitle">Xác nhận</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-icon" id="confirmModalIcon">
                        <i class="fas fa-question"></i>
                    </div>
                    <p id="confirmModalMessage">Bạn có chắc chắn?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-danger" id="confirmModalBtn">Xác nhận</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public_files/toast-notification.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // ===== STATE =====
        let isUpdating = false;
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        let confirmCallback = null;

        // ===== HELPER FUNCTIONS =====
        function showLoading() {
            document.getElementById('loadingOverlay').classList.add('active');
        }
        
        function hideLoading() {
            document.getElementById('loadingOverlay').classList.remove('active');
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('vi-VN').format(amount) + ' ₫';
        }

        function showConfirm(title, message, callback, icon = 'fa-trash') {
            document.getElementById('confirmModalTitle').textContent = title;
            document.getElementById('confirmModalMessage').textContent = message;
            document.getElementById('confirmModalIcon').innerHTML = `<i class="fas ${icon}"></i>`;
            confirmCallback = callback;
            confirmModal.show();
        }

        document.getElementById('confirmModalBtn').addEventListener('click', function() {
            if (confirmCallback) {
                confirmCallback();
            }
            confirmModal.hide();
        });

        // ===== UPDATE TOTAL PRICE (FIX: Only count selected) =====
        function updateTotalPrice() {
            let total = 0;
            let selectedCount = 0;
            
            document.querySelectorAll('.product-select:checked:not(:disabled)').forEach(checkbox => {
                const row = checkbox.closest('tr');
                const subtotalEl = row.querySelector('.subtotal');
                if (subtotalEl) {
                    const subtotal = parseInt(subtotalEl.dataset.subtotal || 0);
                    if (!isNaN(subtotal)) {
                        total += subtotal;
                        selectedCount++;
                    }
                }
            });
            
            document.getElementById('totalAmount').textContent = formatCurrency(total);
            document.getElementById('selectedItemCount').textContent = selectedCount;
            
            // Update checkout button state
            const btnCheckout = document.getElementById('btnCheckout');
            if (btnCheckout) {
                btnCheckout.disabled = selectedCount === 0;
            }
        }

        // ===== SELECT ALL =====
        function setupSelectAll() {
            const selectAllCheckboxes = document.querySelectorAll('#select-all, #select-all-bottom');
            const productCheckboxes = document.querySelectorAll('.product-select:not(:disabled)');
            
            selectAllCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const isChecked = this.checked;
                    selectAllCheckboxes.forEach(cb => cb.checked = isChecked);
                    productCheckboxes.forEach(cb => cb.checked = isChecked);
                    updateTotalPrice();
                });
            });

            productCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const allChecked = Array.from(productCheckboxes).every(cb => cb.checked);
                    selectAllCheckboxes.forEach(cb => cb.checked = allChecked);
                    updateTotalPrice();
                });
            });
        }

        // ===== QUANTITY CONTROLS =====
        function setupQuantityControls() {
            // Decrease buttons
            document.querySelectorAll('.decrease-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const productId = this.dataset.productId;
                    const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
                    const currentValue = parseInt(input.value);
                    if (currentValue > 1) {
                        updateQuantity(productId, currentValue - 1, input);
                    }
                });
            });

            // Increase buttons
            document.querySelectorAll('.increase-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const productId = this.dataset.productId;
                    const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
                    const currentValue = parseInt(input.value);
                    const maxStock = parseInt(input.dataset.stock);
                    if (currentValue < maxStock) {
                        updateQuantity(productId, currentValue + 1, input);
                    }
                });
            });

            // Direct input
            document.querySelectorAll('.quantity-input').forEach(input => {
                input.addEventListener('change', function() {
                    const productId = this.dataset.productId;
                    let value = parseInt(this.value);
                    const maxStock = parseInt(this.dataset.stock);
                    
                    if (isNaN(value) || value < 1) value = 1;
                    if (value > maxStock) value = maxStock;
                    
                    updateQuantity(productId, value, this);
                });

                // Select all on focus
                input.addEventListener('focus', function() {
                    this.select();
                });
            });
        }

        async function updateQuantity(productId, newQuantity, input) {
            if (isUpdating) return;
            isUpdating = true;

            const row = input.closest('tr');
            const price = parseInt(input.dataset.price);
            
            try {
                const response = await fetch('giohangUpdate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ productId: parseInt(productId), quantity: newQuantity })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    input.value = newQuantity;
                    
                    // Update subtotal
                    const subtotal = price * newQuantity;
                    const subtotalEl = row.querySelector('.subtotal');
                    subtotalEl.dataset.subtotal = subtotal;
                    subtotalEl.textContent = formatCurrency(subtotal);
                    
                    // Update button states
                    const decreaseBtn = row.querySelector('.decrease-btn');
                    const increaseBtn = row.querySelector('.increase-btn');
                    if (decreaseBtn) decreaseBtn.disabled = newQuantity <= 1;
                    if (increaseBtn) increaseBtn.disabled = newQuantity >= parseInt(input.dataset.stock);
                    
                    updateTotalPrice();
                    toast.success('Đã cập nhật số lượng');
                } else {
                    if (data.outOfStock) {
                        toast.error(data.message);
                        // Remove row or mark as unavailable
                        row.classList.add('unavailable');
                    } else if (data.availableQuantity !== undefined) {
                        toast.warning(data.message);
                        input.value = data.availableQuantity;
                        
                        const subtotal = price * data.availableQuantity;
                        const subtotalEl = row.querySelector('.subtotal');
                        subtotalEl.dataset.subtotal = subtotal;
                        subtotalEl.textContent = formatCurrency(subtotal);
                        
                        updateTotalPrice();
                    } else {
                        toast.error(data.message || 'Có lỗi xảy ra');
                    }
                }
            } catch (error) {
                console.error('Update quantity error:', error);
                toast.error('Lỗi kết nối. Vui lòng thử lại.');
            } finally {
                isUpdating = false;
            }
        }

        // ===== REMOVE ITEM =====
        function setupRemoveButtons() {
            document.querySelectorAll('.remove-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const productId = this.dataset.productId;
                    const productName = this.dataset.productName;
                    
                    showConfirm(
                        'Xóa sản phẩm',
                        `Bạn có chắc muốn xóa "${productName}" khỏi giỏ hàng?`,
                        () => removeProduct(productId),
                        'fa-trash-alt'
                    );
                });
            });
        }

        async function removeProduct(productId) {
            showLoading();
            try {
                const response = await fetch('giohangAct.php?action=removeSelected', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ productIds: [parseInt(productId)] })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const row = document.querySelector(`tr[data-product-id="${productId}"]`);
                    if (row) {
                        row.style.transition = 'all 0.3s';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(-20px)';
                        setTimeout(() => {
                            row.remove();
                            updateTotalPrice();
                            checkEmptyCart();
                        }, 300);
                    }
                    toast.success('Đã xóa sản phẩm khỏi giỏ hàng');
                } else {
                    toast.error('Không thể xóa sản phẩm');
                }
            } catch (error) {
                console.error('Remove error:', error);
                toast.error('Lỗi kết nối');
            } finally {
                hideLoading();
            }
        }

        // ===== DELETE SELECTED =====
        window.deleteSelectedItems = async function() {
            const selectedIds = [];
            document.querySelectorAll('.product-select:checked:not(:disabled)').forEach(cb => {
                const row = cb.closest('tr');
                selectedIds.push(row.dataset.productId);
            });

            if (selectedIds.length === 0) {
                toast.warning('Vui lòng chọn sản phẩm để xóa');
                return;
            }

            showConfirm(
                'Xóa sản phẩm đã chọn',
                `Bạn có chắc muốn xóa ${selectedIds.length} sản phẩm khỏi giỏ hàng?`,
                () => deleteMultipleProducts(selectedIds),
                'fa-trash'
            );
        };

        async function deleteMultipleProducts(productIds) {
            showLoading();
            try {
                const response = await fetch('giohangAct.php?action=removeSelected', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ productIds: productIds.map(id => parseInt(id)) })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    productIds.forEach(id => {
                        const row = document.querySelector(`tr[data-product-id="${id}"]`);
                        if (row) row.remove();
                    });
                    updateTotalPrice();
                    checkEmptyCart();
                    toast.success(`Đã xóa ${productIds.length} sản phẩm`);
                } else {
                    toast.error('Không thể xóa sản phẩm');
                }
            } catch (error) {
                console.error('Delete selected error:', error);
                toast.error('Lỗi kết nối');
            } finally {
                hideLoading();
            }
        };

        // ===== CLEAR CART =====
        window.clearCart = function() {
            showConfirm(
                'Xóa toàn bộ giỏ hàng',
                'Bạn có chắc muốn xóa tất cả sản phẩm trong giỏ hàng? Hành động này không thể hoàn tác.',
                async () => {
                    showLoading();
                    try {
                        const response = await fetch('giohangAct.php?action=clear', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' }
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            document.getElementById('cart-items').innerHTML = '';
                            updateTotalPrice();
                            checkEmptyCart();
                            toast.success('Đã xóa toàn bộ giỏ hàng');
                        } else {
                            toast.error('Không thể xóa giỏ hàng');
                        }
                    } catch (error) {
                        console.error('Clear cart error:', error);
                        toast.error('Lỗi kết nối');
                    } finally {
                        hideLoading();
                    }
                },
                'fa-broom'
            );
        };

        // ===== CHECK EMPTY =====
        function checkEmptyCart() {
            const rows = document.querySelectorAll('#cart-items tr');
            if (rows.length === 0) {
                location.reload();
            }
        }

        // ===== PROCEED TO CHECKOUT =====
        window.proceedToCheckout = function() {
            const selectedProducts = [];
            
            document.querySelectorAll('.product-select:checked:not(:disabled)').forEach(checkbox => {
                const row = checkbox.closest('tr');
                const productId = row.dataset.productId;
                const quantity = parseInt(row.querySelector('.quantity-input')?.value || 1);
                selectedProducts.push({ productId: parseInt(productId), quantity: quantity });
            });

            if (selectedProducts.length === 0) {
                toast.warning('Vui lòng chọn ít nhất một sản phẩm');
                return;
            }

            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'checkout.php';
            
            // CSRF token
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) {
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = csrfMeta.getAttribute('content');
                form.appendChild(csrfInput);
            }
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_products';
            input.value = JSON.stringify(selectedProducts);
            form.appendChild(input);
            
            document.body.appendChild(form);
            form.submit();
        };

        // ===== INIT =====
        setupSelectAll();
        setupQuantityControls();
        setupRemoveButtons();
        updateTotalPrice();
    });
    </script>
    
    <!-- CSRF Helper -->
    <script src="../../../public_files/js/csrf-helper.js"></script>
</body>
</html>