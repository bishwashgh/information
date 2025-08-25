<?php
/**
 * Database Migration Script
 * This script helps migrate from ecommerce_db to if0_39725628_onlinestore
 */

echo "=== Database Migration Script ===\n";
echo "This script will help you migrate your data from ecommerce_db to if0_39725628_onlinestore\n\n";

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$old_db = 'ecommerce_db';
$new_db = 'if0_39725628_onlinestore';

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to MySQL server\n";
    
    // Check if old database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '$old_db'");
    $old_exists = $stmt->rowCount() > 0;
    
    if ($old_exists) {
        echo "✅ Found existing database: $old_db\n";
        
        // Create new database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$new_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Created new database: $new_db\n";
        
        // Get list of tables from old database
        $stmt = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$old_db'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($tables)) {
            echo "📋 Found " . count($tables) . " tables to migrate:\n";
            foreach ($tables as $table) {
                echo "   - $table\n";
            }
            
            echo "\n🔄 Starting migration...\n";
            
            foreach ($tables as $table) {
                // Copy table structure and data
                $pdo->exec("CREATE TABLE IF NOT EXISTS `$new_db`.`$table` LIKE `$old_db`.`$table`");
                $pdo->exec("INSERT INTO `$new_db`.`$table` SELECT * FROM `$old_db`.`$table`");
                echo "✅ Migrated table: $table\n";
            }
            
            echo "\n🎉 Migration completed successfully!\n";
            echo "📊 Database Summary:\n";
            
            // Show record counts
            foreach ($tables as $table) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM `$new_db`.`$table`");
                $count = $stmt->fetchColumn();
                echo "   - $table: $count records\n";
            }
            
            echo "\n⚠️  IMPORTANT NOTES:\n";
            echo "1. Your old database ($old_db) is still intact\n";
            echo "2. You can now use the new database ($new_db)\n";
            echo "3. Update your application configuration to use: $new_db\n";
            echo "4. Test thoroughly before removing the old database\n";
            
        } else {
            echo "ℹ️  Old database exists but has no tables\n";
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$new_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "✅ Created empty new database: $new_db\n";
        }
        
    } else {
        echo "ℹ️  Old database ($old_db) not found\n";
        echo "✅ Creating fresh database: $new_db\n";
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$new_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ New database created successfully\n";
        echo "📝 You can now run your schema files to set up tables\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "💡 Make sure MySQL is running and credentials are correct\n";
}

echo "\n=== Migration Complete ===\n";
?>
