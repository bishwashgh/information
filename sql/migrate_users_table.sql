-- HORAASTORE Database Migration
-- Fix Users Table Structure
-- This migration adds missing columns to the existing users table

-- Add missing columns to users table if they don't exist
-- Status column
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'status'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE users ADD COLUMN status ENUM(\'active\', \'inactive\', \'suspended\') DEFAULT \'active\'',
    'SELECT "Column status already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Role column
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'role'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE users ADD COLUMN role ENUM(\'customer\', \'vendor\', \'admin\') DEFAULT \'customer\'',
    'SELECT "Column role already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Newsletter subscribed column
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'newsletter_subscribed'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE users ADD COLUMN newsletter_subscribed BOOLEAN DEFAULT FALSE',
    'SELECT "Column newsletter_subscribed already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Last login column
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'last_login'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL',
    'SELECT "Column last_login already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Make username nullable (modify existing column)
ALTER TABLE users MODIFY COLUMN username VARCHAR(50) UNIQUE NULL;

-- Show the updated table structure
DESCRIBE users;
