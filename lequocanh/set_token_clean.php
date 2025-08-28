<?php
// Set authentication token for user - Clean version without early output
require_once './administrator/elements_LQA/mod/sessionManager.php';
require_once './administrator/elements_LQA/mod/TokenAuth.php';

// Start session safely
SessionManager::start();

$message = '';
$tokenSet = false;

// Handle token setting BEFORE any output
if (isset($_GET['set_token']) && !empty($_GET['user'])) {
    $username = $_GET['user'];
    $token = TokenAuth::setTokenCookie($username);
    
    $message = "✅ Token set for user: <strong>$username</strong><br>";
    $message .= "Token: <code style='font-size: 12px; word-break: break-all;'>$token</code>";
    $tokenSet = true;
    
    // Also set session for compatibility
    $_SESSION['USER'] = $username;
}

// Handle token clearing BEFORE any output
if (isset($_GET['clear_token'])) {
    TokenAuth::clearTokenCookie();
    $message = "🗑️ Token cleared";
}

// NOW start HTML output
?>
<!DOCTYPE html>
<html>
<head>
    <title>Set Authentication Token</title>
    <meta charset="utf-8">
</head>
<body>

<h2>Set Authentication Token</h2>

<?php if ($message): ?>
<div style='background: <?= $tokenSet ? 'lightgreen' : 'lightcoral' ?>; padding: 10px; margin: 10px 0;'>
    <?= $message ?>
</div>
<?php endif; ?>

<h3>Current Status:</h3>

<?php
$tokenUser = TokenAuth::getUserFromRequest();
$sessionUser = $_SESSION['USER'] ?? '';
?>

<p>Session USER: <?= $sessionUser ?: 'Not set' ?></p>
<p>Token USER: <?= $tokenUser ?: 'Not set' ?></p>
<p>Session ID: <?= session_id() ?></p>

<?php if (isset($_COOKIE['auth_token'])): ?>
<p>Auth Token Cookie: <code style='font-size: 12px; word-break: break-all;'><?= $_COOKIE['auth_token'] ?></code></p>
<?php else: ?>
<p>Auth Token Cookie: Not set</p>
<?php endif; ?>

<h3>Set Token for User:</h3>

<?php
$users = ['khachhang', 'admin', 'lequocanh', 'manager1', 'staff2'];
foreach ($users as $user): ?>
    <a href='?set_token=1&user=<?= $user ?>' style='margin-right: 10px; padding: 5px 10px; background: lightblue; text-decoration: none; border-radius: 3px;'><?= $user ?></a>
<?php endforeach; ?>

<br><br>
<a href='?clear_token=1' style='padding: 5px 10px; background: lightcoral; text-decoration: none; border-radius: 3px; color: white;'>Clear Token</a>

<h3>Test Links:</h3>
<a href='administrator/elements_LQA/mthongbao/getNotificationsToken.php?action=list' target='_blank'>Test Token API</a><br>
<a href='administrator/elements_LQA/mthongbao/getCustomerNotifications.php?action=list' target='_blank'>Test Session API</a><br>
<a href='index.php'>Go to Index</a><br>

<h3>JavaScript Test:</h3>
<button onclick='testTokenAPI()'>Test Token API</button>
<button onclick='testSessionAPI()'>Test Session API</button>
<div id='testResult'></div>

<script>
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
            '<p>Total notifications: ' + (data.total || 0) + '</p>' +
            '<p>Error: ' + (data.error || 'None') + '</p>';
    })
    .catch(error => {
        console.error('Token API Error:', error);
        document.getElementById('testResult').innerHTML = 
            '<h4>Token API Error:</h4>' +
            '<p style="color: red;">' + error.message + '</p>';
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
            '<p>Total notifications: ' + (data.total || 0) + '</p>' +
            '<p>Error: ' + (data.error || 'None') + '</p>';
    })
    .catch(error => {
        console.error('Session API Error:', error);
        document.getElementById('testResult').innerHTML = 
            '<h4>Session API Error:</h4>' +
            '<p style="color: red;">' + error.message + '</p>';
    });
}
</script>

</body>
</html>
