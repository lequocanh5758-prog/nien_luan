<footer class="footer mt-auto py-5">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-3 col-md-6">
                <h5 class="text-white mb-3">Về chúng tôi</h5>
                <p class="small text-muted">
                    Cửa hàng điện thoại uy tín hàng đầu Việt Nam. Chuyên cung cấp các sản phẩm chính hãng với
                    chất lượng tốt nhất và dịch vụ chăm sóc khách hàng 24/7.
                </p>
                <div class="social-icons mt-4">
                    <a href="#" title="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" title="Youtube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <h5 class="text-white mb-3">Thông tin hữu ích</h5>
                <ul class="list-unstyled text-muted">
                    <li class="mb-2"><a href="page.php?slug=gioi-thieu" class="text-muted text-decoration-none hover-white">
                        <i class="fas fa-info-circle me-1"></i>Giới thiệu</a></li>
                    <li class="mb-2"><a href="page.php?slug=huong-dan-mua-hang" class="text-muted text-decoration-none hover-white">
                        <i class="fas fa-shopping-bag me-1"></i>Hướng dẫn mua hàng</a></li>
                    <li class="mb-2"><a href="page.php?slug=chinh-sach-bao-hanh" class="text-muted text-decoration-none hover-white">
                        <i class="fas fa-shield-alt me-1"></i>Chính sách bảo hành</a></li>
                    <li class="mb-2"><a href="page.php?slug=chinh-sach-doi-tra" class="text-muted text-decoration-none hover-white">
                        <i class="fas fa-exchange-alt me-1"></i>Chính sách đổi trả</a></li>
                    <li class="mb-2"><a href="page.php?slug=chinh-sach-van-chuyen" class="text-muted text-decoration-none hover-white">
                        <i class="fas fa-truck me-1"></i>Chính sách vận chuyển</a></li>
                    <li class="mb-2"><a href="blog.php" class="text-muted text-decoration-none hover-white">
                        <i class="fas fa-blog me-1"></i>Blog tin tức</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h5 class="text-white mb-3">Liên hệ</h5>
                <ul class="list-unstyled text-muted">
                    <li class="mb-3"><i class="fas fa-map-marker-alt me-2"></i>123 Đường ABC, Phường XYZ, Quận 1, TP.HCM</li>
                    <li class="mb-3"><i class="fas fa-phone me-2"></i>Hotline: 1900 xxxx</li>
                    <li class="mb-3"><i class="fas fa-envelope me-2"></i>Email: support@example.com</li>
                    <li class="mb-3"><i class="fas fa-clock me-2"></i>Giờ làm việc: 8:00 - 22:00</li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h5 class="text-white mb-3">Đăng ký nhận tin</h5>
                <p class="small text-muted">Đăng ký để nhận thông tin về sản phẩm mới và khuyến mãi</p>
                <form class="mb-3" onsubmit="event.preventDefault(); this.querySelector('button').innerHTML='<i class=\'fas fa-check\'></i>'; this.querySelector('input').value=''; alert('Cảm ơn bạn đã đăng ký!');">
                    <div class="input-group">
                        <input class="form-control" type="email" name="newsletter_email" placeholder="Email của bạn" required
                            style="border-radius: 20px 0 0 20px;">
                        <button class="btn btn-primary" type="submit" style="border-radius: 0 20px 20px 0;">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
                <div class="mt-4 d-flex gap-2 align-items-center flex-wrap">
                    <span class="badge bg-dark border border-secondary px-2 py-1" style="font-size: 0.7rem;">
                        <i class="fas fa-money-bill-wave me-1"></i>COD
                    </span>
                    <span class="badge bg-dark border border-secondary px-2 py-1" style="font-size: 0.7rem;">
                        <i class="fas fa-university me-1"></i>Bank
                    </span>
                    <span class="badge bg-dark border border-secondary px-2 py-1" style="font-size: 0.7rem;">
                        <i class="fas fa-mobile-alt me-1"></i>MoMo
                    </span>
                    <span class="badge bg-dark border border-secondary px-2 py-1" style="font-size: 0.7rem;">
                        <i class="fas fa-credit-card me-1"></i>Visa
                    </span>
                </div>
            </div>
        </div>

        <hr class="text-muted my-4">

        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <p class="small text-muted mb-0">
                    &copy; <?php echo date('Y'); ?> Cửa Hàng Điện Thoại. All rights reserved.
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <span class="badge bg-success px-2 py-1">
                    <i class="fas fa-shield-alt me-1"></i>Đã xác minh
                </span>
            </div>
        </div>
    </div>
</footer>
