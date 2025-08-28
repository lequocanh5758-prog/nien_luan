<?php
// Debug session file
require_once './administrator/elements_LQA/mod/sessionManager.php';

// Start session safely
SessionManager::start();

echo "<h2>Session Debug</h2>";
echo "<h3>Session ID: " . session_id() . "</h3>";
echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Set Test Session</h3>";
if (isset($_GET['set_user'])) {
    $_SESSION['USER'] = $_GET['set_user'];
    echo "Session USER set to: " . $_SESSION['USER'] . "<br>";
    echo "<a href='debug_session.php'>Refresh</a><br>";
}

echo "<form method='get'>";
echo "Set USER session: <input type='text' name='set_user' value='khaochang'>";
echo "<input type='submit' value='Set'>";
echo "</form>";

echo "<br><a href='index.php'>Go to Index</a>";
echo "<br><a href='test_notifications.php'>Go to Test Notifications</a>";
echo "<br><a href='administrator/elements_LQA/mthongbao/getCustomerNotifications.php?action=list'>Test API</a>";
?>
