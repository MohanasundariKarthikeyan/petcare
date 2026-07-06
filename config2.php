<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'petdemo');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create connection
$servername = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$dbname = DB_NAME;
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create the `appointments` table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS appointments (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pet_name VARCHAR(50) NOT NULL,
    owner_name VARCHAR(50) NOT NULL,
    breed VARCHAR(50) NOT NULL,
    email VARCHAR(50) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";   

if ($conn->query($sql) === FALSE) {
    die("Error creating table: " . $conn->error);
}

// Handle AJAX request to fetch booked times for a specific date
if (isset($_GET['date'])) {
    $date = $_GET['date'];
    $booked_times = [];
    
    // Ensure the bookform table exists
    $checkBookformTable = "CREATE TABLE IF NOT EXISTS bookform (
        id INT AUTO_INCREMENT PRIMARY KEY,
        petsname VARCHAR(100),
        ownername VARCHAR(100),
        category VARCHAR(100),
        email VARCHAR(100),
        phoneno VARCHAR(15),
        `date` DATE,
        `time` TIME
    )";
    $conn->query($checkBookformTable);
    
    // Use backticks for field names and no quotes around column names
    $booked_stmt = $conn->prepare("SELECT `time` FROM bookform WHERE `date` = ?");
    $booked_stmt->bind_param("s", $date);
    $booked_stmt->execute();
    $result = $booked_stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $booked_times[] = $row['time'];
    }
    $booked_stmt->close();
    
    // Return booked times as JSON
    header('Content-Type: application/json');
    echo json_encode($booked_times);
    exit;
}

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$sql = "CREATE TABLE IF NOT EXISTS carts4 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES product(id)
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating cart table: " . $conn->error);
}
?>