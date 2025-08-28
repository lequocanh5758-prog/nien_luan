<?php
// Use SessionManager for safe session handling
require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

// Start session safely
SessionManager::start();
require_once '../../elements_LQA/mod/database.php';
require_once '../../elements_LQA/mod/giohangCls.php';
require_once '../../elements_LQA/mod/mtonkhoCls.php';

$giohang = new GioHang();

// Kiểm tra xem người dùng có thể sử dụng giỏ hàng không
if (!$giohang->canUseCart()) {
    if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
        // Lưu URL hiện tại để chuyển hướng lại sau khi đăng nhập
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ../../userLogin.php');
    } else {
        // Nếu là admin, chuyển hướng về trang quản trị
        header('Location: ../../index.php');
    }
    exit();
}

// Kiểm tra xem có thông tin đơn hàng trong session không
if (!isset($_SESSION['order_details']) || !isset($_SESSION['total_amount']) || !isset($_SESSION['order_code'])) {
    // Nếu không có thông tin đơn hàng, chuyển hướng về trang giỏ hàng
    header('Location: giohangView.php');
    exit();
}

// Kiểm tra xem có mã đơn hàng được gửi từ form không
if (!isset($_POST['order_code']) || $_POST['order_code'] !== $_SESSION['order_code']) {
    // Nếu mã đơn hàng không khớp, chuyển hướng về trang giỏ hàng
    header('Location: giohangView.php');
    exit();
}

// Kiểm tra xem có địa chỉ giao hàng không
if (!isset($_POST['shipping_address']) || empty($_POST['shipping_address'])) {
    // Nếu không có địa chỉ giao hàng, chuyển hướng về trang giỏ hàng
    $_SESSION['checkout_error'] = 'Vui lòng nhập địa chỉ giao hàng';
    header('Location: giohangView.php');
    exit();
}

// Lấy địa chỉ giao hàng
$shippingAddress = trim($_POST['shipping_address']);

// Lấy thông tin đơn hàng từ session
$orderDetails = $_SESSION['order_details'];
$totalAmount = $_SESSION['total_amount'];
$orderCode = $_SESSION['order_code'];

// Khởi tạo các đối tượng
$db = Database::getInstance();
$conn = $db->getConnection();
$giohang = new GioHang();
$tonkho = new MTonKho();

// Kiểm tra xem bảng don_hang đã tồn tại chưa
$checkTableSql = "SHOW TABLES LIKE 'don_hang'";
$checkTableStmt = $conn->prepare($checkTableSql);
$checkTableStmt->execute();

if ($checkTableStmt->rowCount() == 0) {
    // Bảng chưa tồn tại, tạo bảng don_hang
    $createOrdersTableSql = "CREATE TABLE don_hang (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ma_don_hang_text VARCHAR(50) NOT NULL,
        ma_nguoi_dung VARCHAR(50),
        tong_tien DECIMAL(15,2) NOT NULL,
        trang_thai ENUM('pending', 'approved', 'cancelled') NOT NULL DEFAULT 'pending',
        phuong_thuc_thanh_toan VARCHAR(50) NOT NULL DEFAULT 'bank_transfer',
        trang_thai_thanh_toan ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
        ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ngay_cap_nhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->exec($createOrdersTableSql);

    // Tạo bảng chi_tiet_don_hang
    $createOrderItemsTableSql = "CREATE TABLE chi_tiet_don_hang (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ma_don_hang INT NOT NULL,
        ma_san_pham INT NOT NULL,
        so_luong INT NOT NULL,
        gia DECIMAL(15,2) NOT NULL,
        ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ma_don_hang) REFERENCES don_hang(id) ON DELETE CASCADE
    )";
    $conn->exec($createOrderItemsTableSql);
}

// Kiểm tra xem bảng don_hang có cột dia_chi_giao_hang không
$checkShippingAddressColumnSql = "SHOW COLUMNS FROM don_hang LIKE 'dia_chi_giao_hang'";
$checkShippingAddressColumnStmt = $conn->prepare($checkShippingAddressColumnSql);
$checkShippingAddressColumnStmt->execute();
$hasShippingAddressColumn = ($checkShippingAddressColumnStmt->rowCount() > 0);

// Nếu không có cột dia_chi_giao_hang, thêm cột này vào bảng don_hang
if (!$hasShippingAddressColumn) {
    try {
        $addShippingAddressColumnSql = "ALTER TABLE don_hang ADD COLUMN dia_chi_giao_hang TEXT AFTER ma_nguoi_dung";
        $conn->exec($addShippingAddressColumnSql);
        if (class_exists('Logger')) {
            Logger::info("Added shipping address column to orders table");
        }
    } catch (PDOException $e) {
        if (class_exists('Logger')) {
            Logger::error("Failed to add shipping address column", ['error' => $e->getMessage()]);
        }
    }
}

// Kiểm tra xem bảng don_hang có các cột thông báo không
$notificationColumns = [
    'pending_read' => "SHOW COLUMNS FROM don_hang LIKE 'pending_read'",
    'approved_read' => "SHOW COLUMNS FROM don_hang LIKE 'approved_read'",
    'cancelled_read' => "SHOW COLUMNS FROM don_hang LIKE 'cancelled_read'"
];

$missingColumns = [];
foreach ($notificationColumns as $column => $sql) {
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $missingColumns[] = $column;
    }
}

// Nếu thiếu các cột thông báo, thêm vào
if (!empty($missingColumns)) {
    try {
        foreach ($missingColumns as $column) {
            $addColumnSql = "ALTER TABLE don_hang ADD COLUMN $column TINYINT(1) NOT NULL DEFAULT 0";
            $conn->exec($addColumnSql);
            error_log("Đã thêm cột $column vào bảng don_hang");
        }
    } catch (PDOException $e) {
        error_log("Lỗi khi thêm các cột thông báo: " . $e->getMessage());
    }
}

// Bắt đầu transaction
$conn->beginTransaction();

try {
    // Lấy user_id từ session (nếu đã đăng nhập)
    $userId = isset($_SESSION['USER']) ? $_SESSION['USER'] : null;

    // Ghi log để debug - sử dụng Logger
    if (class_exists('Logger')) {
        Logger::info("Creating new order", [
            'order_code' => $orderCode,
            'user_id' => $userId,
            'total_amount' => $totalAmount
        ]);
    }

    // Kiểm tra xem các cột thông báo có tồn tại không
    $hasNotificationColumns = true;
    foreach ($notificationColumns as $column => $sql) {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        if ($stmt->rowCount() == 0) {
            $hasNotificationColumns = false;
            break;
        }
    }

    // Xác định phương thức thanh toán
    $paymentMethod = $_POST['payment_method'] ?? 'bank_transfer';

    // Xác định trạng thái thanh toán dựa trên phương thức
    if ($paymentMethod == 'cod') {
        $paymentStatus = 'pending'; // COD chờ thanh toán khi nhận hàng
    } elseif ($paymentMethod == 'bank_transfer') {
        $paymentStatus = 'pending'; // Chuyển khoản chờ xác nhận
    } else {
        $paymentStatus = 'pending'; // Mặc định chờ thanh toán
    }

    // Thêm đơn hàng vào bảng don_hang với trạng thái thông báo
    if ($hasNotificationColumns) {
        $insertOrderSql = "INSERT INTO don_hang (ma_don_hang_text, ma_nguoi_dung, dia_chi_giao_hang, tong_tien, trang_thai, phuong_thuc_thanh_toan, trang_thai_thanh_toan, pending_read, ngay_tao, ngay_cap_nhat)
                          VALUES (?, ?, ?, ?, 'pending', ?, ?, 0, NOW(), NOW())";
    } else {
        $insertOrderSql = "INSERT INTO don_hang (ma_don_hang_text, ma_nguoi_dung, dia_chi_giao_hang, tong_tien, trang_thai, phuong_thuc_thanh_toan, trang_thai_thanh_toan, ngay_tao, ngay_cap_nhat)
                          VALUES (?, ?, ?, ?, 'pending', ?, ?, NOW(), NOW())";
    }

    $insertOrderStmt = $conn->prepare($insertOrderSql);
    if ($hasNotificationColumns) {
        $insertOrderStmt->execute([$orderCode, $userId, $shippingAddress, $totalAmount, $paymentMethod, $paymentStatus]);
    } else {
        $insertOrderStmt->execute([$orderCode, $userId, $shippingAddress, $totalAmount, $paymentMethod, $paymentStatus]);
    }

    // Lấy ID của đơn hàng vừa thêm
    $orderId = $conn->lastInsertId();

    if (class_exists('Logger')) {
        Logger::info("Order created successfully", ['order_id' => $orderId]);
    }

    // Thêm các sản phẩm vào bảng order_items
    foreach ($orderDetails as $item) {
        try {
            if (class_exists('Logger')) {
                Logger::debug("Adding product to order", [
                    'order_id' => $orderId,
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ]);
            }

            $insertOrderItemSql = "INSERT INTO chi_tiet_don_hang (ma_don_hang, ma_san_pham, so_luong, gia, ngay_tao)
                                  VALUES (?, ?, ?, ?, NOW())";
            $insertOrderItemStmt = $conn->prepare($insertOrderItemSql);
            $insertOrderItemStmt->execute([$orderId, $item['id'], $item['quantity'], $item['price']]);

            error_log("Đã thêm sản phẩm vào đơn hàng thành công");

            // Cập nhật số lượng tồn kho (giảm số lượng)
            $tonkhoInfo = $tonkho->getTonKhoByIdHangHoa($item['id']);
            if ($tonkhoInfo) {
                error_log("Tồn kho hiện tại của sản phẩm ID " . $item['id'] . ": " . $tonkhoInfo->soLuong);

                // Sử dụng hàm updateSoLuong với isIncrement = false để giảm số lượng
                $updateResult = $tonkho->updateSoLuong($item['id'], $item['quantity'], false);

                if ($updateResult) {
                    error_log("Đã cập nhật tồn kho thành công cho sản phẩm ID: " . $item['id'] . ", giảm: " . $item['quantity']);

                    // Kiểm tra lại tồn kho sau khi cập nhật
                    $updatedTonkhoInfo = $tonkho->getTonKhoByIdHangHoa($item['id']);
                    if ($updatedTonkhoInfo) {
                        error_log("Tồn kho sau khi cập nhật của sản phẩm ID " . $item['id'] . ": " . $updatedTonkhoInfo->soLuong);
                    }
                } else {
                    error_log("Cập nhật tồn kho thất bại cho sản phẩm ID: " . $item['id']);
                }
            } else {
                error_log("Không tìm thấy thông tin tồn kho cho sản phẩm ID: " . $item['id'] . ", tạo mới tồn kho");
                // Tạo mới tồn kho với số lượng ban đầu là số lượng đặt hàng (để trừ đi)
                $tonkho->updateSoLuong($item['id'], $item['quantity'], false);
            }

            // Xóa sản phẩm khỏi giỏ hàng
            $giohang->removeFromCart($item['id']);
            error_log("Đã xóa sản phẩm ID: " . $item['id'] . " khỏi giỏ hàng");
        } catch (Exception $e) {
            error_log("Lỗi khi xử lý sản phẩm ID: " . $item['id'] . ": " . $e->getMessage());
            throw $e; // Ném lại ngoại lệ để rollback transaction
        }
    }

    // Commit transaction
    $conn->commit();

    // Xóa thông tin đơn hàng khỏi session
    unset($_SESSION['order_details']);
    unset($_SESSION['total_amount']);
    unset($_SESSION['order_code']);

    // Gửi thông báo cho khách hàng dựa trên phương thức thanh toán
    if ($userId) {
        require_once '../mod/CustomerNotificationManager.php';
        $notificationManager = new CustomerNotificationManager();

        // Debug log
        error_log("Creating notification for user: $userId, payment method: $paymentMethod, order: $orderCode, order_id: $orderId");

        if ($paymentMethod == 'cod') {
            // Thông báo COD - cần duyệt thủ công
            $title = "📦 Đơn hàng COD đã được tạo";
            $message = "Đơn hàng #{$orderCode} đã được tạo thành công. " .
                "Đơn hàng sẽ được xử lý và giao trong thời gian sớm nhất. " .
                "Bạn sẽ thanh toán khi nhận hàng.";
            $result = $notificationManager->createNotification($userId, $title, $message, 'order_created', $orderId);
            error_log("COD notification created: " . ($result ? 'success' : 'failed'));
        } elseif ($paymentMethod == 'bank_transfer') {
            // Thông báo chuyển khoản - chờ thanh toán
            $title = "🏦 Đơn hàng chờ thanh toán";
            $message = "Đơn hàng #{$orderCode} đã được tạo. " .
                "Vui lòng chuyển khoản theo thông tin được cung cấp để hoàn tất đơn hàng.";
            $result = $notificationManager->createNotification($userId, $title, $message, 'payment_pending', $orderId);
            error_log("Bank transfer notification created: " . ($result ? 'success' : 'failed'));
        } else {
            // For any other payment method (momo, etc.)
            $title = "📦 Đơn hàng đã được tạo";
            $message = "Đơn hàng #{$orderCode} đã được tạo thành công với phương thức thanh toán: $paymentMethod";
            $result = $notificationManager->createNotification($userId, $title, $message, 'order_created', $orderId);
            error_log("General notification created: " . ($result ? 'success' : 'failed'));
        }
    } else {
        error_log("No user ID found for notification creation");
    }

    // Lưu thông báo thành công vào session
    $_SESSION['payment_success'] = true;
    $_SESSION['order_id'] = $orderId;

    // Chuyển hướng đến trang xác nhận đơn hàng
    header('Location: order_success.php?order_id=' . $orderId);
    exit();
} catch (PDOException $e) {
    // Rollback transaction nếu có lỗi
    $conn->rollBack();

    // Lưu thông báo lỗi vào session
    $_SESSION['payment_error'] = 'Đã xảy ra lỗi khi xử lý đơn hàng: ' . $e->getMessage();

    // Chuyển hướng về trang giỏ hàng
    header('Location: giohangView.php');
    exit();
}
