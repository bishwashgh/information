<?php
<<<<<<< HEAD
/**
 * Configuration file for Bishwas Ghimire's Portfolio
 * 
 * 1. Copy this file to 'config.local.php'
 * 2. Replace the placeholder values with your actual credentials
 * 3. Never commit config.local.php to version control
 */

return [
    // Brevo API Configuration
    'brevo_api_key' => 'your_brevo_api_key_here', // Replace with your actual Brevo API key (starts with xkeysib-)
    
    // Email Configuration
    'sender_email' => 'bishwasghimire2060@gmail.com', // Your verified sender email in Brevo
    'sender_name' => 'Bishwas Ghimire Portfolio',
    'recipient_email' => 'bishwasghimire2060@gmail.com', // Where contact form messages should be sent
    
    // Security
    'csrf_secret' => 'generate_a_random_string_here', // Change this to a random string
    
    // Debugging (set to false in production)
    'debug' => true
];
=======
// Load environment variables from .env file
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

// Define constants for configuration
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: 'bishwasghimire2060@gmail.com');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: 'your-app-password-here');
define('SMTP_RECIPIENT', getenv('SMTP_RECIPIENT') ?: 'bishwasghimire2060@gmail.com');
>>>>>>> 3b8f3122371c56de57271cc72033e0ff6e27a3dd
