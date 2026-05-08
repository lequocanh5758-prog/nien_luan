<?php

require_once __DIR__ . '/../mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();

if (isset($_SESSION['ADMIN'])) {
    $username = $_SESSION['ADMIN'];
} elseif (isset($_SESSION['USER'])) {
    $username = $_SESSION['USER'];
} else {
    $username = '';
}

$isDirectAdmin = isset($_SESSION['ADMIN']) || $username === 'admin';

if (!$isDirectAdmin && !$phanQuyen->checkAccess('marketing_content', $username)) {
    echo '<div class="alert alert-danger m-3">Bạn không có quyền truy cập chức năng này. Vui lòng liên hệ Admin để được cấp quyền.</div>';
    echo '<p class="m-3"><a href="../index.php">Quay lại trang chủ</a></p>';
    exit();
}

require_once __DIR__ . '/../mod/BannerManager.php';
require_once __DIR__ . '/../mod/NewsManager.php';
require_once __DIR__ . '/../mod/PromotionManager.php';
require_once __DIR__ . '/../mod/PageManager.php';

$bannerManager = new BannerManager();
$newsManager = new NewsManager();
$promotionManager = new PromotionManager();
$pageManager = new PageManager();

$activeTab = $_GET['tab'] ?? 'banners';

$message = '';
$messageSuccess = false;
if (isset($_SESSION['marketing_message'])) {
    $message = $_SESSION['marketing_message'];
    $messageSuccess = $_SESSION['marketing_success'] ?? false;
    unset($_SESSION['marketing_message']);
    unset($_SESSION['marketing_success']);
}

$banners = $bannerManager->getAllBanners();
$news = $newsManager->getAllNews();
$promotions = $promotionManager->getAllPromotions();
$blogs = $pageManager->getAllBlogs();
$staticPages = $pageManager->getAllStaticPages();
?>

<!-- Marketing Content Styles -->
<style>
    #center_div {
        background: #fff !important;
    }
    
    .marketing-content-wrapper {
        background: #fff;
        padding: 20px;
    }
    
    .marketing-content-wrapper h2 {
        color: #333;
        margin-bottom: 20px;
    }
    
    .nav-tabs {
        border-bottom: 2px solid #dee2e6;
        margin-bottom: 20px;
    }
    
    .nav-tabs .nav-link {
        color: #495057;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        margin-right: 5px;
    }
    
    .nav-tabs .nav-link.active {
        color: #fff;
        background: #0d6efd;
        border-color: #0d6efd;
    }
    
    .tab-content {
        background: #fff;
        padding: 20px;
        border: 1px solid #dee2e6;
        border-top: none;
    }
    
    .form-label {
        font-weight: 600;
        color: #333;
    }
    
    .table {
        background: #fff;
    }
    
    .table thead th {
        background: #f8f9fa;
        color: #333;
        font-weight: 600;
    }
    
    .btn-primary {
        background: #0d6efd;
        border-color: #0d6efd;
    }
    
    .btn-warning {
        background: #ffc107;
        border-color: #ffc107;
        color: #000;
    }
    
    .btn-danger {
        background: #dc3545;
        border-color: #dc3545;
    }
</style>

<div class="marketing-content-wrapper">
        <h2>Quản lý Nội dung Marketing</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageSuccess ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs" id="marketingTab" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $activeTab === 'banners' ? 'active' : ''; ?>"
                    id="banners-tab"
                    data-toggle="tab"
                    href="#banners"
                    role="tab">Banners</a>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'news' ? 'active' : ''; ?>"
                    id="news-tab"
                    data-toggle="tab"
                    data-target="#news"
                    type="button"
                    role="tab">Tin tức</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'promotions' ? 'active' : ''; ?>"
                    id="promotions-tab"
                    data-toggle="tab"
                    data-target="#promotions"
                    type="button"
                    role="tab">Chương trình ưu đãi</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'blogs' ? 'active' : ''; ?>"
                    id="blogs-tab"
                    data-toggle="tab"
                    data-target="#blogs"
                    type="button"
                    role="tab">Blog</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'pages' ? 'active' : ''; ?>"
                    id="pages-tab"
                    data-toggle="tab"
                    data-target="#pages"
                    type="button"
                    role="tab">Trang tĩnh</button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="marketingTabContent">
            <!-- Banners Tab -->
            <div class="tab-pane fade <?php echo $activeTab === 'banners' ? 'show active' : ''; ?>"
                id="banners"
                role="tabpanel">

                <div class="row mt-3">
                    <div class="col-md-6">
                        <h4>Thêm Banner mới</h4>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="_marketing_handler" value="1">
                            <input type="hidden" name="tab" value="banners">
                            <input type="hidden" name="action" value="add_banner">
                            <div class="mb-3">
                                <label for="banner_title" class="form-label">Tiêu đề</label>
                                <input type="text" class="form-control" id="banner_title" name="banner_title" required>
                            </div>
                            <div class="mb-3">
                                <label for="banner_description" class="form-label">Mô tả</label>
                                <textarea class="form-control" id="banner_description" name="banner_description" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="banner_link_url" class="form-label">Liên kết</label>
                                <input type="url" class="form-control" id="banner_link_url" name="banner_link_url">
                            </div>
                            <div class="mb-3">
                                <label for="banner_image" class="form-label">Ảnh Banner</label>
                                <input type="file" class="form-control" id="banner_image" name="banner_image" accept="image/*" required>
                            </div>
                            <div class="mb-3">
                                <label for="banner_position" class="form-label">Vị trí</label>
                                <input type="number" class="form-control" id="banner_position" name="banner_position" value="0">
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="banner_is_active" name="banner_is_active" checked>
                                <label class="form-check-label" for="banner_is_active">Kích hoạt</label>
                            </div>
                            <button type="submit" class="btn btn-primary">Thêm Banner</button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <h4>Danh sách Banner</h4>
                        <?php if (empty($banners)): ?>
                            <p class="text-muted">Chưa có banner nào.</p>
                        <?php else: ?>
                            <div class="mb-2">
                                <button type="button" class="btn btn-danger btn-sm" id="btnDeleteSelectedBanner" onclick="deleteSelectedBanner()" disabled>
                                    <i class="fas fa-trash me-1"></i> Xóa đã chọn (<span id="selectedBannerCount">0</span>)
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th style="width:40px;"><input type="checkbox" id="selectAllBanner" onclick="toggleAllBanner(this)"></th>
                                            <th>ID</th>
                                            <th>Tiêu đề</th>
                                            <th>Trạng thái</th>
                                            <th>Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($banners as $banner): ?>
                                            <tr>
                                                <td><input type="checkbox" class="banner-checkbox" value="<?php echo $banner['id']; ?>" onchange="updateBannerCount()"></td>
                                                <td><?php echo $banner['id']; ?></td>
                                                <td><?php echo htmlspecialchars($banner['title']); ?></td>
                                                <td>
                                                    <?php if ($banner['is_active']): ?>
                                                        <span class="badge bg-success">Kích hoạt</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Tắt</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editBannerModal<?php echo $banner['id']; ?>">Sửa</button>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="_marketing_handler" value="1">
                                                        <input type="hidden" name="tab" value="banners">
                                                        <input type="hidden" name="action" value="delete_banner">
                                                        <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa banner này?')">Xóa</button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <!-- Edit Banner Modal -->
                                            <div class="modal fade" id="editBannerModal<?php echo $banner['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST" enctype="multipart/form-data">
                                                            <input type="hidden" name="_marketing_handler" value="1">
                                                            <input type="hidden" name="tab" value="banners">
                                                            <input type="hidden" name="action" value="edit_banner">
                                                            <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Sửa Banner</h5>
                                                                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Tiêu đề</label>
                                                                    <input type="text" class="form-control" name="banner_title" value="<?php echo htmlspecialchars($banner['title']); ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Mô tả</label>
                                                                    <textarea class="form-control" name="banner_description" rows="2"><?php echo htmlspecialchars($banner['description'] ?? ''); ?></textarea>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Liên kết</label>
                                                                    <input type="url" class="form-control" name="banner_link_url" value="<?php echo htmlspecialchars($banner['link_url'] ?? ''); ?>">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Ảnh Banner (để trống nếu không đổi)</label>
                                                                    <input type="file" class="form-control" name="banner_image" accept="image/*">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Vị trí</label>
                                                                    <input type="number" class="form-control" name="banner_position" value="<?php echo $banner['position']; ?>">
                                                                </div>
                                                                <div class="mb-3 form-check">
                                                                    <input type="checkbox" class="form-check-input" name="banner_is_active" <?php echo $banner['is_active'] ? 'checked' : ''; ?>>
                                                                    <label class="form-check-label">Kích hoạt</label>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                                                                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- News Tab -->
            <div class="tab-pane fade <?php echo $activeTab === 'news' ? 'show active' : ''; ?>"
                id="news"
                role="tabpanel">

                <div class="row mt-3">
                    <div class="col-md-6">
                        <h4>Thêm Tin tức mới</h4>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="_marketing_handler" value="1">
                            <input type="hidden" name="tab" value="news">
                            <input type="hidden" name="action" value="add_news">
                            <div class="mb-3">
                                <label for="news_title" class="form-label">Tiêu đề</label>
                                <input type="text" class="form-control" id="news_title" name="news_title" required>
                            </div>
                            <div class="mb-3">
                                <label for="news_content" class="form-label">Nội dung</label>
                                <textarea class="form-control" id="news_content" name="news_content" rows="6" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="news_author" class="form-label">Tác giả</label>
                                <input type="text" class="form-control" id="news_author" name="news_author" value="Admin">
                            </div>
                            <div class="mb-3">
                                <label for="news_image" class="form-label">Ảnh đại diện</label>
                                <input type="file" class="form-control" id="news_image" name="news_image" accept="image/*">
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="news_is_published" name="news_is_published" checked>
                                <label class="form-check-label" for="news_is_published">Xuất bản</label>
                            </div>
                            <button type="submit" class="btn btn-primary">Thêm Tin tức</button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <h4>Danh sách Tin tức</h4>
                        <?php if (empty($news)): ?>
                            <p class="text-muted">Chưa có tin tức nào.</p>
                        <?php else: ?>
                            <div class="mb-2">
                                <button type="button" class="btn btn-danger btn-sm" id="btnDeleteSelectedNews" onclick="deleteSelectedNews()" disabled>
                                    <i class="fas fa-trash me-1"></i> Xóa đã chọn (<span id="selectedNewsCount">0</span>)
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th style="width:40px;"><input type="checkbox" id="selectAllNews" onclick="toggleAllNews(this)"></th>
                                            <th>ID</th>
                                            <th>Tiêu đề</th>
                                            <th>Tác giả</th>
                                            <th>Trạng thái</th>
                                            <th>Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($news as $newsItem): ?>
                                            <tr>
                                                <td><input type="checkbox" class="news-checkbox" value="<?php echo $newsItem['id']; ?>" onchange="updateNewsCount()"></td>
                                                <td><?php echo $newsItem['id']; ?></td>
                                                <td>
                                                    <a href="../news_detail.php?id=<?php echo $newsItem['id']; ?>" target="_blank">
                                                        <?php echo htmlspecialchars($newsItem['title']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo htmlspecialchars($newsItem['author_id'] ?? 'Admin'); ?></td>
                                                <td>
                                                    <?php if ($newsItem['is_published']): ?>
                                                        <span class="badge bg-success">Đã xuất bản</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Nháp</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editNewsModal<?php echo $newsItem['id']; ?>">Sửa</button>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="_marketing_handler" value="1">
                                                        <input type="hidden" name="tab" value="news">
                                                        <input type="hidden" name="action" value="delete_news">
                                                        <input type="hidden" name="news_id" value="<?php echo $newsItem['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa tin tức này?')">Xóa</button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <!-- Edit News Modal -->
                                            <div class="modal fade" id="editNewsModal<?php echo $newsItem['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <form method="POST" enctype="multipart/form-data">
                                                            <input type="hidden" name="_marketing_handler" value="1">
                                                            <input type="hidden" name="tab" value="news">
                                                            <input type="hidden" name="action" value="edit_news">
                                                            <input type="hidden" name="news_id" value="<?php echo $newsItem['id']; ?>">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Sửa Tin tức</h5>
                                                                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Tiêu đề</label>
                                                                    <input type="text" class="form-control" name="news_title" value="<?php echo htmlspecialchars($newsItem['title']); ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Nội dung</label>
                                                                    <textarea class="form-control" name="news_content" rows="10" required><?php echo htmlspecialchars($newsItem['content']); ?></textarea>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Tác giả</label>
                                                                    <input type="text" class="form-control" name="news_author" value="<?php echo htmlspecialchars($newsItem['author_id'] ?? 'Admin'); ?>">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Ảnh đại diện (để trống nếu không đổi)</label>
                                                                    <input type="file" class="form-control" name="news_image" accept="image/*">
                                                                </div>
                                                                <div class="mb-3 form-check">
                                                                    <input type="checkbox" class="form-check-input" name="news_is_published" <?php echo $newsItem['is_published'] ? 'checked' : ''; ?>>
                                                                    <label class="form-check-label">Xuất bản</label>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                                                                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Promotions Tab -->
            <div class="tab-pane fade <?php echo $activeTab === 'promotions' ? 'show active' : ''; ?>"
                id="promotions"
                role="tabpanel">

                <div class="row mt-3">
                    <div class="col-md-6">
                        <h4>Thêm Chương trình ưu đãi mới</h4>
                        <form method="POST">
                            <input type="hidden" name="_marketing_handler" value="1">
                            <input type="hidden" name="tab" value="promotions">
                            <input type="hidden" name="action" value="add_promotion">
                            <div class="mb-3">
                                <label for="promotion_title" class="form-label">Tiêu đề</label>
                                <input type="text" class="form-control" id="promotion_title" name="promotion_title" required>
                            </div>
                            <div class="mb-3">
                                <label for="promotion_description" class="form-label">Mô tả</label>
                                <textarea class="form-control" id="promotion_description" name="promotion_description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="promotion_discount_percent" class="form-label">Phần trăm giảm giá (%)</label>
                                <input type="number" class="form-control" id="promotion_discount_percent" name="promotion_discount_percent" min="0" max="100" step="0.01" value="0">
                            </div>
                            <div class="mb-3">
                                <label for="promotion_start_date" class="form-label">Ngày bắt đầu</label>
                                <input type="date" class="form-control" id="promotion_start_date" name="promotion_start_date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="promotion_end_date" class="form-label">Ngày kết thúc</label>
                                <input type="date" class="form-control" id="promotion_end_date" name="promotion_end_date" value="<?php echo date('Y-m-d', strtotime('+1 month')); ?>">
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="promotion_is_active" name="promotion_is_active" checked>
                                <label class="form-check-label" for="promotion_is_active">Kích hoạt</label>
                            </div>
                            <button type="submit" class="btn btn-primary">Thêm Ưu đãi</button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <h4>Danh sách Chương trình ưu đãi</h4>
                        <?php if (empty($promotions)): ?>
                            <p class="text-muted">Chưa có chương trình ưu đãi nào.</p>
                        <?php else: ?>
                            <div class="mb-2">
                                <button type="button" class="btn btn-danger btn-sm" id="btnDeleteSelectedPromo" onclick="deleteSelectedPromo()" disabled>
                                    <i class="fas fa-trash me-1"></i> Xóa đã chọn (<span id="selectedPromoCount">0</span>)
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th style="width:40px;"><input type="checkbox" id="selectAllPromo" onclick="toggleAllPromo(this)"></th>
                                            <th>ID</th>
                                            <th>Tiêu đề</th>
                                            <th>Giảm giá</th>
                                            <th>Thời gian</th>
                                            <th>Trạng thái</th>
                                            <th>Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($promotions as $promotion): ?>
                                            <tr>
                                                <td><input type="checkbox" class="promo-checkbox" value="<?php echo $promotion['id']; ?>" onchange="updatePromoCount()"></td>
                                                <td><?php echo $promotion['id']; ?></td>
                                                <td><?php echo htmlspecialchars($promotion['title']); ?></td>
                                                <td><?php echo $promotion['discount_percent']; ?>%</td>
                                                <td>
                                                    <?php echo date('d/m/Y', strtotime($promotion['start_date'])); ?> - 
                                                    <?php echo date('d/m/Y', strtotime($promotion['end_date'])); ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $isActive = $promotion['is_active'] && 
                                                              strtotime($promotion['start_date']) <= time() && 
                                                              strtotime($promotion['end_date']) >= time();
                                                    ?>
                                                    <?php if ($isActive): ?>
                                                        <span class="badge bg-success">Hiệu lực</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Không hiệu lực</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editPromotionModal<?php echo $promotion['id']; ?>">Sửa</button>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="_marketing_handler" value="1">
                                                        <input type="hidden" name="tab" value="promotions">
                                                        <input type="hidden" name="action" value="delete_promotion">
                                                        <input type="hidden" name="promotion_id" value="<?php echo $promotion['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa chương trình ưu đãi này?')">Xóa</button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <!-- Edit Promotion Modal -->
                                            <div class="modal fade" id="editPromotionModal<?php echo $promotion['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <input type="hidden" name="_marketing_handler" value="1">
                                                            <input type="hidden" name="tab" value="promotions">
                                                            <input type="hidden" name="action" value="edit_promotion">
                                                            <input type="hidden" name="promotion_id" value="<?php echo $promotion['id']; ?>">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Sửa Chương trình ưu đãi</h5>
                                                                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Tiêu đề</label>
                                                                    <input type="text" class="form-control" name="promotion_title" value="<?php echo htmlspecialchars($promotion['title']); ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Mô tả</label>
                                                                    <textarea class="form-control" name="promotion_description" rows="3"><?php echo htmlspecialchars($promotion['description'] ?? ''); ?></textarea>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Phần trăm giảm giá (%)</label>
                                                                    <input type="number" class="form-control" name="promotion_discount_percent" min="0" max="100" step="0.01" value="<?php echo $promotion['discount_percent']; ?>">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Ngày bắt đầu</label>
                                                                    <input type="date" class="form-control" name="promotion_start_date" value="<?php echo $promotion['start_date']; ?>">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Ngày kết thúc</label>
                                                                    <input type="date" class="form-control" name="promotion_end_date" value="<?php echo $promotion['end_date']; ?>">
                                                                </div>
                                                                <div class="mb-3 form-check">
                                                                    <input type="checkbox" class="form-check-input" name="promotion_is_active" <?php echo $promotion['is_active'] ? 'checked' : ''; ?>>
                                                                    <label class="form-check-label">Kích hoạt</label>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                                                                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Blogs Tab -->
            <div class="tab-pane fade <?php echo $activeTab === 'blogs' ? 'show active' : ''; ?>"
                id="blogs"
                role="tabpanel">

                <div class="row mt-3">
                    <div class="col-md-6">
                        <h4>Thêm Blog mới</h4>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="_marketing_handler" value="1">
                            <input type="hidden" name="tab" value="blogs">
                            <input type="hidden" name="action" value="add_blog">
                            <div class="mb-3">
                                <label for="blog_title" class="form-label">Tiêu đề</label>
                                <input type="text" class="form-control" id="blog_title" name="blog_title" required>
                            </div>
                            <div class="mb-3">
                                <label for="blog_excerpt" class="form-label">Tóm tắt</label>
                                <textarea class="form-control" id="blog_excerpt" name="blog_excerpt" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="blog_content" class="form-label">Nội dung</label>
                                <textarea class="form-control" id="blog_content" name="blog_content" rows="10"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="blog_thumbnail" class="form-label">Ảnh đại diện</label>
                                <input type="file" class="form-control" id="blog_thumbnail" name="blog_thumbnail" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label for="blog_status" class="form-label">Trạng thái</label>
                                <select class="form-select" id="blog_status" name="blog_status">
                                    <option value="draft">Nháp</option>
                                    <option value="published">Xuất bản</option>
                                    <option value="hidden">Ẩn</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Thêm Blog</button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <h4>Danh sách Blog</h4>
                        <?php if (empty($blogs)): ?>
                            <p class="text-muted">Chưa có blog nào.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Tiêu đề</th>
                                            <th>Trạng thái</th>
                                            <th>Ngày tạo</th>
                                            <th>Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($blogs as $blog): ?>
                                            <tr>
                                                <td><?php echo $blog['id']; ?></td>
                                                <td>
                                                    <a href="../page.php?slug=<?php echo htmlspecialchars($blog['slug']); ?>" target="_blank">
                                                        <?php echo htmlspecialchars($blog['title']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo PageManager::getStatusBadge($blog['status']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($blog['created_at'])); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editBlogModal<?php echo $blog['id']; ?>">Sửa</button>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="_marketing_handler" value="1">
                                                        <input type="hidden" name="tab" value="blogs">
                                                        <input type="hidden" name="action" value="delete_page">
                                                        <input type="hidden" name="page_id" value="<?php echo $blog['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa blog này?')">Xóa</button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <!-- Edit Blog Modal -->
                                            <div class="modal fade" id="editBlogModal<?php echo $blog['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <form method="POST" enctype="multipart/form-data">
                                                            <input type="hidden" name="_marketing_handler" value="1">
                                                            <input type="hidden" name="tab" value="blogs">
                                                            <input type="hidden" name="action" value="edit_blog">
                                                            <input type="hidden" name="page_id" value="<?php echo $blog['id']; ?>">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Sửa Blog</h5>
                                                                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Tiêu đề</label>
                                                                    <input type="text" class="form-control" name="blog_title" value="<?php echo htmlspecialchars($blog['title']); ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Slug</label>
                                                                    <input type="text" class="form-control" name="blog_slug" value="<?php echo htmlspecialchars($blog['slug']); ?>">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Tóm tắt</label>
                                                                    <textarea class="form-control" name="blog_excerpt" rows="2"><?php echo htmlspecialchars($blog['excerpt'] ?? ''); ?></textarea>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Nội dung</label>
                                                                    <textarea class="form-control" name="blog_content" rows="10"><?php echo htmlspecialchars($blog['content'] ?? ''); ?></textarea>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Ảnh đại diện (để trống nếu không đổi)</label>
                                                                    <input type="file" class="form-control" name="blog_thumbnail" accept="image/*">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Trạng thái</label>
                                                                    <select class="form-select" name="blog_status">
                                                                        <option value="draft" <?php echo $blog['status'] === 'draft' ? 'selected' : ''; ?>>Nháp</option>
                                                                        <option value="published" <?php echo $blog['status'] === 'published' ? 'selected' : ''; ?>>Xuất bản</option>
                                                                        <option value="hidden" <?php echo $blog['status'] === 'hidden' ? 'selected' : ''; ?>>Ẩn</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                                                                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Static Pages Tab -->
            <div class="tab-pane fade <?php echo $activeTab === 'pages' ? 'show active' : ''; ?>"
                id="pages"
                role="tabpanel">

                <div class="row mt-3">
                    <div class="col-md-6">
                        <h4>Thêm Trang tĩnh mới</h4>
                        <form method="POST">
                            <input type="hidden" name="_marketing_handler" value="1">
                            <input type="hidden" name="tab" value="pages">
                            <input type="hidden" name="action" value="add_page">
                            <div class="mb-3">
                                <label for="page_type" class="form-label">Loại trang</label>
                                <select class="form-select" id="page_type" name="page_type">
                                    <option value="about">Giới thiệu</option>
                                    <option value="policy">Chính sách</option>
                                    <option value="guide">Hướng dẫn</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="page_title" class="form-label">Tiêu đề</label>
                                <input type="text" class="form-control" id="page_title" name="page_title" required>
                            </div>
                            <div class="mb-3">
                                <label for="page_content" class="form-label">Nội dung</label>
                                <textarea class="form-control" id="page_content" name="page_content" rows="10"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="page_position" class="form-label">Vị trí</label>
                                <input type="number" class="form-control" id="page_position" name="page_position" value="0">
                            </div>
                            <div class="mb-3">
                                <label for="page_status" class="form-label">Trạng thái</label>
                                <select class="form-select" id="page_status" name="page_status">
                                    <option value="draft">Nháp</option>
                                    <option value="published">Xuất bản</option>
                                    <option value="hidden">Ẩn</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Thêm Trang</button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <h4>Danh sách Trang tĩnh</h4>
                        <?php if (empty($staticPages)): ?>
                            <p class="text-muted">Chưa có trang tĩnh nào.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Loại</th>
                                            <th>Tiêu đề</th>
                                            <th>Trạng thái</th>
                                            <th>Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($staticPages as $page): ?>
                                            <tr>
                                                <td><?php echo $page['id']; ?></td>
                                                <td><?php echo PageManager::getTypeLabel($page['type']); ?></td>
                                                <td>
                                                    <a href="../page.php?slug=<?php echo htmlspecialchars($page['slug']); ?>" target="_blank">
                                                        <?php echo htmlspecialchars($page['title']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo PageManager::getStatusBadge($page['status']); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editPageModal<?php echo $page['id']; ?>">Sửa</button>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="_marketing_handler" value="1">
                                                        <input type="hidden" name="tab" value="pages">
                                                        <input type="hidden" name="action" value="delete_page">
                                                        <input type="hidden" name="page_id" value="<?php echo $page['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa trang này?')">Xóa</button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <!-- Edit Page Modal -->
                                            <div class="modal fade" id="editPageModal<?php echo $page['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <input type="hidden" name="_marketing_handler" value="1">
                                                            <input type="hidden" name="tab" value="pages">
                                                            <input type="hidden" name="action" value="edit_page">
                                                            <input type="hidden" name="page_id" value="<?php echo $page['id']; ?>">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Sửa Trang tĩnh</h5>
                                                                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Loại trang</label>
                                                                    <select class="form-select" name="page_type">
                                                                        <option value="about" <?php echo $page['type'] === 'about' ? 'selected' : ''; ?>>Giới thiệu</option>
                                                                        <option value="policy" <?php echo $page['type'] === 'policy' ? 'selected' : ''; ?>>Chính sách</option>
                                                                        <option value="guide" <?php echo $page['type'] === 'guide' ? 'selected' : ''; ?>>Hướng dẫn</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Tiêu đề</label>
                                                                    <input type="text" class="form-control" name="page_title" value="<?php echo htmlspecialchars($page['title']); ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Slug</label>
                                                                    <input type="text" class="form-control" name="page_slug" value="<?php echo htmlspecialchars($page['slug']); ?>">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Nội dung</label>
                                                                    <textarea class="form-control" name="page_content" rows="10"><?php echo htmlspecialchars($page['content'] ?? ''); ?></textarea>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Vị trí</label>
                                                                    <input type="number" class="form-control" name="page_position" value="<?php echo $page['position']; ?>">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Trạng thái</label>
                                                                    <select class="form-select" name="page_status">
                                                                        <option value="draft" <?php echo $page['status'] === 'draft' ? 'selected' : ''; ?>>Nháp</option>
                                                                        <option value="published" <?php echo $page['status'] === 'published' ? 'selected' : ''; ?>>Xuất bản</option>
                                                                        <option value="hidden" <?php echo $page['status'] === 'hidden' ? 'selected' : ''; ?>>Ẩn</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                                                                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
</div>

<!-- Form xóa hàng loạt tin tức -->
<form id="batchDeleteNewsForm" method="POST" style="display:none;">
    <input type="hidden" name="_marketing_handler" value="1">
    <input type="hidden" name="tab" value="news">
    <input type="hidden" name="action" value="batch_delete_news">
    <input type="hidden" name="news_ids" id="batchDeleteNewsIds">
</form>

<script>
function toggleAllNews(checkbox) {
    document.querySelectorAll('.news-checkbox').forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateNewsCount();
}

function updateNewsCount() {
    const checked = document.querySelectorAll('.news-checkbox:checked');
    const count = checked.length;
    document.getElementById('selectedNewsCount').textContent = count;
    document.getElementById('btnDeleteSelectedNews').disabled = count === 0;
}

function deleteSelectedNews() {
    const checked = document.querySelectorAll('.news-checkbox:checked');
    if (checked.length === 0) {
        alert('Vui lòng chọn ít nhất một tin tức để xóa.');
        return;
    }
    if (!confirm('Bạn có chắc chắn muốn xóa ' + checked.length + ' tin tức đã chọn?')) return;
    
    const ids = Array.from(checked).map(cb => cb.value).join(',');
    document.getElementById('batchDeleteNewsIds').value = ids;
    document.getElementById('batchDeleteNewsForm').submit();
}
</script>

<!-- Form xóa hàng loạt promotions -->
<form id="batchDeletePromoForm" method="POST" style="display:none;">
    <input type="hidden" name="_marketing_handler" value="1">
    <input type="hidden" name="tab" value="promotions">
    <input type="hidden" name="action" value="batch_delete_promotions">
    <input type="hidden" name="promo_ids" id="batchDeletePromoIds">
</form>

<script>
function toggleAllPromo(checkbox) {
    document.querySelectorAll('.promo-checkbox').forEach(cb => { cb.checked = checkbox.checked; });
    updatePromoCount();
}
function updatePromoCount() {
    const count = document.querySelectorAll('.promo-checkbox:checked').length;
    document.getElementById('selectedPromoCount').textContent = count;
    document.getElementById('btnDeleteSelectedPromo').disabled = count === 0;
}
function deleteSelectedPromo() {
    const checked = document.querySelectorAll('.promo-checkbox:checked');
    if (checked.length === 0) { alert('Vui lòng chọn ít nhất một chương trình.'); return; }
    if (!confirm('Xóa ' + checked.length + ' chương trình ưu đãi đã chọn?')) return;
    document.getElementById('batchDeletePromoIds').value = Array.from(checked).map(cb => cb.value).join(',');
    document.getElementById('batchDeletePromoForm').submit();
}
</script>

<!-- Form xóa hàng loạt banners -->
<form id="batchDeleteBannerForm" method="POST" style="display:none;">
    <input type="hidden" name="_marketing_handler" value="1">
    <input type="hidden" name="tab" value="banners">
    <input type="hidden" name="action" value="batch_delete_banners">
    <input type="hidden" name="banner_ids" id="batchDeleteBannerIds">
</form>

<script>
function toggleAllBanner(checkbox) {
    document.querySelectorAll('.banner-checkbox').forEach(cb => { cb.checked = checkbox.checked; });
    updateBannerCount();
}
function updateBannerCount() {
    const count = document.querySelectorAll('.banner-checkbox:checked').length;
    document.getElementById('selectedBannerCount').textContent = count;
    document.getElementById('btnDeleteSelectedBanner').disabled = count === 0;
}
function deleteSelectedBanner() {
    const checked = document.querySelectorAll('.banner-checkbox:checked');
    if (checked.length === 0) { alert('Vui lòng chọn ít nhất một banner.'); return; }
    if (!confirm('Xóa ' + checked.length + ' banner đã chọn?')) return;
    document.getElementById('batchDeleteBannerIds').value = Array.from(checked).map(cb => cb.value).join(',');
    document.getElementById('batchDeleteBannerForm').submit();
}
</script>
