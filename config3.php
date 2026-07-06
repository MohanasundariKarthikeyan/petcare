<?php
// config.php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'petdemo');
define('DB_USER', 'root');
define('DB_PASS', '');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create database connection
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
        } catch (Exception $e) {
            die("Database error: " . $e->getMessage());
        }
    }
    
    return $conn;
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['username']);
}

// Secure input function
function sanitizeInput($data) {
    $conn = getDBConnection();
    return htmlspecialchars(stripslashes(trim($conn->real_escape_string($data))));
}
?>