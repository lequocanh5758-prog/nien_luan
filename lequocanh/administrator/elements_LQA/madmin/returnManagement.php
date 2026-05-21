<?php
/**
 * Admin - Quản lý đổi trả sản phẩm
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

// Xử lý cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $returnId = intval($_POST['return_id'] ?? 0);
    $action = $_POST['action'];
    
    if ($action === 'update_status') {
        $newStatus = $_POST['status'] ?? '';
        $adminNote = $_POST['admin_note'] ?? '';
        
        $stmt = $conn->prepare("UPDATE doi_tra SET trang_thai = ?, ghi_chu_admin = ?, ngay_cap_nhat = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $adminNote, $returnId]);
        
        $_SESSION['success'] = 'Đã cập nhật trạng thái đổi trả';
    }
    
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE doi_tra SET trang_thai = 'approved', ngay_cap_nhat = NOW() WHERE id = ?");
        $stmt->execute([$returnId]);
        
        $_SESSION['success'] = 'Đã duyệt yêu cầu đổi trả';
    }
    
    if ($action === 'reject') {
        $adminNote = $_POST['admin_note'] ?? 'Không đủ điều kiện';
        $stmt = $conn->prepare("UPDATE doi_tra SET trang_thai = 'rejected', ghi_chu_admin = ?, ngay_cap_nhat = NOW() WHERE id = ?");
        $stmt->execute([$adminNote, $returnId]);
        
        $_SESSION['success'] = 'Đã từ chối yêu cầu đổi trả';
    }
    
    header('Location: returnManagement.php');
    exit;
}

// Lấy danh sách đổi trả
$filter = $_GET['filter'] ?? 'all';
$where = "";
if ($filter !== 'all') {
    $where = "WHERE dt.trang_thai = '" . $conn->quote($filter) . "'";
}

$sql = "SELECT dt.*, dh.ma_don_hang_text, dh.tong_tien, dh.dia_chi_giao_hang,
               u.hoten as ten_khach_hang, u.dienthoai, u.email
        FROM doi_tra dt
        JOIN don_hang dh ON dt.ma_don_hang = dh.id
        LEFT JOIN users u ON dt.ma_nguoi_dung = u.username
        {$where}
        ORDER BY dt.ngay_tao DESC
        LIMIT 50";
$stmt = $conn->prepare($sql);
$stmt->execute();
$returns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê
$stats = [];
foreach (['pending', 'approved', 'rejected', 'completed'] as $status) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM doi_tra WHERE trang_thai = ?");
    $stmt->execute([$status]);
    $stats[$status] = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý đổi trả</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f1f3f5; }
        .container { max-width: 1400px; margin: 20px auto; }
        .stat-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 8px rgba(0,0,0,0.06); }
        .stat-value { font-size: 28px; font-weight: 700; }
        .table-container { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 8px rgba(0,0,0,0.06); }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-undo me-2"></i>Quản lý đổi trả</h2>
            <a href="../index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Quay lại
            </a>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <!-- Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted">Chờ xử lý</div>
                            <div class="stat-value text-warning"><?= $stats['pending'] ?></div>
                        </div>
                        <div class="text-warning"><i class="fas fa-clock fa-2x"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted">Đã duyệt</div>
                            <div class="stat-value text-success"><?= $stats['approved'] ?></div>
                        </div>
                        <div class="text-success"><i class="fas fa-check-circle fa-2x"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted">Đã từ chối</div>
                            <div class="stat-value text-danger"><?= $stats['rejected'] ?></div>
                        </div>
                        <div class="text-danger"><i class="fas fa-times-circle fa-2x"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted">Hoàn tất</div>
                            <div class="stat-value text-info"><?= $stats['completed'] ?></div>
                        </div>
                        <div class="text-info"><i class="fas fa-flag-checkered fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="mb-3">
            <a href="?filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">Tất cả</a>
            <a href="?filter=pending" class="btn btn-sm <?= $filter === 'pending' ? 'btn-warning' : 'btn-outline-warning' ?>">Chờ xử lý</a>
            <a href="?filter=approved" class="btn btn-sm <?= $filter === 'approved' ? 'btn-success' : 'btn-outline-success' ?>">Đã duyệt</a>
            <a href="?filter=rejected" class="btn btn-sm <?= $filter === 'rejected' ? 'btn-danger' : 'btn-outline-danger' ?>">Đã từ chối</a>
            <a href="?filter=completed" class="btn btn-sm <?= $filter === 'completed' ? 'btn-info' : 'btn-outline-info' ?>">Hoàn tất</a>
        </div>
        
        <!-- Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Lý do</th>
                            <th>Phương thức</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($returns as $return): ?>
                        <tr>
                            <td>#<?= $return['id'] ?></td>
                            <td><strong><?= htmlspecialchars($return['ma_don_hang_text']) ?></strong></td>
                            <td>
                                <?= htmlspecialchars($return['ten_khach_hang'] ?? $return['ma_nguoi_dung']) ?>
                                <br><small class="text-muted"><?= $return['dienthoai'] ?? '' ?></small>
                            </td>
                            <td><?= htmlspecialchars(mb_substr($return['ly_do'], 0, 50)) ?>...</td>
                            <td>
                                <?php
                                $methodText = match($return['return_method'] ?? '') {
                                    'pickup' => '<span class="badge bg-primary">Lấy tận nơi</span>',
                                    'drop_off' => '<span class="badge bg-info">Đến bưu cục</span>',
                                    'self_ship' => '<span class="badge bg-secondary">Tự gửi</span>',
                                    default => '<span class="badge bg-light text-dark">Chưa chọn</span>',
                                };
                                echo $methodText;
                                ?>
                            </td>
                            <td>
                                <?php
                                $statusConfig = [
                                    'pending' => ['bg-warning text-dark', 'Chờ xử lý'],
                                    'approved' => ['bg-success', 'Đã duyệt'],
                                    'rejected' => ['bg-danger', 'Đã từ chối'],
                                    'completed' => ['bg-info', 'Hoàn tất'],
                                ];
                                $status = $statusConfig[$return['trang_thai']] ?? ['bg-secondary', $return['trang_thai']];
                                ?>
                                <span class="badge <?= $status[0] ?>"><?= $status[1] ?></span>
                                <?php if ($return['auto_approved']): ?>
                                    <br><small class="text-success"><i class="fas fa-robot"></i> Tự động</small>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($return['ngay_tao'])) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detailModal<?= $return['id'] ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                                
                                <?php if ($return['trang_thai'] === 'pending'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="return_id" value="<?= $return['id'] ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-success" title="Duyệt">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        
                        <!-- Detail Modal -->
                        <div class="modal fade" id="detailModal<?= $return['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Chi tiết đổi trả #<?= $return['id'] ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Thông tin đơn hàng</h6>
                                                <p><strong>Mã đơn:</strong> <?= htmlspecialchars($return['ma_don_hang_text']) ?></p>
                                                <p><strong>Tổng tiền:</strong> <?= number_format($return['tong_tien'], 0, ',', '.') ?>₫</p>
                                                <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($return['dia_chi_giao_hang']) ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Thông tin khách hàng</h6>
                                                <p><strong>Tên:</strong> <?= htmlspecialchars($return['ten_khach_hang'] ?? $return['ma_nguoi_dung']) ?></p>
                                                <p><strong>SĐT:</strong> <?= $return['dienthoai'] ?? 'N/A' ?></p>
                                                <p><strong>Email:</strong> <?= $return['email'] ?? 'N/A' ?></p>
                                            </div>
                                        </div>
                                        
                                        <h6>Thông tin đổi trả</h6>
                                        <p><strong>Lý do:</strong> <?= htmlspecialchars($return['ly_do']) ?></p>
                                        <p><strong>Phương thức:</strong> <?= $return['return_method'] ?? 'Chưa chọn' ?></p>
                                        <p><strong>Trạng thái:</strong> <?= $return['trang_thai'] ?></p>
                                        <p><strong>Ghi chú admin:</strong> <?= $return['ghi_chu_admin'] ?? 'Không có' ?></p>
                                        
                                        <?php if ($return['decision_factors']): ?>
                                        <h6>Quyết định tự động</h6>
                                        <pre><?= $return['decision_factors'] ?></pre>
                                        <?php endif; ?>
                                        
                                        <form method="POST" class="mt-3">
                                            <input type="hidden" name="return_id" value="<?= $return['id'] ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Cập nhật trạng thái</label>
                                                <select name="status" class="form-select">
                                                    <option value="pending" <?= $return['trang_thai'] === 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                                                    <option value="approved" <?= $return['trang_thai'] === 'approved' ? 'selected' : '' ?>>Đã duyệt</option>
                                                    <option value="rejected" <?= $return['trang_thai'] === 'rejected' ? 'selected' : '' ?>>Đã từ chối</option>
                                                    <option value="completed" <?= $return['trang_thai'] === 'completed' ? 'selected' : '' ?>>Hoàn tất</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Ghi chú admin</label>
                                                <textarea name="admin_note" class="form-control" rows="2"><?= $return['ghi_chu_admin'] ?? '' ?></textarea>
                                            </div>
                                            <button type="submit" name="action" value="update_status" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i>Cập nhật
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>