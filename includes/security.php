<?php
// Advanced Security Manager for e-commerce platform

class SecurityManager {
    private $conn;
    private $config;
    
    public function __construct($database) {
        $this->conn = $database;
        $this->initializeSecurityTables();
        $this->config = [
            'max_login_attempts' => 5,
            'lockout_duration' => 900, // 15 minutes
            'session_lifetime' => 7200, // 2 hours
            'password_min_length' => 8,
            'require_2fa' => false,
            'max_file_upload_size' => 5242880, // 5MB
            'allowed_file_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
            'rate_limit_requests' => 100,
            'rate_limit_window' => 3600 // 1 hour
        ];
    }
    
    private function initializeSecurityTables() {
        // Security events log
        $sql = "CREATE TABLE IF NOT EXISTS security_events (
            id INT PRIMARY KEY AUTO_INCREMENT,
            event_type ENUM('login_attempt', 'login_success', 'login_failure', 'password_change', 'account_lockout', 'suspicious_activity', 'file_upload', 'xss_attempt', 'sql_injection_attempt', 'csrf_violation', '2fa_enabled', '2fa_disabled') NOT NULL,
            user_id INT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            event_data JSON,
            severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_ip_address (ip_address),
            INDEX idx_event_type (event_type),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )";
        $this->conn->exec($sql);
        
        // Login attempts tracking
        $sql = "CREATE TABLE IF NOT EXISTS login_attempts (
            id INT PRIMARY KEY AUTO_INCREMENT,
            identifier VARCHAR(255) NOT NULL, -- email or IP
            ip_address VARCHAR(45) NOT NULL,
            attempts INT DEFAULT 1,
            last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            locked_until TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_identifier_ip (identifier, ip_address),
            INDEX idx_ip_address (ip_address),
            INDEX idx_locked_until (locked_until)
        )";
        $this->conn->exec($sql);
        
        // Two-factor authentication
        $sql = "CREATE TABLE IF NOT EXISTS user_2fa (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            secret_key VARCHAR(32) NOT NULL,
            backup_codes JSON,
            is_enabled BOOLEAN DEFAULT FALSE,
            recovery_codes JSON,
            last_used TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_id (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $this->conn->exec($sql);
        
        // Session management
        $sql = "CREATE TABLE IF NOT EXISTS user_sessions (
            id VARCHAR(128) PRIMARY KEY,
            user_id INT NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            device_fingerprint VARCHAR(64),
            INDEX idx_user_id (user_id),
            INDEX idx_expires_at (expires_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $this->conn->exec($sql);
        
        // Rate limiting
        $sql = "CREATE TABLE IF NOT EXISTS rate_limits (
            id INT PRIMARY KEY AUTO_INCREMENT,
            identifier VARCHAR(255) NOT NULL, -- IP or user_id
            endpoint VARCHAR(255) NOT NULL,
            requests INT DEFAULT 1,
            window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_identifier_endpoint (identifier, endpoint),
            INDEX idx_window_start (window_start)
        )";
        $this->conn->exec($sql);
        
        // File upload security
        $sql = "CREATE TABLE IF NOT EXISTS file_uploads (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NULL,
            original_filename VARCHAR(255) NOT NULL,
            stored_filename VARCHAR(255) NOT NULL,
            file_size INT NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            file_hash VARCHAR(64) NOT NULL,
            upload_path VARCHAR(500) NOT NULL,
            is_safe BOOLEAN DEFAULT FALSE,
            scan_result JSON,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_file_hash (file_hash),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )";
        $this->conn->exec($sql);
        
        // Security settings
        $sql = "CREATE TABLE IF NOT EXISTS security_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT NOT NULL,
            description TEXT,
            updated_by INT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
        )";
        $this->conn->exec($sql);
        
        $this->initializeDefaultSettings();
    }
    
    private function initializeDefaultSettings() {
        $defaults = [
            'firewall_enabled' => 'true',
            'auto_ban_suspicious_ips' => 'true',
            'require_https' => 'true',
            'enable_security_headers' => 'true',
            'log_all_requests' => 'false',
            'enable_file_scanning' => 'true',
            'max_upload_size' => '5242880',
            'allowed_file_extensions' => 'jpg,jpeg,png,gif,pdf,doc,docx',
            'password_policy_enabled' => 'true',
            'session_regeneration_interval' => '1800'
        ];
        
        foreach ($defaults as $key => $value) {
            $stmt = $this->conn->prepare("INSERT IGNORE INTO security_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
        }
    }
    
    // Input validation and sanitization
    public function sanitizeInput($input, $type = 'string') {
        switch ($type) {
            case 'email':
                return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
            case 'url':
                return filter_var(trim($input), FILTER_SANITIZE_URL);
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'html':
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
            case 'string':
            default:
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
    }
    
    // XSS protection
    public function preventXSS($input) {
        $input = preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F\\x7F]/', '', $input);
        $input = str_replace(['<script', '</script', 'javascript:', 'vbscript:', 'onload=', 'onerror='], '', $input);
        return $this->sanitizeInput($input, 'html');
    }
    
    // SQL injection protection
    public function detectSQLInjection($input) {
        $patterns = [
            '/(\bor\b|\band\b).*?(\=|>|<)/i',
            '/union.*select/i',
            '/select.*from/i',
            '/insert.*into/i',
            '/update.*set/i',
            '/delete.*from/i',
            '/drop.*table/i',
            '/alter.*table/i',
            '/--.*$/m',
            '/\/\*.*?\*\//s'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                $this->logSecurityEvent('sql_injection_attempt', null, [
                    'input' => substr($input, 0, 200),
                    'pattern_matched' => $pattern
                ], 'high');
                return true;
            }
        }
        return false;
    }
    
    // Rate limiting
    public function checkRateLimit($identifier, $endpoint, $limit = null, $window = null) {
        $limit = $limit ?? $this->config['rate_limit_requests'];
        $window = $window ?? $this->config['rate_limit_window'];
        
        try {
            // Clean old entries
            $stmt = $this->conn->prepare("DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL ? SECOND)");
            $stmt->execute([$window]);
            
            // Check current rate
            $stmt = $this->conn->prepare("SELECT requests FROM rate_limits WHERE identifier = ? AND endpoint = ?");
            $stmt->execute([$identifier, $endpoint]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($current) {
                if ($current['requests'] >= $limit) {
                    $this->logSecurityEvent('rate_limit_exceeded', null, [
                        'identifier' => $identifier,
                        'endpoint' => $endpoint,
                        'requests' => $current['requests'],
                        'limit' => $limit
                    ], 'medium');
                    return false;
                }
                
                // Increment counter
                $stmt = $this->conn->prepare("UPDATE rate_limits SET requests = requests + 1 WHERE identifier = ? AND endpoint = ?");
                $stmt->execute([$identifier, $endpoint]);
            } else {
                // Create new entry
                $stmt = $this->conn->prepare("INSERT INTO rate_limits (identifier, endpoint, requests, window_start) VALUES (?, ?, 1, NOW())");
                $stmt->execute([$identifier, $endpoint]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Rate limit check error: " . $e->getMessage());
            return true; // Allow on error
        }
    }
    
    // Login attempt tracking
    public function trackLoginAttempt($identifier, $success = false, $userId = null) {
        $ip = $this->getClientIP();
        
        try {
            if ($success) {
                // Clear failed attempts on successful login
                $stmt = $this->conn->prepare("DELETE FROM login_attempts WHERE identifier = ? AND ip_address = ?");
                $stmt->execute([$identifier, $ip]);
                
                $this->logSecurityEvent('login_success', $userId, [
                    'identifier' => $identifier
                ], 'low');
            } else {
                // Track failed attempt
                $stmt = $this->conn->prepare("
                    INSERT INTO login_attempts (identifier, ip_address, attempts, last_attempt) 
                    VALUES (?, ?, 1, NOW()) 
                    ON DUPLICATE KEY UPDATE 
                    attempts = attempts + 1, 
                    last_attempt = NOW()
                ");
                $stmt->execute([$identifier, $ip]);
                
                // Check if should be locked
                $stmt = $this->conn->prepare("SELECT attempts FROM login_attempts WHERE identifier = ? AND ip_address = ?");
                $stmt->execute([$identifier, $ip]);
                $attempts = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($attempts && $attempts['attempts'] >= $this->config['max_login_attempts']) {
                    $lockUntil = date('Y-m-d H:i:s', time() + $this->config['lockout_duration']);
                    $stmt = $this->conn->prepare("UPDATE login_attempts SET locked_until = ? WHERE identifier = ? AND ip_address = ?");
                    $stmt->execute([$lockUntil, $identifier, $ip]);
                    
                    $this->logSecurityEvent('account_lockout', $userId, [
                        'identifier' => $identifier,
                        'attempts' => $attempts['attempts'],
                        'locked_until' => $lockUntil
                    ], 'high');
                }
                
                $this->logSecurityEvent('login_failure', $userId, [
                    'identifier' => $identifier,
                    'attempts' => $attempts['attempts'] ?? 1
                ], 'medium');
            }
        } catch (Exception $e) {
            error_log("Login attempt tracking error: " . $e->getMessage());
        }
    }
    
    // Check if account is locked
    public function isAccountLocked($identifier) {
        $ip = $this->getClientIP();
        
        try {
            $stmt = $this->conn->prepare("
                SELECT locked_until FROM login_attempts 
                WHERE identifier = ? AND ip_address = ? 
                AND locked_until > NOW()
            ");
            $stmt->execute([$identifier, $ip]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['locked_until'] : false;
        } catch (Exception $e) {
            error_log("Account lock check error: " . $e->getMessage());
            return false;
        }
    }
    
    // Password security validation
    public function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < $this->config['password_min_length']) {
            $errors[] = "Password must be at least {$this->config['password_min_length']} characters long";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        // Check against common passwords
        $commonPasswords = [
            'password', '123456', '123456789', 'qwerty', 'abc123', 
            'password123', 'admin', 'letmein', 'welcome', 'monkey'
        ];
        
        if (in_array(strtolower($password), $commonPasswords)) {
            $errors[] = "Password is too common. Please choose a more secure password";
        }
        
        return $errors;
    }
    
    // File upload security
    public function validateFileUpload($file) {
        $errors = [];
        
        // Check file size
        if ($file['size'] > $this->config['max_file_upload_size']) {
            $errors[] = "File size exceeds maximum allowed size";
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->config['allowed_file_types'])) {
            $errors[] = "File type not allowed";
        }
        
        // Check MIME type
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        if (isset($allowedMimes[$extension]) && $file['type'] !== $allowedMimes[$extension]) {
            $errors[] = "File MIME type does not match extension";
        }
        
        // Basic malware scan (check for suspicious content)
        if ($this->scanFileForMalware($file['tmp_name'])) {
            $errors[] = "File contains suspicious content";
        }
        
        return $errors;
    }
    
    private function scanFileForMalware($filePath) {
        $suspiciousPatterns = [
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/shell_exec\s*\(/i',
            '/passthru\s*\(/i',
            '/file_get_contents\s*\(/i',
            '/file_put_contents\s*\(/i',
            '/<\?php/i',
            '/<script/i'
        ];
        
        $content = file_get_contents($filePath, false, null, 0, 1024); // Read first 1KB
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    // Session security
    public function regenerateSession($userId = null) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            
            if ($userId) {
                $this->logSecurityEvent('session_regenerated', $userId, [
                    'old_session_id' => session_id(),
                    'new_session_id' => session_id()
                ], 'low');
            }
        }
    }
    
    // Security headers
    public function setSecurityHeaders() {
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; style-src \'self\' \'unsafe-inline\' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src \'self\' https://fonts.gstatic.com; img-src \'self\' data: https:; connect-src \'self\'');
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        }
    }
    
    // Get client IP address
    private function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    // Log security events
    public function logSecurityEvent($eventType, $userId = null, $eventData = [], $severity = 'medium') {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO security_events (event_type, user_id, ip_address, user_agent, event_data, severity) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $eventType,
                $userId,
                $this->getClientIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                json_encode($eventData),
                $severity
            ]);
        } catch (Exception $e) {
            error_log("Security event logging error: " . $e->getMessage());
        }
    }
    
    // Get security statistics
    public function getSecurityStats($days = 7) {
        try {
            $stats = [];
            
            // Total security events
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(*) as total_events,
                    COUNT(CASE WHEN severity = 'critical' THEN 1 END) as critical_events,
                    COUNT(CASE WHEN severity = 'high' THEN 1 END) as high_events,
                    COUNT(CASE WHEN severity = 'medium' THEN 1 END) as medium_events,
                    COUNT(CASE WHEN severity = 'low' THEN 1 END) as low_events
                FROM security_events 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$days]);
            $stats['events'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Login attempts
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(*) as total_attempts,
                    COUNT(CASE WHEN locked_until IS NOT NULL THEN 1 END) as locked_accounts
                FROM login_attempts 
                WHERE last_attempt >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$days]);
            $stats['login_attempts'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Top threatening IPs
            $stmt = $this->conn->prepare("
                SELECT ip_address, COUNT(*) as threat_count
                FROM security_events 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                AND severity IN ('high', 'critical')
                GROUP BY ip_address
                ORDER BY threat_count DESC
                LIMIT 10
            ");
            $stmt->execute([$days]);
            $stats['threatening_ips'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
        } catch (Exception $e) {
            error_log("Security stats error: " . $e->getMessage());
            return [];
        }
    }
    
    // Clean up old security data
    public function cleanupSecurityData($days = 90) {
        try {
            $tables = ['security_events', 'login_attempts', 'rate_limits'];
            $cleaned = 0;
            
            foreach ($tables as $table) {
                $dateField = $table === 'login_attempts' ? 'last_attempt' : 
                           ($table === 'rate_limits' ? 'window_start' : 'created_at');
                
                $stmt = $this->conn->prepare("DELETE FROM $table WHERE $dateField < DATE_SUB(NOW(), INTERVAL ? DAY)");
                $stmt->execute([$days]);
                $cleaned += $stmt->rowCount();
            }
            
            return $cleaned;
        } catch (Exception $e) {
            error_log("Security cleanup error: " . $e->getMessage());
            return 0;
        }
    }
}

// Initialize security manager
if (isset($conn)) {
    $securityManager = new SecurityManager($conn);
}
?>
