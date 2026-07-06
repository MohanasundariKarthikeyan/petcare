<?php
session_start();
// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'petdemo';

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Create reviews table if it doesn't exist
$create_table = "CREATE TABLE IF NOT EXISTS product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products2(id) ON DELETE CASCADE
)";

if (!$db->query($create_table)) {
    die("Error creating table: " . $db->error);
}

if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    
    $stmt = $db->prepare("SELECT * FROM product_reviews WHERE product_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode(['reviews' => $reviews]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Product ID required']);
}
?>