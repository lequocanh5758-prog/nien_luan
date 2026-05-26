<?php ob_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../administrator/elements_LQA/mod/loaihangCls.php';
require_once __DIR__ . '/../includes/query_builder.php';
require_once __DIR__ . '/../includes/advanced_cache.php';
require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
require_once __DIR__ . '/../includes/performance_bootstrap.php';

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductReview;

$hasFilters = isset($_GET['min_price']) || isset($_GET['max_price']) ||
    isset($_GET['colors']) || isset($_GET['sizes']) || isset($_GET['min_rating']);

if ($hasFilters) {
    $filters = [
        'min_price' => isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0,
        'max_price' => isset($_GET['max_price']) ? (int)$_GET['max_price'] : 100000000,
        'colors' => isset($_GET['colors']) ? explode(',', $_GET['colors']) : [],
        'sizes' => isset($_GET['sizes']) ? explode(',', $_GET['sizes']) : [],
        'category' => isset($_GET['reqView']) ? (int)$_GET['reqView'] : null,
        'min_rating' => isset($_GET['min_rating']) ? (int)$_GET['min_rating'] : 0
    ];
    $list_hanghoa = Product::filterProducts($filters);
} elseif (isset($_GET['reqView'])) {
    $idloaihang = $_GET['reqView'];
    
    $list_hanghoa = cache_remember('products_category_' . $idloaihang, 300, function() use ($idloaihang) {
        return Product::getByCategoryWithPricing((int)$idloaihang);
    });
} else {
    $list_hanghoa = cache_remember('all_products', 300, function() {
        return Product::getAllWithPricing();
    });
}

// Pagination
$productsPerPage = 20;
$totalProducts = count($list_hanghoa);
$totalPages = ceil($totalProducts / $productsPerPage);
$currentPage = max(1, min(isset($_GET['page']) ? (int)$_GET['page'] : 1, $totalPages));
$offset = ($currentPage - 1) * $productsPerPage;
$paginatedProducts = array_slice($list_hanghoa, $offset, $productsPerPage);

$carousel_items = array_slice($list_hanghoa, 0, 5);

?>

<?php include __DIR__ . '/../components/head.php'; ?>
<body>
<div class="page-loader" id="pageLoader"></div>

<?php
// Initialize cart count for navbar
require_once __DIR__ . '/../administrator/elements_LQA/mod/giohangCls.php';
$giohang = new GioHang();
$cartItemCount = $giohang->getCartItemCount();
?>

<!-- Navbar with Category Menu -->
<?php include __DIR__ . '/../components/navbar.php'; ?>

<!-- Rating Styles -->
<link rel="stylesheet" href="/lequocanh/public_files/rating_styles.css">

<!-- Product Filter Styles -->
<link rel="stylesheet" href="/lequocanh/public_files/product_filter.css">

<!-- Carousel kết hợp sản phẩm nổi bật, mới, khuyến mãi và banner quảng cáo -->
<?php include __DIR__ . '/productBannerCarousel.php'; ?>

<!-- Sản phẩm nổi bật, mới, khuyến mãi -->
<?php include __DIR__ . '/../components/featuredProductsDisplay.php'; ?>

<!-- Carousel sản phẩm cũ (đã được thay thế) -->
<!--
<div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">
        <?php
        if (!empty($carousel_items)) {
            foreach ($carousel_items as $index => $item):

                $hinhanh = ProductImage::getById((int)$item->hinhanh);
        ?>
        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>" data-bs-interval="3000">
            <a href="./index.php?reqHanghoa=<?php echo $item->idhanghoa; ?>">
                <?php if ($hinhanh && (!empty($hinhanh->duong_dan) || !empty($hinhanh->du_lieu))): ?>
                <img src="/lequocanh/administrator/elements_LQA/mhanghoa/displayImage.php?id=<?php echo $item->hinhanh; ?>"
                    class="d-block" alt="<?php echo $item->tenhanghoa; ?>" loading="lazy">
                <?php else: ?>
                <div class="updating-image-container">
                    <img src="/lequocanh/administrator/elements_LQA/img_LQA/no-image.png" alt="Không có hình ảnh">
                </div>
                <?php endif; ?>
            </a>
        </div>
        <?php
            endforeach;
        } else {
            echo '<div class="alert alert-warning">Không có sản phẩm nào để hiển thị</div>';
        }
        ?>
    </div>

    <?php if (count($carousel_items) > 1): ?>
    <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
    <?php endif; ?>
</div>
-->

<?php

// News and Promotions are now displayed in news_section.php
?>

<h3 class="section-title my-4">
    <i class="fas fa-mobile-alt text-success"></i> Sản phẩm
</h3>

<style>

    .section-title {
        font-weight: 700;
        color: #333;
        padding-bottom: 10px;
        border-bottom: 3px solid #007bff;
        display: inline-block;
    }

    .promotion-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-left: 4px solid #dc3545 !important;
    }

    .promotion-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(220, 53, 69, 0.2);
    }

    .news-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 1px solid #e0e0e0;
    }

    .news-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }

    .news-card .card-img-top {
        transition: transform 0.3s ease;
    }

    .news-card:hover .card-img-top {
        transform: scale(1.05);
        overflow: hidden;
    }

    .product-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        color: #fff;
        z-index: 10;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .badge-featured {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .badge-new {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .badge-sale {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        color: #333;
    }

    .discount-badge {
        position: absolute;
        top: 10px;
        left: 10px;
        background: #e74c3c;
        color: #fff;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 700;
        z-index: 10;
    }

    .card {
        position: relative;
    }

    .card-img-top {
        height: 250px;
        object-fit: contain;
        padding: 10px;
        background-color: #f8f9fa;
    }
    
    .products-container {
        display: flex !important;
        gap: 30px;
        align-items: stretch !important;
        height: auto !important;
        min-height: 500px;
        max-height: none;
        margin-bottom: 30px;
    }
    
    .filter-column {
        flex: 0 0 280px !important;
        min-width: 280px;
        height: auto !important;
        overflow: visible;
        position: sticky;
        top: 20px;
        align-self: flex-start;
    }
    
    .filter-sidebar {
        max-height: calc(100vh - 40px);
        overflow-y: auto !important;
    }
    
    .products-column {
        flex: 1 !important;
        height: 100% !important;
        overflow-y: auto !important;
        padding: 15px;
        background: #fff;
        border-radius: 8px;
    }
    
    .products-column::-webkit-scrollbar,
    .filter-sidebar::-webkit-scrollbar {
        width: 8px;
    }
    
    .products-column::-webkit-scrollbar-thumb,
    .filter-sidebar::-webkit-scrollbar-thumb {
        background: #007bff;
        border-radius: 4px;
    }
    
    .products-column::-webkit-scrollbar-track,
    .filter-sidebar::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
</style>

<!-- Filter Toggle Button (Mobile) -->
<button class="filter-toggle-btn" id="filterToggleBtn">
    <i class="fas fa-filter"></i>
    Bộ lọc
</button>

<!-- Filter Backdrop (Mobile) -->
<div class="filter-backdrop" id="filterBackdrop"></div>

<!-- Products Layout with Filters -->
<div class="products-container">
    <!-- Filter Sidebar -->
    <div class="filter-column" id="filterColumn">
        <div class="filter-sidebar">
            <!-- Mobile Header -->
            <div class="filter-mobile-header d-lg-none">
                <h4>Bộ lọc sản phẩm</h4>
                <button class="filter-close-btn" id="filterCloseBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <h4 class="d-none d-lg-block">Bộ lọc sản phẩm</h4>

            <!-- Price Filter -->
            <div class="filter-section price-filter">
                <h5><i class="fas fa-dollar-sign"></i> Khoảng giá</h5>
                <div class="price-range-slider">
                    <input type="range" class="price-range-input" id="minPrice"
                        min="0" max="100000000" step="100000" value="0">
                    <input type="range" class="price-range-input" id="maxPrice"
                        min="0" max="100000000" step="100000" value="100000000">
                </div>
                <div class="price-values">
                    <div class="price-value" id="minPriceDisplay">0 ₫</div>
                    <div class="price-value" id="maxPriceDisplay">100,000,000 ₫</div>
                </div>
            </div>

            <!-- Color Filter - Server-side Rendered -->
            <div class="filter-section color-filter">
                <h5><i class="fas fa-palette"></i> Màu sắc</h5>
                <div class="color-options" id="colorFilterContainer">
                    <?php include __DIR__ . '/render_color_filter.php'; ?>
                </div>
            </div>

            <!-- Rating Filter -->
            <div class="filter-section rating-filter">
                <h5><i class="fas fa-star"></i> Đánh giá</h5>
                <div class="rating-options" id="ratingFilterContainer">
                    <label class="rating-option">
                        <input type="checkbox" name="rating" value="5" class="rating-checkbox">
                        <span class="rating-stars">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </span>
                        <span class="rating-text">5 sao</span>
                    </label>
                    <label class="rating-option">
                        <input type="checkbox" name="rating" value="4" class="rating-checkbox">
                        <span class="rating-stars">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i>
                        </span>
                        <span class="rating-text">4 sao</span>
                    </label>
                    <label class="rating-option">
                        <input type="checkbox" name="rating" value="3" class="rating-checkbox">
                        <span class="rating-stars">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                        </span>
                        <span class="rating-text">3 sao</span>
                    </label>
                    <label class="rating-option">
                        <input type="checkbox" name="rating" value="2" class="rating-checkbox">
                        <span class="rating-stars">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                        </span>
                        <span class="rating-text">2 sao</span>
                    </label>
                    <label class="rating-option">
                        <input type="checkbox" name="rating" value="1" class="rating-checkbox">
                        <span class="rating-stars">
                            <i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i>
                        </span>
                        <span class="rating-text">1 sao</span>
                    </label>
                </div>
            </div>

            <!-- Filter Actions -->
            <div class="filter-actions">
                <button class="btn-clear-filters">
                    <i class="fas fa-redo"></i> Xóa bộ lọc
                </button>
                <button class="btn-apply-filters d-lg-none">
                    <i class="fas fa-check"></i> Áp dụng
                </button>
            </div>
        </div>

        <!-- News Section in Filter Column -->
        <?php
        require_once __DIR__ . '/../administrator/elements_LQA/mod/NewsManager.php';
        $newsManagerSidebar = new NewsManager();
        $sidebarNews = $newsManagerSidebar->getPublishedNews(5);
        if (!empty($sidebarNews)):
        ?>
        <div class="sidebar-news-section" style="margin-top: 20px;">
            <h5 class="sidebar-news-title">
                <i class="fas fa-newspaper text-primary me-2"></i>Tin tức mới nhất
            </h5>
            <div class="sidebar-news-list">
                <?php foreach ($sidebarNews as $news): ?>
                <a href="news_detail.php?id=<?php echo $news['id']; ?>" class="sidebar-news-item">
                    <?php if ($news['featured_image']): ?>
                    <img src="/lequocanh/administrator/elements_LQA/madmin/displayImage.php?type=news&id=<?php echo $news['id']; ?>" 
                         class="sidebar-news-thumb" alt="" loading="lazy">
                    <?php else: ?>
                    <div class="sidebar-news-thumb-placeholder">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <?php endif; ?>
                    <div class="sidebar-news-info">
                        <div class="sidebar-news-name"><?php echo htmlspecialchars($news['title']); ?></div>
                        <div class="sidebar-news-date">
                            <i class="far fa-clock me-1"></i><?php echo date('d/m/Y', strtotime($news['published_date'])); ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <a href="all_news.php" class="sidebar-news-more">Xem tất cả tin tức →</a>
        </div>

        <style>
        .sidebar-news-section {
            background: #fff;
            border-radius: 8px;
            padding: 15px;
        }
        
        .sidebar-news-title {
            font-size: 14px;
            font-weight: 700;
            color: #333;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #007bff;
        }
        
        .sidebar-news-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .sidebar-news-item {
            display: flex;
            gap: 10px;
            padding: 8px;
            border-radius: 6px;
            text-decoration: none;
            color: inherit;
            transition: background 0.2s;
        }
        
        .sidebar-news-item:hover {
            background: #f0f0f0;
        }
        
        .sidebar-news-thumb {
            width: 60px;
            height: 45px;
            border-radius: 4px;
            object-fit: cover;
            flex-shrink: 0;
        }
        
        .sidebar-news-thumb-placeholder {
            width: 60px;
            height: 45px;
            border-radius: 4px;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #999;
            font-size: 14px;
        }
        
        .sidebar-news-info {
            flex: 1;
            min-width: 0;
        }
        
        .sidebar-news-name {
            font-size: 13px;
            font-weight: 600;
            color: #333;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.4;
            margin-bottom: 4px;
        }
        
        .sidebar-news-name:hover {
            color: #007bff;
        }
        
        .sidebar-news-date {
            font-size: 11px;
            color: #888;
        }
        
        .sidebar-news-more {
            display: block;
            text-align: center;
            margin-top: 12px;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 13px;
            color: #007bff;
            text-decoration: none;
        }
        
        .sidebar-news-more:hover {
            background: #e9ecef;
        }

        /* Sidebar Promotions */
        .sidebar-promo-section {
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .sidebar-promo-title {
            font-size: 14px;
            font-weight: 700;
            color: #333;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #dc3545;
        }
        
        .sidebar-promo-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .sidebar-promo-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border-radius: 8px;
            background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
            border-left: 4px solid #dc3545;
        }
        
        .sidebar-promo-badge {
            background: #dc3545;
            color: #fff;
            padding: 6px 10px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 13px;
            white-space: nowrap;
        }
        
        .sidebar-promo-info {
            flex: 1;
            min-width: 0;
        }
        
        .sidebar-promo-name {
            font-size: 13px;
            font-weight: 600;
            color: #333;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.4;
            margin-bottom: 4px;
        }
        
        .sidebar-promo-date {
            font-size: 11px;
            color: #888;
        }
        
        .sidebar-promo-btn {
            display: block;
            text-align: center;
            margin-top: 12px;
            padding: 10px;
            background: #dc3545;
            color: #fff;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }
        
        .sidebar-promo-btn:hover {
            background: #c82333;
            color: #fff;
        }
        </style>
        <?php endif; ?>
        
        <!-- Promotions Section in Sidebar -->
        <?php
        require_once __DIR__ . '/../administrator/elements_LQA/mod/PromotionManager.php';
        $promoManagerSidebar = new PromotionManager();
        $sidebarPromos = $promoManagerSidebar->getActivePromotions();
        if (!empty($sidebarPromos)):
        ?>
        <div class="sidebar-promo-section">
            <h5 class="sidebar-promo-title">
                <i class="fas fa-gift text-danger me-2"></i>Ưu đãi hot
            </h5>
            <div class="sidebar-promo-list">
                <?php foreach (array_slice($sidebarPromos, 0, 3) as $promo): ?>
                <div class="sidebar-promo-item">
                    <div class="sidebar-promo-badge">-<?php echo $promo['discount_percent']; ?>%</div>
                    <div class="sidebar-promo-info">
                        <div class="sidebar-promo-name"><?php echo htmlspecialchars($promo['title']); ?></div>
                        <div class="sidebar-promo-date">
                            <i class="far fa-calendar me-1"></i>Đến: <?php echo date('d/m/Y', strtotime($promo['end_date'])); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <a href="index.php?sale=1" class="sidebar-promo-btn">
                <i class="fas fa-shopping-cart me-2"></i>Xem sản phẩm giảm giá
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Products Column -->
    <div class="products-column">
        <!-- Active Filters Display -->
        <div class="active-filters empty" id="activeFilters"></div>

        <!-- Results Count -->
        <div class="results-count">
            Hiển thị <?php echo count($paginatedProducts); ?> trong tổng số <?php echo $totalProducts; ?> sản phẩm
            <?php if ($totalPages > 1): ?>
                | Trang <?php echo $currentPage; ?>/<?php echo $totalPages; ?>
            <?php endif; ?>
        </div>

        <h3 class="section-title my-4">
            <i class="fas fa-mobile-alt text-success"></i> Sản phẩm
        </h3>

        <div class="row row-cols-1 row-cols-md-3 g-4 product-list-grid">
            <?php 
            // Batch load ratings for all products on this page
            $productIds = array_map(function($p) { return (int)$p->idhanghoa; }, $paginatedProducts);
            $allRatings = ProductReview::getAverageRatingBatch($productIds);
            
            foreach ($paginatedProducts as $v):

                $hinhanh = ProductImage::getById((int)$v->hinhanh);
                $ratingInfo = $allRatings[(int)$v->idhanghoa] ?? ['average' => 0, 'count' => 0];
                $avgRating = $ratingInfo['average'];
                $reviewCount = $ratingInfo['count'];

                $hasDiscount = false;
                $discountPercent = 0;
                if (isset($v->giakhuyenmai) && $v->giakhuyenmai > 0 && $v->giakhuyenmai < $v->giathamkhao) {
                    $hasDiscount = true;
                    $discountPercent = round((($v->giathamkhao - $v->giakhuyenmai) / $v->giathamkhao) * 100);
                }
            ?>
                <div class="col">
                    <div class="card h-100 position-relative">
                        <?php

                        if ($hasDiscount): ?>
                            <span class="discount-badge">-<?php echo $discountPercent; ?>%</span>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['USER'])): ?>
                        <!-- Nút yêu thích -->
                        <button class="product-wishlist-btn" data-product-id="<?php echo $v->idhanghoa; ?>" 
                                onclick="event.preventDefault(); event.stopPropagation(); Wishlist.toggle(<?php echo $v->idhanghoa; ?>, this)" 
                                title="Thêm vào yêu thích">
                            <i class="far fa-heart"></i>
                        </button>
                        <?php endif; ?>

                        <?php if ($hinhanh && (!empty($hinhanh->duong_dan) || !empty($hinhanh->du_lieu))): ?>
                            <img src="/lequocanh/administrator/elements_LQA/mhanghoa/displayImage.php?id=<?php echo $v->hinhanh; ?>"
                                class="card-img-top" alt="<?php echo $v->tenhanghoa; ?>" loading="lazy">
                        <?php else: ?>
                            <div class="updating-image-container">
                                <img src="/lequocanh/administrator/elements_LQA/img_LQA/no-image.png" alt="Không có hình ảnh" loading="lazy">
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $v->tenhanghoa; ?></h5>

                            <?php

                            ?>
                            <div class="product-code" style="font-size: 11px; color: #6c757d; margin: 4px 0 8px 0;">
                                Mã: SP-<?php echo str_pad($v->idhanghoa, 5, '0', STR_PAD_LEFT); ?>
                            </div>

                            <?php

                            if ($reviewCount > 0):
                                $highRating = $avgRating >= 4.5 ? 'high-rating' : '';
                            ?>
                                <div class="product-rating <?php echo $highRating; ?>" style="display: flex; align-items: center; gap: 4px; margin-bottom: 8px; font-size: 13px;">
                                    <?php

                                    for ($i = 1; $i <= 5; $i++):
                                        if ($i <= floor($avgRating)):
                                    ?>
                                            <i class="fas fa-star" style="color: #ffc107;"></i>
                                        <?php elseif ($i == ceil($avgRating) && ($avgRating - floor($avgRating) >= 0.5)): ?>
                                            <i class="fas fa-star-half-alt" style="color: #ffc107;"></i>
                                        <?php else: ?>
                                            <i class="far fa-star" style="color: #ffc107;"></i>
                                    <?php endif;
                                    endfor; ?>
                                    <span style="font-weight: 600; color: #333; margin-left: 4px;"><?php echo number_format($avgRating, 1); ?></span>
                                    <span style="color: #6c757d; font-size: 12px;">(<?php echo $reviewCount; ?>)</span>
                                </div>
                            <?php else: ?>
                                <div class="product-rating" style="display: flex; align-items: center; gap: 4px; margin-bottom: 8px; font-size: 13px; opacity: 0.5;">
                                    <i class="far fa-star" style="color: #ddd;"></i>
                                    <i class="far fa-star" style="color: #ddd;"></i>
                                    <i class="far fa-star" style="color: #ddd;"></i>
                                    <i class="far fa-star" style="color: #ddd;"></i>
                                    <i class="far fa-star" style="color: #ddd;"></i>
                                    <span style="color: #999; font-size: 11px; margin-left: 4px;">Chưa có đánh giá</span>
                                </div>
                            <?php endif; ?>

                            <p class="card-text text-danger fw-bold">
                                <?php
                                if ($hasDiscount) {
                                    echo '<span style="font-size: 20px;">' . number_format($v->giakhuyenmai, 0, ',', '.') . ' VNĐ</span><br>';
                                    echo '<span style="font-size: 14px; color: #999; text-decoration: line-through;">' . number_format($v->giathamkhao, 0, ',', '.') . ' VNĐ</span>';
                                } else {
                                    echo number_format($v->giathamkhao, 0, ',', '.') . ' VNĐ';
                                }
                                ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="./index.php?reqHanghoa=<?php echo $v->idhanghoa; ?>" class="btn btn-outline-primary">
                                    Xem chi tiết
                                </a>
                                <!-- Thêm checkbox để so sánh -->
                                <div class="form-check">
                                    <input class="form-check-input compare-checkbox" type="checkbox"
                                        value="<?php echo $v->idhanghoa; ?>" id="compare_<?php echo $v->idhanghoa; ?>">
                                    <label class="form-check-label" for="compare_<?php echo $v->idhanghoa; ?>">
                                        So sánh
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Thêm nút so sánh cố định ở góc màn hình -->
        <div id="compareButton" class="position-fixed bottom-0 end-0 mb-4 me-4" style="display: none;">
            <button class="btn btn-primary" onclick="compareProducts()">
                So sánh (<span id="compareCount">0</span>)
            </button>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const compareCheckboxes = document.querySelectorAll('.compare-checkbox');
                const compareButton = document.getElementById('compareButton');
                const compareCount = document.getElementById('compareCount');
                let selectedProducts = [];

                compareCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                compareButton.style.display = 'none';
                compareCount.textContent = '0';

                compareCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        if (this.checked) {
                            if (selectedProducts.length >= 3) {
                                alert('Chỉ có thể so sánh tối đa 3 sản phẩm!');
                                this.checked = false;
                                return;
                            }
                            selectedProducts.push(this.value);
                        } else {
                            selectedProducts = selectedProducts.filter(id => id !== this.value);
                        }

                        compareCount.textContent = selectedProducts.length;
                        compareButton.style.display = selectedProducts.length > 1 ? 'block' : 'none';
                    });
                });
            });

            function compareProducts() {
                const selectedProducts = Array.from(document.querySelectorAll('.compare-checkbox:checked'))
                    .map(checkbox => checkbox.value);

                if (selectedProducts.length < 2) {
                    alert('Vui lòng chọn ít nhất 2 sản phẩm để so sánh!');
                    return;
                }

                window.location.href = `sosanh.php?products=${selectedProducts.join(',')}`;
            }

            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('cartAdded')) {

                alert('Đã thêm sản phẩm vào giỏ hàng!');

                const newUrl = window.location.href.replace(/[&?]cartAdded=1/, '');
                window.history.replaceState({}, document.title, newUrl);
            }

        </script>

        <?php if ($totalPages > 1): ?>
        <!-- Pagination -->
        <nav aria-label="Product pagination" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($currentPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php 
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                for ($i = $startPage; $i <= $endPage; $i++): 
                ?>
                    <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($currentPage < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>

    </div> <!-- End products-column -->
</div> <!-- End products-container -->

<!-- Footer -->
<?php include __DIR__ . '/../components/footer.php'; ?>

<!-- Scripts -->
<?php include __DIR__ . '/../components/scripts.php'; ?>

</body>
</html>
<?php ob_end_flush(); ?>