<?php
/**
 * Admin - Quản lý OAuth Settings
 */

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';
require_once __DIR__ . '/../../../app/autoload.php';

SessionManager::start();

if (!isset($_SESSION['ADMIN'])) {
    header('Location: ../../userLogin.php');
    exit();
}

require_once __DIR__ . '/../mod/database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

use App\Services\OAuthService;

$oauth = OAuthService::fromConfig();

// Lấy danh sách user đăng nhập bằng OAuth
$sql = "SELECT username, hoten, email, google_id, facebook_id, auth_provider, avatar_url
        FROM users 
        WHERE auth_provider != 'local' OR google_id IS NOT NULL OR facebook_id IS NOT NULL
        ORDER BY username
        LIMIT 50";
$stmt = $conn->prepare($sql);
$stmt->execute();
$oauthUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê
$stats = [
    'google' => 0,
    'facebook' => 0,
    'local' => 0,
];
foreach ($oauthUsers as $user) {
    if (!empty($user['google_id'])) $stats['google']++;
    if (!empty($user['facebook_id'])) $stats['facebook']++;
    if ($user['auth_provider'] === 'local') $stats['local']++;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý OAuth</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f1f3f5; }
        .container { max-width: 1200px; margin: 20px auto; }
        .card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 8px rgba(0,0,0,0.06); }
        .oauth-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-key me-2"></i>Quản lý OAuth</h2>
            <a href="../index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Quay lại
            </a>
        </div>
        
        <!-- OAuth Status -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="d-flex align-items-center mb-3">
                        <div class="oauth-icon me-3" style="background: #4285F4;">
                            <i class="fab fa-google text-white"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Google OAuth</h5>
                            <small class="text-muted">Đăng nhập bằng Google</small>
                        </div>
                        <span class="badge bg-<?= $oauth->isGoogleEnabled() ? 'success' : 'danger' ?> ms-auto">
                            <?= $oauth->isGoogleEnabled() ? 'Bật' : 'Tắt' ?>
                        </span>
                    </div>
                    <div class="mb-2">
                        <strong>Users:</strong> <?= $stats['google'] ?> người dùng
                    </div>
                    <div class="mb-2">
                        <strong>Client ID:</strong> 
                        <code><?= !empty($_ENV['GOOGLE_CLIENT_ID'] ?? '') ? substr($_ENV['GOOGLE_CLIENT_ID'], 0, 20) . '...' : 'Chưa cấu hình' ?></code>
                    </div>
                    <div>
                        <strong>Redirect URI:</strong> 
                        <code><?= $_ENV['GOOGLE_REDIRECT_URI'] ?? '/auth/google/callback' ?></code>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="d-flex align-items-center mb-3">
                        <div class="oauth-icon me-3" style="background: #1877F2;">
                            <i class="fab fa-facebook text-white"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Facebook OAuth</h5>
                            <small class="text-muted">Đăng nhập bằng Facebook</small>
                        </div>
                        <span class="badge bg-<?= $oauth->isFacebookEnabled() ? 'success' : 'danger' ?> ms-auto">
                            <?= $oauth->isFacebookEnabled() ? 'Bật' : 'Tắt' ?>
                        </span>
                    </div>
                    <div class="mb-2">
                        <strong>Users:</strong> <?= $stats['facebook'] ?> người dùng
                    </div>
                    <div class="mb-2">
                        <strong>App ID:</strong> 
                        <code><?= !empty($_ENV['FACEBOOK_CLIENT_ID'] ?? '') ? substr($_ENV['FACEBOOK_CLIENT_ID'], 0, 20) . '...' : 'Chưa cấu hình' ?></code>
                    </div>
                    <div>
                        <strong>Redirect URI:</strong> 
                        <code><?= $_ENV['FACEBOOK_REDIRECT_URI'] ?? '/auth/facebook/callback' ?></code>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- OAuth Users -->
        <div class="card">
            <h5 class="mb-3"><i class="fas fa-users me-2"></i>Người dùng đăng nhập OAuth</h5>
            
            <?php if (empty($oauthUsers)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-user-slash fa-3x mb-3"></i>
                    <p>Chưa có người dùng nào đăng nhập bằng OAuth</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Avatar</th>
                                <th>Username</th>
                                <th>Họ tên</th>
                                <th>Email</th>
                                <th>Provider</th>
                                <th>Google ID</th>
                                <th>Facebook ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($oauthUsers as $user): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($user['avatar_url'])): ?>
                                        <img src="<?= htmlspecialchars($user['avatar_url']) ?>" 
                                             alt="Avatar" class="rounded-circle" 
                                             style="width: 40px; height: 40px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 40px; height: 40px;">
                                            <?= strtoupper(substr($user['hoten'] ?? $user['username'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                                <td><?= htmlspecialchars($user['hoten'] ?? '') ?></td>
                                <td><?= htmlspecialchars($user['email'] ?? '') ?></td>
                                <td>
                                    <?php
                                    $providerBadge = match($user['auth_provider'] ?? 'local') {
                                        'google' => '<span class="badge bg-danger"><i class="fab fa-google"></i> Google</span>',
                                        'facebook' => '<span class="badge bg-primary"><i class="fab fa-facebook"></i> Facebook</span>',
                                        default => '<span class="badge bg-secondary"><i class="fas fa-user"></i> Local</span>',
                                    };
                                    echo $providerBadge;
                                    ?>
                                </td>
                                <td><code><?= $user['google_id'] ?? '-' ?></code></td>
                                <td><code><?= $user['facebook_id'] ?? '-' ?></code></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>