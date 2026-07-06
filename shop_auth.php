<?php
// Session configuration must be set BEFORE session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Enable this if using HTTPS
ini_set('session.use_strict_mode', 1);

// Start the session after configuration
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'petdemo');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Establish database connection
$dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if user is logged in as shop user
if (!isset($_SESSION['shop_loggedin']) || $_SESSION['shop_loggedin'] !== true || !isset($_SESSION['shop_user_id'])) {
    // Store the current URL for redirection after login
    $_SESSION['shop_redirect_url'] = $_SERVER['REQUEST_URI'];
    
    // Set the alert message
    $_SESSION['shop_login_message'] = "Please login to access the shop";
    $_SESSION['shop_login_message_type'] = 'warning';
    
    header("Location: shopping.php");
    exit();
}

// Check for session timeout (30 minutes)
if (isset($_SESSION['shop_last_login'])) {
    $inactive = 1800; // 30 minutes in seconds
    $session_life = time() - $_SESSION['shop_last_login'];
    if ($session_life > $inactive) {
        // Clear session data and destroy it
        session_unset();
        session_destroy();
        
        // Set logout message
        $_SESSION['shop_login_message'] = "Your session has expired due to inactivity";
        $_SESSION['shop_login_message_type'] = 'warning';
        
        header("Location: shopping.php");
        exit();
    }
}

// Update last activity time
$_SESSION['shop_last_login'] = time();

// Regenerate session ID periodically to prevent session fixation
if (!isset($_SESSION['shop_session_created'])) {
    $_SESSION['shop_session_created'] = time();
} elseif (time() - $_SESSION['shop_session_created'] > 1800) { // 30 minutes
    session_regenerate_id(true);
    $_SESSION['shop_session_created'] = time();
}

// Additional security headers (optional but recommended)
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
?>