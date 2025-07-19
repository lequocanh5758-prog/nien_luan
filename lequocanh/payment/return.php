<?php

/**
 * Trang người dùng được redirect về sau khi thanh toán
 * Hiển thị kết quả thanh toán
 */

require_once 'MoMoPayment.php';

// Lấy thông tin từ URL parameters
$partnerCode = $_GET['partnerCode'] ?? '';
$orderId = $_GET['orderId'] ?? '';
$requestId = $_GET['requestId'] ?? '';
$amount = $_GET['amount'] ?? '';
$orderInfo = $_GET['orderInfo'] ?? '';
$orderType = $_GET['orderType'] ?? '';
$transId = $_GET['transId'] ?? '';
$resultCode = $_GET['resultCode'] ?? '';
$message = $_GET['message'] ?? '';
$payType = $_GET['payType'] ?? '';
$responseTime = $_GET['responseTime'] ?? '';
$extraData = $_GET['extraData'] ?? '';
$signature = $_GET['signature'] ?? '';

// Log thông tin return
error_log('MoMo Return: ' . json_encode($_GET));

// Verify signature
$momoPayment = new MoMoPayment();
$verifyResult = $momoPayment->verifyCallback($_GET);

// Lấy thông tin giao dịch từ database
$transaction = null;
if ($orderId) {
    $transaction = $momoPayment->getTransaction($orderId);
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết Quả Thanh Toán</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .result-card {
            max-width: 600px;
            margin: 0 auto;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            background: white;
            overflow: hidden;
        }
        .result-header {
            padding: 30px;
            text-align: center;
            color: white;
        }
        .result-header.success {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        .result-header.failed {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
        }
        .result-header.pending {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
        }
        .result-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        .result-body {
            padding: 30px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #666;
        }
        .info-value {
            color: #333;
            text-align: right;
        }
        .btn-group-custom {
            gap: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="result-card">
        <?php if ($resultCode == 0): ?>
            <!-- Thanh toán thành công -->
            <div class="result-header success">
                <div class="result-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>Thanh Toán Thành Công!</h2>
                <p class="mb-0">Giao dịch của bạn đã được xử lý thành công</p>
            </div>
        <?php elseif ($resultCode == 1006): ?>
            <!-- Người dùng hủy thanh toán -->
            <div class="result-header failed">
                <div class="result-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h2>Thanh Toán Bị Hủy</h2>
                <p class="mb-0">Bạn đã hủy giao dịch thanh toán</p>
            </div>
        <?php else: ?>
            <!-- Thanh toán thất bại -->
            <div class="result-header failed">
                <div class="result-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h2>Thanh Toán Thất Bại</h2>
                <p class="mb-0"><?= htmlspecialchars($message) ?></p>
            </div>
        <?php endif; ?>
        
        <div class="result-body">
            <h5 class="mb-3">Thông Tin Giao Dịch</h5>
            
            <div class="info-row">
                <span class="info-label">Mã đơn hàng:</span>
                <span class="info-value"><strong><?= htmlspecialchars($orderId) ?></strong></span>
            </div>
            
            <?php if ($transId): ?>
            <div class="info-row">
                <span class="info-label">Mã giao dịch MoMo:</span>
                <span class="info-value"><?= htmlspecialchars($transId) ?></span>
            </div>
            <?php endif; ?>
            
            <div class="info-row">
                <span class="info-label">Số tiền:</span>
                <span class="info-value"><strong><?= number_format($amount) ?> VND</strong></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Thông tin đơn hàng:</span>
                <span class="info-value"><?= htmlspecialchars($orderInfo) ?></span>
            </div>
            
            <?php if ($payType): ?>
            <div class="info-row">
                <span class="info-label">Phương thức thanh toán:</span>
                <span class="info-value"><?= htmlspecialchars($payType) ?></span>
            </div>
            <?php endif; ?>
            
            <div class="info-row">
                <span class="info-label">Thời gian:</span>
                <span class="info-value"><?= $responseTime ? date('d/m/Y H:i:s', $responseTime/1000) : date('d/m/Y H:i:s') ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Trạng thái:</span>
                <span class="info-value">
                    <?php if ($resultCode == 0): ?>
                        <span class="badge bg-success">Thành công</span>
                    <?php elseif ($resultCode == 1006): ?>
                        <span class="badge bg-warning">Đã hủy</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Thất bại</span>
                    <?php endif; ?>
                </span>
            </div>
            
            <?php if (!$verifyResult['success']): ?>
            <div class="alert alert-warning mt-3">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Cảnh báo:</strong> Không thể xác thực chữ ký từ MoMo. Vui lòng liên hệ hỗ trợ.
            </div>
            <?php endif; ?>
            
            <hr class="my-4">
            
            <div class="d-flex justify-content-center btn-group-custom">
                <?php if ($resultCode == 0): ?>
                    <a href="demo.php" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i>Thanh toán mới
                    </a>
                <?php else: ?>
                    <a href="demo.php" class="btn btn-primary">
                        <i class="fas fa-redo me-2"></i>Thử lại
                    </a>
                <?php endif; ?>
                
                <a href="transactions.php" class="btn btn-outline-secondary">
                    <i class="fas fa-history me-2"></i>Lịch sử giao dịch
                </a>
            </div>
            
            <?php if ($resultCode == 0): ?>
            <div class="text-center mt-4">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Lưu ý:</strong> Đây là môi trường test. Không có tiền thật được giao dịch.
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($resultCode == 0): ?>
<script>
// Hiệu ứng confetti cho thanh toán thành công
function createConfetti() {
    const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57'];
    for (let i = 0; i < 50; i++) {
        setTimeout(() => {
            const confetti = document.createElement('div');
            confetti.style.position = 'fixed';
            confetti.style.left = Math.random() * 100 + 'vw';
            confetti.style.top = '-10px';
            confetti.style.width = '10px';
            confetti.style.height = '10px';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.borderRadius = '50%';
            confetti.style.pointerEvents = 'none';
            confetti.style.zIndex = '9999';
            confetti.style.animation = 'fall 3s linear forwards';
            
            document.body.appendChild(confetti);
            
            setTimeout(() => {
                confetti.remove();
            }, 3000);
        }, i * 100);
    }
}

// CSS animation cho confetti
const style = document.createElement('style');
style.textContent = `
    @keyframes fall {
        to {
            transform: translateY(100vh) rotate(360deg);
        }
    }
`;
document.head.appendChild(style);

// Chạy confetti khi trang load
window.addEventListener('load', createConfetti);
</script>
<?php endif; ?>

</body>
</html>
