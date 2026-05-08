<!-- Scripts - Optimized loading -->
<script>
    window.addEventListener('load', function() {
        document.getElementById('pageLoader').style.display = 'none';
    });

    ! function(e) {
        "use strict";
        var t = function(t, n, r) {
            var o, i = e.document, c = i.createElement("link");
            if (n) o = n;
            else {
                var s = (i.body || i.getElementsByTagName("head")[0]).childNodes;
                o = s[s.length - 1]
            }
            var u = i.styleSheets;
            c.rel = "stylesheet", c.href = t, c.media = "only x",
                function e(t) {
                    if (i.body) return t();
                    setTimeout(function() { e(t) })
                }(function() { o.parentNode.insertBefore(c, n ? o : o.nextSibling) });
            var f = function(e) {
                for (var t = c.href, n = u.length; n--;)
                    if (u[n].href === t) return e();
                setTimeout(function() { f(e) })
            };
            return f(function() { c.media = r || "all" }), c
        };
        "undefined" != typeof module && (module.exports = t)
    }(this);
</script>

<!-- Load jQuery first -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>

<!-- Load Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>

<!-- Load local scripts -->
<script src="administrator/js_LQA/jscript.js" defer></script>
<script src="public_files/search.js" defer></script>
<script src="public_files/product_filter.js" defer></script>
<script src="public_files/product_reviews.js" defer></script>
<script src="public_files/performance.js" defer></script>

<!-- Conditional notification script -->
<?php if (isset($_SESSION['USER'])): ?>
    <script src="public_files/notification.js" defer></script>
    <script src="public_files/wishlist.js?v=<?php echo time(); ?>" defer></script>
<?php endif; ?>

<!-- Performance optimization script -->
<script>
    let notificationsLoaded = false;
    let orderModalLoaded = false;

    function loadNotifications() {
        if (!notificationsLoaded) {
            const modalHTML = `
            <div class="order-detail-modal" id="orderDetailModal">
                <div class="order-detail-content">
                    <span class="order-detail-close" onclick="closeOrderModal()">&times;</span>
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
                        <div class="order-detail-info-col">
                            <div class="order-detail-info-item">
                                <strong>Địa chỉ giao hàng</strong>
                                <div id="order-address" class="order-detail-address"></div>
                            </div>
                        </div>
                    <div class="order-detail-items">
                        <h4>Sản phẩm</h4>
                        <table>
                            <thead>
                                <tr>
                                    <th width="60">Hình ảnh</th>
                                    <th>Sản phẩm</th>
                                    <th width="100">Đơn giá</th>
                                    <th width="80">Số lượng</th>
                                    <th width="120">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody id="order-items"></tbody>
                        </table>
                        <div class="order-detail-total">
                            Tổng tiền: <span id="order-total"></span>
                        </div>
                    </div>
                </div>
            </div>
            `;

            const notificationContainer = document.querySelector('.position-relative.me-2');
            if (notificationContainer) {
                notificationContainer.insertAdjacentHTML('beforeend', modalHTML);
            }
            notificationsLoaded = true;
        }

        const dropdown = document.getElementById('notificationDropdown');
        if (dropdown) {
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }
    }

    function closeOrderModal() {
        const modal = document.getElementById('orderDetailModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    if ('serviceWorker' in navigator) {
        const resources = [
            'public_files/mycss.css',
            'public_files/notification.css',
            'administrator/js_LQA/jscript.js',
            'public_files/search.js'
        ];
        resources.forEach(resource => {
            const link = document.createElement('link');
            link.rel = 'prefetch';
            link.href = resource;
            document.head.appendChild(link);
        });
    }

    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy-load');
                    img.classList.add('loaded');
                    imageObserver.unobserve(img);
                }
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            const lazyImages = document.querySelectorAll('img[data-src]');
            lazyImages.forEach(img => imageObserver.observe(img));
        });
    }
</script>

<!-- CSRF Protection Helper -->
<script src="public_files/js/csrf-helper.js" defer></script>

<?php echo perf_footer(); ?>
