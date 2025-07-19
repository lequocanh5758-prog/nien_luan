<?php ob_start(); ?>
<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once './administrator/elements_LQA/mod/loaihangCls.php';
require_once './administrator/elements_LQA/mod/hanghoaCls.php';
$hanghoa = new hanghoa();

if (isset($_GET['reqView'])) {
    $idloaihang = $_GET['reqView'];
    $list_hanghoa = $hanghoa->HanghoaGetbyIdloaihang($idloaihang);
} else {
    $list_hanghoa = $hanghoa->HanghoaGetAll();
}

// Lấy 5 sản phẩm mới nhất cho carousel
$carousel_items = array_slice($list_hanghoa, 0, 5);

// Debug: Kiểm tra số lượng sản phẩm
// echo "Số sản phẩm trong carousel: " . count($carousel_items);
?>

<div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">
        <?php
        if (!empty($carousel_items)) {
            foreach ($carousel_items as $index => $item):
                // Get image from hinhanh table
                $hinhanh = $hanghoa->GetHinhAnhById($item->hinhanh);
        ?>
        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>" data-bs-interval="3000">
            <a href="./index.php?reqHanghoa=<?php echo $item->idhanghoa; ?>">
                <?php if ($hinhanh && !empty($hinhanh->duong_dan)): ?>
                <img src="./administrator/elements_LQA/mhanghoa/displayImage.php?id=<?php echo $item->hinhanh; ?>"
                    class="d-block" alt="<?php echo $item->tenhanghoa; ?>">
                <?php else: ?>
                <div class="updating-image-container">
                    <img src="./administrator/elements_LQA/img_LQA/no-image.png" alt="Không có hình ảnh">
                </div>
                <?php endif; ?>
            </a>
            <div class="carousel-caption">
                <h5 class="m-0"><?php echo $item->tenhanghoa; ?></h5>
                <p class="m-0"><?php echo number_format($item->giathamkhao, 0, ',', '.') . ' VNĐ'; ?></p>
            </div>
        </div>
        <?php
            endforeach;
        } else {
            echo '<div class="alert alert-warning">Không có sản phẩm nào để hiển thị</div>';
        }
        ?>
    </div>

    <?php if (count($carousel_items) > 1): ?>
    <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
    <?php endif; ?>
</div>

<!-- Thêm script khởi tạo carousel -->
<script src="administrator/elements_LQA/js_LQA/jscript.js"></script>

<div class="row row-cols-1 row-cols-md-3 g-4">
    <?php foreach ($list_hanghoa as $v):
        // Get image from hinhanh table
        $hinhanh = $hanghoa->GetHinhAnhById($v->hinhanh);
    ?>
    <div class="col">
        <div class="card h-100">
            <?php if ($hinhanh && !empty($hinhanh->duong_dan)): ?>
            <img src="./administrator/elements_LQA/mhanghoa/displayImage.php?id=<?php echo $v->hinhanh; ?>"
                class="card-img-top" alt="<?php echo $v->tenhanghoa; ?>">
            <?php else: ?>
            <div class="updating-image-container">
                <img src="./administrator/elements_LQA/img_LQA/no-image.png" alt="Không có hình ảnh">
            </div>
            <?php endif; ?>
            <div class="card-body">
                <h5 class="card-title"><?php echo $v->tenhanghoa; ?></h5>
                <p class="card-text text-danger fw-bold">
                    <?php echo number_format($v->giathamkhao, 0, ',', '.') . ' VNĐ'; ?>
                </p>
                <div class="d-flex justify-content-between align-items-center">
                    <a href="./index.php?reqHanghoa=<?php echo $v->idhanghoa; ?>" class="btn btn-outline-primary">
                        Xem chi tiết
                    </a>
                    <!-- Thêm checkbox để so sánh -->
                    <div class="form-check">
                        <input class="form-check-input compare-checkbox" type="checkbox"
                            value="<?php echo $v->idhanghoa; ?>" id="compare_<?php echo $v->idhanghoa; ?>">
                        <label class="form-check-label" for="compare_<?php echo $v->idhanghoa; ?>">
                            So sánh
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Thêm nút so sánh cố định ở góc màn hình -->
<div id="compareButton" class="position-fixed bottom-0 end-0 mb-4 me-4" style="display: none;">
    <button class="btn btn-primary" onclick="compareProducts()">
        So sánh (<span id="compareCount">0</span>)
    </button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const compareCheckboxes = document.querySelectorAll('.compare-checkbox');
    const compareButton = document.getElementById('compareButton');
    const compareCount = document.getElementById('compareCount');
    let selectedProducts = [];

    // Thêm đoạn code này để xóa trạng thái checked khi load trang
    compareCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    compareButton.style.display = 'none';
    compareCount.textContent = '0';

    compareCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                if (selectedProducts.length >= 3) {
                    alert('Chỉ có thể so sánh tối đa 3 sản phẩm!');
                    this.checked = false;
                    return;
                }
                selectedProducts.push(this.value);
            } else {
                selectedProducts = selectedProducts.filter(id => id !== this.value);
            }

            compareCount.textContent = selectedProducts.length;
            compareButton.style.display = selectedProducts.length > 1 ? 'block' : 'none';
        });
    });
});

function compareProducts() {
    const selectedProducts = Array.from(document.querySelectorAll('.compare-checkbox:checked'))
        .map(checkbox => checkbox.value);

    if (selectedProducts.length < 2) {
        alert('Vui lòng chọn ít nhất 2 sản phẩm để so sánh!');
        return;
    }

    window.location.href = `sosanh.php?products=${selectedProducts.join(',')}`;
}

// Xử lý thông báo khi thêm giỏ hàng thành công
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('cartAdded')) {
    // Hiển thị thông báo
    alert('Đã thêm sản phẩm vào giỏ hàng!');

    // Xóa tham số cartAdded khỏi URL để tránh hiển thị lại thông báo khi refresh
    const newUrl = window.location.href.replace(/[&?]cartAdded=1/, '');
    window.history.replaceState({}, document.title, newUrl);
}

// Ngăn chặn tải lại trang liên tục
let reloadCount = sessionStorage.getItem('reloadCount') || 0;
if (reloadCount > 5) {
    // Nếu trang đã tải lại quá nhiều lần, ngăn chặn việc tải lại tự động
    console.log('Đã phát hiện tải lại trang quá nhiều lần, ngăn chặn tải lại tự động');
    window.stop();
    sessionStorage.setItem('reloadCount', 0);
    alert('Đã phát hiện tải lại trang quá nhiều lần. Vui lòng thử lại sau.');
} else {
    // Tăng số lần tải lại
    sessionStorage.setItem('reloadCount', parseInt(reloadCount) + 1);
    // Sau 5 giây, đặt lại bộ đếm
    setTimeout(() => {
        sessionStorage.setItem('reloadCount', 0);
    }, 5000);
}
</script>
<?php ob_end_flush(); ?>