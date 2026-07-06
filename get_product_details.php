<?php
session_start();
// Database connection - REPLACE WITH YOUR ACTUAL CREDENTIALS
$db_host = 'localhost';
$db_user = 'root'; // Change this
$db_pass = ''; // Change this
$db_name = 'petdemo';

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Get product ID from request
if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    
    // Fetch product details
    $stmt = $db->prepare("SELECT * FROM products2 WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode($product);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Product ID not provided']);
}

$db->close();
?>