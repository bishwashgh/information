-- HORAASTORE Sample Data
-- Test data for development and testing

-- Insert sample categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `is_active`) VALUES
(1, 'Clothing', 'clothing', 'Fashion and apparel items', 1),
(2, 'Jerseys', 'jerseys', 'Sports jerseys and team wear', 1),
(3, 'Hoodies', 'hoodies', 'Comfortable hoodies and sweatshirts', 1),
(4, 'Caps', 'caps', 'Stylish caps and hats', 1),
(5, 'Cafe Items', 'cafe-items', 'Food and beverage products', 1),
(6, 'Pizza', 'pizza', 'Delicious pizza varieties', 1),
(7, 'Coffee', 'coffee', 'Premium coffee and beverages', 1);

-- Insert sample products
INSERT INTO `products` (`id`, `name`, `slug`, `description`, `price`, `stock_quantity`, `category_id`, `status`, `featured`) VALUES
(1, 'Manchester United Home Jersey', 'manchester-united-home-jersey', 'Official Manchester United home jersey for the current season', 79.99, 50, 2, 'active', 1),
(2, 'Nike Classic Hoodie', 'nike-classic-hoodie', 'Comfortable Nike hoodie perfect for casual wear', 59.99, 30, 3, 'active', 1),
(3, 'Baseball Cap - Black', 'baseball-cap-black', 'Classic black baseball cap with adjustable strap', 24.99, 75, 4, 'active', 0),
(4, 'Margherita Pizza', 'margherita-pizza', 'Classic Italian pizza with fresh tomatoes, mozzarella, and basil', 12.99, 100, 6, 'active', 1),
(5, 'Premium Coffee Blend', 'premium-coffee-blend', 'Rich and aromatic coffee blend perfect for any time of day', 18.99, 25, 7, 'active', 1),
(6, 'Barcelona Away Jersey', 'barcelona-away-jersey', 'Official Barcelona away jersey with latest design', 84.99, 40, 2, 'active', 0),
(7, 'Pepperoni Pizza', 'pepperoni-pizza', 'Delicious pizza topped with premium pepperoni slices', 15.99, 80, 6, 'active', 1),
(8, 'Adidas Performance Hoodie', 'adidas-performance-hoodie', 'High-quality Adidas hoodie for sports and casual wear', 69.99, 20, 3, 'active', 0);

-- Insert sample product images
INSERT INTO `product_images` (`product_id`, `image_url`, `alt_text`, `is_primary`) VALUES
(1, 'https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=500', 'Manchester United Jersey', 1),
(2, 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=500', 'Nike Hoodie', 1),
(3, 'https://images.unsplash.com/photo-1521369909029-2afed882baee?w=500', 'Black Baseball Cap', 1),
(4, 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=500', 'Margherita Pizza', 1),
(5, 'https://images.unsplash.com/photo-1447933601403-0c6688de566e?w=500', 'Coffee Blend', 1),
(6, 'https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=500', 'Barcelona Jersey', 1),
(7, 'https://images.unsplash.com/photo-1565299624946-b28f40a0ca4b?w=500', 'Pepperoni Pizza', 1),
(8, 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=500', 'Adidas Hoodie', 1);

-- Insert sample coupons
INSERT INTO `coupons` (`code`, `name`, `type`, `value`, `minimum_amount`, `usage_limit`, `is_active`) VALUES
('WELCOME10', 'Welcome Discount', 'percentage', 10.00, 50.00, 100, 1),
('SAVE20', 'Save 20 on Orders Over 100', 'fixed_amount', 20.00, 100.00, 50, 1),
('NEWUSER15', 'New User Discount', 'percentage', 15.00, 30.00, 200, 1);

-- Insert sample settings
INSERT INTO `settings` (`key_name`, `value`, `description`, `type`) VALUES
('site_name', 'HORAASTORE', 'Website name', 'text'),
('site_description', 'Premium clothing and cafe products with fast delivery', 'Website description', 'text'),
('currency', 'NPR', 'Default currency', 'text'),
('tax_rate', '13', 'Tax rate percentage', 'number'),
('free_shipping_minimum', '2000', 'Minimum order for free shipping', 'number'),
('enable_registration', 'true', 'Allow new user registration', 'boolean');

-- Insert sample payment methods
INSERT INTO `payment_methods` (`name`, `code`, `description`, `is_active`) VALUES
('Cash on Delivery', 'cod', 'Pay when you receive your order', 1),
('eSewa', 'esewa', 'Pay securely with eSewa digital wallet', 1),
('Khalti', 'khalti', 'Pay with Khalti digital payment', 1),
('Bank Transfer', 'bank_transfer', 'Direct bank transfer payment', 1);

-- Insert sample shipping methods
INSERT INTO `shipping_methods` (`name`, `code`, `description`, `cost`, `free_shipping_minimum`, `is_active`) VALUES
('Standard Delivery', 'standard', 'Delivery within 3-5 business days', 100.00, 2000.00, 1),
('Express Delivery', 'express', 'Next day delivery', 200.00, 5000.00, 1),
('Store Pickup', 'pickup', 'Pick up from our store location', 0.00, 0.00, 1);

-- Insert sample pages
INSERT INTO `pages` (`title`, `slug`, `content`, `status`) VALUES
('About Us', 'about-us', '<h1>About HORAASTORE</h1><p>Welcome to HORAASTORE, your premier destination for quality clothing and delicious cafe items.</p>', 'published'),
('Privacy Policy', 'privacy-policy', '<h1>Privacy Policy</h1><p>Your privacy is important to us...</p>', 'published'),
('Terms of Service', 'terms-of-service', '<h1>Terms of Service</h1><p>By using our service, you agree to these terms...</p>', 'published'),
('Shipping Information', 'shipping-info', '<h1>Shipping Information</h1><p>We offer fast and reliable shipping...</p>', 'published');
