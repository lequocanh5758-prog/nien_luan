<?php
/**
 * MoMo Integration Test
 * File test đơn giản để kiểm tra tích hợp MoMo
 */

require_once __DIR__ . '/../mod/sessionManager.php';
SessionManager::start();

// Fake admin session for testing
$_SESSION['ADMIN'] = ['id' => 1, 'username' => 'admin'];

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test MoMo Integration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .test-container { max-width: 800px; margin: 50px auto; padding: 30px; }
        .test-section { margin-bottom: 30px; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px; }
        .log-output { background: #f8f9fa; padding: 15px; border-radius: 4px; font-family: monospace; max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="test-container">
        <h1 class="text-center mb-4">🧪 MoMo Integration Test</h1>
        
        <!-- Test 1: Init Payment -->
        <div class="test-section">
            <h3>1️⃣ Test Init Payment</h3>
            <p>Tạo yêu cầu thanh toán MoMo với thông tin test</p>
            <button class="btn btn-primary" onclick="testInitPayment()">Test Init Payment</button>
            <div id="init-result" class="log-output mt-3" style="display: none;"></div>
        </div>

        <!-- Test 2: Query Transaction -->
        <div class="test-section">
            <h3>2️⃣ Test Query Transaction</h3>
            <p>Kiểm tra trạng thái giao dịch</p>
            <div class="input-group mb-3">
                <input type="text" class="form-control" id="query-order-id" placeholder="Nhập Order ID để query">
                <button class="btn btn-secondary" onclick="testQueryTransaction()">Query Transaction</button>
            </div>
            <div id="query-result" class="log-output" style="display: none;"></div>
        </div>

        <!-- Test 3: Signature Validation -->
        <div class="test-section">
            <h3>3️⃣ Test Signature Validation</h3>
            <p>Kiểm tra tính năng validate signature</p>
            <button class="btn btn-info" onclick="testSignatureValidation()">Test Signature</button>
            <div id="signature-result" class="log-output mt-3" style="display: none;"></div>
        </div>

        <!-- Test 4: Full Payment Flow -->
        <div class="test-section">
            <h3>4️⃣ Test Full Payment Flow</h3>
            <p>Test toàn bộ luồng thanh toán (cần có sản phẩm trong session)</p>
            <button class="btn btn-success" onclick="setupTestOrder()">Setup Test Order</button>
            <button class="btn btn-warning ms-2" onclick="testFullPayment()">Test Full Payment</button>
            <div id="full-result" class="log-output mt-3" style="display: none;"></div>
        </div>

        <!-- Logs -->
        <div class="test-section">
            <h3>📋 Transaction Logs</h3>
            <button class="btn btn-outline-primary" onclick="viewLogs()">View Recent Logs</button>
            <div id="logs-result" class="log-output mt-3" style="display: none;"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function testInitPayment() {
            const resultDiv = document.getElementById('init-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '⏳ Testing init payment...';

            const testData = {
                orderInfo: 'Test Order - ' + new Date().toLocaleString(),
                amount: '50000',
                orderId: 'TEST_' + Date.now(),
                redirectUrl: window.location.origin + '/lequocanh/administrator/elements_LQA/mgiohang/momo_return.php',
                ipnUrl: window.location.origin + '/lequocanh/administrator/elements_LQA/mgiohang/momo_notify.php',
                extraData: JSON.stringify({test: true})
            };

            fetch('init_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(testData)
            })
            .then(response => response.json())
            .then(data => {
                resultDiv.innerHTML = `
                    <strong>✅ Init Payment Result:</strong><br>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                    ${data.payUrl ? `<br><a href="${data.payUrl}" target="_blank" class="btn btn-sm btn-success">Open Payment URL</a>` : ''}
                `;
                
                // Save order ID for query test
                if (data.orderId) {
                    document.getElementById('query-order-id').value = data.orderId;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `<strong>❌ Error:</strong><br>${error.message}`;
            });
        }

        function testQueryTransaction() {
            const orderId = document.getElementById('query-order-id').value;
            const resultDiv = document.getElementById('query-result');
            
            if (!orderId) {
                alert('Vui lòng nhập Order ID');
                return;
            }

            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '⏳ Querying transaction...';

            fetch('query_transaction.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `orderId=${encodeURIComponent(orderId)}`
            })
            .then(response => response.json())
            .then(data => {
                resultDiv.innerHTML = `
                    <strong>✅ Query Result:</strong><br>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
            })
            .catch(error => {
                resultDiv.innerHTML = `<strong>❌ Error:</strong><br>${error.message}`;
            });
        }

        function testSignatureValidation() {
            const resultDiv = document.getElementById('signature-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '⏳ Testing signature validation...';

            // Test data for signature validation
            const testData = {
                partnerCode: 'MOMO',
                orderId: 'TEST_' + Date.now(),
                amount: '10000',
                message: 'Successful.',
                resultCode: '0'
            };

            // This would normally be done server-side
            resultDiv.innerHTML = `
                <strong>✅ Signature Test Data:</strong><br>
                <pre>${JSON.stringify(testData, null, 2)}</pre>
                <br><em>Note: Signature validation is handled server-side in the actual implementation.</em>
            `;
        }

        function setupTestOrder() {
            const resultDiv = document.getElementById('full-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '⏳ Setting up test order...';

            // Setup test order data in session
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>?action=setup_test', {
                method: 'POST'
            })
            .then(response => response.text())
            .then(data => {
                resultDiv.innerHTML = `
                    <strong>✅ Test Order Setup:</strong><br>
                    Test order data has been added to session.<br>
                    You can now test the full payment flow.
                `;
            })
            .catch(error => {
                resultDiv.innerHTML = `<strong>❌ Error:</strong><br>${error.message}`;
            });
        }

        function testFullPayment() {
            const resultDiv = document.getElementById('full-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '⏳ Testing full payment flow...';

            const testData = new FormData();
            testData.append('payment_method', 'momo');
            testData.append('order_code', 'TEST_ORDER_' + Date.now());
            testData.append('shipping_address', 'Test Address, Test City');
            testData.append('amount', '75000');

            fetch('momo_payment.php', {
                method: 'POST',
                body: testData
            })
            .then(response => response.json())
            .then(data => {
                resultDiv.innerHTML = `
                    <strong>✅ Full Payment Test Result:</strong><br>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                    ${data.payUrl ? `<br><a href="${data.payUrl}" target="_blank" class="btn btn-sm btn-success">Open Payment URL</a>` : ''}
                `;
            })
            .catch(error => {
                resultDiv.innerHTML = `<strong>❌ Error:</strong><br>${error.message}`;
            });
        }

        function viewLogs() {
            const resultDiv = document.getElementById('logs-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '⏳ Loading logs...';

            fetch('<?php echo $_SERVER['PHP_SELF']; ?>?action=view_logs')
            .then(response => response.text())
            .then(data => {
                resultDiv.innerHTML = `<strong>📋 Recent Transaction Logs:</strong><br><pre>${data}</pre>`;
            })
            .catch(error => {
                resultDiv.innerHTML = `<strong>❌ Error:</strong><br>${error.message}`;
            });
        }
    </script>
</body>
</html>

<?php
// Handle AJAX requests
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'setup_test':
            // Setup test order data in session
            $_SESSION['order_details'] = [
                [
                    'id' => 1,
                    'name' => 'Test Product',
                    'price' => 25000,
                    'quantity' => 3,
                    'subtotal' => 75000,
                    'image' => 'test.jpg'
                ]
            ];
            $_SESSION['total_amount'] = 75000;
            $_SESSION['order_code'] = 'TEST_ORDER_' . time();
            echo 'Test order setup complete';
            exit;
            
        case 'view_logs':
            $logFile = __DIR__ . '/../logs/momo_transactions.log';
            if (file_exists($logFile)) {
                $logs = file_get_contents($logFile);
                $lines = explode("\n", $logs);
                $recentLines = array_slice($lines, -10); // Last 10 lines
                echo implode("\n", $recentLines);
            } else {
                echo 'No log file found';
            }
            exit;
    }
}
?>