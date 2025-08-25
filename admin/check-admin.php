<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';

try {
    $pdo = Database::getInstance()->getConnection();
    
    echo "<h1>Admin Users Check</h1>";
    
    // Check if admin_users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'admin_users'");
    if ($stmt->rowCount() > 0) {
        echo "✅ admin_users table exists<br>";
        
        // Check admin users
        $stmt = $pdo->query("SELECT id, username, email, role, active FROM admin_users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($users)) {
            echo "<h2>Admin Users:</h2>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Active</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . $user['id'] . "</td>";
                echo "<td>" . $user['username'] . "</td>";
                echo "<td>" . $user['email'] . "</td>";
                echo "<td>" . $user['role'] . "</td>";
                echo "<td>" . ($user['active'] ? 'Yes' : 'No') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "❌ No admin users found. Please run setup.php first.";
        }
    } else {
        echo "❌ admin_users table does not exist. Please run setup.php first.";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

echo "<hr>";
echo "<a href='setup.php'>Run Setup</a> | ";
echo "<a href='login.php'>Admin Login</a> | ";
echo "<a href='products.php'>Products Page</a>";
?>
