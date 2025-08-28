<?php
// Debug script để kiểm tra chart functionality
// Kiểm tra quyền truy cập
require_once './elements_LQA/mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if (!isset($_SESSION['ADMIN']) && !$phanQuyen->checkAccess('doanhThuView', $username)) {
    echo "<h3 class='text-danger'>Bạn không có quyền truy cập!</h3>";
    exit;
}

// Khởi tạo đối tượng báo cáo
require_once './elements_LQA/mbaocao/baocaoCls.php';
$baoCao = new BaoCao();

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>Debug Chart</title>";
echo "<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>";
echo "</head><body>";

echo "<h2>Debug Thông tin Biểu đồ Doanh thu</h2>";

// Test database connection
try {
    $conn = $baoCao->getConnection();
    if ($conn) {
        echo "<p style='color: green;'>✓ Kết nối database thành công</p>";
        
        // Test simple query
        $stmt = $conn->query("SELECT COUNT(*) as count FROM don_hang");
        $result = $stmt->fetch();
        echo "<p>Tổng số đơn hàng trong DB: " . $result['count'] . "</p>";
        
        $stmt = $conn->query("SELECT COUNT(*) as count FROM don_hang WHERE trang_thai = 'approved'");
        $result = $stmt->fetch();
        echo "<p>Số đơn hàng đã duyệt: " . $result['count'] . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Không thể kết nối database</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Lỗi database: " . $e->getMessage() . "</p>";
}

// Test data retrieval
$endDate = date('Y-m-d');
$startDate = date('Y-m-d', strtotime('-30 days'));

echo "<h3>Test dữ liệu doanh thu (30 ngày gần nhất):</h3>";
echo "<p>Từ ngày: $startDate đến ngày: $endDate</p>";

try {
    $reportData = $baoCao->getDoanhThuTheoNgayTrongKhoang($startDate, $endDate);
    echo "<p>Số ngày có dữ liệu: " . count($reportData) . "</p>";
    
    if (!empty($reportData)) {
        echo "<h4>Dữ liệu mẫu:</h4>";
        echo "<pre>";
        foreach (array_slice($reportData, 0, 5) as $item) {
            print_r($item);
        }
        echo "</pre>";
        
        // Tạo dữ liệu chart
        $chartLabels = [];
        $chartData = [];
        
        foreach ($reportData as $item) {
            $chartLabels[] = date('d/m/Y', strtotime($item['ngay']));
            $chartData[] = floatval($item['doanh_thu']);
        }
        
        echo "<h4>Dữ liệu Chart:</h4>";
        echo "<p>Labels: " . json_encode(array_slice($chartLabels, 0, 5)) . "...</p>";
        echo "<p>Data: " . json_encode(array_slice($chartData, 0, 5)) . "...</p>";
        
        $totalRevenue = array_sum($chartData);
        echo "<p>Tổng doanh thu: " . number_format($totalRevenue, 0, ',', '.') . " đ</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Không có dữ liệu doanh thu trong khoảng thời gian này</p>";
        
        // Tạo dữ liệu giả để test chart
        echo "<p>Tạo dữ liệu giả để test chart...</p>";
        $chartLabels = [];
        $chartData = [];
        
        for ($i = 0; $i < 7; $i++) {
            $date = date('d/m/Y', strtotime("-$i days"));
            $chartLabels[] = $date;
            $chartData[] = rand(100000, 1000000);
        }
        
        $chartLabels = array_reverse($chartLabels);
        $chartData = array_reverse($chartData);
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Lỗi khi lấy dữ liệu: " . $e->getMessage() . "</p>";
    
    // Fallback - tạo dữ liệu giả
    echo "<p>Fallback: Tạo dữ liệu giả để test chart...</p>";
    $chartLabels = [];
    $chartData = [];
    
    for ($i = 0; $i < 7; $i++) {
        $date = date('d/m/Y', strtotime("-$i days"));
        $chartLabels[] = $date;
        $chartData[] = rand(100000, 1000000);
    }
    
    $chartLabels = array_reverse($chartLabels);
    $chartData = array_reverse($chartData);
}

?>

<h3>Test Chart Rendering:</h3>
<div style="width: 80%; height: 400px; margin: 20px auto;">
    <canvas id="testChart"></canvas>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Chart.js version:', Chart.version);
    console.log('Chart labels:', <?php echo json_encode($chartLabels ?? []); ?>);
    console.log('Chart data:', <?php echo json_encode($chartData ?? []); ?>);
    
    const ctx = document.getElementById('testChart');
    if (!ctx) {
        console.error('Canvas element not found!');
        return;
    }
    
    const canvas = ctx.getContext('2d');
    if (!canvas) {
        console.error('Cannot get 2D context from canvas!');
        return;
    }
    
    try {
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartLabels ?? []); ?>,
                datasets: [{
                    label: 'Doanh thu (Test)',
                    data: <?php echo json_encode($chartData ?? []); ?>,
                    backgroundColor: 'rgba(0, 123, 255, 0.5)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('vi-VN') + ' đ';
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Doanh thu: ' + context.raw.toLocaleString('vi-VN') + ' đ';
                            }
                        }
                    }
                }
            }
        });
        
        console.log('Chart created successfully:', chart);
    } catch (error) {
        console.error('Error creating chart:', error);
        document.getElementById('testChart').parentElement.innerHTML += 
            '<p style="color: red;">Lỗi tạo biểu đồ: ' + error.message + '</p>';
    }
});
</script>

</body>
</html>
