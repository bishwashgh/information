-- Create OTP table for email verification
CREATE TABLE IF NOT EXISTS `email_otps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `purpose` varchar(50) NOT NULL DEFAULT 'registration',
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `email_purpose` (`email`, `purpose`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add email verification columns to users table if they don't exist
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `email_verified` TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS `email_verified_at` DATETIME NULL;

-- Clean up old OTPs (older than 1 hour)
DELETE FROM `email_otps` WHERE `expires_at` < NOW() OR `used` = 1;
