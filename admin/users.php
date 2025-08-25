<?php
require_once '../includes/db.php';
require_once '../includes/admin_auth.php';

// Check admin authentication
requireAdmin();

$pageTitle = "Users Management";
$pageDescription = "Manage customer accounts and user permissions";

$successMessage = '';
$errorMessage = '';

// Handle user status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $userId = (int)$_POST['user_id'];
    $currentStatus = $_POST['current_status'];
    $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $userId]);
        $successMessage = "User status updated successfully!";
    } catch (Exception $e) {
        $errorMessage = "Error updating user status: " . $e->getMessage();
    }
}

// Get real users from database
try {
    // Get users with their order statistics
    $stmt = $pdo->query("
        SELECT u.*,
               COALESCE(order_stats.order_count, 0) as orders_count,
               COALESCE(order_stats.total_spent, 0) as total_spent
        FROM users u
        LEFT JOIN (
            SELECT user_id, 
                   COUNT(*) as order_count,
                   SUM(total_amount) as total_spent
            FROM orders 
            GROUP BY user_id
        ) order_stats ON u.id = order_stats.user_id
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll();
    
    // If no users found or orders table doesn't exist, get basic user info
    if (empty($users)) {
        $stmt = $pdo->query("
            SELECT u.*,
                   0 as orders_count,
                   0 as total_spent
            FROM users u
            ORDER BY u.created_at DESC
        ");
        $users = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    $users = [];
    $errorMessage = "Error loading users: " . $e->getMessage();
}

$totalUsers = count($users);
$activeUsers = count(array_filter($users, function($u) {
    return isset($u['status']) ? $u['status'] === 'active' : true;
}));
$customerUsers = count(array_filter($users, function($u) {
    return !isset($u['role']) || $u['role'] === 'customer' || empty($u['role']);
}));
$adminUsers = count(array_filter($users, function($u) {
    return isset($u['role']) && $u['role'] === 'admin';
}));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - HORAASTORE Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-card.total { border-left-color: var(--primary-color); }
        .stat-card.active { border-left-color: var(--success-color); }
        .stat-card.customers { border-left-color: var(--info-color); }
        .stat-card.admins { border-left-color: var(--warning-color); }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }

        .stat-card.total .stat-icon { background: var(--primary-color); }
        .stat-card.active .stat-icon { background: var(--success-color); }
        .stat-card.customers .stat-icon { background: var(--info-color); }
        .stat-card.admins .stat-icon { background: var(--warning-color); }

        .stat-content h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .stat-content p {
            color: var(--gray-600);
            font-weight: 500;
            font-size: 0.875rem;
        }

        /* Users Table */
        .users-section {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .section-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
        }

        .section-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th,
        .users-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .users-table th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .users-table tr:hover {
            background: var(--gray-50);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .user-details h4 {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .user-details .email {
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.375rem 0.75rem;
            border-radius: var(--border-radius);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .role-customer {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info-color);
        }

        .role-admin {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.375rem 0.75rem;
            border-radius: var(--border-radius);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .status-inactive {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .user-stats {
            text-align: center;
        }

        .user-stats .stat-number {
            font-weight: 700;
            color: var(--gray-900);
            font-size: 1.1rem;
        }

        .user-stats .stat-label {
            font-size: 0.75rem;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border: 1px solid transparent;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
            border-color: var(--gray-300);
        }

        .btn-secondary:hover {
            background: var(--gray-200);
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
            border-color: var(--danger-color);
        }

        .btn-danger:hover {
            background: #dc2626;
            border-color: #dc2626;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .demo-notice {
            background: var(--info-color);
            color: white;
            padding: 1rem;
            text-align: center;
            font-weight: 600;
            margin-bottom: 2rem;
            border-radius: var(--border-radius);
        }

        .no-users {
            text-align: center;
            padding: 3rem;
            color: var(--gray-500);
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
                <a href="users.php" class="navbar-item active">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="settings.php" class="navbar-item">
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
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <!-- User Statistics -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalUsers; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="stat-card active">
                <div class="stat-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $activeUsers; ?></h3>
                    <p>Active Users</p>
                </div>
            </div>
            <div class="stat-card customers">
                <div class="stat-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $customerUsers; ?></h3>
                    <p>Customers</p>
                </div>
            </div>
            <div class="stat-card admins">
                <div class="stat-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $adminUsers; ?></h3>
                    <p>Administrators</p>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="users-section">
            <div class="section-header">
                <h2>All Users</h2>
            </div>
            
            <?php if (!empty($users)): ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Joined</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php 
                                            $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                                            if (empty($name)) {
                                                $name = $user['username'] ?? 'User';
                                            }
                                            echo strtoupper(substr($name, 0, 2)); 
                                            ?>
                                        </div>
                                        <div class="user-details">
                                            <h4><?php echo htmlspecialchars($name); ?></h4>
                                            <div class="email"><?php echo htmlspecialchars($user['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="role-badge role-<?php echo $user['role'] ?? 'customer'; ?>">
                                        <?php if (($user['role'] ?? 'customer') === 'admin' || ($user['is_admin'] ?? false)): ?>
                                            <i class="fas fa-shield-alt"></i> Admin
                                        <?php else: ?>
                                            <i class="fas fa-user"></i> Customer
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['status'] ?? 'active'; ?>">
                                        <?php if (($user['status'] ?? 'active') === 'active'): ?>
                                            <i class="fas fa-check"></i> Active
                                        <?php elseif (($user['status'] ?? 'active') === 'suspended'): ?>
                                            <i class="fas fa-ban"></i> Suspended
                                        <?php else: ?>
                                            <i class="fas fa-times"></i> Inactive
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="user-stats">
                                        <div class="stat-number"><?php echo $user['orders_count']; ?></div>
                                        <div class="stat-label">Orders</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="user-stats">
                                        <div class="stat-number">NPR <?php echo number_format($user['total_spent'], 2); ?></div>
                                        <div class="stat-label">Spent</div>
                                    </div>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['last_login']): ?>
                                        <?php echo date('M j, Y', strtotime($user['last_login'])); ?>
                                    <?php else: ?>
                                        <span style="color: var(--gray-500);">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <button class="btn btn-primary btn-sm" onclick="viewUser(<?php echo $user['id']; ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <?php if (($user['role'] ?? 'customer') !== 'admin' && !($user['is_admin'] ?? false)): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="current_status" value="<?php echo $user['status'] ?? 'active'; ?>">
                                                <button type="submit" name="toggle_status" 
                                                        class="btn <?php echo ($user['status'] ?? 'active') === 'active' ? 'btn-secondary' : 'btn-primary'; ?> btn-sm"
                                                        onclick="return confirm('Are you sure you want to change this user\'s status?')">
                                                    <i class="fas fa-<?php echo ($user['status'] ?? 'active') === 'active' ? 'pause' : 'play'; ?>"></i>
                                                    <?php echo ($user['status'] ?? 'active') === 'active' ? 'Suspend' : 'Activate'; ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-users">
                    <i class="fas fa-users"></i>
                    <h3>No users found</h3>
                    <p>Users will appear here when they register</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // View user details function
        function viewUser(userId) {
            // For now, show an alert with user ID
            // In a full implementation, this would open a modal with user details
            alert('View user details for User ID: ' + userId + '\n\nThis feature can be expanded to show:\n- Full user profile\n- Order history\n- Account activity\n- Edit user details');
        }

        // Auto-close success messages
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-success');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>
