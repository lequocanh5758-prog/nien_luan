<?php
/**
 * Admin Dashboard - Thống kê tổng quan
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

// Thống kê tổng quan
$stats = [];

// Tổng đơn hàng
$stmt = $conn->query("SELECT COUNT(*) as total FROM don_hang");
$stats['total_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Đơn hàng hôm nay
$stmt = $conn->query("SELECT COUNT(*) as today FROM don_hang WHERE DATE(ngay_tao) = CURDATE()");
$stats['today_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['today'];

// Doanh thu tháng này
$stmt = $conn->query("SELECT COALESCE(SUM(tong_tien), 0) as revenue FROM don_hang WHERE trang_thai = 'completed' AND MONTH(ngay_tao) = MONTH(CURDATE()) AND YEAR(ngay_tao) = YEAR(CURDATE())");
$stats['month_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];

// Doanh thu hôm nay
$stmt = $conn->query("SELECT COALESCE(SUM(tong_tien), 0) as revenue FROM don_hang WHERE trang_thai = 'completed' AND DATE(ngay_tao) = CURDATE()");
$stats['today_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];

// Đơn chờ xử lý
$stmt = $conn->query("SELECT COUNT(*) as pending FROM don_hang WHERE trang_thai = 'pending'");
$stats['pending_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];

// Tổng sản phẩm
$stmt = $conn->query("SELECT COUNT(*) as total FROM hanghoa");
$stats['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Tổng khách hàng
$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$stats['total_customers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Đơn hàng theo trạng thái
$stmt = $conn->query("SELECT trang_thai, COUNT(*) as count FROM don_hang GROUP BY trang_thai");
$orderStatuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Doanh thu 7 ngày gần đây
$stmt = $conn->query("SELECT DATE(ngay_tao) as date, COALESCE(SUM(tong_tien), 0) as revenue 
                       FROM don_hang 
                       WHERE trang_thai = 'completed' AND ngay_tao >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                       GROUP BY DATE(ngay_tao) 
                       ORDER BY date ASC");
$weeklyRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5 đơn hàng gần đây
$stmt = $conn->query("SELECT id, ma_don_hang_text, ma_nguoi_dung, tong_tien, trang_thai, ngay_tao 
                       FROM don_hang ORDER BY ngay_tao DESC LIMIT 5");
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5 sản phẩm bán chạy
$stmt = $conn->query("SELECT h.idhanghoa, h.tenhanghoa, SUM(ct.so_luong) as total_sold
                       FROM chi_tiet_don_hang ct
                       JOIN hanghoa h ON ct.ma_san_pham = h.idhanghoa
                       JOIN don_hang dh ON ct.ma_don_hang = dh.id
                       WHERE dh.trang_thai = 'completed'
                       GROUP BY h.idhanghoa, h.tenhanghoa
                       ORDER BY total_sold DESC LIMIT 5");
$topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #f1f3f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .dashboard-container { max-width: 1400px; margin: 20px auto; padding: 0 20px; }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 8px rgba(0,0,0,0.06);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .stat-card .icon {
            width: 50px; height: 50px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
        }
        .stat-card .stat-value { font-size: 28px; font-weight: 700; margin: 10px 0 5px; }
        .stat-card .stat-label { color: #6c757d; font-size: 14px; }
        
        .bg-gradient-blue { background: linear-gradient(135deg, #3498db, #2980b9); }
        .bg-gradient-green { background: linear-gradient(135deg, #27ae60, #219653); }
        .bg-gradient-orange { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .bg-gradient-purple { background: linear-gradient(135deg, #9b59b6, #8e44ad); }
        .bg-gradient-red { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .bg-gradient-teal { background: linear-gradient(135deg, #1abc9c, #16a085); }
        
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 8px rgba(0,0,0,0.06);
            margin-bottom: 20px;
        }
        .chart-container h5 { font-weight: 600; margin-bottom: 15px; }
        
        .table-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 8px rgba(0,0,0,0.06);
        }
        .table-container h5 { font-weight: 600; margin-bottom: 15px; }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-weight: 500; }
        
        .quick-action {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 15px;
            background: #f8f9fa;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            transition: all 0.2s;
        }
        .quick-action:hover { background: #e9ecef; color: #333; transform: translateX(5px); }
        .quick-action i { font-size: 18px; width: 24px; text-align: center; }
        
        @media (max-width: 768px) {
            .stat-card { margin-bottom: 15px; }
            .stat-card .stat-value { font-size: 22px; }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h2>
                <p class="text-muted mb-0">Xin chào, Quản trị viên! Hôm nay là <?= date('d/m/Y') ?></p>
            </div>
            <div>
                <a href="../index.php" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left me-1"></i>Quay lại
                </a>
                <a href="mgiohang/order_management_with_export.php" class="btn btn-primary">
                    <i class="fas fa-box me-1"></i>Quản lý đơn hàng
                </a>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="stat-label">Tổng đơn hàng</div>
                            <div class="stat-value"><?= number_format($stats['total_orders']) ?></div>
                            <div class="text-muted small">Hôm nay: <?= $stats['today_orders'] ?></div>
                        </div>
                        <div class="icon bg-gradient-blue text-white">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="stat-label">Doanh thu tháng</div>
                            <div class="stat-value text-success"><?= number_format($stats['month_revenue'], 0, ',', '.') ?>₫</div>
                            <div class="text-muted small">Hôm nay: <?= number_format($stats['today_revenue'], 0, ',', '.') ?>₫</div>
                        </div>
                        <div class="icon bg-gradient-green text-white">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="stat-label">Chờ xử lý</div>
                            <div class="stat-value text-warning"><?= number_format($stats['pending_orders']) ?></div>
                            <div class="text-muted small">Đơn hàng cần duyệt</div>
                        </div>
                        <div class="icon bg-gradient-orange text-white">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="stat-label">Sản phẩm</div>
                            <div class="stat-value"><?= number_format($stats['total_products']) ?></div>
                            <div class="text-muted small">Khách hàng: <?= number_format($stats['total_customers']) ?></div>
                        </div>
                        <div class="icon bg-gradient-purple text-white">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <!-- Revenue Chart -->
            <div class="col-lg-8">
                <div class="chart-container">
                    <h5><i class="fas fa-chart-line me-2 text-primary"></i>Doanh thu 7 ngày gần đây</h5>
                    <canvas id="revenueChart" height="250"></canvas>
                </div>
            </div>
            
            <!-- Order Status Chart -->
            <div class="col-lg-4">
                <div class="chart-container">
                    <h5><i class="fas fa-chart-pie me-2 text-success"></i>Trạng thái đơn hàng</h5>
                    <canvas id="statusChart" height="250"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Tables Row -->
        <div class="row g-4 mb-4">
            <!-- Recent Orders -->
            <div class="col-lg-6">
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="fas fa-clock me-2 text-warning"></i>Đơn hàng gần đây</h5>
                        <a href="mgiohang/order_management_with_export.php" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Khách hàng</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($order['ma_don_hang_text']) ?></strong></td>
                                    <td><?= htmlspecialchars($order['ma_nguoi_dung']) ?></td>
                                    <td class="text-danger fw-bold"><?= number_format($order['tong_tien'], 0, ',', '.') ?>₫</td>
                                    <td>
                                        <?php
                                        $statusClass = match($order['trang_thai']) {
                                            'pending' => 'bg-warning text-dark',
                                            'approved' => 'bg-info',
                                            'delivered' => 'bg-primary',
                                            'completed' => 'bg-success',
                                            'cancelled' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        $statusText = match($order['trang_thai']) {
                                            'pending' => 'Chờ xử lý',
                                            'approved' => 'Đã duyệt',
                                            'delivered' => 'Đang giao',
                                            'completed' => 'Hoàn tất',
                                            'cancelled' => 'Đã hủy',
                                            default => $order['trang_thai']
                                        };
                                        ?>
                                        <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Top Products -->
            <div class="col-lg-6">
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="fas fa-trophy me-2 text-danger"></i>Sản phẩm bán chạy</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Sản phẩm</th>
                                    <th class="text-end">Đã bán</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProducts as $index => $product): ?>
                                <tr>
                                    <td><span class="badge bg-primary"><?= $index + 1 ?></span></td>
                                    <td><?= htmlspecialchars($product['tenhanghoa']) ?></td>
                                    <td class="text-end fw-bold"><?= number_format($product['total_sold']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="table-container mb-4">
            <h5><i class="fas fa-bolt me-2 text-warning"></i>Thao tác nhanh</h5>
            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <a href="mgiohang/order_management_with_export.php" class="quick-action">
                        <i class="fas fa-box text-primary"></i>
                        <span>Quản lý đơn hàng</span>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="mhanghoa/hanghoaView.php" class="quick-action">
                        <i class="fas fa-boxes text-success"></i>
                        <span>Quản lý sản phẩm</span>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="mUser/userView.php" class="quick-action">
                        <i class="fas fa-users text-info"></i>
                        <span>Quản lý người dùng</span>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="mBanners/banners.php" class="quick-action">
                        <i class="fas fa-images text-warning"></i>
                        <span>Quản lý banner</span>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="mLoaihang/loaihangView.php" class="quick-action">
                        <i class="fas fa-tags text-danger"></i>
                        <span>Quản lý danh mục</span>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="page.php?p=cauhinhthanhtoan" class="quick-action">
                        <i class="fas fa-cog text-secondary"></i>
                        <span>Cấu hình thanh toán</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Revenue Chart
        const revenueData = <?= json_encode($weeklyRevenue) ?>;
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: revenueData.map(d => {
                    const date = new Date(d.date);
                    return date.toLocaleDateString('vi-VN', { weekday: 'short', day: 'numeric', month: 'numeric' });
                }),
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: revenueData.map(d => d.revenue),
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 5,
                    pointBackgroundColor: '#3498db'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return new Intl.NumberFormat('vi-VN').format(context.raw) + ' ₫';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('vi-VN', { notation: 'compact' }).format(value);
                            }
                        }
                    }
                }
            }
        });
        
        // Status Chart
        const statusData = <?= json_encode($orderStatuses) ?>;
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusColors = {
            'pending': '#f39c12',
            'approved': '#3498db',
            'delivered': '#9b59b6',
            'completed': '#27ae60',
            'cancelled': '#e74c3c'
        };
        const statusLabels = {
            'pending': 'Chờ xử lý',
            'approved': 'Đã duyệt',
            'delivered': 'Đang giao',
            'completed': 'Hoàn tất',
            'cancelled': 'Đã hủy'
        };
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusData.map(d => statusLabels[d.trang_thai] || d.trang_thai),
                datasets: [{
                    data: statusData.map(d => d.count),
                    backgroundColor: statusData.map(d => statusColors[d.trang_thai] || '#95a5a6'),
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 15, usePointStyle: true }
                    }
                },
                cutout: '65%'
            }
        });
    </script>
</body>
</html>