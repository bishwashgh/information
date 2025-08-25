<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/push_notifications.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . SITE_URL . '/admin/login.php');
    exit();
}

$pageTitle = 'PWA Management';
$activePage = 'pwa';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'send_test_notification':
                if (isset($_POST['user_id']) && isset($_POST['template'])) {
                    $result = $pushManager->sendNotification($_POST['user_id'], $_POST['template']);
                    $message = $result ? 'Test notification sent successfully!' : 'Failed to send notification.';
                    $messageType = $result ? 'success' : 'error';
                }
                break;
                
            case 'cleanup_subscriptions':
                $days = intval($_POST['days'] ?? 90);
                $cleaned = $pushManager->cleanupInactiveSubscriptions($days);
                $message = "Cleaned up $cleaned inactive subscriptions.";
                $messageType = 'success';
                break;
        }
    }
}

// Get PWA statistics
$stats = [];
$stats['total_subscriptions'] = $pushManager->getActiveSubscriptions();
$stats['notification_stats'] = $pushManager->getNotificationStats();

// Get recent notifications
try {
    $stmt = $conn->prepare("
        SELECT pnl.*, ps.user_id, u.username
        FROM push_notifications_log pnl
        JOIN push_subscriptions ps ON pnl.subscription_id = ps.id
        LEFT JOIN users u ON ps.user_id = u.id
        ORDER BY pnl.sent_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    $recentNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recentNotifications = [];
}

// Get users for test notifications
try {
    $stmt = $conn->prepare("
        SELECT DISTINCT u.id, u.username, u.email
        FROM users u
        JOIN push_subscriptions ps ON u.id = ps.user_id
        WHERE ps.is_active = TRUE
        ORDER BY u.username
    ");
    $stmt->execute();
    $subscribedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $subscribedUsers = [];
}

// Get notification templates
try {
    $stmt = $conn->prepare("SELECT * FROM notification_templates ORDER BY name");
    $stmt->execute();
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $templates = [];
}

include '../admin/includes/admin_header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1><i class="fas fa-mobile-alt"></i> PWA Management</h1>
        <p>Manage Progressive Web App features, push notifications, and offline functionality</p>
    </div>

    <?php if (isset($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> mb-4">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- PWA Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['total_subscriptions']); ?></h3>
                    <p>Active Subscriptions</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['notification_stats']['total_sent'] ?? 0); ?></h3>
                    <p>Notifications Sent (30d)</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-mouse-pointer"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['notification_stats']['total_clicked'] ?? 0); ?></h3>
                    <p>Notifications Clicked</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo ($stats['notification_stats']['click_rate'] ?? 0) . '%'; ?></h3>
                    <p>Click Rate</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Test Notifications -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-vial"></i> Test Notifications</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="send_test_notification">
                        
                        <div class="form-group">
                            <label for="user_id">Select User:</label>
                            <select name="user_id" id="user_id" class="form-control" required>
                                <option value="">Choose a user...</option>
                                <?php foreach ($subscribedUsers as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['username'] . ' (' . $user['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="template">Notification Template:</label>
                            <select name="template" id="template" class="form-control" required>
                                <option value="">Choose a template...</option>
                                <?php foreach ($templates as $template): ?>
                                    <option value="<?php echo $template['name']; ?>">
                                        <?php echo htmlspecialchars($template['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send Test Notification
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- PWA Settings -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-cog"></i> PWA Settings</h3>
                </div>
                <div class="card-body">
                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>Service Worker Status</h4>
                            <p>Check if service worker is properly registered</p>
                        </div>
                        <button class="btn btn-secondary" onclick="checkServiceWorker()">
                            <i class="fas fa-sync"></i> Check Status
                        </button>
                    </div>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>Cache Management</h4>
                            <p>Clear PWA caches and update offline data</p>
                        </div>
                        <button class="btn btn-warning" onclick="clearCaches()">
                            <i class="fas fa-trash"></i> Clear Caches
                        </button>
                    </div>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>VAPID Keys</h4>
                            <p>Manage push notification VAPID keys</p>
                        </div>
                        <button class="btn btn-info" onclick="showVapidKeys()">
                            <i class="fas fa-key"></i> View Keys
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Maintenance -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-tools"></i> Maintenance</h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="form-inline">
                        <input type="hidden" name="action" value="cleanup_subscriptions">
                        <div class="form-group mr-3">
                            <label for="days" class="mr-2">Clean up subscriptions inactive for:</label>
                            <input type="number" name="days" id="days" value="90" min="1" max="365" class="form-control mr-2" style="width: 80px;">
                            <span>days</span>
                        </div>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-broom"></i> Cleanup Subscriptions
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Notifications -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Recent Notifications</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>User</th>
                                    <th>Title</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentNotifications)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No notifications found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentNotifications as $notification): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y H:i', strtotime($notification['sent_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($notification['username'] ?? 'Unknown'); ?></td>
                                            <td><?php echo htmlspecialchars($notification['title']); ?></td>
                                            <td class="text-truncate" style="max-width: 200px;">
                                                <?php echo htmlspecialchars($notification['message']); ?>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = '';
                                                $statusIcon = '';
                                                switch ($notification['status']) {
                                                    case 'sent':
                                                        $statusClass = 'success';
                                                        $statusIcon = 'check';
                                                        break;
                                                    case 'clicked':
                                                        $statusClass = 'primary';
                                                        $statusIcon = 'mouse-pointer';
                                                        break;
                                                    case 'failed':
                                                        $statusClass = 'danger';
                                                        $statusIcon = 'times';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge badge-<?php echo $statusClass; ?>">
                                                    <i class="fas fa-<?php echo $statusIcon; ?>"></i>
                                                    <?php echo ucfirst($notification['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewNotificationDetails('<?php echo $notification['id']; ?>')">
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

<!-- PWA Status Modal -->
<div class="modal fade" id="pwaStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">PWA Status</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="pwaStatusContent">Loading...</div>
            </div>
        </div>
    </div>
</div>

<script>
function checkServiceWorker() {
    $('#pwaStatusModal').modal('show');
    
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistrations().then(registrations => {
            let status = '<h6>Service Worker Status:</h6>';
            if (registrations.length > 0) {
                status += '<div class="alert alert-success"><i class="fas fa-check"></i> Service Worker is registered</div>';
                status += '<ul>';
                registrations.forEach((registration, index) => {
                    status += `<li>Registration ${index + 1}: ${registration.scope}</li>`;
                });
                status += '</ul>';
            } else {
                status += '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> No service worker registrations found</div>';
            }
            
            // Check PWA installation status
            status += '<h6>PWA Installation:</h6>';
            if (window.matchMedia('(display-mode: standalone)').matches) {
                status += '<div class="alert alert-success"><i class="fas fa-mobile-alt"></i> App is installed and running in standalone mode</div>';
            } else {
                status += '<div class="alert alert-info"><i class="fas fa-globe"></i> App is running in browser</div>';
            }
            
            // Check notification permissions
            status += '<h6>Notification Permission:</h6>';
            if ('Notification' in window) {
                status += `<div class="alert alert-${Notification.permission === 'granted' ? 'success' : 'warning'}">
                    <i class="fas fa-bell"></i> Permission: ${Notification.permission}
                </div>`;
            } else {
                status += '<div class="alert alert-danger"><i class="fas fa-times"></i> Notifications not supported</div>';
            }
            
            document.getElementById('pwaStatusContent').innerHTML = status;
        });
    } else {
        document.getElementById('pwaStatusContent').innerHTML = 
            '<div class="alert alert-danger"><i class="fas fa-times"></i> Service Workers not supported in this browser</div>';
    }
}

function clearCaches() {
    if ('caches' in window) {
        caches.keys().then(cacheNames => {
            const deletePromises = cacheNames.map(cacheName => caches.delete(cacheName));
            return Promise.all(deletePromises);
        }).then(() => {
            showToast('Caches cleared successfully', 'success');
            // Also reload the page to get fresh content
            setTimeout(() => window.location.reload(), 1000);
        }).catch(error => {
            showToast('Error clearing caches: ' + error.message, 'error');
        });
    } else {
        showToast('Cache API not supported', 'error');
    }
}

function showVapidKeys() {
    fetch('<?php echo SITE_URL; ?>/api/pwa.php?action=vapid_key')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const content = `
                    <h6>VAPID Public Key:</h6>
                    <div class="form-group">
                        <textarea class="form-control" readonly rows="3">${data.publicKey}</textarea>
                    </div>
                    <small class="text-muted">This key is used for push notification authentication. Keep it secure.</small>
                `;
                document.getElementById('pwaStatusContent').innerHTML = content;
                $('#pwaStatusModal').modal('show');
            } else {
                showToast('Error getting VAPID keys: ' + data.error, 'error');
            }
        })
        .catch(error => {
            showToast('Error: ' + error.message, 'error');
        });
}

function viewNotificationDetails(notificationId) {
    // This would open a modal with full notification details
    showToast('Notification details feature coming soon', 'info');
}

function showToast(message, type) {
    // Simple toast notification
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}"></i>
        ${message}
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}
</script>

<style>
.setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #eee;
}

.setting-item:last-child {
    border-bottom: none;
}

.setting-info h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
}

.setting-info p {
    margin: 0;
    font-size: 14px;
    color: #666;
}

.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>

<?php include '../admin/includes/admin_footer.php'; ?>
