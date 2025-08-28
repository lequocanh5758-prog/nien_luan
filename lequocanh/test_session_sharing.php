<?php
// Test session sharing
require_once './administrator/elements_LQA/mod/sessionManager.php';

// Start session safely
SessionManager::start();

echo "<h2>Test Session Sharing</h2>";

// Set session if requested
if (isset($_GET['set_user'])) {
    $_SESSION['USER'] = $_GET['set_user'];
    echo "Session USER set to: " . $_SESSION['USER'] . "<br>";
}

echo "<h3>Current Session Info:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session USER: " . ($_SESSION['USER'] ?? 'Not set') . "<br>";
echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h3>Set Session:</h3>";
echo "<form method='get'>";
echo "Set USER: <input type='text' name='set_user' value='khaochang'>";
echo "<input type='submit' value='Set'>";
echo "</form>";

echo "<h3>Test Links:</h3>";
echo "<a href='administrator/elements_LQA/mthongbao/getCustomerNotifications.php?action=list' target='_blank'>Test API in new tab</a><br>";
echo "<a href='debug_session.php'>Debug Session</a><br>";
echo "<a href='index.php'>Go to Index</a><br>";

// Test API call with current session
echo "<h3>Test API Call with Current Session:</h3>";
if (isset($_SESSION['USER'])) {
    $apiUrl = './administrator/elements_LQA/mthongbao/getCustomerNotifications.php?action=list';
    
    // Use cURL to test API with same session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/administrator/elements_LQA/mthongbao/getCustomerNotifications.php?action=list');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'X-Requested-With: XMLHttpRequest'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode<br>";
    echo "Response: <pre>$response</pre>";
} else {
    echo "No user in session. Please set a user first.";
}
?>
