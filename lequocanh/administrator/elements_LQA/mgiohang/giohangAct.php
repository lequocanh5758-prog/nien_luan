<?php
// Use SessionManager for safe session handling
require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../config/logger_config.php';

// Start session safely
SessionManager::start();
require_once '../../elements_LQA/mod/giohangCls.php';
require_once '../../elements_LQA/mod/mtonkhoCls.php';

$giohang = new GioHang();

// Kiểm tra xem người dùng có thể sử dụng giỏ hàng không
if (!$giohang->canUseCart()) {
    // Nếu là yêu cầu AJAX, trả về lỗi dưới dạng JSON
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để sử dụng giỏ hàng', 'redirect' => '../../userLogin.php']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Quản trị viên không có quyền sử dụng giỏ hàng', 'redirect' => '../../index.php']);
        }
        exit();
    } else {
        // Lưu URL hiện tại để chuyển hướng lại sau khi đăng nhập
        if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
            $_SESSION['redirect_after_login'] = $_SERVER['HTTP_REFERER'] ?? '../../../index.php';
            header('Location: ../../userLogin.php');
        } else {
            header('Location: ../../index.php');
        }
        exit();
    }
}

// Debug information - only in development mode
if (class_exists('Logger')) {
    Logger::debug("Processing cart action", [
        'session' => $_SESSION,
        'get' => $_GET,
        'script' => $_SERVER['SCRIPT_NAME']
    ]);
}

$tonkho = new MTonKho();

// Kiểm tra hành động từ GET
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    Logger::info("Cart action requested", ['action' => $action]);

    $productId = isset($_GET['productId']) ? (int)$_GET['productId'] : null;
    $quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;

    switch ($action) {
        case 'add':
            if (isset($_GET['productId']) && isset($_GET['quantity'])) {
                $productId = $_GET['productId'];
                $quantity = $_GET['quantity'];

                // Kiểm tra số lượng tồn kho
                $tonkhoInfo = $tonkho->getTonKhoByIdHangHoa($productId);

                // Lấy số lượng hiện tại trong giỏ hàng (nếu có)
                $currentCart = $giohang->getCart();
                $currentQuantity = 0;

                foreach ($currentCart as $item) {
                    if ($item['product_id'] == $productId) {
                        $currentQuantity = $item['quantity'];
                        break;
                    }
                }

                // Tổng số lượng sau khi thêm
                $totalQuantity = $currentQuantity + $quantity;

                // Kiểm tra xem có đủ hàng không
                if (!$tonkhoInfo || $tonkhoInfo->soLuong == 0) {
                    // Sản phẩm hết hàng
                    $_SESSION['cart_error'] = 'Sản phẩm đã hết hàng!';

                    // Lưu HTTP_REFERER vào biến
                    $referrer = $_SERVER['HTTP_REFERER'];
                    header('Location: ' . $referrer);
                    exit();
                } elseif ($totalQuantity > $tonkhoInfo->soLuong) {
                    // Số lượng yêu cầu vượt quá số lượng tồn kho
                    $_SESSION['cart_error'] = 'Số lượng tồn kho chỉ còn ' . $tonkhoInfo->soLuong . ' sản phẩm!';

                    // Lưu HTTP_REFERER vào biến
                    $referrer = $_SERVER['HTTP_REFERER'];
                    header('Location: ' . $referrer);
                    exit();
                } else {
                    // Đủ hàng, thêm vào giỏ hàng
                    $result = $giohang->addToCart($productId, $quantity);

                    // Lưu HTTP_REFERER vào biến
                    $referrer = $_SERVER['HTTP_REFERER'];

                    if (strpos($referrer, 'administrator') !== false && strpos($referrer, 'administrator/elements_LQA/mgiohang') === false) {
                        // Nếu đang ở trang admin (không phải trang giỏ hàng), chuyển về trang giỏ hàng admin
                        header('Location: ../mgiohang/giohangView.php');
                    } else {
                        // Chuyển hướng đến trang thông báo thành công
                        header('Location: cart_redirect.php?referrer=' . urlencode($referrer));
                    }
                    exit();
                }
            }
            break;

        case 'clear':
            $giohang->clearCart();
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit();

        case 'removeSelected':
            // Nhận dữ liệu JSON từ request
            $data = json_decode(file_get_contents('php://input'), true);

            if (isset($data['productIds']) && is_array($data['productIds'])) {
                foreach ($data['productIds'] as $productId) {
                    $giohang->removeFromCart((int)$productId);
                }
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit();
            }
            break;

        default:
            $_SESSION['error'] = 'Hành động không hợp lệ.';
            break;
    }
}

exit();
