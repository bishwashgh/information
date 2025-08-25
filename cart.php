<?php
require_once 'includes/config.php';

$pageTitle = 'Shopping Cart';
$pageDescription = 'Review your selected items before checkout';

$db = Database::getInstance()->getConnection();

// Get cart items
if (isLoggedIn()) {
    $stmt = $db->prepare("
        SELECT c.*, p.name, p.price, p.sale_price, p.slug, p.stock_quantity, p.manage_stock,
               (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ? AND p.status = 'active'
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([getCurrentUserId()]);
} else {
    $stmt = $db->prepare("
        SELECT c.*, p.name, p.price, p.sale_price, p.slug, p.stock_quantity, p.manage_stock,
               (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.session_id = ? AND p.status = 'active'
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([session_id()]);
}

$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = 0;
$totalItems = 0;
foreach ($cartItems as &$item) {
    $price = $item['sale_price'] ?: $item['price'];
    $item['unit_price'] = $price;
    $item['total_price'] = $price * $item['quantity'];
    $subtotal += $item['total_price'];
    $totalItems += $item['quantity'];
    
    // Parse attributes
    $item['parsed_attributes'] = json_decode($item['attributes'], true) ?: [];
}

// Calculate shipping
$shippingCost = 0;
if ($subtotal > 0 && $subtotal < 2000) {
    $shippingCost = 100; // Rs. 100 shipping for orders under Rs. 2000
}

$total = $subtotal + $shippingCost;

// Get available coupons for display
$stmt = $db->prepare("
    SELECT * FROM coupons 
    WHERE is_active = 1 
    AND (start_date IS NULL OR start_date <= CURDATE())
    AND (end_date IS NULL OR end_date >= CURDATE())
    AND (maximum_uses IS NULL OR used_count < maximum_uses)
    AND ? >= minimum_amount
    ORDER BY value DESC
    LIMIT 3
");
$stmt->execute([$subtotal]);
$availableCoupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<!-- Breadcrumb -->
<div class="breadcrumb-section">
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>/">Home</a>
            <span class="breadcrumb-separator">/</span>
            <span>Shopping Cart</span>
        </nav>
    </div>
</div>

<!-- Cart Section -->
<section class="cart-section">
    <div class="container">
        <?php if (empty($cartItems)): ?>
        <!-- Empty Cart -->
        <div class="empty-cart">
            <div class="empty-cart-content">
                <i class="fas fa-shopping-cart fa-4x"></i>
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added any items to your cart yet.</p>
                <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
            </div>
        </div>
        <?php else: ?>
        <!-- Cart Content -->
        <div class="cart-layout">
            <!-- Cart Items -->
            <div class="cart-items">
                <div class="cart-header">
                    <h1>Shopping Cart</h1>
                    <span class="cart-count"><?php echo $totalItems; ?> item<?php echo $totalItems !== 1 ? 's' : ''; ?></span>
                </div>
                
                <div class="cart-list">
                    <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item" data-cart-id="<?php echo $item['id']; ?>">
                        <div class="item-image">
                            <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $item['slug']; ?>">
                                <?php if ($item['image']): ?>
                                    <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                                <?php else: ?>
                                    <img src="https://images.unsplash.com/photo-1523381210834-895b31b4-3b0c?ixlib=rb-4.0.3&auto=format&fit=crop&w=150&h=150" 
                                         alt="<?php echo $item['name']; ?>">
                                <?php endif; ?>
                            </a>
                        </div>
                        
                        <div class="item-details">
                            <h3 class="item-name">
                                <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $item['slug']; ?>">
                                    <?php echo $item['name']; ?>
                                </a>
                            </h3>
                            
                            <?php if (!empty($item['parsed_attributes'])): ?>
                            <div class="item-attributes">
                                <?php foreach ($item['parsed_attributes'] as $attrName => $attrValue): ?>
                                <span class="attribute">
                                    <strong><?php echo ucfirst($attrName); ?>:</strong> <?php echo $attrValue; ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="item-price">
                                <span class="price"><?php echo formatPrice($item['unit_price']); ?></span>
                                <?php if ($item['sale_price'] && $item['sale_price'] < $item['price']): ?>
                                <span class="original-price"><?php echo formatPrice($item['price']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="item-quantity">
                            <div class="quantity-controls">
                                <button type="button" class="qty-btn minus" onclick="updateCartQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)">-</button>
                                <input type="number" class="cart-quantity" 
                                       value="<?php echo $item['quantity']; ?>" 
                                       min="1" 
                                       max="<?php echo $item['stock_quantity']; ?>"
                                       data-cart-id="<?php echo $item['id']; ?>">
                                <button type="button" class="qty-btn plus" onclick="updateCartQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)">+</button>
                            </div>
                            
                            <?php if ($item['manage_stock'] && $item['stock_quantity'] < 10): ?>
                            <div class="stock-warning">
                                Only <?php echo $item['stock_quantity']; ?> left in stock
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="item-total">
                            <span class="total-price"><?php echo formatPrice($item['total_price']); ?></span>
                        </div>
                        
                        <div class="item-actions">
                            <button class="btn-icon remove-item" 
                                    onclick="removeCartItem(<?php echo $item['id']; ?>)" 
                                    title="Remove item">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php if (isLoggedIn()): ?>
                            <button class="btn-icon move-to-wishlist" 
                                    onclick="moveToWishlist(<?php echo $item['product_id']; ?>, <?php echo $item['id']; ?>)" 
                                    title="Move to wishlist">
                                <i class="fas fa-heart"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-actions">
                    <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>
                    <button class="btn btn-secondary" onclick="clearCart()" id="clearCartBtn">
                        <i class="fas fa-trash"></i> Clear Cart
                    </button>
                </div>
            </div>
            
            <!-- Cart Summary -->
            <div class="cart-summary">
                <div class="summary-card">
                    <h3>Order Summary</h3>
                    
                    <div class="summary-line">
                        <span>Subtotal (<?php echo $totalItems; ?> items)</span>
                        <span><?php echo formatPrice($subtotal); ?></span>
                    </div>
                    
                    <div class="summary-line">
                        <span>Shipping</span>
                        <span>
                            <?php if ($shippingCost > 0): ?>
                                <?php echo formatPrice($shippingCost); ?>
                            <?php else: ?>
                                <span class="free-shipping">FREE</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <?php if ($subtotal < 2000 && $subtotal > 0): ?>
                    <div class="shipping-notice">
                        <i class="fas fa-truck"></i>
                        Add <?php echo formatPrice(2000 - $subtotal); ?> more for free shipping!
                    </div>
                    <?php endif; ?>
                    
                    <div class="coupon-section">
                        <form class="coupon-form" id="couponForm">
                            <input type="text" name="coupon_code" placeholder="Enter coupon code" id="couponInput">
                            <button type="submit" class="btn btn-outline btn-sm">Apply</button>
                        </form>
                        
                        <?php if (!empty($availableCoupons)): ?>
                        <div class="available-coupons">
                            <p><small>Available offers:</small></p>
                            <?php foreach ($availableCoupons as $coupon): ?>
                            <div class="coupon-offer" onclick="applyCoupon('<?php echo $coupon['code']; ?>')">
                                <strong><?php echo $coupon['code']; ?></strong>
                                <span>
                                    <?php if ($coupon['type'] === 'percentage'): ?>
                                        <?php echo $coupon['value']; ?>% OFF
                                    <?php else: ?>
                                        <?php echo formatPrice($coupon['value']); ?> OFF
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="summary-total">
                        <span>Total</span>
                        <span class="total-amount"><?php echo formatPrice($total); ?></span>
                    </div>
                    
                    <div class="checkout-actions">
                        <?php if (isLoggedIn()): ?>
                        <a href="<?php echo SITE_URL; ?>/checkout.php" class="btn btn-primary btn-lg checkout-btn">
                            <i class="fas fa-lock"></i> Proceed to Checkout
                        </a>
                        <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/user/login.php?redirect=checkout" class="btn btn-primary btn-lg checkout-btn">
                            <i class="fas fa-user"></i> Login to Checkout
                        </a>
                        <div class="guest-checkout">
                            <div class="divider">
                                <span>or</span>
                            </div>
                            <a href="<?php echo SITE_URL; ?>/checkout.php?guest=1" class="btn btn-outline btn-lg guest-btn">
                                <i class="fas fa-user-plus"></i> Continue as Guest
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="security-badges">
                        <div class="security-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>Secure Payment</span>
                        </div>
                        <div class="security-item">
                            <i class="fas fa-undo"></i>
                            <span>30-Day Returns</span>
                        </div>
                        <div class="security-item">
                            <i class="fas fa-truck"></i>
                            <span>Fast Delivery</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
/* Enhanced Premium Cart Page Styles */
.cart-section {
    padding: var(--spacing-12) 0;
    min-height: 70vh;
    background: linear-gradient(180deg, var(--gray-50) 0%, var(--white) 50%, var(--gray-50) 100%);
}

.page-header {
    text-align: center;
    margin-bottom: var(--spacing-12);
    padding: var(--spacing-8);
    background: linear-gradient(135deg, var(--white) 0%, #fafbfc 100%);
    border-radius: var(--border-radius-xl);
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
}

.page-header h1 {
    font-size: var(--font-size-3xl);
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: var(--spacing-2);
    letter-spacing: -0.02em;
}

/* Enhanced Empty Cart */
.empty-cart {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 500px;
    background: linear-gradient(135deg, var(--white) 0%, #fafbfc 100%);
    border-radius: var(--border-radius-xl);
    box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    margin: var(--spacing-8) 0;
}

.empty-cart-content {
    text-align: center;
    max-width: 500px;
    padding: var(--spacing-10);
}

.empty-cart-content i {
    color: var(--gray-400);
    margin-bottom: var(--spacing-8);
    background: linear-gradient(135deg, var(--gray-300), var(--gray-400));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
}

.empty-cart-content h2 {
    color: var(--gray-800);
    font-weight: 700;
    font-size: var(--font-size-2xl);
    margin-bottom: var(--spacing-4);
    letter-spacing: -0.01em;
}

.empty-cart-content p {
    color: var(--gray-600);
    font-size: var(--font-size-lg);
    line-height: 1.6;
    margin-bottom: var(--spacing-10);
}

/* Enhanced Cart Layout */
.cart-layout {
    display: grid;
    grid-template-columns: 1fr 420px;
    gap: var(--spacing-10);
    align-items: start;
}

.cart-summary {
    position: sticky;
    top: 120px;
    background: linear-gradient(135deg, var(--white) 0%, #fafbfc 100%);
    border-radius: var(--border-radius-xl);
    padding: var(--spacing-8);
    box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    border: 1px solid rgba(255,255,255,0.8);
}

.cart-summary h3 {
    font-size: var(--font-size-xl);
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: var(--spacing-6);
    padding-bottom: var(--spacing-4);
    border-bottom: 2px solid var(--gray-100);
    position: relative;
}

.cart-summary h3::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 60px;
    height: 2px;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
}

/* Enhanced Cart Table */
.cart-table-container {
    background: linear-gradient(135deg, var(--white) 0%, #fafbfc 100%);
    border-radius: var(--border-radius-xl);
    overflow: hidden;
    box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    border: 1px solid rgba(255,255,255,0.8);
}

.cart-table {
    width: 100%;
    border-collapse: collapse;
    background: transparent;
}

.cart-table th {
    background: linear-gradient(135deg, var(--gray-50) 0%, #f8fafc 100%);
    padding: var(--spacing-6);
    text-align: left;
    font-weight: 700;
    color: var(--gray-800);
    font-size: var(--font-size-sm);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 2px solid var(--gray-200);
}

.cart-table td {
    padding: var(--spacing-6);
    border-bottom: 1px solid var(--gray-100);
    vertical-align: middle;
}

.cart-table tr:hover {
    background: rgba(37, 99, 235, 0.02);
}

.cart-table tr:last-child td {
    border-bottom: none;
}

.product-info {
    display: flex;
    align-items: center;
    gap: var(--spacing-4);
}

.product-image {
    width: 80px;
    height: 80px;
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.product-info:hover .product-image img {
    transform: scale(1.05);
}

.product-details h4 {
    font-size: var(--font-size-base);
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: var(--spacing-1);
    letter-spacing: -0.01em;
}

.product-details p {
    font-size: var(--font-size-sm);
    color: var(--gray-600);
    margin: 0;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    background: var(--gray-50);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-1);
}

.quantity-controls button {
    width: 35px;
    height: 35px;
    border: none;
    background: var(--white);
    color: var(--gray-600);
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.quantity-controls button:hover {
    background: var(--primary-color);
    color: var(--white);
    transform: scale(1.1);
}

.quantity-controls input {
    width: 50px;
    text-align: center;
    border: none;
    background: transparent;
    font-weight: 600;
    color: var(--gray-800);
}

.price-cell {
    font-size: var(--font-size-lg);
    font-weight: 700;
    color: var(--primary-color);
}

.remove-btn {
    background: linear-gradient(135deg, var(--danger-color), #dc2626);
    color: var(--white);
    border: none;
    padding: var(--spacing-2) var(--spacing-3);
    border-radius: var(--border-radius-lg);
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
    font-size: var(--font-size-sm);
}

.remove-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
}

/* Cart Items */
.cart-items {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-6);
    box-shadow: var(--shadow);
}

.cart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-6);
    padding-bottom: var(--spacing-4);
    border-bottom: 1px solid var(--gray-200);
}

.cart-header h1 {
    margin: 0;
    color: var(--gray-900);
}

.cart-count {
    color: var(--gray-600);
    font-size: var(--font-size-sm);
}

.cart-list {
    margin-bottom: var(--spacing-6);
}

.cart-item {
    display: grid;
    grid-template-columns: 100px 1fr auto auto auto;
    gap: var(--spacing-4);
    align-items: center;
    padding: var(--spacing-4);
    border-bottom: 1px solid var(--gray-200);
    transition: var(--transition-fast);
}

.cart-item:last-child {
    border-bottom: none;
}

.cart-item:hover {
    background: var(--gray-50);
}

.item-image {
    width: 80px;
    height: 80px;
    border-radius: var(--border-radius);
    overflow: hidden;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-details {
    flex: 1;
}

.item-name {
    margin-bottom: var(--spacing-2);
    font-size: var(--font-size-lg);
}

.item-name a {
    color: var(--gray-900);
    text-decoration: none;
}

.item-name a:hover {
    color: var(--primary-color);
}

.item-attributes {
    margin-bottom: var(--spacing-2);
}

.attribute {
    display: inline-block;
    margin-right: var(--spacing-3);
    font-size: var(--font-size-sm);
    color: var(--gray-600);
}

.item-price .price {
    font-weight: 600;
    color: var(--gray-900);
}

.item-price .original-price {
    color: var(--gray-500);
    text-decoration: line-through;
    margin-left: var(--spacing-2);
    font-size: var(--font-size-sm);
}

.item-quantity {
    text-align: center;
}

.quantity-controls {
    display: flex;
    align-items: center;
    border: 1px solid var(--gray-300);
    border-radius: var(--border-radius);
    overflow: hidden;
    margin-bottom: var(--spacing-2);
}

.qty-btn {
    background: var(--gray-100);
    border: none;
    width: 32px;
    height: 32px;
    cursor: pointer;
    transition: var(--transition-fast);
}

.qty-btn:hover {
    background: var(--gray-200);
}

.cart-quantity {
    width: 50px;
    height: 32px;
    text-align: center;
    border: none;
    background: var(--white);
}

.stock-warning {
    font-size: var(--font-size-xs);
    color: var(--warning-color);
    font-weight: 500;
}

.item-total {
    text-align: right;
    font-weight: 600;
    color: var(--gray-900);
    font-size: var(--font-size-lg);
}

.item-actions {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}

.btn-icon {
    background: none;
    border: none;
    color: var(--gray-500);
    cursor: pointer;
    padding: var(--spacing-1);
    border-radius: var(--border-radius);
    transition: var(--transition-fast);
}

.btn-icon:hover {
    background: var(--gray-100);
    color: var(--gray-700);
}

.btn-icon.remove-item:hover {
    color: var(--danger-color);
}

.cart-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: var(--spacing-6);
    border-top: 1px solid var(--gray-200);
}

/* Cart Summary */
.cart-summary {
    position: sticky;
    top: var(--spacing-8);
}

.summary-card {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-6);
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--gray-200);
}

.summary-card h3 {
    margin-bottom: var(--spacing-5);
    color: var(--gray-900);
    font-size: var(--font-size-lg);
    border-bottom: 2px solid var(--primary-color);
    padding-bottom: var(--spacing-3);
}

.summary-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-4);
    color: var(--gray-700);
    padding: var(--spacing-2) 0;
}

.summary-line span:first-child {
    font-weight: 500;
}

.summary-line span:last-child {
    font-weight: 600;
    color: var(--gray-900);
}

.free-shipping {
    color: var(--success-color);
    font-weight: 600;
}

.shipping-notice {
    background: var(--info-color);
    color: var(--white);
    padding: var(--spacing-3);
    border-radius: var(--border-radius);
    font-size: var(--font-size-sm);
    margin: var(--spacing-4) 0;
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
}

.coupon-section {
    margin: var(--spacing-5) 0;
    padding: var(--spacing-4) 0;
    border-top: 1px solid var(--gray-200);
    border-bottom: 1px solid var(--gray-200);
}

.coupon-form {
    display: flex;
    gap: var(--spacing-2);
    margin-bottom: var(--spacing-3);
}

.coupon-form input {
    flex: 1;
    padding: var(--spacing-2);
    border: 1px solid var(--gray-300);
    border-radius: var(--border-radius);
    font-size: var(--font-size-sm);
}

.available-coupons {
    margin-top: var(--spacing-3);
}

.coupon-offer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-2);
    background: var(--gray-50);
    border-radius: var(--border-radius);
    cursor: pointer;
    margin-bottom: var(--spacing-2);
    transition: var(--transition-fast);
}

.coupon-offer:hover {
    background: var(--gray-100);
}

.coupon-offer strong {
    color: var(--primary-color);
}

.coupon-offer span {
    font-size: var(--font-size-sm);
    color: var(--success-color);
    font-weight: 600;
}

.summary-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: var(--font-size-lg);
    font-weight: 700;
    color: var(--gray-900);
    margin: var(--spacing-5) 0;
    padding: var(--spacing-4);
    border-top: 2px solid var(--gray-200);
    border-bottom: 2px solid var(--gray-200);
    background: var(--gray-50);
    border-radius: var(--border-radius);
}

.total-amount {
    font-size: var(--font-size-xl);
    color: var(--primary-color);
    font-weight: 800;
}

.checkout-actions {
    margin-bottom: var(--spacing-5);
}

.checkout-btn {
    width: 100%;
    margin-bottom: var(--spacing-4);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-2);
}

.guest-checkout {
    text-align: center;
    margin-top: var(--spacing-4);
}

.guest-checkout .divider {
    position: relative;
    margin: var(--spacing-4) 0;
}

.guest-checkout .divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: var(--gray-200);
}

.guest-checkout .divider span {
    background: var(--white);
    padding: 0 var(--spacing-3);
    color: var(--gray-500);
    font-size: var(--font-size-sm);
    font-weight: 500;
}

.guest-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-2);
    margin-top: var(--spacing-3);
}

.security-badges {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--spacing-2);
    padding-top: var(--spacing-4);
    border-top: 1px solid var(--gray-200);
}

.security-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    color: var(--gray-600);
    font-size: var(--font-size-sm);
}

.security-item i {
    color: var(--success-color);
    width: 16px;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .cart-layout {
        grid-template-columns: 1fr;
        gap: var(--spacing-6);
    }
    
    .cart-summary {
        position: static;
        order: -1;
        top: auto;
    }
    
    .summary-card {
        margin-bottom: var(--spacing-6);
    }
}

@media (max-width: 768px) {
    .cart-item {
        grid-template-columns: 80px 1fr;
        gap: var(--spacing-3);
    }
    
    .item-quantity,
    .item-total,
    .item-actions {
        grid-column: 1 / -1;
        justify-self: start;
        margin-top: var(--spacing-3);
    }
    
    .item-quantity {
        display: flex;
        align-items: center;
        gap: var(--spacing-3);
    }
    
    .cart-actions {
        flex-direction: column;
        gap: var(--spacing-3);
    }
}

@media (max-width: 480px) {
    .cart-items,
    .summary-card {
        padding: var(--spacing-4);
    }
    
    .coupon-form {
        flex-direction: column;
    }
    
    .security-badges {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Update cart quantity
function updateCartQuantity(cartId, newQuantity) {
    if (newQuantity < 1) {
        removeCartItem(cartId);
        return;
    }
    
    $.ajax({
        url: window.siteUrl + '/api/cart.php',
        method: 'POST',
        data: {
            action: 'update',
            cart_id: cartId,
            quantity: newQuantity,
            csrf_token: window.csrfToken
        },
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                showToast(response.message || 'Failed to update cart', 'error');
            }
        },
        error: function() {
            showToast('An error occurred. Please try again.', 'error');
        }
    });
}

// Remove cart item
function removeCartItem(cartId) {
    if (!confirm('Are you sure you want to remove this item?')) {
        return;
    }
    
    $.ajax({
        url: window.siteUrl + '/api/cart.php',
        method: 'POST',
        data: {
            action: 'remove',
            cart_id: cartId,
            csrf_token: window.csrfToken
        },
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                showToast(response.message || 'Failed to remove item', 'error');
            }
        },
        error: function() {
            showToast('An error occurred. Please try again.', 'error');
        }
    });
}

// Clear cart
function clearCart() {
    if (!confirm('Are you sure you want to clear your cart?')) {
        return;
    }
    
    $.ajax({
        url: window.siteUrl + '/api/cart.php',
        method: 'POST',
        data: {
            action: 'clear',
            csrf_token: window.csrfToken
        },
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                showToast(response.message || 'Failed to clear cart', 'error');
            }
        },
        error: function() {
            showToast('An error occurred. Please try again.', 'error');
        }
    });
}

// Apply coupon
function applyCoupon(code) {
    $('#couponInput').val(code);
    $('#couponForm').submit();
}

// Move to wishlist
function moveToWishlist(productId, cartId) {
    $.ajax({
        url: window.siteUrl + '/api/wishlist.php',
        method: 'POST',
        data: {
            action: 'add',
            product_id: productId,
            csrf_token: window.csrfToken
        },
        success: function(response) {
            if (response.success) {
                removeCartItem(cartId);
                showToast('Item moved to wishlist', 'success');
            } else {
                showToast(response.message || 'Failed to move to wishlist', 'error');
            }
        },
        error: function() {
            showToast('An error occurred. Please try again.', 'error');
        }
    });
}

$(document).ready(function() {
    // Coupon form
    $('#couponForm').on('submit', function(e) {
        e.preventDefault();
        
        const couponCode = $('#couponInput').val().trim();
        if (!couponCode) {
            showToast('Please enter a coupon code', 'warning');
            return;
        }
        
        // Apply coupon logic here
        showToast('Coupon functionality will be implemented in checkout', 'info');
    });
    
    // Quantity input change
    $('.cart-quantity').on('change', function() {
        const cartId = $(this).data('cart-id');
        const quantity = parseInt($(this).val());
        updateCartQuantity(cartId, quantity);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
