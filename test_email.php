<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Check if vendor/autoload.php exists
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die('Please run "composer install" to install the required dependencies.');
}

// Load Composer's autoloader
require $autoloadPath;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load configuration
$configFile = __DIR__ . '/config.local.php';
if (!file_exists($configFile)) {
    $configFile = __DIR__ . '/config.sample.php';
    if (!file_exists($configFile)) {
        die('Configuration file not found');
    }
}

$config = include $configFile;

// Email configuration
$senderEmail = $config['sender_email'] ?? 'bishwasghimire2060@gmail.com';
$senderName = $config['sender_name'] ?? 'Bishwas Ghimire Portfolio';
$recipientEmail = $config['recipient_email'] ?? 'bishwasghimire2060@gmail.com';

// SMTP Configuration
$smtpHost = $config['smtp_host'] ?? 'smtp.gmail.com';
$smtpUsername = $config['smtp_username'] ?? 'bishwasghimire2060@gmail.com';
$smtpPassword = $config['smtp_password'] ?? '';
$smtpPort = $config['smtp_port'] ?? 587;
$smtpSecure = $config['smtp_secure'] ?? 'tls';

// Create a new PHPMailer instance
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUsername;
    $mail->Password = $smtpPassword;
    $mail->SMTPSecure = $smtpSecure;
    $mail->Port = $smtpPort;
    
    // Enable debug output
    $mail->SMTPDebug = 2;
    
    // Recipients
    $mail->setFrom($senderEmail, $senderName);
    $mail->addAddress($recipientEmail, 'Test Recipient');
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from Portfolio';
    $mail->Body = 'This is a test email from your portfolio contact form.';
    $mail->AltBody = 'This is a test email from your portfolio contact form.';
    
    // Send email
    $mail->send();
    echo 'Message has been sent';
    
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
