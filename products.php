<?php
require_once 'includes/config.php';

$pageTitle = 'Products';
$pageDescription = 'Browse our complete collection of premium clothing and delicious cafe items';
$pageKeywords = 'products, clothing, jersey, cap, hoodie, cafe, pizza, coffee, shop online';

// Get parameters
$categorySlug = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$sortBy = $_GET['sort'] ?? 'newest';
$priceMin = (float) ($_GET['price_min'] ?? 0);
$priceMax = (float) ($_GET['price_max'] ?? 0);
$page = (int) ($_GET['page'] ?? 1);
$perPage = 12;
$offset = ($page - 1) * $perPage;

$db = Database::getInstance()->getConnection();

// Build query
$whereConditions = ["p.status = 'active'"];
$params = [];

if ($categorySlug) {
    $whereConditions[] = "c.slug = ?";
    $params[] = $categorySlug;
}

if ($search) {
    $whereConditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($priceMin > 0) {
    $whereConditions[] = "COALESCE(p.sale_price, p.price) >= ?";
    $params[] = $priceMin;
}

if ($priceMax > 0) {
    $whereConditions[] = "COALESCE(p.sale_price, p.price) <= ?";
    $params[] = $priceMax;
}

$whereClause = implode(' AND ', $whereConditions);

// Order by
$orderBy = 'p.created_at DESC';
switch ($sortBy) {
    case 'name_asc':
        $orderBy = 'p.name ASC';
        break;
    case 'name_desc':
        $orderBy = 'p.name DESC';
        break;
    case 'price_asc':
        $orderBy = 'COALESCE(p.sale_price, p.price) ASC';
        break;
    case 'price_desc':
        $orderBy = 'COALESCE(p.sale_price, p.price) DESC';
        break;
    case 'popular':
        $orderBy = 'p.featured DESC, p.created_at DESC';
        break;
    case 'newest':
    default:
        $orderBy = 'p.created_at DESC';
        break;
}

// Get total count
$countQuery = "
    SELECT COUNT(*) 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE $whereClause
";
$stmt = $db->prepare($countQuery);
$stmt->execute($params);
$totalProducts = $stmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Get products
$query = "
    SELECT p.*, c.name as category_name, c.slug as category_slug,
           (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE $whereClause
    ORDER BY $orderBy
    LIMIT $offset, $perPage
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$categories = getCategories();

// Get price range
$priceQuery = "
    SELECT MIN(COALESCE(p.sale_price, p.price)) as min_price, 
           MAX(COALESCE(p.sale_price, p.price)) as max_price
    FROM products p 
    WHERE p.status = 'active'
";
$stmt = $db->prepare($priceQuery);
$stmt->execute();
$priceRange = $stmt->fetch(PDO::FETCH_ASSOC);

// Get current category info
$currentCategory = null;
if ($categorySlug) {
    $stmt = $db->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->execute([$categorySlug]);
    $currentCategory = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($currentCategory) {
        $pageTitle = $currentCategory['name'] . ' - Products';
        $pageDescription = $currentCategory['description'] ?? $pageDescription;
    }
}
?>

<?php include 'includes/header.php'; ?>

<!-- Breadcrumb -->
<div class="breadcrumb-section">
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>/">Home</a>
            <span class="breadcrumb-separator">/</span>
            <?php if ($currentCategory): ?>
                <span><?php echo $currentCategory['name']; ?></span>
            <?php else: ?>
                <span>Products</span>
            <?php endif; ?>
        </nav>
    </div>
</div>

<!-- Products Section -->
<section class="products-page">
    <div class="container">
        <div class="products-layout">
            <!-- Sidebar Filters -->
            <aside class="products-sidebar">
                <div class="filter-section">
                    <h3>Categories</h3>
                    <ul class="category-filter">
                        <li>
                            <a href="<?php echo SITE_URL; ?>/products.php" 
                               class="<?php echo !$categorySlug ? 'active' : ''; ?>">
                                All Products
                            </a>
                        </li>
                        <?php foreach ($categories as $category): ?>
                        <li>
                            <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $category['slug']; ?>" 
                               class="<?php echo $categorySlug === $category['slug'] ? 'active' : ''; ?>">
                                <?php echo $category['name']; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="filter-section">
                    <h3>Price Range</h3>
                    <form class="price-filter" method="GET">
                        <?php if ($categorySlug): ?>
                        <input type="hidden" name="category" value="<?php echo $categorySlug; ?>">
                        <?php endif; ?>
                        <?php if ($search): ?>
                        <input type="hidden" name="search" value="<?php echo sanitizeInput($search); ?>">
                        <?php endif; ?>
                        <input type="hidden" name="sort" value="<?php echo $sortBy; ?>">
                        
                        <div class="price-inputs">
                            <input type="number" name="price_min" placeholder="Min" 
                                   value="<?php echo $priceMin ?: ''; ?>" 
                                   min="0" max="<?php echo $priceRange['max_price']; ?>">
                            <span>to</span>
                            <input type="number" name="price_max" placeholder="Max" 
                                   value="<?php echo $priceMax ?: ''; ?>" 
                                   min="0" max="<?php echo $priceRange['max_price']; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    </form>
                </div>
                
                <?php if ($priceMin || $priceMax || $search): ?>
                <div class="filter-section">
                    <h3>Active Filters</h3>
                    <div class="active-filters">
                        <?php if ($search): ?>
                        <span class="filter-tag">
                            Search: "<?php echo sanitizeInput($search); ?>"
                            <a href="<?php echo SITE_URL; ?>/products.php<?php echo $categorySlug ? '?category=' . $categorySlug : ''; ?>">×</a>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($priceMin || $priceMax): ?>
                        <span class="filter-tag">
                            Price: Rs. <?php echo $priceMin ?: '0'; ?> - Rs. <?php echo $priceMax ?: $priceRange['max_price']; ?>
                            <a href="<?php echo str_replace(['&price_min=' . $priceMin, '&price_max=' . $priceMax, '?price_min=' . $priceMin, '?price_max=' . $priceMax], '', $_SERVER['REQUEST_URI']); ?>">×</a>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </aside>
            
            <!-- Main Content -->
            <main class="products-main">
                <!-- Products Header -->
                <div class="products-header">
                    <div class="products-info">
                        <h1>
                            <?php if ($currentCategory): ?>
                                <?php echo $currentCategory['name']; ?>
                            <?php elseif ($search): ?>
                                Search Results for "<?php echo sanitizeInput($search); ?>"
                            <?php else: ?>
                                All Products
                            <?php endif; ?>
                        </h1>
                        <p class="products-count">
                            Showing <?php echo count($products); ?> of <?php echo $totalProducts; ?> products
                        </p>
                    </div>
                    
                    <div class="products-controls">
                        <form class="sort-form" method="GET">
                            <?php if ($categorySlug): ?>
                            <input type="hidden" name="category" value="<?php echo $categorySlug; ?>">
                            <?php endif; ?>
                            <?php if ($search): ?>
                            <input type="hidden" name="search" value="<?php echo sanitizeInput($search); ?>">
                            <?php endif; ?>
                            <?php if ($priceMin): ?>
                            <input type="hidden" name="price_min" value="<?php echo $priceMin; ?>">
                            <?php endif; ?>
                            <?php if ($priceMax): ?>
                            <input type="hidden" name="price_max" value="<?php echo $priceMax; ?>">
                            <?php endif; ?>
                            
                            <select name="sort" onchange="this.form.submit()">
                                <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Newest</option>
                                <option value="popular" <?php echo $sortBy === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                                <option value="price_asc" <?php echo $sortBy === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_desc" <?php echo $sortBy === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="name_asc" <?php echo $sortBy === 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                                <option value="name_desc" <?php echo $sortBy === 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                            </select>
                        </form>
                        
                        <div class="view-toggle">
                            <button class="view-btn active" data-view="grid" title="Grid View">
                                <i class="fas fa-th"></i>
                            </button>
                            <button class="view-btn" data-view="list" title="List View">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <?php if (empty($products)): ?>
                <div class="no-products">
                    <div class="no-products-content">
                        <i class="fas fa-search fa-3x"></i>
                        <h3>No products found</h3>
                        <p>Try adjusting your search criteria or browse our categories.</p>
                        <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">View All Products</a>
                    </div>
                </div>
                <?php else: ?>
                <div class="product-grid" id="productGrid">
                    <?php foreach ($products as $product): ?>
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
                                <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $product['slug']; ?>" 
                                   class="product-action" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
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
                                <span class="rating-count">(12 reviews)</span>
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
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination-wrapper">
                    <nav class="pagination">
                        <?php
                        $baseUrl = $_SERVER['PHP_SELF'] . '?';
                        $params = $_GET;
                        unset($params['page']);
                        $queryString = http_build_query($params);
                        if ($queryString) {
                            $baseUrl .= $queryString . '&';
                        }
                        ?>
                        
                        <?php if ($page > 1): ?>
                        <a href="<?php echo $baseUrl; ?>page=<?php echo $page - 1; ?>" class="page-link prev">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        if ($startPage > 1): ?>
                        <a href="<?php echo $baseUrl; ?>page=1" class="page-link">1</a>
                        <?php if ($startPage > 2): ?>
                        <span class="page-dots">...</span>
                        <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="<?php echo $baseUrl; ?>page=<?php echo $i; ?>" 
                           class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                        <span class="page-dots">...</span>
                        <?php endif; ?>
                        <a href="<?php echo $baseUrl; ?>page=<?php echo $totalPages; ?>" class="page-link"><?php echo $totalPages; ?></a>
                        <?php endif; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <a href="<?php echo $baseUrl; ?>page=<?php echo $page + 1; ?>" class="page-link next">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>
</section>

<style>
/* Enhanced Premium Products Page Styles */

/* Breadcrumb Enhancement */
.breadcrumb-section {
    background: linear-gradient(135deg, var(--white) 0%, var(--gray-50) 100%);
    padding: var(--spacing-6) 0;
    border-bottom: 1px solid var(--gray-200);
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    font-size: var(--font-size-sm);
    font-weight: 500;
}

.breadcrumb a {
    color: var(--gray-600);
    text-decoration: none;
    transition: all 0.3s ease;
    padding: var(--spacing-1) var(--spacing-2);
    border-radius: var(--border-radius);
}

.breadcrumb a:hover {
    color: var(--primary-color);
    background: rgba(37, 99, 235, 0.1);
}

.breadcrumb-separator {
    color: var(--gray-400);
    font-weight: 600;
}

.products-page {
    padding: var(--spacing-12) 0;
    background: linear-gradient(180deg, var(--gray-50) 0%, var(--white) 50%, var(--gray-50) 100%);
    min-height: 80vh;
}

.products-layout {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: var(--spacing-10);
    align-items: start;
}
}

/* Enhanced Sidebar */
.products-sidebar {
    background: linear-gradient(135deg, var(--white) 0%, #fafbfc 100%);
    border-radius: var(--border-radius-xl);
    padding: var(--spacing-8);
    height: fit-content;
    box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    border: 1px solid rgba(255,255,255,0.8);
    position: sticky;
    top: var(--spacing-8);
}

.filter-section {
    margin-bottom: var(--spacing-8);
    padding-bottom: var(--spacing-8);
    border-bottom: 1px solid var(--gray-200);
    position: relative;
}

.filter-section::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 40px;
    height: 2px;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
}

.filter-section:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.filter-section:last-child::after {
    display: none;
}

.filter-section h3 {
    font-size: var(--font-size-lg);
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: var(--spacing-6);
    letter-spacing: -0.01em;
}

.category-filter {
    list-style: none;
}

.category-filter li {
    margin-bottom: var(--spacing-3);
}

.category-filter a {
    display: block;
    padding: var(--spacing-3) var(--spacing-4);
    color: var(--gray-600);
    text-decoration: none;
    border-radius: var(--border-radius-lg);
    transition: all 0.3s ease;
    font-weight: 500;
    position: relative;
    overflow: hidden;
}

.category-filter a::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(37, 99, 235, 0.1), transparent);
    transition: left 0.3s ease;
}

.category-filter a:hover::before {
    left: 100%;
}

.category-filter a:hover,
.category-filter a.active {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: var(--white);
    transform: translateX(5px);
    box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
}

.price-filter {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-4);
}

.price-inputs {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
}

.price-inputs input {
    flex: 1;
    padding: var(--spacing-3);
    border: 2px solid var(--gray-200);
    border-radius: var(--border-radius-lg);
    font-size: var(--font-size-sm);
    transition: all 0.3s ease;
}

.price-inputs input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* Enhanced Main Content */
.products-main {
    background: transparent;
}

.products-header {
    background: linear-gradient(135deg, var(--white) 0%, #fafbfc 100%);
    padding: var(--spacing-6);
    border-radius: var(--border-radius-xl);
    margin-bottom: var(--spacing-8);
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    border: 1px solid rgba(255,255,255,0.8);
}

.products-header h1 {
    font-size: var(--font-size-3xl);
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: var(--spacing-2);
    letter-spacing: -0.02em;
}

.products-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--spacing-4);
    padding: var(--spacing-6);
    background: linear-gradient(135deg, var(--white) 0%, #fafbfc 100%);
    border-radius: var(--border-radius-xl);
    margin-bottom: var(--spacing-8);
    box-shadow: 0 8px 20px rgba(0,0,0,0.05);
    border: 1px solid rgba(255,255,255,0.8);
}

.view-toggle {
    display: flex;
    gap: var(--spacing-2);
    background: var(--gray-100);
    padding: var(--spacing-1);
    border-radius: var(--border-radius-lg);
}

.view-toggle button {
    padding: var(--spacing-2) var(--spacing-3);
    border: none;
    background: transparent;
    color: var(--gray-600);
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
}

.view-toggle button.active,
.view-toggle button:hover {
    background: var(--white);
    color: var(--primary-color);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.sort-dropdown select {
    padding: var(--spacing-3) var(--spacing-4);
    border: 2px solid var(--gray-200);
    border-radius: var(--border-radius-lg);
    background: var(--white);
    color: var(--gray-700);
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.sort-dropdown select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}
    border: 1px solid var(--gray-300);
    border-radius: var(--border-radius);
    font-size: var(--font-size-sm);
}

.active-filters {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}

.filter-tag {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    background: var(--gray-100);
    color: var(--gray-700);
    padding: var(--spacing-1) var(--spacing-3);
    border-radius: var(--border-radius);
    font-size: var(--font-size-sm);
}

.filter-tag a {
    color: var(--gray-500);
    text-decoration: none;
    font-weight: bold;
}

.filter-tag a:hover {
    color: var(--danger-color);
}

/* Main Content */
.products-main {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-6);
    box-shadow: var(--shadow);
}

.products-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--spacing-6);
    padding-bottom: var(--spacing-6);
    border-bottom: 1px solid var(--gray-200);
}

.products-info h1 {
    font-size: var(--font-size-2xl);
    color: var(--gray-900);
    margin-bottom: var(--spacing-2);
}

.products-count {
    color: var(--gray-600);
    font-size: var(--font-size-sm);
    margin: 0;
}

.products-controls {
    display: flex;
    align-items: center;
    gap: var(--spacing-4);
}

.sort-form select {
    padding: var(--spacing-2) var(--spacing-3);
    border: 1px solid var(--gray-300);
    border-radius: var(--border-radius);
    background: var(--white);
    font-size: var(--font-size-sm);
}

.view-toggle {
    display: flex;
    border: 1px solid var(--gray-300);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.view-btn {
    background: var(--white);
    border: none;
    padding: var(--spacing-2) var(--spacing-3);
    color: var(--gray-600);
    cursor: pointer;
    transition: var(--transition-fast);
}

.view-btn:hover,
.view-btn.active {
    background: var(--primary-color);
    color: var(--white);
}

/* No Products */
.no-products {
    text-align: center;
    padding: var(--spacing-16) var(--spacing-8);
}

.no-products-content i {
    color: var(--gray-400);
    margin-bottom: var(--spacing-6);
}

.no-products-content h3 {
    color: var(--gray-700);
    margin-bottom: var(--spacing-4);
}

.no-products-content p {
    color: var(--gray-600);
    margin-bottom: var(--spacing-6);
}

/* Pagination */
.pagination-wrapper {
    margin-top: var(--spacing-8);
    padding-top: var(--spacing-6);
    border-top: 1px solid var(--gray-200);
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: var(--spacing-2);
}

.page-link {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: var(--spacing-2) var(--spacing-3);
    background: var(--white);
    border: 1px solid var(--gray-300);
    border-radius: var(--border-radius);
    color: var(--gray-700);
    text-decoration: none;
    transition: var(--transition-fast);
}

.page-link:hover {
    background: var(--gray-50);
    border-color: var(--gray-400);
    color: var(--gray-900);
}

.page-link.active {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: var(--white);
}

.page-dots {
    color: var(--gray-500);
    padding: var(--spacing-2);
}

/* Responsive Design */
@media (max-width: 1024px) {
    .products-layout {
        grid-template-columns: 250px 1fr;
        gap: var(--spacing-6);
    }
}

@media (max-width: 768px) {
    .products-layout {
        grid-template-columns: 1fr;
        gap: var(--spacing-4);
    }
    
    .products-sidebar {
        order: 2;
    }
    
    .products-main {
        order: 1;
    }
    
    .products-header {
        flex-direction: column;
        gap: var(--spacing-4);
        align-items: stretch;
    }
    
    .products-controls {
        justify-content: space-between;
    }
    
    .pagination {
        flex-wrap: wrap;
    }
}

@media (max-width: 480px) {
    .price-inputs {
        flex-direction: column;
    }
    
    .products-controls {
        flex-direction: column;
        gap: var(--spacing-3);
    }
}
</style>

<?php include 'includes/footer.php'; ?>
