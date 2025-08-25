<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode('/user/dashboard.php'));
    exit;
}

$pageTitle = 'Dashboard';
$pageDescription = 'User account dashboard';

$db = Database::getInstance()->getConnection();
$userId = getCurrentUserId();

// Get user statistics
$stats = getUserStats($userId);

// Get recent orders
$recentOrders = getRecentOrders($userId, 5);

// Welcome message for new users
$showWelcome = isset($_GET['welcome']) && $_GET['welcome'] == '1';

function getUserStats($userId) {
    global $db;
    
    $stats = [
        'total_orders' => 0,
        'total_spent' => 0,
        'pending_orders' => 0,
        'wishlist_items' => 0,
        'addresses' => 0
    ];
    
    // Create missing tables if they don't exist
    try {
        // Create orders table
        $db->exec("CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            total DECIMAL(10,2) DEFAULT 0,
            status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
            payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(user_id)
        )");
        
        // Create wishlist table
        $db->exec("CREATE TABLE IF NOT EXISTS wishlist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(user_id)
        )");
        
        // Create user_addresses table
        $db->exec("CREATE TABLE IF NOT EXISTS user_addresses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type ENUM('billing', 'shipping') DEFAULT 'shipping',
            first_name VARCHAR(50),
            last_name VARCHAR(50),
            address_line_1 VARCHAR(255),
            address_line_2 VARCHAR(255),
            city VARCHAR(100),
            state VARCHAR(100),
            postal_code VARCHAR(20),
            country VARCHAR(100),
            is_default BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(user_id)
        )");
    } catch (Exception $e) {
        error_log("Table creation error: " . $e->getMessage());
    }
    
    // Safe queries with error handling
    try {
        // Total orders
        $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats['total_orders'] = $stmt->fetchColumn();
    } catch (Exception $e) {
        $stats['total_orders'] = 0;
    }
    
    try {
        // Total spent
        $stmt = $db->prepare("SELECT COALESCE(SUM(total), 0) FROM orders WHERE user_id = ? AND payment_status = 'paid'");
        $stmt->execute([$userId]);
        $stats['total_spent'] = $stmt->fetchColumn();
    } catch (Exception $e) {
        $stats['total_spent'] = 0;
    }
    
    try {
        // Pending orders
        $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status IN ('pending', 'processing', 'shipped')");
        $stmt->execute([$userId]);
        $stats['pending_orders'] = $stmt->fetchColumn();
    } catch (Exception $e) {
        $stats['pending_orders'] = 0;
    }
    
    try {
        // Wishlist items
        $stmt = $db->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats['wishlist_items'] = $stmt->fetchColumn();
    } catch (Exception $e) {
        $stats['wishlist_items'] = 0;
    }
    
    try {
        // Saved addresses
        $stmt = $db->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats['addresses'] = $stmt->fetchColumn();
    } catch (Exception $e) {
        $stats['addresses'] = 0;
    }
    
    return $stats;
}

function getRecentOrders($userId, $limit = 5) {
    global $db;
    
    try {
        // Create order_items table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT DEFAULT 1,
            price DECIMAL(10,2) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(order_id),
            INDEX(product_id)
        )");
        
        // Try to get recent orders with items (simplified without products table)
        $stmt = $db->prepare("
            SELECT o.*, 
                   COALESCE(GROUP_CONCAT(CONCAT(oi.quantity, 'x Product #', oi.product_id) SEPARATOR ', '), 'No items') as items_summary,
                   COUNT(oi.id) as item_count
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT ?
        ");
        
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("getRecentOrders error: " . $e->getMessage());
        
        // Fallback: simple orders query
        try {
            $stmt = $db->prepare("
                SELECT *, 'No items available' as items_summary, 0 as item_count
                FROM orders 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e2) {
            error_log("Fallback orders query error: " . $e2->getMessage());
            return [];
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<!-- Dashboard Section -->
<section class="dashboard-section">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <h1>Dashboard</h1>
                <p>Manage your account, orders, and preferences</p>
            </div>
            
            <div class="header-actions">
                <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i>
                    Continue Shopping
                </a>
                <a href="<?php echo SITE_URL; ?>/user/orders.php" class="btn btn-outline">
                    <i class="fas fa-receipt"></i>
                    View Orders
                </a>
            </div>
        </div>
        
        <?php if ($showWelcome): ?>
        <div class="welcome-banner">
            <div class="welcome-content">
                <h2>Welcome to your account, <?php echo sanitizeInput($_SESSION['first_name']); ?>! 🎉</h2>
                <p>Your account has been created successfully. You can now enjoy all the benefits of being a member.</p>
                <button type="button" class="btn btn-outline welcome-close" onclick="hideWelcome()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon orders">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_orders']); ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon spent">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo formatPrice($stats['total_spent']); ?></h3>
                    <p>Total Spent</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['pending_orders']); ?></h3>
                    <p>Pending Orders</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon wishlist">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['wishlist_items']); ?></h3>
                    <p>Wishlist Items</p>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Content -->
        <div class="dashboard-grid">
            <!-- Recent Orders -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>Recent Orders</h2>
                    <a href="<?php echo SITE_URL; ?>/user/orders.php" class="view-all">View All</a>
                </div>
                
                <div class="card-content">
                    <?php if (empty($recentOrders)): ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>No orders yet</h3>
                        <p>Start shopping to see your orders here</p>
                        <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">Start Shopping</a>
                    </div>
                    <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($recentOrders as $order): ?>
                        <div class="order-item">
                            <div class="order-info">
                                <div class="order-header">
                                    <span class="order-number">#<?php echo $order['order_number']; ?></span>
                                    <span class="order-status status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                                <div class="order-details">
                                    <p class="order-items"><?php echo $order['items_summary']; ?></p>
                                    <p class="order-meta">
                                        <?php echo date('M j, Y', strtotime($order['created_at'])); ?> • 
                                        <?php echo $order['item_count']; ?> item<?php echo $order['item_count'] > 1 ? 's' : ''; ?> • 
                                        <?php echo formatPrice($order['total']); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="order-actions">
                                <a href="<?php echo SITE_URL; ?>/user/order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline">
                                    View Details
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>Quick Actions</h2>
                </div>
                
                <div class="card-content">
                    <div class="quick-actions-grid">
                        <a href="<?php echo SITE_URL; ?>/user/profile.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="action-info">
                                <h3>Edit Profile</h3>
                                <p>Update your personal information</p>
                            </div>
                        </a>
                        
                        <a href="<?php echo SITE_URL; ?>/user/addresses.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="action-info">
                                <h3>Manage Addresses</h3>
                                <p><?php echo $stats['addresses']; ?> saved address<?php echo $stats['addresses'] != 1 ? 'es' : ''; ?></p>
                            </div>
                        </a>
                        
                        <a href="<?php echo SITE_URL; ?>/user/wishlist.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div class="action-info">
                                <h3>Wishlist</h3>
                                <p><?php echo $stats['wishlist_items']; ?> item<?php echo $stats['wishlist_items'] != 1 ? 's' : ''; ?> saved</p>
                            </div>
                        </a>
                        
                        <a href="<?php echo SITE_URL; ?>/user/settings.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="action-info">
                                <h3>Account Settings</h3>
                                <p>Privacy, notifications, security</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Enhanced Premium Dashboard Styles */
.dashboard-section {
    padding: var(--spacing-12) 0;
    background: linear-gradient(180deg, var(--gray-50) 0%, var(--white) 50%, var(--gray-50) 100%);
    min-height: 85vh;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-12);
    background: linear-gradient(135deg, var(--white) 0%, #fafbfc 100%);
    padding: var(--spacing-8);
    border-radius: var(--border-radius-xl);
    box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    border: 1px solid rgba(255,255,255,0.8);
    position: relative;
    overflow: hidden;
}

.page-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
}

.header-content h1 {
    font-size: var(--font-size-3xl);
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: var(--spacing-2);
    letter-spacing: -0.02em;
}

.header-content p {
    color: var(--gray-600);
    font-size: var(--font-size-lg);
    font-weight: 500;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: var(--spacing-4);
}

.btn {
    padding: var(--spacing-3) var(--spacing-6);
    border-radius: var(--border-radius-lg);
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: var(--white);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
}

.btn-outline {
    background: transparent;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}

.btn-outline:hover {
    background: var(--primary-color);
    color: var(--white);
    transform: translateY(-2px);
}

.welcome-banner {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
    color: var(--white);
    padding: var(--spacing-10);
    border-radius: var(--border-radius-xl);
    margin-bottom: var(--spacing-12);
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(37, 99, 235, 0.3);
}

.welcome-banner::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><defs><radialGradient id="a" cx="50%" cy="50%"><stop offset="0%" stop-color="white" stop-opacity="0.1"/><stop offset="100%" stop-color="white" stop-opacity="0"/></radialGradient></defs><circle cx="10" cy="5" r="8" fill="url(%23a)"/><circle cx="90" cy="15" r="8" fill="url(%23a)"/></svg>');
    animation: float 8s ease-in-out infinite;
}

.welcome-banner h2 {
    font-size: var(--font-size-2xl);
    font-weight: 700;
    margin-bottom: var(--spacing-3);
    position: relative;
    z-index: 2;
}

.welcome-banner p {
    font-size: var(--font-size-lg);
    opacity: 0.95;
    position: relative;
    z-index: 2;
    margin: 0;
}

/* Enhanced Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--spacing-8);
    margin-bottom: var(--spacing-12);
}

.stat-card {
    background: linear-gradient(135deg, var(--white) 0%, #fafbfc 100%);
    padding: var(--spacing-8);
    border-radius: var(--border-radius-xl);
    box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    border: 1px solid rgba(255,255,255,0.8);
    transition: all 0.4s ease;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.stat-card:hover::before {
    transform: scaleX(1);
}

.stat-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 25px 50px rgba(0,0,0,0.15);
}

.stat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--spacing-6);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: var(--border-radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--white);
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
}

.stat-value {
    font-size: var(--font-size-3xl);
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: var(--spacing-2);
    letter-spacing: -0.02em;
}

.stat-label {
    font-size: var(--font-size-base);
    color: var(--gray-600);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
    color: var(--white);
    padding: var(--spacing-6);
    border-radius: var(--border-radius-lg);
    margin-bottom: var(--spacing-8);
    margin-top: var(--spacing-8); /* Add margin to compensate for removed section padding */
    position: relative;
}

.welcome-content h2 {
    margin-bottom: var(--spacing-2);
    font-size: var(--font-size-2xl);
}

.welcome-content p {
    margin: 0;
    opacity: 0.9;
}

.welcome-close {
    position: absolute;
    top: var(--spacing-4);
    right: var(--spacing-4);
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: var(--white);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-6);
    margin-bottom: var(--spacing-8);
}

.stat-card {
    background: var(--white);
    padding: var(--spacing-6);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    gap: var(--spacing-4);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: var(--border-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--font-size-xl);
    color: var(--white);
}

.stat-icon.orders {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}

.stat-icon.spent {
    background: linear-gradient(135deg, #10b981, #059669);
}

.stat-icon.pending {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.stat-icon.wishlist {
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

.stat-info h3 {
    font-size: var(--font-size-2xl);
    color: var(--gray-900);
    margin-bottom: var(--spacing-1);
}

.stat-info p {
    color: var(--gray-600);
    margin: 0;
    font-size: var(--font-size-sm);
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--spacing-8);
}

.dashboard-card {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-6);
    border-bottom: 1px solid var(--gray-200);
}

.card-header h2 {
    font-size: var(--font-size-xl);
    color: var(--gray-900);
    margin: 0;
}

.view-all {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    font-size: var(--font-size-sm);
}

.view-all:hover {
    text-decoration: underline;
}

.card-content {
    padding: var(--spacing-6);
}

.empty-state {
    text-align: center;
    padding: var(--spacing-8) var(--spacing-4);
}

.empty-state i {
    font-size: 4rem;
    color: var(--gray-300);
    margin-bottom: var(--spacing-4);
}

.empty-state h3 {
    color: var(--gray-700);
    margin-bottom: var(--spacing-2);
}

.empty-state p {
    color: var(--gray-500);
    margin-bottom: var(--spacing-4);
}

.orders-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-4);
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-4);
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
    transition: var(--transition-fast);
}

.order-item:hover {
    border-color: var(--primary-color);
}

.order-header {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    margin-bottom: var(--spacing-2);
}

.order-number {
    font-weight: 600;
    color: var(--gray-900);
}

.order-status {
    padding: var(--spacing-1) var(--spacing-2);
    border-radius: var(--border-radius);
    font-size: var(--font-size-xs);
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-processing {
    background: #dbeafe;
    color: #1e40af;
}

.status-shipped {
    background: #d1fae5;
    color: #065f46;
}

.status-delivered {
    background: #dcfce7;
    color: #166534;
}

.status-cancelled {
    background: #fee2e2;
    color: #991b1b;
}

.order-items {
    color: var(--gray-700);
    margin-bottom: var(--spacing-1);
    font-size: var(--font-size-sm);
}

.order-meta {
    color: var(--gray-500);
    font-size: var(--font-size-xs);
    margin: 0;
}

.quick-actions-grid {
    display: grid;
    gap: var(--spacing-4);
}

.action-card {
    display: flex;
    align-items: center;
    gap: var(--spacing-4);
    padding: var(--spacing-4);
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
    text-decoration: none;
    color: inherit;
    transition: var(--transition-fast);
}

.action-card:hover {
    border-color: var(--primary-color);
    background: var(--gray-50);
}

.action-icon {
    width: 40px;
    height: 40px;
    background: var(--gray-100);
    border-radius: var(--border-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
}

.action-info h3 {
    font-size: var(--font-size-base);
    color: var(--gray-900);
    margin-bottom: var(--spacing-1);
}

.action-info p {
    color: var(--gray-600);
    font-size: var(--font-size-sm);
    margin: 0;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
        gap: var(--spacing-6);
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: var(--spacing-4);
        text-align: center;
    }
    
    .header-actions {
        justify-content: center;
    }
        flex-direction: column;
        width: 100%;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
    
    .stat-card {
        flex-direction: column;
        text-align: center;
    }
    
    .order-item {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-3);
    }
    
    .order-actions {
        width: 100%;
    }
    
    .order-actions .btn {
        width: 100%;
    }
}
</style>

<script>
function hideWelcome() {
    $('.welcome-banner').slideUp(function() {
        $(this).remove();
    });
}

$(document).ready(function() {
    // Auto-hide welcome banner after 10 seconds
    setTimeout(function() {
        if ($('.welcome-banner').length) {
            hideWelcome();
        }
    }, 10000);
});
</script>

<?php include '../includes/footer.php'; ?>
