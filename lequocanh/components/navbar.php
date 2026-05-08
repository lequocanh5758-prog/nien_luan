<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="fas fa-mobile-alt me-2"></i>
            Cửa Hàng Điện Thoại
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="search-container mx-auto">
                <form class="d-flex" action="./search.php" method="GET" id="searchForm">
                    <input class="form-control me-2" type="search" placeholder="Tìm kiếm sản phẩm..."
                        aria-label="Search" name="query" id="searchInput">
                    <button class="btn btn-light" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                <div id="searchResults"></div>
            </div>

            <div class="ms-auto d-flex align-items-center">
                <?php if (isset($_SESSION['USER'])): ?>
                    <div class="dropdown me-2">
                        <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-2"></i>
                            <?php
                            $username = $_SESSION['USER'];
                            $user = \App\Services\UserService::getInstance()->getUserByUsername($username);
                            echo $user ? $user->hoten : $username;
                            ?>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="./administrator/elements_LQA/mUser/userProfile.php">
                                    <i class="fas fa-user-circle me-2"></i>Thông tin tài khoản
                                </a></li>
                            <?php if ($isNhanVien): ?>
                                <li><a class="dropdown-item" href="./administrator/index.php">
                                        <i class="fas fa-user-cog me-2"></i>Đến trang quản trị
                                    </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item"
                                    href="./administrator/elements_LQA/mUser/userAct.php?reqact=userlogout">
                                    <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                                </a></li>
                        </ul>
                    </div>
                <?php elseif (isset($_SESSION['ADMIN'])): ?>
                    <div class="dropdown me-2">
                        <button class="btn btn-light dropdown-toggle" type="button" id="adminDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-shield me-2"></i>
                            Quản trị viên
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                            <li><a class="dropdown-item" href="./administrator/index.php">
                                    <i class="fas fa-tachometer-alt me-2"></i>Bảng điều khiển
                                </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item"
                                    href="./administrator/elements_LQA/mUser/userAct.php?reqact=userlogout">
                                    <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                                </a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="./administrator/userLogin.php" class="btn btn-light me-2">
                        <i class="fas fa-user me-2"></i>
                        Đăng nhập
                    </a>
                <?php endif; ?>

                <?php if (isset($_SESSION['USER'])): ?>
                    <!-- Nút Yêu thích với Dropdown -->
                    <div class="dropdown me-2">
                        <button class="btn btn-light position-relative" type="button" id="wishlistDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-heart text-danger"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger wishlist-badge" style="display: none;">0</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-0" aria-labelledby="wishlistDropdownBtn" style="width: 320px; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 25px rgba(0,0,0,0.2);">
                            <div style="background: linear-gradient(135deg, #fff5f5 0%, #fff 100%); padding: 12px 15px; border-bottom: 1px solid #eee;">
                                <h6 class="mb-0" style="font-weight: 600; color: #333;"><i class="fas fa-heart text-danger me-2"></i>Sản phẩm yêu thích</h6>
                            </div>
                            <div id="wishlistDropdownContent" style="max-height: 280px; overflow-y: auto; padding: 10px; background: white;">
                                <div class="text-center py-3 text-muted">
                                    <i class="fas fa-spinner fa-spin"></i> Đang tải...
                                </div>
                            </div>
                            <div style="padding: 10px 15px; border-top: 1px solid #eee; text-align: center; background: #f8f9fa;">
                                <a href="#wishlistSection" onclick="scrollToWishlistSection()" style="color: #e74c3c; text-decoration: none; font-weight: 600; font-size: 0.9rem;">Xem tất cả</a>
                            </div>
                        </div>
                    </div>

                    <!-- Nút Hỗ Trợ/Khiếu Nại -->
                    <a href="./customer/support.php" class="btn btn-warning me-2 pulse-animation" title="Liên hệ hỗ trợ">
                        <i class="fas fa-headset"></i>
                    </a>

                    <!-- Icon thông báo đơn hàng với dropdown -->
                    <div class="position-relative me-2">
                        <button class="btn btn-light position-relative notification-btn" onclick="loadNotifications()">
                            <i class="fas fa-bell"></i>
                            <span
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge"
                                style="display: none;">
                                0
                            </span>
                        </button>

                        <!-- Dropdown thông báo -->
                        <div class="notification-dropdown">
                            <div class="notification-header">
                                <h6>Thông báo</h6>
                                <div class="notification-actions-header">
                                    <button class="mark-all-read">Đánh dấu tất cả đã đọc</button>
                                    <button class="delete-read-notifications">Xóa thông báo đã đọc</button>
                                </div>
                                <ul class="notification-list">
                                    <li class="notification-empty">
                                        <i class="fas fa-spinner fa-spin"></i>
                                        <p>Đang tải thông báo...</p>
                                    </li>
                                </ul>
                                <div class="notification-footer">
                                    <a href="./customer/order_history.php">Lịch sử mua hàng</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Icon giỏ hàng -->
                    <a href="./administrator/elements_LQA/mgiohang/giohangView.php"
                        class="btn btn-light position-relative">
                        <i class="fas fa-shopping-cart"></i>
                        <span
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $cartItemCount; ?>
                        </span>
                    </a>
                <?php endif; ?>

                <!-- Modal chi tiết đơn hàng -->
                <div class="order-detail-modal">
                    <div class="order-detail-content">
                        <span class="order-detail-close">&times;</span>
                        <div class="order-detail-header">
                            <h3>Chi tiết đơn hàng #<span id="order-id"></span></h3>
                            <span class="order-status" id="order-status"></span>
                        </div>
                        <div class="order-detail-info">
                            <div class="order-detail-info-col">
                                <div class="order-detail-info-item">
                                    <strong>Mã đơn hàng</strong>
                                    <div id="order-code"></div>
                                </div>
                                <div class="order-detail-info-item">
                                    <strong>Ngày đặt</strong>
                                    <div id="order-date"></div>
                                </div>
                                <div class="order-detail-info-item">
                                    <strong>Phương thức thanh toán</strong>
                                    <div id="order-payment-method"></div>
                                </div>
                            </div>
                            <div class="order-detail-info-col">
                                <div class="order-detail-info-item">
                                    <strong>Phương thức vận chuyển</strong>
                                    <div id="order-shipping-method"></div>
                                </div>
                                <div class="order-detail-info-item" id="estimated-delivery-row">
                                    <strong>Thời gian giao hàng dự kiến</strong>
                                    <div id="order-estimated-delivery"></div>
                                </div>
                                <div class="order-detail-info-item">
                                    <strong>Địa chỉ giao hàng</strong>
                                    <div id="order-address" class="order-detail-address"></div>
                                </div>
                            </div>
                        </div>
                        <div class="order-detail-items">
                            <h4>Sản phẩm</h4>
                            <table>
                                <thead>
                                    <tr>
                                        <th width="70">Hình ảnh</th>
                                        <th>Sản phẩm</th>
                                        <th width="110">Đơn giá</th>
                                        <th width="80">Số lượng</th>
                                        <th width="120">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody id="order-items">
                                </tbody>
                            </table>

                            <!-- Chi tiết thanh toán -->
                            <div class="order-payment-details">
                                <div class="payment-row">
                                    <span>Tạm tính:</span>
                                    <span id="order-subtotal"></span>
                                </div>
                                <div class="payment-row">
                                    <span>Thuế VAT (10%):</span>
                                    <span id="order-tax"></span>
                                </div>
                                <div class="payment-row">
                                    <span>Phí vận chuyển:</span>
                                    <span id="order-shipping"></span>
                                </div>
                                <div class="payment-row payment-status-row">
                                    <span>Trạng thái thanh toán:</span>
                                    <span id="order-payment-status" class="payment-status-badge"></span>
                                </div>
                                <div class="order-detail-total">
                                    Tổng cộng: <span id="order-total"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Category Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <?php require __DIR__ . '/../apart/menuLoaihang.php'; ?>
    </div>
</nav>
