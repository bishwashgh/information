<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode('/user/profile.php'));
    exit;
}

$pageTitle = 'Profile Settings';
$pageDescription = 'Manage your profile information';

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
    if (isset($_POST['update_profile'])) {
        $firstName = sanitizeInput($_POST['first_name'] ?? '');
        $lastName = sanitizeInput($_POST['last_name'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $dateOfBirth = sanitizeInput($_POST['date_of_birth'] ?? '');
        $newsletterSubscribed = isset($_POST['newsletter_subscribed']) ? 1 : 0;
        
        if (empty($firstName) || empty($lastName)) {
            $error = 'First name and last name are required';
        } else {
            try {
                $stmt = $db->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, phone = ?, date_of_birth = ?, 
                        newsletter_subscribed = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $firstName, $lastName, $phone, 
                    $dateOfBirth ?: null, 
                    $newsletterSubscribed, $userId
                ]);
                
                // Update session data
                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name'] = $lastName;
                
                // Update newsletter subscription
                if ($newsletterSubscribed) {
                    $stmt = $db->prepare("
                        INSERT INTO newsletter_subscriptions (email, status, subscribed_at)
                        VALUES (?, 'active', NOW())
                        ON DUPLICATE KEY UPDATE status = 'active', subscribed_at = NOW()
                    ");
                    $stmt->execute([$user['email']]);
                } else {
                    $stmt = $db->prepare("
                        UPDATE newsletter_subscriptions 
                        SET status = 'unsubscribed', unsubscribed_at = NOW()
                        WHERE email = ?
                    ");
                    $stmt->execute([$user['email']]);
                }
                
                $success = 'Profile updated successfully';
                
                // Refresh user data
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } catch (Exception $e) {
                error_log("Profile update error: " . $e->getMessage());
                $error = 'Failed to update profile';
            }
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<!-- Profile Section -->
<section class="profile-section">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <h1>Profile Settings</h1>
                <p>Manage your personal information and account preferences</p>
            </div>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <div class="profile-layout">
            <!-- Profile Navigation -->
            <div class="profile-nav">
                <div class="nav-card">
                    <div class="user-avatar-large">
                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <h3><?php echo sanitizeInput($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                        <p><?php echo sanitizeInput($user['email']); ?></p>
                        <span class="member-since">
                            Member since <?php echo date('M Y', strtotime($user['created_at'])); ?>
                        </span>
                    </div>
                </div>
                
                <nav class="profile-menu">
                    <a href="#profile-info" class="menu-item active" data-tab="profile-info">
                        <i class="fas fa-user"></i>
                        Personal Information
                    </a>
                    <a href="<?php echo SITE_URL; ?>/user/security.php" class="menu-item">
                        <i class="fas fa-shield-alt"></i>
                        Security
                    </a>
                    <a href="<?php echo SITE_URL; ?>/user/addresses.php" class="menu-item">
                        <i class="fas fa-map-marker-alt"></i>
                        Addresses
                    </a>
                    <a href="<?php echo SITE_URL; ?>/user/orders.php" class="menu-item">
                        <i class="fas fa-receipt"></i>
                        Order History
                    </a>
                    <a href="<?php echo SITE_URL; ?>/user/wishlist.php" class="menu-item">
                        <i class="fas fa-heart"></i>
                        Wishlist
                    </a>
                </nav>
            </div>
            
            <!-- Profile Content -->
            <div class="profile-content">
                <!-- Personal Information Tab -->
                <div class="tab-content active" id="profile-info">
                    <div class="content-card">
                        <h2>Personal Information</h2>
                        <p>Update your personal details and preferences</p>
                        
                        <form method="POST" class="profile-form" id="profileForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="firstName" class="form-label">First Name *</label>
                                    <input type="text" id="firstName" name="first_name" class="form-control" required
                                           value="<?php echo sanitizeInput($user['first_name']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="lastName" class="form-label">Last Name *</label>
                                    <input type="text" id="lastName" name="last_name" class="form-control" required
                                           value="<?php echo sanitizeInput($user['last_name']); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" id="email" class="form-control" 
                                       value="<?php echo sanitizeInput($user['email']); ?>" readonly>
                                <small class="form-help">Email address cannot be changed. Contact support if needed.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-control"
                                       value="<?php echo sanitizeInput($user['phone']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="dateOfBirth" class="form-label">Date of Birth</label>
                                <?php 
                                $dateValue = '';
                                if (isset($user['date_of_birth']) && $user['date_of_birth'] !== null && $user['date_of_birth'] !== '') {
                                    $dateValue = htmlspecialchars(trim($user['date_of_birth']));
                                }
                                ?>
                                <input type="date" id="dateOfBirth" name="date_of_birth" class="form-control"
                                       value="<?php echo $dateValue; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="newsletter_subscribed" value="1" 
                                           <?php echo $user['newsletter_subscribed'] ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Subscribe to newsletter for exclusive offers and updates
                                </label>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Enhanced Premium Profile Page Styles */
.profile-section {
    padding: var(--spacing-12) 0;
    background: linear-gradient(180deg, var(--gray-50) 0%, var(--white) 50%, var(--gray-50) 100%);
    min-height: 85vh;
}

.page-header {
    margin-bottom: var(--spacing-12);
    background: linear-gradient(135deg, var(--white) 0%, #fafbfc 100%);
    padding: var(--spacing-8);
    border-radius: var(--border-radius-xl);
    box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    border: 1px solid rgba(255,255,255,0.8);
    position: relative;
    overflow: hidden;
}

.page-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
}

.header-content h1 {
    font-size: var(--font-size-3xl);
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: var(--spacing-2);
    letter-spacing: -0.02em;
}

.header-content p {
    color: var(--gray-600);
    font-size: var(--font-size-lg);
    font-weight: 500;
    margin: 0;
}

.profile-layout {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: var(--spacing-10);
    align-items: start;
}

/* Enhanced Profile Sidebar */
.profile-sidebar {
    background: linear-gradient(135deg, var(--white) 0%, #fafbfc 100%);
    border-radius: var(--border-radius-xl);
    padding: var(--spacing-8);
    box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    border: 1px solid rgba(255,255,255,0.8);
    position: sticky;
    top: var(--spacing-8);
}

.profile-avatar {
    text-align: center;
    margin-bottom: var(--spacing-8);
    padding-bottom: var(--spacing-8);
    border-bottom: 2px solid var(--gray-100);
    position: relative;
}

.profile-avatar::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 2px;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
}

.avatar-image {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    font-weight: 700;
    margin: 0 auto var(--spacing-4);
    box-shadow: 0 15px 35px rgba(37, 99, 235, 0.3);
    border: 4px solid var(--white);
}

.profile-name {
    font-size: var(--font-size-xl);
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: var(--spacing-1);
}

.profile-email {
    color: var(--gray-600);
    font-size: var(--font-size-base);
    font-weight: 500;
}

.profile-nav {
    list-style: none;
}

.profile-nav li {
    margin-bottom: var(--spacing-2);
}

.profile-nav a {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    padding: var(--spacing-3) var(--spacing-4);
    color: var(--gray-600);
    text-decoration: none;
    border-radius: var(--border-radius-lg);
    transition: all 0.3s ease;
    font-weight: 500;
    position: relative;
    overflow: hidden;
}

.profile-nav a::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(37, 99, 235, 0.1), transparent);
    transition: left 0.3s ease;
}

.profile-nav a:hover::before {
    left: 100%;
}

.profile-nav a:hover,
.profile-nav a.active {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: var(--white);
    transform: translateX(5px);
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
}

.profile-nav i {
    width: 20px;
    text-align: center;
}

/* Enhanced Profile Content */
.profile-content {
    background: linear-gradient(135deg, var(--white) 0%, #fafbfc 100%);
    border-radius: var(--border-radius-xl);
    padding: var(--spacing-8);
    box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    border: 1px solid rgba(255,255,255,0.8);
}

.content-header {
    margin-bottom: var(--spacing-8);
    padding-bottom: var(--spacing-6);
    border-bottom: 2px solid var(--gray-100);
    position: relative;
}

.content-header::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 80px;
    height: 2px;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
}

.content-header h2 {
    font-size: var(--font-size-2xl);
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: var(--spacing-2);
    letter-spacing: -0.01em;
}

.content-header p {
    color: var(--gray-600);
    font-size: var(--font-size-base);
    margin: 0;
}

/* Enhanced Form Styles */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--spacing-6);
}

.form-group {
    margin-bottom: var(--spacing-6);
}

.form-group label {
    display: block;
    margin-bottom: var(--spacing-2);
    font-weight: 600;
    color: var(--gray-700);
    font-size: var(--font-size-sm);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: var(--spacing-4);
    border: 2px solid var(--gray-200);
    border-radius: var(--border-radius-lg);
    font-size: var(--font-size-base);
    transition: all 0.3s ease;
    background: var(--white);
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: var(--white);
    border: none;
    padding: var(--spacing-4) var(--spacing-8);
    border-radius: var(--border-radius-lg);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
}
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: var(--spacing-8);
}

.profile-nav {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-6);
}

.nav-card {
    background: var(--white);
    padding: var(--spacing-6);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    text-align: center;
}

.user-avatar-large {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, var(--primary-color), var(--info-color));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--white);
    font-size: var(--font-size-3xl);
    font-weight: 700;
    margin: 0 auto var(--spacing-4);
}

.user-info h3 {
    font-size: var(--font-size-xl);
    color: var(--gray-900);
    margin-bottom: var(--spacing-1);
}

.user-info p {
    color: var(--gray-600);
    margin-bottom: var(--spacing-2);
}

.member-since {
    font-size: var(--font-size-sm);
    color: var(--gray-500);
}

.profile-menu {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    padding: var(--spacing-4) var(--spacing-5);
    text-decoration: none;
    color: var(--gray-700);
    border-bottom: 1px solid var(--gray-100);
    transition: var(--transition-fast);
}

.menu-item:hover {
    background: var(--gray-50);
    color: var(--primary-color);
}

.menu-item.active {
    background: var(--primary-color);
    color: var(--white);
}

.menu-item:last-child {
    border-bottom: none;
}

.profile-content {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.content-card {
    padding: var(--spacing-8);
}

.content-card h2 {
    font-size: var(--font-size-2xl);
    color: var(--gray-900);
    margin-bottom: var(--spacing-2);
}

.content-card > p {
    color: var(--gray-600);
    margin-bottom: var(--spacing-8);
}

.profile-form,
.security-form {
    margin-bottom: var(--spacing-8);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-4);
}

.form-row .form-group {
    margin-bottom: 0;
}

.form-help {
    display: block;
    margin-top: var(--spacing-2);
    font-size: var(--font-size-sm);
    color: var(--gray-500);
}

.password-input {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--gray-400);
    cursor: pointer;
    padding: var(--spacing-2);
}

.password-toggle:hover {
    color: var(--gray-600);
}

.password-strength {
    margin-top: var(--spacing-2);
    font-size: var(--font-size-xs);
    height: 16px;
}

.strength-weak {
    color: var(--danger-color);
}

.strength-medium {
    color: var(--warning-color);
}

.strength-strong {
    color: var(--success-color);
}

.password-match {
    margin-top: var(--spacing-2);
    font-size: var(--font-size-xs);
    height: 16px;
}

.match-no {
    color: var(--danger-color);
}

.match-yes {
    color: var(--success-color);
}

.form-actions {
    padding-top: var(--spacing-6);
    border-top: 1px solid var(--gray-200);
}

.security-info {
    padding-top: var(--spacing-6);
    border-top: 1px solid var(--gray-200);
}

.security-info h3 {
    color: var(--gray-900);
    margin-bottom: var(--spacing-4);
}

.security-info ul {
    list-style: none;
    padding: 0;
}

.security-info li {
    padding: var(--spacing-2) 0;
    padding-left: var(--spacing-6);
    position: relative;
    color: var(--gray-600);
}

.security-info li::before {
    content: '•';
    position: absolute;
    left: 0;
    color: var(--primary-color);
    font-weight: bold;
}

/* Alert Styles */
.alert {
    padding: var(--spacing-4);
    border-radius: var(--border-radius);
    margin-bottom: var(--spacing-6);
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
}

.alert-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

.alert-success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .profile-layout {
        grid-template-columns: 1fr;
        gap: var(--spacing-6);
    }
    
    .profile-nav {
        order: 1;
    }
    
    .profile-content {
        order: 0;
    }
    
    .nav-card {
        display: flex;
        align-items: center;
        gap: var(--spacing-4);
        text-align: left;
    }
    
    .user-avatar-large {
        width: 80px;
        height: 80px;
        font-size: var(--font-size-2xl);
        margin: 0;
    }
    
    .profile-menu {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
    
    .menu-item {
        border-bottom: none;
        border-right: 1px solid var(--gray-100);
    }
    
    .menu-item:last-child {
        border-right: none;
    }
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .content-card {
        padding: var(--spacing-6);
    }
    
    .nav-card {
        flex-direction: column;
        text-align: center;
    }
    
    .profile-menu {
        grid-template-columns: 1fr;
    }
    
    .menu-item {
        border-right: none;
        border-bottom: 1px solid var(--gray-100);
    }
}
</style>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const toggle = input.nextElementSibling.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        toggle.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        toggle.className = 'fas fa-eye';
    }
}

// Tab switching
$(document).ready(function() {
    $('.menu-item[data-tab]').on('click', function(e) {
        e.preventDefault();
        
        const tabId = $(this).data('tab');
        
        // Update active menu item
        $('.menu-item').removeClass('active');
        $(this).addClass('active');
        
        // Show corresponding tab content
        $('.tab-content').removeClass('active');
        $('#' + tabId).addClass('active');
    });
    
    // Password strength checker
    $('#newPassword').on('input', function() {
        const password = $(this).val();
        const result = checkPasswordStrength(password);
        const strengthDiv = $('#passwordStrength');
        
        if (password.length === 0) {
            strengthDiv.html('');
            return;
        }
        
        let strengthText = '';
        let strengthClass = '';
        
        if (result.strength <= 2) {
            strengthText = 'Weak password';
            strengthClass = 'strength-weak';
        } else if (result.strength <= 3) {
            strengthText = 'Medium password';
            strengthClass = 'strength-medium';
        } else {
            strengthText = 'Strong password';
            strengthClass = 'strength-strong';
        }
        
        if (result.feedback.length > 0) {
            strengthText += ' (Missing: ' + result.feedback.join(', ') + ')';
        }
        
        strengthDiv.html(strengthText).removeClass().addClass(strengthClass);
    });
    
    // Password match checker
    $('#confirmPassword').on('input', function() {
        const password = $('#newPassword').val();
        const confirmPassword = $(this).val();
        const matchDiv = $('#passwordMatch');
        
        if (confirmPassword.length === 0) {
            matchDiv.html('');
            return;
        }
        
        if (password === confirmPassword) {
            matchDiv.html('Passwords match').removeClass().addClass('match-yes');
        } else {
            matchDiv.html('Passwords do not match').removeClass().addClass('match-no');
        }
    });
});

function checkPasswordStrength(password) {
    let strength = 0;
    let feedback = [];
    
    if (password.length >= 8) strength++;
    else feedback.push('At least 8 characters');
    
    if (/[a-z]/.test(password)) strength++;
    else feedback.push('Lowercase letter');
    
    if (/[A-Z]/.test(password)) strength++;
    else feedback.push('Uppercase letter');
    
    if (/[0-9]/.test(password)) strength++;
    else feedback.push('Number');
    
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    else feedback.push('Special character');
    
    return { strength, feedback };
}
</script>

<?php include '../includes/footer.php'; ?>
