<?php
require_once __DIR__ . '/config/local_config.php';
require_once __DIR__ . '/administrator/elements_LQA/mod/sessionManager.php';
require_once __DIR__ . '/administrator/elements_LQA/mod/NewsManager.php';

SessionManager::start();

$newsManager = new NewsManager();
$allNews = $newsManager->getPublishedNews(100);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tin tức - Cửa Hàng Điện Thoại</title>
    <base href="/lequocanh/">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/lequocanh/public_files/mycss.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-mobile-alt me-2"></i>Cửa Hàng Điện Thoại
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Trang chủ</a></li>
                    <li class="nav-item"><a class="nav-link" href="all_news.php">Tin tức</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
                <li class="breadcrumb-item active">Tin tức</li>
            </ol>
        </nav>
        
        <h1 class="mb-4">
            <i class="fas fa-newspaper text-primary"></i> Tin tức
        </h1>
        
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($allNews as $news): ?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <?php if ($news['featured_image']): ?>
                    <img src="/lequocanh/administrator/elements_LQA/madmin/displayImage.php?type=news&id=<?php echo $news['id']; ?>" 
                         class="card-img-top" 
                         alt="<?php echo htmlspecialchars($news['title']); ?>"
                         style="height: 200px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($news['title']); ?></h5>
                        <p class="card-text text-muted">
                            <?php 
                            $content = strip_tags($news['content']);
                            echo htmlspecialchars(mb_substr($content, 0, 150)) . '...'; 
                            ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">
                                <i class="far fa-user"></i> <?php echo htmlspecialchars($news['author']); ?>
                            </small>
                            <small class="text-muted">
                                <i class="far fa-clock"></i> 
                                <?php echo date('d/m/Y', strtotime($news['published_date'])); ?>
                            </small>
                        </div>
                        <a href="news_detail.php?id=<?php echo $news['id']; ?>" class="btn btn-primary btn-sm">
                            Đọc thêm <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($allNews)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Chưa có tin tức nào được xuất bản.
        </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại trang chủ
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Cửa Hàng Điện Thoại</h5>
                    <p class="text-muted small">Cửa hàng điện thoại uy tín hàng đầu Việt Nam.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted small">© 2026 Cửa Hàng Điện Thoại. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
