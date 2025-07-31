<?php
session_start();
include 'dbconnect.php';

if (!isset($_GET['id'])) {
    header('Location: allbooks.php');
    exit();
}

$book_id = intval($_GET['id']);

// Fetch book details from database
$sql = "SELECT * FROM books WHERE id = $book_id";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) === 1) {
    $book = mysqli_fetch_assoc($result);
    $cart_item = [
        'id' => $book['id'],
        'title' => $book['title'],
        'author' => $book['author'],
        'price' => $book['price'],
        'quantity' => 1
    ];
    // Initialize cart if not set
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    // Check if book already in cart
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] == $book_id) {
            $item['quantity']++;
            $found = true;
            break;
        }
    }
    unset($item); // break reference
    if (!$found) {
        $_SESSION['cart'][] = $cart_item;
    }
}

header('Location: cart.php');
exit();
