<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/user/dashboard.php');
    exit;
}

$pageTitle = 'Login';
$pageDescription = 'Login to your account';

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Update last login
            $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['role'] = $user['role'];
            
            // Handle remember me
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                $stmt = $db->prepare("INSERT INTO user_sessions (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], hash('sha256', $token), $expires]);
                
                setcookie('remember_token', $token, strtotime('+30 days'), '/', '', true, true);
            }
            
            // Merge guest cart if exists
            if (isset($_SESSION['guest_cart_merged']) && !$_SESSION['guest_cart_merged']) {
                // Check if function exists before calling it
                if (function_exists('mergeGuestCart')) {
                    mergeGuestCart($user['id']);
                } else {
                    // Simple cart merge for guest session to user
                    mergeGuestCartToUser($user['id']);
                }
                $_SESSION['guest_cart_merged'] = true;
            }
            
            // Redirect
            $redirect = $_GET['redirect'] ?? '/user/dashboard.php';
            header('Location: ' . SITE_URL . $redirect);
            exit;
        } else {
            $error = 'Invalid email or password';
        }
    }
}

// Function to merge guest cart items to user account
function mergeGuestCartToUser($userId) {
    global $db;
    
    try {
        // Get guest cart items by session_id
        $sessionId = session_id();
        $stmt = $db->prepare("
            SELECT product_id, quantity, attributes 
            FROM cart 
            WHERE session_id = ? AND user_id IS NULL
        ");
        $stmt->execute([$sessionId]);
        $guestItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($guestItems)) {
            // Merge each item to user's cart
            foreach ($guestItems as $item) {
                $stmt = $db->prepare("
                    INSERT INTO cart (user_id, product_id, quantity, attributes, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
                ");
                $stmt->execute([
                    $userId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['attributes'] ?? '{}'
                ]);
            }
            
            // Remove guest cart items
            $stmt = $db->prepare("DELETE FROM cart WHERE session_id = ? AND user_id IS NULL");
            $stmt->execute([$sessionId]);
        }
    } catch (Exception $e) {
        // Log error but don't break login process
        error_log("Error merging guest cart: " . $e->getMessage());
    }
}
?>

<?php include 'includes/header.php'; ?>

<!-- Login Section -->
<section class="auth-section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-form">
                <div class="auth-form-wrapper">
                <div class="auth-header">
                    <h1>Welcome Back</h1>
                    <p>Sign in to your account to continue shopping</p>
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
                
                <form method="POST" class="auth-form" id="loginForm">
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" required
                               value="<?php echo isset($_POST['email']) ? sanitizeInput($_POST['email']) : ''; ?>"
                               placeholder="Enter your email">
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" class="form-control" required
                                   placeholder="Enter your password">
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember" value="1">
                            <span class="checkmark"></span>
                            Remember me
                        </label>
                        
                        <a href="<?php echo SITE_URL; ?>/forgot-password.php" class="forgot-link">
                            Forgot Password?
                        </a>
                    </div>
                    
                    <button type="submit" name="login" class="btn btn-primary btn-lg btn-block">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>
                </form>
                
                <div class="auth-divider">
                    <span>or</span>
                </div>
                
                <div class="social-login">
                    <button type="button" class="btn btn-outline btn-block social-btn" onclick="loginWithGoogle()">
                        <i class="fab fa-google"></i>
                        Continue with Google
                    </button>
                    
                    <button type="button" class="btn btn-outline btn-block social-btn" onclick="loginWithFacebook()">
                        <i class="fab fa-facebook-f"></i>
                        Continue with Facebook
                    </button>
                </div>
                
                <div class="auth-footer">
                    <p>Don't have an account? <a href="<?php echo SITE_URL; ?>/register.php">Sign up here</a></p>
                </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.auth-section {
    padding: var(--spacing-8) 0;
    background: var(--gray-50);
    min-height: calc(100vh - 80px);
}

.auth-container {
    max-width: 500px;
    margin: 0 auto;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 600px;
}

.auth-image {
    position: relative;
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    height: 600px;
}

.auth-bg-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.auth-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.9), rgba(168, 85, 247, 0.9));
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    padding: var(--spacing-8);
    color: white;
}

.auth-overlay h2 {
    font-size: var(--font-size-3xl);
    margin-bottom: var(--spacing-4);
    font-weight: 700;
}

.auth-overlay p {
    font-size: var(--font-size-lg);
    opacity: 0.9;
    max-width: 400px;
}

.auth-form-wrapper {
    background: var(--white);
    padding: var(--spacing-8);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-lg);
}

.auth-header {
    text-align: center;
    margin-bottom: var(--spacing-8);
}

.auth-header h1 {
    font-size: var(--font-size-2xl);
    color: var(--gray-900);
    margin-bottom: var(--spacing-2);
    font-weight: 700;
}

.auth-header p {
    color: var(--gray-600);
    font-size: var(--font-size-base);
}

.auth-form {
    margin-bottom: var(--spacing-6);
}

.form-group {
    margin-bottom: var(--spacing-6);
}

.form-label {
    display: block;
    margin-bottom: var(--spacing-2);
    font-weight: 600;
    color: var(--gray-700);
    font-size: var(--font-size-sm);
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid var(--gray-200);
    border-radius: var(--border-radius);
    font-size: var(--font-size-base);
    transition: all 0.2s ease;
    background: var(--white);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
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
    border-radius: var(--border-radius);
    transition: all 0.2s ease;
}

.password-toggle:hover {
    color: var(--gray-600);
    background: var(--gray-100);
}

.form-options {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--spacing-6);
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-size: var(--font-size-sm);
    color: var(--gray-600);
}

.checkbox-label input[type="checkbox"] {
    margin-right: var(--spacing-2);
}

.forgot-link {
    color: var(--primary-color);
    text-decoration: none;
    font-size: var(--font-size-sm);
    font-weight: 500;
}

.forgot-link:hover {
    text-decoration: underline;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 12px 24px;
    border: none;
    border-radius: var(--border-radius);
    font-size: var(--font-size-base);
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    gap: var(--spacing-2);
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.btn-outline {
    background: transparent;
    border: 2px solid var(--gray-200);
    color: var(--gray-700);
}

.btn-outline:hover {
    border-color: var(--primary-color);
    background: var(--primary-color);
    color: white;
}

.btn-lg {
    padding: 16px 32px;
    font-size: var(--font-size-lg);
}

.btn-block {
    width: 100%;
}

.auth-divider {
    text-align: center;
    margin: var(--spacing-6) 0;
    position: relative;
}

.auth-divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: var(--gray-200);
}

.auth-divider span {
    background: var(--white);
    padding: 0 var(--spacing-4);
    color: var(--gray-500);
    font-size: var(--font-size-sm);
}

.social-login {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-3);
    margin-bottom: var(--spacing-6);
}

.social-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-3);
}

.auth-footer {
    text-align: center;
    padding-top: var(--spacing-6);
    border-top: 1px solid var(--gray-200);
}

.auth-footer p {
    color: var(--gray-600);
    font-size: var(--font-size-sm);
}

.auth-footer a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 600;
}

.auth-footer a:hover {
    text-decoration: underline;
}

.alert {
    padding: var(--spacing-4);
    border-radius: var(--border-radius);
    margin-bottom: var(--spacing-6);
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
}

.alert-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
}

.alert-success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #16a34a;
}

/* Responsive Design */
@media (max-width: 968px) {
    .auth-container {
        grid-template-columns: 1fr;
        gap: var(--spacing-6);
    }
    
    .auth-image {
        height: 300px;
        order: -1;
    }
}

@media (max-width: 640px) {
    .auth-section {
        padding: var(--spacing-4) 0;
    }
    
    .auth-form-wrapper {
        padding: var(--spacing-6);
    }
    
    .auth-header h1 {
        font-size: var(--font-size-xl);
    }
    
    .form-options {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--spacing-3);
    }
}
</style>
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

function loginWithGoogle() {
    // In a real application, integrate with Google OAuth
    showToast('Google login would be integrated here', 'info');
}

function loginWithFacebook() {
    // In a real application, integrate with Facebook login
    showToast('Facebook login would be integrated here', 'info');
}

// Form validation
$(document).ready(function() {
    $('#loginForm').on('submit', function(e) {
        const email = $('#email').val();
        const password = $('#password').val();
        
        if (!email || !password) {
            e.preventDefault();
            showToast('Please fill in all fields', 'error');
            return;
        }
        
        if (!isValidEmail(email)) {
            e.preventDefault();
            showToast('Please enter a valid email address', 'error');
            return;
        }
    });
});

function mergeGuestCart(userId) {
    $.ajax({
        url: window.siteUrl + '/api/cart.php',
        method: 'POST',
        data: {
            action: 'merge_guest_cart',
            user_id: userId,
            csrf_token: window.csrfToken
        },
        success: function(response) {
            if (response.success) {
                updateCartCount(response.cart_count);
            }
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
