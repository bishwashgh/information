<?php
require_once 'includes/config.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: ' . SITE_URL . '/products.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Get product details
$stmt = $db->prepare("
    SELECT p.*, c.name as category_name, c.slug as category_slug
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.slug = ? AND p.status = 'active'
");
$stmt->execute([$slug]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: ' . SITE_URL . '/products.php');
    exit;
}

// Get product images
$stmt = $db->prepare("
    SELECT * FROM product_images 
    WHERE product_id = ? 
    ORDER BY is_primary DESC, sort_order ASC
");
$stmt->execute([$product['id']]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get product attributes
$stmt = $db->prepare("
    SELECT DISTINCT attribute_name, attribute_value 
    FROM product_attributes 
    WHERE product_id = ? 
    ORDER BY attribute_name, attribute_value
");
$stmt->execute([$product['id']]);
$attributesRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group attributes by name
$attributes = [];
foreach ($attributesRaw as $attr) {
    $attributes[$attr['attribute_name']][] = $attr['attribute_value'];
}

// Get related products
$stmt = $db->prepare("
    SELECT p.*, c.name as category_name, c.slug as category_slug,
           (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
    FROM products p 
    JOIN categories c ON p.category_id = c.id
    WHERE p.category_id = ? AND p.id != ? AND p.status = 'active'
    ORDER BY RAND()
    LIMIT 4
");
$stmt->execute([$product['category_id'], $product['id']]);
$relatedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get product reviews
$stmt = $db->prepare("
    SELECT r.*, u.first_name, u.last_name 
    FROM product_reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = ? AND r.is_approved = 1 
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt->execute([$product['id']]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate average rating
$avgRating = 0;
if (!empty($reviews)) {
    $avgRating = array_sum(array_column($reviews, 'rating')) / count($reviews);
}

$pageTitle = $product['name'];
$pageDescription = $product['short_description'] ?: $product['description'];
$pageKeywords = $product['name'] . ', ' . $product['category_name'] . ', buy online, ecommerce';
?>

<?php include 'includes/header.php'; ?>

<!-- Breadcrumb -->
<div class="breadcrumb-section">
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>/">Home</a>
            <span class="breadcrumb-separator">/</span>
            <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $product['category_slug']; ?>">
                <?php echo $product['category_name']; ?>
            </a>
            <span class="breadcrumb-separator">/</span>
            <span><?php echo $product['name']; ?></span>
        </nav>
    </div>
</div>

<!-- Product Details -->
<section class="product-details">
    <div class="container">
        <div class="product-layout">
            <!-- Product Images -->
            <div class="product-images">
                <div class="main-image">
                    <?php if (!empty($images)): ?>
                        <img src="<?php echo $images[0]['image_url']; ?>" 
                             alt="<?php echo $product['name']; ?>" 
                             class="product-image-main" id="mainImage">
                    <?php else: ?>
                        <img src="https://images.unsplash.com/photo-1523381210834-895b31b4-3b0c?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=600" 
                             alt="<?php echo $product['name']; ?>" 
                             class="product-image-main" id="mainImage">
                    <?php endif; ?>
                </div>
                
                <?php if (count($images) > 1): ?>
                <div class="thumbnail-images">
                    <?php foreach ($images as $index => $image): ?>
                    <img src="<?php echo $image['image_url']; ?>" 
                         alt="<?php echo $product['name']; ?>" 
                         class="product-image-thumb <?php echo $index === 0 ? 'active' : ''; ?>"
                         onclick="changeMainImage(this.src)">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Product Info -->
            <div class="product-info">
                <div class="product-category"><?php echo $product['category_name']; ?></div>
                <h1 class="product-title"><?php echo $product['name']; ?></h1>
                
                <div class="product-rating">
                    <div class="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="star <?php echo $i <= round($avgRating) ? 'fas filled' : 'far'; ?> fa-star"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="rating-text"><?php echo number_format($avgRating, 1); ?> (<?php echo count($reviews); ?> reviews)</span>
                </div>
                
                <div class="product-price">
                    <?php if ($product['sale_price']): ?>
                        <span class="price-current"><?php echo formatPrice($product['sale_price']); ?></span>
                        <span class="price-original"><?php echo formatPrice($product['price']); ?></span>
                        <span class="price-discount">
                            <?php echo round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>% OFF
                        </span>
                    <?php else: ?>
                        <span class="price-current"><?php echo formatPrice($product['price']); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if ($product['short_description']): ?>
                <div class="product-summary">
                    <p><?php echo nl2br($product['short_description']); ?></p>
                </div>
                <?php endif; ?>
                
                <form class="product-form" id="addToCartForm">
                    <!-- Product Attributes -->
                    <?php if (!empty($attributes)): ?>
                    <div class="product-attributes">
                        <?php foreach ($attributes as $attrName => $attrValues): ?>
                        <div class="attribute-group">
                            <label class="attribute-label"><?php echo ucfirst($attrName); ?>:</label>
                            <div class="attribute-options">
                                <?php foreach ($attrValues as $value): ?>
                                <label class="attribute-option">
                                    <input type="radio" name="attribute_<?php echo $attrName; ?>" 
                                           value="<?php echo $value; ?>" 
                                           class="product-attribute" 
                                           data-attribute="<?php echo $attrName; ?>">
                                    <span><?php echo $value; ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Quantity -->
                    <div class="quantity-group">
                        <label class="quantity-label">Quantity:</label>
                        <div class="quantity-controls">
                            <button type="button" class="qty-btn minus" onclick="updateQuantity(-1)">-</button>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                            <button type="button" class="qty-btn plus" onclick="updateQuantity(1)">+</button>
                        </div>
                        <span class="stock-info">
                            <?php if ($product['manage_stock']): ?>
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <?php echo $product['stock_quantity']; ?> in stock
                                <?php else: ?>
                                    <span class="out-of-stock">Out of stock</span>
                                <?php endif; ?>
                            <?php else: ?>
                                In stock
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="product-actions">
                        <?php if (!$product['manage_stock'] || $product['stock_quantity'] > 0): ?>
                        <button type="submit" class="btn btn-primary btn-lg add-to-cart-btn">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                        <?php else: ?>
                        <button type="button" class="btn btn-secondary btn-lg" disabled>
                            Out of Stock
                        </button>
                        <?php endif; ?>
                        
                        <button type="button" class="btn btn-outline btn-lg buy-now-btn">
                            <i class="fas fa-bolt"></i> Buy Now
                        </button>
                        
                        <?php if (isLoggedIn()): ?>
                        <button type="button" class="btn btn-outline wishlist-btn" 
                                data-product-id="<?php echo $product['id']; ?>">
                            <i class="far fa-heart"></i> Add to Wishlist
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
                
                <!-- Product Features -->
                <div class="product-features">
                    <div class="feature">
                        <i class="fas fa-truck"></i>
                        <span>Free delivery on orders over Rs. 2000</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-undo"></i>
                        <span>30-day return policy</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-shield-alt"></i>
                        <span>Secure payment guaranteed</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Product Tabs -->
<section class="product-tabs">
    <div class="container">
        <div class="tabs-container">
            <div class="tab-buttons">
                <button class="tab-btn active" data-tab="description">Description</button>
                <button class="tab-btn" data-tab="reviews">Reviews (<?php echo count($reviews); ?>)</button>
                <button class="tab-btn" data-tab="shipping">Shipping Info</button>
            </div>
            
            <div class="tab-content">
                <!-- Description Tab -->
                <div class="tab-pane active" id="description">
                    <div class="description-content">
                        <?php if ($product['description']): ?>
                            <?php echo nl2br($product['description']); ?>
                        <?php else: ?>
                            <p>No detailed description available for this product.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Reviews Tab -->
                <div class="tab-pane" id="reviews">
                    <div class="reviews-section">
                        <?php if (isLoggedIn()): ?>
                        <div class="review-form-section">
                            <h3>Write a Review</h3>
                            <form class="review-form" id="reviewForm">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <div class="rating-input">
                                    <label>Rating:</label>
                                    <div class="star-rating-input">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>">
                                        <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="reviewTitle">Review Title</label>
                                    <input type="text" name="title" id="reviewTitle" required>
                                </div>
                                <div class="form-group">
                                    <label for="reviewComment">Your Review</label>
                                    <textarea name="comment" id="reviewComment" rows="4" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Submit Review</button>
                            </form>
                        </div>
                        <?php endif; ?>
                        
                        <div class="reviews-list">
                            <?php if (empty($reviews)): ?>
                            <p>No reviews yet. Be the first to review this product!</p>
                            <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <span class="reviewer-name"><?php echo $review['first_name'] . ' ' . substr($review['last_name'], 0, 1) . '.'; ?></span>
                                        <div class="review-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="star <?php echo $i <= $review['rating'] ? 'fas filled' : 'far'; ?> fa-star"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <span class="review-date"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></span>
                                </div>
                                <?php if ($review['title']): ?>
                                <h4 class="review-title"><?php echo $review['title']; ?></h4>
                                <?php endif; ?>
                                <p class="review-comment"><?php echo nl2br($review['comment']); ?></p>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Shipping Tab -->
                <div class="tab-pane" id="shipping">
                    <div class="shipping-info">
                        <h3>Delivery Information</h3>
                        <ul>
                            <li><strong>Standard Delivery:</strong> 2-5 business days - Rs. 100</li>
                            <li><strong>Express Delivery:</strong> 1-2 business days - Rs. 200</li>
                            <li><strong>Free Delivery:</strong> On orders over Rs. 2000</li>
                        </ul>
                        
                        <h3>Return Policy</h3>
                        <ul>
                            <li>30-day return window</li>
                            <li>Items must be in original condition</li>
                            <li>Free returns for defective items</li>
                            <li>Customer pays return shipping for other returns</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Related Products -->
<?php if (!empty($relatedProducts)): ?>
<section class="related-products">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">You Might Also Like</h2>
            <p class="section-subtitle">Similar products from the same category</p>
        </div>
        <div class="product-grid">
            <?php foreach ($relatedProducts as $relatedProduct): ?>
            <div class="product-card">
                <div class="product-image">
                    <?php if ($relatedProduct['image']): ?>
                        <img src="<?php echo $relatedProduct['image']; ?>" 
                             alt="<?php echo $relatedProduct['name']; ?>" 
                             loading="lazy">
                    <?php else: ?>
                        <img src="https://images.unsplash.com/photo-1523381210834-895b31b4-3b0c?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&h=250" 
                             alt="<?php echo $relatedProduct['name']; ?>" 
                             loading="lazy">
                    <?php endif; ?>
                    
                    <?php if ($relatedProduct['sale_price']): ?>
                    <div class="product-badge">Sale</div>
                    <?php endif; ?>
                    
                    <div class="product-actions">
                        <?php if (isLoggedIn()): ?>
                        <button class="product-action add-to-wishlist" 
                                data-product-id="<?php echo $relatedProduct['id']; ?>" 
                                title="Add to Wishlist">
                            <i class="far fa-heart"></i>
                        </button>
                        <?php endif; ?>
                        <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $relatedProduct['slug']; ?>" 
                           class="product-action" title="View Details">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </div>
                
                <div class="product-info">
                    <div class="product-category"><?php echo $relatedProduct['category_name']; ?></div>
                    <h3 class="product-title">
                        <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $relatedProduct['slug']; ?>">
                            <?php echo $relatedProduct['name']; ?>
                        </a>
                    </h3>
                    
                    <div class="product-rating">
                        <div class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="star <?php echo $i <= 4 ? 'fas filled' : 'far'; ?> fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-count">(<?php echo rand(5, 25); ?> reviews)</span>
                    </div>
                    
                    <div class="product-price">
                        <?php if ($relatedProduct['sale_price']): ?>
                            <span class="price-current"><?php echo formatPrice($relatedProduct['sale_price']); ?></span>
                            <span class="price-original"><?php echo formatPrice($relatedProduct['price']); ?></span>
                            <span class="price-discount">
                                <?php echo round((($relatedProduct['price'] - $relatedProduct['sale_price']) / $relatedProduct['price']) * 100); ?>% OFF
                            </span>
                        <?php else: ?>
                            <span class="price-current"><?php echo formatPrice($relatedProduct['price']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-actions-bottom">
                        <button class="btn btn-primary add-to-cart" 
                                data-product-id="<?php echo $relatedProduct['id']; ?>">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<style>
/* Product Details Styles */
.product-details {
    padding: var(--spacing-8) 0;
}

.product-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-12);
    align-items: start;
}

/* Product Images */
.product-images {
    position: sticky;
    top: var(--spacing-8);
}

.main-image {
    margin-bottom: var(--spacing-4);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow);
}

.product-image-main {
    width: 100%;
    height: 500px;
    object-fit: cover;
    cursor: zoom-in;
    transition: var(--transition);
}

.thumbnail-images {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
    gap: var(--spacing-3);
}

.product-image-thumb {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: var(--border-radius);
    cursor: pointer;
    border: 2px solid transparent;
    transition: var(--transition-fast);
}

.product-image-thumb:hover,
.product-image-thumb.active {
    border-color: var(--primary-color);
}

/* Product Info */
.product-info {
    padding: var(--spacing-4) 0;
}

.product-category {
    font-size: var(--font-size-sm);
    color: var(--primary-color);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: var(--spacing-2);
}

.product-title {
    font-size: var(--font-size-3xl);
    color: var(--gray-900);
    margin-bottom: var(--spacing-4);
    line-height: 1.2;
}

.product-rating {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    margin-bottom: var(--spacing-6);
}

.rating-text {
    color: var(--gray-600);
    font-size: var(--font-size-sm);
}

.product-price {
    margin-bottom: var(--spacing-6);
}

.product-price .price-current {
    font-size: var(--font-size-3xl);
    font-weight: 700;
    color: var(--gray-900);
}

.product-price .price-original {
    font-size: var(--font-size-xl);
    color: var(--gray-500);
    text-decoration: line-through;
    margin-left: var(--spacing-3);
}

.product-price .price-discount {
    background: var(--success-color);
    color: var(--white);
    padding: var(--spacing-1) var(--spacing-2);
    border-radius: var(--border-radius);
    font-size: var(--font-size-sm);
    font-weight: 600;
    margin-left: var(--spacing-3);
}

.product-summary {
    margin-bottom: var(--spacing-6);
    padding: var(--spacing-4);
    background: var(--gray-50);
    border-radius: var(--border-radius);
}

/* Product Form */
.product-form {
    margin-bottom: var(--spacing-8);
}

.product-attributes {
    margin-bottom: var(--spacing-6);
}

.attribute-group {
    margin-bottom: var(--spacing-5);
}

.attribute-label {
    display: block;
    font-weight: 600;
    color: var(--gray-900);
    margin-bottom: var(--spacing-3);
}

.attribute-options {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-2);
}

.attribute-option {
    position: relative;
    cursor: pointer;
}

.attribute-option input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.attribute-option span {
    display: block;
    padding: var(--spacing-2) var(--spacing-4);
    border: 2px solid var(--gray-300);
    border-radius: var(--border-radius);
    transition: var(--transition-fast);
    background: var(--white);
}

.attribute-option:hover span,
.attribute-option input:checked + span {
    border-color: var(--primary-color);
    background: var(--primary-color);
    color: var(--white);
}

.quantity-group {
    display: flex;
    align-items: center;
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-6);
}

.quantity-label {
    font-weight: 600;
    color: var(--gray-900);
}

.quantity-controls {
    display: flex;
    align-items: center;
    border: 2px solid var(--gray-300);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.qty-btn {
    background: var(--gray-100);
    border: none;
    width: 40px;
    height: 40px;
    cursor: pointer;
    transition: var(--transition-fast);
}

.qty-btn:hover {
    background: var(--gray-200);
}

#quantity {
    width: 60px;
    height: 40px;
    text-align: center;
    border: none;
    background: var(--white);
}

.stock-info {
    color: var(--gray-600);
    font-size: var(--font-size-sm);
}

.out-of-stock {
    color: var(--danger-color);
    font-weight: 600;
}

.product-actions {
    display: flex;
    gap: var(--spacing-3);
    margin-bottom: var(--spacing-6);
}

.product-features {
    border-top: 1px solid var(--gray-200);
    padding-top: var(--spacing-6);
}

.feature {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    margin-bottom: var(--spacing-3);
    color: var(--gray-600);
}

.feature i {
    color: var(--primary-color);
    width: 20px;
}

/* Product Tabs */
.product-tabs {
    padding: var(--spacing-8) 0;
    background: var(--gray-50);
}

.tabs-container {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.tab-buttons {
    display: flex;
    border-bottom: 1px solid var(--gray-200);
}

.tab-btn {
    flex: 1;
    padding: var(--spacing-4) var(--spacing-6);
    background: var(--gray-50);
    border: none;
    cursor: pointer;
    transition: var(--transition-fast);
    font-weight: 500;
}

.tab-btn:hover,
.tab-btn.active {
    background: var(--white);
    color: var(--primary-color);
}

.tab-content {
    padding: var(--spacing-8);
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

/* Reviews */
.reviews-section {
    max-width: 800px;
}

.review-form-section {
    margin-bottom: var(--spacing-8);
    padding-bottom: var(--spacing-6);
    border-bottom: 1px solid var(--gray-200);
}

.star-rating-input {
    display: flex;
    flex-direction: row-reverse;
    gap: var(--spacing-1);
    margin-bottom: var(--spacing-4);
}

.star-rating-input input {
    display: none;
}

.star-rating-input label {
    cursor: pointer;
    color: var(--gray-300);
    font-size: var(--font-size-xl);
    transition: var(--transition-fast);
}

.star-rating-input label:hover,
.star-rating-input input:checked ~ label {
    color: var(--accent-color);
}

.review-item {
    margin-bottom: var(--spacing-6);
    padding-bottom: var(--spacing-6);
    border-bottom: 1px solid var(--gray-200);
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-3);
}

.reviewer-info {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
}

.reviewer-name {
    font-weight: 600;
    color: var(--gray-900);
}

.review-date {
    color: var(--gray-500);
    font-size: var(--font-size-sm);
}

.review-title {
    font-size: var(--font-size-lg);
    color: var(--gray-900);
    margin-bottom: var(--spacing-2);
}

.review-comment {
    color: var(--gray-700);
    line-height: 1.6;
    margin: 0;
}

/* Related Products */
.related-products {
    padding: 4rem 0;
    background: #f8f9fa;
    margin-top: 3rem;
}

.related-products .section-header {
    text-align: center;
    margin-bottom: 3rem;
}

.related-products .section-title {
    font-size: 2.25rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 1rem;
}

.related-products .section-subtitle {
    font-size: 1.125rem;
    color: #6b7280;
    margin: 0;
}

/* Override product grid for related products */
.related-products .product-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
    margin-bottom: 0;
}

.related-products .product-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    border: 1px solid #f0f0f0;
}

.related-products .product-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
    border-color: #e0e0e0;
}

.related-products .product-image {
    position: relative;
    overflow: hidden;
    height: 200px;
    background: #f8f9fa;
}

.related-products .product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.related-products .product-card:hover .product-image img {
    transform: scale(1.08);
}

.related-products .product-info {
    padding: 20px;
}

.related-products .product-category {
    font-size: 11px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 8px;
    font-weight: 600;
}

.related-products .product-title {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 12px;
    line-height: 1.4;
}

.related-products .product-title a {
    color: inherit;
    text-decoration: none;
    transition: color 0.3s ease;
}

.related-products .product-title a:hover {
    color: var(--primary-color);
}

/* Responsive design for related products */
@media (max-width: 1200px) {
    .related-products .product-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 1.25rem;
    }
}

@media (max-width: 992px) {
    .related-products .product-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .related-products .product-image {
        height: 180px;
    }
}

@media (max-width: 768px) {
    .related-products {
        padding: 2rem 0;
    }
    
    .related-products .section-header {
        margin-bottom: 2rem;
    }
    
    .related-products .section-title {
        font-size: 1.875rem;
    }
    
    .related-products .product-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .related-products .product-image {
        height: 160px;
    }
}

@media (max-width: 480px) {
    .related-products .product-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .related-products .product-card {
        max-width: 320px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .related-products .product-image {
        height: 200px;
    }
}

/* Related Products - Product Actions & Badges */
.related-products .product-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: linear-gradient(135deg, #ff4757, #ff3838);
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    z-index: 2;
    box-shadow: 0 2px 8px rgba(255, 71, 87, 0.3);
}

.related-products .product-actions {
    position: absolute;
    top: 12px;
    right: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    opacity: 0;
    transform: translateX(20px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 3;
}

.related-products .product-card:hover .product-actions {
    opacity: 1;
    transform: translateX(0);
}

.related-products .product-action {
    width: 36px;
    height: 36px;
    background: rgba(255, 255, 255, 0.95);
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(10px);
    font-size: 14px;
    color: #374151;
    text-decoration: none;
}

.related-products .product-action:hover {
    background: var(--primary-color);
    color: white;
    transform: scale(1.15);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.related-products .product-rating {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
}

.related-products .rating-stars {
    display: flex;
    gap: 2px;
}

.related-products .star {
    color: #d1d5db;
    font-size: 12px;
}

.related-products .star.filled {
    color: #fbbf24;
}

.related-products .rating-count {
    font-size: 12px;
    color: #6b7280;
}

.related-products .product-price {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.related-products .price-current {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
}

.related-products .price-original {
    font-size: 14px;
    color: #9ca3af;
    text-decoration: line-through;
}

.related-products .price-discount {
    font-size: 11px;
    color: #10b981;
    font-weight: 600;
    background: #ecfdf5;
    padding: 2px 6px;
    border-radius: 4px;
}

.related-products .product-actions-bottom {
    display: flex;
    gap: 8px;
}

.related-products .add-to-cart {
    flex: 1;
    padding: 10px 16px;
    background: linear-gradient(135deg, var(--primary-color), #2563eb);
    border: none;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 40px;
}

.related-products .add-to-cart:hover {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.related-products .add-to-cart:active {
    transform: translateY(0);
}

/* Responsive Design */
@media (max-width: 1024px) {
    .product-layout {
        gap: var(--spacing-8);
    }
    
    .product-actions {
        flex-direction: column;
    }
}

@media (max-width: 768px) {
    .product-layout {
        grid-template-columns: 1fr;
        gap: var(--spacing-6);
    }
    
    .product-images {
        position: static;
    }
    
    .product-image-main {
        height: 400px;
    }
    
    .tab-buttons {
        flex-direction: column;
    }
    
    .quantity-group {
        flex-wrap: wrap;
    }
    
    .attribute-options {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .product-title {
        font-size: var(--font-size-2xl);
    }
    
    .product-price .price-current {
        font-size: var(--font-size-2xl);
    }
    
    .thumbnail-images {
        grid-template-columns: repeat(4, 1fr);
    }
}
</style>

<script>
$(document).ready(function() {
    // Tab functionality
    $('.tab-btn').on('click', function() {
        const tabId = $(this).data('tab');
        
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        
        $('.tab-pane').removeClass('active');
        $('#' + tabId).addClass('active');
    });
    
    // Add to cart form
    $('#addToCartForm').on('submit', function(e) {
        e.preventDefault();
        
        const productId = <?php echo $product['id']; ?>;
        const quantity = parseInt($('#quantity').val());
        const attributes = getProductAttributes();
        
        addToCart(productId, quantity, attributes);
    });
    
    // Wishlist button
    $('.wishlist-btn').on('click', function() {
        const productId = $(this).data('product-id');
        addToWishlist(productId);
    });
    
    // Buy now button
    $('.buy-now-btn').on('click', function() {
        // Add to cart and redirect to checkout
        const productId = <?php echo $product['id']; ?>;
        const quantity = parseInt($('#quantity').val());
        const attributes = getProductAttributes();
        
        $.ajax({
            url: window.siteUrl + '/api/cart.php',
            method: 'POST',
            data: {
                action: 'add',
                product_id: productId,
                quantity: quantity,
                attributes: JSON.stringify(attributes),
                csrf_token: window.csrfToken
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = window.siteUrl + '/checkout.php';
                } else {
                    showToast(response.message || 'Failed to add product to cart', 'error');
                }
            },
            error: function() {
                showToast('An error occurred. Please try again.', 'error');
            }
        });
    });
    
    // Review form
    $('#reviewForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: window.siteUrl + '/api/reviews.php',
            method: 'POST',
            data: formData + '&csrf_token=' + window.csrfToken,
            success: function(response) {
                if (response.success) {
                    showToast('Review submitted successfully!', 'success');
                    $('#reviewForm')[0].reset();
                    // Reload reviews section
                    location.reload();
                } else {
                    showToast(response.message || 'Failed to submit review', 'error');
                }
            },
            error: function() {
                showToast('An error occurred. Please try again.', 'error');
            }
        });
    });
});

// Change main image
function changeMainImage(src) {
    $('#mainImage').attr('src', src);
    $('.product-image-thumb').removeClass('active');
    $(`.product-image-thumb[src="${src}"]`).addClass('active');
}

// Update quantity
function updateQuantity(change) {
    const quantityInput = $('#quantity');
    const currentValue = parseInt(quantityInput.val());
    const newValue = currentValue + change;
    const maxValue = parseInt(quantityInput.attr('max'));
    
    if (newValue >= 1 && newValue <= maxValue) {
        quantityInput.val(newValue);
    }
}
</script>

<?php include 'includes/footer.php'; ?>
