<?php
// Milestone 8: Advanced Security & Two-Factor Authentication
class SecurityManager {
    private $conn;
    private $totpSecretLength = 16;
    private $sessionTimeout = 3600; // 1 hour
    
    public function __construct($database) {
        $this->conn = $database;
    }
    
    // Two-Factor Authentication (2FA) Implementation
    public function generateTOTPSecret() {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $this->totpSecretLength; $i++) {
            $secret .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $secret;
    }
    
    public function enable2FA($userId) {
        try {
            $secret = $this->generateTOTPSecret();
            
            $stmt = $this->conn->prepare("
                UPDATE users SET 
                totp_secret = ?, 
                two_factor_enabled = 1,
                two_factor_setup_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$secret, $userId]);
            
            if ($result) {
                // Log security event
                $this->logSecurityEvent($userId, '2FA_ENABLED', 'Two-factor authentication enabled');
                return $secret;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("2FA enable error: " . $e->getMessage());
            return false;
        }
    }
    
    public function disable2FA($userId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE users SET 
                totp_secret = NULL, 
                two_factor_enabled = 0,
                two_factor_disabled_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$userId]);
            
            if ($result) {
                $this->logSecurityEvent($userId, '2FA_DISABLED', 'Two-factor authentication disabled');
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("2FA disable error: " . $e->getMessage());
            return false;
        }
    }
    
    public function verifyTOTP($userId, $code) {
        try {
            $stmt = $this->conn->prepare("SELECT totp_secret FROM users WHERE id = ? AND two_factor_enabled = 1");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !$user['totp_secret']) {
                return false;
            }
            
            $secret = $user['totp_secret'];
            $timestamp = floor(time() / 30);
            
            // Check current timestamp and ±1 window for clock skew
            for ($i = -1; $i <= 1; $i++) {
                $testTimestamp = $timestamp + $i;
                $expectedCode = $this->calculateTOTP($secret, $testTimestamp);
                
                if (hash_equals($expectedCode, $code)) {
                    $this->logSecurityEvent($userId, '2FA_SUCCESS', 'TOTP verification successful');
                    return true;
                }
            }
            
            $this->logSecurityEvent($userId, '2FA_FAILED', 'TOTP verification failed', ['code' => $code]);
            return false;
            
        } catch (Exception $e) {
            error_log("TOTP verification error: " . $e->getMessage());
            return false;
        }
    }
    
    private function calculateTOTP($secret, $timestamp) {
        $secret = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timestamp);
        $hash = hash_hmac('sha1', $time, $secret, true);
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset + 0]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
    
    private function base32Decode($data) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;
        
        for ($i = 0, $j = strlen($data); $i < $j; $i++) {
            $v <<= 5;
            $v += strpos($alphabet, $data[$i]);
            $vbits += 5;
            
            if ($vbits >= 8) {
                $output .= chr($v >> ($vbits - 8));
                $vbits -= 8;
            }
        }
        
        return $output;
    }
    
    // Advanced Session Management
    public function createSecureSession($userId, $remember = false) {
        try {
            $sessionId = bin2hex(random_bytes(32));
            $deviceFingerprint = $this->generateDeviceFingerprint();
            $expiresAt = date('Y-m-d H:i:s', time() + ($remember ? 2592000 : $this->sessionTimeout)); // 30 days if remember
            
            $stmt = $this->conn->prepare("
                INSERT INTO user_sessions (
                    session_id, user_id, device_fingerprint, ip_address, 
                    user_agent, expires_at, is_active, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            
            $result = $stmt->execute([
                $sessionId,
                $userId,
                $deviceFingerprint,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                $expiresAt
            ]);
            
            if ($result) {
                $this->logSecurityEvent($userId, 'SESSION_CREATED', 'Secure session created');
                return $sessionId;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Secure session creation error: " . $e->getMessage());
            return false;
        }
    }
    
    public function validateSession($sessionId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT us.*, u.id as user_id, u.role, u.status
                FROM user_sessions us
                JOIN users u ON us.user_id = u.id
                WHERE us.session_id = ? 
                AND us.is_active = 1 
                AND us.expires_at > NOW()
                AND u.status = 'active'
            ");
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$session) {
                return false;
            }
            
            // Verify device fingerprint
            $currentFingerprint = $this->generateDeviceFingerprint();
            if (!hash_equals($session['device_fingerprint'], $currentFingerprint)) {
                $this->logSecurityEvent($session['user_id'], 'SESSION_HIJACK_ATTEMPT', 'Device fingerprint mismatch');
                $this->invalidateSession($sessionId);
                return false;
            }
            
            // Update last activity
            $this->updateSessionActivity($sessionId);
            
            return $session;
            
        } catch (Exception $e) {
            error_log("Session validation error: " . $e->getMessage());
            return false;
        }
    }
    
    private function generateDeviceFingerprint() {
        $factors = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? ''
        ];
        
        return hash('sha256', implode('|', $factors));
    }
    
    private function updateSessionActivity($sessionId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE user_sessions 
                SET last_activity = NOW() 
                WHERE session_id = ?
            ");
            $stmt->execute([$sessionId]);
            
        } catch (Exception $e) {
            error_log("Session activity update error: " . $e->getMessage());
        }
    }
    
    public function invalidateSession($sessionId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE user_sessions 
                SET is_active = 0, ended_at = NOW() 
                WHERE session_id = ?
            ");
            return $stmt->execute([$sessionId]);
            
        } catch (Exception $e) {
            error_log("Session invalidation error: " . $e->getMessage());
            return false;
        }
    }
    
    // Fraud Detection System
    public function detectFraudulentActivity($userId, $action, $data = []) {
        try {
            $riskScore = 0;
            $riskFactors = [];
            
            // Check for rapid successive actions
            $rapidActions = $this->checkRapidActions($userId, $action);
            if ($rapidActions > 5) {
                $riskScore += 30;
                $riskFactors[] = 'rapid_actions';
            }
            
            // Check for unusual IP activity
            $ipRisk = $this->checkIPRisk($_SERVER['REMOTE_ADDR']);
            $riskScore += $ipRisk;
            if ($ipRisk > 20) {
                $riskFactors[] = 'suspicious_ip';
            }
            
            // Check for unusual time patterns
            $timeRisk = $this->checkTimePatterns($userId);
            $riskScore += $timeRisk;
            if ($timeRisk > 15) {
                $riskFactors[] = 'unusual_time';
            }
            
            // Check for device anomalies
            $deviceRisk = $this->checkDeviceAnomalies($userId);
            $riskScore += $deviceRisk;
            if ($deviceRisk > 25) {
                $riskFactors[] = 'device_anomaly';
            }
            
            // Log fraud analysis
            $this->logFraudAnalysis($userId, $action, $riskScore, $riskFactors, $data);
            
            // Take action based on risk score
            if ($riskScore >= 70) {
                $this->handleHighRiskActivity($userId, $action, $riskScore, $riskFactors);
                return ['risk' => 'high', 'score' => $riskScore, 'action' => 'blocked'];
            } elseif ($riskScore >= 40) {
                $this->handleMediumRiskActivity($userId, $action, $riskScore, $riskFactors);
                return ['risk' => 'medium', 'score' => $riskScore, 'action' => 'review'];
            }
            
            return ['risk' => 'low', 'score' => $riskScore, 'action' => 'allow'];
            
        } catch (Exception $e) {
            error_log("Fraud detection error: " . $e->getMessage());
            return ['risk' => 'unknown', 'score' => 0, 'action' => 'allow'];
        }
    }
    
    private function checkRapidActions($userId, $action) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as action_count
                FROM security_logs
                WHERE user_id = ? 
                AND event_type = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
            ");
            $stmt->execute([$userId, $action]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['action_count'] ?? 0;
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function checkIPRisk($ipAddress) {
        // Check against known bad IP ranges, VPN detection, etc.
        // This is a simplified version
        $riskScore = 0;
        
        // Check if IP is in suspicious range
        if ($this->isIPSuspicious($ipAddress)) {
            $riskScore += 25;
        }
        
        // Check recent failed attempts from this IP
        $failedAttempts = $this->getFailedAttemptsFromIP($ipAddress);
        if ($failedAttempts > 3) {
            $riskScore += 20;
        }
        
        return min($riskScore, 50); // Cap at 50
    }
    
    private function isIPSuspicious($ipAddress) {
        // This would integrate with threat intelligence feeds
        $suspiciousRanges = [
            // Example suspicious IP ranges (this would be much more comprehensive)
            '192.168.1.0/24' // Local network example
        ];
        
        foreach ($suspiciousRanges as $range) {
            if ($this->ipInRange($ipAddress, $range)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function ipInRange($ip, $range) {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }
        
        list($subnet, $bits) = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;
        
        return ($ip & $mask) === $subnet;
    }
    
    private function getFailedAttemptsFromIP($ipAddress) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as failed_count
                FROM security_logs
                WHERE ip_address = ? 
                AND event_type IN ('LOGIN_FAILED', '2FA_FAILED', 'PAYMENT_FAILED')
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stmt->execute([$ipAddress]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['failed_count'] ?? 0;
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function checkTimePatterns($userId) {
        // Check if user is active at unusual times compared to their pattern
        $currentHour = date('H');
        $riskScore = 0;
        
        // Very early morning hours (2-6 AM)
        if ($currentHour >= 2 && $currentHour <= 6) {
            $riskScore += 10;
        }
        
        return $riskScore;
    }
    
    private function checkDeviceAnomalies($userId) {
        try {
            $currentFingerprint = $this->generateDeviceFingerprint();
            
            $stmt = $this->conn->prepare("
                SELECT COUNT(DISTINCT device_fingerprint) as device_count
                FROM user_sessions
                WHERE user_id = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $deviceCount = $result['device_count'] ?? 0;
            
            if ($deviceCount > 5) {
                return 25; // Many different devices
            } elseif ($deviceCount > 3) {
                return 15;
            }
            
            return 0;
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function handleHighRiskActivity($userId, $action, $riskScore, $riskFactors) {
        // Temporarily lock account
        $this->lockUserAccount($userId, 'high_risk_activity');
        
        // Send alert to admin
        $this->sendSecurityAlert('High Risk Activity Detected', [
            'user_id' => $userId,
            'action' => $action,
            'risk_score' => $riskScore,
            'risk_factors' => $riskFactors
        ]);
        
        // Log security incident
        $this->logSecurityEvent($userId, 'HIGH_RISK_ACTIVITY', 'Account temporarily locked due to high risk score', [
            'risk_score' => $riskScore,
            'factors' => $riskFactors
        ]);
    }
    
    private function handleMediumRiskActivity($userId, $action, $riskScore, $riskFactors) {
        // Require additional verification
        $this->requireAdditionalVerification($userId);
        
        // Send notification to user
        $this->sendSecurityNotification($userId, 'Unusual activity detected on your account');
    }
    
    // Security Logging
    public function logSecurityEvent($userId, $eventType, $description, $metadata = []) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO security_logs (
                    user_id, event_type, description, metadata, 
                    ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            return $stmt->execute([
                $userId,
                $eventType,
                $description,
                json_encode($metadata),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
        } catch (Exception $e) {
            error_log("Security logging error: " . $e->getMessage());
            return false;
        }
    }
    
    private function logFraudAnalysis($userId, $action, $riskScore, $riskFactors, $data) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO fraud_analysis_logs (
                    user_id, action, risk_score, risk_factors, 
                    analysis_data, ip_address, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            return $stmt->execute([
                $userId,
                $action,
                $riskScore,
                json_encode($riskFactors),
                json_encode($data),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
        } catch (Exception $e) {
            error_log("Fraud analysis logging error: " . $e->getMessage());
            return false;
        }
    }
    
    // GDPR Compliance
    public function exportUserData($userId) {
        try {
            $userData = [];
            
            // Basic user information
            $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userData['profile'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Orders
            $stmt = $this->conn->prepare("SELECT * FROM orders WHERE user_id = ?");
            $stmt->execute([$userId]);
            $userData['orders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Security logs
            $stmt = $this->conn->prepare("SELECT * FROM security_logs WHERE user_id = ?");
            $stmt->execute([$userId]);
            $userData['security_logs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Sessions
            $stmt = $this->conn->prepare("SELECT * FROM user_sessions WHERE user_id = ?");
            $stmt->execute([$userId]);
            $userData['sessions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $userData;
            
        } catch (Exception $e) {
            error_log("Data export error: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteUserData($userId) {
        try {
            $this->conn->beginTransaction();
            
            // Anonymize instead of delete to maintain data integrity
            $stmt = $this->conn->prepare("
                UPDATE users SET 
                first_name = 'Deleted', 
                last_name = 'User',
                email = CONCAT('deleted_', id, '@deleted.com'),
                phone = NULL,
                date_of_birth = NULL,
                totp_secret = NULL,
                status = 'deleted',
                deleted_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            
            // Delete sensitive data
            $stmt = $this->conn->prepare("DELETE FROM user_sessions WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            $stmt = $this->conn->prepare("DELETE FROM push_subscriptions WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            $this->conn->commit();
            
            $this->logSecurityEvent($userId, 'DATA_DELETION', 'User data deleted per GDPR request');
            
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Data deletion error: " . $e->getMessage());
            return false;
        }
    }
    
    // Helper methods
    private function lockUserAccount($userId, $reason) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE users SET 
                status = 'locked', 
                locked_at = NOW(), 
                lock_reason = ?
                WHERE id = ?
            ");
            return $stmt->execute([$reason, $userId]);
            
        } catch (Exception $e) {
            error_log("Account lock error: " . $e->getMessage());
            return false;
        }
    }
    
    private function requireAdditionalVerification($userId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE users SET 
                requires_verification = 1,
                verification_required_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$userId]);
            
        } catch (Exception $e) {
            error_log("Additional verification error: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendSecurityAlert($subject, $data) {
        // This would send alerts to administrators
        error_log("Security Alert: $subject - " . json_encode($data));
    }
    
    private function sendSecurityNotification($userId, $message) {
        // This would send notifications to users about security events
        error_log("Security Notification for user $userId: $message");
    }
    
    // Security Headers
    public function setSecurityHeaders() {
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.jsdelivr.net cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com fonts.googleapis.com; font-src 'self' fonts.gstatic.com; img-src 'self' data: blob:; connect-src 'self'; frame-ancestors 'none';");
        
        // Other security headers
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: DENY");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
        
        // HSTS (only if using HTTPS)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
        }
    }
}
?>
