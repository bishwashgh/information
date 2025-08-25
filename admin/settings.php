<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';
require_once '../includes/admin_auth.php';

// Check if user is admin
requireAdmin();

// Get database connection
$pdo = Database::getInstance()->getConnection();

$pageTitle = 'Admin Settings';
$pageDescription = 'Manage your admin account settings';

// Get current admin user data
$currentAdminId = $_SESSION['admin_id'];
$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
$stmt->execute([$currentAdminId]);
$currentAdmin = $stmt->fetch();

// Handle form submissions
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_profile'])) {
            // Update profile information
            $fullName = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $username = trim($_POST['username']);
            
            // Validate required fields
            if (empty($fullName) || empty($email) || empty($username)) {
                throw new Exception('Please fill in all required fields.');
            }
            
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Please enter a valid email address.');
            }
            
            // Check if username/email already exists (excluding current user)
            $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $currentAdminId]);
            if ($stmt->fetch()) {
                throw new Exception('Username or email already exists.');
            }
            
            // Update the admin user
            $stmt = $pdo->prepare("UPDATE admin_users SET full_name = ?, email = ?, username = ? WHERE id = ?");
            $result = $stmt->execute([$fullName, $email, $username, $currentAdminId]);
            
            if ($result) {
                $successMessage = 'Profile updated successfully!';
                $_SESSION['admin_username'] = $username; // Update session
                
                // Refresh current admin data
                $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
                $stmt->execute([$currentAdminId]);
                $currentAdmin = $stmt->fetch();
            } else {
                throw new Exception('Failed to update profile.');
            }
        }
        
        if (isset($_POST['change_password'])) {
            // Change password
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            // Validate required fields
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                throw new Exception('Please fill in all password fields.');
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $currentAdmin['password'])) {
                throw new Exception('Current password is incorrect.');
            }
            
            // Check if new passwords match
            if ($newPassword !== $confirmPassword) {
                throw new Exception('New passwords do not match.');
            }
            
            // Validate new password strength
            if (strlen($newPassword) < 6) {
                throw new Exception('New password must be at least 6 characters long.');
            }
            
            // Hash new password and update
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
            $result = $stmt->execute([$hashedPassword, $currentAdminId]);
            
            if ($result) {
                $successMessage = 'Password changed successfully!';
            } else {
                throw new Exception('Failed to change password.');
            }
        }
        
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #667eea;
            --primary-dark: #5a6fd8;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --border-radius: 8px;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: white;
            color: var(--gray-900);
            line-height: 1.6;
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem 2rem 2rem;
        }

        /* Admin Navbar Styles */
        .admin-navbar {
            background: white;
            border-bottom: 2px solid var(--primary-color);
            padding: 1rem 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .navbar-brand h2 {
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .navbar-menu {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .navbar-item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            color: var(--gray-700);
            font-weight: 500;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .navbar-item:hover {
            background: var(--gray-50);
            color: var(--primary-color);
        }

        .navbar-item.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .navbar-item.logout {
            margin-left: auto;
            color: var(--danger-color);
        }

        .navbar-item.logout:hover {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .admin-header {
            background: white;
            padding: 2rem 0;
            border-bottom: 1px solid var(--gray-200);
            margin-bottom: 2rem;
        }

        .admin-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .admin-header p {
            color: var(--gray-600);
        }

        /* Alert Styles */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-left: 4px solid;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #065f46;
            border-left-color: var(--success-color);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #991b1b;
            border-left-color: var(--danger-color);
        }

        /* Settings Cards */
        .settings-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .settings-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            overflow: hidden;
        }

        .card-header {
            background: var(--gray-50);
            color: var(--gray-900);
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .card-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .card-body {
            padding: 2rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--gray-700);
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-lg);
        }

        /* Info Card */
        .info-card {
            background: var(--gray-50);
            border-left: 4px solid var(--primary-color);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-radius: var(--border-radius);
        }

        .info-card h4 {
            margin-bottom: 0.5rem;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-card p {
            margin: 0.25rem 0;
            color: var(--gray-600);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius);
        }

        .stat-item h4 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .stat-item p {
            margin: 0;
            font-weight: 600;
            color: var(--gray-900);
        }

        .status-active {
            color: var(--success-color) !important;
        }

        .status-inactive {
            color: var(--danger-color) !important;
        }

        @media (max-width: 768px) {
            .admin-container {
                padding: 0 1rem 1rem 1rem;
            }

            .admin-navbar {
                padding: 1rem;
            }

            .navbar-menu {
                justify-content: center;
            }

            .settings-cards {
                grid-template-columns: 1fr;
            }

            .admin-header {
                padding: 1rem 0;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Admin Navigation Bar -->
        <nav class="admin-navbar">
            <div class="navbar-brand">
                <h2>HORAASTORE Admin</h2>
            </div>
            <div class="navbar-menu">
                <a href="products.php" class="navbar-item">
                    <i class="fas fa-box"></i> Products
                </a>
                <a href="categories.php" class="navbar-item">
                    <i class="fas fa-tags"></i> Categories
                </a>
                <a href="orders.php" class="navbar-item">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
                <a href="users.php" class="navbar-item">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="settings.php" class="navbar-item active">
                    <i class="fas fa-cogs"></i> Settings
                </a>
                <a href="../index.php" class="navbar-item">
                    <i class="fas fa-home"></i> Visit Store
                </a>
                <a href="logout.php" class="navbar-item logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>

        <div class="admin-header">
            <h1><?php echo $pageTitle; ?></h1>
            <p><?php echo $pageDescription; ?></p>
        </div>

        <?php if ($successMessage): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <!-- Current Admin Info -->
        <div class="info-card">
            <h4><i class="fas fa-info-circle"></i> Current Admin Information</h4>
            <p>Logged in as: <strong><?php echo htmlspecialchars($currentAdmin['full_name']); ?></strong> (<?php echo htmlspecialchars($currentAdmin['username']); ?>)</p>
            <p>Role: <strong><?php echo ucfirst($currentAdmin['role']); ?></strong> | Last Login: <?php echo $currentAdmin['last_login'] ? date('M j, Y g:i A', strtotime($currentAdmin['last_login'])) : 'Never'; ?></p>
        </div>

        <div class="settings-cards">
                <!-- Profile Settings Card -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3><i class="fas fa-user"></i> Profile Settings</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label for="full_name">Full Name *</label>
                                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($currentAdmin['full_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="username">Username *</label>
                                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($currentAdmin['username']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($currentAdmin['email']); ?>" required>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Password Settings Card -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3><i class="fas fa-lock"></i> Change Password</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label for="current_password">Current Password *</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password * (minimum 6 characters)</label>
                                <input type="password" id="new_password" name="new_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password *</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Account Statistics -->
            <div class="settings-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-bar"></i> Account Statistics</h3>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <h4>Account Created</h4>
                            <p><?php echo date('M j, Y', strtotime($currentAdmin['created_at'])); ?></p>
                        </div>
                        <div class="stat-item">
                            <h4>Last Updated</h4>
                            <p>
                                <?php 
                                if (isset($currentAdmin['updated_at']) && $currentAdmin['updated_at']) {
                                    echo date('M j, Y g:i A', strtotime($currentAdmin['updated_at']));
                                } else {
                                    echo date('M j, Y g:i A', strtotime($currentAdmin['created_at']));
                                }
                                ?>
                            </p>
                        </div>
                        <div class="stat-item">
                            <h4>Account Status</h4>
                            <p class="<?php echo $currentAdmin['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $currentAdmin['is_active'] ? 'Active' : 'Inactive'; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
