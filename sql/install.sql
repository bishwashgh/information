-- HORAASTORE Database Installation Script
-- Complete database setup for fresh installation

-- Create database
CREATE DATABASE IF NOT EXISTS `if0_39725628_onlinestore` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `if0_39725628_onlinestore`;

-- Enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Source the main schema
SOURCE schema.sql;

-- Insert initial data
SOURCE sample_data.sql;

-- Create admin user (password: admin123)
INSERT INTO `users` (
    `username`, 
    `email`, 
    `password`, 
    `first_name`, 
    `last_name`, 
    `role`, 
    `status`, 
    `is_admin`, 
    `is_verified`
) VALUES (
    'admin',
    'admin@horaastore.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: admin123
    'Admin',
    'User',
    'admin',
    'active',
    1,
    1
);

-- Show completion message
SELECT 'HORAASTORE database installation completed successfully!' as message;
