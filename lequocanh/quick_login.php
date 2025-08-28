<?php
// Quick login for testing
require_once './administrator/elements_LQA/mod/sessionManager.php';

// Start session safely
SessionManager::start();

if (isset($_POST['username'])) {
    $_SESSION['USER'] = $_POST['username'];
    echo "Logged in as: " . $_SESSION['USER'] . "<br>";
    echo "<a href='index.php'>Go to Index</a><br>";
    echo "<a href='test_notifications.php'>Test Notifications</a><br>";
} else {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quick Login</title>
</head>
<body>
    <h2>Quick Login for Testing</h2>
    <form method="post">
        <label>Username:</label>
        <input type="text" name="username" value="khaochang" required>
        <input type="submit" value="Login">
    </form>
    
    <h3>Current Session:</h3>
    <pre><?php print_r($_SESSION); ?></pre>
</body>
</html>
<?php
}
?>
