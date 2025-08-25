<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verify CSRF token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$action = $_POST['action'] ?? '';
$db = Database::getInstance()->getConnection();

switch ($action) {
    case 'subscribe':
        subscribeNewsletter();
        break;
    case 'unsubscribe':
        unsubscribeNewsletter();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function subscribeNewsletter() {
    global $db;
    
    $email = trim($_POST['email'] ?? '');
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        return;
    }
    
    try {
        // Check if email already exists
        $stmt = $db->prepare("SELECT id, is_active FROM newsletter_subscriptions WHERE email = ?");
        $stmt->execute([$email]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            if ($existing['is_active']) {
                echo json_encode(['success' => false, 'message' => 'Email already subscribed']);
                return;
            } else {
                // Reactivate subscription
                $stmt = $db->prepare("UPDATE newsletter_subscriptions SET is_active = 1, unsubscribed_at = NULL WHERE email = ?");
                $stmt->execute([$email]);
                
                echo json_encode(['success' => true, 'message' => 'Successfully resubscribed to newsletter']);
                return;
            }
        }
        
        // Add new subscription
        $stmt = $db->prepare("INSERT INTO newsletter_subscriptions (email) VALUES (?)");
        $stmt->execute([$email]);
        
        // Send welcome email (basic implementation)
        $subject = "Welcome to " . SITE_NAME . " Newsletter!";
        $body = "
        <html>
        <body>
            <h2>Welcome to " . SITE_NAME . "!</h2>
            <p>Thank you for subscribing to our newsletter. You'll now receive updates about:</p>
            <ul>
                <li>New product launches</li>
                <li>Exclusive offers and discounts</li>
                <li>Special promotions</li>
                <li>Company news and updates</li>
            </ul>
            <p>If you no longer wish to receive these emails, you can <a href='" . SITE_URL . "/newsletter/unsubscribe.php?email=" . urlencode($email) . "'>unsubscribe</a> at any time.</p>
            <p>Best regards,<br>The " . SITE_NAME . " Team</p>
        </body>
        </html>
        ";
        
        sendEmail($email, $subject, $body, true);
        
        echo json_encode(['success' => true, 'message' => 'Successfully subscribed to newsletter']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to subscribe to newsletter']);
    }
}

function unsubscribeNewsletter() {
    global $db;
    
    $email = trim($_POST['email'] ?? '');
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        return;
    }
    
    try {
        // Check if email exists
        $stmt = $db->prepare("SELECT id FROM newsletter_subscriptions WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email not found in subscriptions']);
            return;
        }
        
        // Unsubscribe
        $stmt = $db->prepare("UPDATE newsletter_subscriptions SET is_active = 0, unsubscribed_at = NOW() WHERE email = ?");
        $stmt->execute([$email]);
        
        echo json_encode(['success' => true, 'message' => 'Successfully unsubscribed from newsletter']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to unsubscribe from newsletter']);
    }
}
?>
