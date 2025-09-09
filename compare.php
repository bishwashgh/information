<?php
require_once 'includes/config.php';

$pageTitle = 'Product Comparison';
$db = Database::getInstance()->getConnection();

// Get product IDs from URL
$productIds = [];
if (isset($_GET['products'])) {
    $productIds = array_filter(array_map('intval', explode(',', $_GET['products'])));
}

// Limit to maximum 4 products
$productIds = array_slice($productIds, 0, 4);

if (empty($productIds)) {
    header('Location: ' . SITE_URL . '/products.php');
    exit;
}

// Get product details
$placeholders = str_repeat('?,', count($productIds) - 1) . '?';
$sql = "
    SELECT p.*, c.name as category_name,
           (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
           (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1) as avg_rating,
           (SELECT COUNT(*) FROM reviews WHERE product_id = p.id AND is_approved = 1) as review_count
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id IN ($placeholders) AND p.status = 'active'
    ORDER BY FIELD(p.id, " . implode(',', $productIds) . ")
";

$stmt = $db->prepare($sql);
$stmt->execute($productIds);
$products = $stmt->fetchAll();

if (empty($products)) {
    header('Location: ' . SITE_URL . '/products.php');
    exit;
}

// Get product specifications
$productSpecs = [];
foreach ($products as $product) {
    $stmt = $db->prepare("SELECT spec_name, spec_value FROM product_specifications WHERE product_id = ? ORDER BY spec_name");
    $stmt->execute([$product['id']]);
    $specs = $stmt->fetchAll();
    $productSpecs[$product['id']] = $specs;
}

// Get all unique specification names
$allSpecs = [];
foreach ($productSpecs as $specs) {
    foreach ($specs as $spec) {
        if (!in_array($spec['spec_name'], $allSpecs)) {
            $allSpecs[] = $spec['spec_name'];
        }
    }
}
sort($allSpecs);
?>

<?php include 'includes/header.php'; ?>

<div class="comparison-page">
    <div class="container">
        <!-- Page Header -->
        <div class="comparison-header">
            <div class="header-content">
                <h1><i class="fas fa-balance-scale"></i> Product Comparison</h1>
                <p>Compare features, specifications, and prices</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-outline" onclick="printComparison()">
                    <i class="fas fa-print"></i>
                    Print
                </button>
                <button class="btn btn-outline" onclick="shareComparison()">
                    <i class="fas fa-share-alt"></i>
                    Share
                </button>
            </div>
        </div>
        
        <!-- Comparison Table -->
        <div class="comparison-table-wrapper">
            <table class="comparison-table">
                <!-- Product Images and Names -->
                <thead>
                    <tr class="product-header">
                        <th class="feature-column">Products</th>
                        <?php foreach ($products as $product): ?>
                        <th class="product-column">
                            <div class="product-header-content">
                                <div class="product-image">
                                    <img src="<?php echo $product['primary_image'] ?: SITE_URL . '/assets/images/placeholder.jpg'; ?>" 
                                         alt="<?php echo $product['name']; ?>">
                                </div>
                                <div class="product-basic-info">
                                    <h3 class="product-name">
                                        <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $product['slug']; ?>" target="_blank">
                                            <?php echo $product['name']; ?>
                                        </a>
                                    </h3>
                                    <div class="product-category"><?php echo $product['category_name']; ?></div>
                                </div>
                                <button class="remove-product-btn" onclick="removeFromComparison(<?php echo $product['id']; ?>)" title="Remove from comparison">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                
                <tbody>
                    <!-- Price Comparison -->
                    <tr class="feature-row price-row">
                        <td class="feature-name">
                            <i class="fas fa-rupee-sign"></i>
                            Price
                        </td>
                        <?php foreach ($products as $product): ?>
                        <td class="feature-value">
                            <div class="price-comparison">
                                <?php if ($product['sale_price']): ?>
                                <div class="sale-price">₹<?php echo number_format($product['sale_price']); ?></div>
                                <div class="original-price">₹<?php echo number_format($product['price']); ?></div>
                                <div class="discount-badge">
                                    <?php echo round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>% OFF
                                </div>
                                <?php else: ?>
                                <div class="current-price">₹<?php echo number_format($product['price']); ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <!-- Rating Comparison -->
                    <tr class="feature-row rating-row">
                        <td class="feature-name">
                            <i class="fas fa-star"></i>
                            Rating
                        </td>
                        <?php foreach ($products as $product): ?>
                        <td class="feature-value">
                            <?php if ($product['avg_rating']): ?>
                            <div class="rating-comparison">
                                <div class="stars">
                                    <?php
                                    $rating = round($product['avg_rating']);
                                    for ($i = 1; $i <= 5; $i++):
                                    ?>
                                    <i class="star <?php echo $i <= $rating ? 'fas filled' : 'far'; ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="rating-text">
                                    <?php echo number_format($product['avg_rating'], 1); ?>/5 
                                    (<?php echo $product['review_count']; ?> reviews)
                                </div>
                            </div>
                            <?php else: ?>
                            <span class="no-rating">No reviews yet</span>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <!-- Stock Status -->
                    <tr class="feature-row stock-row">
                        <td class="feature-name">
                            <i class="fas fa-boxes"></i>
                            Availability
                        </td>
                        <?php foreach ($products as $product): ?>
                        <td class="feature-value">
                            <?php if ($product['stock_quantity'] > 0): ?>
                            <span class="in-stock">
                                <i class="fas fa-check-circle"></i>
                                In Stock (<?php echo $product['stock_quantity']; ?> available)
                            </span>
                            <?php else: ?>
                            <span class="out-of-stock">
                                <i class="fas fa-times-circle"></i>
                                Out of Stock
                            </span>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <!-- Description -->
                    <tr class="feature-row description-row">
                        <td class="feature-name">
                            <i class="fas fa-info-circle"></i>
                            Description
                        </td>
                        <?php foreach ($products as $product): ?>
                        <td class="feature-value">
                            <div class="description-text">
                                <?php echo $product['short_description'] ?: substr($product['description'], 0, 150) . '...'; ?>
                            </div>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <!-- Specifications -->
                    <?php if (!empty($allSpecs)): ?>
                    <tr class="section-header">
                        <td colspan="<?php echo count($products) + 1; ?>">
                            <h3><i class="fas fa-cogs"></i> Specifications</h3>
                        </td>
                    </tr>
                    
                    <?php foreach ($allSpecs as $specName): ?>
                    <tr class="feature-row spec-row">
                        <td class="feature-name"><?php echo $specName; ?></td>
                        <?php foreach ($products as $product): ?>
                        <td class="feature-value">
                            <?php
                            $specValue = '';
                            foreach ($productSpecs[$product['id']] as $spec) {
                                if ($spec['spec_name'] === $specName) {
                                    $specValue = $spec['spec_value'];
                                    break;
                                }
                            }
                            echo $specValue ?: '<span class="not-specified">Not specified</span>';
                            ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <!-- Action Buttons -->
                    <tr class="action-row">
                        <td class="feature-name">Actions</td>
                        <?php foreach ($products as $product): ?>
                        <td class="feature-value">
                            <div class="product-actions">
                                <?php if ($product['stock_quantity'] > 0): ?>
                                <button class="btn btn-primary" onclick="addToCart(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-shopping-cart"></i>
                                    Add to Cart
                                </button>
                                <?php else: ?>
                                <button class="btn btn-secondary" disabled>
                                    Out of Stock
                                </button>
                                <?php endif; ?>
                                
                                <button class="btn btn-outline" onclick="addToWishlist(<?php echo $product['id']; ?>)">
                                    <i class="far fa-heart"></i>
                                    Wishlist
                                </button>
                                
                                <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $product['slug']; ?>" 
                                   class="btn btn-outline" target="_blank">
                                    <i class="fas fa-eye"></i>
                                    View Details
                                </a>
                            </div>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Comparison Summary -->
        <div class="comparison-summary">
            <h3>Comparison Summary</h3>
            <div class="summary-grid">
                <div class="summary-card">
                    <h4>Most Affordable</h4>
                    <?php
                    $cheapestProduct = null;
                    $lowestPrice = PHP_INT_MAX;
                    foreach ($products as $product) {
                        $price = $product['sale_price'] ?: $product['price'];
                        if ($price < $lowestPrice) {
                            $lowestPrice = $price;
                            $cheapestProduct = $product;
                        }
                    }
                    ?>
                    <div class="summary-product">
                        <img src="<?php echo $cheapestProduct['primary_image'] ?: SITE_URL . '/assets/images/placeholder.jpg'; ?>" 
                             alt="<?php echo $cheapestProduct['name']; ?>">
                        <div>
                            <div class="product-name"><?php echo $cheapestProduct['name']; ?></div>
                            <div class="product-price">₹<?php echo number_format($lowestPrice); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="summary-card">
                    <h4>Highest Rated</h4>
                    <?php
                    $bestRatedProduct = null;
                    $highestRating = 0;
                    foreach ($products as $product) {
                        if ($product['avg_rating'] > $highestRating) {
                            $highestRating = $product['avg_rating'];
                            $bestRatedProduct = $product;
                        }
                    }
                    ?>
                    <?php if ($bestRatedProduct && $bestRatedProduct['avg_rating'] > 0): ?>
                    <div class="summary-product">
                        <img src="<?php echo $bestRatedProduct['primary_image'] ?: SITE_URL . '/assets/images/placeholder.jpg'; ?>" 
                             alt="<?php echo $bestRatedProduct['name']; ?>">
                        <div>
                            <div class="product-name"><?php echo $bestRatedProduct['name']; ?></div>
                            <div class="product-rating">
                                <?php echo number_format($bestRatedProduct['avg_rating'], 1); ?>/5 
                                (<?php echo $bestRatedProduct['review_count']; ?> reviews)
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="no-data">No rated products</div>
                    <?php endif; ?>
                </div>
                
                <div class="summary-card">
                    <h4>Best Value</h4>
                    <?php
                    $bestValueProduct = null;
                    $bestValue = 0;
                    foreach ($products as $product) {
                        $price = $product['sale_price'] ?: $product['price'];
                        $rating = $product['avg_rating'] ?: 0;
                        $value = $rating > 0 ? $rating / ($price / 1000) : 0;
                        if ($value > $bestValue) {
                            $bestValue = $value;
                            $bestValueProduct = $product;
                        }
                    }
                    ?>
                    <?php if ($bestValueProduct && $bestValueProduct['avg_rating'] > 0): ?>
                    <div class="summary-product">
                        <img src="<?php echo $bestValueProduct['primary_image'] ?: SITE_URL . '/assets/images/placeholder.jpg'; ?>" 
                             alt="<?php echo $bestValueProduct['name']; ?>">
                        <div>
                            <div class="product-name"><?php echo $bestValueProduct['name']; ?></div>
                            <div class="value-score">Best price-to-rating ratio</div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="no-data">Insufficient data</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Add More Products -->
        <div class="add-more-section">
            <h3>Add More Products to Compare</h3>
            <p>You can compare up to 4 products. <a href="<?php echo SITE_URL; ?>/products.php">Browse products</a> to add more.</p>
        </div>
    </div>
</div>

<style>
/* Comparison Page Styles */
.comparison-page {
    padding: 2rem 0;
    min-height: 70vh;
}

.comparison-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.header-content h1 {
    margin: 0 0 0.5rem;
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
}

.header-content h1 i {
    color: #007bff;
    margin-right: 0.5rem;
}

.header-content p {
    margin: 0;
    color: #6c757d;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
}

/* Comparison Table */
.comparison-table-wrapper {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    overflow: hidden;
    margin-bottom: 2rem;
}

.comparison-table {
    width: 100%;
    border-collapse: collapse;
}

.comparison-table th,
.comparison-table td {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    vertical-align: top;
}

.feature-column {
    background: #f8f9fa;
    font-weight: 600;
    color: #2c3e50;
    width: 200px;
    position: sticky;
    left: 0;
    z-index: 10;
}

.product-column {
    min-width: 250px;
    background: white;
    text-align: center;
}

.product-header-content {
    position: relative;
}

.product-image {
    width: 120px;
    height: 120px;
    margin: 0 auto 1rem;
    border-radius: 0.5rem;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-name {
    margin: 0 0 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    line-height: 1.4;
}

.product-name a {
    color: #2c3e50;
    text-decoration: none;
}

.product-name a:hover {
    color: #007bff;
}

.product-category {
    font-size: 0.875rem;
    color: #6c757d;
}

.remove-product-btn {
    position: absolute;
    top: -0.5rem;
    right: -0.5rem;
    background: #dc3545;
    color: white;
    border: none;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.remove-product-btn:hover {
    background: #c82333;
    transform: scale(1.1);
}

/* Feature Rows */
.feature-row:hover {
    background: rgba(0, 123, 255, 0.05);
}

.feature-name {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}

.feature-name i {
    color: #007bff;
    width: 1rem;
}

.section-header td {
    background: #f8f9fa;
    padding: 1.5rem 1rem;
    border-top: 2px solid #007bff;
}

.section-header h3 {
    margin: 0;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Price Comparison */
.price-comparison {
    text-align: center;
}

.sale-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #dc3545;
}

.original-price {
    font-size: 0.875rem;
    color: #6c757d;
    text-decoration: line-through;
    margin-top: 0.25rem;
}

.current-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #2c3e50;
}

.discount-badge {
    background: #dc3545;
    color: white;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.625rem;
    font-weight: 600;
    text-transform: uppercase;
    margin-top: 0.25rem;
    display: inline-block;
}

/* Rating Comparison */
.rating-comparison {
    text-align: center;
}

.stars {
    display: flex;
    justify-content: center;
    gap: 0.125rem;
    margin-bottom: 0.25rem;
}

.star {
    color: #ddd;
    font-size: 0.875rem;
}

.star.filled {
    color: #ffc107;
}

.rating-text {
    font-size: 0.875rem;
    color: #6c757d;
}

.no-rating {
    color: #6c757d;
    font-style: italic;
}

/* Stock Status */
.in-stock {
    color: #28a745;
    font-weight: 500;
}

.out-of-stock {
    color: #dc3545;
    font-weight: 500;
}

/* Description */
.description-text {
    text-align: left;
    line-height: 1.5;
    color: #495057;
    font-size: 0.875rem;
}

/* Specifications */
.not-specified {
    color: #6c757d;
    font-style: italic;
}

/* Actions */
.product-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    align-items: center;
}

.product-actions .btn {
    width: 100%;
    max-width: 200px;
}

/* Comparison Summary */
.comparison-summary {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    padding: 2rem;
    margin-bottom: 2rem;
}

.comparison-summary h3 {
    margin: 0 0 1.5rem;
    color: #2c3e50;
    font-size: 1.5rem;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.summary-card {
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    padding: 1.5rem;
    text-align: center;
}

.summary-card h4 {
    margin: 0 0 1rem;
    color: #007bff;
    font-size: 1.125rem;
}

.summary-product {
    display: flex;
    align-items: center;
    gap: 1rem;
    text-align: left;
}

.summary-product img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 0.5rem;
    flex-shrink: 0;
}

.summary-product .product-name {
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.summary-product .product-price {
    color: #dc3545;
    font-weight: 700;
}

.summary-product .product-rating {
    color: #ffc107;
    font-size: 0.875rem;
}

.summary-product .value-score {
    color: #28a745;
    font-size: 0.875rem;
    font-weight: 500;
}

.no-data {
    color: #6c757d;
    font-style: italic;
}

/* Add More Section */
.add-more-section {
    background: #f8f9fa;
    border-radius: 1rem;
    padding: 2rem;
    text-align: center;
}

.add-more-section h3 {
    margin: 0 0 0.5rem;
    color: #2c3e50;
}

.add-more-section p {
    margin: 0;
    color: #6c757d;
}

.add-more-section a {
    color: #007bff;
    text-decoration: none;
    font-weight: 500;
}

.add-more-section a:hover {
    text-decoration: underline;
}

/* Responsive Design */
@media (max-width: 768px) {
    .comparison-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .header-actions {
        justify-content: center;
    }
    
    .comparison-table-wrapper {
        overflow-x: auto;
    }
    
    .feature-column {
        position: static;
        min-width: 150px;
    }
    
    .product-column {
        min-width: 200px;
    }
    
    .summary-grid {
        grid-template-columns: 1fr;
    }
    
    .summary-product {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script>
function removeFromComparison(productId) {
    // Remove from localStorage
    let compareProducts = JSON.parse(localStorage.getItem('compareProducts') || '[]');
    compareProducts = compareProducts.filter(id => id !== productId);
    localStorage.setItem('compareProducts', JSON.stringify(compareProducts));
    
    // Reload page with updated product list
    const currentParams = new URLSearchParams(window.location.search);
    const currentProducts = currentParams.get('products').split(',').map(id => parseInt(id));
    const updatedProducts = currentProducts.filter(id => id !== productId);
    
    if (updatedProducts.length === 0) {
        window.location.href = window.siteUrl + '/products.php';
    } else {
        window.location.href = window.siteUrl + '/compare.php?products=' + updatedProducts.join(',');
    }
}

function printComparison() {
    window.print();
}

function shareComparison() {
    const url = window.location.href;
    if (navigator.share) {
        navigator.share({
            title: 'Product Comparison',
            url: url
        });
    } else {
        navigator.clipboard.writeText(url).then(function() {
            showToast('Comparison link copied to clipboard!', 'success');
        });
    }
}

// Print styles
const printStyles = `
@media print {
    .comparison-header .header-actions,
    .remove-product-btn,
    .product-actions {
        display: none !important;
    }
    
    .comparison-table {
        font-size: 12px;
    }
    
    .comparison-table th,
    .comparison-table td {
        padding: 0.5rem;
    }
}
`;

const styleSheet = document.createElement('style');
styleSheet.type = 'text/css';
styleSheet.innerText = printStyles;
document.head.appendChild(styleSheet);
</script>

<?php include 'includes/footer.php'; ?>
