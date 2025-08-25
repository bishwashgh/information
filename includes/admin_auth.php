<?php
/**
 * Admin Authentication Functions
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in as admin (specific to admin panel)
 */
if (!function_exists('isAdminPanelUser')) {
    function isAdminPanelUser() {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }
}

/**
 * Require admin authentication
 */
if (!function_exists('requireAdmin')) {
    function requireAdmin() {
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isAdminPanelUser()) {
            // Clear any partial session data
            session_unset();
            session_destroy();
            
            // Start new session for login
            session_start();
            
            // Redirect to admin login
            header('Location: login.php');
            exit();
        }
        
        // Check session timeout
        if (isset($_SESSION['admin_login_time'])) {
            $currentTime = time();
            $sessionTimeout = 24 * 60 * 60; // 24 hours
            
            if (($currentTime - $_SESSION['admin_login_time']) > $sessionTimeout) {
                // Session expired
                session_unset();
                session_destroy();
                session_start();
                header('Location: login.php?expired=1');
                exit();
            }
        }
    }
}

/**
 * Get current admin user info
 */
if (!function_exists('getCurrentAdmin')) {
    function getCurrentAdmin() {
        if (isAdminPanelUser()) {
            return [
                'id' => $_SESSION['admin_id'] ?? null,
                'username' => $_SESSION['admin_username'] ?? null,
                'email' => $_SESSION['admin_email'] ?? null,
                'role' => $_SESSION['admin_role'] ?? 'admin'
            ];
        }
        return null;
    }
}

/**
 * Login admin user
 */
if (!function_exists('loginAdmin')) {
    function loginAdmin($userId, $username, $email, $role = 'admin') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $userId;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_email'] = $email;
        $_SESSION['admin_role'] = $role;
        $_SESSION['admin_login_time'] = time();
    }
}

/**
 * Logout admin user
 */
if (!function_exists('logoutAdmin')) {
    function logoutAdmin() {
        unset($_SESSION['admin_logged_in']);
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_username']);
        unset($_SESSION['admin_email']);
        unset($_SESSION['admin_role']);
        unset($_SESSION['admin_login_time']);
        
        // Destroy session if no other data
        if (empty($_SESSION)) {
            session_destroy();
        }
    }
}

/**
 * Check if admin session is valid (not expired)
 */
if (!function_exists('isAdminSessionValid')) {
    function isAdminSessionValid() {
        if (!isAdminPanelUser()) {
            return false;
        }
        
        // Check session timeout (24 hours)
        $loginTime = $_SESSION['admin_login_time'] ?? 0;
        $currentTime = time();
        $sessionTimeout = 24 * 60 * 60; // 24 hours
        
        if (($currentTime - $loginTime) > $sessionTimeout) {
            logoutAdmin();
            return false;
        }
        
        return true;
    }
}

/**
 * Generate CSRF token for admin forms
 */
if (!function_exists('generateAdminCSRFToken')) {
    function generateAdminCSRFToken() {
        if (!isset($_SESSION['admin_csrf_token'])) {
            $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['admin_csrf_token'];
    }
}

/**
 * Verify CSRF token for admin forms
 */
if (!function_exists('verifyAdminCSRFToken')) {
    function verifyAdminCSRFToken($token) {
        return isset($_SESSION['admin_csrf_token']) && hash_equals($_SESSION['admin_csrf_token'], $token);
    }
}

/**
 * Require valid admin session (with timeout check)
 */
if (!function_exists('requireValidAdmin')) {
    function requireValidAdmin() {
        if (!isAdminSessionValid()) {
            header('Location: login.php?expired=1');
            exit();
        }
    }
}
?>
