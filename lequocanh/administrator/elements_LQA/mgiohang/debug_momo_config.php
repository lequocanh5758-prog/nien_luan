<?php
/**
 * Debug MoMo Configuration và paths
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>MoMo Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; }
        .error { background-color: #f8d7da; }
        .info { background-color: #d1ecf1; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 MoMo Debug Information</h1>

    <div class="section info">
        <h3>🌐 Server Information</h3>
        <strong>Current URL:</strong> <?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?><br>
        <strong>Host:</strong> <?php echo $_SERVER['HTTP_HOST'] ?? 'unknown'; ?><br>
        <strong>Protocol:</strong> <?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'HTTPS' : 'HTTP'; ?><br>
        <strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'unknown'; ?><br>
    </div>

    <div class="section">
        <h3>⚙️ MoMo Configuration</h3>
        <?php
        try {
            require_once __DIR__ . '/../../../payment/MoMoConfig.php';
            echo '<div class="success">';
            echo '<strong>✅ MoMoConfig loaded successfully</strong><br>';
            $baseUrl = MoMoConfig::getBaseUrl();
            $returnUrl = MoMoConfig::getReturnUrl();
            $notifyUrl = MoMoConfig::getNotifyUrl();
            
            echo '<strong>Base URL:</strong> ' . $baseUrl . '<br>';
            echo '<strong>Return URL:</strong> <a href="' . $returnUrl . '" target="_blank">' . $returnUrl . '</a><br>';
            echo '<strong>Notify URL:</strong> <a href="' . $notifyUrl . '" target="_blank">' . $notifyUrl . '</a><br>';
            
            // Kiểm tra các file endpoint có tồn tại không
            echo '<div style="margin-top:10px; font-size:14px;">';
            
            // Kiểm tra return file
            $returnFile = $_SERVER['DOCUMENT_ROOT'] . '/administrator/elements_LQA/mgiohang/momo_return.php';
            if (file_exists($returnFile)) {
                echo '✅ <strong>momo_return.php:</strong> Tồn tại<br>';
            } else {
                echo '❌ <strong>momo_return.php:</strong> KHÔNG tồn tại tại: ' . $returnFile . '<br>';
            }
            
            // Kiểm tra notify file
            $notifyFile = $_SERVER['DOCUMENT_ROOT'] . '/payment/notify.php';
            if (file_exists($notifyFile)) {
                echo '✅ <strong>notify.php:</strong> Tồn tại<br>';
            } else {
                echo '❌ <strong>notify.php:</strong> KHÔNG tồn tại tại: ' . $notifyFile . '<br>';
            }
            
            echo '</div>';
            echo '<strong>Partner Code:</strong> ' . MoMoConfig::getPartnerCode() . '<br>';
            echo '<strong>Access Key:</strong> ' . substr(MoMoConfig::getAccessKey(), 0, 5) . '...' . '<br>';
            echo '<strong>Endpoint:</strong> ' . MoMoConfig::getEndpoint() . '<br>';
            echo '</div>';
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<strong>❌ Error loading MoMoConfig:</strong> ' . $e->getMessage();
            echo '</div>';
        }
        ?>
    </div>

    <div class="section">
        <h3>📁 File Paths</h3>
        <?php
        $files = [
            'MoMoConfig.php' => __DIR__ . '/../../../payment/MoMoConfig.php',
            'MoMoPayment.php' => __DIR__ . '/../../../payment/MoMoPayment.php',
            'momo_payment.php' => __DIR__ . '/momo_payment.php',
            'checkout.php' => __DIR__ . '/checkout.php'
        ];

        foreach ($files as $name => $path) {
            if (file_exists($path)) {
                echo "✅ <strong>$name:</strong> $path<br>";
            } else {
                echo "❌ <strong>$name:</strong> $path (NOT FOUND)<br>";
            }
        }
        ?>
    </div>

    <div class="section">
        <h3>🔧 Test MoMo API Call</h3>
        <?php
        try {
            require_once __DIR__ . '/../../../payment/MoMoPayment.php';
            
            // Fake session for test
            $_SESSION['USER'] = 'test_user';
            
            $momoPayment = new MoMoPayment();
            echo '<div class="success">';
            echo '✅ <strong>MoMoPayment class instantiated successfully</strong><br>';
            echo '✅ Ready to test payment creation<br>';
            echo '</div>';
            
            // Test data
            $testAmount = 10000;
            $testOrderInfo = "Test order from debug";
            $testExtraData = json_encode(['test' => true, 'debug' => true]);
            
            echo '<div class="info">';
            echo '<strong>Test Parameters:</strong><br>';
            echo "Amount: " . number_format($testAmount) . " VND<br>";
            echo "Order Info: $testOrderInfo<br>";
            echo "Extra Data: $testExtraData<br>";
            echo '</div>';
            
            // Simulate API call (don't actually call MoMo in debug)
            echo '<div class="info">';
            echo '🧪 <strong>Simulation Mode:</strong> API call would be made with above parameters<br>';
            echo '📝 <strong>Next Step:</strong> Click on MoMo payment in actual checkout page to test real API call';
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '❌ <strong>Error testing MoMo:</strong> ' . $e->getMessage();
            echo '</div>';
        }
        ?>
    </div>

    <div class="section">
        <h3>🎯 Quick Test Links</h3>
        <a href="checkout.php?test=1" target="_blank">🔗 Test Checkout Page (Fake Data)</a><br>
        <a href="test_api.php" target="_blank">🔗 Test API Endpoint</a><br>
        <a href="../../index.php" target="_blank">🔗 Go to Home Page</a>
    </div>

    <div class="section">
        <h3>📋 Instructions</h3>
        <ol>
            <li><strong>Verify all files exist</strong> (check green checkmarks above)</li>
            <li><strong>Check MoMo configuration</strong> (Base URL should match your ngrok URL)</li>
            <li><strong>Test checkout page</strong> using the "Test Checkout Page" link above</li>
            <li><strong>Open browser DevTools</strong> (F12) → Console tab</li>
            <li><strong>Click "Thanh toán MoMo"</strong> and watch console logs</li>
            <li><strong>Check Network tab</strong> to see API request/response</li>
        </ol>
    </div>

</body>
</html>
