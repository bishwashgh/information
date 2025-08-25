# HORAASTORE Database SQL Scripts

This folder contains all SQL scripts for the HORAASTORE e-commerce platform.

## Files Overview

### 📁 Core Database Files

- **`schema.sql`** - Complete database schema with all tables
- **`install.sql`** - Full installation script for fresh setup
- **`migrate_users_table.sql`** - Migration script to fix existing users table
- **`sample_data.sql`** - Sample data for testing and development

## 🚀 Quick Setup

### Option 1: Fresh Installation
If you're setting up the database for the first time:

```bash
# Navigate to your MySQL/phpMyAdmin
# Run the install.sql file
mysql -u root -p < install.sql
```

Or in phpMyAdmin:
1. Go to SQL tab
2. Click "Import"
3. Select `install.sql`
4. Execute

### Option 2: Fix Existing Database
If you have an existing database with missing columns:

```bash
# Run the migration script
mysql -u root -p if0_39725628_onlinestore < migrate_users_table.sql
```

Or in phpMyAdmin:
1. Select your database
2. Go to SQL tab
3. Copy and paste content from `migrate_users_table.sql`
4. Execute

## 📋 Database Structure

### Core Tables

#### Users & Authentication
- `users` - User accounts and profiles
- `user_addresses` - User shipping/billing addresses  
- `user_sessions` - Remember me sessions

#### Product Catalog
- `categories` - Product categories
- `products` - Product information
- `product_images` - Product images
- `product_attributes` - Product variants (size, color, etc.)

#### Shopping & Orders
- `cart` - Shopping cart items
- `wishlist` - User wishlists
- `orders` - Order information
- `order_items` - Order line items

#### Marketing & Content
- `coupons` - Discount coupons
- `newsletter_subscriptions` - Email subscriptions
- `reviews` - Product reviews
- `pages` - CMS pages

#### Settings & Configuration
- `settings` - Site configuration
- `payment_methods` - Available payment options
- `shipping_methods` - Shipping options

## 🔧 Common Operations

### Add Sample Data
```sql
SOURCE sample_data.sql;
```

### Check Table Structure
```sql
DESCRIBE users;
SHOW TABLES;
```

### Reset Database
```sql
DROP DATABASE if0_39725628_onlinestore;
SOURCE install.sql;
```

## 🐛 Troubleshooting

### Missing Columns Error
If you get "Unknown column" errors during registration:
1. Run `migrate_users_table.sql`
2. Or visit `/fix_database.php` in your browser

### Foreign Key Errors
Make sure tables are created in the correct order (users first, then related tables).

### Character Set Issues
Ensure your database uses `utf8mb4` collation for proper Unicode support.

## 📝 Notes

- All passwords in sample data use bcrypt hashing
- Default admin credentials: admin@horaastore.com / admin123
- Foreign key constraints are enabled for data integrity
- Timestamps use UTC timezone

## 🔄 Migration History

- **v1.0** - Initial schema creation
- **v1.1** - Added missing users table columns (status, role, newsletter_subscribed, last_login)
- **v1.2** - Made username field nullable for email-only registration

## 🆘 Need Help?

If you encounter any issues:
1. Check the error logs
2. Verify your MySQL version compatibility
3. Ensure proper user permissions
4. Visit `/fix_database.php` for automated diagnostics
