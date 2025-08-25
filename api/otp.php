<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    sendJsonResponse(false, 'Invalid CSRF token');
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'send':
        sendOTP();
        break;
        
    case 'verify':
        verifyOTP();
        break;
        
    default:
        sendJsonResponse(false, 'Invalid action');
}

function sendOTP() {
    $email = sanitizeInput($_POST['email'] ?? '');
    $type = sanitizeInput($_POST['type'] ?? '');
    
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJsonResponse(false, 'Please enter a valid email address');
    }
    
    if (!in_array($type, ['checkout', 'registration', 'password_reset'])) {
        sendJsonResponse(false, 'Invalid OTP type');
    }
    
    // Check rate limiting (max 3 OTPs per 15 minutes)
    $sessionKey = "otp_attempts_{$email}";
    $attempts = $_SESSION[$sessionKey] ?? [];
    $currentTime = time();
    
    // Remove attempts older than 15 minutes
    $attempts = array_filter($attempts, function($timestamp) use ($currentTime) {
        return ($currentTime - $timestamp) < 900; // 15 minutes
    });
    
    if (count($attempts) >= 3) {
        sendJsonResponse(false, 'Too many OTP requests. Please try again later.');
    }
    
    // Generate 6-digit OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiryTime = time() + 300; // 5 minutes
    
    // Store OTP in session
    $_SESSION['otp_data'] = [
        'email' => $email,
        'otp' => $otp,
        'type' => $type,
        'expires_at' => $expiryTime,
        'verified' => false
    ];
    
    // Store attempt timestamp
    $attempts[] = $currentTime;
    $_SESSION[$sessionKey] = $attempts;
    
    // Store email for checkout
    if ($type === 'checkout') {
        $_SESSION['checkout_email'] = $email;
    }
    
    // In a real application, send actual email here
    // For demo purposes, we'll simulate email sending
    $emailSent = sendOTPEmail($email, $otp, $type);
    
    if ($emailSent) {
        sendJsonResponse(true, 'Verification code sent to your email');
    } else {
        sendJsonResponse(false, 'Failed to send verification code');
    }
}

function verifyOTP() {
    $email = sanitizeInput($_POST['email'] ?? '');
    $otp = sanitizeInput($_POST['otp'] ?? '');
    $type = sanitizeInput($_POST['type'] ?? '');
    
    if (!$email || !$otp || !$type) {
        sendJsonResponse(false, 'Missing required fields');
    }
    
    // Check if OTP exists and is valid
    if (!isset($_SESSION['otp_data'])) {
        sendJsonResponse(false, 'No verification code found. Please request a new one.');
    }
    
    $otpData = $_SESSION['otp_data'];
    
    // Validate OTP data
    if ($otpData['email'] !== $email || $otpData['type'] !== $type) {
        sendJsonResponse(false, 'Invalid verification request');
    }
    
    // Check if OTP is expired
    if (time() > $otpData['expires_at']) {
        unset($_SESSION['otp_data']);
        sendJsonResponse(false, 'Verification code has expired. Please request a new one.');
    }
    
    // Check if OTP matches
    if ($otpData['otp'] !== $otp) {
        sendJsonResponse(false, 'Invalid verification code');
    }
    
    // Mark as verified
    $_SESSION['otp_data']['verified'] = true;
    
    // Set verification flags based on type
    switch ($type) {
        case 'checkout':
            $_SESSION['checkout_email_verified'] = true;
            break;
        case 'registration':
            $_SESSION['registration_email_verified'] = true;
            break;
        case 'password_reset':
            $_SESSION['password_reset_verified'] = true;
            break;
    }
    
    sendJsonResponse(true, 'Email verified successfully');
}

function sendOTPEmail($email, $otp, $type) {
    // In a real application, you would use a proper email service
    // For demo purposes, we'll just log the OTP
    
    $subject = '';
    $message = '';
    
    switch ($type) {
        case 'checkout':
            $subject = 'Verify Your Email - Order Confirmation';
            $message = "Your verification code for order confirmation is: {$otp}\n\nThis code will expire in 5 minutes.";
            break;
        case 'registration':
            $subject = 'Verify Your Email - Account Registration';
            $message = "Your verification code for account registration is: {$otp}\n\nThis code will expire in 5 minutes.";
            break;
        case 'password_reset':
            $subject = 'Password Reset Verification';
            $message = "Your verification code for password reset is: {$otp}\n\nThis code will expire in 5 minutes.";
            break;
    }
    
    // Log for demo (in production, send actual email)
    error_log("OTP Email sent to {$email}: Subject: {$subject}, OTP: {$otp}");
    
    // Simulate email sending delay
    usleep(500000); // 0.5 second delay
    
    return true; // Simulate successful email sending
}

function sendJsonResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}
?>
