<?php
require_once '../includes/db.php';
require_once '../includes/admin_auth.php';

// Check admin authentication
requireAdmin();

$pageTitle = "Orders Management";
$pageDescription = "View and manage customer orders";

$successMessage = '';
$errorMessage = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['new_status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        $successMessage = "Order status updated successfully!";
    } catch (Exception $e) {
        $errorMessage = "Error updating order status: " . $e->getMessage();
    }
}

// Get real orders from database
try {
    // First ensure orders table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        order_number VARCHAR(50) UNIQUE,
        total DECIMAL(10,2) DEFAULT 0,
        status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
        payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX(user_id)
    )");
    
    // Create order_items table for order details
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT,
        product_name VARCHAR(255),
        quantity INT DEFAULT 1,
        price DECIMAL(10,2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(order_id),
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )");
    
    // Check what columns exist in the orders table
    $columnsStmt = $pdo->query("DESCRIBE orders");
    $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Determine which total column to use
    $totalColumn = 'total';
    if (in_array('total_amount', $columns)) {
        $totalColumn = 'total_amount';
    } elseif (in_array('amount', $columns)) {
        $totalColumn = 'amount';
    }
    
    // Get orders with user information - use the correct total column
    $stmt = $pdo->query("
        SELECT o.*,
               CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as customer_name,
               u.email as customer_email,
               COALESCE(oi.items_count, 0) as items_count,
               COALESCE(o.{$totalColumn}, 0) as order_total
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN (
            SELECT order_id, COUNT(*) as items_count
            FROM order_items
            GROUP BY order_id
        ) oi ON o.id = oi.order_id
        ORDER BY o.created_at DESC
        LIMIT 50
    ");
    $orders = $stmt->fetchAll();
    
    // If no orders found, create sample data for demonstration
    if (empty($orders)) {
        // Create sample orders if none exist
        $sampleOrders = [
            [
                'user_id' => 1,
                'order_number' => 'ORD-' . date('Y') . '-001',
                'total' => 125.99,
                'status' => 'pending',
                'payment_status' => 'paid'
            ],
            [
                'user_id' => 2,
                'order_number' => 'ORD-' . date('Y') . '-002',
                'total' => 89.50,
                'status' => 'processing',
                'payment_status' => 'paid'
            ],
            [
                'user_id' => 3,
                'order_number' => 'ORD-' . date('Y') . '-003',
                'total' => 199.99,
                'status' => 'shipped',
                'payment_status' => 'paid'
            ]
        ];
        
        foreach ($sampleOrders as $sampleOrder) {
            try {
                $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_number, total, status, payment_status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $sampleOrder['user_id'],
                    $sampleOrder['order_number'],
                    $sampleOrder['total'],
                    $sampleOrder['status'],
                    $sampleOrder['payment_status']
                ]);
            } catch (Exception $e) {
                // Ignore if sample order already exists
            }
        }
        
        // Re-fetch orders after creating samples
        $stmt = $pdo->query("
            SELECT o.*,
                   CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as customer_name,
                   u.email as customer_email,
                   COALESCE(oi.items_count, 0) as items_count
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN (
                SELECT order_id, COUNT(*) as items_count
                FROM order_items
                GROUP BY order_id
            ) oi ON o.id = oi.order_id
            ORDER BY o.created_at DESC
            LIMIT 50
        ");
        $orders = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    $orders = [];
    $errorMessage = "Error loading orders: " . $e->getMessage();
}

$totalOrders = count($orders);
$pendingOrders = count(array_filter($orders, fn($o) => $o['status'] === 'pending'));
$processingOrders = count(array_filter($orders, fn($o) => $o['status'] === 'processing'));
$shippedOrders = count(array_filter($orders, fn($o) => $o['status'] === 'shipped'));
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-card.total { border-left-color: var(--primary-color); }
        .stat-card.pending { border-left-color: var(--warning-color); }
        .stat-card.processing { border-left-color: var(--info-color); }
        .stat-card.shipped { border-left-color: var(--success-color); }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }

        .stat-card.total .stat-icon { background: var(--primary-color); }
        .stat-card.pending .stat-icon { background: var(--warning-color); }
        .stat-card.processing .stat-icon { background: var(--info-color); }
        .stat-card.shipped .stat-icon { background: var(--success-color); }

        .stat-content h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .stat-content p {
            color: var(--gray-600);
            font-weight: 500;
            font-size: 0.875rem;
        }

        /* Orders Table */
        .orders-section {
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

        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th,
        .orders-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .orders-table th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .orders-table tr:hover {
            background: var(--gray-50);
        }

        .order-number {
            font-weight: 600;
            color: var(--primary-color);
        }

        .customer-info h4 {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .customer-info .email {
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.375rem 0.75rem;
            border-radius: var(--border-radius);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .status-processing {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info-color);
        }

        .status-shipped {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .status-delivered {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .status-cancelled {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .payment-status {
            font-size: 0.875rem;
            color: var(--success-color);
            font-weight: 500;
        }

        .order-total {
            font-weight: 700;
            color: var(--gray-900);
            font-size: 1.1rem;
        }

        .order-actions {
            display: flex;
            gap: 0.5rem;
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

        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info-color);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .no-orders {
            text-align: center;
            padding: 3rem;
            color: var(--gray-500);
        }

        .demo-notice {
            background: var(--warning-color);
            color: white;
            padding: 1rem;
            text-align: center;
            font-weight: 600;
            margin-bottom: 2rem;
            border-radius: var(--border-radius);
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
                <a href="categories.php" class="navbar-item">
                    <i class="fas fa-tags"></i> Categories
                </a>
                <a href="orders.php" class="navbar-item active">
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

        <?php if ($errorMessage): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <!-- Order Statistics -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalOrders; ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
            <div class="stat-card pending">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $pendingOrders; ?></h3>
                    <p>Pending Orders</p>
                </div>
            </div>
            <div class="stat-card processing">
                <div class="stat-icon">
                    <i class="fas fa-cog"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $processingOrders; ?></h3>
                    <p>Processing Orders</p>
                </div>
            </div>
            <div class="stat-card shipped">
                <div class="stat-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $shippedOrders; ?></h3>
                    <p>Shipped Orders</p>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="orders-section">
            <div class="section-header">
                <h2>Recent Orders</h2>
            </div>
            
            <?php if (!empty($orders)): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <div class="order-number"><?php echo htmlspecialchars($order['order_number']); ?></div>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <h4><?php echo htmlspecialchars($order['customer_name']); ?></h4>
                                        <div class="email"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                                    </div>
                                </td>
                                <td><?php echo $order['items_count']; ?> items</td>
                                <td>
                                    <div class="order-total">NPR <?php echo number_format($order['order_total'], 2); ?></div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php if ($order['status'] === 'pending'): ?>
                                            <i class="fas fa-clock"></i> Pending
                                        <?php elseif ($order['status'] === 'processing'): ?>
                                            <i class="fas fa-cog"></i> Processing
                                        <?php elseif ($order['status'] === 'shipped'): ?>
                                            <i class="fas fa-truck"></i> Shipped
                                        <?php elseif ($order['status'] === 'delivered'): ?>
                                            <i class="fas fa-check-circle"></i> Delivered
                                        <?php elseif ($order['status'] === 'cancelled'): ?>
                                            <i class="fas fa-times-circle"></i> Cancelled
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="payment-status">
                                        <i class="fas fa-check-circle"></i> <?php echo ucfirst($order['payment_status']); ?>
                                    </div>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <div class="order-actions">
                                        <button class="btn btn-primary btn-sm" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return updateOrderStatus(this)">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <select name="new_status" onchange="this.form.submit()" class="btn btn-secondary btn-sm" style="background: none; border: 1px solid var(--gray-300); padding: 0.375rem 0.75rem; border-radius: var(--border-radius);">
                                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-orders">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>No orders yet</h3>
                    <p>Orders will appear here when customers make purchases</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // View order details function
        function viewOrder(orderId) {
            // For now, show an alert with order ID
            // In a full implementation, this would open a modal with order details
            alert('View order details for Order ID: ' + orderId + '\n\nThis feature can be expanded to show:\n- Complete order information\n- Customer details\n- Order items list\n- Payment information\n- Shipping details');
        }

        // Update order status function
        function updateOrderStatus(form) {
            const orderId = form.querySelector('input[name="order_id"]').value;
            const newStatus = form.querySelector('select[name="new_status"]').value;
            
            return confirm('Are you sure you want to update the status of Order #' + orderId + ' to "' + newStatus.charAt(0).toUpperCase() + newStatus.slice(1) + '"?');
        }

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
