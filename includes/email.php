<?php
/**
 * Email Configuration for Gmail SMTP
 */

// Gmail SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com'); // Replace with your Gmail
define('SMTP_PASSWORD', 'your-app-password'); // Replace with your Gmail App Password
define('SMTP_FROM_EMAIL', 'your-email@gmail.com'); // Replace with your Gmail
define('SMTP_FROM_NAME', 'HORAASTORE');

/**
 * Send email using Gmail SMTP
 */
function sendEmail($to, $subject, $body, $isHTML = true) {
    require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once 'vendor/phpmailer/phpmailer/src/SMTP.php';
    require_once 'vendor/phpmailer/phpmailer/src/Exception.php';
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate 6-digit OTP
 */
function generateOTP() {
    return sprintf("%06d", mt_rand(1, 999999));
}

/**
 * Store OTP in database
 */
function storeOTP($email, $otp, $type = 'registration') {
    global $db;
    
    if (!$db) {
        $db = Database::getInstance()->getConnection();
    }
    
    // Create OTP table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS otps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        otp VARCHAR(6) NOT NULL,
        type ENUM('registration', 'password_reset', 'login') DEFAULT 'registration',
        expires_at DATETIME NOT NULL,
        verified BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(email),
        INDEX(otp),
        INDEX(expires_at)
    )");
    
    // Delete old OTPs for this email and type
    $stmt = $db->prepare("DELETE FROM otps WHERE email = ? AND type = ?");
    $stmt->execute([$email, $type]);
    
    // Store new OTP (valid for 10 minutes)
    $expiresAt = date('Y-m-d H:i:s', time() + (10 * 60));
    $stmt = $db->prepare("INSERT INTO otps (email, otp, type, expires_at) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$email, $otp, $type, $expiresAt]);
}

/**
 * Verify OTP
 */
function verifyOTP($email, $otp, $type = 'registration') {
    global $db;
    
    if (!$db) {
        $db = Database::getInstance()->getConnection();
    }
    
    $stmt = $db->prepare("
        SELECT id FROM otps 
        WHERE email = ? AND otp = ? AND type = ? 
        AND expires_at > NOW() AND verified = FALSE
    ");
    $stmt->execute([$email, $otp, $type]);
    
    if ($stmt->fetch()) {
        // Mark OTP as verified
        $stmt = $db->prepare("UPDATE otps SET verified = TRUE WHERE email = ? AND otp = ? AND type = ?");
        $stmt->execute([$email, $otp, $type]);
        return true;
    }
    
    return false;
}

/**
 * Send OTP email for registration
 */
function sendRegistrationOTP($email, $firstName = '') {
    $otp = generateOTP();
    
    if (storeOTP($email, $otp, 'registration')) {
        $subject = "Verify Your Email - " . SITE_NAME;
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Email Verification</title>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .otp-box { background: #f8fafc; border: 2px dashed #3b82f6; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
                .otp { font-size: 32px; font-weight: bold; color: #2563eb; letter-spacing: 8px; }
                .footer { background: #f8fafc; padding: 20px; text-align: center; color: #64748b; font-size: 14px; }
                .btn { display: inline-block; background: #3b82f6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📧 Email Verification</h1>
                    <p>Welcome to " . SITE_NAME . "!</p>
                </div>
                
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($firstName) . "!</h2>
                    <p>Thank you for registering with " . SITE_NAME . ". To complete your registration, please verify your email address using the OTP below:</p>
                    
                    <div class='otp-box'>
                        <p style='margin: 0; color: #64748b;'>Your verification code is:</p>
                        <div class='otp'>" . $otp . "</div>
                        <p style='margin: 5px 0 0 0; color: #64748b; font-size: 14px;'>This code expires in 10 minutes</p>
                    </div>
                    
                    <p>If you didn't create an account with " . SITE_NAME . ", please ignore this email.</p>
                    
                    <p>For security reasons, please do not share this code with anyone.</p>
                </div>
                
                <div class='footer'>
                    <p>© " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
                    <p>This is an automated email, please do not reply.</p>
                </div>
            </div>
        </body>
        </html>";
        
        return sendEmail($email, $subject, $body);
    }
    
    return false;
}

/**
 * Check if OTP is required for email
 */
function isOTPVerified($email, $type = 'registration') {
    global $db;
    
    if (!$db) {
        $db = Database::getInstance()->getConnection();
    }
    
    $stmt = $db->prepare("
        SELECT id FROM otps 
        WHERE email = ? AND type = ? AND verified = TRUE 
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$email, $type]);
    
    return $stmt->fetch() !== false;
}
?>
