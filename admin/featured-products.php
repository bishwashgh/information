<?php
require_once '../includes/config.php';
require_once '../includes/admin_auth.php';

// Check if user is admin
requireAdmin();

// Get database connection
$pdo = Database::getInstance()->getConnection();

$pageTitle = 'Featured Products Management';
$pageDescription = 'Manage featured products display on homepage';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['toggle_featured'])) {
            // Toggle featured status
            $productId = (int)$_POST['product_id'];
            $currentStatus = (int)$_POST['current_status'];
            $newStatus = $currentStatus === 1 ? 0 : 1;
            
            $stmt = $pdo->prepare("UPDATE products SET featured = ? WHERE id = ?");
            $stmt->execute([$newStatus, $productId]);
            
            $successMessage = "Product featured status updated successfully!";
        }
        
        if (isset($_POST['update_featured_order']) && $hasOrderColumn) {
            // Update featured order (only if column exists)
            $productId = (int)$_POST['product_id'];
            $featuredOrder = (int)$_POST['featured_order'];
            
            $stmt = $pdo->prepare("UPDATE products SET featured_order = ? WHERE id = ?");
            $stmt->execute([$featuredOrder, $productId]);
            
            $successMessage = "Featured order updated successfully!";
        }
        
    } catch (Exception $e) {
        $errorMessage = "Error: " . $e->getMessage();
    }
}

// Check if featured_order column exists
$hasOrderColumn = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'featured_order'");
    $hasOrderColumn = $stmt->rowCount() > 0;
} catch (Exception $e) {
    $hasOrderColumn = false;
}

// Get all products with featured info
$orderClause = $hasOrderColumn ? "p.featured DESC, p.featured_order ASC, p.name ASC" : "p.featured DESC, p.name ASC";
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name, 
           (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image,
           CASE WHEN p.featured = 1 THEN 1 ELSE 0 END as is_featured_bool
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'active'
    ORDER BY $orderClause
");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current featured products count
$stmt = $pdo->prepare("SELECT COUNT(*) as featured_count FROM products WHERE featured = 1 AND status = 'active'");
$stmt->execute();
$featuredCount = $stmt->fetch(PDO::FETCH_ASSOC)['featured_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Panel</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Nunito:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            /* Colors */
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --accent-color: #f59e0b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            
            /* Neutral Colors */
            --white: #ffffff;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            
            /* Typography */
            --font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-family-heading: 'Nunito', 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            
            /* Spacing */
            --spacing-2: 0.5rem;
            --spacing-3: 0.75rem;
            --spacing-4: 1rem;
            --spacing-5: 1.25rem;
            --spacing-6: 1.5rem;
            --spacing-8: 2rem;
            --spacing-12: 3rem;
            --spacing-16: 4rem;
            --spacing-20: 5rem;
            
            /* Border Radius */
            --border-radius: 8px;
            --border-radius-lg: 12px;
            --border-radius-xl: 16px;
        }

        body {
            font-family: var(--font-family);
            background-color: #ffffff;
            color: var(--gray-700);
            line-height: 1.6;
        }

        /* Header */
        .admin-header {
            background: #ffffff;
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-200);
            margin-bottom: 2rem;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--gray-900);
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: var(--gray-600);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: var(--gray-100);
            color: var(--gray-900);
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Page Header */
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #333333;
            margin-bottom: 1rem;
        }

        .page-description {
            font-size: 1.2rem;
            color: #666666;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: #ffffff;
            padding: 1.5rem;
            border-radius: var(--border-radius-lg);
            border: 1px solid var(--gray-200);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--gray-600);
            font-weight: 500;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        /* Products Table */
        .products-section {
            background: #ffffff;
            border-radius: var(--border-radius-lg);
            border: 1px solid var(--gray-200);
            overflow: hidden;
        }

        .section-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .section-subtitle {
            color: var(--gray-600);
        }

        .table-container {
            overflow-x: auto;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
        }

        .products-table th,
        .products-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .products-table th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-700);
        }

        .products-table tr:hover {
            background: var(--gray-50);
        }

        /* Product Info */
        .product-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .product-image {
            width: 60px;
            height: 60px;
            border-radius: var(--border-radius);
            object-fit: cover;
        }

        .product-image-placeholder {
            width: 60px;
            height: 60px;
            border-radius: var(--border-radius);
            background: var(--gray-100);
            border: 2px dashed var(--gray-300);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-400);
            font-size: 1.5rem;
        }

        .product-details h4 {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .product-details .category {
            font-size: 0.875rem;
            color: var(--gray-500);
        }

        /* Status Badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-featured {
            background: #dcfce7;
            color: #166534;
        }

        .status-not-featured {
            background: var(--gray-100);
            color: var(--gray-600);
        }

        /* Form Elements */
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }

        .form-input {
            width: 60px;
            padding: 0.25rem 0.5rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            font-size: 0.875rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .page-title {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .products-table {
                font-size: 0.875rem;
            }

            .product-info {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="header-content">
            <a href="index.php" class="logo">
                <i class="fas fa-cog"></i> Admin Panel
            </a>
            <nav class="nav-links">
                <a href="index.php"><i class="fas fa-dashboard"></i> Dashboard</a>
                <a href="products.php"><i class="fas fa-box"></i> Products</a>
                <a href="featured-products.php" class="active"><i class="fas fa-star"></i> Featured</a>
                <a href="categories.php"><i class="fas fa-tags"></i> Categories</a>
                <a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                <a href="users.php"><i class="fas fa-users"></i> Users</a>
                <a href="../index.php"><i class="fas fa-home"></i> View Site</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Featured Products Management</h1>
            <p class="page-description">Manage which products appear in the featured section on your homepage</p>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $featuredCount; ?></div>
                <div class="stat-label">Featured Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($products); ?></div>
                <div class="stat-label">Total Active Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">8</div>
                <div class="stat-label">Recommended Limit</div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <!-- Products Section -->
        <div class="products-section">
            <div class="section-header">
                <h2 class="section-title">All Products</h2>
                <p class="section-subtitle">Toggle featured status and manage display order for homepage</p>
            </div>

            <div class="table-container">
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <?php if (!empty($product['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 class="product-image">
                                        <?php else: ?>
                                            <div class="product-image-placeholder">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="product-details">
                                            <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                            <div class="category"><?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?></td>
                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                <td>
                                    <?php if ($product['featured'] == 1): ?>
                                        <span class="status-badge status-featured">
                                            <i class="fas fa-star"></i> Featured
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-not-featured">
                                            Not Featured
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product['featured'] == 1): ?>
                                        <?php if ($hasOrderColumn): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="number" name="featured_order" value="<?php echo $product['featured_order'] ?? 0; ?>" 
                                                       class="form-input" min="0" max="100" style="width: 60px;">
                                                <button type="submit" name="update_featured_order" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-save"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="status-badge status-featured">Auto</span>
                                            <small style="display: block; color: var(--gray-500); font-size: 10px;">Run setup to enable ordering</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: var(--gray-400);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $product['featured']; ?>">
                                        <?php if ($product['featured'] == 1): ?>
                                            <button type="submit" name="toggle_featured" class="btn btn-danger btn-sm">
                                                <i class="fas fa-star-o"></i> Remove
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" name="toggle_featured" class="btn btn-success btn-sm">
                                                <i class="fas fa-star"></i> Feature
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);

            // Confirm before removing featured status
            $('button[name="toggle_featured"]').click(function(e) {
                const isRemoving = $(this).text().includes('Remove');
                if (isRemoving) {
                    if (!confirm('Are you sure you want to remove this product from featured?')) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
</body>
</html>
