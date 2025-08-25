<?php
// API endpoints for security management
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

header('Content-Type: application/json');

// Check admin access
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'unlock_account':
        // Unlock a locked user account
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['identifier']) || !isset($input['ip_address'])) {
            echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
            break;
        }
        
        try {
            $stmt = $conn->prepare("DELETE FROM login_attempts WHERE identifier = ? AND ip_address = ?");
            $stmt->execute([$input['identifier'], $input['ip_address']]);
            
            $securityManager->logSecurityEvent('account_unlocked', $_SESSION['user_id'], [
                'unlocked_identifier' => $input['identifier'],
                'unlocked_ip' => $input['ip_address']
            ], 'low');
            
            echo json_encode(['success' => true, 'message' => 'Account unlocked successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Failed to unlock account']);
        }
        break;
        
    case 'get_event_details':
        // Get detailed information about a security event
        $eventId = intval($_GET['id'] ?? 0);
        
        if (!$eventId) {
            echo json_encode(['success' => false, 'error' => 'Invalid event ID']);
            break;
        }
        
        try {
            $stmt = $conn->prepare("
                SELECT se.*, u.username 
                FROM security_events se
                LEFT JOIN users u ON se.user_id = u.id
                WHERE se.id = ?
            ");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($event) {
                echo json_encode(['success' => true, 'event' => $event]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Event not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Failed to get event details']);
        }
        break;
        
    case 'ban_ip':
        // Ban an IP address
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['ip_address'])) {
            echo json_encode(['success' => false, 'error' => 'IP address required']);
            break;
        }
        
        try {
            // Add to banned IPs (you might want to create a separate table for this)
            $stmt = $conn->prepare("
                INSERT INTO security_settings (setting_key, setting_value, updated_by) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                setting_value = CONCAT(setting_value, ',', VALUES(setting_value))
            ");
            $stmt->execute([
                'banned_ips',
                $input['ip_address'],
                $_SESSION['user_id']
            ]);
            
            $securityManager->logSecurityEvent('ip_banned', $_SESSION['user_id'], [
                'banned_ip' => $input['ip_address'],
                'reason' => $input['reason'] ?? 'Manual ban'
            ], 'high');
            
            echo json_encode(['success' => true, 'message' => 'IP address banned successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Failed to ban IP address']);
        }
        break;
        
    case 'validate_password':
        // Validate password strength
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['password'])) {
            echo json_encode(['success' => false, 'error' => 'Password required']);
            break;
        }
        
        $errors = $securityManager->validatePassword($input['password']);
        
        echo json_encode([
            'success' => empty($errors),
            'errors' => $errors,
            'strength' => empty($errors) ? 'strong' : (count($errors) <= 2 ? 'medium' : 'weak')
        ]);
        break;
        
    case 'scan_file':
        // Security scan for uploaded file
        if (!isset($_FILES['file'])) {
            echo json_encode(['success' => false, 'error' => 'No file uploaded']);
            break;
        }
        
        $file = $_FILES['file'];
        $errors = $securityManager->validateFileUpload($file);
        
        if (empty($errors)) {
            // File is safe
            $securityManager->logSecurityEvent('file_upload', $_SESSION['user_id'], [
                'filename' => $file['name'],
                'size' => $file['size'],
                'type' => $file['type'],
                'scan_result' => 'safe'
            ], 'low');
            
            echo json_encode(['success' => true, 'message' => 'File passed security scan']);
        } else {
            // File has security issues
            $securityManager->logSecurityEvent('file_upload', $_SESSION['user_id'], [
                'filename' => $file['name'],
                'size' => $file['size'],
                'type' => $file['type'],
                'scan_result' => 'blocked',
                'issues' => $errors
            ], 'high');
            
            echo json_encode(['success' => false, 'errors' => $errors]);
        }
        break;
        
    case 'check_rate_limit':
        // Check rate limit for specific endpoint
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['endpoint'])) {
            echo json_encode(['success' => false, 'error' => 'Endpoint required']);
            break;
        }
        
        $identifier = $_SESSION['user_id'] ?? $securityManager->getClientIP();
        $allowed = $securityManager->checkRateLimit($identifier, $input['endpoint'], 
                                                   $input['limit'] ?? null, $input['window'] ?? null);
        
        echo json_encode([
            'success' => true,
            'allowed' => $allowed,
            'remaining' => $allowed ? null : 0
        ]);
        break;
        
    case 'security_report':
        // Generate security report
        $days = intval($_GET['days'] ?? 7);
        
        try {
            $report = [
                'period' => $days,
                'generated_at' => date('Y-m-d H:i:s'),
                'stats' => $securityManager->getSecurityStats($days)
            ];
            
            // Add additional report data
            $stmt = $conn->prepare("
                SELECT event_type, COUNT(*) as count
                FROM security_events 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY event_type
                ORDER BY count DESC
            ");
            $stmt->execute([$days]);
            $report['event_breakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Geographic distribution of threats (simplified)
            $stmt = $conn->prepare("
                SELECT ip_address, COUNT(*) as threat_count
                FROM security_events 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                AND severity IN ('high', 'critical')
                GROUP BY ip_address
                ORDER BY threat_count DESC
                LIMIT 20
            ");
            $stmt->execute([$days]);
            $report['threat_sources'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'report' => $report]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Failed to generate report']);
        }
        break;
        
    case 'export_security_log':
        // Export security events as CSV
        $days = intval($_GET['days'] ?? 30);
        
        try {
            $stmt = $conn->prepare("
                SELECT se.created_at, se.event_type, se.ip_address, se.severity, 
                       COALESCE(u.username, 'Guest') as username, se.event_data
                FROM security_events se
                LEFT JOIN users u ON se.user_id = u.id
                WHERE se.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ORDER BY se.created_at DESC
            ");
            $stmt->execute([$days]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Generate CSV
            $csv = "Date,Event Type,Username,IP Address,Severity,Details\n";
            foreach ($events as $event) {
                $csv .= sprintf('"%s","%s","%s","%s","%s","%s"' . "\n",
                    $event['created_at'],
                    $event['event_type'],
                    $event['username'],
                    $event['ip_address'],
                    $event['severity'],
                    str_replace('"', '""', json_encode($event['event_data']))
                );
            }
            
            // Set headers for download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="security_log_' . date('Y-m-d') . '.csv"');
            header('Content-Length: ' . strlen($csv));
            
            echo $csv;
            exit();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Failed to export security log']);
        }
        break;
        
    case 'test_security':
        // Test security measures
        $testType = $_GET['test_type'] ?? '';
        
        switch ($testType) {
            case 'xss':
                // Test XSS detection
                $testInput = '<script>alert("XSS Test")</script>';
                $cleaned = $securityManager->preventXSS($testInput);
                echo json_encode([
                    'success' => true,
                    'test' => 'XSS Protection',
                    'input' => $testInput,
                    'output' => $cleaned,
                    'blocked' => $testInput !== $cleaned
                ]);
                break;
                
            case 'sql_injection':
                // Test SQL injection detection
                $testInput = "'; DROP TABLE users; --";
                $detected = $securityManager->detectSQLInjection($testInput);
                echo json_encode([
                    'success' => true,
                    'test' => 'SQL Injection Detection',
                    'input' => $testInput,
                    'detected' => $detected
                ]);
                break;
                
            case 'rate_limit':
                // Test rate limiting
                $identifier = 'test_' . time();
                $endpoint = 'test_endpoint';
                $results = [];
                
                for ($i = 0; $i < 10; $i++) {
                    $allowed = $securityManager->checkRateLimit($identifier, $endpoint, 5, 60);
                    $results[] = ['request' => $i + 1, 'allowed' => $allowed];
                }
                
                echo json_encode([
                    'success' => true,
                    'test' => 'Rate Limiting',
                    'results' => $results
                ]);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Unknown test type']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        break;
}
?>
