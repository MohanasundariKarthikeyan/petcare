<?php
session_start();

// After successful login
if (isset($_SESSION['login_redirect'])) {
    $redirect = $_SESSION['login_redirect'];
    unset($_SESSION['login_redirect']);
    header("Location: $redirect");
    exit();
} else {
    header("Location: cart.php");
    exit();
}