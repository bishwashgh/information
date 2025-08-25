    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <!-- Company Info -->
                <div class="footer-section">
                    <h3><?php echo SITE_NAME; ?></h3>
                    <p>Your trusted e-commerce destination for premium clothing and delicious cafe items. Quality products, exceptional service, and fast delivery.</p>
                    <div class="social-links mt-4">
                        <a href="#" class="social-link" title="Facebook"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="social-link" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link" title="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="<?php echo SITE_URL; ?>/">Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/products.php">All Products</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/products.php?category=clothing">Clothing</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/products.php?category=cafe">Cafe</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/contact.php">Contact Us</a></li>
                    </ul>
                </div>
                
                <!-- Customer Service -->
                <div class="footer-section">
                    <h3>Customer Service</h3>
                    <ul>
                        <?php if (isLoggedIn()): ?>
                        <li><a href="<?php echo SITE_URL; ?>/user/dashboard.php">My Account</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/user/orders.php">Order History</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/user/wishlist.php">My Wishlist</a></li>
                        <?php else: ?>
                        <li><a href="<?php echo SITE_URL; ?>/user/login.php">Login</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/user/register.php">Register</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo SITE_URL; ?>/order-tracking.php">Track Order</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/faq.php">FAQ</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/returns.php">Returns & Refunds</a></li>
                    </ul>
                </div>
                
                <!-- Newsletter -->
                <div class="footer-section">
                    <h3>Newsletter</h3>
                    <p>Subscribe to get updates on new products and exclusive offers!</p>
                    <form class="newsletter-form" id="newsletterForm">
                        <input type="email" name="email" class="newsletter-input" placeholder="Enter your email" required>
                        <button type="submit" class="btn btn-primary">Subscribe</button>
                    </form>
                    
                    <div class="contact-info mt-4">
                        <p><i class="fas fa-map-marker-alt"></i> Kathmandu, Nepal</p>
                        <p><i class="fas fa-phone"></i> +977-123-456-7890</p>
                        <p><i class="fas fa-envelope"></i> <?php echo ADMIN_EMAIL; ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-4">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                    <div class="footer-links">
                        <a href="<?php echo SITE_URL; ?>/privacy-policy.php">Privacy Policy</a>
                        <span>|</span>
                        <a href="<?php echo SITE_URL; ?>/terms.php">Terms & Conditions</a>
                        <span>|</span>
                        <a href="<?php echo SITE_URL; ?>/sitemap.php">Sitemap</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Toast Notifications -->
    <div id="toastContainer" class="toast-container"></div>
    
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay d-none">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading...</p>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    
    <!-- Additional JavaScript -->
    <?php if (isset($additionalJS)) echo $additionalJS; ?>
    
    <!-- CSRF Token for AJAX -->
    <script>
        window.csrfToken = '<?php echo generateCSRFToken(); ?>';
        window.siteUrl = '<?php echo SITE_URL; ?>';
    </script>
    
    <style>
        /* Additional Footer Styles */
        .social-links {
            display: flex;
            gap: var(--spacing-3);
        }
        
        .social-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: var(--gray-700);
            color: var(--gray-300);
            border-radius: 50%;
            transition: var(--transition-fast);
        }
        
        .social-link:hover {
            background-color: var(--primary-color);
            color: var(--white);
            text-decoration: none;
            transform: translateY(-2px);
        }
        
        .footer-links {
            display: flex;
            align-items: center;
            gap: var(--spacing-3);
        }
        
        .footer-links a {
            color: var(--gray-300);
            font-size: var(--font-size-sm);
        }
        
        .footer-links span {
            color: var(--gray-500);
        }
        
        /* Toast Notifications */
        .toast-container {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            max-width: 400px;
            pointer-events: none;
        }
        
        .toast {
            background: var(--white);
            border-left: 4px solid var(--primary-color);
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            margin-bottom: 16px;
            padding: 20px;
            transform: translateY(100px) scale(0.95);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            pointer-events: auto;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .toast::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--primary-color), transparent);
            animation: toastProgress 5s linear forwards;
        }
        
        .toast.show {
            transform: translateY(0) scale(1);
            opacity: 1;
            animation: toastSlideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }
        
        .toast.hiding {
            transform: translateY(100px) scale(0.95);
            opacity: 0;
            animation: toastSlideOut 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }
        
        .toast.success {
            border-left-color: var(--success-color);
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.05), rgba(255, 255, 255, 0.95));
        }
        
        .toast.success::before {
            background: linear-gradient(90deg, var(--success-color), transparent);
        }
        
        .toast.error {
            border-left-color: var(--danger-color);
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.05), rgba(255, 255, 255, 0.95));
        }
        
        .toast.error::before {
            background: linear-gradient(90deg, var(--danger-color), transparent);
        }
        
        .toast.warning {
            border-left-color: var(--warning-color);
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.05), rgba(255, 255, 255, 0.95));
        }
        
        .toast.warning::before {
            background: linear-gradient(90deg, var(--warning-color), transparent);
        }
        
        .toast.info {
            border-left-color: var(--primary-color);
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(255, 255, 255, 0.95));
        }
        
        @keyframes toastSlideIn {
            0% {
                transform: translateY(100px) scale(0.95) rotateX(-15deg);
                opacity: 0;
            }
            50% {
                transform: translateY(-5px) scale(1.02) rotateX(0deg);
                opacity: 0.8;
            }
            100% {
                transform: translateY(0) scale(1) rotateX(0deg);
                opacity: 1;
            }
        }
        
        @keyframes toastSlideOut {
            0% {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
            100% {
                transform: translateY(100px) scale(0.9);
                opacity: 0;
            }
        }
        
        @keyframes toastProgress {
            0% {
                width: 100%;
                opacity: 1;
            }
            90% {
                width: 10%;
                opacity: 0.8;
            }
            100% {
                width: 0%;
                opacity: 0;
            }
        }
        
        .toast-header {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            gap: 10px;
        }
        
        .toast-icon {
            font-size: 18px;
            line-height: 1;
        }
        
        .toast.success .toast-icon {
            color: var(--success-color);
        }
        
        .toast.error .toast-icon {
            color: var(--danger-color);
        }
        
        .toast.warning .toast-icon {
            color: var(--warning-color);
        }
        
        .toast.info .toast-icon {
            color: var(--primary-color);
        }
        
        .toast-title {
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
            flex: 1;
            font-size: 14px;
        }
        
        .toast-close {
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            font-size: 14px;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s ease;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .toast-close:hover {
            background: rgba(0, 0, 0, 0.1);
            color: var(--gray-600);
        }
        
        .toast-body {
            color: var(--gray-700);
            font-size: 14px;
            line-height: 1.5;
        }
        
        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(4px);
        }
        
        .loading-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        .loading-spinner {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            min-width: 240px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            transform: scale(0.8) translateY(20px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .loading-overlay.show .loading-spinner {
            transform: scale(1) translateY(0);
        }
        
        .loading-spinner .spinner-icon {
            width: 48px;
            height: 48px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: loadingRotate 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        .loading-spinner .spinner-dots {
            display: flex;
            justify-content: center;
            gap: 4px;
            margin-bottom: 20px;
        }
        
        .loading-spinner .spinner-dot {
            width: 8px;
            height: 8px;
            background: var(--primary-color);
            border-radius: 50%;
            animation: loadingPulse 1.4s ease-in-out infinite both;
        }
        
        .loading-spinner .spinner-dot:nth-child(1) { animation-delay: -0.32s; }
        .loading-spinner .spinner-dot:nth-child(2) { animation-delay: -0.16s; }
        .loading-spinner .spinner-dot:nth-child(3) { animation-delay: 0s; }
        
        .loading-spinner p {
            color: var(--gray-700);
            margin: 0;
            font-weight: 500;
            font-size: 16px;
        }
        
        @keyframes loadingRotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes loadingPulse {
            0%, 80%, 100% {
                transform: scale(0);
                opacity: 0.5;
            }
            40% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .toast-container {
                bottom: 20px;
                right: 15px;
                left: 15px;
                max-width: none;
                transform: none;
            }
            
            .toast {
                transform: translateY(100px) scale(0.95);
                margin-bottom: 12px;
                padding: 16px;
            }
            
            .toast.show {
                transform: translateY(0) scale(1);
                animation: toastSlideInMobile 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
            }
            
            .toast.hiding {
                transform: translateY(100px) scale(0.95);
                animation: toastSlideOutMobile 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards;
            }
            
            .loading-spinner {
                min-width: 200px;
                padding: 30px 20px;
            }
            
            .loading-spinner p {
                font-size: 14px;
            }
        }
        
        @keyframes toastSlideInMobile {
            0% {
                transform: translateY(100px) scale(0.95);
                opacity: 0;
            }
            50% {
                transform: translateY(-5px) scale(1.02);
                opacity: 0.8;
            }
            100% {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }
        
        @keyframes toastSlideOutMobile {
            0% {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
            100% {
                transform: translateY(100px) scale(0.9);
                opacity: 0;
            }
        }
        
        .mobile-nav-list {
            list-style: none;
            padding: var(--spacing-4) 0;
        }
        
        .mobile-nav-link {
            display: block;
            padding: var(--spacing-3) 0;
            color: var(--gray-700);
            border-bottom: 1px solid var(--gray-100);
        }
        
        .mobile-nav-link:hover {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .mobile-menu-toggle {
            background: none;
            border: none;
            color: var(--gray-700);
            font-size: var(--font-size-xl);
            cursor: pointer;
            padding: var(--spacing-2);
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block !important;
            }
            
            .nav {
                display: none !important;
            }
            
            .footer-bottom .d-flex {
                flex-direction: column;
                text-align: center;
            }
            
            .footer-links {
                justify-content: center;
            }
            
            .toast-container {
                left: 10px;
                right: 10px;
                max-width: none;
            }
            
            .toast {
                transform: translateY(-100px);
            }
            
            .toast.show {
                transform: translateY(0);
            }
        }
    </style>
    
    <!-- PWA Scripts -->
    <script src="<?php echo SITE_URL; ?>/assets/js/pwa.js"></script>
    <script>
        // Initialize PWA
        if (typeof PWAManager !== 'undefined') {
            const pwa = new PWAManager();
        }
    </script>
</body>
</html>
