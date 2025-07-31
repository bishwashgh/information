<?php
session_start();
include 'dbconnect.php';


if (!isset($_SESSION['uid'])) {
    
    header("Location: login.php");
    exit();
}


$userId = intval($_SESSION['uid']);

if (isset($_SESSION['cart'])) {
    $cart = $_SESSION['cart'];
} else {
    $cart = [];
}


if (!empty($cart)) {
    
    unset($_SESSION['cart']);
    
    
    header("Location: order_confirmation.php");
    exit();
} else {
    
    header("Location: allbooks.php");
    exit();
}
?>
