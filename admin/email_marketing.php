<?php
require_once '../includes/config.php';
requireLogin();
requireAdmin();

$db = Database::getInstance()->getConnection();

// Email marketing configuration
$emailConfig = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => '',
    'smtp_password' => '',
    'from_email' => 'noreply@yourstore.com',
    'from_name' => 'E-Commerce Store'
];

// Get email campaigns
$stmt = $db->query("
    SELECT * FROM email_campaigns 
    ORDER BY created_at DESC
    LIMIT 10
");
$campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get newsletter subscribers
$stmt = $db->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'active'");
$subscriberCount = $stmt->fetchColumn();

// Get recent email stats
$stmt = $db->query("
    SELECT 
        SUM(sent) as total_sent,
        SUM(opened) as total_opened,
        SUM(clicked) as total_clicked
    FROM email_campaigns 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$emailStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate rates
$openRate = $emailStats['total_sent'] > 0 ? round(($emailStats['total_opened'] / $emailStats['total_sent']) * 100, 2) : 0;
$clickRate = $emailStats['total_opened'] > 0 ? round(($emailStats['total_clicked'] / $emailStats['total_opened']) * 100, 2) : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_campaign':
            createEmailCampaign();
            break;
        case 'send_campaign':
            sendEmailCampaign($_POST['campaign_id']);
            break;
        case 'update_config':
            updateEmailConfig();
            break;
    }
}

function createEmailCampaign() {
    global $db;
    
    $name = $_POST['campaign_name'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $content = $_POST['content'] ?? '';
    $target_audience = $_POST['target_audience'] ?? 'all';
    
    if (empty($name) || empty($subject) || empty($content)) {
        $_SESSION['error'] = 'All fields are required';
        return;
    }
    
    try {
        // Create email campaigns table if not exists
        $db->exec("
            CREATE TABLE IF NOT EXISTS email_campaigns (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                target_audience VARCHAR(50) DEFAULT 'all',
                status ENUM('draft', 'sent', 'scheduled') DEFAULT 'draft',
                sent INT DEFAULT 0,
                opened INT DEFAULT 0,
                clicked INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                sent_at TIMESTAMP NULL
            )
        ");
        
        $stmt = $db->prepare("
            INSERT INTO email_campaigns (name, subject, content, target_audience)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$name, $subject, $content, $target_audience]);
        
        $_SESSION['success'] = 'Email campaign created successfully';
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error creating campaign: ' . $e->getMessage();
    }
}

function sendEmailCampaign($campaignId) {
    global $db;
    
    try {
        // Get campaign
        $stmt = $db->prepare("SELECT * FROM email_campaigns WHERE id = ?");
        $stmt->execute([$campaignId]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$campaign) {
            $_SESSION['error'] = 'Campaign not found';
            return;
        }
        
        // Get subscribers based on target audience
        $subscriberQuery = "SELECT email, first_name FROM newsletter_subscribers WHERE status = 'active'";
        
        if ($campaign['target_audience'] === 'customers') {
            $subscriberQuery = "
                SELECT DISTINCT u.email, u.first_name 
                FROM users u 
                JOIN orders o ON u.id = o.user_id 
                WHERE u.email_verified = 1
            ";
        } elseif ($campaign['target_audience'] === 'new_users') {
            $subscriberQuery = "
                SELECT email, first_name 
                FROM users 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                AND email_verified = 1
            ";
        }
        
        $stmt = $db->query($subscriberQuery);
        $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $sentCount = 0;
        foreach ($subscribers as $subscriber) {
            // In a real implementation, use a proper email service like SendGrid, Mailgun, etc.
            $emailSent = sendEmail(
                $subscriber['email'],
                $campaign['subject'],
                $campaign['content'],
                $subscriber['first_name']
            );
            
            if ($emailSent) {
                $sentCount++;
            }
        }
        
        // Update campaign status
        $stmt = $db->prepare("
            UPDATE email_campaigns 
            SET status = 'sent', sent = ?, sent_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$sentCount, $campaignId]);
        
        $_SESSION['success'] = "Campaign sent to {$sentCount} subscribers";
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error sending campaign: ' . $e->getMessage();
    }
}

function sendEmail($to, $subject, $content, $firstName = '') {
    global $emailConfig;
    
    // Personalize content
    $personalizedContent = str_replace('{{first_name}}', $firstName, $content);
    $personalizedContent = str_replace('{{unsubscribe_url}}', SITE_URL . '/unsubscribe.php', $personalizedContent);
    
    // In a real implementation, use a proper email service
    // For demo purposes, we'll use PHP's mail() function
    $headers = [
        'From: ' . $emailConfig['from_name'] . ' <' . $emailConfig['from_email'] . '>',
        'Reply-To: ' . $emailConfig['from_email'],
        'Content-Type: text/html; charset=UTF-8',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    return mail($to, $subject, $personalizedContent, implode("\r\n", $headers));
}

include '../admin/includes/admin_header.php';
?>

<style>
.email-dashboard {
    padding: var(--spacing-6);
}

.email-header {
    margin-bottom: var(--spacing-8);
}

.email-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-8);
}

.stat-card {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-6);
    text-align: center;
    box-shadow: var(--shadow);
    border: 1px solid var(--gray-200);
}

.stat-value {
    font-size: var(--font-size-2xl);
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: var(--spacing-2);
}

.stat-label {
    color: var(--gray-600);
    font-size: var(--font-size-sm);
}

.email-actions {
    display: flex;
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-6);
    flex-wrap: wrap;
}

.campaigns-table {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-6);
    box-shadow: var(--shadow);
    border: 1px solid var(--gray-200);
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: var(--spacing-4);
}

.table th,
.table td {
    padding: var(--spacing-3);
    text-align: left;
    border-bottom: 1px solid var(--gray-200);
}

.table th {
    background: var(--gray-50);
    font-weight: 600;
    color: var(--gray-900);
}

.status-badge {
    display: inline-block;
    padding: var(--spacing-1) var(--spacing-3);
    border-radius: var(--border-radius-full);
    font-size: var(--font-size-xs);
    font-weight: 600;
}

.status-draft {
    background: var(--gray-100);
    color: var(--gray-700);
}

.status-sent {
    background: var(--success-100);
    color: var(--success-700);
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
}

.modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-6);
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.form-group {
    margin-bottom: var(--spacing-4);
}

.form-group label {
    display: block;
    margin-bottom: var(--spacing-2);
    font-weight: 500;
    color: var(--gray-700);
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: var(--spacing-3);
    border: 1px solid var(--gray-300);
    border-radius: var(--border-radius);
    font-size: var(--font-size-sm);
}

.form-group textarea {
    min-height: 200px;
    resize: vertical;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-3) var(--spacing-4);
    border: none;
    border-radius: var(--border-radius);
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: var(--transition-fast);
}

.btn-primary {
    background: var(--primary-color);
    color: var(--white);
}

.btn-primary:hover {
    background: var(--primary-dark);
}

.btn-secondary {
    background: var(--gray-100);
    color: var(--gray-700);
}

.btn-secondary:hover {
    background: var(--gray-200);
}

.btn-success {
    background: var(--success-color);
    color: var(--white);
}

.btn-success:hover {
    background: var(--success-dark);
}

@media (max-width: 768px) {
    .email-dashboard {
        padding: var(--spacing-4);
    }
    
    .email-stats {
        grid-template-columns: 1fr;
    }
    
    .email-actions {
        flex-direction: column;
    }
}
</style>

<div class="email-dashboard">
    <div class="email-header">
        <h1><i class="fas fa-envelope"></i> Email Marketing</h1>
        <p>Manage email campaigns and track subscriber engagement</p>
    </div>

    <!-- Email Statistics -->
    <div class="email-stats">
        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($subscriberCount); ?></div>
            <div class="stat-label">Active Subscribers</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($emailStats['total_sent'] ?: 0); ?></div>
            <div class="stat-label">Emails Sent (30d)</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $openRate; ?>%</div>
            <div class="stat-label">Open Rate</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $clickRate; ?>%</div>
            <div class="stat-label">Click Rate</div>
        </div>
    </div>

    <!-- Actions -->
    <div class="email-actions">
        <button class="btn btn-primary" onclick="showCreateCampaignModal()">
            <i class="fas fa-plus"></i>
            Create Campaign
        </button>
        <button class="btn btn-secondary" onclick="showSubscribersModal()">
            <i class="fas fa-users"></i>
            View Subscribers
        </button>
        <button class="btn btn-secondary" onclick="showEmailConfigModal()">
            <i class="fas fa-cog"></i>
            Email Settings
        </button>
        <button class="btn btn-success" onclick="exportSubscribers()">
            <i class="fas fa-download"></i>
            Export List
        </button>
    </div>

    <!-- Campaigns Table -->
    <div class="campaigns-table">
        <h2>Recent Campaigns</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Campaign Name</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Sent</th>
                    <th>Open Rate</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($campaigns)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--gray-500);">
                        No campaigns yet. <a href="#" onclick="showCreateCampaignModal()">Create your first campaign</a>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($campaigns as $campaign): ?>
                <tr>
                    <td><?php echo htmlspecialchars($campaign['name']); ?></td>
                    <td><?php echo htmlspecialchars($campaign['subject']); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $campaign['status']; ?>">
                            <?php echo ucfirst($campaign['status']); ?>
                        </span>
                    </td>
                    <td><?php echo number_format($campaign['sent']); ?></td>
                    <td>
                        <?php 
                        $openRate = $campaign['sent'] > 0 ? round(($campaign['opened'] / $campaign['sent']) * 100, 1) : 0;
                        echo $openRate . '%';
                        ?>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($campaign['created_at'])); ?></td>
                    <td>
                        <?php if ($campaign['status'] === 'draft'): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="send_campaign">
                            <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Send this campaign?')">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                        <button class="btn btn-secondary btn-sm" onclick="viewCampaign(<?php echo $campaign['id']; ?>)">
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

<!-- Create Campaign Modal -->
<div id="createCampaignModal" class="modal">
    <div class="modal-content">
        <h2>Create Email Campaign</h2>
        <form method="POST">
            <input type="hidden" name="action" value="create_campaign">
            
            <div class="form-group">
                <label for="campaign_name">Campaign Name</label>
                <input type="text" id="campaign_name" name="campaign_name" required>
            </div>
            
            <div class="form-group">
                <label for="subject">Email Subject</label>
                <input type="text" id="subject" name="subject" required>
            </div>
            
            <div class="form-group">
                <label for="target_audience">Target Audience</label>
                <select id="target_audience" name="target_audience">
                    <option value="all">All Subscribers</option>
                    <option value="customers">Customers Only</option>
                    <option value="new_users">New Users (30 days)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="content">Email Content (HTML)</label>
                <textarea id="content" name="content" required placeholder="Use {{first_name}} for personalization and {{unsubscribe_url}} for unsubscribe link"></textarea>
            </div>
            
            <div style="display: flex; gap: var(--spacing-3); justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="hideCreateCampaignModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Campaign</button>
            </div>
        </form>
    </div>
</div>

<script>
function showCreateCampaignModal() {
    document.getElementById('createCampaignModal').classList.add('show');
}

function hideCreateCampaignModal() {
    document.getElementById('createCampaignModal').classList.remove('show');
}

function showSubscribersModal() {
    alert('Subscribers management modal would open here');
}

function showEmailConfigModal() {
    alert('Email configuration modal would open here');
}

function exportSubscribers() {
    window.location.href = 'export_subscribers.php';
}

function viewCampaign(id) {
    alert('Campaign details modal would open for campaign ID: ' + id);
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});
</script>

<?php include '../admin/includes/admin_footer.php'; ?>
