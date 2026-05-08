<?php

require_once __DIR__ . '/../mod/database.php';

$db = Database::getInstance()->getConnection();

$cartWeight = floatval($_SESSION['cart_weight'] ?? 1.0);
$cartValue = floatval($_SESSION['cart_total'] ?? 0);
$provinceId = intval($_SESSION['province_id'] ?? 0);
$districtId = intval($_SESSION['district_id'] ?? 0);

// Lấy danh sách phương thức vận chuyển - DÙNG VIEW GIỐNG ADMIN
$methodsStmt = $db->query("
    SELECT id, code, name, description, delivery_time, sort_order,
           fee_config_count, min_base_fee, min_free_ship_threshold
    FROM v_shipping_methods_with_fees
    WHERE is_active = 1
    ORDER BY sort_order ASC, id ASC
");
$shippingMethods = $methodsStmt->fetchAll(PDO::FETCH_ASSOC);

// Gán phí hiển thị (dùng đúng dữ liệu từ VIEW như admin)
foreach ($shippingMethods as &$method) {
    $baseFee = floatval($method['min_base_fee'] ?? 0);
    $freeThreshold = floatval($method['min_free_ship_threshold'] ?? 0);
    $configCount = intval($method['fee_config_count'] ?? 0);
    
    // Kiểm tra miễn ship
    $isFree = ($baseFee == 0) || ($freeThreshold > 0 && $cartValue >= $freeThreshold);
    
    $method['base_fee'] = $baseFee;
    $method['calculated_fee'] = $isFree ? 0 : $baseFee;
    $method['min_free_ship'] = $freeThreshold;
    $method['is_free'] = $isFree;
    $method['has_config'] = $configCount > 0;
}
unset($method);
?>

<!-- Phương thức vận chuyển -->
<div class="card mb-4" id="shipping-method-section">
    <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <h5 class="mb-0"><i class="fas fa-shipping-fast me-2"></i>Phương thức vận chuyển</h5>
    </div>
    <div class="card-body">
        <?php if (empty($shippingMethods)): ?>
        <div class="alert alert-warning mb-0">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Hiện tại chưa có phương thức vận chuyển nào. Vui lòng liên hệ cửa hàng.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="border-collapse: separate; border-spacing: 0;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="width: 50px; text-align: center; border-top: none;"></th>
                        <th style="border-top: none;">Phương thức</th>
                        <th style="border-top: none;">Thời gian giao</th>
                        <th style="text-align: right; border-top: none;">Phí vận chuyển</th>
                        <th style="text-align: center; border-top: none;">Miễn phí từ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shippingMethods as $index => $method): ?>
                    <?php $isSelected = ($index === 0); ?>
                    <tr class="shipping-method-row <?php echo $isSelected ? 'table-active' : ''; ?>"
                        data-method-code="<?php echo htmlspecialchars($method['code']); ?>"
                        data-method-fee="<?php echo $method['base_fee']; ?>"
                        data-method-id="<?php echo $method['id']; ?>"
                        style="cursor: pointer; <?php echo $isSelected ? 'background: rgba(102, 126, 234, 0.08);' : ''; ?>">
                        
                        <td style="text-align: center; vertical-align: middle;">
                            <input type="radio" 
                                   name="shipping_method_radio" 
                                   value="<?php echo htmlspecialchars($method['code']); ?>"
                                   <?php echo $isSelected ? 'checked' : ''; ?>
                                   style="width: 18px; height: 18px; cursor: pointer; accent-color: #667eea;">
                        </td>
                        
                        <td style="vertical-align: middle;">
                            <strong style="color: #2c3e50; font-size: 15px;">
                                <?php echo htmlspecialchars($method['name']); ?>
                            </strong>
                            <?php if (!empty($method['description'])): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($method['description']); ?></small>
                            <?php endif; ?>
                        </td>
                        
                        <td style="vertical-align: middle; color: #6c757d;">
                            <?php 
                            $deliveryTime = $method['delivery_time'] ?? '';
                            if ($deliveryTime): ?>
                                <i class="far fa-clock me-1"></i>
                                <?php echo htmlspecialchars($deliveryTime); ?>
                                <?php if (!stripos($deliveryTime, 'ngày')): ?>
                                    <small>(ngày làm việc)</small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        
                        <td style="text-align: right; vertical-align: middle;">
                            <?php if (!$method['has_config']): ?>
                                <span class="text-danger" style="font-size: 13px;">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Chưa cấu hình
                                </span>
                            <?php elseif ($method['is_free']): ?>
                                <strong style="color: #28a745; font-size: 16px;">Miễn phí</strong>
                            <?php elseif ($method['base_fee'] == 0): ?>
                                <strong style="color: #28a745; font-size: 16px;">Miễn phí</strong>
                            <?php else: ?>
                                <strong style="color: #2c3e50; font-size: 16px;">
                                    <?php echo number_format($method['base_fee'], 0, ',', '.'); ?>₫
                                </strong>
                            <?php endif; ?>
                        </td>
                        
                        <td style="text-align: center; vertical-align: middle;">
                            <?php if ($method['min_free_ship'] > 0): ?>
                                <span style="color: #28a745; font-size: 13px;">
                                    <i class="fas fa-gift me-1"></i>
                                    Đơn từ <?php echo number_format($method['min_free_ship'], 0, ',', '.'); ?>₫
                                </span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Hidden inputs để gửi lên server -->
        <input type="hidden" id="selected_shipping_method" name="selected_shipping_method" 
               value="<?php echo htmlspecialchars($shippingMethods[0]['code'] ?? 'standard'); ?>">
        <input type="hidden" id="selected_shipping_fee" name="selected_shipping_fee" 
               value="<?php echo $shippingMethods[0]['base_fee'] ?? 0; ?>">
        <?php endif; ?>
    </div>
</div>

<style>
.shipping-method-row:hover {
    background: rgba(102, 126, 234, 0.05) !important;
}
.shipping-method-row.table-active {
    background: rgba(102, 126, 234, 0.08) !important;
}
.shipping-method-row.table-active input[type="radio"] {
    accent-color: #667eea;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('.shipping-method-row');
    const hiddenMethod = document.getElementById('selected_shipping_method');
    const hiddenFee = document.getElementById('selected_shipping_fee');
    
    rows.forEach(row => {
        row.addEventListener('click', function() {
            // Bỏ chọn tất cả
            rows.forEach(r => {
                r.classList.remove('table-active');
                r.style.background = '';
                const radio = r.querySelector('input[type="radio"]');
                if (radio) radio.checked = false;
            });
            
            // Chọn dòng hiện tại
            this.classList.add('table-active');
            this.style.background = 'rgba(102, 126, 234, 0.08)';
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
            
            const methodCode = this.dataset.methodCode;
            const methodFee = parseFloat(this.dataset.methodFee) || 0;
            
            // Cập nhật hidden inputs
            if (hiddenMethod) hiddenMethod.value = methodCode;
            if (hiddenFee) hiddenFee.value = methodFee;
            
            // Cập nhật hiển thị phí vận chuyển
            updateShippingFeeDisplay(methodFee);
            
            // Cập nhật global shipping fee
            window.currentShippingFee = methodFee;
            
            // Thông báo cho checkout qua CustomEvent
            document.dispatchEvent(new CustomEvent('shippingMethodChanged', {
                detail: { method: methodCode, fee: methodFee }
            }));
            
            // Gọi hàm cập nhật tổng tiền của checkout nếu có
            if (typeof window.updateFinalTotal === 'function') {
                window.updateFinalTotal();
            }
        });
    });
    
    // Radio change event
    document.querySelectorAll('input[name="shipping_method_radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const row = this.closest('.shipping-method-row');
            if (row) row.click();
        });
    });
    
    // Khởi tạo phí ban đầu - cập nhật hiển thị ngay
    const firstRow = document.querySelector('.shipping-method-row.table-active') || document.querySelector('.shipping-method-row');
    if (firstRow) {
        const initialFee = parseFloat(firstRow.dataset.methodFee) || 0;
        window.currentShippingFee = initialFee;
        updateShippingFeeDisplay(initialFee);
    }
});

// Hàm khởi tạo lại sau khi checkout load xong
window.addEventListener('load', function() {
    const firstRow = document.querySelector('.shipping-method-row.table-active') || document.querySelector('.shipping-method-row');
    if (firstRow) {
        const initialFee = parseFloat(firstRow.dataset.methodFee) || 0;
        window.currentShippingFee = initialFee;
        updateShippingFeeDisplay(initialFee);
        if (typeof window.updateFinalTotal === 'function') {
            window.updateFinalTotal();
        }
    }
});

function updateShippingFeeDisplay(fee) {
    const feeDisplay = document.getElementById('shipping-fee-value');
    if (feeDisplay) {
        if (fee === 0) {
            feeDisplay.innerHTML = '<span class="text-success fw-bold">Miễn phí</span>';
        } else {
            feeDisplay.textContent = new Intl.NumberFormat('vi-VN').format(fee) + ' ₫';
        }
    }
    
    const statusEl = document.getElementById('shipping-status');
    if (statusEl) {
        statusEl.textContent = fee === 0 ? 'Miễn phí vận chuyển' : 'Phí vận chuyển đã tính';
    }
    
    if (typeof window.currentShippingFee !== 'undefined') {
        window.currentShippingFee = fee;
    }
}
</script>
