<?php
// Admin header include
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Panel'; ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/admin/assets/css/admin.css">
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.siteUrl = '<?php echo SITE_URL; ?>';
        window.csrfToken = '<?php echo generateCSRFToken(); ?>';
    </script>
</head>
<body class="admin-body">

<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-store"></i>
                <span>Admin Panel</span>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <ul>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/" class="<?php echo $currentPage == 'index' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-section">Products</li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/products.php" class="<?php echo $currentPage == 'products' ? 'active' : ''; ?>">
                        <i class="fas fa-box"></i>
                        <span>All Products</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/categories.php" class="<?php echo $currentPage == 'categories' ? 'active' : ''; ?>">
                        <i class="fas fa-tags"></i>
                        <span>Categories</span>
                    </a>
                </li>
                
                <li class="nav-section">Orders</li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="<?php echo $currentPage == 'orders' ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-cart"></i>
                        <span>All Orders</span>
                    </a>
                </li>
                
                <li class="nav-section">Users</li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/users.php" class="<?php echo $currentPage == 'users' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Customers</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/reviews.php" class="<?php echo $currentPage == 'reviews' ? 'active' : ''; ?>">
                        <i class="fas fa-star"></i>
                        <span>Reviews</span>
                    </a>
                </li>
                
                <li class="nav-section">Analytics</li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/analytics.php" class="<?php echo $currentPage == 'analytics' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i>
                        <span>Advanced Analytics</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/performance.php" class="<?php echo $currentPage == 'performance' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Performance</span>
                    </a>
                </li>
                
                <li class="nav-section">Security & Payments</li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/security.php" class="<?php echo $currentPage == 'security' ? 'active' : ''; ?>">
                        <i class="fas fa-shield-alt"></i>
                        <span>Security</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/payments.php" class="<?php echo $currentPage == 'payments' ? 'active' : ''; ?>">
                        <i class="fas fa-credit-card"></i>
                        <span>Payments</span>
                    </a>
                </li>
                
                <li class="nav-section">Marketing</li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/email_marketing.php" class="<?php echo $currentPage == 'email_marketing' ? 'active' : ''; ?>">
                        <i class="fas fa-envelope"></i>
                        <span>Email Marketing</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/coupons.php" class="<?php echo $currentPage == 'coupons' ? 'active' : ''; ?>">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Coupons</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/newsletter.php" class="<?php echo $currentPage == 'newsletter' ? 'active' : ''; ?>">
                        <i class="fas fa-newspaper"></i>
                        <span>Newsletter</span>
                    </a>
                </li>
                
                <li class="nav-section">SEO & Optimization</li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/seo.php" class="<?php echo $currentPage == 'seo' ? 'active' : ''; ?>">
                        <i class="fas fa-search"></i>
                        <span>SEO Management</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/cache.php" class="<?php echo $currentPage == 'cache' ? 'active' : ''; ?>">
                        <i class="fas fa-memory"></i>
                        <span>Cache Management</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/pwa.php" class="<?php echo $currentPage == 'pwa' ? 'active' : ''; ?>">
                        <i class="fas fa-mobile-alt"></i>
                        <span>PWA Management</span>
                    </a>
                </li>
                
                <li class="nav-section">Settings</li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/settings.php" class="<?php echo $currentPage == 'settings' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i>
                        <span>Site Settings</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="sidebar-footer">
            <a href="<?php echo SITE_URL; ?>/" target="_blank" class="view-site-btn">
                <i class="fas fa-external-link-alt"></i>
                View Site
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="admin-main">
        <!-- Top Bar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h2><?php echo $pageTitle ?? 'Admin Panel'; ?></h2>
            </div>
            
            <div class="topbar-right">
                <div class="admin-notifications">
                    <button class="notification-btn" onclick="toggleNotifications()">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </button>
                    
                    <div class="notifications-dropdown" id="notificationsDropdown">
                        <div class="notification-header">
                            <h4>Notifications</h4>
                            <button class="mark-all-read">Mark all read</button>
                        </div>
                        <div class="notification-list">
                            <div class="notification-item unread">
                                <i class="fas fa-exclamation-triangle text-warning"></i>
                                <div class="notification-content">
                                    <p>Low stock: Premium Jersey</p>
                                    <span class="notification-time">2 minutes ago</span>
                                </div>
                            </div>
                            <div class="notification-item">
                                <i class="fas fa-shopping-cart text-success"></i>
                                <div class="notification-content">
                                    <p>New order #12345</p>
                                    <span class="notification-time">5 minutes ago</span>
                                </div>
                            </div>
                            <div class="notification-item">
                                <i class="fas fa-user text-info"></i>
                                <div class="notification-content">
                                    <p>New user registration</p>
                                    <span class="notification-time">10 minutes ago</span>
                                </div>
                            </div>
                        </div>
                        <div class="notification-footer">
                            <a href="#" class="view-all-notifications">View all notifications</a>
                        </div>
                    </div>
                </div>
                
                <div class="admin-user-menu">
                    <button class="user-menu-btn" onclick="toggleUserMenu()">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)); ?>
                        </div>
                        <span class="user-name"><?php echo sanitizeInput($_SESSION['first_name']); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    
                    <div class="user-dropdown" id="adminUserDropdown">
                        <a href="<?php echo SITE_URL; ?>/user/profile.php">
                            <i class="fas fa-user"></i>
                            Profile
                        </a>
                        <a href="<?php echo SITE_URL; ?>/admin/settings.php">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo SITE_URL; ?>/logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Content Area -->
        <div class="admin-content">
