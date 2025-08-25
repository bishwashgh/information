<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/user/dashboard.php');
    exit;
}

$pageTitle = 'Forgot Password';
$pageDescription = 'Reset your password';

$error = '';
$success = '';
$step = $_GET['step'] ?? 'email';

// Handle email submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reset'])) {
    $email = sanitizeInput($_POST['email'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT id, first_name FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $stmt = $db->prepare("
                INSERT INTO password_resets (email, token, expires_at, created_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                token = VALUES(token), 
                expires_at = VALUES(expires_at), 
                created_at = NOW()
            ");
            $stmt->execute([$email, hash('sha256', $token), $expires]);
            
            // Send reset email (simulated)
            $resetLink = SITE_URL . '/reset-password.php?token=' . $token;
            sendPasswordResetEmail($email, $user['first_name'], $resetLink);
            
            $success = 'Password reset instructions have been sent to your email address.';
        } else {
            // Don't reveal if email exists or not for security
            $success = 'If an account with that email exists, password reset instructions have been sent.';
        }
    }
}

function sendPasswordResetEmail($email, $firstName, $resetLink) {
    // In a real application, send actual email
    error_log("Password reset email sent to {$email}: {$resetLink}");
    return true;
}
?>

<?php include 'includes/header.php'; ?>

<!-- Forgot Password Section -->
<section class="auth-section">
    <div class="container">
        <div class="auth-container-single">
            <div class="auth-form-container">
                <div class="auth-header">
                    <h1>Forgot Password</h1>
                    <p>Enter your email address and we'll send you instructions to reset your password</p>
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
                
                <?php if (!$success): ?>
                <form method="POST" class="auth-form" id="forgotPasswordForm">
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" required
                               value="<?php echo isset($_POST['email']) ? sanitizeInput($_POST['email']) : ''; ?>"
                               placeholder="Enter your email address">
                    </div>
                    
                    <button type="submit" name="send_reset" class="btn btn-primary btn-lg btn-block">
                        <i class="fas fa-paper-plane"></i>
                        Send Reset Instructions
                    </button>
                </form>
                <?php endif; ?>
                
                <div class="auth-footer">
                    <p>Remember your password? <a href="<?php echo SITE_URL; ?>/login.php">Sign in here</a></p>
                    <p>Don't have an account? <a href="<?php echo SITE_URL; ?>/register.php">Sign up here</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.auth-container-single {
    max-width: 500px;
    margin: 0 auto;
    background: var(--white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
}

.auth-container-single .auth-form-container {
    padding: var(--spacing-8);
    display: flex;
    flex-direction: column;
    justify-content: center;
}

@media (max-width: 768px) {
    .auth-container-single {
        margin: var(--spacing-4);
    }
    
    .auth-container-single .auth-form-container {
        padding: var(--spacing-6);
    }
}
</style>

<script>
$(document).ready(function() {
    $('#forgotPasswordForm').on('submit', function(e) {
        const email = $('#email').val();
        
        if (!email) {
            e.preventDefault();
            showToast('Please enter your email address', 'error');
            return;
        }
        
        if (!isValidEmail(email)) {
            e.preventDefault();
            showToast('Please enter a valid email address', 'error');
            return;
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
