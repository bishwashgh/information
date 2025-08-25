<?php
require_once '../includes/config.php';

// SEO Configuration
$seoConfig = [
    'site_name' => 'HORAASTORE',
    'site_description' => 'Premium clothing and cafe products with fast delivery',
    'site_keywords' => 'ecommerce, clothing, jerseys, hoodies, cafe, coffee, pizza, online shopping',
    'site_url' => SITE_URL,
    'company_name' => 'HORAASTORE',
    'social_media' => [
        'facebook' => 'https://facebook.com/yourstore',
        'twitter' => 'https://twitter.com/yourstore',
        'instagram' => 'https://instagram.com/yourstore'
    ]
];

/**
 * Generate SEO meta tags for pages
 */
function generateSEOMeta($page = 'home', $product = null, $category = null) {
    global $seoConfig;
    
    $meta = [
        'title' => '',
        'description' => '',
        'keywords' => $seoConfig['site_keywords'],
        'canonical' => '',
        'og_image' => SITE_URL . '/assets/images/og-default.jpg'
    ];
    
    switch ($page) {
        case 'home':
            $meta['title'] = $seoConfig['site_name'] . ' - ' . $seoConfig['site_description'];
            $meta['description'] = 'Shop the latest in ' . $seoConfig['site_description'] . '. Free shipping on orders over $50.';
            $meta['canonical'] = $seoConfig['site_url'];
            break;
            
        case 'product':
            if ($product) {
                $meta['title'] = htmlspecialchars($product['name']) . ' - ' . $seoConfig['site_name'];
                $meta['description'] = 'Buy ' . htmlspecialchars($product['name']) . ' for $' . number_format($product['price'], 2) . '. ' . 
                                     substr(strip_tags($product['description']), 0, 150) . '...';
                $meta['keywords'] = $product['name'] . ', ' . $product['category'] . ', ' . $seoConfig['site_keywords'];
                $meta['canonical'] = $seoConfig['site_url'] . '/product.php?id=' . $product['id'];
                $meta['og_image'] = $product['image'] ?: $meta['og_image'];
            }
            break;
            
        case 'category':
            if ($category) {
                $meta['title'] = ucfirst($category) . ' Products - ' . $seoConfig['site_name'];
                $meta['description'] = 'Browse our collection of ' . $category . ' products. Quality guaranteed with fast shipping.';
                $meta['keywords'] = $category . ', products, ' . $seoConfig['site_keywords'];
                $meta['canonical'] = $seoConfig['site_url'] . '/products.php?category=' . $category;
            }
            break;
            
        case 'search':
            $query = $_GET['q'] ?? '';
            $meta['title'] = 'Search Results for "' . htmlspecialchars($query) . '" - ' . $seoConfig['site_name'];
            $meta['description'] = 'Find products matching "' . htmlspecialchars($query) . '" at ' . $seoConfig['site_name'];
            $meta['canonical'] = $seoConfig['site_url'] . '/search.php?q=' . urlencode($query);
            break;
    }
    
    return $meta;
}

/**
 * Output SEO meta tags
 */
function outputSEOMeta($meta) {
    global $seoConfig;
    ?>
    <!-- SEO Meta Tags -->
    <title><?php echo htmlspecialchars($meta['title']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta['description']); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($meta['keywords']); ?>">
    <meta name="robots" content="index, follow">
    <meta name="author" content="<?php echo htmlspecialchars($seoConfig['company_name']); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($meta['canonical']); ?>">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($meta['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta['description']); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($meta['og_image']); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($meta['canonical']); ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($seoConfig['site_name']); ?>">
    
    <!-- Twitter Card Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($meta['title']); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($meta['description']); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($meta['og_image']); ?>">
    
    <!-- Additional SEO Tags -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <meta name="theme-color" content="#3b82f6">
    <?php
}

/**
 * Generate structured data (JSON-LD) for products
 */
function generateProductStructuredData($product) {
    global $seoConfig;
    
    $structuredData = [
        "@context" => "https://schema.org/",
        "@type" => "Product",
        "name" => $product['name'],
        "description" => strip_tags($product['description']),
        "image" => $product['image'] ?: SITE_URL . '/assets/images/placeholder.jpg',
        "brand" => [
            "@type" => "Brand",
            "name" => $seoConfig['company_name']
        ],
        "offers" => [
            "@type" => "Offer",
            "url" => SITE_URL . '/product.php?id=' . $product['id'],
            "priceCurrency" => "USD",
            "price" => $product['price'],
            "availability" => $product['stock_quantity'] > 0 ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
            "seller" => [
                "@type" => "Organization",
                "name" => $seoConfig['company_name']
            ]
        ]
    ];
    
    // Add rating if available
    if (isset($product['avg_rating']) && $product['avg_rating'] > 0) {
        $structuredData["aggregateRating"] = [
            "@type" => "AggregateRating",
            "ratingValue" => $product['avg_rating'],
            "ratingCount" => $product['review_count'] ?? 1
        ];
    }
    
    return json_encode($structuredData, JSON_UNESCAPED_SLASHES);
}

/**
 * Generate structured data for organization
 */
function generateOrganizationStructuredData() {
    global $seoConfig;
    
    $structuredData = [
        "@context" => "https://schema.org",
        "@type" => "Organization",
        "name" => $seoConfig['company_name'],
        "url" => $seoConfig['site_url'],
        "logo" => $seoConfig['site_url'] . '/assets/images/logo.png',
        "sameAs" => array_values($seoConfig['social_media'])
    ];
    
    return json_encode($structuredData, JSON_UNESCAPED_SLASHES);
}

/**
 * Generate breadcrumb structured data
 */
function generateBreadcrumbStructuredData($breadcrumbs) {
    $items = [];
    $position = 1;
    
    foreach ($breadcrumbs as $crumb) {
        $items[] = [
            "@type" => "ListItem",
            "position" => $position++,
            "name" => $crumb['name'],
            "item" => $crumb['url']
        ];
    }
    
    $structuredData = [
        "@context" => "https://schema.org",
        "@type" => "BreadcrumbList",
        "itemListElement" => $items
    ];
    
    return json_encode($structuredData, JSON_UNESCAPED_SLASHES);
}

/**
 * Generate sitemap XML
 */
function generateSitemap() {
    $db = Database::getInstance()->getConnection();
    
    header('Content-Type: application/xml; charset=utf-8');
    
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    // Homepage
    echo '<url>' . "\n";
    echo '<loc>' . SITE_URL . '</loc>' . "\n";
    echo '<lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
    echo '<changefreq>daily</changefreq>' . "\n";
    echo '<priority>1.0</priority>' . "\n";
    echo '</url>' . "\n";
    
    // Static pages
    $staticPages = [
        '/products.php' => ['daily', '0.9'],
        '/about.php' => ['monthly', '0.5'],
        '/contact.php' => ['monthly', '0.5'],
        '/login.php' => ['monthly', '0.3'],
        '/register.php' => ['monthly', '0.3']
    ];
    
    foreach ($staticPages as $page => $settings) {
        echo '<url>' . "\n";
        echo '<loc>' . SITE_URL . $page . '</loc>' . "\n";
        echo '<lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
        echo '<changefreq>' . $settings[0] . '</changefreq>' . "\n";
        echo '<priority>' . $settings[1] . '</priority>' . "\n";
        echo '</url>' . "\n";
    }
    
    // Products
    $stmt = $db->query("SELECT id, updated_at FROM products WHERE status = 'active'");
    while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '<url>' . "\n";
        echo '<loc>' . SITE_URL . '/product.php?id=' . $product['id'] . '</loc>' . "\n";
        echo '<lastmod>' . date('Y-m-d', strtotime($product['updated_at'])) . '</lastmod>' . "\n";
        echo '<changefreq>weekly</changefreq>' . "\n";
        echo '<priority>0.8</priority>' . "\n";
        echo '</url>' . "\n";
    }
    
    // Categories
    $categories = ['clothing', 'cafe'];
    foreach ($categories as $category) {
        echo '<url>' . "\n";
        echo '<loc>' . SITE_URL . '/products.php?category=' . $category . '</loc>' . "\n";
        echo '<lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
        echo '<changefreq>weekly</changefreq>' . "\n";
        echo '<priority>0.7</priority>' . "\n";
        echo '</url>' . "\n";
    }
    
    echo '</urlset>';
}

/**
 * Track page analytics for performance monitoring
 */
function trackPageAnalytics($pageUrl, $loadTime = null) {
    $db = Database::getInstance()->getConnection();
    
    $loadTime = $loadTime ?: (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
    
    try {
        // Create analytics table if it doesn't exist
        $db->exec("
            CREATE TABLE IF NOT EXISTS page_analytics (
                id INT AUTO_INCREMENT PRIMARY KEY,
                page_url VARCHAR(255) NOT NULL,
                load_time DECIMAL(10,4) NOT NULL,
                user_agent TEXT,
                ip_address VARCHAR(45),
                referrer VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_page_url (page_url),
                INDEX idx_created_at (created_at)
            )
        ");
        
        $stmt = $db->prepare("
            INSERT INTO page_analytics (page_url, load_time, user_agent, ip_address, referrer)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $pageUrl,
            $loadTime,
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_REFERER'] ?? ''
        ]);
    } catch (Exception $e) {
        // Silently fail - analytics shouldn't break the site
        error_log("Analytics tracking error: " . $e->getMessage());
    }
}

/**
 * Clean old analytics data (keep last 30 days)
 */
function cleanOldAnalytics() {
    $db = Database::getInstance()->getConnection();
    
    try {
        $stmt = $db->prepare("DELETE FROM page_analytics WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Analytics cleanup error: " . $e->getMessage());
    }
}

/**
 * Get SEO analysis for a page
 */
function getSEOAnalysis($url, $content) {
    $analysis = [
        'score' => 0,
        'issues' => [],
        'recommendations' => []
    ];
    
    // Check title tag
    if (preg_match('/<title[^>]*>(.*?)<\/title>/i', $content, $matches)) {
        $title = trim($matches[1]);
        if (strlen($title) < 30) {
            $analysis['issues'][] = 'Title tag is too short (less than 30 characters)';
        } elseif (strlen($title) > 60) {
            $analysis['issues'][] = 'Title tag is too long (more than 60 characters)';
        } else {
            $analysis['score'] += 20;
        }
    } else {
        $analysis['issues'][] = 'Missing title tag';
    }
    
    // Check meta description
    if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']+)["\']/i', $content, $matches)) {
        $description = trim($matches[1]);
        if (strlen($description) < 120) {
            $analysis['issues'][] = 'Meta description is too short (less than 120 characters)';
        } elseif (strlen($description) > 160) {
            $analysis['issues'][] = 'Meta description is too long (more than 160 characters)';
        } else {
            $analysis['score'] += 20;
        }
    } else {
        $analysis['issues'][] = 'Missing meta description';
    }
    
    // Check H1 tags
    if (preg_match_all('/<h1[^>]*>(.*?)<\/h1>/i', $content, $matches)) {
        if (count($matches[0]) > 1) {
            $analysis['issues'][] = 'Multiple H1 tags found - use only one H1 per page';
        } else {
            $analysis['score'] += 15;
        }
    } else {
        $analysis['issues'][] = 'Missing H1 tag';
    }
    
    // Check for alt attributes on images
    if (preg_match_all('/<img[^>]*>/i', $content, $matches)) {
        $imagesWithoutAlt = 0;
        foreach ($matches[0] as $img) {
            if (!preg_match('/alt\s*=/i', $img)) {
                $imagesWithoutAlt++;
            }
        }
        if ($imagesWithoutAlt > 0) {
            $analysis['issues'][] = $imagesWithoutAlt . ' images missing alt attributes';
        } else {
            $analysis['score'] += 15;
        }
    }
    
    // Check for canonical URL
    if (preg_match('/<link\s+rel=["\']canonical["\']/i', $content)) {
        $analysis['score'] += 10;
    } else {
        $analysis['issues'][] = 'Missing canonical URL';
    }
    
    // Check for Open Graph tags
    if (preg_match('/<meta\s+property=["\']og:/i', $content)) {
        $analysis['score'] += 10;
    } else {
        $analysis['issues'][] = 'Missing Open Graph tags';
    }
    
    // Check for structured data
    if (preg_match('/<script[^>]*type=["\']application\/ld\+json["\']/i', $content)) {
        $analysis['score'] += 10;
    } else {
        $analysis['recommendations'][] = 'Add structured data (JSON-LD) for better search engine understanding';
    }
    
    // Generate recommendations based on issues
    if (count($analysis['issues']) > 0) {
        $analysis['recommendations'][] = 'Fix the identified issues to improve SEO score';
    }
    
    if ($analysis['score'] < 60) {
        $analysis['recommendations'][] = 'Consider hiring an SEO professional for optimization';
    }
    
    return $analysis;
}
?>
