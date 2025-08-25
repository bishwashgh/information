<?php
/**
 * Railway Environment Check
 * Quick check to verify Railway environment setup
 */

echo "🚂 Railway Environment Check\n";
echo str_repeat("=", 40) . "\n";

// Check for Railway environment variables
$railway_vars = [
    'MYSQL_URL' => $_ENV['MYSQL_URL'] ?? null,
    'MYSQLHOST' => $_ENV['MYSQLHOST'] ?? null,
    'MYSQLDATABASE' => $_ENV['MYSQLDATABASE'] ?? null,
    'MYSQLUSER' => $_ENV['MYSQLUSER'] ?? null,
    'RAILWAY_ENVIRONMENT' => $_ENV['RAILWAY_ENVIRONMENT'] ?? null,
    'PORT' => $_ENV['PORT'] ?? null,
    'RAILWAY_PUBLIC_DOMAIN' => $_ENV['RAILWAY_PUBLIC_DOMAIN'] ?? null
];

echo "📋 Environment Variables:\n";
foreach ($railway_vars as $var => $value) {
    $status = $value ? "✅" : "❌";
    $display_value = $value ? (strlen($value) > 50 ? substr($value, 0, 50) . "..." : $value) : "Not set";
    echo "   $status $var: $display_value\n";
}

// Test database connection
echo "\n🗄️  Database Connection Test:\n";
try {
    $mysql_url = $_ENV['MYSQL_URL'] ?? null;
    
    if ($mysql_url) {
        $url_parts = parse_url($mysql_url);
        $host = $url_parts['host'];
        $port = $url_parts['port'] ?? 3306;
        $dbname = ltrim($url_parts['path'], '/');
        $username = $url_parts['user'];
        $password = $url_parts['pass'];
        
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        echo "   📡 Using MYSQL_URL connection\n";
    } elseif (isset($_ENV['MYSQLHOST'])) {
        $host = $_ENV['MYSQLHOST'];
        $port = $_ENV['MYSQLPORT'] ?? 3306;
        $dbname = $_ENV['MYSQLDATABASE'] ?? $_ENV['MYSQL_DATABASE'] ?? 'railway';
        $username = $_ENV['MYSQLUSER'] ?? 'root';
        $password = $_ENV['MYSQLPASSWORD'] ?? $_ENV['MYSQL_ROOT_PASSWORD'] ?? '';
        
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        echo "   📡 Using Railway individual variables\n";
    } else {
        echo "   ❌ No Railway MySQL variables found. Using local configuration.\n";
        
        // Try local connection
        require_once 'includes/config.php';
        $pdo = Database::getInstance()->getConnection();
        echo "   ✅ Local database connection: SUCCESS\n";
        return;
    }
    
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "   ✅ Database connection: SUCCESS\n";
    echo "   📊 Database: $dbname on $host:$port\n";
    
    // Check if tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   📋 Tables found: " . count($tables) . "\n";
    
    if (count($tables) === 0) {
        echo "   ⚠️  No tables found. Run railway_setup.php to initialize database.\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Database connection: FAILED\n";
    echo "   Error: " . $e->getMessage() . "\n";
}

// Check PHP version and extensions
echo "\n🐘 PHP Environment:\n";
echo "   ✅ PHP Version: " . PHP_VERSION . "\n";

$required_extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? "✅" : "❌";
    echo "   $status Extension $ext\n";
}

// Check file permissions
echo "\n📁 File System:\n";
$upload_dir = 'assets/images/products/';
if (is_dir($upload_dir)) {
    $writable = is_writable($upload_dir) ? "✅" : "❌";
    echo "   $writable Upload directory writable: $upload_dir\n";
} else {
    echo "   ⚠️  Upload directory not found: $upload_dir\n";
}

echo "\n" . str_repeat("=", 40) . "\n";

if (isset($_ENV['MYSQL_URL'])) {
    echo "🚀 Railway Environment: DETECTED\n";
    echo "🔗 Next steps:\n";
    echo "   1. Run railway_setup.php to initialize database\n";
    echo "   2. Set environment variables in Railway dashboard\n";
    echo "   3. Test your application\n";
} else {
    echo "💻 Local Development Environment: DETECTED\n";
    echo "🔗 To deploy to Railway:\n";
    echo "   1. Push code to GitHub\n";
    echo "   2. Connect Railway to your repository\n";
    echo "   3. Set MYSQL_URL environment variable\n";
}

echo "\n✨ Environment check complete!\n";
?>
