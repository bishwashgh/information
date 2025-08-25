<?php
/**
 * Railway Database Setup Script
 * Run this after deployment to set up your database tables
 */

echo "🚂 Setting up HORAASTORE database on Railway...\n\n";

// Use Railway's MySQL URL environment variable
$mysql_url = $_ENV['MYSQL_URL'] ?? null;

if (!$mysql_url && isset($_ENV['MYSQLHOST'])) {
    // Fallback to individual Railway MySQL variables
    $host = $_ENV['MYSQLHOST'];
    $port = $_ENV['MYSQLPORT'] ?? 3306;
    $dbname = $_ENV['MYSQLDATABASE'] ?? $_ENV['MYSQL_DATABASE'] ?? 'railway';
    $username = $_ENV['MYSQLUSER'] ?? 'root';
    $password = $_ENV['MYSQLPASSWORD'] ?? $_ENV['MYSQL_ROOT_PASSWORD'] ?? '';
    
    echo "📡 Using Railway MySQL individual variables...\n";
} elseif ($mysql_url) {
    // Parse the MySQL URL
    $url_parts = parse_url($mysql_url);
    $host = $url_parts['host'];
    $port = $url_parts['port'] ?? 3306;
    $dbname = ltrim($url_parts['path'], '/');
    $username = $url_parts['user'];
    $password = $url_parts['pass'];
    
    echo "📡 Using Railway MYSQL_URL...\n";
} else {
    die("❌ No Railway MySQL environment variables found. Please check your Railway dashboard.\n");
}

try {
    echo "📡 Connecting to Railway MySQL database...\n";
    
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    
    echo "✅ Connected to Railway MySQL database\n";
    
    // Read the installation SQL file
    $sql_file = __DIR__ . '/sql/install.sql';
    if (!file_exists($sql_file)) {
        die("❌ SQL file not found: $sql_file\n");
    }
    
    $sql_content = file_get_contents($sql_file);
    
    // Remove database creation commands (Railway manages the database)
    $sql_content = preg_replace('/CREATE DATABASE.*?;/i', '', $sql_content);
    $sql_content = preg_replace('/USE\s+.*?;/i', '', $sql_content);
    
    // Split into individual statements
    $statements = array_filter(
        array_map('trim', 
        preg_split('/;(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $sql_content))
    );
    
    echo "📋 Executing " . count($statements) . " SQL statements...\n";
    
    $executed = 0;
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^\s*--/', $statement)) {
            try {
                $pdo->exec($statement);
                $executed++;
                
                // Show progress for table creation
                if (stripos($statement, 'CREATE TABLE') !== false) {
                    preg_match('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?`?(\w+)`?/i', $statement, $matches);
                    $table_name = $matches[1] ?? 'unknown';
                    echo "✅ Created table: $table_name\n";
                }
            } catch (PDOException $e) {
                // Skip if table already exists
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "⚠️  Warning: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "✅ Executed $executed SQL statements\n\n";
    
    // Create default admin user
    echo "👤 Creating default admin user...\n";
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        // Create default admin user in the `users` table (app expects `users`)
        echo "\ud83d\udc64 Creating default admin user...\n";
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);

        // Ensure we insert into the same `users` table that the app uses
        $stmt = $pdo->prepare(
            "INSERT INTO users (username, email, password, first_name, last_name, is_admin, is_verified, created_at) 
             SELECT ?, ?, ?, ?, ?, ?, ?, NOW()
             FROM DUAL
             WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = ?)"
        );

        $result = $stmt->execute([
            'admin',
            'admin@horaastore.com',
            $hashedPassword,
            'Administrator',
            'User',
            1,
            1,
            'admin@horaastore.com'
        ]);

        if ($result) {
            echo "\u2705 Default admin user created (or already exists).\n";
            echo "   Username: admin\n";
            echo "   Password: admin123\n";
            echo "   Email: admin@horaastore.com\n\n";
        }
    
    // Show database summary
    echo "📊 Database Summary:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "   Total tables: " . count($tables) . "\n";
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
            $count = $stmt->fetchColumn();
            echo "   - $table: $count records\n";
        } catch (Exception $e) {
            echo "   - $table: (error counting)\n";
        }
    }
    
    echo "\n🎉 Database setup completed successfully!\n";
    echo "🔗 Your HORAASTORE is ready at: " . ($_ENV['RAILWAY_PUBLIC_DOMAIN'] ?? 'your-railway-domain') . "\n";
    echo "🔑 Admin panel: /admin/login.php\n";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    echo "💡 Please check your Railway MySQL service and MYSQL_URL variable\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🚀 HORAASTORE is now live on Railway!\n";
echo str_repeat("=", 50) . "\n";
?>
