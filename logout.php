<?php
require_once 'includes/config.php';

// Only process if user is logged in
if (isLoggedIn()) {
    $db = Database::getInstance()->getConnection();
    
    // Clear remember me token if exists
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        // Remove from database
        $stmt = $db->prepare("DELETE FROM user_sessions WHERE token = ?");
        $stmt->execute([hash('sha256', $token)]);
        
        // Clear cookie
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }
    
    // Clear session
    session_destroy();
    
    // Start new session and set logout message
    session_start();
    $_SESSION['logout_message'] = 'You have been logged out successfully.';
}

// Redirect to login page
header('Location: ' . SITE_URL . '/login.php');
exit;
?>
