<?php
/**
 * Cấu hình Logic Giá Sản Phẩm
 * 
 * File này chứa các cấu hình để điều khiển logic cập nhật giá
 * khi có phiếu nhập hoặc thay đổi đơn giá
 */

class PriceLogicConfig 
{
    // Cấu hình có tự động cập nhật giá tham khảo khi duyệt phiếu nhập hay không
    const AUTO_UPDATE_PRICE_ON_IMPORT = false; // Đặt false để không tự động cập nhật
    
    // Cấu hình có ghi đè giá đã có khi duyệt phiếu nhập hay không
    const OVERRIDE_EXISTING_PRICE = false; // Đặt false để không ghi đè giá đã có
    
    // Cấu hình có tạo đơn giá mới từ giá nhập hay không
    const CREATE_PRICE_FROM_IMPORT = true; // Đặt true để tạo đơn giá mới từ giá nhập
    
    // Cấu hình tỷ lệ lợi nhuận mặc định (%)
    const DEFAULT_PROFIT_MARGIN = 20; // 20% lợi nhuận
    
    // Cấu hình có áp dụng tỷ lệ lợi nhuận tự động hay không
    const AUTO_APPLY_PROFIT_MARGIN = true;
    
    /**
     * Tính giá bán từ giá nhập với tỷ lệ lợi nhuận
     */
    public static function calculateSellingPrice($importPrice, $profitMargin = null)
    {
        if ($profitMargin === null) {
            $profitMargin = self::DEFAULT_PROFIT_MARGIN;
        }
        
        return $importPrice * (1 + $profitMargin / 100);
    }
    
    /**
     * Kiểm tra có nên cập nhật giá tham khảo hay không
     */
    public static function shouldUpdateReferencePrice($hasActivePrice = false)
    {
        if (!self::AUTO_UPDATE_PRICE_ON_IMPORT) {
            return false;
        }
        
        if ($hasActivePrice && !self::OVERRIDE_EXISTING_PRICE) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Kiểm tra có nên tạo đơn giá mới từ phiếu nhập hay không
     */
    public static function shouldCreatePriceFromImport()
    {
        return self::CREATE_PRICE_FROM_IMPORT;
    }
    
    /**
     * Lấy thông tin cấu hình hiện tại
     */
    public static function getCurrentConfig()
    {
        return [
            'auto_update_price_on_import' => self::AUTO_UPDATE_PRICE_ON_IMPORT,
            'override_existing_price' => self::OVERRIDE_EXISTING_PRICE,
            'create_price_from_import' => self::CREATE_PRICE_FROM_IMPORT,
            'default_profit_margin' => self::DEFAULT_PROFIT_MARGIN,
            'auto_apply_profit_margin' => self::AUTO_APPLY_PROFIT_MARGIN
        ];
    }
}
