<?php
// Redirect to products.php as the main admin page
header('Location: products.php');
exit;
?>
$db = Database::getInstance()->getConnection();

// Get dashboard statistics
try {
    // Total products
    $stmt = $db->query("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
    $totalProducts = $stmt->fetch()['total'];
    
    // Total orders
    $stmt = $db->query("SELECT COUNT(*) as total FROM orders");
    $totalOrders = $stmt->fetch()['total'];
    
    // Total users
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role != 'admin'");
    $totalUsers = $stmt->fetch()['total'];
    
    // Total revenue
    $stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status IN ('completed', 'delivered')");
    $totalRevenue = $stmt->fetch()['total'];
    
    // Recent orders
    $stmt = $db->query("
        SELECT o.*, u.first_name, u.last_name, u.email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 10
    ");
    $recentOrders = $stmt->fetchAll();
    
    // Sales by month (last 6 months)
    $stmt = $db->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as order_count,
            SUM(total_amount) as revenue
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        AND status IN ('completed', 'delivered')
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
    ");
    $salesData = $stmt->fetchAll();
    
    // Low stock products
    $stmt = $db->query("
        SELECT id, name, stock_quantity, sku 
        FROM products 
        WHERE stock_quantity < 10 AND status = 'active'
        ORDER BY stock_quantity ASC
        LIMIT 10
    ");
    $lowStockProducts = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    $totalProducts = $totalOrders = $totalUsers = $totalRevenue = 0;
    $recentOrders = $salesData = $lowStockProducts = [];
}
?>

<?php include 'includes/admin_header.php'; ?>

<div class="admin-dashboard">
    <div class="dashboard-header">
        <h1>Dashboard Overview</h1>
        <p>Welcome back, <?php echo sanitizeInput($_SESSION['first_name']); ?>! Here's what's happening with your store.</p>
    </div>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($totalProducts); ?></h3>
                <p>Active Products</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($totalOrders); ?></h3>
                <p>Total Orders</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($totalUsers); ?></h3>
                <p>Registered Users</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-rupee-sign"></i>
            </div>
            <div class="stat-info">
                <h3>₹<?php echo number_format($totalRevenue); ?></h3>
                <p>Total Revenue</p>
            </div>
        </div>
    </div>
    
    <!-- Charts and Recent Activity -->
    <div class="dashboard-content">
        <div class="dashboard-left">
            <!-- Sales Chart -->
            <div class="admin-card">
                <div class="card-header">
                    <h3>Sales Overview (Last 6 Months)</h3>
                </div>
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="admin-card">
                <div class="card-header">
                    <h3>Recent Orders</h3>
                    <a href="orders.php" class="btn btn-outline btn-sm">View All</a>
                </div>
                <div class="orders-list">
                    <?php if (empty($recentOrders)): ?>
                    <p>No orders yet.</p>
                    <?php else: ?>
                    <?php foreach ($recentOrders as $order): ?>
                    <div class="order-item">
                        <div class="order-info">
                            <span class="order-id">#<?php echo $order['order_number']; ?></span>
                            <span class="customer-name">
                                <?php echo $order['first_name'] ? $order['first_name'] . ' ' . $order['last_name'] : 'Guest'; ?>
                            </span>
                        </div>
                        <div class="order-meta">
                            <span class="order-amount">₹<?php echo number_format($order['total_amount']); ?></span>
                            <span class="order-status status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="dashboard-right">
            <!-- Quick Actions -->
            <div class="admin-card">
                <div class="card-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="quick-actions">
                    <a href="products.php" class="action-btn">
                        <i class="fas fa-plus"></i>
                        Add Product
                    </a>
                    <a href="orders.php" class="action-btn">
                        <i class="fas fa-list"></i>
                        Manage Orders
                    </a>
                    <a href="users.php" class="action-btn">
                        <i class="fas fa-users"></i>
                        View Users
                    </a>
                    <a href="settings.php" class="action-btn">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                </div>
            </div>
            
            <!-- Low Stock Alert -->
            <div class="admin-card">
                <div class="card-header">
                    <h3>Low Stock Alert</h3>
                </div>
                <div class="low-stock-list">
                    <?php if (empty($lowStockProducts)): ?>
                    <p>All products are well stocked!</p>
                    <?php else: ?>
                    <?php foreach ($lowStockProducts as $product): ?>
                    <div class="stock-item">
                        <div class="product-info">
                            <span class="product-name"><?php echo $product['name']; ?></span>
                            <span class="product-sku"><?php echo $product['sku']; ?></span>
                        </div>
                        <span class="stock-quantity <?php echo $product['stock_quantity'] < 5 ? 'critical' : 'warning'; ?>">
                            <?php echo $product['stock_quantity']; ?> left
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sales Chart
const salesCtx = document.getElementById('salesChart').getContext('2d');
const salesData = <?php echo json_encode($salesData); ?>;

const salesChart = new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: salesData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        }).reverse(),
        datasets: [{
            label: 'Revenue',
            data: salesData.map(item => item.revenue).reverse(),
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            tension: 0.1,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₹' + value.toLocaleString();
                    }
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?>
