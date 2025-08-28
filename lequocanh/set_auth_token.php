<?php
// Set authentication token for user
require_once './administrator/elements_LQA/mod/sessionManager.php';
require_once './administrator/elements_LQA/mod/TokenAuth.php';

// Start session safely
SessionManager::start();

echo "<h2>Set Authentication Token</h2>";

// Handle token setting
if (isset($_GET['set_token']) && !empty($_GET['user'])) {
    $username = $_GET['user'];
    $token = TokenAuth::setTokenCookie($username);
    
    echo "<div style='background: lightgreen; padding: 10px; margin: 10px 0;'>";
    echo "✅ Token set for user: <strong>$username</strong><br>";
    echo "Token: <code style='font-size: 12px; word-break: break-all;'>$token</code>";
    echo "</div>";
    
    // Also set session for compatibility
    $_SESSION['USER'] = $username;
}

// Handle token clearing
if (isset($_GET['clear_token'])) {
    TokenAuth::clearTokenCookie();
    echo "<div style='background: lightcoral; padding: 10px; margin: 10px 0;'>";
    echo "🗑️ Token cleared";
    echo "</div>";
}

// Current status
echo "<h3>Current Status:</h3>";

$tokenUser = TokenAuth::getUserFromRequest();
$sessionUser = $_SESSION['USER'] ?? '';

echo "Session USER: " . ($sessionUser ?: 'Not set') . "<br>";
echo "Token USER: " . ($tokenUser ?: 'Not set') . "<br>";
echo "Session ID: " . session_id() . "<br>";

if (isset($_COOKIE['auth_token'])) {
    echo "Auth Token Cookie: <code style='font-size: 12px; word-break: break-all;'>{$_COOKIE['auth_token']}</code><br>";
} else {
    echo "Auth Token Cookie: Not set<br>";
}

// Available users
echo "<h3>Set Token for User:</h3>";
$users = ['khachhang', 'admin', 'lequocanh', 'manager1', 'staff2'];

foreach ($users as $user) {
    echo "<a href='?set_token=1&user=$user' style='margin-right: 10px; padding: 5px 10px; background: lightblue; text-decoration: none; border-radius: 3px;'>$user</a>";
}

echo "<br><br>";
echo "<a href='?clear_token=1' style='padding: 5px 10px; background: lightcoral; text-decoration: none; border-radius: 3px; color: white;'>Clear Token</a>";

// Test links
echo "<h3>Test Links:</h3>";
echo "<a href='administrator/elements_LQA/mthongbao/getNotificationsToken.php?action=list' target='_blank'>Test Token API</a><br>";
echo "<a href='administrator/elements_LQA/mthongbao/getCustomerNotifications.php?action=list' target='_blank'>Test Session API</a><br>";
echo "<a href='index.php'>Go to Index</a><br>";

// JavaScript test
echo "<h3>JavaScript Test:</h3>";
echo "<button onclick='testTokenAPI()'>Test Token API</button>";
echo "<button onclick='testSessionAPI()'>Test Session API</button>";
echo "<div id='testResult'></div>";

echo "<script>
function testTokenAPI() {
    console.log('Testing token API...');
    
    fetch('./administrator/elements_LQA/mthongbao/getNotificationsToken.php?action=list', {
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('Token API Response:', data);
        document.getElementById('testResult').innerHTML = 
            '<h4>Token API Result:</h4>' +
            '<p>Success: ' + data.success + '</p>' +
            '<p>Auth method: ' + (data.auth_method || 'N/A') + '</p>' +
            '<p>User ID: ' + (data.user_id || 'N/A') + '</p>' +
            '<p>Unread count: ' + (data.unread_count || 0) + '</p>' +
            '<p>Total notifications: ' + (data.total || 0) + '</p>';
    })
    .catch(error => {
        console.error('Token API Error:', error);
        document.getElementById('testResult').innerHTML = 
            '<h4>Token API Error:</h4>' +
            '<p style=\"color: red;\">' + error.message + '</p>';
    });
}

function testSessionAPI() {
    console.log('Testing session API...');
    
    fetch('./administrator/elements_LQA/mthongbao/getCustomerNotifications.php?action=list', {
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('Session API Response:', data);
        document.getElementById('testResult').innerHTML = 
            '<h4>Session API Result:</h4>' +
            '<p>Success: ' + data.success + '</p>' +
            '<p>Unread count: ' + (data.unread_count || 0) + '</p>' +
            '<p>Total notifications: ' + (data.total || 0) + '</p>';
    })
    .catch(error => {
        console.error('Session API Error:', error);
        document.getElementById('testResult').innerHTML = 
            '<h4>Session API Error:</h4>' +
            '<p style=\"color: red;\">' + error.message + '</p>';
    });
}
</script>";
?>
