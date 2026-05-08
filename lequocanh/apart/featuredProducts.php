<?php

require_once __DIR__ . '/../administrator/elements_LQA/mod/FeaturedProductsCls.php';

$featuredMgr = new FeaturedProducts();

$featuredProducts = $featuredMgr->getFeaturedProducts(16);
$newProducts = $featuredMgr->getNewProducts(16);
$saleProducts = $featuredMgr->getSaleProducts(16);

function formatPrice($price) {
    return number_format($price, 0, ',', '.') . ' đ';
}

function getProductImage($product) {
    if (!empty($product->hinhanh) && $product->hinhanh != 0) {
        return "administrator/elements_LQA/mhanghoa/displayImage.php?id=" . $product->hinhanh;
    }
    return "public_files/no-image.png";
}
?>

<style>
.featured-section {
    margin: 30px 0;
    padding: 20px 0;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e74c3c;
}

.section-title {
    font-size: 24px;
    font-weight: bold;
    color: #333;
    position: relative;
}

.section-title::before {
    content: '';
    position: absolute;
    bottom: -12px;
    left: 0;
    width: 60px;
    height: 3px;
    background: #e74c3c;
}

.view-all-link {
    color: #e74c3c;
    text-decoration: none;
    font-size: 14px;
}

.view-all-link:hover {
    text-decoration: underline;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
    margin-top: 20px;
}

.product-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
}

.product-card:hover {
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    transform: translateY(-5px);
}

.product-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: bold;
    color: #fff;
    z-index: 1;
}

.badge-new {
    background: #27ae60;
}

.badge-sale {
    background: #e74c3c;
}

.badge-featured {
    background: #f39c12;
}

.product-image {
    width: 100%;
    height: 300px;
    object-fit: contain;
    background: #f5f5f5;
    padding: 15px;
}

.product-info {
    padding: 18px;
}

.product-name {
    font-size: 17px;
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 50px;
}

.product-brand {
    font-size: 14px;
    color: #666;
    margin-bottom: 12px;
}

.product-price {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.current-price {
    font-size: 22px;
    font-weight: bold;
    color: #e74c3c;
}

.original-price {
    font-size: 15px;
    color: #999;
    text-decoration: line-through;
}

.discount-badge {
    background: #e74c3c;
    color: #fff;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: bold;
}

.product-actions {
    display: flex;
    gap: 10px;
}

.btn-view, .btn-cart {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
}

.btn-view {
    background: #fff;
    border: 1px solid #e74c3c;
    color: #e74c3c;
}

.btn-view:hover {
    background: #e74c3c;
    color: #fff;
}

.btn-cart {
    background: #e74c3c;
    color: #fff;
}

.btn-cart:hover {
    background: #c0392b;
}

.sale-countdown {
    font-size: 12px;
    color: #e74c3c;
    margin-top: 5px;
}

.wishlist-btn {
    position: absolute;
    top: 10px;
    left: 10px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #fff;
    border: 1px solid #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 2;
    transition: all 0.3s;
}

.wishlist-btn:hover {
    background: #ff4757;
    border-color: #ff4757;
    color: #fff;
}

.wishlist-btn.active {
    background: #ff4757;
    border-color: #ff4757;
    color: #fff;
}

.wishlist-btn i {
    font-size: 16px;
    color: inherit;
}

.wishlist-btn:hover i,
.wishlist-btn.active i {
    color: #fff;
}

@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    
    .product-image {
        height: 200px;
    }
    
    .product-info {
        padding: 12px;
    }
    
    .product-name {
        font-size: 14px;
        min-height: 40px;
    }
    
    .current-price {
        font-size: 18px;
    }
}
</style>

<!-- Sản phẩm nổi bật -->
<?php if (!empty($featuredProducts)): ?>
<div class="featured-section">
    <div class="section-header">
        <h2 class="section-title">🔥 Sản Phẩm Nổi Bật</h2>
        <a href="?featured=1" class="view-all-link">Xem tất cả →</a>
    </div>
    
    <div class="products-grid">
        <?php foreach ($featuredProducts as $product): ?>
        <div class="product-card">
            <button class="wishlist-btn" onclick="toggleWishlist(<?= $product->idhanghoa ?>, this)" title="Thêm vào yêu thích">
                <i class="far fa-heart"></i>
            </button>
            <span class="product-badge badge-featured">Nổi bật</span>
            <a href="?idhanghoa=<?= $product->idhanghoa ?>">
                <img src="<?= getProductImage($product) ?>" 
                     alt="<?= htmlspecialchars($product->tenhanghoa) ?>" 
                     class="product-image"
                     loading="lazy">
            </a>
            <div class="product-info">
                <h3 class="product-name"><?= htmlspecialchars($product->tenhanghoa) ?></h3>
                <?php if (!empty($product->ten_thuonghieu)): ?>
                <div class="product-brand"><?= htmlspecialchars($product->ten_thuonghieu) ?></div>
                <?php endif; ?>
                
                <div class="product-price">
                    <span class="current-price"><?= formatPrice($product->gia_hien_tai) ?></span>
                    <?php if ($product->discount_percent > 0): ?>
                    <span class="original-price"><?= formatPrice($product->giathamkhao) ?></span>
                    <span class="discount-badge">-<?= $product->discount_percent ?>%</span>
                    <?php endif; ?>
                </div>
                
                <div class="product-actions">
                    <button class="btn-view" onclick="window.location.href='?idhanghoa=<?= $product->idhanghoa ?>'">
                        Xem chi tiết
                    </button>
                    <button class="btn-cart" onclick="addToCart(<?= $product->idhanghoa ?>)">
                        Thêm giỏ hàng
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Sản phẩm mới -->
<?php if (!empty($newProducts)): ?>
<div class="featured-section">
    <div class="section-header">
        <h2 class="section-title">✨ Sản Phẩm Mới</h2>
        <a href="?new=1" class="view-all-link">Xem tất cả →</a>
    </div>
    
    <div class="products-grid">
        <?php foreach ($newProducts as $product): ?>
        <div class="product-card">
            <button class="wishlist-btn" onclick="toggleWishlist(<?= $product->idhanghoa ?>, this)" title="Thêm vào yêu thích">
                <i class="far fa-heart"></i>
            </button>
            <span class="product-badge badge-new">Mới</span>
            <a href="?idhanghoa=<?= $product->idhanghoa ?>">
                <img src="<?= getProductImage($product) ?>" 
                     alt="<?= htmlspecialchars($product->tenhanghoa) ?>" 
                     class="product-image"
                     loading="lazy">
            </a>
            <div class="product-info">
                <h3 class="product-name"><?= htmlspecialchars($product->tenhanghoa) ?></h3>
                <?php if (!empty($product->ten_thuonghieu)): ?>
                <div class="product-brand"><?= htmlspecialchars($product->ten_thuonghieu) ?></div>
                <?php endif; ?>
                
                <div class="product-price">
                    <span class="current-price"><?= formatPrice($product->gia_hien_tai) ?></span>
                    <?php if ($product->discount_percent > 0): ?>
                    <span class="original-price"><?= formatPrice($product->giathamkhao) ?></span>
                    <span class="discount-badge">-<?= $product->discount_percent ?>%</span>
                    <?php endif; ?>
                </div>
                
                <div class="product-actions">
                    <button class="btn-view" onclick="window.location.href='?idhanghoa=<?= $product->idhanghoa ?>'">
                        Xem chi tiết
                    </button>
                    <button class="btn-cart" onclick="addToCart(<?= $product->idhanghoa ?>)">
                        Thêm giỏ hàng
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Sản phẩm khuyến mãi -->
<?php if (!empty($saleProducts)): ?>
<div class="featured-section">
    <div class="section-header">
        <h2 class="section-title">🎁 Khuyến Mãi Hot</h2>
        <a href="?sale=1" class="view-all-link">Xem tất cả →</a>
    </div>
    
    <div class="products-grid">
        <?php foreach ($saleProducts as $product): ?>
        <div class="product-card">
            <button class="wishlist-btn" onclick="toggleWishlist(<?= $product->idhanghoa ?>, this)" title="Thêm vào yêu thích">
                <i class="far fa-heart"></i>
            </button>
            <span class="product-badge badge-sale">-<?= $product->discount_percent ?>%</span>
            <a href="?idhanghoa=<?= $product->idhanghoa ?>">
                <img src="<?= getProductImage($product) ?>" 
                     alt="<?= htmlspecialchars($product->tenhanghoa) ?>" 
                     class="product-image"
                     loading="lazy">
            </a>
            <div class="product-info">
                <h3 class="product-name"><?= htmlspecialchars($product->tenhanghoa) ?></h3>
                <?php if (!empty($product->ten_thuonghieu)): ?>
                <div class="product-brand"><?= htmlspecialchars($product->ten_thuonghieu) ?></div>
                <?php endif; ?>
                
                <div class="product-price">
                    <span class="current-price"><?= formatPrice($product->gia_hien_tai) ?></span>
                    <span class="original-price"><?= formatPrice($product->giathamkhao) ?></span>
                    <span class="discount-badge">-<?= $product->discount_percent ?>%</span>
                </div>
                
                <?php if ($product->sale_end_date): ?>
                <div class="sale-countdown">
                    ⏰ Kết thúc: <?= date('d/m/Y', strtotime($product->sale_end_date)) ?>
                </div>
                <?php endif; ?>
                
                <div class="product-actions">
                    <button class="btn-view" onclick="window.location.href='?idhanghoa=<?= $product->idhanghoa ?>'">
                        Xem chi tiết
                    </button>
                    <button class="btn-cart" onclick="addToCart(<?= $product->idhanghoa ?>)">
                        Thêm giỏ hàng
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
function addToCart(productId) {
    window.location.href = '?addcart=' + productId;
}

async function toggleWishlist(productId, btn) {
    try {
        const formData = new FormData();
        formData.append('product_id', productId);
        
        const response = await fetch('/lequocanh/api/wishlist.php?action=toggle', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (result.action === 'added') {
                btn.classList.add('active');
                btn.querySelector('i').classList.remove('far');
                btn.querySelector('i').classList.add('fas');
                alert('Đã thêm vào yêu thích!');
            } else {
                btn.classList.remove('active');
                btn.querySelector('i').classList.remove('fas');
                btn.querySelector('i').classList.add('far');
                alert('Đã xóa khỏi yêu thích!');
            }
        } else {
            if (result.error === 'login_required') {
                alert('Vui lòng đăng nhập để sử dụng chức năng yêu thích!');
                window.location.href = '/lequocanh/index.php?req=login';
            } else {
                alert(result.error || 'Có lỗi xảy ra');
            }
        }
    } catch (error) {
        console.error('Wishlist error:', error);
        alert('Có lỗi xảy ra');
    }
}

// Check wishlist status on page load
async function checkWishlistStatus() {
    try {
        const response = await fetch('/lequocanh/api/wishlist.php?action=list', {
            credentials: 'include'
        });
        const result = await response.json();
        
        if (result.success && result.data.wishlist) {
            const wishlistIds = result.data.wishlist.map(item => item.product_id);
            
            document.querySelectorAll('.wishlist-btn').forEach(btn => {
                const onclick = btn.getAttribute('onclick');
                const match = onclick.match(/toggleWishlist\((\d+)/);
                if (match) {
                    const productId = parseInt(match[1]);
                    if (wishlistIds.includes(productId)) {
                        btn.classList.add('active');
                        btn.querySelector('i').classList.remove('far');
                        btn.querySelector('i').classList.add('fas');
                    }
                }
            });
        }
    } catch (error) {
        console.error('Check wishlist error:', error);
    }
}

// Run on page load
checkWishlistStatus();
</script>
