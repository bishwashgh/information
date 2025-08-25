-- HORAASTORE Database Schema
-- E-commerce Platform Database Structure
-- Created: August 25, 2025

-- Create database
CREATE DATABASE IF NOT EXISTS `if0_39725628_onlinestore` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `if0_39725628_onlinestore`;

-- ==============================================
-- USERS AND AUTHENTICATION TABLES
-- ==============================================

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `username` VARCHAR(50) UNIQUE NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `phone` VARCHAR(20),
    `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    `role` ENUM('customer', 'vendor', 'admin') DEFAULT 'customer',
    `newsletter_subscribed` BOOLEAN DEFAULT FALSE,
    `is_admin` BOOLEAN DEFAULT FALSE,
    `is_verified` BOOLEAN DEFAULT FALSE,
    `last_login` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- User addresses table
CREATE TABLE IF NOT EXISTS `user_addresses` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `type` ENUM('billing', 'shipping') DEFAULT 'shipping',
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `company` VARCHAR(100),
    `address_line_1` VARCHAR(255) NOT NULL,
    `address_line_2` VARCHAR(255),
    `city` VARCHAR(100) NOT NULL,
    `state` VARCHAR(100),
    `postal_code` VARCHAR(20),
    `country` VARCHAR(100) DEFAULT 'Nepal',
    `phone` VARCHAR(20),
    `is_default` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- User sessions table for remember me functionality
CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- ==============================================
-- PRODUCT CATALOG TABLES
-- ==============================================

-- Categories table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) UNIQUE NOT NULL,
    `description` TEXT,
    `image` VARCHAR(255),
    `parent_id` INT DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
);

-- Products table
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(200) UNIQUE NOT NULL,
    `description` TEXT,
    `short_description` TEXT,
    `sku` VARCHAR(100) UNIQUE,
    `price` DECIMAL(10,2) NOT NULL,
    `sale_price` DECIMAL(10,2),
    `cost_price` DECIMAL(10,2),
    `stock_quantity` INT DEFAULT 0,
    `manage_stock` BOOLEAN DEFAULT TRUE,
    `low_stock_threshold` INT DEFAULT 5,
    `weight` DECIMAL(8,2),
    `dimensions` VARCHAR(100),
    `category_id` INT,
    `brand` VARCHAR(100),
    `status` ENUM('active', 'inactive', 'draft') DEFAULT 'active',
    `featured` BOOLEAN DEFAULT FALSE,
    `meta_title` VARCHAR(200),
    `meta_description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
);

-- Product images table
CREATE TABLE IF NOT EXISTS `product_images` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `product_id` INT NOT NULL,
    `image_url` VARCHAR(255) NOT NULL,
    `alt_text` VARCHAR(200),
    `sort_order` INT DEFAULT 0,
    `is_primary` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
);

-- Product attributes table (for variants like size, color)
CREATE TABLE IF NOT EXISTS `product_attributes` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `product_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `value` VARCHAR(200) NOT NULL,
    `price_modifier` DECIMAL(10,2) DEFAULT 0,
    `stock_quantity` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
);

-- ==============================================
-- SHOPPING CART AND WISHLIST TABLES
-- ==============================================

-- Shopping cart table
CREATE TABLE IF NOT EXISTS `cart` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT,
    `session_id` VARCHAR(255),
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `attributes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
);

-- Wishlist table
CREATE TABLE IF NOT EXISTS `wishlist` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_wishlist` (`user_id`, `product_id`)
);

-- ==============================================
-- ORDER MANAGEMENT TABLES
-- ==============================================

-- Orders table
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT,
    `order_number` VARCHAR(50) UNIQUE NOT NULL,
    `status` ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded') DEFAULT 'pending',
    `total_amount` DECIMAL(10,2) NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL,
    `tax_amount` DECIMAL(10,2) DEFAULT 0,
    `shipping_amount` DECIMAL(10,2) DEFAULT 0,
    `discount_amount` DECIMAL(10,2) DEFAULT 0,
    `payment_method` VARCHAR(50),
    `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    `shipping_address` TEXT,
    `billing_address` TEXT,
    `notes` TEXT,
    `tracking_number` VARCHAR(100),
    `shipped_at` TIMESTAMP NULL,
    `delivered_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- Order items table
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `product_name` VARCHAR(200) NOT NULL,
    `product_sku` VARCHAR(100),
    `quantity` INT NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `total` DECIMAL(10,2) NOT NULL,
    `attributes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
);

-- ==============================================
-- MARKETING AND PROMOTIONS TABLES
-- ==============================================

-- Coupons table
CREATE TABLE IF NOT EXISTS `coupons` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `code` VARCHAR(50) UNIQUE NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `type` ENUM('percentage', 'fixed_amount') NOT NULL,
    `value` DECIMAL(10,2) NOT NULL,
    `minimum_amount` DECIMAL(10,2) DEFAULT 0,
    `maximum_discount` DECIMAL(10,2),
    `usage_limit` INT,
    `used_count` INT DEFAULT 0,
    `start_date` DATE,
    `end_date` DATE,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Newsletter subscriptions table
CREATE TABLE IF NOT EXISTS `newsletter_subscriptions` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `status` ENUM('active', 'unsubscribed') DEFAULT 'active',
    `subscribed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `unsubscribed_at` TIMESTAMP NULL
);

-- ==============================================
-- CONTENT MANAGEMENT TABLES
-- ==============================================

-- Pages table for CMS
CREATE TABLE IF NOT EXISTS `pages` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `title` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(200) UNIQUE NOT NULL,
    `content` TEXT,
    `meta_title` VARCHAR(200),
    `meta_description` TEXT,
    `status` ENUM('published', 'draft') DEFAULT 'draft',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Reviews table
CREATE TABLE IF NOT EXISTS `reviews` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `product_id` INT NOT NULL,
    `user_id` INT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `rating` INT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
    `title` VARCHAR(200),
    `content` TEXT,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- ==============================================
-- SETTINGS AND CONFIGURATION TABLES
-- ==============================================

-- Settings table
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `key_name` VARCHAR(100) UNIQUE NOT NULL,
    `value` TEXT,
    `description` VARCHAR(255),
    `type` ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Payment methods table
CREATE TABLE IF NOT EXISTS `payment_methods` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(50) UNIQUE NOT NULL,
    `description` TEXT,
    `is_active` BOOLEAN DEFAULT TRUE,
    `settings` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Shipping methods table
CREATE TABLE IF NOT EXISTS `shipping_methods` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(50) UNIQUE NOT NULL,
    `description` TEXT,
    `cost` DECIMAL(10,2) NOT NULL,
    `free_shipping_minimum` DECIMAL(10,2),
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
