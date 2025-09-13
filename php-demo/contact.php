<?php
/**
 * PHP Contact Form Demo
 * Bishwas Ghimire Portfolio
 * 
 * Note: This is for demonstration purposes only.
 * GitHub Pages cannot run PHP - deploy this on a PHP-enabled host.
 */

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// CORS headers (adjust as needed)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Rate limiting (simple implementation)
session_start();
$max_attempts = 5;
$time_window = 300; // 5 minutes

if (!isset($_SESSION['form_attempts'])) {
    $_SESSION['form_attempts'] = [];
}

// Clean old attempts
$current_time = time();
$_SESSION['form_attempts'] = array_filter($_SESSION['form_attempts'], function($timestamp) use ($current_time, $time_window) {
    return ($current_time - $timestamp) < $time_window;
});

// Check rate limit
if (count($_SESSION['form_attempts']) >= $max_attempts) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests. Please try again later.']);
    exit();
}

// Add current attempt
$_SESSION['form_attempts'][] = $current_time;

// Load environment variables (create .env file)
function loadEnv($file) {
    if (!file_exists($file)) {
        return;
    }
    
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

loadEnv(__DIR__ . '/.env');

// Configuration
$config = [
    'smtp_host' => $_ENV['SMTP_HOST'] ?? 'localhost',
    'smtp_port' => $_ENV['SMTP_PORT'] ?? 587,
    'smtp_username' => $_ENV['SMTP_USERNAME'] ?? '',
    'smtp_password' => $_ENV['SMTP_PASSWORD'] ?? '',
    'smtp_encryption' => $_ENV['SMTP_ENCRYPTION'] ?? 'tls',
    'from_email' => $_ENV['FROM_EMAIL'] ?? 'noreply@example.com',
    'to_email' => $_ENV['TO_EMAIL'] ?? 'bishwas.ghimire@example.com',
    'recaptcha_secret' => $_ENV['RECAPTCHA_SECRET'] ?? '',
];

// Validation functions
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateLength($string, $min, $max) {
    $len = strlen($string);
    return $len >= $min && $len <= $max;
}

// reCAPTCHA verification (optional)
function verifyRecaptcha($token, $secret) {
    if (empty($secret) || empty($token)) {
        return true; // Skip if not configured
    }
    
    $response = file_get_contents(
        'https://www.google.com/recaptcha/api/siteverify?secret=' . 
        urlencode($secret) . '&response=' . urlencode($token)
    );
    
    $result = json_decode($response, true);
    return isset($result['success']) && $result['success'] === true;
}

// Main processing
try {
    // Get and validate input
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    $recaptcha_token = $_POST['g-recaptcha-response'] ?? '';
    
    // Validation errors
    $errors = [];
    
    if (!validateLength($name, 2, 50)) {
        $errors[] = 'Name must be between 2 and 50 characters';
    }
    
    if (!validateEmail($email)) {
        $errors[] = 'Invalid email address';
    }
    
    if (!validateLength($subject, 3, 100)) {
        $errors[] = 'Subject must be between 3 and 100 characters';
    }
    
    if (!validateLength($message, 10, 1000)) {
        $errors[] = 'Message must be between 10 and 1000 characters';
    }
    
    // Verify reCAPTCHA
    if (!verifyRecaptcha($recaptcha_token, $config['recaptcha_secret'])) {
        $errors[] = 'reCAPTCHA verification failed';
    }
    
    // Check for spam patterns
    $spam_keywords = ['viagra', 'casino', 'lottery', 'winner', 'click here', 'free money'];
    $content_check = strtolower($message . ' ' . $subject);
    
    foreach ($spam_keywords as $keyword) {
        if (strpos($content_check, $keyword) !== false) {
            $errors[] = 'Message contains prohibited content';
            break;
        }
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['errors' => $errors]);
        exit();
    }
    
    // Prepare email content
    $email_subject = "Portfolio Contact: " . $subject;
    $email_body = generateEmailBody($name, $email, $subject, $message);
    
    // Send email using PHPMailer (if available) or mail()
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $result = sendEmailWithPHPMailer($config, $email_subject, $email_body, $email, $name);
    } else {
        $result = sendEmailNative($config['to_email'], $email_subject, $email_body, $email, $name);
    }
    
    if ($result) {
        // Log successful submission
        error_log("Contact form submission: {$name} ({$email}) - {$subject}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for your message! I will get back to you soon.'
        ]);
    } else {
        throw new Exception('Failed to send email');
    }
    
} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while sending your message. Please try again.']);
}

// Email generation function
function generateEmailBody($name, $email, $subject, $message) {
    $timestamp = date('Y-m-d H:i:s');
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #6366f1; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .content { background: #f8fafc; padding: 20px; border-radius: 0 0 8px 8px; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #4f46e5; }
        .meta { font-size: 12px; color: #666; border-top: 1px solid #ddd; padding-top: 15px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>Portfolio Contact Form Submission</h2>
        </div>
        <div class='content'>
            <div class='field'>
                <div class='label'>Name:</div>
                <div>" . htmlspecialchars($name) . "</div>
            </div>
            <div class='field'>
                <div class='label'>Email:</div>
                <div>" . htmlspecialchars($email) . "</div>
            </div>
            <div class='field'>
                <div class='label'>Subject:</div>
                <div>" . htmlspecialchars($subject) . "</div>
            </div>
            <div class='field'>
                <div class='label'>Message:</div>
                <div>" . nl2br(htmlspecialchars($message)) . "</div>
            </div>
            <div class='meta'>
                <div><strong>Timestamp:</strong> {$timestamp}</div>
                <div><strong>IP Address:</strong> {$ip_address}</div>
                <div><strong>User Agent:</strong> " . htmlspecialchars($user_agent) . "</div>
            </div>
        </div>
    </div>
</body>
</html>";
}

// PHPMailer function (requires PHPMailer library)
function sendEmailWithPHPMailer($config, $subject, $body, $reply_email, $reply_name) {
    require_once 'vendor/autoload.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_username'];
        $mail->Password = $config['smtp_password'];
        $mail->SMTPSecure = $config['smtp_encryption'];
        $mail->Port = $config['smtp_port'];
        
        // Recipients
        $mail->setFrom($config['from_email'], 'Portfolio Contact Form');
        $mail->addAddress($config['to_email'], 'Bishwas Ghimire');
        $mail->addReplyTo($reply_email, $reply_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

// Native mail function (fallback)
function sendEmailNative($to_email, $subject, $body, $reply_email, $reply_name) {
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: Portfolio Contact Form <noreply@example.com>',
        'Reply-To: ' . $reply_name . ' <' . $reply_email . '>',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    return mail($to_email, $subject, $body, implode("\r\n", $headers));
}
?>