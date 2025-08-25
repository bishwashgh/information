-- Create user_addresses table for storing user shipping/billing addresses

CREATE TABLE IF NOT EXISTS `user_addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('home','work','billing','shipping','other') NOT NULL DEFAULT 'home',
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `company` varchar(100) DEFAULT NULL,
  `address_1` varchar(255) NOT NULL,
  `address_2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `type` (`type`),
  KEY `is_default` (`is_default`),
  CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add columns to users table for security settings (if not already present)
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0,
ADD COLUMN IF NOT EXISTS `email_notifications` tinyint(1) NOT NULL DEFAULT 1,
ADD COLUMN IF NOT EXISTS `login_alerts` tinyint(1) NOT NULL DEFAULT 1;

-- Create user_activity_log table for tracking security events
CREATE TABLE IF NOT EXISTS `user_activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `user_activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
