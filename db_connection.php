<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'petdemo');
define('DB_USER', 'root');
define('DB_PASS', '');

// Establish database connection
function getPDOConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => true // Optional: for better performance
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log the error securely (don't expose details to users)
            error_log("Database connection failed: " . $e->getMessage());
            
            // Display user-friendly message
            die("We're experiencing technical difficulties. Please try again later.");
        }
    }
    
    return $pdo;
}

// Helper function to execute prepared statements
function executeQuery($sql, $params = []) {
    $pdo = getPDOConnection();
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query failed: " . $e->getMessage() . " [SQL: $sql]");
        return false;
    }
}

// Helper function to fetch single row
function fetchSingle($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

// Helper function to fetch all rows
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetchAll() : false;
}
?>