<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['quantity'])) {
    $id = $_POST['id'];
    $quantity = max(1, intval($_POST['quantity']));
    // Find the cart item by id (cart may be indexed by book id or be a list)
    if (isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]['quantity'] = $quantity;
    } else {
        // fallback: loop through cart if not directly indexed
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $id) {
                $_SESSION['cart'][$key]['quantity'] = $quantity;
                break;
            }
        }
    }
}
header('Location: cart.php');
exit();
