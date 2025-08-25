<?php
session_start();
require_once '../includes/config.php';

// Simple check - bypass complex auth for now
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if (!$isLoggedIn) {
    echo "<h1>Admin Login Required</h1>";
    echo "<p>Please <a href='login.php'>login as admin</a> first.</p>";
    echo "<p>Default credentials: admin / admin123</p>";
    echo "<p>If you haven't set up admin yet, <a href='setup.php'>run setup first</a>.</p>";
    exit;
}

$pdo = Database::getInstance()->getConnection();

// Get products
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name,
           (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.created_at DESC 
    LIMIT 50
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get stats
$statsStmt = $pdo->query("
    SELECT 
        COUNT(*) as total_products,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_products,
        SUM(CASE WHEN featured = 1 THEN 1 ELSE 0 END) as featured_products
    FROM products
");
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .header { border-bottom: 1px solid #ddd; padding-bottom: 20px; margin-bottom: 20px; }
        .stats { display: flex; gap: 20px; margin-bottom: 20px; }
        .stat-card { background: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center; flex: 1; }
        .stat-number { font-size: 24px; font-weight: bold; color: #333; }
        .stat-label { color: #666; font-size: 14px; }
        .nav-links { margin-bottom: 20px; }
        .nav-links a { display: inline-block; padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px; }
        .nav-links a:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        .product-image { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; }
        .status { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .featured { color: #856404; font-weight: bold; }
        .btn { padding: 4px 8px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Products Management</h1>
            <p>Manage all products in your store</p>
        </div>
        
        <div class="nav-links">
            <a href="featured-products.php">Featured Products</a>
            <a href="products.php">All Products</a>
            <a href="../index.php">View Store</a>
            <a href="logout.php">Logout</a>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_products']); ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['active_products']); ?></div>
                <div class="stat-label">Active Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['featured_products']); ?></div>
                <div class="stat-label">Featured Products</div>
            </div>
        </div>

        <?php if (empty($products)): ?>
            <p>No products found. <a href="add-product.php">Add your first product</a>.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <?php if ($product['image']): ?>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                <?php endif; ?>
                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                <?php if ($product['sku']): ?>
                                    <br><small>SKU: <?php echo htmlspecialchars($product['sku']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?></td>
                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo number_format($product['stock_quantity']); ?></td>
                            <td>
                                <span class="status status-<?php echo $product['status']; ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($product['featured']): ?>
                                    <span class="featured">Featured</span>
                                <?php else: ?>
                                    <span>-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">Edit</a>
                                <a href="?toggle_featured=<?php echo $product['id']; ?>" class="btn btn-warning">
                                    <?php echo $product['featured'] ? 'Unfeature' : 'Feature'; ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
