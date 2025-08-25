<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode('/user/orders.php'));
    exit;
}

$pageTitle = 'My Orders';
$pageDescription = 'View and track your orders';

$db = Database::getInstance()->getConnection();
$userId = getCurrentUserId();

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filter by status
$statusFilter = $_GET['status'] ?? 'all';
$validStatuses = ['all', 'pending', 'processing', 'shipped', 'delivered', 'cancelled'];
if (!in_array($statusFilter, $validStatuses)) {
    $statusFilter = 'all';
}

// Get orders with pagination
$whereClause = "WHERE o.user_id = ?";
$params = [$userId];

if ($statusFilter !== 'all') {
    $whereClause .= " AND o.status = ?";
    $params[] = $statusFilter;
}

$stmt = $db->prepare("
    SELECT COUNT(*) 
    FROM orders o 
    $whereClause
");
$stmt->execute($params);
$totalOrders = $stmt->fetchColumn();
$totalPages = ceil($totalOrders / $limit);

$stmt = $db->prepare("
    SELECT o.*, 
           GROUP_CONCAT(CONCAT(oi.quantity, 'x ', oi.product_name) SEPARATOR ', ') as items_summary,
           COUNT(oi.id) as item_count,
           (SELECT image_url FROM product_images pi 
            JOIN order_items oi2 ON pi.product_id = oi2.product_id 
            WHERE oi2.order_id = o.id AND pi.is_primary = 1 
            LIMIT 1) as first_item_image
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    $whereClause
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT $offset, $limit
");
// Remove limit and offset from params
// $params[] = $limit;
// $params[] = $offset;
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order status counts
$statusCounts = [];
$stmt = $db->prepare("
    SELECT status, COUNT(*) as count 
    FROM orders 
    WHERE user_id = ? 
    GROUP BY status
");
$stmt->execute([$userId]);
$statusResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($statusResults as $result) {
    $statusCounts[$result['status']] = $result['count'];
}
$statusCounts['all'] = $totalOrders;
?>

<?php include '../includes/header.php'; ?>

<!-- Orders Section -->
<section class="orders-section">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <h1>My Orders</h1>
                <p>Track and manage your order history</p>
            </div>
            
            <div class="header-actions">
                <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i>
                    Continue Shopping
                </a>
            </div>
        </div>
        
        <!-- Order Filters -->
        <div class="order-filters">
            <div class="filter-tabs">
                <a href="?status=all" class="filter-tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">
                    All Orders
                    <?php if (isset($statusCounts['all'])): ?>
                    <span class="count"><?php echo $statusCounts['all']; ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="?status=pending" class="filter-tab <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
                    Pending
                    <?php if (isset($statusCounts['pending'])): ?>
                    <span class="count"><?php echo $statusCounts['pending']; ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="?status=processing" class="filter-tab <?php echo $statusFilter === 'processing' ? 'active' : ''; ?>">
                    Processing
                    <?php if (isset($statusCounts['processing'])): ?>
                    <span class="count"><?php echo $statusCounts['processing']; ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="?status=shipped" class="filter-tab <?php echo $statusFilter === 'shipped' ? 'active' : ''; ?>">
                    Shipped
                    <?php if (isset($statusCounts['shipped'])): ?>
                    <span class="count"><?php echo $statusCounts['shipped']; ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="?status=delivered" class="filter-tab <?php echo $statusFilter === 'delivered' ? 'active' : ''; ?>">
                    Delivered
                    <?php if (isset($statusCounts['delivered'])): ?>
                    <span class="count"><?php echo $statusCounts['delivered']; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
        
        <!-- Orders List -->
        <div class="orders-container">
            <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3>No orders found</h3>
                <p>
                    <?php if ($statusFilter === 'all'): ?>
                        You haven't placed any orders yet. Start shopping to see your orders here.
                    <?php else: ?>
                        No orders with status "<?php echo ucfirst($statusFilter); ?>" found.
                    <?php endif; ?>
                </p>
                <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">
                    Start Shopping
                </a>
            </div>
            <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-info">
                            <div class="order-number">
                                <strong>#<?php echo $order['order_number']; ?></strong>
                            </div>
                            <div class="order-date">
                                <?php echo date('M j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                            </div>
                        </div>
                        
                        <div class="order-status">
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="order-content">
                        <div class="order-image">
                            <?php if ($order['first_item_image']): ?>
                            <img src="<?php echo $order['first_item_image']; ?>" alt="Order item">
                            <?php else: ?>
                            <img src="https://images.unsplash.com/photo-1523381210834-895b31b4-3b0c?ixlib=rb-4.0.3&auto=format&fit=crop&w=80&h=80" alt="Order item">
                            <?php endif; ?>
                        </div>
                        
                        <div class="order-details">
                            <div class="order-items">
                                <p class="items-summary"><?php echo $order['items_summary']; ?></p>
                                <p class="order-meta">
                                    <?php echo $order['item_count']; ?> item<?php echo $order['item_count'] > 1 ? 's' : ''; ?> • 
                                    <?php echo formatPrice($order['total_amount']); ?>
                                </p>
                            </div>
                            
                            <div class="delivery-info">
                                <p class="delivery-address">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php 
                                    $shippingAddress = json_decode($order['shipping_address'], true);
                                    if ($shippingAddress && isset($shippingAddress['city'])) {
                                        echo $shippingAddress['city'] . ', ' . $shippingAddress['country'];
                                    } else {
                                        echo 'Address not available';
                                    }
                                    ?>
                                </p>
                                <p class="payment-method">
                                    <i class="fas fa-credit-card"></i>
                                    <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="order-actions">
                            <a href="<?php echo SITE_URL; ?>/user/order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-outline">
                                <i class="fas fa-eye"></i>
                                View Details
                            </a>
                            
                            <?php if (in_array($order['status'], ['pending', 'processing'])): ?>
                            <button type="button" class="btn btn-secondary" onclick="trackOrder('<?php echo $order['order_number']; ?>')">
                                <i class="fas fa-truck"></i>
                                Track Order
                            </button>
                            <?php endif; ?>
                            
                            <?php if ($order['status'] === 'delivered'): ?>
                            <button type="button" class="btn btn-primary" onclick="reorderItems(<?php echo $order['id']; ?>)">
                                <i class="fas fa-redo"></i>
                                Reorder
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Order Progress -->
                    <?php if (in_array($order['status'], ['pending', 'processing', 'shipped'])): ?>
                    <div class="order-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php 
                                echo $order['status'] === 'pending' ? '25%' : 
                                    ($order['status'] === 'processing' ? '50%' : 
                                    ($order['status'] === 'shipped' ? '75%' : '100%')); 
                            ?>"></div>
                        </div>
                        <div class="progress-steps">
                            <div class="progress-step <?php echo in_array($order['status'], ['pending', 'processing', 'shipped', 'delivered']) ? 'completed' : ''; ?>">
                                <i class="fas fa-receipt"></i>
                                <span>Placed</span>
                            </div>
                            <div class="progress-step <?php echo in_array($order['status'], ['processing', 'shipped', 'delivered']) ? 'completed' : ''; ?>">
                                <i class="fas fa-cogs"></i>
                                <span>Processing</span>
                            </div>
                            <div class="progress-step <?php echo in_array($order['status'], ['shipped', 'delivered']) ? 'completed' : ''; ?>">
                                <i class="fas fa-truck"></i>
                                <span>Shipped</span>
                            </div>
                            <div class="progress-step <?php echo $order['status'] === 'delivered' ? 'completed' : ''; ?>">
                                <i class="fas fa-check"></i>
                                <span>Delivered</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination-container">
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $statusFilter; ?>" class="pagination-btn">
                        <i class="fas fa-chevron-left"></i>
                        Previous
                    </a>
                    <?php endif; ?>
                    
                    <div class="pagination-info">
                        Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                    </div>
                    
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $statusFilter; ?>" class="pagination-btn">
                        Next
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
/* Enhanced Premium Orders Page Styles */
.orders-section {
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

.order-filters {
    margin-bottom: var(--spacing-10);
    background: linear-gradient(135deg, var(--white) 0%, #fafbfc 100%);
    padding: var(--spacing-6);
    border-radius: var(--border-radius-xl);
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
}

.filter-tabs {
    display: flex;
    gap: var(--spacing-2);
    background: var(--gray-100);
    padding: var(--spacing-1);
    border-radius: var(--border-radius-lg);
    width: fit-content;
}

.filter-tab {
    padding: var(--spacing-3) var(--spacing-6);
    border: none;
    background: transparent;
    color: var(--gray-600);
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
    font-size: var(--font-size-sm);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.filter-tab.active,
.filter-tab:hover {
    background: var(--white);
    color: var(--primary-color);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}

/* Enhanced Order Cards */
.orders-grid {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-6);
}

.order-card {
    background: linear-gradient(135deg, var(--white) 0%, #fafbfc 100%);
    border-radius: var(--border-radius-xl);
    box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    border: 1px solid rgba(255,255,255,0.8);
    overflow: hidden;
    transition: all 0.4s ease;
    position: relative;
}

.order-card::before {
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

.order-card:hover::before {
    transform: scaleX(1);
}

.order-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 25px 50px rgba(0,0,0,0.12);
}

.order-header {
    padding: var(--spacing-6) var(--spacing-8);
    background: linear-gradient(135deg, var(--gray-50) 0%, #f8fafc 100%);
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-info h3 {
    font-size: var(--font-size-lg);
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: var(--spacing-1);
}

.order-date {
    color: var(--gray-600);
    font-size: var(--font-size-sm);
    font-weight: 500;
}

.order-status {
    padding: var(--spacing-2) var(--spacing-4);
    border-radius: var(--border-radius-lg);
    font-size: var(--font-size-xs);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.status-pending {
    background: linear-gradient(135deg, var(--warning-color), #f59e0b);
    color: var(--white);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.status-confirmed {
    background: linear-gradient(135deg, var(--info-color), #3b82f6);
    color: var(--white);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.status-shipped {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: var(--white);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.status-delivered {
    background: linear-gradient(135deg, var(--success-color), #059669);
    color: var(--white);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.status-cancelled {
    background: linear-gradient(135deg, var(--danger-color), #dc2626);
    color: var(--white);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.order-body {
    padding: var(--spacing-8);
}

.order-total {
    font-size: var(--font-size-xl);
    font-weight: 800;
    color: var(--primary-color);
    text-align: right;
    margin-bottom: var(--spacing-6);
}

.order-actions {
    display: flex;
    gap: var(--spacing-3);
    justify-content: flex-end;
}

.btn-sm {
    padding: var(--spacing-2) var(--spacing-4);
    font-size: var(--font-size-sm);
    border-radius: var(--border-radius);
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-1);
}

.btn-outline {
    background: transparent;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}

.btn-outline:hover {
    background: var(--primary-color);
    color: var(--white);
    transform: translateY(-1px);
}
    display: flex;
    gap: var(--spacing-2);
    background: var(--white);
    padding: var(--spacing-4);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    overflow-x: auto;
}

.filter-tab {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-3) var(--spacing-4);
    border-radius: var(--border-radius);
    text-decoration: none;
    color: var(--gray-600);
    font-weight: 500;
    white-space: nowrap;
    transition: var(--transition-fast);
}

.filter-tab:hover {
    background: var(--gray-100);
    color: var(--gray-900);
}

.filter-tab.active {
    background: var(--primary-color);
    color: var(--white);
}

.filter-tab .count {
    background: rgba(255, 255, 255, 0.2);
    padding: var(--spacing-1) var(--spacing-2);
    border-radius: var(--border-radius);
    font-size: var(--font-size-xs);
    font-weight: 600;
}

.filter-tab.active .count {
    background: rgba(255, 255, 255, 0.3);
}

.orders-container {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.empty-state {
    text-align: center;
    padding: var(--spacing-12) var(--spacing-6);
}

.empty-icon {
    margin-bottom: var(--spacing-6);
}

.empty-icon i {
    font-size: 4rem;
    color: var(--gray-300);
}

.empty-state h3 {
    font-size: var(--font-size-xl);
    color: var(--gray-700);
    margin-bottom: var(--spacing-2);
}

.empty-state p {
    color: var(--gray-500);
    margin-bottom: var(--spacing-6);
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

.orders-list {
    padding: var(--spacing-6);
}

.order-card {
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius-lg);
    margin-bottom: var(--spacing-6);
    overflow: hidden;
    transition: var(--transition-fast);
}

.order-card:hover {
    border-color: var(--primary-color);
    box-shadow: var(--shadow);
}

.order-card:last-child {
    margin-bottom: 0;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-4) var(--spacing-6);
    background: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
}

.order-number {
    font-size: var(--font-size-lg);
    color: var(--gray-900);
    margin-bottom: var(--spacing-1);
}

.order-date {
    font-size: var(--font-size-sm);
    color: var(--gray-600);
}

.status-badge {
    padding: var(--spacing-2) var(--spacing-3);
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

.order-content {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: var(--spacing-4);
    padding: var(--spacing-6);
    align-items: center;
}

.order-image img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: var(--border-radius);
}

.order-details {
    flex: 1;
}

.items-summary {
    font-weight: 500;
    color: var(--gray-900);
    margin-bottom: var(--spacing-2);
}

.order-meta {
    font-size: var(--font-size-sm);
    color: var(--gray-600);
    margin-bottom: var(--spacing-3);
}

.delivery-info p {
    font-size: var(--font-size-sm);
    color: var(--gray-600);
    margin-bottom: var(--spacing-1);
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
}

.delivery-info i {
    width: 16px;
    color: var(--gray-400);
}

.order-actions {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}

.order-progress {
    padding: var(--spacing-4) var(--spacing-6);
    background: var(--gray-50);
    border-top: 1px solid var(--gray-200);
}

.progress-bar {
    height: 4px;
    background: var(--gray-200);
    border-radius: 2px;
    margin-bottom: var(--spacing-4);
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: var(--primary-color);
    border-radius: 2px;
    transition: width 0.3s ease;
}

.progress-steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--spacing-2);
    opacity: 0.5;
    transition: var(--transition-fast);
}

.progress-step.completed {
    opacity: 1;
    color: var(--primary-color);
}

.progress-step i {
    font-size: var(--font-size-lg);
}

.progress-step span {
    font-size: var(--font-size-xs);
    font-weight: 500;
}

.pagination-container {
    padding: var(--spacing-6);
    border-top: 1px solid var(--gray-200);
}

.pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.pagination-btn {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-3) var(--spacing-4);
    background: var(--primary-color);
    color: var(--white);
    text-decoration: none;
    border-radius: var(--border-radius);
    font-weight: 500;
    transition: var(--transition-fast);
}

.pagination-btn:hover {
    background: var(--primary-dark);
}

.pagination-info {
    color: var(--gray-600);
    font-weight: 500;
}

/* Responsive Design */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: var(--spacing-4);
        text-align: center;
    }
    
    .order-content {
        grid-template-columns: 1fr;
        gap: var(--spacing-4);
        text-align: center;
    }
    
    .order-actions {
        flex-direction: row;
        justify-content: center;
    }
    
    .order-actions .btn {
        flex: 1;
        min-width: auto;
    }
    
    .progress-steps {
        flex-wrap: wrap;
        gap: var(--spacing-4);
    }
    
    .progress-step {
        flex: 1;
        min-width: 80px;
    }
    
    .pagination {
        flex-direction: column;
        gap: var(--spacing-3);
    }
}
</style>

<script>
function trackOrder(orderNumber) {
    // In a real application, this would open a tracking modal or redirect to tracking page
    showToast(`Tracking information for order ${orderNumber} would be displayed here`, 'info');
}

function reorderItems(orderId) {
    showLoading();
    
    $.ajax({
        url: window.siteUrl + '/api/reorder.php',
        method: 'POST',
        data: {
            action: 'reorder',
            order_id: orderId,
            csrf_token: window.csrfToken
        },
        success: function(response) {
            if (response.success) {
                showToast('Items added to cart successfully!', 'success');
                updateCartCount(response.cart_count);
            } else {
                showToast(response.message || 'Failed to reorder items', 'error');
            }
        },
        error: function() {
            showToast('An error occurred. Please try again.', 'error');
        },
        complete: function() {
            hideLoading();
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>
