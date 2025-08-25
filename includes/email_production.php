<?php
/**
 * Production Email System with Gmail SMTP and Fallback Support
 */

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $envFile = file_get_contents(__DIR__ . '/../.env');
    $lines = explode("\n", $envFile);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Load PHPMailer if available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

/**
 * Send email using PHPMailer with fallback to PHP mail()
 */
function sendProductionEmail($to, $subject, $body, $isHTML = true) {
    // Ensure environment is loaded
    if (!isset($_ENV['SMTP_PASS']) || empty($_ENV['SMTP_PASS'])) {
        // Manually load .env if not already loaded
        if (file_exists(__DIR__ . '/../.env')) {
            $envFile = file_get_contents(__DIR__ . '/../.env');
            $lines = explode("\n", $envFile);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value);
                }
            }
        }
    }
    
    // Try PHPMailer first
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER'] ?? 'bishwasghimire2060@gmail.com';
            $mail->Password   = $_ENV['SMTP_PASS'] ?? 'clxygljjpmuvkhcr';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $_ENV['SMTP_PORT'] ?? 587;
            
            // Recipients
            $mail->setFrom($_ENV['SMTP_FROM'] ?? 'bishwasghimire2060@gmail.com', $_ENV['SMTP_FROM_NAME'] ?? 'HORAASTORE');
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML($isHTML);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            
            $mail->send();
            error_log("PHPMailer email sent successfully to: $to");
            return true;
            
        } catch (Exception $e) {
            error_log("PHPMailer error: " . $e->getMessage());
            // Fall through to PHP mail() fallback
        }
    }
    
    // Fallback: Use PHP's built-in mail()
    $fromEmail = $_ENV['SMTP_FROM'] ?? 'bishwasghimire2060@gmail.com';
    $fromName = $_ENV['SMTP_FROM_NAME'] ?? 'HORAASTORE';
    
    $headers = [];
    $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
    $headers[] = 'Reply-To: ' . $fromEmail;
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    $headers[] = 'Return-Path: ' . $fromEmail;
    
    if ($isHTML) {
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
    }
    
    // Additional headers for better delivery
    $headers[] = 'X-Priority: 3';
    $headers[] = 'X-MSMail-Priority: Normal';
    $headers[] = 'Importance: Normal';
    
    $result = mail($to, $subject, $body, implode("\r\n", $headers));
    
    if ($result) {
        error_log("PHP mail() sent successfully to: $to");
        return true;
    } else {
        error_log("PHP mail() failed to: $to");
        return false;
    }
}

/**
 * Generate 6-digit OTP
 */
function generateOTP() {
    return sprintf("%06d", mt_rand(100000, 999999));
}

/**
 * Store OTP in database
 */
function storeOTP($email, $otp, $purpose = 'registration', $expiryMinutes = 10) {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Clean up old OTPs for this email and purpose
        $stmt = $db->prepare("DELETE FROM email_otps WHERE email = ? AND purpose = ?");
        $stmt->execute([$email, $purpose]);
        
        // Insert new OTP
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryMinutes} minutes"));
        $stmt = $db->prepare("
            INSERT INTO email_otps (email, otp_code, purpose, expires_at, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$email, $otp, $purpose, $expiresAt]);
        
        return true;
    } catch (Exception $e) {
        error_log("OTP storage error: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify OTP
 */
function verifyOTP($email, $otp, $purpose = 'registration') {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT id FROM email_otps 
            WHERE email = ? AND otp_code = ? AND purpose = ? 
            AND expires_at > NOW() AND (used = 0 OR used IS NULL)
        ");
        $stmt->execute([$email, $otp, $purpose]);
        
        if ($otpRecord = $stmt->fetch()) {
            // Mark as used
            $stmt = $db->prepare("UPDATE email_otps SET used = 1 WHERE id = ?");
            $stmt->execute([$otpRecord['id']]);
            
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("OTP verification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check OTP validity without marking as used
 */
function checkOTP($email, $otp, $purpose = 'registration') {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT id FROM email_otps 
            WHERE email = ? AND otp_code = ? AND purpose = ? 
            AND expires_at > NOW() AND (used = 0 OR used IS NULL)
        ");
        $stmt->execute([$email, $otp, $purpose]);
        
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        error_log("OTP check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark OTP as used after successful operation
 */
function markOTPAsUsed($email, $otp, $purpose = 'registration') {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            UPDATE email_otps SET used = 1 
            WHERE email = ? AND otp_code = ? AND purpose = ? 
            AND expires_at > NOW() AND (used = 0 OR used IS NULL)
        ");
        $stmt->execute([$email, $otp, $purpose]);
        
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("OTP mark used error: " . $e->getMessage());
        return false;
    }
}

/**
 * Clean expired OTPs
 */
function cleanExpiredOTPs() {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM email_otps WHERE expires_at < NOW() OR used = 1");
        $stmt->execute();
        return true;
    } catch (Exception $e) {
        error_log("OTP cleanup error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send registration OTP email
 */
function sendRegistrationOTP($email, $firstName = 'User') {
    try {
        // Generate OTP
        $otp = generateOTP();
        
        // Store in database
        if (!storeOTP($email, $otp, 'registration', 10)) {
            error_log("Failed to store OTP for: $email");
            return false;
        }
        
        // Create email content
        $subject = "Verify Your Email - HORAASTORE";
        
        $body = createRegistrationOTPEmailTemplate($otp, $firstName);
        
        // Send email
        if (sendProductionEmail($email, $subject, $body, true)) {
            error_log("Registration OTP sent successfully to: $email (OTP: $otp)");
            return true;
        } else {
            error_log("Failed to send registration OTP to: $email");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Registration OTP error: " . $e->getMessage());
        return false;
    }
}

/**
 * Create beautiful OTP email template
 */
function createRegistrationOTPEmailTemplate($otp, $firstName) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Email Verification - HORAASTORE</title>
    </head>
    <body style='font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4;'>
        <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #007cba, #005a87); padding: 30px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 28px; font-weight: bold;'>HORAASTORE</h1>
                <p style='color: #e0f3ff; margin: 10px 0 0 0; font-size: 16px;'>Email Verification Required</p>
            </div>
            
            <!-- Content -->
            <div style='padding: 30px;'>
                <h2 style='color: #333; margin-top: 0; font-size: 24px;'>Hello " . htmlspecialchars($firstName) . "!</h2>
                <p style='color: #666; font-size: 16px; line-height: 1.6; margin-bottom: 25px;'>
                    Thank you for registering with HORAASTORE. To complete your registration and secure your account, please verify your email address using the verification code below:
                </p>
                
                <!-- OTP Code Box -->
                <div style='text-align: center; margin: 30px 0;'>
                    <div style='background: linear-gradient(135deg, #f8f9fa, #e9ecef); border: 3px solid #007cba; border-radius: 12px; display: inline-block; padding: 25px 35px; box-shadow: 0 4px 8px rgba(0,124,186,0.1);'>
                        <div style='color: #666; font-size: 14px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;'>Your Verification Code</div>
                        <div style='font-size: 36px; font-weight: bold; color: #007cba; letter-spacing: 8px; font-family: \"Courier New\", monospace;'>" . $otp . "</div>
                    </div>
                </div>
                
                <div style='background: #e8f4fd; border-left: 4px solid #007cba; padding: 15px; margin: 25px 0; border-radius: 0 8px 8px 0;'>
                    <p style='color: #0c5460; margin: 0; font-size: 14px;'>
                        <strong>Important:</strong> This verification code will expire in <strong>10 minutes</strong>. Enter it on the verification page to activate your account.
                    </p>
                </div>
                
                <p style='color: #666; font-size: 16px; line-height: 1.6; margin-bottom: 20px;'>
                    If you have any questions or need assistance, please don't hesitate to contact our support team.
                </p>
                
                <!-- Security Notice -->
                <div style='background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin: 25px 0;'>
                    <div style='color: #856404; font-size: 14px;'>
                        <strong>🔒 Security Notice:</strong>
                        <ul style='margin: 10px 0 0 0; padding-left: 20px;'>
                            <li>Never share this verification code with anyone</li>
                            <li>HORAASTORE will never ask for your code via phone or email</li>
                            <li>If you didn't request this verification, please ignore this email</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div style='background: #f8f9fa; padding: 25px; text-align: center; border-top: 1px solid #dee2e6;'>
                <p style='color: #666; margin: 0 0 10px 0; font-size: 14px;'>
                    © 2025 HORAASTORE. All rights reserved.
                </p>
                <p style='color: #999; margin: 0; font-size: 12px;'>
                    This is an automated message. Please do not reply to this email.
                </p>
            </div>
        </div>
    </body>
    </html>";
}
?>
