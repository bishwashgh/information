<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/admin_auth.php';

// If already logged in, redirect to admin panel
if (isAdminPanelUser()) {
    // Clear any expired sessions first
    if (isset($_SESSION['admin_login_time'])) {
        $currentTime = time();
        $sessionTimeout = 24 * 60 * 60; // 24 hours
        
        if (($currentTime - $_SESSION['admin_login_time']) > $sessionTimeout) {
            // Session expired, clear it
            session_unset();
            session_destroy();
            session_start();
        } else {
            // Valid session, redirect to products
            header('Location: products.php');
            exit();
        }
    } else {
        // No login time set, redirect to products
        header('Location: products.php');
        exit();
    }
}

$error = '';
$success = '';

// Handle login form submission
if ($_POST && isset($_POST['username']) && isset($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            // Test database connection first
            $pdo->query("SELECT 1");
            
            // First, ensure admin_users table exists with proper structure
            $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                role ENUM('admin', 'manager') DEFAULT 'admin',
                is_active BOOLEAN DEFAULT TRUE,
                last_login TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            // Check if password column exists, if not add it
            $stmt = $pdo->query("SHOW COLUMNS FROM admin_users LIKE 'password'");
            if ($stmt->rowCount() == 0) {
                $pdo->exec("ALTER TABLE admin_users ADD COLUMN password VARCHAR(255) NOT NULL AFTER email");
            }
            
            // Check if full_name column exists, if not add it
            $stmt = $pdo->query("SHOW COLUMNS FROM admin_users LIKE 'full_name'");
            if ($stmt->rowCount() == 0) {
                $pdo->exec("ALTER TABLE admin_users ADD COLUMN full_name VARCHAR(100) NOT NULL AFTER password");
            }
            
            // Check if role column exists, if not add it
            $stmt = $pdo->query("SHOW COLUMNS FROM admin_users LIKE 'role'");
            if ($stmt->rowCount() == 0) {
                $pdo->exec("ALTER TABLE admin_users ADD COLUMN role ENUM('admin', 'manager') DEFAULT 'admin' AFTER full_name");
            }
            
            // Check if is_active column exists, if not add it
            $stmt = $pdo->query("SHOW COLUMNS FROM admin_users LIKE 'is_active'");
            if ($stmt->rowCount() == 0) {
                $pdo->exec("ALTER TABLE admin_users ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER role");
            }
            
            // Check if there are any admin users, if not create a default one
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM admin_users");
            $count = $stmt->fetch()['count'];
            
            if ($count == 0) {
                // Create default admin user
                $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admin_users (username, email, password, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $result = $stmt->execute(['admin', 'admin@horaastore.com', $defaultPassword, 'Administrator', 'admin', 1]);
                
                if ($result) {
                    $success = 'Default admin user created! Use username: admin, password: admin123';
                } else {
                    $error = 'Failed to create default admin user';
                }
            }
            
            // Debug: Check what users exist
            $stmt = $pdo->query("SELECT username, email, is_active FROM admin_users");
            $existingUsers = $stmt->fetchAll();
            
            // Check admin credentials
            $stmt = $pdo->prepare("SELECT id, username, email, password, full_name, role, is_active FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            
            if (!$admin) {
                $error = 'User not found. Available users: ' . implode(', ', array_column($existingUsers, 'username'));
            } elseif ($admin['is_active'] != 1) {
                $error = 'User account is not active.';
            } elseif (!password_verify($password, $admin['password'])) {
                $error = 'Password verification failed. Please check your password.';
            } else {
                // Login successful - set session variables
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['admin_login_time'] = time(); // Add login time for session timeout
                
                // Update last login
                $updateStmt = $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$admin['id']]);
                
                // Redirect to admin panel
                header('Location: products.php');
                exit();
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Check if session expired
if (isset($_GET['expired'])) {
    $error = 'Your session has expired. Please login again.';
}

// Check for logout message
if (isset($_GET['message']) && $_GET['message'] === 'logged_out') {
    $success = 'You have been successfully logged out.';
}

// Check for reset admin user request
if (isset($_GET['reset_admin']) && $_GET['reset_admin'] === '1') {
    try {
        // Delete existing admin user and recreate
        $pdo->exec("DELETE FROM admin_users WHERE username = 'admin'");
        
        $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, email, password, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute(['admin', 'admin@horaastore.com', $defaultPassword, 'Administrator', 'admin', 1]);
        
        if ($result) {
            $success = 'Admin user reset successfully! Use username: admin, password: admin123';
        } else {
            $error = 'Failed to reset admin user';
        }
    } catch (Exception $e) {
        $error = 'Error resetting admin user: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - HORAASTORE</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .alert-error {
            background: #fee;
            color: #c53030;
            border: 1px solid #fed7d7;
        }

        .alert-success {
            background: #f0fff4;
            color: #38a169;
            border: 1px solid #c6f6d5;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .security-note {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            margin-top: 20px;
            font-size: 12px;
            color: #718096;
            text-align: center;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 10px;
            }

            .login-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Admin Login</h1>
            <p>HORAASTORE Admin Panel</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                       autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required
                       autocomplete="current-password">
            </div>

            <button type="submit" class="login-btn">
                Login to Admin Panel
            </button>
        </form>

        <div style="background: #e3f2fd; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; color: #1565c0;">
            <strong>Default Login:</strong><br>
            Username: <code>admin</code><br>
            Password: <code>admin123</code>
        </div>

        <div class="back-link">
            <a href="../index.php">← Back to Store</a> | 
            <a href="?reset_admin=1" style="color: #f59e0b;">Reset Admin User</a>
        </div>

        <div class="security-note">
            🔒 This is a secure admin area. All login attempts are logged.
        </div>
    </div>

    <script>
        // Auto-focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            const usernameField = document.getElementById('username');
            if (usernameField && !usernameField.value) {
                usernameField.focus();
            }
        });

        // Add loading state to form
        document.querySelector('form').addEventListener('submit', function() {
            const btn = document.querySelector('.login-btn');
            btn.textContent = 'Logging in...';
            btn.disabled = true;
        });
    </script>
</body>
</html>
