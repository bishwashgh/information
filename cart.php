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
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="style.css"> <!-- Optional: Add your CSS file -->
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        table { width: 80%; border-collapse: collapse; margin: 20px auto; }
        th, td { padding: 12px; border: 1px solid #ccc; text-align: center; }
        th { background: #f4f4f4; }
        .total { font-weight: bold; }
        .empty { text-align: center; margin-top: 40px; font-size: 1.2em; color: #888; }
        .actions a { color: #d00; text-decoration: none; }
    </style>
</head>
<body>
    <h2 style="text-align:center;">Your Shopping Cart</h2>
    <?php if (empty($cart)): ?>
        <div class="empty">Your cart is empty.</div>
    <?php else: ?>
        <table>
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($cart as $item): ?>
                <?php $subtotal = $item['price'] * $item['quantity']; $total += $subtotal; ?>
                <tr>
                    <td><?= htmlspecialchars($item['title']) ?></td>
                    <td><?= htmlspecialchars($item['author']) ?></td>
                    <td>$<?= number_format($item['price'], 2) ?></td>
                    <td>
    <form method="post" action="update_cart.php" style="display:inline-block;">
        <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']) ?>">
        <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" style="width:60px;">
        <button type="submit" style="padding:2px 8px;">Update</button>
    </form>
</td>
                    <td>$<?= number_format($subtotal, 2) ?></td>
                    <td class="actions">
                        <!-- You can add update/remove actions here -->
                        <a href="remove_from_cart.php?id=<?= urlencode($item['id']) ?>" onclick="return confirm('Remove this book from cart?');">Remove</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="4" class="total">Total</td>
                <td class="total">$<?= number_format($total, 2) ?></td>
                <td></td>
            </tr>
        </table>
        <div style="text-align:center; margin-top:20px;">
            <a href="cartdetails.php" style="padding:10px 20px; background:#28a745; color:#fff; text-decoration:none; border-radius:4px;">Proceed to Checkout</a>
        </div>
    <?php endif; ?>
    <div style="text-align:center; margin-top:30px;">
        <a href="allbooks.php">Continue Shopping</a>
    </div>
</body>
</html>
