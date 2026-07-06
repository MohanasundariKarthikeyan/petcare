<?php
// logout.php - Enhanced logout with better security

// Start session
session_start();

// Log logout activity (optional)
if (isset($_SESSION['user_id'])) {
    // You can log this to a database or file
    error_log("User " . $_SESSION['user_id'] . " logged out at " . date('Y-m-d H:i:s'));
}

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear any additional cookies if needed
setcookie('remember_me', '', time() - 3600, '/');

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login page with success message
header("Location: shopping.php?message=logout_success");
exit();
?>