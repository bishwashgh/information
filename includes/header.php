<!DOCTYPE html>
<html lang="<?php echo DEFAULT_LANGUAGE; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Meta Tags -->
    <meta name="description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Premium e-commerce store for clothing and cafe items'; ?>">
    <meta name="keywords" content="<?php echo isset($pageKeywords) ? $pageKeywords : 'ecommerce, clothing, jersey, cap, hoodie, cafe, pizza, coffee'; ?>">
    <meta name="author" content="<?php echo SITE_NAME; ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?>">
    <meta property="og:description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Premium e-commerce store for clothing and cafe items'; ?>">
    <meta property="og:image" content="<?php echo SITE_URL; ?>/assets/images/og-image.jpg">
    <meta property="og:url" content="<?php echo SITE_URL . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo SITE_NAME; ?>">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?>">
    <meta name="twitter:description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Premium e-commerce store for clothing and cafe items'; ?>">
    <meta name="twitter:image" content="<?php echo SITE_URL; ?>/assets/images/og-image.jpg">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo SITE_URL; ?>/assets/images/apple-touch-icon.png">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="<?php echo SITE_URL; ?>/manifest.json">
    <meta name="theme-color" content="#ffffff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?php echo SITE_NAME; ?>">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-config" content="<?php echo SITE_URL; ?>/browserconfig.xml">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Nunito:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/pwa.css">
    
    <!-- Logo Styling -->
    <style>
    .logo {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
        color: inherit;
        font-weight: 700;
        font-size: 1.5rem;
        transition: all 0.3s ease;
    }
    
    .logo:hover {
        text-decoration: none;
        color: inherit;
        opacity: 0.8;
    }
    
    .logo-img {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        object-fit: cover;
        display: block;
    }
    
    .logo-text {
        font-family: 'Nunito', sans-serif;
        font-weight: 700;
        color: inherit;
    }
    
    /* Responsive logo sizing */
    @media (max-width: 768px) {
        .logo {
            font-size: 1.25rem;
        }
        
        .logo-img {
            width: 35px;
            height: 35px;
        }
    }
    
    @media (max-width: 480px) {
        .logo {
            font-size: 1.1rem;
        }
        
        .logo-img {
            width: 30px;
            height: 30px;
        }
    }
    </style>
    
    <!-- Additional CSS -->
    <?php if (isset($additionalCSS)) echo $additionalCSS; ?>
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <!-- Header Main -->
        <div class="header-main">
            <div class="container">
                <div class="header-content">
                    <!-- Logo -->
                    <a href="<?php echo SITE_URL; ?>" class="logo">
                        <img src="https://yt3.googleusercontent.com/v-XIMeNgwk-OHgSInq4IadjeIheljWckCEc3O4zPqS9QL58xJViFhyI8AWE-BG6tF0tDO9cS9g=s900-c-k-c0x00ffffff-no-rj" alt="<?php echo SITE_NAME; ?> Logo" class="logo-img">
                        <span class="logo-text"><?php echo SITE_NAME; ?></span>
                    </a>
                    
                    <!-- Navigation -->
                    <nav class="nav">
                        <ul class="nav-list">
                            <li><a href="<?php echo SITE_URL; ?>/" class="nav-link">Home</a></li>
                            <?php 
                            $categories = getCategories();
                            foreach ($categories as $category): 
                            ?>
                                <li><a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $category['slug']; ?>" class="nav-link"><?php echo $category['name']; ?></a></li>
                            <?php endforeach; ?>
                            <li><a href="<?php echo SITE_URL; ?>/about.php" class="nav-link">About</a></li>
                        </ul>
                    </nav>
                    
                    <!-- Search Bar -->
                    <div class="search-bar">
                        <form action="<?php echo SITE_URL; ?>/search.php" method="GET" class="search-form">
                            <input type="text" name="q" class="search-input" placeholder="Search products..." value="<?php echo isset($_GET['q']) ? sanitizeInput($_GET['q']) : ''; ?>">
                            <button type="submit" class="search-btn">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Header Actions -->
                    <div class="header-actions">
                        <!-- User Account -->
                        <?php if (isLoggedIn()): ?>
                        <div class="user-menu">
                            <button class="user-toggle" onclick="toggleUserMenu()">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)); ?>
                                </div>
                                <span class="user-name"><?php echo sanitizeInput($_SESSION['first_name']); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            
                            <div class="user-dropdown" id="userDropdown">
                                <div class="user-info">
                                    <strong><?php echo sanitizeInput($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></strong>
                                    <span><?php echo sanitizeInput($_SESSION['email']); ?></span>
                                </div>
                                
                                <div class="dropdown-divider"></div>
                                
                                <a href="<?php echo SITE_URL; ?>/user/dashboard.php" class="dropdown-item">
                                    <i class="fas fa-tachometer-alt"></i>
                                    Dashboard
                                </a>
                                <a href="<?php echo SITE_URL; ?>/user/orders.php" class="dropdown-item">
                                    <i class="fas fa-receipt"></i>
                                    My Orders
                                </a>
                                <a href="<?php echo SITE_URL; ?>/user/profile.php" class="dropdown-item">
                                    <i class="fas fa-user"></i>
                                    Profile Settings
                                </a>
                                <a href="<?php echo SITE_URL; ?>/user/addresses.php" class="dropdown-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Addresses
                                </a>
                                <a href="<?php echo SITE_URL; ?>/user/wishlist.php" class="dropdown-item">
                                    <i class="fas fa-heart"></i>
                                    Wishlist
                                </a>
                                
                                <div class="dropdown-divider"></div>
                                
                                <a href="<?php echo SITE_URL; ?>/logout.php" class="dropdown-item logout">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Logout
                                </a>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="auth-links">
                            <a href="<?php echo SITE_URL; ?>/login.php" class="auth-link">
                                <i class="fas fa-sign-in-alt"></i>
                                Login
                            </a>
                            <a href="<?php echo SITE_URL; ?>/register.php" class="auth-link register">
                                <i class="fas fa-user-plus"></i>
                                Register
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Wishlist -->
                        <?php if (isLoggedIn()): ?>
                        <a href="<?php echo SITE_URL; ?>/user/wishlist.php" class="cart-icon" title="Wishlist">
                            <i class="fas fa-heart"></i>
                        </a>
                        <?php endif; ?>
                        
                        <!-- Cart -->
                        <a href="<?php echo SITE_URL; ?>/cart.php" class="cart-icon" title="Shopping Cart">
                            <i class="fas fa-shopping-cart"></i>
                            <?php 
                            $cartCount = getCartCount();
                            if ($cartCount > 0): 
                            ?>
                            <span class="cart-count"><?php echo $cartCount; ?></span>
                            <?php endif; ?>
                        </a>
                        
                        <!-- Mobile Menu Toggle -->
                        <button class="mobile-menu-toggle d-none" id="mobileMenuToggle">
                            <i class="fas fa-bars"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Mobile Navigation (Hidden by default) -->
    <div class="mobile-nav d-none" id="mobileNav">
        <div class="container">
            <ul class="mobile-nav-list">
                <li><a href="<?php echo SITE_URL; ?>/" class="mobile-nav-link">Home</a></li>
                <?php foreach ($categories as $category): ?>
                    <li><a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $category['slug']; ?>" class="mobile-nav-link"><?php echo $category['name']; ?></a></li>
                <?php endforeach; ?>
                <li><a href="<?php echo SITE_URL; ?>/about.php" class="mobile-nav-link">About</a></li>
            </ul>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay d-none">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <span>Loading...</span>
        </div>
    </div>
    
    <!-- PWA Install Button -->
    <button id="pwaInstallBtn" class="pwa-install-button hidden">
        <i class="fas fa-mobile-alt"></i>
        Install App
    </button>
    
    <!-- Offline Indicator -->
    <div id="offlineIndicator" class="offline-indicator hidden">
        <div class="offline-content">
            <i class="fas fa-wifi" style="opacity: 0.5;"></i>
            You're offline. Some features may be limited.
        </div>
    </div>
    
    <!-- Main Content -->
    <main class="main-content">
