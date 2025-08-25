<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/user/dashboard.php');
    exit;
}

$pageTitle = 'Reset Password';
$pageDescription = 'Set your new password';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: ' . SITE_URL . '/forgot-password.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Verify token
$stmt = $db->prepare("
    SELECT email FROM password_resets 
    WHERE token = ? AND expires_at > NOW() AND used_at IS NULL
");
$stmt->execute([hash('sha256', $token)]);
$resetData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$resetData) {
    $error = 'Invalid or expired reset token. Please request a new password reset.';
} else {
    // Handle password reset form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($password) || empty($confirmPassword)) {
            $error = 'Please fill in all fields';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
        } else {
            try {
                $db->beginTransaction();
                
                // Update user password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    UPDATE users 
                    SET password = ?, updated_at = NOW()
                    WHERE email = ?
                ");
                $stmt->execute([$hashedPassword, $resetData['email']]);
                
                // Mark reset token as used
                $stmt = $db->prepare("
                    UPDATE password_resets 
                    SET used_at = NOW()
                    WHERE token = ?
                ");
                $stmt->execute([hash('sha256', $token)]);
                
                $db->commit();
                
                $success = 'Your password has been reset successfully. You can now sign in with your new password.';
                
            } catch (Exception $e) {
                $db->rollBack();
                error_log("Password reset error: " . $e->getMessage());
                $error = 'Failed to reset password. Please try again.';
            }
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<!-- Reset Password Section -->
<section class="auth-section">
    <div class="container">
        <div class="auth-container-single">
            <div class="auth-form-container">
                <div class="auth-header">
                    <h1>Reset Password</h1>
                    <p>Enter your new password below</p>
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
                <div class="auth-footer">
                    <p><a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary">Sign In Now</a></p>
                </div>
                <?php elseif ($resetData): ?>
                <form method="POST" class="auth-form" id="resetPasswordForm">
                    <div class="form-group">
                        <label for="password" class="form-label">New Password</label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" class="form-control" required
                                   placeholder="Enter your new password">
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmPassword" class="form-label">Confirm New Password</label>
                        <div class="password-input">
                            <input type="password" id="confirmPassword" name="confirm_password" class="form-control" required
                                   placeholder="Confirm your new password">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-match" id="passwordMatch"></div>
                    </div>
                    
                    <button type="submit" name="reset_password" class="btn btn-primary btn-lg btn-block">
                        <i class="fas fa-key"></i>
                        Reset Password
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>Remember your password? <a href="<?php echo SITE_URL; ?>/login.php">Sign in here</a></p>
                </div>
                <?php else: ?>
                <div class="auth-footer">
                    <p><a href="<?php echo SITE_URL; ?>/forgot-password.php" class="btn btn-primary">Request New Reset</a></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
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
    
    // Form validation
    $('#resetPasswordForm').on('submit', function(e) {
        const password = $('#password').val();
        const confirmPassword = $('#confirmPassword').val();
        
        if (!password || !confirmPassword) {
            e.preventDefault();
            showToast('Please fill in all fields', 'error');
            return;
        }
        
        if (password.length < 8) {
            e.preventDefault();
            showToast('Password must be at least 8 characters long', 'error');
            return;
        }
        
        if (password !== confirmPassword) {
            e.preventDefault();
            showToast('Passwords do not match', 'error');
            return;
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
