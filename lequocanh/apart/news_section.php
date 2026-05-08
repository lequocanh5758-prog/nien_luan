<?php

require_once __DIR__ . '/../administrator/elements_LQA/mod/PageManager.php';

$pageManager = new PageManager();

$latestBlogs = $pageManager->getAllBlogs(true);
$latestBlogs = array_slice($latestBlogs, 0, 4);
?>

<style>
.marketing-section {
    background: #f8f9fa;
    padding: 30px 0;
    margin-top: 30px;
}

.marketing-column {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.column-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 15px;
    border-bottom: 2px solid #e0e0e0;
    margin-bottom: 15px;
}

.column-header h5 {
    margin: 0;
    font-weight: 700;
    color: #333;
}

.column-header a {
    font-size: 13px;
}

/* Promotions Compact */
.promo-list-compact {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
}

.promo-item-compact {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    border-radius: 10px;
    background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
    border-left: 4px solid #dc3545;
}

.promo-discount {
    background: #dc3545;
    color: #fff;
    padding: 8px 12px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 16px;
    white-space: nowrap;
}

.promo-info {
    flex: 1;
}

.promo-info h6 {
    margin: 0 0 6px 0;
    font-size: 14px;
    font-weight: 600;
    color: #333;
}

.promo-info small {
    font-size: 12px;
    color: #666;
}

.promo-buy-btn {
    padding: 8px 16px;
    background: #dc3545;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    text-decoration: none;
    white-space: nowrap;
}

.promo-buy-btn:hover {
    background: #c82333;
    color: #fff;
}

/* Blog Compact */
.blog-list-compact {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
}

@media (max-width: 768px) {
    .blog-list-compact {
        grid-template-columns: repeat(2, 1fr);
    }
    .promo-list-compact {
        grid-template-columns: 1fr;
    }
}

.blog-item-compact {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
    text-decoration: none;
    color: inherit;
}

.blog-item-compact:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.blog-thumb {
    width: 100%;
    height: 100px;
    object-fit: cover;
    background: #e9ecef;
}

.blog-thumb-placeholder {
    width: 100%;
    height: 100px;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
}

.blog-item-compact .card-body {
    padding: 10px;
}

.blog-item-compact h6 {
    font-size: 13px;
    font-weight: 600;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.4;
}

.blog-item-compact h6:hover {
    color: #0d6efd;
}

.blog-item-compact .blog-meta {
    font-size: 11px;
    color: #888;
    margin-top: 5px;
}
</style>

<section class="marketing-section">
    <div class="container">
        <!-- Blog Section -->
        <?php if (!empty($latestBlogs)): ?>
        <div class="marketing-column">
            <div class="column-header">
                <h5><i class="fas fa-blog text-primary me-2"></i>Bài Viết Mới</h5>
                <a href="blog.php" class="btn btn-outline-primary btn-sm">Xem tất cả</a>
            </div>
            <div class="blog-list-compact">
                <?php foreach ($latestBlogs as $blog): ?>
                <a href="page.php?slug=<?php echo htmlspecialchars($blog['slug']); ?>" class="blog-item-compact">
                    <?php if ($blog['thumbnail']): ?>
                    <img src="/lequocanh/administrator/elements_LQA/madmin/displayImage.php?type=page&id=<?php echo $blog['id']; ?>" 
                         class="blog-thumb" alt="">
                    <?php else: ?>
                    <div class="blog-thumb-placeholder">
                        <i class="fas fa-image fa-2x text-muted"></i>
                    </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h6><?php echo htmlspecialchars($blog['title']); ?></h6>
                        <div class="blog-meta">
                            <i class="far fa-calendar-alt me-1"></i><?php echo date('d/m/Y', strtotime($blog['created_at'])); ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
