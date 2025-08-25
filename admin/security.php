<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . SITE_URL . '/admin/login.php');
    exit();
}

$pageTitle = 'Security Management';
$activePage = 'security';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_settings':
                updateSecuritySettings();
                break;
            case 'cleanup_logs':
                $days = intval($_POST['days'] ?? 90);
                $cleaned = $securityManager->cleanupSecurityData($days);
                $message = "Cleaned up $cleaned security records older than $days days.";
                $messageType = 'success';
                break;
        }
    }
}

function updateSecuritySettings() {
    global $conn, $message, $messageType;
    
    try {
        $settings = [
            'firewall_enabled' => $_POST['firewall_enabled'] ?? 'false',
            'auto_ban_suspicious_ips' => $_POST['auto_ban_suspicious_ips'] ?? 'false',
            'require_https' => $_POST['require_https'] ?? 'false',
            'enable_security_headers' => $_POST['enable_security_headers'] ?? 'false',
            'log_all_requests' => $_POST['log_all_requests'] ?? 'false',
            'enable_file_scanning' => $_POST['enable_file_scanning'] ?? 'false',
            'max_upload_size' => $_POST['max_upload_size'] ?? '5242880',
            'password_policy_enabled' => $_POST['password_policy_enabled'] ?? 'false',
            'session_regeneration_interval' => $_POST['session_regeneration_interval'] ?? '1800'
        ];
        
        foreach ($settings as $key => $value) {
            $stmt = $conn->prepare("
                INSERT INTO security_settings (setting_key, setting_value, updated_by) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value), 
                updated_by = VALUES(updated_by), 
                updated_at = NOW()
            ");
            $stmt->execute([$key, $value, $_SESSION['user_id']]);
        }
        
        $message = 'Security settings updated successfully!';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'Error updating security settings: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get security statistics
$securityStats = $securityManager->getSecurityStats(30);

// Get recent security events
try {
    $stmt = $conn->prepare("
        SELECT se.*, u.username 
        FROM security_events se
        LEFT JOIN users u ON se.user_id = u.id
        ORDER BY se.created_at DESC 
        LIMIT 100
    ");
    $stmt->execute();
    $recentEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recentEvents = [];
}

// Get current security settings
try {
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM security_settings");
    $stmt->execute();
    $settingsData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    $settingsData = [];
}

// Get locked accounts
try {
    $stmt = $conn->prepare("
        SELECT identifier, ip_address, attempts, last_attempt, locked_until 
        FROM login_attempts 
        WHERE locked_until > NOW() 
        ORDER BY last_attempt DESC 
        LIMIT 50
    ");
    $stmt->execute();
    $lockedAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $lockedAccounts = [];
}

include '../admin/includes/admin_header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1><i class="fas fa-shield-alt"></i> Security Management</h1>
        <p>Monitor and manage platform security, threats, and access controls</p>
    </div>

    <?php if (isset($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> mb-4">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Security Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($securityStats['events']['total_events'] ?? 0); ?></h3>
                    <p>Security Events (30d)</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-ban text-danger"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($securityStats['events']['critical_events'] ?? 0); ?></h3>
                    <p>Critical Threats</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-lock text-info"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($securityStats['login_attempts']['locked_accounts'] ?? 0); ?></h3>
                    <p>Locked Accounts</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-key text-success"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($securityStats['login_attempts']['total_attempts'] ?? 0); ?></h3>
                    <p>Login Attempts</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Security Settings -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-cog"></i> Security Settings</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Access Control</h5>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="firewall_enabled" value="true" 
                                           id="firewall_enabled" <?php echo ($settingsData['firewall_enabled'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="firewall_enabled">
                                        Enable Web Application Firewall
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="auto_ban_suspicious_ips" value="true" 
                                           id="auto_ban_suspicious_ips" <?php echo ($settingsData['auto_ban_suspicious_ips'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="auto_ban_suspicious_ips">
                                        Auto-ban Suspicious IP Addresses
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="require_https" value="true" 
                                           id="require_https" <?php echo ($settingsData['require_https'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="require_https">
                                        Force HTTPS Connections
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="enable_security_headers" value="true" 
                                           id="enable_security_headers" <?php echo ($settingsData['enable_security_headers'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enable_security_headers">
                                        Enable Security Headers
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5>Monitoring & Logging</h5>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="log_all_requests" value="true" 
                                           id="log_all_requests" <?php echo ($settingsData['log_all_requests'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="log_all_requests">
                                        Log All HTTP Requests
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="enable_file_scanning" value="true" 
                                           id="enable_file_scanning" <?php echo ($settingsData['enable_file_scanning'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enable_file_scanning">
                                        Enable File Upload Scanning
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="password_policy_enabled" value="true" 
                                           id="password_policy_enabled" <?php echo ($settingsData['password_policy_enabled'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="password_policy_enabled">
                                        Enforce Strong Password Policy
                                    </label>
                                </div>
                                
                                <div class="form-group">
                                    <label for="max_upload_size">Max File Upload Size (bytes):</label>
                                    <input type="number" class="form-control" name="max_upload_size" 
                                           value="<?php echo $settingsData['max_upload_size'] ?? '5242880'; ?>" 
                                           min="1048576" max="52428800">
                                    <small class="form-text text-muted">Default: 5MB (5242880 bytes)</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="session_regeneration_interval">Session Regeneration Interval (seconds):</label>
                                    <input type="number" class="form-control" name="session_regeneration_interval" 
                                           value="<?php echo $settingsData['session_regeneration_interval'] ?? '1800'; ?>" 
                                           min="300" max="7200">
                                    <small class="form-text text-muted">Default: 30 minutes (1800 seconds)</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Security Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Threat Summary -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-pie"></i> Threat Summary</h3>
                </div>
                <div class="card-body">
                    <canvas id="threatChart" width="400" height="300"></canvas>
                    
                    <div class="mt-3">
                        <h5>Top Threatening IPs</h5>
                        <?php if (empty($securityStats['threatening_ips'])): ?>
                            <p class="text-muted">No threats detected recently</p>
                        <?php else: ?>
                            <ul class="list-unstyled">
                                <?php foreach (array_slice($securityStats['threatening_ips'], 0, 5) as $threat): ?>
                                    <li class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-monospace"><?php echo htmlspecialchars($threat['ip_address']); ?></span>
                                        <span class="badge badge-danger"><?php echo $threat['threat_count']; ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Locked Accounts -->
    <?php if (!empty($lockedAccounts)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-lock"></i> Currently Locked Accounts</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Identifier</th>
                                    <th>IP Address</th>
                                    <th>Failed Attempts</th>
                                    <th>Last Attempt</th>
                                    <th>Locked Until</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lockedAccounts as $account): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($account['identifier']); ?></td>
                                        <td class="text-monospace"><?php echo htmlspecialchars($account['ip_address']); ?></td>
                                        <td><span class="badge badge-warning"><?php echo $account['attempts']; ?></span></td>
                                        <td><?php echo date('M j, Y H:i', strtotime($account['last_attempt'])); ?></td>
                                        <td><?php echo date('M j, Y H:i', strtotime($account['locked_until'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="unlockAccount('<?php echo htmlspecialchars($account['identifier']); ?>', '<?php echo htmlspecialchars($account['ip_address']); ?>')">
                                                <i class="fas fa-unlock"></i> Unlock
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Security Events -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3><i class="fas fa-list"></i> Recent Security Events</h3>
                    <form method="POST" class="form-inline">
                        <input type="hidden" name="action" value="cleanup_logs">
                        <div class="form-group mr-2">
                            <label for="cleanup_days" class="mr-2">Clean logs older than:</label>
                            <input type="number" name="days" id="cleanup_days" value="90" min="1" max="365" class="form-control mr-2" style="width: 80px;">
                            <span class="mr-2">days</span>
                        </div>
                        <button type="submit" class="btn btn-sm btn-warning">
                            <i class="fas fa-broom"></i> Cleanup Logs
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Event Type</th>
                                    <th>User</th>
                                    <th>IP Address</th>
                                    <th>Severity</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentEvents)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No security events found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentEvents as $event): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y H:i', strtotime($event['created_at'])); ?></td>
                                            <td>
                                                <?php
                                                $eventIcons = [
                                                    'login_success' => 'fas fa-sign-in-alt text-success',
                                                    'login_failure' => 'fas fa-times text-danger',
                                                    'account_lockout' => 'fas fa-lock text-warning',
                                                    'suspicious_activity' => 'fas fa-exclamation-triangle text-danger',
                                                    'xss_attempt' => 'fas fa-code text-danger',
                                                    'sql_injection_attempt' => 'fas fa-database text-danger',
                                                    'csrf_violation' => 'fas fa-shield-alt text-warning'
                                                ];
                                                $icon = $eventIcons[$event['event_type']] ?? 'fas fa-info text-info';
                                                ?>
                                                <i class="<?php echo $icon; ?>"></i>
                                                <?php echo ucfirst(str_replace('_', ' ', $event['event_type'])); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($event['username'] ?? 'Guest'); ?></td>
                                            <td class="text-monospace"><?php echo htmlspecialchars($event['ip_address']); ?></td>
                                            <td>
                                                <?php
                                                $severityClass = [
                                                    'low' => 'success',
                                                    'medium' => 'warning',
                                                    'high' => 'danger',
                                                    'critical' => 'dark'
                                                ];
                                                $class = $severityClass[$event['severity']] ?? 'secondary';
                                                ?>
                                                <span class="badge badge-<?php echo $class; ?>">
                                                    <?php echo ucfirst($event['severity']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-info" 
                                                        onclick="viewEventDetails('<?php echo $event['id']; ?>')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Event Details Modal -->
<div class="modal fade" id="eventDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Security Event Details</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="eventDetailsContent">Loading...</div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Threat severity chart
const threatData = {
    labels: ['Low', 'Medium', 'High', 'Critical'],
    datasets: [{
        data: [
            <?php echo $securityStats['events']['low_events'] ?? 0; ?>,
            <?php echo $securityStats['events']['medium_events'] ?? 0; ?>,
            <?php echo $securityStats['events']['high_events'] ?? 0; ?>,
            <?php echo $securityStats['events']['critical_events'] ?? 0; ?>
        ],
        backgroundColor: ['#28a745', '#ffc107', '#fd7e14', '#dc3545'],
        borderWidth: 0
    }]
};

new Chart(document.getElementById('threatChart'), {
    type: 'doughnut',
    data: threatData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        legend: {
            position: 'bottom'
        }
    }
});

function unlockAccount(identifier, ipAddress) {
    if (confirm('Are you sure you want to unlock this account?')) {
        fetch('<?php echo SITE_URL; ?>/api/security.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'unlock_account',
                identifier: identifier,
                ip_address: ipAddress,
                csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Account unlocked successfully', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Error unlocking account', 'error');
        });
    }
}

function viewEventDetails(eventId) {
    $('#eventDetailsModal').modal('show');
    
    fetch('<?php echo SITE_URL; ?>/api/security.php?action=get_event_details&id=' + eventId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const event = data.event;
                const content = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Basic Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Event Type:</strong></td><td>${event.event_type}</td></tr>
                                <tr><td><strong>Severity:</strong></td><td><span class="badge badge-${getSeverityClass(event.severity)}">${event.severity}</span></td></tr>
                                <tr><td><strong>IP Address:</strong></td><td class="text-monospace">${event.ip_address}</td></tr>
                                <tr><td><strong>User:</strong></td><td>${event.username || 'Guest'}</td></tr>
                                <tr><td><strong>Date:</strong></td><td>${new Date(event.created_at).toLocaleString()}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Technical Details</h6>
                            <table class="table table-sm">
                                <tr><td><strong>User Agent:</strong></td><td class="text-break">${event.user_agent || 'N/A'}</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Event Data</h6>
                            <pre class="bg-light p-3 rounded"><code>${JSON.stringify(JSON.parse(event.event_data || '{}'), null, 2)}</code></pre>
                        </div>
                    </div>
                `;
                document.getElementById('eventDetailsContent').innerHTML = content;
            } else {
                document.getElementById('eventDetailsContent').innerHTML = '<div class="alert alert-danger">Failed to load event details</div>';
            }
        })
        .catch(error => {
            document.getElementById('eventDetailsContent').innerHTML = '<div class="alert alert-danger">Error loading event details</div>';
        });
}

function getSeverityClass(severity) {
    const classes = {
        'low': 'success',
        'medium': 'warning',
        'high': 'danger',
        'critical': 'dark'
    };
    return classes[severity] || 'secondary';
}

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : 'times'}"></i>
        ${message}
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}
</script>

<?php include '../admin/includes/admin_footer.php'; ?>
