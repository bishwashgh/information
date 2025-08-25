<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';
require_once '../includes/admin_auth.php';

// Debug: Check if functions exist
if (!function_exists('requireAdmin')) {
    die('Error: requireAdmin function not found. Please check admin_auth.php');
}

// Check if user is admin
requireAdmin();

// Get database connection
$pdo = Database::getInstance()->getConnection();

$pageTitle = 'Products Management';
$pageDescription = 'Manage all products in your store';

// Handle form submissions
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_product'])) {
            // Add new product
            $name = trim($_POST['product_name']);
            $description = trim($_POST['description']);
            $short_description = trim($_POST['short_description']);
            $category_id = (int)$_POST['category_id'];
            $price = (float)$_POST['price'];
            $sale_price = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
            $sku = trim($_POST['sku']);
            $stock_quantity = (int)$_POST['stock_quantity'];
            $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : null;
            $dimensions = trim($_POST['dimensions']);
            $status = $_POST['status'];
            $featured = isset($_POST['featured']) ? 1 : 0;
            
            // Generate slug from name
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            
            // Validate required fields
            if (empty($name) || empty($price) || $category_id <= 0) {
                throw new Exception('Please fill in all required fields (Name, Price, Category).');
            }
            
            // Check if SKU already exists (if provided)
            if (!empty($sku)) {
                $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = ?");
                $stmt->execute([$sku]);
                if ($stmt->fetch()) {
                    throw new Exception('SKU already exists. Please use a unique SKU.');
                }
            }
            
            // Insert product
            $stmt = $pdo->prepare("
                INSERT INTO products (name, slug, description, short_description, category_id, price, sale_price, 
                                    sku, stock_quantity, weight, dimensions, featured, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $name, $slug, $description, $short_description, $category_id, $price, $sale_price,
                $sku, $stock_quantity, $weight, $dimensions, $featured, $status
            ]);
            
            $productId = $pdo->lastInsertId();
            
            // Handle image upload if provided
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../assets/images/products/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileExtension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($fileExtension, $allowedExtensions)) {
                    $fileName = 'product_' . $productId . '_' . time() . '.' . $fileExtension;
                    $uploadPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $uploadPath)) {
                        // Insert image record
                        $imageUrl = 'assets/images/products/' . $fileName;
                        $stmt = $pdo->prepare("
                            INSERT INTO product_images (product_id, image_url, is_primary, sort_order, created_at)
                            VALUES (?, ?, 1, 0, NOW())
                        ");
                        $stmt->execute([$productId, $imageUrl]);
                    }
                }
            }
            
            $successMessage = "Product added successfully!";
        }
        
        if (isset($_POST['delete_product'])) {
            // Delete product
            $productId = (int)$_POST['product_id'];
            
            // First delete related images
            $stmt = $pdo->prepare("DELETE FROM product_images WHERE product_id = ?");
            $stmt->execute([$productId]);
            
            // Then delete the product
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            
            $successMessage = "Product deleted successfully!";
        }
        
        if (isset($_POST['toggle_status'])) {
            // Toggle product status
            $productId = (int)$_POST['product_id'];
            $currentStatus = $_POST['current_status'];
            $newStatus = $currentStatus === 'active' ? 'inactive' : 'active';
            
            $stmt = $pdo->prepare("UPDATE products SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $productId]);
            
            $successMessage = "Product status updated successfully!";
        }
        
        if (isset($_POST['toggle_featured'])) {
            // Toggle featured status
            $productId = (int)$_POST['product_id'];
            $currentFeatured = (int)$_POST['current_featured'];
            $newFeatured = $currentFeatured === 1 ? 0 : 1;
            
            $stmt = $pdo->prepare("UPDATE products SET featured = ? WHERE id = ?");
            $stmt->execute([$newFeatured, $productId]);
            
            $successMessage = "Featured status updated successfully!";
        }
        
        if (isset($_POST['edit_product'])) {
            // Edit existing product
            $productId = (int)$_POST['product_id'];
            $name = trim($_POST['product_name']);
            $description = trim($_POST['description']);
            $short_description = trim($_POST['short_description']);
            $category_id = (int)$_POST['category_id'];
            $price = (float)$_POST['price'];
            $sale_price = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
            $sku = trim($_POST['sku']);
            $stock_quantity = (int)$_POST['stock_quantity'];
            $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : null;
            $dimensions = trim($_POST['dimensions']);
            $status = $_POST['status'];
            $featured = isset($_POST['featured']) ? 1 : 0;
            
            // Generate slug from name
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            
            // Validate required fields
            if (empty($name) || empty($price) || $category_id <= 0) {
                throw new Exception('Please fill in all required fields (Name, Price, Category).');
            }
            
            // Check if SKU already exists (excluding current product)
            if (!empty($sku)) {
                $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
                $stmt->execute([$sku, $productId]);
                if ($stmt->fetch()) {
                    throw new Exception('SKU already exists. Please use a unique SKU.');
                }
            }
            
            // Update product
            $stmt = $pdo->prepare("
                UPDATE products SET 
                    name = ?, slug = ?, description = ?, short_description = ?, category_id = ?, 
                    price = ?, sale_price = ?, sku = ?, stock_quantity = ?, weight = ?, 
                    dimensions = ?, featured = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $name, $slug, $description, $short_description, $category_id, $price, $sale_price,
                $sku, $stock_quantity, $weight, $dimensions, $featured, $status, $productId
            ]);
            
            // Handle image upload if provided
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../assets/images/products/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileExtension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($fileExtension, $allowedExtensions)) {
                    $fileName = 'product_' . $productId . '_' . time() . '.' . $fileExtension;
                    $uploadPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $uploadPath)) {
                        // Remove old primary image
                        $stmt = $pdo->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?");
                        $stmt->execute([$productId]);
                        
                        // Insert new image record
                        $imageUrl = 'assets/images/products/' . $fileName;
                        $stmt = $pdo->prepare("
                            INSERT INTO product_images (product_id, image_url, is_primary, sort_order, created_at)
                            VALUES (?, ?, 1, 0, NOW())
                        ");
                        $stmt->execute([$productId, $imageUrl]);
                    }
                }
            }
            
            $successMessage = "Product updated successfully!";
        }
        
        if (isset($_POST['bulk_action']) && isset($_POST['selected_products'])) {
            // Handle bulk actions
            $action = $_POST['bulk_action'];
            $selectedProducts = $_POST['selected_products'];
            
            if (!empty($selectedProducts) && in_array($action, ['activate', 'deactivate', 'feature', 'unfeature', 'delete'])) {
                $placeholders = str_repeat('?,', count($selectedProducts) - 1) . '?';
                
                switch ($action) {
                    case 'activate':
                        $stmt = $pdo->prepare("UPDATE products SET status = 'active' WHERE id IN ($placeholders)");
                        $stmt->execute($selectedProducts);
                        $successMessage = "Selected products activated successfully!";
                        break;
                        
                    case 'deactivate':
                        $stmt = $pdo->prepare("UPDATE products SET status = 'inactive' WHERE id IN ($placeholders)");
                        $stmt->execute($selectedProducts);
                        $successMessage = "Selected products deactivated successfully!";
                        break;
                        
                    case 'feature':
                        $stmt = $pdo->prepare("UPDATE products SET featured = 1 WHERE id IN ($placeholders)");
                        $stmt->execute($selectedProducts);
                        $successMessage = "Selected products marked as featured!";
                        break;
                        
                    case 'unfeature':
                        $stmt = $pdo->prepare("UPDATE products SET featured = 0 WHERE id IN ($placeholders)");
                        $stmt->execute($selectedProducts);
                        $successMessage = "Selected products removed from featured!";
                        break;
                        
                    case 'delete':
                        // Delete related images first
                        $stmt = $pdo->prepare("DELETE FROM product_images WHERE product_id IN ($placeholders)");
                        $stmt->execute($selectedProducts);
                        
                        // Then delete products
                        $stmt = $pdo->prepare("DELETE FROM products WHERE id IN ($placeholders)");
                        $stmt->execute($selectedProducts);
                        $successMessage = "Selected products deleted successfully!";
                        break;
                }
            }
        }
        
    } catch (Exception $e) {
        $errorMessage = "Error: " . $e->getMessage();
    }
}

// Handle search and filtering
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';
$featured_filter = $_GET['featured'] ?? '';

// Build WHERE clause
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category_filter)) {
    $whereConditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($status_filter)) {
    $whereConditions[] = "p.status = ?";
    $params[] = $status_filter;
}

if ($featured_filter !== '') {
    $whereConditions[] = "p.featured = ?";
    $params[] = (int)$featured_filter;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get total products count
$countSql = "SELECT COUNT(*) as total FROM products p $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalProducts = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;
$totalPages = ceil($totalProducts / $perPage);

// Get products with pagination
$sql = "
    SELECT p.*, c.name as category_name,
           (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    $whereClause
    ORDER BY p.created_at DESC 
    LIMIT $perPage OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$categoriesStmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$statsStmt = $pdo->query("
    SELECT 
        COUNT(*) as total_products,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_products,
        SUM(CASE WHEN featured = 1 THEN 1 ELSE 0 END) as featured_products,
        SUM(CASE WHEN stock_quantity <= 5 THEN 1 ELSE 0 END) as low_stock_products
    FROM products
");
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Ensure all stats have default values to prevent null warnings
$stats = [
    'total_products' => (int)($stats['total_products'] ?? 0),
    'active_products' => (int)($stats['active_products'] ?? 0),
    'featured_products' => (int)($stats['featured_products'] ?? 0),
    'low_stock_products' => (int)($stats['low_stock_products'] ?? 0)
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --primary-dark: #5a6fd8;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
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

        .admin-nav {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .nav-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--gray-100);
            color: var(--gray-700);
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-link:hover {
            background: var(--primary-color);
            color: white;
        }

        .nav-link.active {
            background: var(--primary-color);
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow);
        }

        .stat-card .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
            color: white;
        }

        .stat-card.total .stat-icon { background: var(--info-color); }
        .stat-card.active .stat-icon { background: var(--success-color); }
        .stat-card.featured .stat-icon { background: var(--warning-color); }
        .stat-card.low-stock .stat-icon { background: var(--danger-color); }

        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .stat-card .stat-label {
            color: var(--gray-600);
            font-weight: 500;
        }

        .filters-section {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-200);
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .form-input, .form-select {
            padding: 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .products-section {
            background: white;
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .section-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
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
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

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

        .product-details .sku {
            font-size: 0.875rem;
            color: var(--gray-500);
            font-family: monospace;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .status-inactive {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .status-draft {
            background: rgba(107, 114, 128, 0.1);
            color: var(--gray-600);
        }

        .status-featured {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .status-not-featured {
            background: var(--gray-100);
            color: var(--gray-500);
        }

        .stock-status {
            font-weight: 500;
        }

        .stock-low {
            color: var(--danger-color);
        }

        .stock-medium {
            color: var(--warning-color);
        }

        .stock-good {
            color: var(--success-color);
        }

        /* Bulk Actions Styles */
        .bulk-actions {
            margin-bottom: 1rem;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-200);
        }

        .bulk-actions select {
            min-width: 150px;
        }

        #selected-count {
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Checkbox Styles */
        input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--primary-color);
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            padding: 1.5rem;
            border-top: 1px solid var(--gray-200);
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            text-decoration: none;
            color: var(--gray-700);
            font-weight: 500;
        }

        .pagination a:hover {
            background: var(--gray-50);
        }

        .pagination .current {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
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

        .no-products {
            text-align: center;
            padding: 3rem;
            color: var(--gray-500);
        }

        .no-products i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow-y: auto;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-content {
            background: white;
            border-radius: var(--border-radius);
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--gray-400);
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }

        .modal-close:hover {
            background: var(--gray-100);
            color: var(--gray-600);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-row.full {
            grid-template-columns: 1fr;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .form-group label.required::after {
            content: " *";
            color: var(--danger-color);
        }

        .form-input, .form-select, .form-textarea {
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

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .form-checkbox input[type="checkbox"] {
            margin: 0;
        }

        .file-upload {
            position: relative;
            display: inline-block;
            cursor: pointer;
            width: 100%;
        }

        .file-upload input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem;
            border: 2px dashed var(--gray-300);
            border-radius: var(--border-radius);
            background: var(--gray-50);
            color: var(--gray-600);
            transition: all 0.2s;
        }

        .file-upload:hover .file-upload-label {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.05);
            color: var(--primary-color);
        }

        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--gray-200);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
        }

        .btn-secondary:hover {
            background: var(--gray-200);
        }

        @media (max-width: 768px) {
            .admin-container {
                padding: 1rem;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .products-table {
                font-size: 0.875rem;
            }

            .product-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .modal-content {
                margin: 10px;
                max-height: calc(100vh - 20px);
            }
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
                <a href="products.php" class="navbar-item active">
                    <i class="fas fa-box"></i> Products
                </a>
                <a href="categories.php" class="navbar-item">
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

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['total_products']); ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card active">
                <div class="stat-icon">
                    <i class="fas fa-check"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['active_products']); ?></div>
                <div class="stat-label">Active Products</div>
            </div>
            <div class="stat-card featured">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['featured_products']); ?></div>
                <div class="stat-label">Featured Products</div>
            </div>
            <div class="stat-card low-stock">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['low_stock_products']); ?></div>
                <div class="stat-label">Low Stock Items</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="form-group">
                        <label for="search">Search Products</label>
                        <input type="text" id="search" name="search" class="form-input" 
                               placeholder="Search by name, description, or SKU..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="featured">Featured</label>
                        <select id="featured" name="featured" class="form-select">
                            <option value="">All Products</option>
                            <option value="1" <?php echo $featured_filter === '1' ? 'selected' : ''; ?>>Featured Only</option>
                            <option value="0" <?php echo $featured_filter === '0' ? 'selected' : ''; ?>>Non-Featured</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Add Product Modal -->
        <div id="addProductModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Add New Product</h3>
                    <button type="button" class="modal-close" onclick="closeAddProductModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="product_name" class="required">Product Name</label>
                                <input type="text" id="product_name" name="product_name" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label for="sku">SKU</label>
                                <input type="text" id="sku" name="sku" class="form-input" placeholder="Optional">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="category_id" class="required">Category</label>
                                <select id="category_id" name="category_id" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="price" class="required">Price ($)</label>
                                <input type="number" id="price" name="price" class="form-input" 
                                       step="0.01" min="0" required>
                            </div>
                            <div class="form-group">
                                <label for="sale_price">Sale Price ($)</label>
                                <input type="number" id="sale_price" name="sale_price" class="form-input" 
                                       step="0.01" min="0" placeholder="Optional">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="stock_quantity">Stock Quantity</label>
                                <input type="number" id="stock_quantity" name="stock_quantity" class="form-input" 
                                       min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label for="weight">Weight (kg)</label>
                                <input type="number" id="weight" name="weight" class="form-input" 
                                       step="0.01" min="0" placeholder="Optional">
                            </div>
                        </div>

                        <div class="form-row full">
                            <div class="form-group">
                                <label for="dimensions">Dimensions</label>
                                <input type="text" id="dimensions" name="dimensions" class="form-input" 
                                       placeholder="e.g., 20cm x 15cm x 5cm">
                            </div>
                        </div>

                        <div class="form-row full">
                            <div class="form-group">
                                <label for="short_description">Short Description</label>
                                <textarea id="short_description" name="short_description" class="form-textarea" 
                                          rows="3" placeholder="Brief product description for listings..."></textarea>
                            </div>
                        </div>

                        <div class="form-row full">
                            <div class="form-group">
                                <label for="description">Full Description</label>
                                <textarea id="description" name="description" class="form-textarea" 
                                          rows="5" placeholder="Detailed product description..."></textarea>
                            </div>
                        </div>

                        <div class="form-row full">
                            <div class="form-group">
                                <label for="product_image">Product Image</label>
                                <div class="file-upload">
                                    <input type="file" id="product_image" name="product_image" 
                                           accept="image/*" onchange="updateFileName(this)">
                                    <div class="file-upload-label">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <span id="file-name">Choose image file...</span>
                                    </div>
                                </div>
                                <small style="color: var(--gray-500); margin-top: 0.5rem; display: block;">
                                    Supported formats: JPG, PNG, GIF, WebP. Max size: 5MB
                                </small>
                            </div>
                        </div>

                        <div class="form-row full">
                            <div class="form-group">
                                <div class="form-checkbox">
                                    <input type="checkbox" id="featured" name="featured" value="1">
                                    <label for="featured">Feature this product on homepage</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeAddProductModal()">
                            Cancel
                        </button>
                        <button type="submit" name="add_product" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Product
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Product Modal -->
        <div id="editProductModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Edit Product</h3>
                    <button type="button" class="modal-close" onclick="closeEditProductModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="editProductForm">
                    <input type="hidden" name="product_id" id="edit_product_id">
                    <div class="modal-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="edit_product_name">Product Name <span class="required">*</span></label>
                                <input type="text" id="edit_product_name" name="product_name" class="form-input" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_category_id">Category <span class="required">*</span></label>
                                <select id="edit_category_id" name="category_id" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_price">Price <span class="required">*</span></label>
                                <input type="number" id="edit_price" name="price" class="form-input" step="0.01" min="0" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_sale_price">Sale Price</label>
                                <input type="number" id="edit_sale_price" name="sale_price" class="form-input" step="0.01" min="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_sku">SKU</label>
                                <input type="text" id="edit_sku" name="sku" class="form-input">
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_stock_quantity">Stock Quantity</label>
                                <input type="number" id="edit_stock_quantity" name="stock_quantity" class="form-input" min="0" value="0">
                            </div>
                            
                            <div class="form-group span-2">
                                <label for="edit_short_description">Short Description</label>
                                <textarea id="edit_short_description" name="short_description" class="form-textarea" rows="2"></textarea>
                            </div>
                            
                            <div class="form-group span-2">
                                <label for="edit_description">Description</label>
                                <textarea id="edit_description" name="description" class="form-textarea" rows="4"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_weight">Weight (kg)</label>
                                <input type="number" id="edit_weight" name="weight" class="form-input" step="0.01" min="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_dimensions">Dimensions</label>
                                <input type="text" id="edit_dimensions" name="dimensions" class="form-input" placeholder="L x W x H">
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_status">Status</label>
                                <select id="edit_status" name="status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="edit_featured" name="featured">
                                    <span class="checkmark"></span>
                                    Featured Product
                                </label>
                            </div>
                            
                            <div class="form-group span-2">
                                <label for="edit_product_image">Product Image</label>
                                <div class="file-input-wrapper">
                                    <input type="file" id="edit_product_image" name="product_image" class="file-input" 
                                           accept="image/*" onchange="updateEditFileName(this)">
                                    <label for="edit_product_image" class="file-input-label">
                                        <i class="fas fa-upload"></i>
                                        <span id="edit-file-name">Choose image file...</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeEditProductModal()">
                            Cancel
                        </button>
                        <button type="submit" name="edit_product" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Product
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Products Table -->
        <div class="products-section">
            <div class="section-header">
                <h2 class="section-title">
                    Products (<?php echo number_format($totalProducts); ?> total)
                </h2>
                <div class="action-buttons">
                    <button onclick="openAddProductModal()" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Product
                    </button>
                </div>
            </div>

            <?php if (empty($products)): ?>
                <div class="no-products">
                    <i class="fas fa-box-open"></i>
                    <h3>No products found</h3>
                    <p>Try adjusting your search criteria or add a new product.</p>
                </div>
            <?php else: ?>
                <!-- Bulk Actions -->
                <div class="bulk-actions" style="margin-bottom: 1rem; padding: 1rem; background: var(--gray-50); border-radius: var(--border-radius);">
                    <form method="POST" id="bulk-form">
                        <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                            <span style="font-weight: 600;">Bulk Actions:</span>
                            <select name="bulk_action" class="form-select" style="width: auto;">
                                <option value="">Select Action</option>
                                <option value="activate">Activate</option>
                                <option value="deactivate">Deactivate</option>
                                <option value="feature">Mark as Featured</option>
                                <option value="unfeature">Remove from Featured</option>
                                <option value="delete">Delete</option>
                            </select>
                            <button type="submit" class="btn btn-secondary" onclick="return confirmBulkAction()">
                                <i class="fas fa-check"></i> Apply
                            </button>
                            <span id="selected-count" style="color: var(--gray-600);">0 items selected</span>
                        </div>
                    </form>
                </div>
                
                <div class="table-container">
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select-all"></th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Featured</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><input type="checkbox" name="selected_products[]" value="<?php echo $product['id']; ?>"></td>
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
                                                <?php if ($product['sku']): ?>
                                                    <div class="sku">SKU: <?php echo htmlspecialchars($product['sku']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?></td>
                                    <td>
                                        <strong>NPR <?php echo number_format($product['price'], 2); ?></strong>
                                        <?php if ($product['sale_price']): ?>
                                            <br><small style="color: var(--danger-color);">Sale: NPR <?php echo number_format($product['sale_price'], 2); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $stock = (int)$product['stock_quantity'];
                                        $stockClass = $stock <= 5 ? 'stock-low' : ($stock <= 20 ? 'stock-medium' : 'stock-good');
                                        ?>
                                        <span class="stock-status <?php echo $stockClass; ?>">
                                            <?php echo number_format($stock); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $product['status']; ?>">
                                            <?php if ($product['status'] === 'active'): ?>
                                                <i class="fas fa-check"></i> Active
                                            <?php elseif ($product['status'] === 'inactive'): ?>
                                                <i class="fas fa-times"></i> Inactive
                                            <?php else: ?>
                                                <i class="fas fa-edit"></i> Draft
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($product['featured']): ?>
                                            <span class="status-badge status-featured">
                                                <i class="fas fa-star"></i> Featured
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-not-featured">
                                                Not Featured
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                            <button type="button" onclick="openEditProductModal(<?php echo $product['id']; ?>)" 
                                                    class="btn btn-primary btn-sm" title="Edit Product">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="hidden" name="current_status" value="<?php echo $product['status']; ?>">
                                                <button type="submit" name="toggle_status" 
                                                        class="btn <?php echo $product['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?> btn-sm"
                                                        title="<?php echo $product['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                    <i class="fas fa-<?php echo $product['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="hidden" name="current_featured" value="<?php echo $product['featured']; ?>">
                                                <button type="submit" name="toggle_featured" 
                                                        class="btn <?php echo $product['featured'] ? 'btn-warning' : 'btn-success'; ?> btn-sm"
                                                        title="<?php echo $product['featured'] ? 'Remove from Featured' : 'Make Featured'; ?>">
                                                    <i class="fas fa-star<?php echo $product['featured'] ? '' : '-o'; ?>"></i>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this product? This cannot be undone.');">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" name="delete_product" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">&laquo; First</a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">&lsaquo; Previous</a>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        
                        for ($i = $start; $i <= $end; $i++):
                        ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next &rsaquo;</a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>">Last &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-submit form on filter change
        document.querySelectorAll('.form-select').forEach(select => {
            select.addEventListener('change', function() {
                // Only auto-submit if it's not in the modal
                if (!this.closest('.modal')) {
                    this.closest('form').submit();
                }
            });
        });

        // Confirm delete actions
        document.querySelectorAll('form[onsubmit]').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to delete this product? This cannot be undone.')) {
                    e.preventDefault();
                }
            });
        });

        // Modal functions
        function openAddProductModal() {
            document.getElementById('addProductModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeAddProductModal() {
            document.getElementById('addProductModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            
            // Reset form
            const form = document.querySelector('#addProductModal form');
            form.reset();
            document.getElementById('file-name').textContent = 'Choose image file...';
        }

        // Edit modal functions
        function openEditProductModal(productId) {
            // Fetch product data via AJAX
            fetch(`get-product.php?id=${productId}`)
                .then(response => response.json())
                .then(product => {
                    if (product.error) {
                        alert('Error loading product data');
                        return;
                    }
                    
                    // Populate form fields
                    document.getElementById('edit_product_id').value = product.id;
                    document.getElementById('edit_product_name').value = product.name || '';
                    document.getElementById('edit_category_id').value = product.category_id || '';
                    document.getElementById('edit_price').value = product.price || '';
                    document.getElementById('edit_sale_price').value = product.sale_price || '';
                    document.getElementById('edit_sku').value = product.sku || '';
                    document.getElementById('edit_stock_quantity').value = product.stock_quantity || 0;
                    document.getElementById('edit_short_description').value = product.short_description || '';
                    document.getElementById('edit_description').value = product.description || '';
                    document.getElementById('edit_weight').value = product.weight || '';
                    document.getElementById('edit_dimensions').value = product.dimensions || '';
                    document.getElementById('edit_status').value = product.status || 'active';
                    document.getElementById('edit_featured').checked = product.featured == 1;
                    
                    // Show modal
                    document.getElementById('editProductModal').classList.add('show');
                    document.body.style.overflow = 'hidden';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading product data');
                });
        }

        function closeEditProductModal() {
            document.getElementById('editProductModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            
            // Reset form
            const form = document.querySelector('#editProductModal form');
            form.reset();
            document.getElementById('edit-file-name').textContent = 'Choose image file...';
        }

        // Update file name display
        function updateFileName(input) {
            const fileName = input.files[0] ? input.files[0].name : 'Choose image file...';
            document.getElementById('file-name').textContent = fileName;
        }

        function updateEditFileName(input) {
            const fileName = input.files[0] ? input.files[0].name : 'Choose image file...';
            document.getElementById('edit-file-name').textContent = fileName;
        }

        // Bulk actions
        function confirmBulkAction() {
            const selected = document.querySelectorAll('input[name="selected_products[]"]:checked');
            const action = document.querySelector('select[name="bulk_action"]').value;
            
            if (selected.length === 0) {
                alert('Please select at least one product');
                return false;
            }
            
            if (!action) {
                alert('Please select an action');
                return false;
            }
            
            const actionText = {
                'activate': 'activate',
                'deactivate': 'deactivate', 
                'feature': 'mark as featured',
                'unfeature': 'remove from featured',
                'delete': 'delete'
            };
            
            return confirm(`Are you sure you want to ${actionText[action]} ${selected.length} product(s)?`);
        }

        // Select all functionality
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="selected_products[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedCount();
        });

        // Update selected count
        function updateSelectedCount() {
            const selected = document.querySelectorAll('input[name="selected_products[]"]:checked');
            document.getElementById('selected-count').textContent = `${selected.length} items selected`;
        }

        // Close modals when clicking outside
        document.getElementById('addProductModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddProductModal();
            }
        });

        document.getElementById('editProductModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditProductModal();
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAddProductModal();
                closeEditProductModal();
            }
        });

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

            // Add event listeners to product checkboxes
            document.querySelectorAll('input[name="selected_products[]"]').forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectedCount);
            });
        });
    </script>
</body>
</html>
