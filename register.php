<?php
require_once 'includes/config.php';
require_once 'includes/email_production.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/user/dashboard.php');
    exit;
}

$pageTitle = 'Register';
$pageDescription = 'Create your account';

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $agreeTerms = isset($_POST['agree_terms']);
    $newsletter = isset($_POST['newsletter']);
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (!$agreeTerms) {
        $error = 'Please agree to the terms and conditions';
    } else {
        $db = Database::getInstance()->getConnection();
        
        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists';
        } else {
            try {
                $db = Database::getInstance()->getConnection();
                
                // Hash password for database storage
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Store registration data in both session and database for reliability
                $_SESSION['pending_registration'] = [
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => $password,
                    'password_hash' => $hashedPassword, // Add hashed password to session
                    'agreeTerms' => $agreeTerms,
                    'newsletter' => $newsletter,
                    'timestamp' => time()
                ];
                
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour')); // 1 hour expiry
                
                // Clean up any existing pending registration for this email
                $stmt = $db->prepare("DELETE FROM pending_registrations WHERE email = ?");
                $stmt->execute([$email]);
                
                // Store in database as backup
                $stmt = $db->prepare("
                    INSERT INTO pending_registrations 
                    (email, first_name, last_name, phone, password_hash, agree_terms, newsletter, expires_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $email, 
                    $firstName, 
                    $lastName, 
                    $phone, 
                    $hashedPassword, 
                    $agreeTerms ? 1 : 0, 
                    $newsletter ? 1 : 0, 
                    $expiresAt
                ]);
                
                // Send OTP to email
                if (sendRegistrationOTP($email, $firstName)) {
                    // Redirect to OTP verification page
                    header('Location: ' . SITE_URL . '/verify-email.php?email=' . urlencode($email));
                    exit;
                } else {
                    $error = 'Failed to send verification email. Please try again.';
                }
            } catch (Exception $e) {
                error_log("Registration error: " . $e->getMessage());
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

function sendWelcomeEmail($email, $firstName) {
    // In a real application, send actual welcome email
    error_log("Welcome email sent to {$email} for {$firstName}");
    return true;
}

function mergeGuestCartItems($userId) {
    // Merge guest cart items to user account
    if (isset($_SESSION['guest_cart_items']) && is_array($_SESSION['guest_cart_items'])) {
        $db = Database::getInstance()->getConnection();
        
        foreach ($_SESSION['guest_cart_items'] as $item) {
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
        
        unset($_SESSION['guest_cart_items']);
    }
}
?>

<?php include 'includes/header.php'; ?>

<!-- Registration Section -->
<section class="auth-section">
    <div class="container">
        <div class="auth-container-centered">
            <div class="auth-form-wrapper">
                <div class="auth-header">
                    <div class="auth-logo">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h1>Create Account</h1>
                    <p>Join us and start your shopping journey</p>
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
                
                <form method="POST" class="auth-form" id="registerForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName" class="form-label">First Name *</label>
                            <input type="text" id="firstName" name="first_name" class="form-control" required
                                   value="<?php echo isset($_POST['first_name']) ? sanitizeInput($_POST['first_name']) : ''; ?>"
                                   placeholder="Enter your first name">
                        </div>
                        
                        <div class="form-group">
                            <label for="lastName" class="form-label">Last Name *</label>
                            <input type="text" id="lastName" name="last_name" class="form-control" required
                                   value="<?php echo isset($_POST['last_name']) ? sanitizeInput($_POST['last_name']) : ''; ?>"
                                   placeholder="Enter your last name">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control" required
                               value="<?php echo isset($_POST['email']) ? sanitizeInput($_POST['email']) : ''; ?>"
                               placeholder="Enter your email">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control"
                               value="<?php echo isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : ''; ?>"
                               placeholder="Enter your phone number">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password" class="form-label">Password *</label>
                            <div class="password-input">
                                <input type="password" id="password" name="password" class="form-control" required
                                       placeholder="Create a password">
                                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength" id="passwordStrength"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmPassword" class="form-label">Confirm Password *</label>
                            <div class="password-input">
                                <input type="password" id="confirmPassword" name="confirm_password" class="form-control" required
                                       placeholder="Confirm your password">
                                <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-match" id="passwordMatch"></div>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="newsletter" value="1" 
                                   <?php echo isset($_POST['newsletter']) ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Subscribe to newsletter for exclusive offers
                        </label>
                        
                        <label class="checkbox-label required">
                            <input type="checkbox" name="agree_terms" value="1" required>
                            <span class="checkmark"></span>
                            I agree to the <a href="<?php echo SITE_URL; ?>/terms.php" target="_blank">Terms & Conditions</a> 
                            and <a href="<?php echo SITE_URL; ?>/privacy.php" target="_blank">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <button type="submit" name="register" class="btn btn-primary btn-lg btn-block">
                        <i class="fas fa-user-plus"></i>
                        Create Account
                    </button>
                </form>
                
                <div class="auth-divider">
                    <span>or</span>
                </div>
                
                <div class="social-login">
                    <button type="button" class="btn btn-outline btn-block social-btn" onclick="registerWithGoogle()">
                        <i class="fab fa-google"></i>
                        Sign up with Google
                    </button>
                    
                    <button type="button" class="btn btn-outline btn-block social-btn" onclick="registerWithFacebook()">
                        <i class="fab fa-facebook-f"></i>
                        Sign up with Facebook
                    </button>
                </div>
                
                <div class="auth-footer">
                    <p>Already have an account? <a href="<?php echo SITE_URL; ?>/login.php">Sign in here</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Centered Auth Styles */
.auth-section {
    padding: var(--spacing-8) 0;
    background: white;
    min-height: 100vh;
    display: flex;
    align-items: center;
}

.auth-container-centered {
    max-width: 600px;
    margin: 0 auto;
    width: 100%;
}

.auth-form-wrapper {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    padding: var(--spacing-8);
    backdrop-filter: blur(10px);
}

.auth-header {
    text-align: center;
    margin-bottom: var(--spacing-8);
}

.auth-logo {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto var(--spacing-4);
    font-size: 24px;
    color: white;
}

.auth-header h1 {
    font-size: var(--font-size-3xl);
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

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-6);
}

.form-row .form-group {
    margin-bottom: 0;
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

.form-options {
    margin-bottom: var(--spacing-6);
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    cursor: pointer;
    font-size: var(--font-size-sm);
    color: var(--gray-600);
    margin-bottom: var(--spacing-3);
    line-height: 1.5;
}

.checkbox-label input[type="checkbox"] {
    margin-right: var(--spacing-2);
    margin-top: 2px;
}

.checkbox-label.required {
    margin-top: var(--spacing-3);
}

.checkbox-label a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
}

.checkbox-label a:hover {
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
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
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
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .form-row .form-group {
        margin-bottom: var(--spacing-6);
    }
}

@media (max-width: 640px) {
    .auth-section {
        padding: var(--spacing-4) 0;
    }
    
    .auth-form-wrapper {
        margin: var(--spacing-4);
        padding: var(--spacing-6);
    }
    
    .auth-header h1 {
        font-size: var(--font-size-2xl);
    }
    
    .auth-container-centered {
        max-width: 100%;
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

function registerWithGoogle() {
    showToast('Google registration would be integrated here', 'info');
}

function registerWithFacebook() {
    showToast('Facebook registration would be integrated here', 'info');
}

// Password strength checker
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

// Form validation and interactions
$(document).ready(function() {
    // Password strength indicator
    $('#password').on('input', function() {
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
        const password = $('#password').val();
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
    
    // Form submission validation
    $('#registerForm').on('submit', function(e) {
        const firstName = $('#firstName').val().trim();
        const lastName = $('#lastName').val().trim();
        const email = $('#email').val().trim();
        const password = $('#password').val();
        const confirmPassword = $('#confirmPassword').val();
        const agreeTerms = $('input[name="agree_terms"]').is(':checked');
        
        let errors = [];
        
        if (!firstName) errors.push('First name is required');
        if (!lastName) errors.push('Last name is required');
        if (!email) errors.push('Email is required');
        if (!isValidEmail(email)) errors.push('Valid email is required');
        if (password.length < 8) errors.push('Password must be at least 8 characters');
        if (password !== confirmPassword) errors.push('Passwords must match');
        if (!agreeTerms) errors.push('You must agree to the terms and conditions');
        
        if (errors.length > 0) {
            e.preventDefault();
            showToast(errors.join('<br>'), 'error');
            return false;
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
