<?php
require_once 'includes/config.php';

$pageTitle = 'Search Results';
$db = Database::getInstance()->getConnection();

// Get search parameters
$query = trim($_GET['q'] ?? '');
$category = $_GET['category'] ?? '';
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$sortBy = $_GET['sort'] ?? 'relevance';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

// Build search query
$searchConditions = ['p.status = ?'];
$searchParams = ['active'];

if ($query) {
    $searchConditions[] = '(p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ? OR c.name LIKE ?)';
    $searchTerm = "%$query%";
    $searchParams[] = $searchTerm;
    $searchParams[] = $searchTerm;
    $searchParams[] = $searchTerm;
    $searchParams[] = $searchTerm;
}

if ($category) {
    $searchConditions[] = 'p.category_id = ?';
    $searchParams[] = $category;
}

if ($minPrice) {
    $searchConditions[] = 'COALESCE(p.sale_price, p.price) >= ?';
    $searchParams[] = $minPrice;
}

if ($maxPrice) {
    $searchConditions[] = 'COALESCE(p.sale_price, p.price) <= ?';
    $searchParams[] = $maxPrice;
}

// Determine order by clause
$orderBy = 'p.created_at DESC';
switch ($sortBy) {
    case 'price_low':
        $orderBy = 'COALESCE(p.sale_price, p.price) ASC';
        break;
    case 'price_high':
        $orderBy = 'COALESCE(p.sale_price, p.price) DESC';
        break;
    case 'name':
        $orderBy = 'p.name ASC';
        break;
    case 'rating':
        $orderBy = 'avg_rating DESC, p.created_at DESC';
        break;
    case 'popularity':
        $orderBy = 'p.view_count DESC, p.created_at DESC';
        break;
}

// Get total count
$countSql = "
    SELECT COUNT(DISTINCT p.id)
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE " . implode(' AND ', $searchConditions);

$stmt = $db->prepare($countSql);
$stmt->execute($searchParams);
$totalResults = $stmt->fetchColumn();
$totalPages = ceil($totalResults / $limit);

// Get products
try {
    // Ensure required tables exist
    $db->exec("CREATE TABLE IF NOT EXISTS product_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        image_url VARCHAR(500) NOT NULL,
        alt_text VARCHAR(255),
        is_primary BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(product_id)
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS product_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        user_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        title VARCHAR(255),
        review TEXT,
        is_approved BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(product_id),
        INDEX(user_id)
    )");
} catch (Exception $e) {
    error_log("Table creation error: " . $e->getMessage());
}

$sql = "
    SELECT p.*, c.name as category_name, c.slug as category_slug,
           COALESCE((SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1), 'https://via.placeholder.com/400x300?text=No+Image') as primary_image,
           COALESCE((SELECT AVG(rating) FROM product_reviews WHERE product_id = p.id AND is_approved = 1), 0) as avg_rating,
           COALESCE((SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id AND is_approved = 1), 0) as review_count
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE " . implode(' AND ', $searchConditions) . "
    ORDER BY $orderBy
    LIMIT $offset, $limit
";

// Execute with error handling
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($searchParams);
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Search query error: " . $e->getMessage());
    
    // Fallback to simple query without reviews and images
    $simpleSql = "
        SELECT p.*, c.name as category_name, c.slug as category_slug,
               'https://via.placeholder.com/400x300?text=No+Image' as primary_image,
               0 as avg_rating,
               0 as review_count
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE " . implode(' AND ', $searchConditions) . "
        ORDER BY $orderBy
        LIMIT $offset, $limit
    ";
    
    $stmt = $db->prepare($simpleSql);
    $stmt->execute($searchParams);
    $products = $stmt->fetchAll();
}

// Get categories for filter
$stmt = $db->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Search suggestions (if no results)
$suggestions = [];
if (empty($products) && $query) {
    // Get similar products by partial name match
    $stmt = $db->prepare("
        SELECT DISTINCT p.name
        FROM products p
        WHERE p.status = 'active' AND p.name LIKE ?
        LIMIT 5
    ");
    $stmt->execute(["%$query%"]);
    $suggestions = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

<?php include 'includes/header.php'; ?>

<div class="search-page">
    <div class="container">
        <!-- Search Header -->
        <div class="search-header">
            <div class="search-title">
                <?php if ($query): ?>
                <h1>Search Results for "<?php echo htmlspecialchars($query); ?>"</h1>
                <?php else: ?>
                <h1>All Products</h1>
                <?php endif; ?>
                <p class="search-count"><?php echo number_format($totalResults); ?> product<?php echo $totalResults !== 1 ? 's' : ''; ?> found</p>
            </div>
            
            <!-- Advanced Search Toggle -->
            <button class="btn btn-outline advanced-search-toggle" onclick="toggleAdvancedSearch()">
                <i class="fas fa-filter"></i>
                Advanced Filters
            </button>
        </div>
        
        <!-- Advanced Search Filters -->
        <div class="advanced-search" id="advancedSearch">
            <form method="GET" class="search-filters">
                <input type="hidden" name="q" value="<?php echo htmlspecialchars($query); ?>">
                
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="categoryFilter">Category</label>
                        <select name="category" id="categoryFilter">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo $cat['name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="minPrice">Min Price</label>
                        <input type="number" name="min_price" id="minPrice" value="<?php echo $minPrice; ?>" placeholder="₹0">
                    </div>
                    
                    <div class="filter-group">
                        <label for="maxPrice">Max Price</label>
                        <input type="number" name="max_price" id="maxPrice" value="<?php echo $maxPrice; ?>" placeholder="₹10000">
                    </div>
                    
                    <div class="filter-group">
                        <label for="sortBy">Sort By</label>
                        <select name="sort" id="sortBy">
                            <option value="relevance" <?php echo $sortBy === 'relevance' ? 'selected' : ''; ?>>Relevance</option>
                            <option value="price_low" <?php echo $sortBy === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sortBy === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                            <option value="rating" <?php echo $sortBy === 'rating' ? 'selected' : ''; ?>>Rating</option>
                            <option value="popularity" <?php echo $sortBy === 'popularity' ? 'selected' : ''; ?>>Popularity</option>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="search.php<?php echo $query ? '?q=' . urlencode($query) : ''; ?>" class="btn btn-outline">Clear</a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Search Results -->
        <div class="search-results">
            <?php if (empty($products)): ?>
            <!-- No Results -->
            <div class="no-results">
                <div class="no-results-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h2>No products found</h2>
                <?php if ($query): ?>
                <p>Sorry, we couldn't find any products matching "<?php echo htmlspecialchars($query); ?>".</p>
                <?php endif; ?>
                
                <?php if (!empty($suggestions)): ?>
                <div class="search-suggestions">
                    <h3>Did you mean:</h3>
                    <div class="suggestion-list">
                        <?php foreach ($suggestions as $suggestion): ?>
                        <a href="search.php?q=<?php echo urlencode($suggestion); ?>" class="suggestion-item">
                            <?php echo htmlspecialchars($suggestion); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="search-help">
                    <h3>Search Tips:</h3>
                    <ul>
                        <li>Check your spelling</li>
                        <li>Use different keywords</li>
                        <li>Try broader search terms</li>
                        <li>Browse our categories</li>
                    </ul>
                </div>
                
                <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">
                    View All Products
                </a>
            </div>
            
            <?php else: ?>
            <!-- Results Header -->
            <div class="results-header">
                <div class="results-info">
                    Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $totalResults); ?> 
                    of <?php echo number_format($totalResults); ?> results
                </div>
                
                <div class="view-options">
                    <button class="view-toggle active" data-view="grid" title="Grid View">
                        <i class="fas fa-th"></i>
                    </button>
                    <button class="view-toggle" data-view="list" title="List View">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
            </div>
            
            <!-- Product Grid -->
            <div class="products-grid" id="productsGrid">
                <?php foreach ($products as $product): ?>
                <div class="product-card" data-product-id="<?php echo $product['id']; ?>">
                    <div class="product-image">
                        <img src="<?php echo $product['primary_image'] ?: SITE_URL . '/assets/images/placeholder.jpg'; ?>" 
                             alt="<?php echo $product['name']; ?>" loading="lazy">
                        
                        <!-- Product Actions -->
                        <div class="product-actions">
                            <button class="action-btn wishlist-btn" onclick="toggleWishlist(<?php echo $product['id']; ?>)" title="Add to Wishlist">
                                <i class="far fa-heart"></i>
                            </button>
                            <button class="action-btn compare-btn" onclick="addToCompare(<?php echo $product['id']; ?>)" title="Compare">
                                <i class="fas fa-balance-scale"></i>
                            </button>
                            <button class="action-btn quick-view-btn" onclick="quickView(<?php echo $product['id']; ?>)" title="Quick View">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        
                        <!-- Sale Badge -->
                        <?php if ($product['sale_price']): ?>
                        <div class="sale-badge">
                            <?php echo round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>% OFF
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-info">
                        <div class="product-category">
                            <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $product['category_slug']; ?>">
                                <?php echo $product['category_name']; ?>
                            </a>
                        </div>
                        
                        <h3 class="product-name">
                            <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $product['slug']; ?>">
                                <?php echo $product['name']; ?>
                            </a>
                        </h3>
                        
                        <div class="product-rating">
                            <?php if ($product['avg_rating']): ?>
                            <div class="stars">
                                <?php
                                $rating = round($product['avg_rating']);
                                for ($i = 1; $i <= 5; $i++):
                                ?>
                                <i class="star <?php echo $i <= $rating ? 'fas filled' : 'far'; ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-text">(<?php echo $product['review_count']; ?>)</span>
                            <?php else: ?>
                            <span class="no-rating">No reviews yet</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-price">
                            <?php if ($product['sale_price']): ?>
                            <span class="sale-price">₹<?php echo number_format($product['sale_price']); ?></span>
                            <span class="original-price">₹<?php echo number_format($product['price']); ?></span>
                            <?php else: ?>
                            <span class="current-price">₹<?php echo number_format($product['price']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <button class="btn btn-primary add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>)">
                            <i class="fas fa-shopping-cart"></i>
                            Add to Cart
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination-wrapper">
                <nav class="pagination">
                    <?php
                    $queryParams = $_GET;
                    unset($queryParams['page']);
                    $baseUrl = 'search.php?' . http_build_query($queryParams);
                    $baseUrl .= empty($queryParams) ? 'page=' : '&page=';
                    ?>
                    
                    <?php if ($page > 1): ?>
                    <a href="<?php echo $baseUrl . ($page - 1); ?>" class="page-link prev">
                        <i class="fas fa-chevron-left"></i>
                        Previous
                    </a>
                    <?php endif; ?>
                    
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                    <a href="<?php echo $baseUrl . $i; ?>" 
                       class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <a href="<?php echo $baseUrl . ($page + 1); ?>" class="page-link next">
                        Next
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Compare Products Bar -->
<div class="compare-bar" id="compareBar" style="display: none;">
    <div class="container">
        <div class="compare-content">
            <div class="compare-info">
                <span class="compare-count">0</span> products selected for comparison
            </div>
            <div class="compare-actions">
                <button class="btn btn-primary" onclick="viewComparison()">Compare Now</button>
                <button class="btn btn-outline" onclick="clearComparison()">Clear All</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Search Page Styles */
.search-page {
    padding: 2rem 0;
    min-height: 70vh;
}

.search-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.search-title h1 {
    margin: 0 0 0.5rem;
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
}

.search-count {
    margin: 0;
    color: #6c757d;
    font-size: 1rem;
}

.advanced-search {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    margin-bottom: 2rem;
    overflow: hidden;
    display: none;
}

.advanced-search.active {
    display: block;
}

.search-filters {
    padding: 1.5rem;
}

.filter-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-group label {
    font-weight: 500;
    color: #2c3e50;
    font-size: 0.875rem;
}

.filter-group input, .filter-group select {
    padding: 0.75rem;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.filter-group input:focus, .filter-group select:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 0.125rem rgba(0, 123, 255, 0.25);
}

.filter-actions {
    display: flex;
    gap: 0.5rem;
}

/* No Results */
.no-results {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 1rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.no-results-icon {
    font-size: 4rem;
    color: #dee2e6;
    margin-bottom: 1.5rem;
}

.no-results h2 {
    margin-bottom: 1rem;
    color: #2c3e50;
}

.search-suggestions {
    margin: 2rem 0;
    text-align: left;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

.suggestion-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.suggestion-item {
    padding: 0.5rem 1rem;
    background: #f8f9fa;
    color: #007bff;
    text-decoration: none;
    border-radius: 2rem;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.suggestion-item:hover {
    background: #007bff;
    color: white;
}

.search-help {
    margin: 2rem 0;
    text-align: left;
    max-width: 300px;
    margin-left: auto;
    margin-right: auto;
}

.search-help ul {
    list-style: none;
    padding: 0;
}

.search-help li {
    padding: 0.25rem 0;
    color: #6c757d;
}

.search-help li:before {
    content: "•";
    color: #007bff;
    margin-right: 0.5rem;
}

/* Results */
.results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e9ecef;
}

.view-options {
    display: flex;
    gap: 0.25rem;
}

.view-toggle {
    background: none;
    border: 1px solid #dee2e6;
    padding: 0.5rem;
    border-radius: 0.25rem;
    cursor: pointer;
    color: #6c757d;
    transition: all 0.3s ease;
}

.view-toggle.active, .view-toggle:hover {
    background: #007bff;
    border-color: #007bff;
    color: white;
}

/* Product Grid */
.products-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr); /* Exactly 4 products per row */
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.products-grid.list-view {
    grid-template-columns: 1fr;
}

.products-grid.list-view .product-card {
    display: flex;
    gap: 1rem;
}

.products-grid.list-view .product-image {
    width: 200px;
    flex-shrink: 0;
}

.products-grid.list-view .product-info {
    flex: 1;
}

/* Product Card Styles */
.product-card {
    background: white;
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.3s ease;
    position: relative;
    height: fit-content;
    width: 100%;
    max-width: 100%;
}

.product-card:hover {
    transform: translateY(-0.25rem);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.product-image {
    position: relative;
    overflow: hidden;
    background: #f8f9fa;
    width: 100%;
    height: 200px; /* Fixed height for consistency */
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover; /* Ensures image fills container while maintaining aspect ratio */
    object-position: center;
    transition: transform 0.3s ease;
}

.product-card:hover .product-image img {
    transform: scale(1.05);
}

.product-actions {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    opacity: 0;
    transform: translateX(1rem);
    transition: all 0.3s ease;
}

.product-card:hover .product-actions {
    opacity: 1;
    transform: translateX(0);
}

.action-btn {
    width: 2.5rem;
    height: 2.5rem;
    background: white;
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: all 0.3s ease;
    color: #6c757d;
}

.action-btn:hover {
    background: #007bff;
    color: white;
    transform: scale(1.1);
}

.sale-badge {
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    background: #dc3545;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    z-index: 2;
}

.product-info {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    text-align: center;
}

.product-category {
    margin-bottom: 0.25rem;
}

.product-category a {
    color: #6c757d;
    text-decoration: none;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
}

.product-category a:hover {
    color: #007bff;
}

.product-name {
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
    font-weight: 600;
    line-height: 1.3;
    min-height: 2.6rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-name a {
    color: #2c3e50;
    text-decoration: none;
    transition: color 0.3s ease;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-name a:hover {
    color: #007bff;
}

.product-rating {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.stars {
    display: flex;
    gap: 0.125rem;
}

.star {
    font-size: 0.875rem;
    color: #dee2e6;
}

.star.filled {
    color: #ffc107;
}

.rating-text {
    font-size: 0.75rem;
    color: #6c757d;
}

.no-rating {
    font-size: 0.75rem;
    color: #adb5bd;
    font-style: italic;
}

.product-price {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.sale-price {
    font-size: 1.125rem;
    font-weight: 700;
    color: #dc3545;
}

.original-price {
    font-size: 0.875rem;
    color: #6c757d;
    text-decoration: line-through;
}

.current-price {
    font-size: 1.125rem;
    font-weight: 700;
    color: #2c3e50;
}

.add-to-cart-btn {
    width: auto;
    max-width: 100%;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 0.5rem;
    background: #007bff;
    color: white;
    font-weight: 500;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    text-align: center;
    white-space: nowrap;
    margin: 0 auto;
}

.add-to-cart-btn:hover {
    background: #0056b3;
    transform: translateY(-0.125rem);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 123, 255, 0.3);
}

.add-to-cart-btn:active {
    transform: translateY(0);
}

.add-to-cart-btn i {
    font-size: 0.875rem;
}

/* List view adjustments */
.products-grid.list-view .product-card {
    flex-direction: row;
    align-items: stretch;
    text-align: left;
}

.products-grid.list-view .product-image {
    width: 200px;
    height: 200px;
    flex-shrink: 0;
    aspect-ratio: unset;
}

.products-grid.list-view .product-info {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    flex: 1;
    text-align: left;
    padding: 1.5rem;
}

.products-grid.list-view .product-name {
    text-align: left;
    font-size: 1.125rem;
    min-height: auto;
}

.products-grid.list-view .product-rating {
    justify-content: flex-start;
}

.products-grid.list-view .product-price {
    justify-content: flex-start;
    margin-bottom: 1.5rem;
}

.products-grid.list-view .add-to-cart-btn {
    align-self: flex-start;
    width: auto;
    margin: 0;
}

.products-grid.list-view .product-actions {
    position: static;
    flex-direction: row;
    opacity: 1;
    transform: none;
    margin-bottom: 1rem;
    justify-content: flex-start;
}

/* Compare Bar */
.compare-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #2c3e50;
    color: white;
    padding: 1rem 0;
    box-shadow: 0 -0.25rem 0.5rem rgba(0, 0, 0, 0.1);
    z-index: 1000;
    transform: translateY(100%);
    transition: transform 0.3s ease;
}

.compare-bar.active {
    transform: translateY(0);
}

.compare-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.compare-info {
    font-weight: 500;
}

.compare-count {
    background: #007bff;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-weight: 600;
}

.compare-actions {
    display: flex;
    gap: 0.75rem;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .products-grid {
        grid-template-columns: repeat(3, 1fr); /* 3 products on medium screens */
        gap: 1.25rem;
    }
}

@media (max-width: 992px) {
    .products-grid {
        grid-template-columns: repeat(2, 1fr); /* 2 products on tablets */
        gap: 1rem;
    }
    
    .product-image {
        height: 180px;
    }
}

@media (max-width: 768px) {
    .search-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .filter-actions {
        grid-column: 1 / -1;
        justify-content: center;
    }
    
    .results-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .view-options {
        justify-content: center;
    }
    
    .products-grid {
        grid-template-columns: repeat(2, 1fr); /* Still 2 products on mobile landscape */
        gap: 1rem;
    }
    
    .product-image {
        height: 160px;
    }
    
    .products-grid.list-view .product-card {
        flex-direction: column;
    }
    
    .products-grid.list-view .product-image {
        width: 100%;
        height: 160px;
    }
    
    .product-actions {
        opacity: 1;
        transform: translateX(0);
        flex-direction: row;
        top: 0.5rem;
        right: 0.5rem;
        gap: 0.25rem;
    }
    
    .action-btn {
        width: 2rem;
        height: 2rem;
        font-size: 0.75rem;
    }
    
    .compare-content {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .compare-actions {
        justify-content: center;
    }
    
    .pagination {
        gap: 0.25rem;
    }
    
    .page-link {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }
}

@media (max-width: 480px) {
    .container {
        padding: 0 1rem;
    }
    
    .products-grid {
        grid-template-columns: 1fr; /* Single column on small phones */
        gap: 1rem;
    }
    
    .product-card {
        margin-bottom: 1rem;
        max-width: 320px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .product-image {
        height: 200px;
    }
    
    .search-form {
        flex-direction: column;
    }
    
    .search-input {
        margin-bottom: 0.5rem;
    }
    
    .filter-row {
        grid-template-columns: 1fr;
    }
    
    .no-results {
        padding: 2rem 1rem;
    }
    
    .no-results-icon {
        font-size: 3rem;
    }
}

/* Loading State */
.loading {
    text-align: center;
    padding: 2rem;
}

.loading-spinner {
    display: inline-block;
    width: 2rem;
    height: 2rem;
    border: 0.25rem solid #f3f3f3;
    border-top: 0.25rem solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Enhanced Pagination */
.pagination-wrapper {
    display: flex;
    justify-content: center;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e9ecef;
}

.pagination {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.page-link {
    padding: 0.75rem 1rem;
    background: white;
    border: 1px solid #dee2e6;
    color: #007bff;
    text-decoration: none;
    border-radius: 0.375rem;
    transition: all 0.3s ease;
    font-weight: 500;
}

.page-link:hover {
    background: #f8f9fa;
    border-color: #007bff;
    transform: translateY(-0.125rem);
}

.page-link.active {
    background: #007bff;
    border-color: #007bff;
    color: white;
}

.page-link.prev,
.page-link.next {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Quick View Modal Enhancement */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    animation: fadeIn 0.3s ease;
}

.modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Enhanced Filters */
.filter-section {
    background: white;
    border-radius: 0.75rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.filter-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    align-items: end;
}
        gap: 1rem;
        align-items: stretch;
    }
    
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1rem;
    }
    
    .products-grid.list-view .product-card {
        flex-direction: column;
    }
    
    .products-grid.list-view .product-image {
        width: 100%;
    }
    
    .compare-content {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
}
</style>

<script>
// Advanced search toggle
function toggleAdvancedSearch() {
    const advancedSearch = document.getElementById('advancedSearch');
    advancedSearch.classList.toggle('active');
}

// View toggle
document.querySelectorAll('.view-toggle').forEach(button => {
    button.addEventListener('click', function() {
        document.querySelectorAll('.view-toggle').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
        
        const view = this.dataset.view;
        const grid = document.getElementById('productsGrid');
        
        if (view === 'list') {
            grid.classList.add('list-view');
        } else {
            grid.classList.remove('list-view');
        }
    });
});

// Compare functionality
let compareProducts = JSON.parse(localStorage.getItem('compareProducts') || '[]');

function addToCompare(productId) {
    if (compareProducts.includes(productId)) {
        showToast('Product already in comparison', 'warning');
        return;
    }
    
    if (compareProducts.length >= 4) {
        showToast('You can compare maximum 4 products', 'warning');
        return;
    }
    
    compareProducts.push(productId);
    localStorage.setItem('compareProducts', JSON.stringify(compareProducts));
    updateCompareBar();
    showToast('Product added to comparison', 'success');
}

function removeFromCompare(productId) {
    compareProducts = compareProducts.filter(id => id !== productId);
    localStorage.setItem('compareProducts', JSON.stringify(compareProducts));
    updateCompareBar();
}

function clearComparison() {
    compareProducts = [];
    localStorage.setItem('compareProducts', JSON.stringify(compareProducts));
    updateCompareBar();
    showToast('Comparison cleared', 'info');
}

function updateCompareBar() {
    const compareBar = document.getElementById('compareBar');
    const countElement = compareBar.querySelector('.compare-count');
    
    countElement.textContent = compareProducts.length;
    
    if (compareProducts.length > 0) {
        compareBar.classList.add('active');
        compareBar.style.display = 'block';
    } else {
        compareBar.classList.remove('active');
        setTimeout(() => {
            if (compareProducts.length === 0) {
                compareBar.style.display = 'none';
            }
        }, 300);
    }
}

function viewComparison() {
    if (compareProducts.length < 2) {
        showToast('Please select at least 2 products to compare', 'warning');
        return;
    }
    
    const productIds = compareProducts.join(',');
    window.open(`${window.siteUrl}/compare.php?products=${productIds}`, '_blank');
}

// Initialize compare bar
document.addEventListener('DOMContentLoaded', function() {
    updateCompareBar();
});

// Quick view functionality
function quickView(productId) {
    window.open(`${window.siteUrl}/product.php?id=${productId}`, '_blank');
}
</script>

<?php include 'includes/footer.php'; ?>
