<?php
require_once 'includes/config.php';

$pageTitle = 'Home';
$pageDescription = 'Welcome to our premium e-commerce store featuring quality clothing and delicious cafe items';
$pageKeywords = 'ecommerce, clothing, jersey, cap, hoodie, cafe, pizza, coffee, online shopping';

// Get featured products
$featuredProducts = getFeaturedProducts(8);

// Get categories for hero section
$categories = getCategories();
?>

<?php include 'includes/header.php'; ?>

<!-- Simple Auto-Sliding Carousel -->
<section class="hero">
    <div class="simple-carousel" id="autoCarousel">
        <div class="carousel-slides">
            <!-- Slide 1 - Cafe -->
            <div class="slide active">
                <img src="https://fruitbasket.limepack.com/blog/wp-content/uploads/2024/03/modern-cafe-house.jpg" alt="Modern Cafe Experience">
                <div class="slide-content">
                    <h2>Premium Cafe Experience</h2>
                    <p>Enjoy fresh coffee and delicious treats in our modern cafe</p>
                    <a href="products.php?category=cafe" class="btn btn-primary cafe-btn" data-url="products.php?category=cafe">Explore Cafe</a>
                </div>
            </div>
            
            <!-- Slide 2 - Clothing -->
            <div class="slide">
                <img src="https://sp-ao.shortpixel.ai/client/to_webp,q_glossy,ret_img,w_800,h_533/https://huntsvillemagazine.com/wp-content/uploads/2024/01/hans-isaacson-_a_FlMKo4Lk-unsplash-800x533.jpg" alt="Fashion Collection">
                <div class="slide-content">
                    <h2>Fashion Collection</h2>
                    <p>Discover our premium clothing and accessories</p>
                    <a href="products.php?category=clothing" class="btn btn-primary clothing-btn" data-url="products.php?category=clothing">Shop Now</a>
                </div>
            </div>
            
            <!-- Slide 3 - Support -->
            <div class="slide">
                <img src="https://trainingmag.com/wp/wp-content/uploads/2023/01/shutterstock_1983847265-696x392.jpg" alt="Customer Support">
                <div class="slide-content">
                    <h2>24/7 Customer Support</h2>
                    <p>We're here to help with privacy protection and excellent service</p>
                    <a href="about.php" class="btn btn-primary support-btn" data-url="about.php">Contact Us</a>
                </div>
            </div>
        </div>
        
        <!-- Simple Navigation Dots -->
        <div class="slide-dots">
            <span class="dot active" onclick="currentSlide(1)"></span>
            <span class="dot" onclick="currentSlide(2)"></span>
            <span class="dot" onclick="currentSlide(3)"></span>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="categories-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Shop by Category</h2>
            <p class="section-subtitle">Explore our carefully curated collections</p>
        </div>
        
        <div class="category-grid">
            <?php foreach ($categories as $category): ?>
            <div class="category-card">
                <div class="category-image">
                    <img src="<?php echo $category['slug'] === 'clothing' ? 'https://www.visithalfmoonbay.org/wp-content/uploads/AdobeStock_331002038-1600x900.jpeg' : 'https://images.unsplash.com/photo-1554118811-1e0d58224f24?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&h=300'; ?>" 
                         alt="<?php echo $category['name']; ?>" 
                         loading="lazy">
                </div>
                <div class="category-info">
                    <h3><?php echo $category['name']; ?></h3>
                    <p><?php echo $category['description']; ?></p>
                    <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $category['slug']; ?>" class="btn btn-outline">
                        Shop <?php echo $category['name']; ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="products-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Featured Products</h2>
            <p class="section-subtitle">Our handpicked selection of premium products</p>
        </div>
        
        <div class="product-grid">
            <?php foreach ($featuredProducts as $product): ?>
            <div class="product-card">
                <div class="product-image">
                    <?php if ($product['image']): ?>
                        <img src="<?php echo $product['image']; ?>" 
                             alt="<?php echo $product['name']; ?>" 
                             loading="lazy">
                    <?php else: ?>
                        <img src="https://images.unsplash.com/photo-1523381210834-895b31b4-3b0c?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&h=250" 
                             alt="<?php echo $product['name']; ?>" 
                             loading="lazy">
                    <?php endif; ?>
                    
                    <?php if ($product['sale_price']): ?>
                    <div class="product-badge">Sale</div>
                    <?php endif; ?>
                    
                    <div class="product-actions">
                        <?php if (isLoggedIn()): ?>
                        <button class="product-action add-to-wishlist" 
                                data-product-id="<?php echo $product['id']; ?>" 
                                title="Add to Wishlist">
                            <i class="far fa-heart"></i>
                        </button>
                        <?php endif; ?>
                        <button class="product-action" title="Quick View">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="product-info">
                    <div class="product-category"><?php echo $product['category_name']; ?></div>
                    <h3 class="product-title">
                        <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $product['slug']; ?>">
                            <?php echo $product['name']; ?>
                        </a>
                    </h3>
                    
                    <div class="product-rating">
                        <div class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="star <?php echo $i <= 4 ? 'fas filled' : 'far'; ?> fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-count">(24 reviews)</span>
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
                    
                    <div class="product-actions-bottom">
                        <button class="btn btn-primary add-to-cart" 
                                data-product-id="<?php echo $product['id']; ?>">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-6">
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-outline btn-lg">
                View All Products
            </a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section bg-light">
    <div class="container">
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <h3>Fast Delivery</h3>
                <p>Quick and reliable delivery with real-time tracking for all your orders.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Secure Payment</h3>
                <p>Your payment information is protected with advanced security measures.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3>24/7 Support</h3>
                <p>Our customer support team is available round the clock to help you.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-undo"></i>
                </div>
                <h3>Easy Returns</h3>
                <p>Not satisfied? Return your products within 30 days for a full refund.</p>
            </div>
        </div>
    </div>
</section>

<style>
/* Global Background Override */
html, body {
    background-color: #ffffff !important;
    background: #ffffff !important;
}

/* Simple Auto-Sliding Carousel Styles */

/* Hero Section */
.hero {
    position: relative;
    overflow: hidden;
    background: #ffffff;
    padding: 2rem;
}

.simple-carousel {
    position: relative;
    width: 100%;
    max-width: 1200px;
    height: 400px;
    overflow: hidden;
    background: #ffffff;
    border-radius: 20px;
    margin: 0 auto;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.carousel-slides {
    position: relative;
    width: 100%;
    height: 100%;
    background: #ffffff;
}

.slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 1s ease-in-out;
    border-radius: 20px;
    overflow: hidden;
}

.slide.active {
    opacity: 1;
}

.slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    border-radius: 20px;
}

.slide-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    color: white;
    background: rgba(0, 0, 0, 0.7);
    padding: 2rem;
    border-radius: 10px;
    backdrop-filter: blur(5px);
    z-index: 10;
}

.slide-content h2 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    font-weight: bold;
    color: #ffffff !important;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
}

.slide-content p {
    font-size: 1.2rem;
    margin-bottom: 1.5rem;
    color: #ffffff !important;
    opacity: 1;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8);
}

.slide-content .btn {
    padding: 12px 30px;
    font-size: 1.1rem;
    border-radius: 25px;
    background: #ffffff;
    color: #333333;
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-block;
    position: relative;
    z-index: 100;
    cursor: pointer;
    pointer-events: auto;
    border: 2px solid #ffffff;
    font-weight: 600;
}

.slide-content .btn:hover {
    background: #f8f9fa;
    color: #000000;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    border-color: #f8f9fa;
}

/* Navigation Dots */
.slide-dots {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 10px;
}

.dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: all 0.3s ease;
}

.dot.active,
.dot:hover {
    background: white;
    transform: scale(1.2);
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero {
        padding: 1rem;
    }
    
    .simple-carousel {
        height: 300px;
        border-radius: 15px;
        max-width: 100%;
    }
    
    .slide,
    .slide img {
        border-radius: 15px;
    }
    
    .slide-content {
        padding: 1.5rem;
    }
    
    .slide-content h2 {
        font-size: 2rem;
    }
    
    .slide-content p {
        font-size: 1rem;
    }
}

@media (max-width: 480px) {
    .hero {
        padding: 0.5rem;
    }
    
    .simple-carousel {
        height: 250px;
        border-radius: 12px;
    }
    
    .slide,
    .slide img {
        border-radius: 12px;
    }
    
    .slide-content {
        padding: 1rem;
    }
    
    .slide-content h2 {
        font-size: 1.5rem;
    }
    
    .slide-content p {
        font-size: 0.9rem;
    }
}

.carousel-content h1 {
    font-size: 3.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 1.5rem;
    letter-spacing: -0.02em;
}

.carousel-content p {
    font-size: 1.25rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.95);
    margin-bottom: 2rem;
    max-width: 600px;
}

/* Enhanced Category Grid */
.categories-section {
    padding: var(--spacing-20) 0;
    background: linear-gradient(180deg, var(--gray-50) 0%, var(--white) 50%, var(--gray-50) 100%);
    position: relative;
}

.categories-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><defs><radialGradient id="a" cx="50%" cy="50%"><stop offset="0%" stop-color="rgb(37, 99, 235)" stop-opacity="0.03"/><stop offset="100%" stop-color="rgb(37, 99, 235)" stop-opacity="0"/></radialGradient></defs><circle cx="20" cy="10" r="10" fill="url(%23a)"/><circle cx="80" cy="10" r="10" fill="url(%23a)"/></svg>');
    opacity: 0.5;
}

.section-header {
    text-align: center;
    margin-bottom: var(--spacing-12);
    position: relative;
}

.section-title {
    font-size: 3rem;
    font-weight: 800;
    background: linear-gradient(135deg, #333333 0%, #666666 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: var(--spacing-4);
    letter-spacing: -0.03em;
}

.section-subtitle {
    font-size: var(--font-size-xl);
    font-weight: 500;
    color: var(--gray-600);
    max-width: 600px;
    margin: 0 auto;
}

.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: var(--spacing-8);
    position: relative;
    z-index: 2;
}

.category-card {
    background: linear-gradient(135deg, var(--white) 0%, #fafbfc 100%);
    border-radius: var(--border-radius-xl);
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0,0,0,0.08);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(255,255,255,0.8);
    position: relative;
}

.category-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.6), transparent);
    transition: left 0.6s;
    z-index: 1;
}

.category-card:hover::before {
    left: 100%;
}

.category-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 30px 60px rgba(0,0,0,0.15);
}

.category-image {
    height: 220px;
    overflow: hidden;
    position: relative;
}

.category-image::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.1) 100%);
}

.category-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: all 0.4s ease;
}

.category-card:hover .category-image img {
    transform: scale(1.1);
    filter: brightness(1.1) saturate(1.2);
}

.category-info {
    padding: var(--spacing-8);
    text-align: center;
    position: relative;
    z-index: 2;
}

.category-info h3 {
    font-size: var(--font-size-xl);
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: var(--spacing-3);
    letter-spacing: -0.01em;
}

.category-info p {
    color: var(--gray-600);
    margin-bottom: var(--spacing-6);
    line-height: 1.6;
}

.btn-outline {
    background: transparent;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
    font-weight: 600;
    padding: var(--spacing-3) var(--spacing-8);
    border-radius: var(--border-radius-lg);
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-size: var(--font-size-sm);
}

.btn-outline:hover {
    background: var(--primary-color);
    color: var(--white);
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
}

/* Enhanced Products Section */
.products-section {
    padding: var(--spacing-20) 0;
    background: linear-gradient(180deg, var(--white) 0%, var(--gray-50) 100%);
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--spacing-6);
    margin-top: var(--spacing-12);
}

.product-card {
    background: linear-gradient(135deg, var(--white) 0%, #fafbfc 100%);
    border-radius: var(--border-radius-xl);
    overflow: hidden;
    box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(255,255,255,0.8);
    position: relative;
}

.product-card::before {
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

.product-card:hover::before {
    transform: scaleX(1);
}

.product-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 25px 50px rgba(0,0,0,0.15);
}

.product-image {
    height: 220px;
    overflow: hidden;
    position: relative;
}

.product-image::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.05) 100%);
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: all 0.4s ease;
}

.product-card:hover .product-image img {
    transform: scale(1.1);
    filter: brightness(1.1) saturate(1.2);
}

.product-badge {
    position: absolute;
    top: var(--spacing-3);
    right: var(--spacing-3);
    background: linear-gradient(135deg, var(--danger-color), #dc2626);
    color: var(--white);
    padding: var(--spacing-2) var(--spacing-3);
    border-radius: var(--border-radius);
    font-size: var(--font-size-xs);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    z-index: 2;
}

.product-info {
    padding: var(--spacing-6);
}

.product-info h3 {
    font-size: var(--font-size-lg);
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: var(--spacing-2);
    letter-spacing: -0.01em;
}

.product-price {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    margin-bottom: var(--spacing-4);
}

.current-price {
    font-size: var(--font-size-xl);
    font-weight: 800;
    color: var(--primary-color);
}

.original-price {
    font-size: var(--font-size-base);
    color: var(--gray-400);
    text-decoration: line-through;
}

/* Enhanced Features Section */
.features-section {
    padding: var(--spacing-20) 0;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    color: #333333;
    position: relative;
    overflow: hidden;
}

.features-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><defs><radialGradient id="a" cx="50%" cy="50%"><stop offset="0%" stop-color="white" stop-opacity="0.1"/><stop offset="100%" stop-color="white" stop-opacity="0"/></radialGradient></defs><circle cx="10" cy="5" r="8" fill="url(%23a)"/><circle cx="90" cy="15" r="8" fill="url(%23a)"/></svg>');
    animation: float 8s ease-in-out infinite;
}

.features-section .section-title {
    color: #333333 !important;
    -webkit-text-fill-color: #333333 !important;
    text-shadow: none;
}

.features-section .section-subtitle {
    color: #666666;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--spacing-8);
    margin-top: var(--spacing-12);
    position: relative;
    z-index: 2;
}

.feature-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: var(--border-radius-xl);
    padding: var(--spacing-8);
    text-align: center;
    transition: all 0.3s ease;
}

.feature-card:hover {
    background: rgba(0, 0, 0, 0.05);
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

.feature-icon {
    font-size: 3rem;
    margin-bottom: var(--spacing-4);
    color: #333333;
    filter: none;
}

.feature-card h3 {
    font-size: var(--font-size-xl);
    font-weight: 700;
    color: #333333;
    margin-bottom: var(--spacing-3);
}

.feature-card p {
    color: #666666;
    line-height: 1.6;
}

.category-info h3 {
    margin-bottom: var(--spacing-3);
    color: var(--gray-900);
}

.category-info p {
    color: var(--gray-600);
    margin-bottom: var(--spacing-5);
}

/* Features Section */
.features-section {
    padding: var(--spacing-16) 0;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-8);
}

.feature-card {
    text-align: center;
    padding: var(--spacing-6);
}

.feature-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto var(--spacing-5);
    color: var(--white);
    font-size: var(--font-size-2xl);
}

.feature-card h3 {
    color: var(--gray-900);
    margin-bottom: var(--spacing-3);
}

.feature-card p {
    color: var(--gray-600);
    margin-bottom: 0;
}

/* Responsive Design */
@media (max-width: 768px) {
    .features-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--spacing-6);
    }
    
    .category-grid {
        grid-template-columns: 1fr;
    }
    
    .product-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: var(--spacing-4);
    }
}

@media (max-width: 480px) {
    .product-grid {
        grid-template-columns: 1fr;
        gap: var(--spacing-4);
    }
}
</style>

<script>
// Simple Auto-Sliding Carousel - Initialize immediately
let currentSlideIndex = 0;
let autoSlideInterval;

function initCarousel() {
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.dot');
    const totalSlides = slides.length;

    console.log('Initializing carousel with', totalSlides, 'slides');

    // Function to show a specific slide
    function showSlide(n) {
        console.log('Showing slide:', n);
        
        // Handle wrapping around
        if (n >= totalSlides) {
            currentSlideIndex = 0;
        } else if (n < 0) {
            currentSlideIndex = totalSlides - 1;
        } else {
            currentSlideIndex = n;
        }
        
        // Hide all slides and dots
        slides.forEach((slide, index) => {
            slide.classList.remove('active');
        });
        dots.forEach((dot, index) => {
            dot.classList.remove('active');
        });
        
        // Show current slide and dot
        if (slides[currentSlideIndex]) {
            slides[currentSlideIndex].classList.add('active');
        }
        if (dots[currentSlideIndex]) {
            dots[currentSlideIndex].classList.add('active');
        }
        
        console.log('Active slide index:', currentSlideIndex);
    }

    // Function to go to next slide
    function nextSlide() {
        currentSlideIndex++;
        showSlide(currentSlideIndex);
    }

    // Function for dot navigation
    window.currentSlide = function(n) {
        console.log('Dot clicked:', n);
        clearInterval(autoSlideInterval);
        showSlide(n - 1);
        startAutoSlide();
    }

    // Start auto-sliding
    function startAutoSlide() {
        autoSlideInterval = setInterval(nextSlide, 4000);
        console.log('Auto-slide started');
    }

    // Initialize first slide
    showSlide(0);
    
    // Start auto-sliding after 2 seconds
    setTimeout(startAutoSlide, 2000);
    
    // Add click event debugging for carousel buttons
    const carouselButtons = document.querySelectorAll('.slide-content .btn');
    carouselButtons.forEach((button, index) => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default link behavior
            e.stopPropagation(); // Stop event bubbling
            
            const buttonText = this.textContent.trim();
            const href = this.getAttribute('href');
            const dataUrl = this.getAttribute('data-url');
            const targetUrl = dataUrl || href;
            
            console.log('Button clicked:', buttonText);
            console.log('Original href:', href);
            console.log('Data URL:', dataUrl);
            console.log('Target URL:', targetUrl);
            
            if (targetUrl) {
                console.log('Navigating to:', targetUrl);
                // Use window.location.href for immediate navigation
                window.location.href = targetUrl;
            } else {
                console.error('No valid URL found for button:', buttonText);
            }
            
            return false;
        });
    });
    
    // Specific button handlers for extra safety
    const cafeBtn = document.querySelector('.cafe-btn');
    const clothingBtn = document.querySelector('.clothing-btn');
    const supportBtn = document.querySelector('.support-btn');
    
    if (cafeBtn) {
        cafeBtn.onclick = function(e) {
            e.preventDefault();
            console.log('Cafe button direct click');
            window.location.href = 'products.php?category=cafe';
            return false;
        };
    }
    
    if (clothingBtn) {
        clothingBtn.onclick = function(e) {
            e.preventDefault();
            console.log('Clothing button direct click');
            window.location.href = 'products.php?category=clothing';
            return false;
        };
    }
    
    if (supportBtn) {
        supportBtn.onclick = function(e) {
            e.preventDefault();
            console.log('Support button direct click');
            window.location.href = 'about.php';
            return false;
        };
    }
    
    // Prevent carousel container from intercepting clicks
    const carouselContainer = document.querySelector('.simple-carousel');
    if (carouselContainer) {
        carouselContainer.addEventListener('click', function(e) {
            // Only handle clicks that are not on buttons
            if (!e.target.closest('.btn')) {
                console.log('Carousel container clicked, but not a button');
            }
        });
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCarousel);
} else {
    initCarousel();
}

// Also initialize with jQuery for compatibility
$(document).ready(function() {
    // Backup initialization in case the above doesn't work
    setTimeout(initCarousel, 1000);
});
</script>

<?php include 'includes/footer.php'; ?>
