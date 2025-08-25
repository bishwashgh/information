<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$pageTitle = 'My Wishlist';
$db = Database::getInstance()->getConnection();
$userId = getCurrentUserId();

// Get user's wishlist items
try {
    $stmt = $db->prepare("
        SELECT w.*, p.name, p.slug, p.price, p.sale_price, p.stock_quantity, p.status,
               (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image,
               c.name as category_name,
               (SELECT AVG(rating) FROM product_reviews WHERE product_id = p.id AND is_approved = 1) as avg_rating,
               (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id AND is_approved = 1) as review_count
        FROM wishlist w 
        JOIN products p ON w.product_id = p.id 
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE w.user_id = ? AND p.status = 'active'
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$userId]);
    $wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Wishlist error: " . $e->getMessage());
    $wishlistItems = [];
}
?>

<?php include '../includes/header.php'; ?>

<div class="wishlist-page">
    <div class="container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>/">Home</a>
            <span class="breadcrumb-separator">/</span>
            <a href="<?php echo SITE_URL; ?>/user/dashboard.php">My Account</a>
            <span class="breadcrumb-separator">/</span>
            <span>Wishlist</span>
        </nav>
        
        <div class="wishlist-header">
            <h1><i class="fas fa-heart"></i> My Wishlist</h1>
            <p>Save your favorite items for later</p>
        </div>
        
        <?php if (empty($wishlistItems)): ?>
        <!-- Empty Wishlist -->
        <div class="empty-wishlist">
            <div class="empty-icon">
                <i class="far fa-heart"></i>
            </div>
            <h2>Your wishlist is empty</h2>
            <p>Save items you love so you don't lose sight of them.</p>
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">
                <i class="fas fa-shopping-bag"></i>
                Start Shopping
            </a>
        </div>
        
        <?php else: ?>
        <!-- Wishlist Items -->
        <div class="wishlist-content">
            <div class="wishlist-header-actions">
                <div class="wishlist-count">
                    <span><?php echo count($wishlistItems); ?> item<?php echo count($wishlistItems) > 1 ? 's' : ''; ?> in your wishlist</span>
                </div>
                <div class="wishlist-actions">
                    <button class="btn btn-outline" onclick="addAllToCart()">
                        <i class="fas fa-shopping-cart"></i>
                        Add All to Cart
                    </button>
                    <button class="btn btn-outline btn-danger" onclick="clearWishlist()">
                        <i class="fas fa-trash"></i>
                        Clear Wishlist
                    </button>
                </div>
            </div>
            
            <div class="product-grid">
                <?php foreach ($wishlistItems as $item): ?>
                <div class="product-card" data-product-id="<?php echo $item['product_id']; ?>">
                    <div class="product-image">
                        <img src="<?php echo $item['image'] ?: SITE_URL . '/assets/images/placeholder.jpg'; ?>" 
                             alt="<?php echo $item['name']; ?>" loading="lazy">
                        
                        <?php if ($item['sale_price']): ?>
                        <div class="product-badge">Sale</div>
                        <?php endif; ?>
                        
                        <!-- Product Actions -->
                        <div class="product-actions">
                            <button class="product-action" onclick="quickViewProduct(<?php echo $item['product_id']; ?>)" title="Quick View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="product-action remove-wishlist-btn" onclick="removeFromWishlist(<?php echo $item['product_id']; ?>)" title="Remove from Wishlist">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="product-info">
                        <div class="product-category">
                            <?php echo $item['category_name']; ?>
                        </div>
                        
                        <h3 class="product-title">
                            <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $item['slug']; ?>">
                                <?php echo $item['name']; ?>
                            </a>
                        </h3>
                        
                        <div class="product-rating">
                            <?php if ($item['avg_rating']): ?>
                            <div class="rating-stars">
                                <?php
                                $rating = round($item['avg_rating']);
                                for ($i = 1; $i <= 5; $i++):
                                ?>
                                <i class="star <?php echo $i <= $rating ? 'fas filled' : 'far'; ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-count">(<?php echo $item['review_count']; ?> reviews)</span>
                            <?php else: ?>
                            <span class="rating-count">No reviews yet</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-price">
                            <?php if ($item['sale_price']): ?>
                            <span class="price-current"><?php echo formatPrice($item['sale_price']); ?></span>
                            <span class="price-original"><?php echo formatPrice($item['price']); ?></span>
                            <span class="price-discount">
                                <?php echo round((($item['price'] - $item['sale_price']) / $item['price']) * 100); ?>% OFF
                            </span>
                            <?php else: ?>
                            <span class="price-current"><?php echo formatPrice($item['price']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-stock">
                            <?php if ($item['stock_quantity'] > 0): ?>
                            <span class="in-stock">
                                <i class="fas fa-check-circle"></i>
                                In Stock (<?php echo $item['stock_quantity']; ?> available)
                            </span>
                            <?php else: ?>
                            <span class="out-of-stock">
                                <i class="fas fa-times-circle"></i>
                                Out of Stock
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-actions-bottom">
                            <?php if ($item['stock_quantity'] > 0): ?>
                            <button class="btn btn-primary add-to-cart" 
                                    data-product-id="<?php echo $item['product_id']; ?>">
                                <i class="fas fa-shopping-cart"></i>
                                Add to Cart
                            </button>
                            <?php else: ?>
                            <button class="btn btn-secondary" disabled>
                                Out of Stock
                            </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="wishlist-date">
                            Added on <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Wishlist Page Styles */
.wishlist-page {
    padding: 2rem 0;
    min-height: 70vh;
}

.wishlist-header {
    text-align: center;
    margin-bottom: 3rem;
}

.wishlist-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.wishlist-header h1 i {
    color: #e74c3c;
    margin-right: 0.5rem;
}

.wishlist-header p {
    font-size: 1.125rem;
    color: #6c757d;
    margin: 0;
}

/* Empty Wishlist */
.empty-wishlist {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 1rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.empty-icon {
    font-size: 4rem;
    color: #dee2e6;
    margin-bottom: 1.5rem;
}

.empty-wishlist h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.empty-wishlist p {
    color: #6c757d;
    margin-bottom: 2rem;
}

/* Wishlist Content */
.wishlist-content {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    overflow: hidden;
}

.wishlist-header-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
    flex-wrap: wrap;
    gap: 1rem;
}

.wishlist-count span {
    font-weight: 600;
    color: #2c3e50;
}

.wishlist-actions {
    display: flex;
    gap: 0.75rem;
}

/* Wishlist Specific Styles */
.wishlist-date {
    font-size: 12px;
    color: #6b7280;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #f3f4f6;
}

.product-stock {
    margin-bottom: 12px;
    font-size: 14px;
}

.in-stock {
    color: #10b981;
    font-weight: 600;
}

.in-stock i {
    margin-right: 4px;
}

.out-of-stock {
    color: #ef4444;
    font-weight: 600;
}

.out-of-stock i {
    margin-right: 4px;
}
}

/* Product Info */
.product-info {
    padding: 1.25rem;
}

.product-category {
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.product-name {
    margin: 0 0 0.75rem;
    font-size: 1rem;
    font-weight: 600;
    line-height: 1.4;
}

.product-name a {
    color: #2c3e50;
    text-decoration: none;
    transition: color 0.3s ease;
}

.product-name a:hover {
    color: #007bff;
}

.product-rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.stars {
    display: flex;
    gap: 0.125rem;
}

.star {
    color: #ddd;
    font-size: 0.875rem;
/* Responsive Design for Wishlist */
@media (max-width: 768px) {
    .wishlist-header-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .wishlist-actions {
        justify-content: center;
    }
}
}

@media (max-width: 480px) {
    .wishlist-header h1 {
        font-size: 2rem;
    }
    
    .wishlist-actions {
        flex-direction: column;
    }
}
</style>

<script>
function removeFromWishlist(productId) {
    // Show loading state on the remove button
    const removeButton = $(`.product-action[onclick="removeFromWishlist(${productId})"]`);
    const originalIcon = removeButton.html();
    removeButton.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
    
    $.ajax({
        url: window.siteUrl + '/api/wishlist.php',
        method: 'POST',
        data: {
            action: 'remove',
            product_id: productId,
            csrf_token: window.csrfToken
        },
        success: function(response) {
            if (response.success) {
                showToast('Item removed from wishlist', 'success');
                $(`.product-card[data-product-id="${productId}"]`).fadeOut(300, function() {
                    $(this).remove();
                    updateWishlistCount();
                });
            } else {
                showToast(response.message || 'Failed to remove item', 'error');
                removeButton.html(originalIcon).prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            console.error('Remove from wishlist error:', error);
            showToast('An error occurred. Please try again.', 'error');
            removeButton.html(originalIcon).prop('disabled', false);
        }
    });
}

function addToCartFromWishlist(productId) {
    $.ajax({
        url: window.siteUrl + '/api/cart.php',
        method: 'POST',
        data: {
            action: 'add',
            product_id: productId,
            quantity: 1,
            csrf_token: window.csrfToken
        },
        success: function(response) {
            if (response.success) {
                showToast('Item added to cart!', 'success');
                // Update cart count if function exists
                if (typeof updateCartCount === 'function') {
                    updateCartCount();
                }
            } else {
                showToast(response.message || 'Failed to add to cart', 'error');
            }
        },
        error: function() {
            showToast('An error occurred. Please try again.', 'error');
        }
    });
}

function addAllToCart() {
    const addToCartButtons = $('.add-to-cart:not(:disabled)');
    
    if (addToCartButtons.length === 0) {
        showToast('No items available to add to cart', 'warning');
        return;
    }
    
    let addedCount = 0;
    let totalItems = addToCartButtons.length;
    let hasErrors = false;
    
    // Disable all buttons
    addToCartButtons.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Adding...');
    
    addToCartButtons.each(function() {
        const button = $(this);
        const productId = button.data('product-id');
        
        $.ajax({
            url: window.siteUrl + '/api/cart.php',
            method: 'POST',
            data: {
                action: 'add',
                product_id: productId,
                quantity: 1,
                csrf_token: window.csrfToken
            },
            success: function(response) {
                addedCount++;
                if (response.success) {
                    button.removeClass('btn-primary').addClass('btn-success')
                          .html('<i class="fas fa-check"></i> Added!');
                } else {
                    hasErrors = true;
                    button.removeClass('btn-primary').addClass('btn-danger')
                          .html('<i class="fas fa-times"></i> Failed');
                }
                
                // Check if all requests completed
                if (addedCount === totalItems) {
                    if (hasErrors) {
                        showToast(`Some items could not be added to cart`, 'warning');
                    } else {
                        showToast(`All ${totalItems} items added to cart!`, 'success');
                    }
                    
                    // Update cart count
                    if (typeof updateCartCount === 'function') {
                        updateCartCount();
                    }
                    
                    // Reset buttons after delay
                    setTimeout(function() {
                        addToCartButtons.each(function() {
                            const btn = $(this);
                            btn.removeClass('btn-success btn-danger').addClass('btn-primary')
                               .html('<i class="fas fa-shopping-cart"></i> Add to Cart')
                               .prop('disabled', false);
                        });
                    }, 3000);
                }
            },
            error: function() {
                addedCount++;
                hasErrors = true;
                button.removeClass('btn-primary').addClass('btn-danger')
                      .html('<i class="fas fa-times"></i> Error');
                
                if (addedCount === totalItems) {
                    showToast('Some items could not be added to cart', 'error');
                    setTimeout(function() {
                        addToCartButtons.prop('disabled', false)
                                      .removeClass('btn-danger').addClass('btn-primary')
                                      .html('<i class="fas fa-shopping-cart"></i> Add to Cart');
                    }, 3000);
                }
            }
        });
    });
}

function clearWishlist() {
    if (!confirm('Are you sure you want to clear your entire wishlist? This action cannot be undone.')) {
        return;
    }
    
    const wishlistItems = $('.product-card[data-product-id]');
    
    if (wishlistItems.length === 0) {
        showToast('Your wishlist is already empty', 'info');
        return;
    }
    
    // Show loading state
    const clearButton = $('.btn-danger:contains("Clear Wishlist")');
    const originalText = clearButton.html();
    clearButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Clearing...');
    
    // Use the new clear API endpoint for efficiency
    $.ajax({
        url: window.siteUrl + '/api/wishlist.php',
        method: 'POST',
        data: {
            action: 'clear',
            csrf_token: window.csrfToken
        },
        success: function(response) {
            if (response.success) {
                showToast(`Wishlist cleared successfully! ${response.deleted_count || ''} items removed.`, 'success');
                
                // Fade out all product cards
                wishlistItems.fadeOut(500, function() {
                    // Reload page after animation completes
                    setTimeout(function() {
                        location.reload();
                    }, 300);
                });
            } else {
                showToast(response.message || 'Failed to clear wishlist', 'error');
                clearButton.prop('disabled', false).html(originalText);
            }
        },
        error: function(xhr, status, error) {
            console.error('Clear wishlist error:', error);
            showToast('An error occurred while clearing wishlist. Please try again.', 'error');
            clearButton.prop('disabled', false).html(originalText);
        }
    });
}

function quickViewProduct(productId) {
    // Implementation for quick view modal
    window.open(`${window.siteUrl}/product.php?id=${productId}`, '_blank');
}

function shareProduct(productId) {
    // Simple share functionality
    const url = `${window.siteUrl}/product.php?id=${productId}`;
    if (navigator.share) {
        navigator.share({
            title: 'Check out this product',
            url: url
        });
    } else {
        // Fallback - copy to clipboard
        navigator.clipboard.writeText(url).then(function() {
            showToast('Product link copied to clipboard!', 'success');
        });
    }
}

function updateWishlistCount() {
    const remainingItems = $('.product-card[data-product-id]').length;
    if (remainingItems === 0) {
        // Show empty wishlist message
        setTimeout(function() {
            location.reload();
        }, 500);
    } else {
        $('.wishlist-count span').text(`${remainingItems} item${remainingItems > 1 ? 's' : ''} in your wishlist`);
    }
}

// Global add-to-cart functionality
$(document).on('click', '.add-to-cart', function(e) {
    e.preventDefault();
    
    const button = $(this);
    const productId = button.data('product-id');
    const quantity = button.data('quantity') || 1;
    
    if (!productId) {
        showToast('Product ID not found', 'error');
        return;
    }
    
    // Disable button and show loading
    const originalText = button.html();
    button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Adding...');
    
    $.ajax({
        url: window.siteUrl + '/api/cart.php',
        method: 'POST',
        data: {
            action: 'add',
            product_id: productId,
            quantity: quantity,
            csrf_token: window.csrfToken
        },
        success: function(response) {
            if (response.success) {
                showToast('Item added to cart!', 'success');
                
                // Update cart count if function exists
                if (typeof updateCartCount === 'function') {
                    updateCartCount();
                }
                
                // Update button to show success state temporarily
                button.removeClass('btn-primary').addClass('btn-success')
                      .html('<i class="fas fa-check"></i> Added!');
                
                setTimeout(function() {
                    button.removeClass('btn-success').addClass('btn-primary')
                          .html(originalText);
                }, 2000);
                
            } else {
                showToast(response.message || 'Failed to add to cart', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Add to cart error:', error);
            showToast('An error occurred. Please try again.', 'error');
        },
        complete: function() {
            button.prop('disabled', false);
            if (!button.hasClass('btn-success')) {
                button.html(originalText);
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
