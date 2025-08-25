<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode('/user/security.php'));
    exit;
}

$pageTitle = 'Security Settings';
$pageDescription = 'Manage your password and security preferences';

$db = Database::getInstance()->getConnection();
$userId = getCurrentUserId();

$error = '';
$success = '';

// Get current user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Ensure user data is loaded
if (!$user) {
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'All password fields are required';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match';
        } elseif (strlen($newPassword) < 6) {
            $error = 'New password must be at least 6 characters long';
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $error = 'Current password is incorrect';
        } else {
            try {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$hashedPassword, $userId]);
                
                $success = 'Password changed successfully';
                
                // Log the password change
                $logStmt = $db->prepare("INSERT INTO user_activity_log (user_id, action, details, created_at) VALUES (?, 'password_change', 'Password changed from security settings', NOW())");
                $logStmt->execute([$userId]);
                
            } catch (PDOException $e) {
                $error = 'Failed to change password. Please try again.';
            }
        }
    }
    
    if (isset($_POST['update_security_settings'])) {
        $twoFactorEnabled = isset($_POST['two_factor_enabled']) ? 1 : 0;
        $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
        $loginAlerts = isset($_POST['login_alerts']) ? 1 : 0;
        
        try {
            $stmt = $db->prepare("
                UPDATE users 
                SET two_factor_enabled = ?, email_notifications = ?, login_alerts = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$twoFactorEnabled, $emailNotifications, $loginAlerts, $userId]);
            
            $success = 'Security settings updated successfully';
            
            // Refresh user data
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "Unknown column") !== false) {
                $error = 'Database columns are not set up yet. Please run the SQL setup file: sql/user_addresses_and_security.sql';
            } else {
                $error = 'Failed to update security settings. Please try again.';
            }
        }
    }
}

include '../includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Nunito:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* Security Page - Homepage Style */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background-color: #f8f9fa;
    min-height: 100vh;
    color: #333;
}

.security-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
    min-height: 100vh;
}

/* Header Styling */
.security-header {
    text-align: center;
    margin-bottom: 2rem;
    padding: 2rem 0;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.security-header h1 {
    font-family: 'Nunito', sans-serif;
    font-size: 2.5rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.5rem;
}

.security-header p {
    font-size: 1.1rem;
    color: #666;
    font-weight: 400;
}

/* Navigation */
.profile-nav {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.nav-breadcrumb {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.nav-breadcrumb a {
    color: #007bff;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.nav-breadcrumb a:hover {
    color: #0056b3;
}

.nav-breadcrumb span {
    color: #666;
}

/* Main Content */
.security-content {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
}

.security-card {
    background: white;
    border-radius: 8px;
    padding: 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e0e0e0;
}

.card-header {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e0e0e0;
}

.card-header h2 {
    font-family: 'Nunito', sans-serif;
    font-size: 1.5rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
}

.card-header p {
    color: #666;
    font-size: 1rem;
}

/* Form Styling */
.security-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    font-weight: 500;
    color: #333;
    font-size: 0.95rem;
}

.form-control {
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
    font-family: 'Poppins', sans-serif;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #007bff;
}

/* Checkbox Styling */
.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.checkbox-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 5px;
    border: 1px solid #e0e0e0;
}

.checkbox-item input[type="checkbox"] {
    margin-top: 0.25rem;
    width: 16px;
    height: 16px;
}

.checkbox-label {
    font-weight: 400;
    color: #333;
    cursor: pointer;
    line-height: 1.4;
}

.checkbox-label strong {
    font-weight: 500;
}

.checkbox-label small {
    color: #666;
    font-size: 0.9rem;
}

/* Button Styling */
.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 5px;
    font-size: 1rem;
    font-weight: 500;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    text-align: center;
    display: inline-block;
}

.btn-primary {
    background-color: #007bff;
    color: white;
}

.btn-primary:hover {
    background-color: #0056b3;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #545b62;
}

/* Alert Styling */
.alert {
    padding: 1rem;
    border-radius: 5px;
    margin-bottom: 1.5rem;
    font-weight: 500;
    border: 1px solid transparent;
}

.alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

/* Security Info */
.security-info {
    background-color: #f8f9fa;
    border-radius: 5px;
    padding: 1.5rem;
    margin-top: 1.5rem;
    border: 1px solid #e0e0e0;
}

.security-info h3 {
    font-family: 'Nunito', sans-serif;
    font-weight: 600;
    color: #333;
    margin-bottom: 1rem;
}

.security-tips {
    list-style: none;
    padding: 0;
}

.security-tips li {
    padding: 0.5rem 0;
    padding-left: 1.5rem;
    position: relative;
    color: #555;
    line-height: 1.4;
}

.security-tips li::before {
    content: "•";
    position: absolute;
    left: 0;
    top: 0.5rem;
    color: #007bff;
    font-weight: bold;
}

/* Responsive Design */
@media (min-width: 768px) {
    .security-content {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 768px) {
    .security-container {
        padding: 1rem 0.5rem;
    }
    
    .security-header h1 {
        font-size: 2rem;
    }
    
    .security-card {
        padding: 1.5rem;
    }
}
</style>

<div class="security-container">
    <!-- Header -->
    <div class="security-header">
        <h1>Security Settings</h1>
        <p>Protect your account with enhanced security measures</p>
    </div>

    <!-- Navigation -->
    <div class="profile-nav">
        <div class="nav-breadcrumb">
            <a href="<?php echo SITE_URL; ?>/user/profile.php">← Back to Profile</a>
            <span>•</span>
            <span>Security Settings</span>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="security-content">
        <!-- Change Password Card -->
        <div class="security-card">
            <div class="card-header">
                <h2>Change Password</h2>
                <p>Update your account password for better security</p>
            </div>

            <form method="POST" class="security-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="current_password" class="form-label">Current Password *</label>
                    <input type="password" id="current_password" name="current_password" 
                           class="form-control" required placeholder="Enter your current password">
                </div>

                <div class="form-group">
                    <label for="new_password" class="form-label">New Password *</label>
                    <input type="password" id="new_password" name="new_password" 
                           class="form-control" required placeholder="Enter new password (min 6 characters)">
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm New Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           class="form-control" required placeholder="Confirm your new password">
                </div>

                <button type="submit" name="change_password" class="btn btn-primary">
                    <i class="fas fa-key"></i> Update Password
                </button>
            </form>
        </div>

        <!-- Security Settings Card -->
        <div class="security-card">
            <div class="card-header">
                <h2>Security Preferences</h2>
                <p>Configure your security and notification settings</p>
            </div>

            <form method="POST" class="security-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="two_factor_enabled" name="two_factor_enabled" 
                               value="1" <?php echo ($user['two_factor_enabled'] ?? 0) ? 'checked' : ''; ?>>
                        <label for="two_factor_enabled" class="checkbox-label">
                            <strong>Enable Two-Factor Authentication</strong><br>
                            <small>Add an extra layer of security to your account</small>
                        </label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="email_notifications" name="email_notifications" 
                               value="1" <?php echo ($user['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                        <label for="email_notifications" class="checkbox-label">
                            <strong>Email Notifications</strong><br>
                            <small>Receive important account updates via email</small>
                        </label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="login_alerts" name="login_alerts" 
                               value="1" <?php echo ($user['login_alerts'] ?? 1) ? 'checked' : ''; ?>>
                        <label for="login_alerts" class="checkbox-label">
                            <strong>Login Alerts</strong><br>
                            <small>Get notified when someone logs into your account</small>
                        </label>
                    </div>
                </div>

                <button type="submit" name="update_security_settings" class="btn btn-secondary">
                    <i class="fas fa-shield-alt"></i> Save Security Settings
                </button>
            </form>
        </div>
    </div>

    <!-- Security Tips -->
    <div class="security-card">
        <div class="security-info">
            <h3>Security Tips</h3>
            <ul class="security-tips">
                <li>Use a strong, unique password that includes letters, numbers, and symbols</li>
                <li>Enable two-factor authentication for additional account protection</li>
                <li>Never share your password or login credentials with anyone</li>
                <li>Log out from shared or public computers after use</li>
                <li>Regularly review your account activity and report suspicious behavior</li>
                <li>Keep your contact information up to date for security notifications</li>
            </ul>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
