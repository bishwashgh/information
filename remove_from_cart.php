<?php
session_start();

if (!isset($_GET['id'])) {
    header('Location: cart.php');
    exit();
}

$book_id = intval($_GET['id']);

if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] == $book_id) {
            unset($_SESSION['cart'][$key]);
            // Re-index array
            $_SESSION['cart'] = array_values($_SESSION['cart']);
            break;
        }
    }
}

header('Location: cart.php');
exit();
