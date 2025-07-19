<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán MoMo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #d946ef, #a855f7);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .payment-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 400px;
        }

        .momo-logo {
            width: 80px;
            height: 80px;
            background: #d946ef;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
        }

        .btn-pay {
            background: linear-gradient(135deg, #d946ef, #a855f7);
            border: none;
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            font-weight: bold;
            transition: all 0.3s;
        }

        .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            color: white;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }

        .loading-text {
            animation: pulse 1.5s infinite;
        }
    </style>
</head>

<body>
    <div class="payment-card">
        <div class="momo-logo">M</div>
        <h3 class="mb-3">Thanh toán MoMo</h3>
        <p class="text-muted mb-3">Đơn hàng: <strong><?php echo $_GET['orderId'] ?? ''; ?></strong></p>
        <p class="text-muted mb-4">Số tiền: <strong><?php echo number_format($_GET['amount'] ?? 0); ?> VND</strong></p>

        <div class="mb-4">
            <p class="small text-muted">Xác nhận thanh toán qua ví MoMo</p>
            <p class="small text-muted">Nhấn "Thanh toán" để hoàn tất giao dịch</p>
        </div>

        <button class="btn btn-pay w-100" onclick="processPayment()">
            <i class="fas fa-credit-card me-2"></i>Thanh toán ngay
        </button>

        <button class="btn btn-outline-secondary w-100 mt-2" onclick="cancelPayment()">
            Hủy thanh toán
        </button>
    </div>

    <script>
        function processPayment() {
            // Mô phỏng xử lý thanh toán
            const btn = document.querySelector('.btn-pay');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...';
            btn.disabled = true;

            setTimeout(() => {
                // Hiển thị trang loading "Đang lập hóa đơn"
                showInvoiceLoading();

                setTimeout(() => {
                    // Redirect về momo_return.php với thông tin thành công
                    const params = new URLSearchParams({
                        partnerCode: 'MOMOBKUN20180529',
                        orderId: '<?php echo $_GET['orderId'] ?? ''; ?>',
                        requestId: 'TEST_' + Date.now(),
                        amount: '<?php echo $_GET['amount'] ?? 0; ?>',
                        orderInfo: '<?php echo $_GET['orderInfo'] ?? ''; ?>',
                        transId: 'MOMO_TEST_' + Date.now(),
                        resultCode: '0',
                        message: 'Successful.',
                        extraData: JSON.stringify({
                            order_code: '<?php echo $_GET['orderId'] ?? ''; ?>',
                            source: 'test_localhost'
                        })
                    });

                    window.location.href = 'momo_return.php?' + params.toString();
                }, 2000);
            }, 2000);
        }

        function showInvoiceLoading() {
            document.querySelector('.payment-card').innerHTML = `
                <div class="text-center">
                    <div class="momo-logo">✓</div>
                    <h3 class="text-success mb-3">Thanh toán thành công</h3>
                    <div class="mb-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <h5 class="text-primary loading-text">Đang lập hóa đơn...</h5>
                    <p class="text-muted">Vui lòng đợi trong giây lát</p>
                </div>
            `;
        }

        function cancelPayment() {
            // Redirect về giỏ hàng
            window.location.href = 'giohangView.php';
        }
    </script>
</body>

</html>