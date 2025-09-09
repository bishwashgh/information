<?php
/**
 * WORKING Email Verification Page - Clean Design
 */
session_start();
require_once 'includes/config.php';
require_once 'includes/email_production.php';

$pageTitle = 'Verify Email';
$pageDescription = 'Verify your email address with OTP';

$error = '';
$success = '';
$email = '';
$registration_complete = false;

// Get email from URL parameter if provided
if (isset($_GET['email'])) {
    $email = sanitizeInput($_GET['email']);
}

// Setup session if needed (for testing)
if (!isset($_SESSION['pending_registration']) && !empty($email)) {
    $_SESSION['pending_registration'] = [
        'firstName' => 'Test',
        'lastName' => 'User',
        'email' => $email,
        'phone' => '1234567890',
        'password_hash' => password_hash('testpass123', PASSWORD_DEFAULT),
        'agreeTerms' => true,
        'newsletter' => false,
        'timestamp' => time()
    ];
}

// Check if there's a pending registration
if (isset($_SESSION['pending_registration'])) {
    $email = $_SESSION['pending_registration']['email'];
}

// WORKING FORM SUBMISSION LOGIC (FROM EMERGENCY VERSION)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $posted_email = trim($_POST['email'] ?? '');
    $posted_otp = trim($_POST['otp'] ?? '');
    
    if (empty($posted_email) || empty($posted_otp)) {
        $error = 'Please enter both email and OTP';
    } elseif (!filter_var($posted_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($posted_otp) !== 6 || !ctype_digit($posted_otp)) {
        $error = 'Please enter a valid 6-digit OTP';
    } else {
        $checkResult = checkOTP($posted_email, $posted_otp, 'registration');
        
        if ($checkResult) {
            if (isset($_SESSION['pending_registration'])) {
                try {
                    $regData = $_SESSION['pending_registration'];
                    $db = Database::getInstance()->getConnection();
                    $db->beginTransaction();
                    
                    // Check if user exists
                    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$posted_email]);
                    $existingUser = $stmt->fetch();
                    
                    if ($existingUser) {
                        $userId = $existingUser['id'];
                    } else {
                        $stmt = $db->prepare("
                            INSERT INTO users (first_name, last_name, email, password, phone, status, role, email_verified, created_at)
                            VALUES (?, ?, ?, ?, ?, 'active', 'customer', 1, NOW())
                        ");
                        $stmt->execute([
                            $regData['firstName'],
                            $regData['lastName'],
                            $posted_email,
                            $regData['password_hash'],
                            $regData['phone']
                        ]);
                        
                        $userId = $db->lastInsertId();
                    }
                    
                    $db->commit();
                    
                    // Mark OTP as used
                    markOTPAsUsed($posted_email, $posted_otp, 'registration');
                    
                    // Success
                    $success = "🎉 Registration completed successfully! Your account has been created and verified.";
                    $registration_complete = true;
                    
                    // Clean up
                    unset($_SESSION['pending_registration']);
                    
                } catch (Exception $e) {
                    if (isset($db)) $db->rollback();
                    $error = 'Registration failed: ' . $e->getMessage();
                }
            } else {
                $error = 'No session data found';
            }
        } else {
            $error = 'Invalid or expired OTP';
        }
    }
}

// Handle resend OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_otp'])) {
    $email = sanitizeInput($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        $firstName = isset($_SESSION['pending_registration']['firstName']) ? $_SESSION['pending_registration']['firstName'] : 'User';
        
        if (sendRegistrationOTP($email, $firstName)) {
            $success = 'A new OTP has been sent to your email address.';
        } else {
            $error = 'Failed to send OTP. Please try again.';
        }
    }
}

// Get current OTP for debugging
$current_otp = '';
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT otp_code FROM email_otps WHERE email = ? AND purpose = 'registration' AND used = 0 ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$email]);
    $otp_data = $stmt->fetch();
    if ($otp_data) {
        $current_otp = $otp_data['otp_code'];
    }
} catch (Exception $e) {
    // ignore
}
?>

<?php include 'includes/header.php'; ?>

<style>
/* Clean, Simple Styling - No Complex Animations */
.auth-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    padding: 20px 0;
}

.auth-wrapper {
    max-width: 500px;
    margin: 0 auto;
    width: 100%;
}

.auth-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.auth-header {
    text-align: center;
    padding: 40px 30px 20px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.auth-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 24px;
}

.success-icon {
    background: rgba(40, 167, 69, 0.2);
}

.auth-header h1 {
    margin: 0 0 10px;
    font-size: 1.8rem;
    font-weight: 700;
}

.auth-header p {
    margin: 0;
    font-size: 1rem;
    opacity: 0.9;
}

.auth-form {
    padding: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 16px;
    box-sizing: border-box;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
}

.otp-input {
    text-align: center;
    font-size: 20px;
    font-weight: 700;
    letter-spacing: 4px;
    font-family: monospace;
}

.btn {
    padding: 12px 25px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-full {
    width: 100%;
}

.btn-link {
    background: none;
    color: #667eea;
    border: none;
    padding: 10px;
    font-size: 14px;
    cursor: pointer;
}

.btn-link:hover {
    color: #764ba2;
    text-decoration: underline;
}

.alert {
    padding: 15px;
    border-radius: 8px;
    margin: 15px 0;
    font-weight: 500;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.success-actions {
    padding: 30px;
    text-align: center;
}

.auth-links {
    text-align: center;
    padding: 20px 30px;
    border-top: 1px solid #e9ecef;
}

@media (max-width: 768px) {
    .auth-card {
        margin: 20px;
    }
    .auth-form, .success-actions {
        padding: 20px;
    }
}
</style>

<section class="auth-section">
    <div class="container">
        <div class="auth-wrapper">
            <div class="auth-card">
                <?php if ($registration_complete): ?>
                <!-- Registration Success View -->
                <div class="auth-header">
                    <div class="auth-icon success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h1>🎉 Registration Successful!</h1>
                    <p>Your account has been created and verified successfully.</p>
                </div>
                
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
                
                <div class="success-actions">
                    <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary btn-full">
                        <i class="fas fa-sign-in-alt"></i>
                        Continue to Login
                    </a>
                    
                    <div style="margin-top: 20px; padding: 15px; background: rgba(40, 167, 69, 0.1); border-radius: 8px; color: #155724;">
                        <p><i class="fas fa-info-circle"></i> You can now log in with your email and password to start shopping!</p>
                    </div>
                </div>
                
                <?php else: ?>
                <!-- OTP Verification View -->
                <div class="auth-header">
                    <div class="auth-icon">
                        <i class="fas fa-envelope-open"></i>
                    </div>
                    <h1>Verify Your Email</h1>
                    <p>We've sent a 6-digit verification code to <strong><?php echo htmlspecialchars($email); ?></strong></p>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success && !$registration_complete): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
                <?php endif; ?>
                
                <!-- Debug info for testing -->
                <?php if (isset($_GET['debug'])): ?>
                <div class="alert alert-info">
                    <strong>Debug Info:</strong><br>
                    Current OTP: <code><?php echo $current_otp; ?></code><br>
                    Session exists: <?php echo isset($_SESSION['pending_registration']) ? 'YES' : 'NO'; ?><br>
                    Registration complete: <?php echo $registration_complete ? 'YES' : 'NO'; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="auth-form">
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i>
                            Email Address
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($email); ?>" 
                               readonly
                               style="background-color: #f8f9fa;">
                    </div>
                    
                    <div class="form-group">
                        <label for="otp" class="form-label">
                            <i class="fas fa-key"></i>
                            Verification Code
                        </label>
                        <input type="text" 
                               id="otp" 
                               name="otp" 
                               class="form-control otp-input" 
                               maxlength="6" 
                               placeholder="Enter 6-digit code"
                               value="<?php echo isset($_GET['debug']) ? $current_otp : ''; ?>"
                               required>
                        <small style="color: #6c757d; font-size: 0.9rem;">Enter the 6-digit code sent to your email</small>
                    </div>
                    
                    <button type="submit" name="verify_otp" value="1" class="btn btn-primary btn-full">
                        <i class="fas fa-check"></i>
                        Verify Email & Complete Registration
                    </button>
                </form>
                
                <div class="auth-links">
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                        <button type="submit" name="resend_otp" value="1" class="btn-link">
                            <i class="fas fa-redo"></i>
                            Didn't receive the code? Resend
                        </button>
                    </form>
                </div>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Simple JavaScript - NO Complex Animations -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const otpInput = document.getElementById('otp');
    
    // Auto-focus OTP input
    if (otpInput && !otpInput.value) {
        otpInput.focus();
    }
    
    // Only allow digits
    otpInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
});
</script>

<?php include 'includes/footer.php'; ?>
