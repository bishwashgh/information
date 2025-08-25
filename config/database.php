<?php
/**
 * Database Configuration and Connection
 */

class Database {
    private static $instance = null;
    private $connection;
    private $host;
    private $db_name;
    private $username;
    private $password;

    private function __construct() {
        $this->loadEnv();
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db_name = $_ENV['DB_NAME'] ?? 'if0_39725628_onlinestore';
        $this->username = $_ENV['DB_USER'] ?? 'root';
        $this->password = $_ENV['DB_PASS'] ?? '';
        
        $this->connect();
        $this->createTables();
    }

    private function loadEnv() {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && substr($line, 0, 1) !== '#') {
                    list($key, $value) = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value);
                }
            }
        }
    }

    private function connect() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . $this->host,
                $this->username,
                $this->password
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if it doesn't exist
            $this->connection->exec("CREATE DATABASE IF NOT EXISTS `{$this->db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->connection->exec("USE `{$this->db_name}`");
            
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public static function resetInstance() {
        self::$instance = null;
    }

    public function getConnection() {
        return $this->connection;
    }

    private function createTables() {
        // First, run migrations to update existing tables
        $this->runMigrations();
        
        // If you want to use SQL files instead of PHP queries, 
        // uncomment the following line and comment out the $queries array:
        // $this->executeSQLFile(__DIR__ . '/../sql/schema.sql');
        
        $queries = [
            // Users table
            "CREATE TABLE IF NOT EXISTS users (
                id INT PRIMARY KEY AUTO_INCREMENT,
                username VARCHAR(50) UNIQUE,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                first_name VARCHAR(50) NOT NULL,
                last_name VARCHAR(50) NOT NULL,
                phone VARCHAR(20),
                status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
                role ENUM('customer', 'vendor', 'admin') DEFAULT 'customer',
                newsletter_subscribed BOOLEAN DEFAULT FALSE,
                is_admin BOOLEAN DEFAULT FALSE,
                is_verified BOOLEAN DEFAULT FALSE,
                last_login TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",

            // Categories table
            "CREATE TABLE IF NOT EXISTS categories (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) UNIQUE NOT NULL,
                description TEXT,
                image VARCHAR(255),
                parent_id INT DEFAULT NULL,
                sort_order INT DEFAULT 0,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
            )",

            // Products table
            "CREATE TABLE IF NOT EXISTS products (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(200) NOT NULL,
                slug VARCHAR(200) UNIQUE NOT NULL,
                description TEXT,
                short_description TEXT,
                category_id INT NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                sale_price DECIMAL(10,2) DEFAULT NULL,
                sku VARCHAR(100) UNIQUE,
                stock_quantity INT DEFAULT 0,
                manage_stock BOOLEAN DEFAULT TRUE,
                weight DECIMAL(8,2) DEFAULT NULL,
                dimensions VARCHAR(100),
                featured BOOLEAN DEFAULT FALSE,
                status ENUM('active', 'inactive', 'draft') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
            )",

            // Product images table
            "CREATE TABLE IF NOT EXISTS product_images (
                id INT PRIMARY KEY AUTO_INCREMENT,
                product_id INT NOT NULL,
                image_url VARCHAR(255) NOT NULL,
                alt_text VARCHAR(255),
                sort_order INT DEFAULT 0,
                is_primary BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )",

            // Product attributes table (for sizes, colors, etc.)
            "CREATE TABLE IF NOT EXISTS product_attributes (
                id INT PRIMARY KEY AUTO_INCREMENT,
                product_id INT NOT NULL,
                attribute_name VARCHAR(50) NOT NULL,
                attribute_value VARCHAR(100) NOT NULL,
                price_modifier DECIMAL(10,2) DEFAULT 0,
                stock_quantity INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )",

            // User addresses table
            "CREATE TABLE IF NOT EXISTS user_addresses (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                type ENUM('billing', 'shipping') DEFAULT 'shipping',
                first_name VARCHAR(50) NOT NULL,
                last_name VARCHAR(50) NOT NULL,
                company VARCHAR(100),
                address_line_1 VARCHAR(255) NOT NULL,
                address_line_2 VARCHAR(255),
                city VARCHAR(100) NOT NULL,
                state VARCHAR(100),
                postal_code VARCHAR(20),
                country VARCHAR(100) DEFAULT 'Nepal',
                phone VARCHAR(20),
                is_default BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",

            // Orders table
            "CREATE TABLE IF NOT EXISTS orders (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT,
                order_number VARCHAR(50) UNIQUE NOT NULL,
                status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded') DEFAULT 'pending',
                total_amount DECIMAL(10,2) NOT NULL,
                subtotal DECIMAL(10,2) NOT NULL,
                tax_amount DECIMAL(10,2) DEFAULT 0,
                shipping_amount DECIMAL(10,2) DEFAULT 0,
                discount_amount DECIMAL(10,2) DEFAULT 0,
                payment_method VARCHAR(50),
                payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
                shipping_address TEXT,
                billing_address TEXT,
                notes TEXT,
                tracking_number VARCHAR(100),
                shipped_at TIMESTAMP NULL,
                delivered_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )",

            // Order items table
            "CREATE TABLE IF NOT EXISTS order_items (
                id INT PRIMARY KEY AUTO_INCREMENT,
                order_id INT NOT NULL,
                product_id INT NOT NULL,
                product_name VARCHAR(200) NOT NULL,
                product_sku VARCHAR(100),
                quantity INT NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                total DECIMAL(10,2) NOT NULL,
                product_attributes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )",

            // Shopping cart table
            "CREATE TABLE IF NOT EXISTS cart (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT,
                session_id VARCHAR(255),
                product_id INT NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                attributes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )",

            // Wishlist table
            "CREATE TABLE IF NOT EXISTS wishlist (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                UNIQUE KEY unique_wishlist (user_id, product_id)
            )",

            // Product reviews table
            "CREATE TABLE IF NOT EXISTS product_reviews (
                id INT PRIMARY KEY AUTO_INCREMENT,
                product_id INT NOT NULL,
                user_id INT NOT NULL,
                rating INT CHECK (rating >= 1 AND rating <= 5),
                title VARCHAR(200),
                comment TEXT,
                is_approved BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_review (product_id, user_id)
            )",

            // Coupons table
            "CREATE TABLE IF NOT EXISTS coupons (
                id INT PRIMARY KEY AUTO_INCREMENT,
                code VARCHAR(50) UNIQUE NOT NULL,
                type ENUM('percentage', 'fixed') DEFAULT 'percentage',
                value DECIMAL(10,2) NOT NULL,
                minimum_amount DECIMAL(10,2) DEFAULT 0,
                maximum_uses INT DEFAULT NULL,
                used_count INT DEFAULT 0,
                start_date DATE,
                end_date DATE,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",

            // Newsletter subscriptions table
            "CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
                id INT PRIMARY KEY AUTO_INCREMENT,
                email VARCHAR(100) UNIQUE NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                unsubscribed_at TIMESTAMP NULL
            )",

            // OTP verifications table
            "CREATE TABLE IF NOT EXISTS otp_verifications (
                id INT PRIMARY KEY AUTO_INCREMENT,
                email VARCHAR(100) NOT NULL,
                otp VARCHAR(6) NOT NULL,
                type ENUM('registration', 'login', 'reset_password') DEFAULT 'registration',
                expires_at TIMESTAMP NOT NULL,
                is_used BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",

            // Site settings table
            "CREATE TABLE IF NOT EXISTS site_settings (
                id INT PRIMARY KEY AUTO_INCREMENT,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )"
        ];

        foreach ($queries as $query) {
            try {
                $this->connection->exec($query);
            } catch(PDOException $e) {
                error_log("Error creating table: " . $e->getMessage());
            }
        }

        // Insert default admin user if not exists
        $this->createDefaultAdmin();
        $this->insertDefaultCategories();
        $this->insertSampleProducts();
    }

    private function createDefaultAdmin() {
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM users WHERE is_admin = 1");
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            $stmt = $this->connection->prepare("
                INSERT INTO users (username, email, password, first_name, last_name, is_admin, is_verified) 
                VALUES (?, ?, ?, ?, ?, 1, 1)
            ");
            $stmt->execute(['admin', 'admin@store.com', password_hash('admin123', PASSWORD_DEFAULT), 'Admin', 'User']);
        }
    }

    private function insertDefaultCategories() {
        $categories = [
            ['name' => 'Clothing', 'slug' => 'clothing', 'description' => 'Jerseys, caps, hoodies and more'],
            ['name' => 'Cafe', 'slug' => 'cafe', 'description' => 'Pizza, coffee, snacks and drinks']
        ];

        foreach ($categories as $category) {
            $stmt = $this->connection->prepare("SELECT COUNT(*) FROM categories WHERE slug = ?");
            $stmt->execute([$category['slug']]);
            
            if ($stmt->fetchColumn() == 0) {
                $stmt = $this->connection->prepare("
                    INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)
                ");
                $stmt->execute([$category['name'], $category['slug'], $category['description']]);
            }
        }
    }

    private function insertSampleProducts() {
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM products");
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            // Get category IDs
            $stmt = $this->connection->prepare("SELECT id FROM categories WHERE slug = 'clothing'");
            $stmt->execute();
            $clothingId = $stmt->fetchColumn();

            $stmt = $this->connection->prepare("SELECT id FROM categories WHERE slug = 'cafe'");
            $stmt->execute();
            $cafeId = $stmt->fetchColumn();

            $products = [
                [
                    'name' => 'Premium Jersey',
                    'slug' => 'premium-jersey',
                    'description' => 'High-quality sports jersey with moisture-wicking fabric',
                    'category_id' => $clothingId,
                    'price' => 2500.00,
                    'sku' => 'JER001',
                    'stock_quantity' => 50
                ],
                [
                    'name' => 'Classic Baseball Cap',
                    'slug' => 'classic-baseball-cap',
                    'description' => 'Comfortable cotton baseball cap with adjustable strap',
                    'category_id' => $clothingId,
                    'price' => 800.00,
                    'sku' => 'CAP001',
                    'stock_quantity' => 100
                ],
                [
                    'name' => 'Margherita Pizza',
                    'slug' => 'margherita-pizza',
                    'description' => 'Classic pizza with tomato sauce, mozzarella, and fresh basil',
                    'category_id' => $cafeId,
                    'price' => 650.00,
                    'sku' => 'PIZ001',
                    'stock_quantity' => 999
                ],
                [
                    'name' => 'Premium Coffee',
                    'slug' => 'premium-coffee',
                    'description' => 'Freshly brewed coffee from premium beans',
                    'category_id' => $cafeId,
                    'price' => 250.00,
                    'sku' => 'COF001',
                    'stock_quantity' => 999
                ]
            ];

            foreach ($products as $product) {
                $stmt = $this->connection->prepare("
                    INSERT INTO products (name, slug, description, category_id, price, sku, stock_quantity, featured) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([
                    $product['name'],
                    $product['slug'],
                    $product['description'],
                    $product['category_id'],
                    $product['price'],
                    $product['sku'],
                    $product['stock_quantity']
                ]);
            }
        }
    }
    
    private function runMigrations() {
        try {
            // Force check and add missing columns immediately
            $this->forceAddMissingColumns();
        } catch (Exception $e) {
            error_log("Migration error: " . $e->getMessage());
        }
    }
    
    private function forceAddMissingColumns() {
        // Check if users table exists first
        $stmt = $this->connection->query("SHOW TABLES LIKE 'users'");
        if (!$stmt->fetch()) {
            return; // Table doesn't exist, will be created by createTables()
        }
        
        // Force add each missing column
        $columns = [
            'status' => "ENUM('active', 'inactive', 'suspended') DEFAULT 'active'",
            'role' => "ENUM('customer', 'vendor', 'admin') DEFAULT 'customer'",
            'newsletter_subscribed' => 'BOOLEAN DEFAULT FALSE',
            'last_login' => 'TIMESTAMP NULL'
        ];
        
        foreach ($columns as $column => $definition) {
            try {
                // Try to add the column - if it exists, MySQL will give an error which we'll ignore
                $this->connection->exec("ALTER TABLE users ADD COLUMN `$column` $definition");
                error_log("Added missing column: $column");
            } catch (PDOException $e) {
                // Column probably already exists - this is fine
                if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                    error_log("Error adding column $column: " . $e->getMessage());
                }
            }
        }
        
        // Make username nullable
        try {
            $this->connection->exec("ALTER TABLE users MODIFY COLUMN username VARCHAR(50) UNIQUE NULL");
        } catch (PDOException $e) {
            // Ignore errors if column is already nullable
        }
    }
    
    private function addColumnIfNotExists($table, $column, $definition) {
        try {
            $stmt = $this->connection->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
            if (!$stmt->fetch()) {
                $this->connection->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
                error_log("Added column $column to table $table");
            }
        } catch (Exception $e) {
            error_log("Error adding column $column to table $table: " . $e->getMessage());
        }
    }
    
    private function modifyColumnIfNeeded($table, $column, $definition) {
        try {
            $stmt = $this->connection->query("SHOW COLUMNS FROM `$table` WHERE Field = '$column' AND `Null` = 'NO'");
            if ($stmt->fetch()) {
                $this->connection->exec("ALTER TABLE `$table` MODIFY COLUMN `$column` $definition");
                error_log("Modified column $column in table $table");
            }
        } catch (Exception $e) {
            error_log("Error modifying column $column in table $table: " . $e->getMessage());
        }
    }
    
    /**
     * Execute SQL file
     */
    private function executeSQLFile($filePath) {
        if (!file_exists($filePath)) {
            error_log("SQL file not found: $filePath");
            return false;
        }
        
        try {
            $sql = file_get_contents($filePath);
            // Split by semicolon but ignore semicolons in quotes
            $statements = $this->splitSQLStatements($sql);
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement) && $statement !== ';') {
                    $this->connection->exec($statement);
                }
            }
            return true;
        } catch (Exception $e) {
            error_log("Error executing SQL file $filePath: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Split SQL statements properly
     */
    private function splitSQLStatements($sql) {
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            
            if (!$inString) {
                if ($char === '"' || $char === "'") {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === ';') {
                    $statements[] = $current;
                    $current = '';
                    continue;
                }
            } else {
                if ($char === $stringChar && $sql[$i-1] !== '\\') {
                    $inString = false;
                }
            }
            
            $current .= $char;
        }
        
        if (!empty(trim($current))) {
            $statements[] = $current;
        }
        
        return $statements;
    }
}

// Initialize database connection
$db = Database::getInstance();
?>
