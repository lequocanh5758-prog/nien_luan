<?php
/**
 * Admin - Quản lý CDN Settings
 */

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';
require_once __DIR__ . '/../../../app/autoload.php';

SessionManager::start();

if (!isset($_SESSION['ADMIN'])) {
    header('Location: ../../userLogin.php');
    exit();
}

use App\Services\CDNService;

$cdn = CDNService::fromConfig();

// Load config
$configPath = __DIR__ . '/../../../config/cdn.php';
$config = file_exists($configPath) ? require $configPath : [];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý CDN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f1f3f5; }
        .container { max-width: 1000px; margin: 20px auto; }
        .card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 8px rgba(0,0,0,0.06); }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-cloud me-2"></i>Quản lý CDN</h2>
            <a href="../index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Quay lại
            </a>
        </div>
        
        <!-- CDN Status -->
        <div class="card mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h5 class="mb-0">Cloudflare CDN</h5>
                    <small class="text-muted">Phân phối nội dung toàn cầu</small>
                </div>
                <span class="badge bg-<?= $cdn->isEnabled() ? 'success' : 'danger' ?> fs-6">
                    <?= $cdn->isEnabled() ? 'Bật' : 'Tắt' ?>
                </span>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <p><strong>CDN URL:</strong></p>
                    <code><?= $cdn->getCdnUrl() ?: 'Chưa cấu hình' ?></code>
                </div>
                <div class="col-md-6">
                    <p><strong>Image Optimization:</strong></p>
                    <span class="badge bg-<?= ($config['image_optimization'] ?? false) ? 'success' : 'secondary' ?>">
                        <?= ($config['image_optimization'] ?? false) ? 'Bật' : 'Tắt' ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Cache Settings -->
        <div class="card mb-4">
            <h5 class="mb-3"><i class="fas fa-database me-2"></i>Cài đặt Cache</h5>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Loại</th>
                            <th>TTL</th>
                            <th>Mô tả</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><i class="fas fa-image text-primary"></i> Images</td>
                            <td><?= ($config['cache_ttl']['images'] ?? 0) / 86400 ?> ngày</td>
                            <td>JPG, PNG, GIF, WebP</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-css3 text-info"></i> CSS</td>
                            <td><?= ($config['cache_ttl']['css'] ?? 0) / 3600 ?> giờ</td>
                            <td>Stylesheets</td>
                        </tr>
                        <tr>
                            <td><i class="fab fa-js text-warning"></i> JavaScript</td>
                            <td><?= ($config['cache_ttl']['js'] ?? 0) / 3600 ?> giờ</td>
                            <td>Scripts</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-code text-success"></i> HTML</td>
                            <td><?= ($config['cache_ttl']['html'] ?? 0) / 3600 ?> giờ</td>
                            <td>Pages</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- CDN Test -->
        <div class="card mb-4">
            <h5 class="mb-3"><i class="fas fa-vial me-2"></i>Test CDN</h5>
            
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Test URL Generation:</strong></p>
                    <div class="bg-light p-3 rounded">
                        <code>
                            <?php
                            $testPath = '/uploads/products/test.jpg';
                            echo "Original: {$testPath}<br>";
                            echo "CDN: " . $cdn->url($testPath) . "<br>";
                            echo "Optimized: " . $cdn->image($testPath, 800, 80);
                            ?>
                        </code>
                    </div>
                </div>
                <div class="col-md-6">
                    <p><strong>Test Image:</strong></p>
                    <img src="<?= $cdn->image('/lequocanh/administrator/elements_LQA/img_LQA/no-image.png', 200, 80) ?>" 
                         alt="Test" class="img-fluid rounded" style="max-height: 100px;">
                </div>
            </div>
        </div>
        
        <!-- Configuration Help -->
        <div class="card">
            <h5 class="mb-3"><i class="fas fa-cog me-2"></i>Hướng dẫn cấu hình</h5>
            
            <div class="accordion" id="configHelp">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#step1">
                            Bước 1: Đăng ký Cloudflare
                        </button>
                    </h2>
                    <div id="step1" class="accordion-collapse collapse show" data-bs-parent="#configHelp">
                        <div class="accordion-body">
                            <ol>
                                <li>Truy cập <a href="https://dash.cloudflare.com" target="_blank">dash.cloudflare.com</a></li>
                                <li>Tạo tài khoản miễn phí</li>
                                <li>Thêm domain của bạn</li>
                                <li>Cập nhật nameservers tại nhà cung cấp domain</li>
                            </ol>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step2">
                            Bước 2: Lấy API Token
                        </button>
                    </h2>
                    <div id="step2" class="accordion-collapse collapse" data-bs-parent="#configHelp">
                        <div class="accordion-body">
                            <ol>
                                <li>Vào <strong>My Profile → API Tokens</strong></li>
                                <li>Tạo token với quyền <strong>Zone: Zone Settings: Edit</strong></li>
                                <li>Copy token và Zone ID</li>
                            </ol>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step3">
                            Bước 3: Cập nhật .env
                        </button>
                    </h2>
                    <div id="step3" class="accordion-collapse collapse" data-bs-parent="#configHelp">
                        <div class="accordion-body">
                            <pre class="bg-dark text-light p-3 rounded">
CDN_ENABLED=true
CLOUDFLARE_ZONE_ID=your_zone_id
CLOUDFLARE_API_TOKEN=your_api_token
CDN_URL=https://cdn.yourdomain.com</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>