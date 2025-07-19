<?php

/**
 * Quản lý thông báo khi có giao dịch MoMo
 */

class NotificationManager 
{
    private $adminEmail;
    private $adminPhone;
    private $webhookUrl;
    
    public function __construct() 
    {
        // Cấu hình thông tin admin (có thể đưa vào config file)
        $this->adminEmail = 'admin@yourdomain.com'; // Thay bằng email của bạn
        $this->adminPhone = '0123456789'; // Thay bằng SĐT của bạn
        $this->webhookUrl = 'https://hooks.slack.com/your-webhook'; // Slack webhook (optional)
    }
    
    /**
     * Gửi thông báo khi có giao dịch thành công
     */
    public function notifyPaymentSuccess($transaction) 
    {
        $subject = "✅ Thanh toán thành công - " . number_format($transaction['amount']) . " VND";
        $message = $this->buildSuccessMessage($transaction);
        
        // Gửi email
        $this->sendEmail($subject, $message);
        
        // Gửi SMS (nếu có cấu hình)
        $this->sendSMS("Bạn vừa nhận được " . number_format($transaction['amount']) . " VND từ MoMo. Order: " . $transaction['order_id']);
        
        // Gửi Slack notification (nếu có)
        $this->sendSlackNotification($subject, $message, 'good');
        
        // Log notification
        $this->logNotification('SUCCESS', $transaction['order_id'], $message);
    }
    
    /**
     * Gửi thông báo khi có giao dịch thất bại
     */
    public function notifyPaymentFailed($transaction) 
    {
        $subject = "❌ Thanh toán thất bại - " . $transaction['order_id'];
        $message = $this->buildFailedMessage($transaction);
        
        // Chỉ gửi email cho trường hợp thất bại
        $this->sendEmail($subject, $message);
        
        // Log notification
        $this->logNotification('FAILED', $transaction['order_id'], $message);
    }
    
    /**
     * Gửi thông báo tổng kết hàng ngày
     */
    public function sendDailySummary() 
    {
        $summary = $this->getDailySummary();
        $subject = "📊 Báo cáo giao dịch ngày " . date('d/m/Y');
        $message = $this->buildSummaryMessage($summary);
        
        $this->sendEmail($subject, $message);
        $this->logNotification('DAILY_SUMMARY', 'SYSTEM', $message);
    }
    
    /**
     * Xây dựng nội dung email thành công
     */
    private function buildSuccessMessage($transaction) 
    {
        return "
        <h2 style='color: #28a745;'>💰 Bạn vừa nhận được thanh toán!</h2>
        
        <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
            <h3>Thông tin giao dịch:</h3>
            <ul style='list-style: none; padding: 0;'>
                <li><strong>Mã đơn hàng:</strong> {$transaction['order_id']}</li>
                <li><strong>Số tiền:</strong> <span style='color: #28a745; font-size: 18px;'>" . number_format($transaction['amount']) . " VND</span></li>
                <li><strong>Thông tin:</strong> {$transaction['order_info']}</li>
                <li><strong>Mã giao dịch MoMo:</strong> {$transaction['trans_id']}</li>
                <li><strong>Thời gian:</strong> " . date('d/m/Y H:i:s', strtotime($transaction['created_at'])) . "</li>
            </ul>
        </div>
        
        <p>Tiền sẽ được chuyển vào tài khoản MoMo Business của bạn trong vòng 24h.</p>
        
        <div style='margin-top: 30px; padding: 15px; background: #e3f2fd; border-radius: 5px;'>
            <p><strong>Lưu ý:</strong> Đây là thông báo tự động từ hệ thống thanh toán.</p>
        </div>
        ";
    }
    
    /**
     * Xây dựng nội dung email thất bại
     */
    private function buildFailedMessage($transaction) 
    {
        return "
        <h2 style='color: #dc3545;'>⚠️ Giao dịch thanh toán thất bại</h2>
        
        <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
            <h3>Thông tin giao dịch:</h3>
            <ul style='list-style: none; padding: 0;'>
                <li><strong>Mã đơn hàng:</strong> {$transaction['order_id']}</li>
                <li><strong>Số tiền:</strong> " . number_format($transaction['amount']) . " VND</li>
                <li><strong>Thông tin:</strong> {$transaction['order_info']}</li>
                <li><strong>Lý do thất bại:</strong> {$transaction['message']}</li>
                <li><strong>Thời gian:</strong> " . date('d/m/Y H:i:s', strtotime($transaction['created_at'])) . "</li>
            </ul>
        </div>
        
        <p>Vui lòng kiểm tra và liên hệ khách hàng nếu cần thiết.</p>
        ";
    }
    
    /**
     * Gửi email
     */
    private function sendEmail($subject, $message) 
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: MoMo Payment System <noreply@yourdomain.com>',
            'Reply-To: noreply@yourdomain.com',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        try {
            $result = mail($this->adminEmail, $subject, $message, implode("\r\n", $headers));
            if (!$result) {
                error_log("Failed to send email notification: $subject");
            }
        } catch (Exception $e) {
            error_log("Email notification error: " . $e->getMessage());
        }
    }
    
    /**
     * Gửi SMS (cần tích hợp với SMS gateway)
     */
    private function sendSMS($message) 
    {
        // Ví dụ tích hợp với SMS gateway (cần cấu hình)
        // Có thể sử dụng: Twilio, AWS SNS, hoặc SMS gateway Việt Nam
        
        try {
            // Ví dụ với cURL call tới SMS API
            /*
            $smsData = [
                'phone' => $this->adminPhone,
                'message' => $message,
                'api_key' => 'YOUR_SMS_API_KEY'
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://sms-api.example.com/send');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($smsData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            
            $response = curl_exec($ch);
            curl_close($ch);
            */
            
            // Tạm thời log SMS thay vì gửi thật
            error_log("SMS Notification: $message");
            
        } catch (Exception $e) {
            error_log("SMS notification error: " . $e->getMessage());
        }
    }
    
    /**
     * Gửi Slack notification
     */
    private function sendSlackNotification($title, $message, $color = 'good') 
    {
        if (empty($this->webhookUrl)) {
            return;
        }
        
        try {
            $payload = [
                'attachments' => [
                    [
                        'color' => $color,
                        'title' => $title,
                        'text' => strip_tags($message),
                        'footer' => 'MoMo Payment System',
                        'ts' => time()
                    ]
                ]
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->webhookUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            
            curl_exec($ch);
            curl_close($ch);
            
        } catch (Exception $e) {
            error_log("Slack notification error: " . $e->getMessage());
        }
    }
    
    /**
     * Lấy thống kê hàng ngày
     */
    private function getDailySummary() 
    {
        try {
            require_once '../administrator/elements_LQA/mPDO.php';
            $pdo = new mPDO();
            
            $today = date('Y-m-d');
            
            // Tổng giao dịch thành công
            $successQuery = "SELECT COUNT(*) as count, SUM(amount) as total 
                           FROM momo_transactions 
                           WHERE DATE(created_at) = ? AND status = 'SUCCESS'";
            $success = $pdo->executeS($successQuery, [$today]);
            
            // Tổng giao dịch thất bại
            $failedQuery = "SELECT COUNT(*) as count 
                          FROM momo_transactions 
                          WHERE DATE(created_at) = ? AND status = 'FAILED'";
            $failed = $pdo->executeS($failedQuery, [$today]);
            
            return [
                'date' => $today,
                'success_count' => $success['count'] ?? 0,
                'success_amount' => $success['total'] ?? 0,
                'failed_count' => $failed['count'] ?? 0
            ];
            
        } catch (Exception $e) {
            error_log("Error getting daily summary: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Xây dựng nội dung báo cáo tổng kết
     */
    private function buildSummaryMessage($summary) 
    {
        if (!$summary) {
            return "<p>Không thể tạo báo cáo do lỗi hệ thống.</p>";
        }
        
        return "
        <h2>📊 Báo cáo giao dịch ngày " . date('d/m/Y', strtotime($summary['date'])) . "</h2>
        
        <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
            <h3>Thống kê:</h3>
            <ul style='list-style: none; padding: 0;'>
                <li>✅ <strong>Giao dịch thành công:</strong> {$summary['success_count']} giao dịch</li>
                <li>💰 <strong>Tổng doanh thu:</strong> <span style='color: #28a745; font-size: 18px;'>" . number_format($summary['success_amount']) . " VND</span></li>
                <li>❌ <strong>Giao dịch thất bại:</strong> {$summary['failed_count']} giao dịch</li>
            </ul>
        </div>
        
        <p>Báo cáo được tạo tự động lúc " . date('H:i:s d/m/Y') . "</p>
        ";
    }
    
    /**
     * Log notification
     */
    private function logNotification($type, $orderId, $message) 
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $type,
            'order_id' => $orderId,
            'message' => strip_tags($message)
        ];
        
        error_log("Notification Log: " . json_encode($logEntry));
    }
}
