<?php
require_once '../includes/config.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$pageTitle = 'Reviews Management';
$db = Database::getInstance()->getConnection();

// Handle review actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'approve':
            $reviewId = (int)$_POST['review_id'];
            $stmt = $db->prepare("UPDATE reviews SET is_approved = 1 WHERE id = ?");
            if ($stmt->execute([$reviewId])) {
                $success = 'Review approved successfully!';
            }
            break;
        case 'reject':
            $reviewId = (int)$_POST['review_id'];
            $stmt = $db->prepare("UPDATE reviews SET is_approved = 0 WHERE id = ?");
            if ($stmt->execute([$reviewId])) {
                $success = 'Review rejected successfully!';
            }
            break;
        case 'delete':
            $reviewId = (int)$_POST['review_id'];
            $stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
            if ($stmt->execute([$reviewId])) {
                $success = 'Review deleted successfully!';
            }
            break;
    }
}

// Get filters
$statusFilter = $_GET['status'] ?? '';
$ratingFilter = $_GET['rating'] ?? '';
$productFilter = $_GET['product'] ?? '';

// Build query
$where = ['1=1'];
$params = [];

if ($statusFilter !== '') {
    if ($statusFilter === 'pending') {
        $where[] = 'r.is_approved = 0';
    } else {
        $where[] = 'r.is_approved = 1';
    }
}

if ($ratingFilter) {
    $where[] = 'r.rating = ?';
    $params[] = $ratingFilter;
}

if ($productFilter) {
    $where[] = 'p.id = ?';
    $params[] = $productFilter;
}

// Get reviews with product and user information
$sql = "
    SELECT r.*, 
           p.name as product_name, p.slug as product_slug,
           u.first_name, u.last_name, u.email
    FROM reviews r
    LEFT JOIN products p ON r.product_id = p.id
    LEFT JOIN users u ON r.user_id = u.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY r.created_at DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Get products for filter
$stmt = $db->query("SELECT id, name FROM products WHERE status = 'active' ORDER BY name");
$products = $stmt->fetchAll();

// Get review statistics
$stats = [
    'total' => 0,
    'approved' => 0,
    'pending' => 0,
    'avg_rating' => 0
];

foreach ($reviews as $review) {
    $stats['total']++;
    if ($review['is_approved']) {
        $stats['approved']++;
    } else {
        $stats['pending']++;
    }
}

// Get overall average rating
$stmt = $db->query("SELECT AVG(rating) as avg_rating FROM reviews WHERE is_approved = 1");
$result = $stmt->fetch();
$stats['avg_rating'] = $result['avg_rating'] ? round($result['avg_rating'], 1) : 0;
?>

<?php include 'includes/admin_header.php'; ?>

<div class="admin-reviews">
    <div class="page-header">
        <div class="header-left">
            <h1>Reviews Management</h1>
            <p>Manage customer reviews and ratings</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-outline" onclick="exportReviews()">
                <i class="fas fa-download"></i>
                Export Reviews
            </button>
        </div>
    </div>
    
    <!-- Review Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Reviews</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['approved']; ?></h3>
                <p>Approved Reviews</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['pending']; ?></h3>
                <p>Pending Approval</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-heart"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['avg_rating']; ?> <small>/5</small></h3>
                <p>Average Rating</p>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="admin-card">
        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="filter-group">
                    <label>Status:</label>
                    <select name="status">
                        <option value="">All Reviews</option>
                        <option value="approved" <?php echo $statusFilter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="pending" <?php echo $statusFilter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Rating:</label>
                    <select name="rating">
                        <option value="">All Ratings</option>
                        <option value="5" <?php echo $ratingFilter == '5' ? 'selected' : ''; ?>>5 Stars</option>
                        <option value="4" <?php echo $ratingFilter == '4' ? 'selected' : ''; ?>>4 Stars</option>
                        <option value="3" <?php echo $ratingFilter == '3' ? 'selected' : ''; ?>>3 Stars</option>
                        <option value="2" <?php echo $ratingFilter == '2' ? 'selected' : ''; ?>>2 Stars</option>
                        <option value="1" <?php echo $ratingFilter == '1' ? 'selected' : ''; ?>>1 Star</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Product:</label>
                    <select name="product">
                        <option value="">All Products</option>
                        <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>" <?php echo $productFilter == $product['id'] ? 'selected' : ''; ?>>
                            <?php echo $product['name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-outline">Filter</button>
                    <a href="reviews.php" class="btn btn-outline">Clear</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Reviews List -->
    <div class="admin-card">
        <div class="reviews-list">
            <?php if (empty($reviews)): ?>
            <div class="empty-state">
                <i class="fas fa-star"></i>
                <h3>No reviews found</h3>
                <p>No reviews match your current filters.</p>
            </div>
            <?php else: ?>
            <?php foreach ($reviews as $review): ?>
            <div class="review-item <?php echo !$review['is_approved'] ? 'pending' : ''; ?>">
                <div class="review-header">
                    <div class="review-product">
                        <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $review['product_slug']; ?>" target="_blank">
                            <?php echo $review['product_name']; ?>
                        </a>
                    </div>
                    <div class="review-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>"></i>
                        <?php endfor; ?>
                        <span class="rating-number"><?php echo $review['rating']; ?>/5</span>
                    </div>
                    <div class="review-status">
                        <?php if ($review['is_approved']): ?>
                        <span class="status-badge approved">Approved</span>
                        <?php else: ?>
                        <span class="status-badge pending">Pending</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="review-content">
                    <?php if ($review['title']): ?>
                    <h4 class="review-title"><?php echo htmlspecialchars($review['title']); ?></h4>
                    <?php endif; ?>
                    <p class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                </div>
                
                <div class="review-meta">
                    <div class="reviewer-info">
                        <span class="reviewer-name"><?php echo $review['first_name'] . ' ' . $review['last_name']; ?></span>
                        <span class="reviewer-email"><?php echo $review['email']; ?></span>
                        <span class="review-date"><?php echo date('M j, Y \a\t H:i', strtotime($review['created_at'])); ?></span>
                    </div>
                    
                    <div class="review-actions">
                        <?php if (!$review['is_approved']): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                        <?php else: ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-warning" title="Reject">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this review?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Reviews Management Styles */
.admin-reviews .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.filters-form {
    display: flex;
    gap: 1rem;
    align-items: end;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.filter-group label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #6c757d;
}

.filter-group select {
    padding: 0.5rem;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #dee2e6;
}

.reviews-list {
    padding: 0;
}

.review-item {
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.review-item:hover {
    background: #f8f9fa;
}

.review-item.pending {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
}

.review-item:last-child {
    border-bottom: none;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.review-product a {
    font-weight: 600;
    color: #007bff;
    text-decoration: none;
}

.review-product a:hover {
    text-decoration: underline;
}

.review-rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.review-rating .fa-star {
    color: #ddd;
    font-size: 0.875rem;
}

.review-rating .fa-star.filled {
    color: #ffc107;
}

.rating-number {
    font-weight: 600;
    color: #2c3e50;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.approved {
    background: #d4edda;
    color: #155724;
}

.status-badge.pending {
    background: #fff3cd;
    color: #856404;
}

.review-content {
    margin-bottom: 1rem;
}

.review-title {
    margin: 0 0 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    color: #2c3e50;
}

.review-comment {
    margin: 0;
    color: #495057;
    line-height: 1.6;
}

.review-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.reviewer-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.reviewer-name {
    font-weight: 500;
    color: #2c3e50;
}

.reviewer-email {
    font-size: 0.875rem;
    color: #6c757d;
}

.review-date {
    font-size: 0.75rem;
    color: #6c757d;
}

.review-actions {
    display: flex;
    gap: 0.25rem;
}

.btn-success {
    background: #28a745;
    border-color: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
    border-color: #1e7e34;
}

.btn-warning {
    background: #ffc107;
    border-color: #ffc107;
    color: #212529;
}

.btn-warning:hover {
    background: #e0a800;
    border-color: #d39e00;
}

@media (max-width: 768px) {
    .review-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .review-meta {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<script>
function exportReviews() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    params.set('csrf_token', window.csrfToken);
    
    window.location.href = `${window.location.pathname}?${params.toString()}`;
}
</script>

<?php include 'includes/admin_footer.php'; ?>
