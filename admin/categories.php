<?php
require_once '../includes/db.php';
require_once '../includes/admin_auth.php';

// Check admin authentication
requireAdmin();

$pageTitle = "Categories Management";
$pageDescription = "Manage product categories for your store";

$successMessage = '';
$errorMessage = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        // Add new category
        $name = trim($_POST['category_name']);
        $description = trim($_POST['description']);
        
        if (empty($name)) {
            $errorMessage = "Category name is required.";
        } else {
            try {
                // Check if category already exists
                $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
                $stmt->execute([$name]);
                
                if ($stmt->fetch()) {
                    $errorMessage = "Category with this name already exists.";
                } else {
                    // Insert new category
                    $stmt = $pdo->prepare("
                        INSERT INTO categories (name, description, created_at) 
                        VALUES (?, ?, NOW())
                    ");
                    $stmt->execute([$name, $description]);
                    $successMessage = "Category added successfully!";
                }
            } catch (Exception $e) {
                $errorMessage = "Error adding category: " . $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['update_category'])) {
        // Update category
        $categoryId = (int)$_POST['category_id'];
        $name = trim($_POST['category_name']);
        $description = trim($_POST['description']);
        
        if (empty($name)) {
            $errorMessage = "Category name is required.";
        } else {
            try {
                // Check if another category with same name exists
                $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
                $stmt->execute([$name, $categoryId]);
                
                if ($stmt->fetch()) {
                    $errorMessage = "Another category with this name already exists.";
                } else {
                    // Update category
                    $stmt = $pdo->prepare("
                        UPDATE categories 
                        SET name = ?, description = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $description, $categoryId]);
                    $successMessage = "Category updated successfully!";
                }
            } catch (Exception $e) {
                $errorMessage = "Error updating category: " . $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['delete_category'])) {
        // Delete category
        $categoryId = (int)$_POST['category_id'];
        
        try {
            // Check if category has products
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
            $stmt->execute([$categoryId]);
            $productCount = $stmt->fetch()['count'];
            
            if ($productCount > 0) {
                $errorMessage = "Cannot delete category. It contains {$productCount} product(s). Please move or delete the products first.";
            } else {
                // Delete category
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$categoryId]);
                $successMessage = "Category deleted successfully!";
            }
        } catch (Exception $e) {
            $errorMessage = "Error deleting category: " . $e->getMessage();
        }
    }
}

// Get all categories with product count
try {
    $stmt = $pdo->query("
        SELECT c.*, 
               COUNT(p.id) as product_count,
               COUNT(CASE WHEN p.status = 'active' THEN 1 END) as active_products
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id 
        GROUP BY c.id 
        ORDER BY c.name ASC
    ");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
    $errorMessage = "Error loading categories: " . $e->getMessage();
}

// Get category for editing
$editCategory = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$editId]);
    $editCategory = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - HORAASTORE Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --border-radius: 8px;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: white;
            color: var(--gray-900);
            line-height: 1.6;
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem 2rem 2rem;
        }

        /* Admin Navbar Styles */
        .admin-navbar {
            background: white;
            border-bottom: 2px solid var(--primary-color);
            padding: 1rem 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .navbar-brand h2 {
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .navbar-menu {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .navbar-item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            color: var(--gray-700);
            font-weight: 500;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .navbar-item:hover {
            background: var(--gray-50);
            color: var(--primary-color);
        }

        .navbar-item.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .navbar-item.logout {
            margin-left: auto;
            color: var(--danger-color);
        }

        .navbar-item.logout:hover {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .admin-header {
            background: white;
            padding: 2rem 0;
            border-bottom: 1px solid var(--gray-200);
            margin-bottom: 2rem;
        }

        .admin-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .admin-header p {
            color: var(--gray-600);
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        .categories-section {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .section-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
        }

        .section-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
        }

        .categories-list {
            padding: 1.5rem;
        }

        .category-item {
            padding: 1rem;
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-200);
            margin-bottom: 1rem;
            transition: all 0.2s;
        }

        .category-item:hover {
            border-color: var(--primary-color);
            background: var(--gray-50);
        }

        .category-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }

        .category-name {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .category-description {
            color: var(--gray-600);
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
        }

        .category-stats {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
            color: var(--gray-500);
            margin-bottom: 0.75rem;
        }

        .category-actions {
            display: flex;
            gap: 0.5rem;
        }

        /* Form Styles */
        .form-section {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .form-content {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray-700);
        }

        .form-input, .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border: 1px solid transparent;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
            border-color: var(--gray-300);
        }

        .btn-secondary:hover {
            background: var(--gray-200);
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
            border-color: var(--danger-color);
        }

        .btn-danger:hover {
            background: #dc2626;
            border-color: #dc2626;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .no-categories {
            text-align: center;
            padding: 3rem;
            color: var(--gray-500);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Admin Navigation Bar -->
        <nav class="admin-navbar">
            <div class="navbar-brand">
                <h2>HORAASTORE Admin</h2>
            </div>
            <div class="navbar-menu">
                <a href="products.php" class="navbar-item">
                    <i class="fas fa-box"></i> Products
                </a>
                <a href="categories.php" class="navbar-item active">
                    <i class="fas fa-tags"></i> Categories
                </a>
                <a href="orders.php" class="navbar-item">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
                <a href="users.php" class="navbar-item">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="settings.php" class="navbar-item">
                    <i class="fas fa-cogs"></i> Settings
                </a>
                <a href="../index.php" class="navbar-item">
                    <i class="fas fa-home"></i> Visit Store
                </a>
                <a href="logout.php" class="navbar-item logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>

        <div class="admin-header">
            <h1><?php echo $pageTitle; ?></h1>
            <p><?php echo $pageDescription; ?></p>
        </div>

        <?php if ($successMessage): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <!-- Categories List -->
            <div class="categories-section">
                <div class="section-header">
                    <h2>All Categories (<?php echo count($categories); ?>)</h2>
                </div>
                <div class="categories-list">
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <div class="category-item">
                                <div class="category-name"><?php echo htmlspecialchars($category['name']); ?></div>
                                <?php if ($category['description']): ?>
                                    <div class="category-description"><?php echo htmlspecialchars($category['description']); ?></div>
                                <?php endif; ?>
                                <div class="category-stats">
                                    <span><i class="fas fa-box"></i> <?php echo $category['product_count']; ?> products</span>
                                    <span><i class="fas fa-check"></i> <?php echo $category['active_products']; ?> active</span>
                                    <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($category['created_at'])); ?></span>
                                </div>
                                <div class="category-actions">
                                    <a href="?edit=<?php echo $category['id']; ?>" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <?php if ($category['product_count'] == 0): ?>
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete this category?');">
                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                            <button type="submit" name="delete_category" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-categories">
                            <i class="fas fa-tags"></i>
                            <h3>No categories yet</h3>
                            <p>Start by adding your first category</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Add/Edit Category Form -->
            <div class="form-section">
                <div class="section-header">
                    <h2><?php echo $editCategory ? 'Edit Category' : 'Add New Category'; ?></h2>
                </div>
                <div class="form-content">
                    <form method="POST">
                        <?php if ($editCategory): ?>
                            <input type="hidden" name="category_id" value="<?php echo $editCategory['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="category_name">Category Name *</label>
                            <input type="text" id="category_name" name="category_name" class="form-input" 
                                   value="<?php echo $editCategory ? htmlspecialchars($editCategory['name']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-textarea" 
                                      placeholder="Optional description for this category"><?php echo $editCategory ? htmlspecialchars($editCategory['description']) : ''; ?></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" name="<?php echo $editCategory ? 'update_category' : 'add_category'; ?>" class="btn btn-primary">
                                <i class="fas fa-<?php echo $editCategory ? 'save' : 'plus'; ?>"></i> 
                                <?php echo $editCategory ? 'Update Category' : 'Add Category'; ?>
                            </button>
                            
                            <?php if ($editCategory): ?>
                                <a href="categories.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-close success messages
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-success');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>
