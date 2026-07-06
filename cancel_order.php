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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    $user_id = $_SESSION['user_id'];
    
    // Check if the order belongs to the user
    $check_query = $db->prepare("SELECT * FROM orders7 WHERE id = ? AND user_id = ?");
    $check_query->bind_param("ii", $order_id, $user_id);
    $check_query->execute();
    $check_result = $check_query->get_result();
    
    if ($check_result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Order not found or does not belong to you']);
        exit();
    }
    
    // Update order status to cancelled
    $update_query = $db->prepare("UPDATE orders7 SET status = 'cancelled' WHERE id = ?");
    $update_query->bind_param("i", $order_id);
    
    if ($update_query->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to cancel order']);
    }
    
    $update_query->close();
    $check_query->close();
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$db->close();
?>