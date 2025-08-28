<?php
// Use SessionManager for safe session handling
require_once './elements_LQA/mod/sessionManager.php';
require_once './elements_LQA/config/logger_config.php';

// Start session safely
SessionManager::start();

// Kiểm tra quyền truy cập - cho phép cả admin và user thông thường
require_once './elements_LQA/mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

// Nếu không có session nào, chuyển hướng về trang đăng nhập
if (empty($username)) {
    header('Location: ./userLogin.php');
    exit();
}

// Kiểm tra quyền truy cập module don_hang
if (!isset($_SESSION['ADMIN']) && !$phanQuyen->checkAccess('don_hang', $username)) {
    echo "<div class='alert alert-danger'>Bạn không có quyền truy cập trang này!</div>";
    exit();
}

// Cấu hình hiển thị lỗi dựa trên môi trường
if (class_exists('Logger')) {
    // Logger đã được cấu hình trong logger_config.php
    Logger::info("Accessing orders management page", ['user' => $username]);
} else {
    // Fallback nếu Logger chưa được load
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

require_once './elements_LQA/mod/database.php';
require_once './elements_LQA/mod/hanghoaCls.php';
require_once './elements_LQA/mod/mtonkhoCls.php';
require_once './elements_LQA/mod/CustomerNotificationManager.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Kiểm tra kết nối cơ sở dữ liệu
if (!$conn) {
    die('<div class="alert alert-danger">Không thể kết nối đến cơ sở dữ liệu. Vui lòng kiểm tra lại cấu hình kết nối.</div>');
}

// Kiểm tra xem cơ sở dữ liệu có hoạt động không
try {
    $testQuery = $conn->query("SELECT 1");
    if (!$testQuery) {
        die('<div class="alert alert-danger">Kết nối cơ sở dữ liệu không hoạt động. Vui lòng kiểm tra lại cấu hình kết nối.</div>');
    }
} catch (PDOException $e) {
    die('<div class="alert alert-danger">Lỗi khi kiểm tra kết nối cơ sở dữ liệu: ' . $e->getMessage() . '</div>');
}

$hanghoa = new hanghoa();
$tonkho = new MTonKho();

// Kiểm tra xem bảng don_hang đã tồn tại chưa
$checkTableSql = "SHOW TABLES LIKE 'don_hang'";
$checkTableStmt = $conn->prepare($checkTableSql);
$checkTableStmt->execute();

// Nếu bảng don_hang chưa tồn tại, tạo bảng
if ($checkTableStmt->rowCount() == 0) {
    try {
        $createOrdersTableSql = "CREATE TABLE don_hang (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ma_don_hang_text VARCHAR(50) NOT NULL,
            ma_nguoi_dung VARCHAR(50),
            dia_chi_giao_hang TEXT,
            tong_tien DECIMAL(15,2) NOT NULL DEFAULT 0,
            trang_thai ENUM('pending', 'approved', 'cancelled') NOT NULL DEFAULT 'pending',
            phuong_thuc_thanh_toan VARCHAR(50) NOT NULL DEFAULT 'bank_transfer',
            trang_thai_thanh_toan ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
            ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ngay_cap_nhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->exec($createOrdersTableSql);
        // error_log("Đã tạo bảng don_hang thành công!");

        // Kiểm tra lại xem bảng đã được tạo thành công chưa
        $checkTableAgainSql = "SHOW TABLES LIKE 'don_hang'";
        $checkTableAgainStmt = $conn->prepare($checkTableAgainSql);
        $checkTableAgainStmt->execute();
        if ($checkTableAgainStmt->rowCount() == 0) {
            echo '<div class="alert alert-danger">Không thể tạo bảng don_hang. Vui lòng kiểm tra quyền của cơ sở dữ liệu.</div>';
        } else {
            echo '<div class="alert alert-success">Đã tạo bảng don_hang thành công!</div>';
        }
    } catch (PDOException $e) {
        // error_log("Lỗi khi tạo bảng don_hang: " . $e->getMessage());
        echo '<div class="alert alert-danger">Lỗi khi tạo bảng don_hang: ' . $e->getMessage() . '</div>';
    }
}

// Kiểm tra xem bảng chi_tiet_don_hang đã tồn tại chưa
$checkOrderItemsTableSql = "SHOW TABLES LIKE 'chi_tiet_don_hang'";
$checkOrderItemsTableStmt = $conn->prepare($checkOrderItemsTableSql);
$checkOrderItemsTableStmt->execute();

// Nếu bảng chi_tiet_don_hang chưa tồn tại, tạo bảng
if ($checkOrderItemsTableStmt->rowCount() == 0) {
    try {
        $createOrderItemsTableSql = "CREATE TABLE chi_tiet_don_hang (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ma_don_hang INT NOT NULL,
            ma_san_pham INT NOT NULL,
            so_luong INT NOT NULL DEFAULT 1,
            gia DECIMAL(15,2) NOT NULL DEFAULT 0,
            ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ma_don_hang) REFERENCES don_hang(id) ON DELETE CASCADE,
            FOREIGN KEY (ma_san_pham) REFERENCES hanghoa(idhanghoa) ON DELETE RESTRICT
        )";
        $conn->exec($createOrderItemsTableSql);
        // error_log("Đã tạo bảng chi_tiet_don_hang thành công!");
    } catch (PDOException $e) {
        // error_log("Lỗi khi tạo bảng chi_tiet_don_hang: " . $e->getMessage());
    }
}

// Kiểm tra lại xem bảng don_hang đã tồn tại chưa (sau khi có thể đã tạo)
$checkTableSql = "SHOW TABLES LIKE 'don_hang'";
$checkTableStmt = $conn->prepare($checkTableSql);
$checkTableStmt->execute();

if ($checkTableStmt->rowCount() == 0) {
    // Bảng don_hang chưa tồn tại, hiển thị thông báo
    $noOrdersTable = true;
} else {
    $noOrdersTable = false;

    // Kiểm tra và thêm cột dia_chi_giao_hang nếu chưa có
    try {
        $checkAddressColumnSql = "SHOW COLUMNS FROM don_hang LIKE 'dia_chi_giao_hang'";
        $checkAddressColumnStmt = $conn->prepare($checkAddressColumnSql);
        $checkAddressColumnStmt->execute();

        if ($checkAddressColumnStmt->rowCount() == 0) {
            // Thêm cột dia_chi_giao_hang
            $addAddressColumnSql = "ALTER TABLE don_hang ADD COLUMN dia_chi_giao_hang TEXT AFTER ma_nguoi_dung";
            $conn->exec($addAddressColumnSql);
            Logger::info("Added shipping address column to orders table");
        }
    } catch (PDOException $e) {
        Logger::error("Failed to add shipping address column", ['error' => $e->getMessage()]);
    }

    // Kiểm tra xem có dữ liệu trong bảng don_hang không
    $countOrdersSql = "SELECT COUNT(*) as count FROM don_hang";
    $countOrdersStmt = $conn->prepare($countOrdersSql);
    $countOrdersStmt->execute();
    $countOrders = $countOrdersStmt->fetch(PDO::FETCH_ASSOC);

    // Nếu không có dữ liệu, thêm dữ liệu mẫu
    if ($countOrders['count'] == 0) {
        try {
            // Tạo mã đơn hàng
            $orderCode = 'ORD' . date('YmdHis');
            $totalAmount = 100000;
            $trang_thai = 'pending';
            $paymentMethod = 'bank_transfer';
            $paymentStatus = 'pending';
            $createdAt = date('Y-m-d H:i:s');

            $insertOrderSql = "INSERT INTO don_hang (ma_don_hang_text, tong_tien, trang_thai, phuong_thuc_thanh_toan, trang_thai_thanh_toan, ngay_tao)
                              VALUES (?, ?, ?, ?, ?, ?)";
            $insertOrderStmt = $conn->prepare($insertOrderSql);

            $insertOrderStmt->execute([$orderCode, $totalAmount, $trang_thai, $paymentMethod, $paymentStatus, $createdAt]);
            $orderId = $conn->lastInsertId();
            // Đã thêm đơn hàng mẫu

            // Kiểm tra xem bảng chi_tiet_don_hang đã tồn tại không
            $checkOrderItemsTableSql = "SHOW TABLES LIKE 'chi_tiet_don_hang'";
            $checkOrderItemsTableStmt = $conn->prepare($checkOrderItemsTableSql);
            $checkOrderItemsTableStmt->execute();

            if ($checkOrderItemsTableStmt->rowCount() > 0) {
                // Lấy một sản phẩm từ bảng hanghoa
                $getProductSql = "SELECT idhanghoa, giathamkhao FROM hanghoa LIMIT 1";
                $getProductStmt = $conn->prepare($getProductSql);
                $getProductStmt->execute();
                $product = $getProductStmt->fetch(PDO::FETCH_ASSOC);

                if ($product) {
                    $insertOrderItemSql = "INSERT INTO chi_tiet_don_hang (ma_don_hang, ma_san_pham, so_luong, gia, ngay_tao)
                                         VALUES (?, ?, ?, ?, ?)";
                    $insertOrderItemStmt = $conn->prepare($insertOrderItemSql);

                    $productId = $product['idhanghoa'];
                    $so_luong = 1;
                    $gia = $product['giathamkhao'];

                    $insertOrderItemStmt->execute([$orderId, $productId, $so_luong, $gia, $createdAt]);
                    // Đã thêm chi tiết đơn hàng mẫu
                } else {
                    // Không tìm thấy sản phẩm nào trong bảng hanghoa để thêm vào đơn hàng
                }
            }
        } catch (PDOException $e) {
            // Lỗi khi thêm đơn hàng mẫu
        }
    }

    // Kiểm tra xem bảng chi_tiet_don_hang có tồn tại không
    $noOrderItemsTable = ($checkOrderItemsTableStmt->rowCount() == 0);

    // Xử lý hành động
    if (isset($_GET['action']) && isset($_GET['id'])) {
        $action = $_GET['action'];
        $orderId = (int)$_GET['id'];

        switch ($action) {
            case 'approve':
                // Kiểm tra xem cột approved_read có tồn tại không
                $checkApprovedReadColumnSql = "SHOW COLUMNS FROM don_hang LIKE 'approved_read'";
                $checkApprovedReadColumnStmt = $conn->prepare($checkApprovedReadColumnSql);
                $checkApprovedReadColumnStmt->execute();
                $hasApprovedReadColumn = ($checkApprovedReadColumnStmt->rowCount() > 0);

                // Cập nhật trạng thái đơn hàng thành 'approved' và đánh dấu là chưa đọc
                if ($hasApprovedReadColumn) {
                    $updateOrderSql = "UPDATE don_hang SET trang_thai = 'approved', approved_read = 0 WHERE id = ?";
                } else {
                    $updateOrderSql = "UPDATE don_hang SET trang_thai = 'approved' WHERE id = ?";
                }

                $updateOrderStmt = $conn->prepare($updateOrderSql);
                $updateOrderStmt->execute([$orderId]);

                // Lấy thông tin đơn hàng để gửi thông báo
                $orderInfoSql = "SELECT ma_nguoi_dung FROM don_hang WHERE id = ?";
                $orderInfoStmt = $conn->prepare($orderInfoSql);
                $orderInfoStmt->execute([$orderId]);
                $orderInfo = $orderInfoStmt->fetch(PDO::FETCH_ASSOC);

                // Gửi thông báo cho khách hàng
                if ($orderInfo) {
                    $notificationManager = new CustomerNotificationManager();
                    $notificationManager->notifyOrderApproved($orderId, $orderInfo['ma_nguoi_dung']);
                }

                // Kiểm tra xem bảng chi_tiet_don_hang có tồn tại không
                if (!$noOrderItemsTable) {
                    try {
                        // Lấy danh sách sản phẩm trong đơn hàng
                        $orderItemsSql = "SELECT ma_san_pham, so_luong FROM chi_tiet_don_hang WHERE ma_don_hang = ?";
                        $orderItemsStmt = $conn->prepare($orderItemsSql);
                        $orderItemsStmt->execute([$orderId]);
                        $orderItems = $orderItemsStmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        // Lỗi khi lấy sản phẩm trong đơn hàng
                        $orderItems = [];
                    }
                } else {
                    $orderItems = [];
                }

                // Không cần cập nhật số lượng tồn kho ở đây vì đã được cập nhật khi tạo đơn hàng
                // Đơn hàng đã được duyệt. Không cần cập nhật số lượng tồn kho vì đã cập nhật khi tạo đơn hàng.

                $_SESSION['order_message'] = 'Đơn hàng #' . $orderId . ' đã được duyệt thành công và đã gửi thông báo cho khách hàng.';
                break;

            case 'cancel':
                // Kiểm tra xem cột cancelled_read có tồn tại không
                $checkCancelledReadColumnSql = "SHOW COLUMNS FROM don_hang LIKE 'cancelled_read'";
                $checkCancelledReadColumnStmt = $conn->prepare($checkCancelledReadColumnSql);
                $checkCancelledReadColumnStmt->execute();
                $hasCancelledReadColumn = ($checkCancelledReadColumnStmt->rowCount() > 0);

                // Cập nhật trạng thái đơn hàng thành 'cancelled' và đánh dấu là chưa đọc
                if ($hasCancelledReadColumn) {
                    $updateOrderSql = "UPDATE don_hang SET trang_thai = 'cancelled', cancelled_read = 0 WHERE id = ?";
                } else {
                    $updateOrderSql = "UPDATE don_hang SET trang_thai = 'cancelled' WHERE id = ?";
                }

                $updateOrderStmt = $conn->prepare($updateOrderSql);
                $updateOrderStmt->execute([$orderId]);

                // Lấy thông tin đơn hàng để gửi thông báo
                $orderInfoSql = "SELECT ma_nguoi_dung FROM don_hang WHERE id = ?";
                $orderInfoStmt = $conn->prepare($orderInfoSql);
                $orderInfoStmt->execute([$orderId]);
                $orderInfo = $orderInfoStmt->fetch(PDO::FETCH_ASSOC);

                // Gửi thông báo cho khách hàng
                if ($orderInfo) {
                    $notificationManager = new CustomerNotificationManager();
                    $notificationManager->notifyOrderCancelled($orderId, $orderInfo['ma_nguoi_dung'], 'Đơn hàng bị hủy bởi admin');
                }

                // Kiểm tra xem bảng chi_tiet_don_hang có tồn tại không
                if (!$noOrderItemsTable) {
                    try {
                        // Lấy danh sách sản phẩm trong đơn hàng
                        $orderItemsSql = "SELECT ma_san_pham, so_luong FROM chi_tiet_don_hang WHERE ma_don_hang = ?";
                        $orderItemsStmt = $conn->prepare($orderItemsSql);
                        $orderItemsStmt->execute([$orderId]);
                        $orderItems = $orderItemsStmt->fetchAll(PDO::FETCH_ASSOC);

                        // Hoàn trả số lượng tồn kho cho từng sản phẩm
                        foreach ($orderItems as $item) {
                            $productId = $item['ma_san_pham'];
                            $so_luong = $item['so_luong'];

                            // Sử dụng hàm updateSoLuong với isIncrement = true để tăng số lượng
                            $tonkho->updateSoLuong($productId, $so_luong, true);

                            // Ghi log
                            error_log("Đã hoàn trả tồn kho cho sản phẩm ID: " . $productId . ", tăng: " . $so_luong);
                        }

                        $_SESSION['order_message'] = 'Đơn hàng #' . $orderId . ' đã được hủy và số lượng tồn kho đã được hoàn trả.';
                    } catch (PDOException $e) {
                        // Lỗi khi hoàn trả tồn kho
                        error_log("Lỗi khi hoàn trả tồn kho: " . $e->getMessage());
                        $_SESSION['order_message'] = 'Đơn hàng #' . $orderId . ' đã được hủy nhưng có lỗi khi hoàn trả tồn kho.';
                    }
                } else {
                    $_SESSION['order_message'] = 'Đơn hàng #' . $orderId . ' đã được hủy.';
                }
                break;

            case 'view':
                // Lấy thông tin chi tiết đơn hàng
                // Nếu là người dùng thông thường, chỉ cho phép xem đơn hàng của họ
                // Nếu là admin, cho phép xem tất cả đơn hàng
                if (isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
                    $orderSql = "SELECT * FROM don_hang WHERE id = ? AND ma_nguoi_dung = ?";
                    $orderStmt = $conn->prepare($orderSql);
                    $orderStmt->execute([$orderId, $_SESSION['USER']]);
                } else {
                    $orderSql = "SELECT * FROM don_hang WHERE id = ?";
                    $orderStmt = $conn->prepare($orderSql);
                    $orderStmt->execute([$orderId]);
                }
                $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

                if ($order) {
                    // Kiểm tra xem bảng chi_tiet_don_hang có tồn tại không
                    if (!$noOrderItemsTable) {
                        try {
                            // Lấy danh sách sản phẩm trong đơn hàng
                            $orderItemsSql = "SELECT oi.*, h.tenhanghoa
                                             FROM chi_tiet_don_hang oi
                                             JOIN hanghoa h ON oi.ma_san_pham = h.idhanghoa
                                             WHERE oi.ma_don_hang = ?";
                            $orderItemsStmt = $conn->prepare($orderItemsSql);
                            $orderItemsStmt->execute([$orderId]);
                            $orderItems = $orderItemsStmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                            // Lỗi khi lấy chi tiết đơn hàng
                            $orderItems = [];
                        }
                    } else {
                        $orderItems = [];
                    }

                    // Nếu người dùng đang xem đơn hàng của họ, đánh dấu là đã đọc
                    if (isset($_SESSION['USER']) && isset($order['ma_nguoi_dung']) && $_SESSION['USER'] === $order['ma_nguoi_dung']) {
                        // Kiểm tra xem các cột đánh dấu đã đọc có tồn tại không
                        $checkColumns = [
                            'pending_read' => "SHOW COLUMNS FROM don_hang LIKE 'pending_read'",
                            'approved_read' => "SHOW COLUMNS FROM don_hang LIKE 'approved_read'",
                            'cancelled_read' => "SHOW COLUMNS FROM don_hang LIKE 'cancelled_read'"
                        ];

                        $hasReadColumns = true;
                        foreach ($checkColumns as $column => $sql) {
                            $stmt = $conn->prepare($sql);
                            $stmt->execute();
                            if ($stmt->rowCount() == 0) {
                                $hasReadColumns = false;
                                break;
                            }
                        }

                        // Nếu các cột đánh dấu đã đọc tồn tại, cập nhật trạng thái đã đọc
                        if ($hasReadColumns) {
                            $trang_thai = $order['trang_thai'];
                            $field = '';

                            switch ($trang_thai) {
                                case 'pending':
                                    $field = 'pending_read';
                                    break;
                                case 'approved':
                                    $field = 'approved_read';
                                    break;
                                case 'cancelled':
                                    $field = 'cancelled_read';
                                    break;
                            }

                            if (!empty($field)) {
                                $updateReadSql = "UPDATE don_hang SET $field = 1 WHERE id = ?";
                                $updateReadStmt = $conn->prepare($updateReadSql);
                                $updateReadStmt->execute([$orderId]);
                            }
                        }
                    }

                    // Hiển thị chi tiết đơn hàng
                    $viewOrderDetail = true;
                } else {
                    $_SESSION['order_message'] = 'Không tìm thấy đơn hàng #' . $orderId . '.';
                    $viewOrderDetail = false;
                }
                break;
        }

        if ($action != 'view') {
            // Sử dụng JavaScript để chuyển hướng thay vì header()
            echo '<script>window.location.href = "./index.php?req=don_hang";</script>';
            exit();
        }
    }

    // Lấy danh sách đơn hàng
    try {
        // Kiểm tra xem cột ma_nguoi_dung có tồn tại trong bảng don_hang không
        $checkUserIdColumnSql = "SHOW COLUMNS FROM don_hang LIKE 'ma_nguoi_dung'";
        $checkUserIdColumnStmt = $conn->prepare($checkUserIdColumnSql);
        $checkUserIdColumnStmt->execute();
        $hasUserIdColumn = ($checkUserIdColumnStmt->rowCount() > 0);

        // Kiểm tra xem bảng user có tồn tại không
        $checkUserTableSql = "SHOW TABLES LIKE 'user'";
        $checkUserTableStmt = $conn->prepare($checkUserTableSql);
        $checkUserTableStmt->execute();
        $hasUserTable = ($checkUserTableStmt->rowCount() > 0);

        // Kiểm tra các cột trong bảng don_hang
        $columnsQuery = "SHOW COLUMNS FROM don_hang";
        $columnsStmt = $conn->prepare($columnsQuery);
        $columnsStmt->execute();
        $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);

        error_log("Các cột trong bảng don_hang: " . implode(", ", $columns));

        // Xây dựng truy vấn dựa trên các cột có sẵn
        $selectColumns = "id, ma_don_hang_text, ma_nguoi_dung";

        // Thêm các cột tùy chọn nếu chúng tồn tại
        if (in_array('dia_chi_giao_hang', $columns)) {
            $selectColumns .= ", dia_chi_giao_hang";
        } elseif (in_array('shipping_address', $columns)) {
            $selectColumns .= ", shipping_address";
        }

        if (in_array('tong_tien', $columns)) {
            $selectColumns .= ", tong_tien";
        } else {
            $selectColumns .= ", 0 as tong_tien";
        }

        if (in_array('trang_thai', $columns)) {
            $selectColumns .= ", trang_thai";
        } else {
            $selectColumns .= ", 'pending' as trang_thai";
        }

        if (in_array('phuong_thuc_thanh_toan', $columns)) {
            $selectColumns .= ", phuong_thuc_thanh_toan";
        } else {
            $selectColumns .= ", 'bank_transfer' as phuong_thuc_thanh_toan";
        }

        if (in_array('trang_thai_thanh_toan', $columns)) {
            $selectColumns .= ", trang_thai_thanh_toan";
        }

        if (in_array('ngay_tao', $columns)) {
            $selectColumns .= ", ngay_tao";
        }

        if (in_array('ngay_cap_nhat', $columns)) {
            $selectColumns .= ", ngay_cap_nhat";
        }

        // Truy vấn an toàn
        // Nếu là người dùng thông thường, chỉ hiển thị đơn hàng của họ
        // Nếu là admin, hiển thị tất cả đơn hàng
        if (isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
            $don_hangSql = "SELECT $selectColumns FROM don_hang WHERE ma_nguoi_dung = ? ORDER BY ngay_tao DESC";
            error_log("SQL query (user): " . $don_hangSql);

            $don_hangStmt = $conn->prepare($don_hangSql);
            $don_hangStmt->execute([$_SESSION['USER']]);
        } else {
            $don_hangSql = "SELECT $selectColumns FROM don_hang ORDER BY ngay_tao DESC";
            error_log("SQL query (admin): " . $don_hangSql);

            $don_hangStmt = $conn->prepare($don_hangSql);
            $don_hangStmt->execute();
        }

        $don_hang = $don_hangStmt->fetchAll(PDO::FETCH_ASSOC);

        // Nếu có cột ma_nguoi_dung và bảng user tồn tại, thực hiện JOIN riêng để lấy thông tin người dùng
        if ($hasUserIdColumn && $hasUserTable && count($don_hang) > 0) {
            foreach ($don_hang as $key => $order) {
                if (!empty($order['ma_nguoi_dung'])) {
                    $userSql = "SELECT hoten FROM user WHERE username = ?";
                    $userStmt = $conn->prepare($userSql);
                    $userStmt->execute([$order['ma_nguoi_dung']]);
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                    if ($user) {
                        $don_hang[$key]['hoten'] = $user['hoten'];
                    }
                }
            }
        }

        // Số lượng đơn hàng: count($don_hang)
    } catch (PDOException $e) {
        // Lỗi khi lấy danh sách đơn hàng
        $don_hang = [];
    }
}
?>

<div class="admin-title">Quản lý đơn hàng</div>
<hr>

<!-- Thêm Bootstrap CSS nếu chưa có -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">



<?php
// Phần hiển thị lỗi PHP và thông tin debug đã được xóa
?>

<?php if (isset($_SESSION['order_message'])): ?>
    <div class="alert alert-success">
        <?php echo $_SESSION['order_message']; ?>
    </div>
    <?php unset($_SESSION['order_message']); ?>
<?php endif; ?>

<?php if ($noOrdersTable): ?>
    <div class="alert alert-warning">
        <p>Chưa có bảng đơn hàng trong cơ sở dữ liệu. Bảng sẽ được tạo tự động khi có đơn hàng đầu tiên.</p>
    </div>
<?php elseif (isset($viewOrderDetail) && $viewOrderDetail): ?>
    <!-- Chi tiết đơn hàng -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Chi tiết đơn hàng #<?php echo $order['id']; ?></h5>
            <a href="./index.php?req=don_hang" class="btn btn-light btn-sm">Quay lại</a>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6>Thông tin đơn hàng</h6>
                    <p><strong>Mã đơn hàng:</strong> <?php echo $order['ma_don_hang_text']; ?></p>
                    <p><strong>Ngày đặt:</strong> <?php echo date('d/m/Y H:i', strtotime($order['ngay_tao'])); ?></p>
                    <p><strong>Trạng thái:</strong>
                        <?php
                        switch ($order['trang_thai']) {
                            case 'pending':
                                echo '<span class="badge badge-warning">Chờ xác nhận</span>';
                                break;
                            case 'approved':
                                echo '<span class="badge badge-success">Đã duyệt</span>';
                                break;
                            case 'cancelled':
                                echo '<span class="badge badge-danger">Đã hủy</span>';
                                break;
                            default:
                                echo '<span class="badge badge-secondary">Không xác định</span>';
                        }
                        ?>
                    </p>
                    <p><strong>Phương thức thanh toán:</strong>
                        <?php
                        $paymentMethod = isset($order['phuong_thuc_thanh_toan']) ? $order['phuong_thuc_thanh_toan'] : 'N/A';
                        switch ($paymentMethod) {
                            case 'cod':
                                echo '<span class="badge badge-info">COD (Thanh toán khi nhận hàng)</span>';
                                break;
                            case 'momo':
                                echo '<span class="badge badge-primary">MoMo Wallet</span>';
                                break;
                            case 'bank_transfer':
                                echo '<span class="badge badge-success">Chuyển khoản ngân hàng</span>';
                                break;
                            default:
                                echo '<span class="badge badge-secondary">' . htmlspecialchars($paymentMethod) . '</span>';
                        }
                        ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <h6>Thông tin khách hàng</h6>
                    <?php if (isset($order['ma_nguoi_dung']) && !empty($order['ma_nguoi_dung'])): ?>
                        <p><strong>Tài khoản:</strong> <?php echo $order['ma_nguoi_dung']; ?></p>
                    <?php else: ?>
                        <p><strong>Khách hàng:</strong> Khách vãng lai</p>
                    <?php endif; ?>

                    <!-- Hiển thị địa chỉ giao hàng -->
                    <?php if (isset($order['shipping_address']) && !empty($order['shipping_address'])): ?>
                        <div class="mt-3">
                            <p><strong>Địa chỉ giao hàng:</strong></p>
                            <div class="p-2 bg-light rounded">
                                <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <h6>Danh sách sản phẩm</h6>
            <table class="table">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Đơn giá</th>
                        <th>Số lượng</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['tenhanghoa']); ?></td>
                            <td><?php echo number_format($item['gia'], 0, ',', '.'); ?> ₫</td>
                            <td><?php echo $item['so_luong']; ?></td>
                            <td><?php echo number_format($item['gia'] * $item['so_luong'], 0, ',', '.'); ?> ₫</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Tổng tiền:</strong></td>
                        <td><strong><?php echo number_format($order['tong_tien'], 0, ',', '.'); ?> ₫</strong></td>
                    </tr>
                </tfoot>
            </table>

            <div class="mt-4">
                <?php if ($order['trang_thai'] == 'pending'): ?>
                    <a href="./index.php?req=don_hang&action=approve&id=<?php echo $order['id']; ?>" class="btn btn-success" onclick="return confirm('Xác nhận duyệt đơn hàng này?');">Duyệt đơn hàng</a>
                    <a href="./index.php?req=don_hang&action=cancel&id=<?php echo $order['id']; ?>" class="btn btn-danger" onclick="return confirm('Xác nhận hủy đơn hàng này? Số lượng tồn kho sẽ được hoàn trả.');">Hủy đơn hàng</a>
                <?php endif; ?>

                <!-- Nút in hóa đơn -->
                <a href="print_invoice.php?id=<?php echo $order['id']; ?>" class="btn btn-primary" target="_blank">
                    <i class="fas fa-print"></i> In hóa đơn
                </a>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Danh sách đơn hàng -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Danh sách đơn hàng</h5>
        </div>
        <div class="card-body">
            <?php if (empty($don_hang)): ?>
                <div class="alert alert-info">
                    <p>Chưa có đơn hàng nào.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Mã đơn hàng</th>
                                <th>Khách hàng</th>
                                <th>Địa chỉ</th>
                                <th>Tổng tiền</th>
                                <th>Phương thức TT</th>
                                <th>Trạng thái</th>
                                <th>Ngày đặt</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($don_hang as $order): ?>
                                <tr>
                                    <td><?php echo $order['id']; ?></td>
                                    <td><?php echo $order['ma_don_hang_text']; ?></td>
                                    <td><?php
                                        if (isset($order['ma_nguoi_dung']) && !empty($order['ma_nguoi_dung'])) {
                                            echo isset($order['hoten']) && !empty($order['hoten']) ? $order['hoten'] : $order['ma_nguoi_dung'];
                                        } else {
                                            echo 'Khách vãng lai';
                                        }
                                        ?></td>
                                    <td>
                                        <?php
                                        $address = '';
                                        if (isset($order['dia_chi_giao_hang']) && !empty($order['dia_chi_giao_hang'])) {
                                            $address = $order['dia_chi_giao_hang'];
                                        } elseif (isset($order['shipping_address']) && !empty($order['shipping_address'])) {
                                            $address = $order['shipping_address'];
                                        }

                                        if (!empty($address)): ?>
                                            <?php
                                            // Hiển thị tối đa 30 ký tự đầu tiên của địa chỉ
                                            $shortAddress = mb_substr(htmlspecialchars($address), 0, 30);
                                            if (mb_strlen($address) > 30) {
                                                $shortAddress .= '...';
                                            }
                                            echo $shortAddress;
                                            ?>
                                        <?php else: ?>
                                            <span class="text-muted">Không có</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($order['tong_tien'], 0, ',', '.'); ?> ₫</td>
                                    <td>
                                        <?php
                                        $paymentMethod = isset($order['phuong_thuc_thanh_toan']) ? $order['phuong_thuc_thanh_toan'] : 'N/A';
                                        switch ($paymentMethod) {
                                            case 'cod':
                                                echo '<span class="badge badge-info">COD</span>';
                                                break;
                                            case 'momo':
                                                echo '<span class="badge badge-primary">MoMo</span>';
                                                break;
                                            case 'bank_transfer':
                                                echo '<span class="badge badge-success">Chuyển khoản</span>';
                                                break;
                                            default:
                                                echo '<span class="badge badge-secondary">' . htmlspecialchars($paymentMethod) . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        switch ($order['trang_thai']) {
                                            case 'pending':
                                                echo '<span class="badge badge-warning">Chờ xác nhận</span>';
                                                break;
                                            case 'approved':
                                                echo '<span class="badge badge-success">Đã duyệt</span>';
                                                break;
                                            case 'cancelled':
                                                echo '<span class="badge badge-danger">Đã hủy</span>';
                                                break;
                                            default:
                                                echo '<span class="badge badge-secondary">Không xác định</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['ngay_tao'])); ?></td>
                                    <td>
                                        <a href="./index.php?req=don_hang&action=view&id=<?php echo $order['id']; ?>" class="btn btn-info btn-sm">Xem</a>
                                        <a href="print_invoice.php?id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm" target="_blank">
                                            <i class="fas fa-print"></i> In
                                        </a>
                                        <?php if (isset($_SESSION['ADMIN']) && $order['trang_thai'] == 'pending'): ?>
                                            <a href="./index.php?req=don_hang&action=approve&id=<?php echo $order['id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Xác nhận duyệt đơn hàng này?');">Duyệt</a>
                                            <a href="./index.php?req=don_hang&action=cancel&id=<?php echo $order['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xác nhận hủy đơn hàng này? Số lượng tồn kho sẽ được hoàn trả.');">Hủy</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>