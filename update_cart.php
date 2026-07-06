<?php
session_start();

if (isset($_GET['index']) && isset($_GET['quantity'])) {
    $index = (int)$_GET['index'];
    $quantity = (int)$_GET['quantity'];
    
    if (isset($_SESSION['cart'][$index])) {
        $_SESSION['cart'][$index]['quantity'] = $quantity;
    }
}

header('Location: cart.php');
exit();
?>