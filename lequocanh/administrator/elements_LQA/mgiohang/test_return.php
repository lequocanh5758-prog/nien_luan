<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Return - Hóa đơn thanh toán</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .result-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 800px;
            margin: 0 auto;
        }
        .result-header {
            padding: 30px;
            text-align: center;
            color: white;
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        .result-icon {
            font-size: 4rem;
            margin-bottom: 15px;
        }
        .invoice-section {
            padding: 30px;
        }
        .transaction-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .btn-return {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .btn-return:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="result-card">
            <!-- Header thành công -->
            <div class="result-header">
                <i class="fas fa-check-circle result-icon"></i>
                <h2>🎉 Thanh toán MoMo thành công!</h2>
                <p class="mb-0">Cảm ơn bạn đã mua hàng tại cửa hàng chúng tôi!</p>
            </div>

            <!-- Hóa đơn chi tiết -->
            <div class="invoice-section">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h4><i class="fas fa-receipt text-primary"></i> Hóa đơn thanh toán</h4>
                        <p class="text-muted mb-0">Mã đơn hàng: <strong>ORDER_<?php echo time(); ?></strong></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="text-muted mb-1">Ngày: <?php echo date('d/m/Y H:i:s'); ?></p>
                        <p class="text-muted mb-0">Khách hàng: <strong>Test User</strong></p>
                    </div>
                </div>

                <!-- Thông tin giao dịch -->
                <div class="transaction-info">
                    <h5><i class="fas fa-credit-card text-success"></i> Thông tin giao dịch</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Mã giao dịch MoMo:</strong> MOMO_TEST_<?php echo time(); ?></p>
                            <p><strong>Phương thức:</strong> Ví MoMo</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Thời gian:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
                            <p><strong>Trạng thái:</strong> <span class="text-success">Đã thanh toán</span></p>
                        </div>
                    </div>
                </div>

                <!-- Chi tiết sản phẩm -->
                <h5><i class="fas fa-shopping-cart text-primary"></i> Chi tiết đơn hàng</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th class="text-center">Số lượng</th>
                                <th class="text-end">Đơn giá</th>
                                <th class="text-end">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Nokia C32</strong></td>
                                <td class="text-center">1</td>
                                <td class="text-end">7.999.999 đ</td>
                                <td class="text-end">7.999.999 đ</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr style="background-color: #f8f9fa; font-weight: bold;">
                                <td colspan="3" class="text-end"><strong>Tổng cộng:</strong></td>
                                <td class="text-end"><strong>7.999.999 đ</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Địa chỉ giao hàng -->
                <div class="transaction-info">
                    <h5><i class="fas fa-truck text-info"></i> Thông tin giao hàng</h5>
                    <p><strong>Địa chỉ:</strong> Địa chỉ test giao hàng</p>
                    <p class="text-muted mb-0">Đơn hàng sẽ được giao trong 2-3 ngày làm việc.</p>
                </div>

                <!-- Nút hành động -->
                <div class="text-center border-top pt-4">
                    <a href="../../../index.php" class="btn btn-primary btn-return">
                        <i class="fas fa-shopping-bag me-2"></i>Tiếp tục mua hàng
                    </a>
                    <button onclick="window.print()" class="btn btn-outline-secondary ms-3">
                        <i class="fas fa-print me-2"></i>In hóa đơn
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
