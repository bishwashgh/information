<?php
/**
 * Main Configuration File
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment variables
require_once __DIR__ . '/../config/database.php';

// Site Configuration
define('SITE_URL', $_ENV['SITE_URL'] ?? 'http://localhost/WEB');
define('SITE_NAME', $_ENV['SITE_NAME'] ?? 'HORAASTORE');
define('ADMIN_EMAIL', $_ENV['ADMIN_EMAIL'] ?? 'admin@store.com');

// Security
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'your_jwt_secret_key');
define('CSRF_SECRET', $_ENV['CSRF_SECRET'] ?? 'your_csrf_secret_key');

// Other settings
date_default_timezone_set($_ENV['TIMEZONE'] ?? 'Asia/Kathmandu');
define('DEFAULT_LANGUAGE', $_ENV['DEFAULT_LANGUAGE'] ?? 'en');
define('DEBUG_MODE', $_ENV['DEBUG_MODE'] === 'true');

// Error reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

/**
 * Common utility functions
 */

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Sanitize input
function sanitizeInput($input) {
    if ($input === null) {
        return '';
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Generate random string
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

// Format price
function formatPrice($price, $currency = 'Rs. ') {
    return $currency . number_format($price, 2);
}

// Generate slug
function generateSlug($string) {
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isLoggedIn() && (($_SESSION['role'] ?? '') === 'admin' || ($_SESSION['is_admin'] ?? false) === true);
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// JSON response
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Get client IP address
function getClientIP() {
    $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// Send email function (basic implementation)
function sendEmail($to, $subject, $body, $isHTML = true) {
    // This is a basic implementation - in production, use PHPMailer
    $headers = "From: " . ADMIN_EMAIL . "\r\n";
    if ($isHTML) {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    }
    return mail($to, $subject, $body, $headers);
}

// Get cart count
function getCartCount() {
    $db = Database::getInstance()->getConnection();
    
    if (isLoggedIn()) {
        $stmt = $db->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
        $stmt->execute([getCurrentUserId()]);
    } else {
        $sessionId = session_id();
        $stmt = $db->prepare("SELECT SUM(quantity) FROM cart WHERE session_id = ?");
        $stmt->execute([$sessionId]);
    }
    
    return (int) $stmt->fetchColumn();
}

// Get categories for navigation
function getCategories() {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get featured products
function getFeaturedProducts($limit = 8) {
    $db = Database::getInstance()->getConnection();
    
    // Ensure limit is a positive integer
    $limit = max(1, (int)$limit);
    
    $stmt = $db->prepare("
        SELECT p.*, c.name as category_name,
               (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.featured = 1 AND p.status = 'active' 
        ORDER BY p.created_at DESC 
        LIMIT " . $limit
    );
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get user role
function getUserRole() {
    return $_SESSION['role'] ?? 'guest';
}
?>
