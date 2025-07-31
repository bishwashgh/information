<?php
session_start();
if (isset($_SESSION['cart'])) {
    $cart = $_SESSION['cart'];
} else {
    $cart = [];
}
$total = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart View</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .cart-books {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            justify-content: center;
            margin-top: 30px;
        }
        .book-card {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 18px 24px;
            width: 260px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .book-card img {
            max-width: 120px;
            max-height: 170px;
            margin-bottom: 15px;
            border-radius: 6px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .book-info {
            text-align: center;
        }
        .book-title {
            font-size: 1.08em;
            font-weight: bold;
            margin-bottom: 7px;
            color: #333;
        }
        .book-author {
            font-size: 0.95em;
            color: #555;
            margin-bottom: 8px;
        }
        .book-price {
            font-size: 1.08em;
            color: #2e7d32;
            margin-bottom: 5px;
        }
        .book-quantity {
            font-size: 0.96em;
            color: #444;
        }
        .total { font-weight: bold; }
        .empty { text-align: center; margin-top: 40px; font-size: 1.2em; color: #888; }
    </style>
</head>
<body>
    <h2 style="text-align:center;">Books Proceeded to Checkout</h2>
    <?php if (empty($cart)): ?>
        <div class="empty">No books have been proceeded to checkout.</div>
    <?php else: ?>
        <div class="cart-books">
            <?php $total = 0; foreach ($cart as $item): ?>
    <?php $subtotal = $item['price'] * $item['quantity']; $total += $subtotal; ?>
    <div class="book-card">
        <?php
        $imgPath = '';
        if (!empty($item['image'])) {
            $imgPath = $item['image'];
        } else {
            
            include_once 'dbconnect.php';
            $bid = intval($item['id']);
            $imgQ = mysqli_query($conn, "SELECT image FROM books WHERE id=$bid LIMIT 1");
            if ($imgQ && $imgR = mysqli_fetch_assoc($imgQ)) {
                $imgPath = $imgR['image'] ?: 'images/default_book.png';
            } else {
                $imgPath = 'images/default_book.png';
            }
        }
        ?>
        <img src="<?= $imgPath ?>" alt="<?= $item['title'] ?>">
        <div class="book-info">
            <div class="book-title"><?= $item['title'] ?></div>
            <div class="book-author">By <?= $item['author'] ?></div>
            <div class="book-price">$<?= number_format($item['price'], 2) ?></div>
            <div class="book-quantity">Quantity: <?= $item['quantity'] ?></div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<div class="total" style="text-align:center; font-size:1.2em; margin-top:20px;">Total: $<?= number_format($total, 2) ?></div>
    <?php endif; ?>
    <div style="text-align:center; margin-top:30px;">
        <a href="allbooks.php">Continue Shopping</a>
    </div>
</body>
</html>
