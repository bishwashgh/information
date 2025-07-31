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
    <title>Cart Details</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        table { width: 80%; border-collapse: collapse; margin: 20px auto; }
        th, td { padding: 12px; border: 1px solid #ccc; text-align: center; }
        th { background: #f4f4f4; }
        .total { font-weight: bold; }
        .empty { text-align: center; margin-top: 40px; font-size: 1.2em; color: #888; }
        img { max-width: 80px; max-height: 120px; }
    </style>
</head>
<body>
    <h2 style="text-align:center;">Cart Details</h2>
    <?php if (empty($cart)): ?>
        <div class="empty">Your cart is empty.</div>
    <?php else: ?>
        <table>
            <tr>
                <th>Image</th>
                <th>Title</th>
                <th>Author</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
            </tr>
            <?php foreach ($cart as $item): ?>
                <?php $subtotal = $item['price'] * $item['quantity']; $total += $subtotal; ?>
                <tr>
                    <td><img src="<?= $item['image'] ?? 'images/default_book.png' ?>" alt="<?= $item['title'] ?>"></td>
                    <td><?= $item['title'] ?></td>
                    <td><?= $item['author'] ?></td>
                    <td>$<?= number_format($item['price'], 2) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td>$<?= number_format($subtotal, 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="5" class="total">Total</td>
                <td class="total">$<?= number_format($total, 2) ?></td>
            </tr>
        </table>
    <?php endif; ?>
    <div style="text-align:center; margin-top:30px;">
        <a href="allbooks.php">Continue Shopping</a>
    </div>
</body>
</html>
