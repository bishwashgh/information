<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/payment_gateway.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . SITE_URL . '/admin/login.php');
    exit();
}

$pageTitle = 'Payment Gateway Management';
$activePage = 'payments';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_gateway':
                updatePaymentGateway();
                break;
            case 'test_gateway':
                testPaymentGateway();
                break;
            case 'process_refund':
                processRefund();
                break;
        }
    }
}

function updatePaymentGateway() {
    global $conn, $message, $messageType;
    
    try {
        $gatewayId = intval($_POST['gateway_id']);
        $isEnabled = isset($_POST['is_enabled']) ? 1 : 0;
        $isSandbox = isset($_POST['is_sandbox']) ? 1 : 0;
        $displayName = $_POST['display_name'];
        $minAmount = floatval($_POST['min_amount']);
        $maxAmount = floatval($_POST['max_amount']);
        $feePercentage = floatval($_POST['processing_fee_percentage']);
        $feeFixed = floatval($_POST['processing_fee_fixed']);
        
        // Build configuration array
        $config = [];
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'config_') === 0) {
                $configKey = substr($key, 7); // Remove 'config_' prefix
                $config[$configKey] = $value;
            }
        }
        
        $stmt = $conn->prepare("
            UPDATE payment_methods 
            SET display_name = ?, is_enabled = ?, is_sandbox = ?, 
                min_amount = ?, max_amount = ?, 
                processing_fee_percentage = ?, processing_fee_fixed = ?,
                configuration = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $displayName, $isEnabled, $isSandbox,
            $minAmount, $maxAmount,
            $feePercentage, $feeFixed,
            json_encode($config),
            $gatewayId
        ]);
        
        $message = 'Payment gateway updated successfully!';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'Error updating payment gateway: ' . $e->getMessage();
        $messageType = 'error';
    }
}

function testPaymentGateway() {
    global $paymentManager, $message, $messageType;
    
    try {
        $gatewayId = intval($_POST['gateway_id']);
        $testAmount = 1.00; // $1 test amount
        
        // Create a test payment intent
        $result = $paymentManager->createPaymentIntent(
            0, // dummy order ID
            $testAmount,
            'USD',
            $gatewayId,
            ['test' => true]
        );
        
        if ($result['success']) {
            $message = 'Payment gateway test successful! Transaction ID: ' . $result['transaction_id'];
            $messageType = 'success';
        } else {
            $message = 'Payment gateway test failed: ' . $result['error'];
            $messageType = 'error';
        }
    } catch (Exception $e) {
        $message = 'Error testing payment gateway: ' . $e->getMessage();
        $messageType = 'error';
    }
}

function processRefund() {
    global $paymentManager, $message, $messageType;
    
    try {
        $transactionId = $_POST['transaction_id'];
        $refundAmount = floatval($_POST['refund_amount']);
        $reason = $_POST['refund_reason'];
        
        $result = $paymentManager->refundPayment($transactionId, $refundAmount, $reason);
        
        if ($result['success']) {
            $message = 'Refund processed successfully! Refund ID: ' . $result['refund_id'];
            $messageType = 'success';
        } else {
            $message = 'Refund failed: ' . $result['error'];
            $messageType = 'error';
        }
    } catch (Exception $e) {
        $message = 'Error processing refund: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get payment statistics
$paymentStats = $paymentManager->getPaymentStats(30);

// Get payment methods
try {
    $stmt = $conn->prepare("SELECT * FROM payment_methods ORDER BY gateway_name");
    $stmt->execute();
    $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $paymentMethods = [];
}

// Get recent transactions
$recentTransactions = $paymentManager->getTransactionHistory(null, null, 50);

// Get recent refunds
try {
    $stmt = $conn->prepare("
        SELECT pr.*, pt.transaction_id as parent_transaction_id, pt.amount as original_amount
        FROM payment_refunds pr
        JOIN payment_transactions pt ON pr.transaction_id = pt.id
        ORDER BY pr.created_at DESC
        LIMIT 20
    ");
    $stmt->execute();
    $recentRefunds = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recentRefunds = [];
}

include '../admin/includes/admin_header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1><i class="fas fa-credit-card"></i> Payment Gateway Management</h1>
        <p>Configure payment methods, monitor transactions, and manage refunds</p>
    </div>

    <?php if (isset($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> mb-4">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Payment Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-dollar-sign text-success"></i>
                </div>
                <div class="stat-content">
                    <h3>$<?php echo number_format($paymentStats['overview']['total_revenue'] ?? 0, 2); ?></h3>
                    <p>Total Revenue (30d)</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-exchange-alt text-primary"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($paymentStats['overview']['total_transactions'] ?? 0); ?></h3>
                    <p>Total Transactions</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check text-success"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($paymentStats['overview']['successful_transactions'] ?? 0); ?></h3>
                    <p>Successful Payments</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-undo text-warning"></i>
                </div>
                <div class="stat-content">
                    <h3>$<?php echo number_format($paymentStats['overview']['total_refunded'] ?? 0, 2); ?></h3>
                    <p>Total Refunded</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Methods Configuration -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-cog"></i> Payment Methods Configuration</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($paymentMethods as $method): ?>
                            <div class="col-md-6 mb-4">
                                <div class="payment-method-card <?php echo $method['is_enabled'] ? 'enabled' : 'disabled'; ?>">
                                    <div class="method-header">
                                        <h5><?php echo htmlspecialchars($method['display_name']); ?></h5>
                                        <div class="method-status">
                                            <?php if ($method['is_enabled']): ?>
                                                <span class="badge badge-success">Enabled</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Disabled</span>
                                            <?php endif; ?>
                                            <?php if ($method['is_sandbox']): ?>
                                                <span class="badge badge-warning">Sandbox</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <form method="POST" class="method-config-form">
                                        <input type="hidden" name="action" value="update_gateway">
                                        <input type="hidden" name="gateway_id" value="<?php echo $method['id']; ?>">
                                        
                                        <div class="form-group">
                                            <label>Display Name:</label>
                                            <input type="text" name="display_name" class="form-control" 
                                                   value="<?php echo htmlspecialchars($method['display_name']); ?>" required>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Min Amount ($):</label>
                                                    <input type="number" name="min_amount" class="form-control" 
                                                           value="<?php echo $method['min_amount']; ?>" step="0.01" min="0">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Max Amount ($):</label>
                                                    <input type="number" name="max_amount" class="form-control" 
                                                           value="<?php echo $method['max_amount']; ?>" step="0.01" min="0">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Fee Percentage (%):</label>
                                                    <input type="number" name="processing_fee_percentage" class="form-control" 
                                                           value="<?php echo $method['processing_fee_percentage'] * 100; ?>" 
                                                           step="0.01" min="0" max="100">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Fixed Fee ($):</label>
                                                    <input type="number" name="processing_fee_fixed" class="form-control" 
                                                           value="<?php echo $method['processing_fee_fixed']; ?>" step="0.01" min="0">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Gateway specific configuration -->
                                        <?php 
                                        $config = json_decode($method['configuration'], true) ?: [];
                                        switch ($method['gateway_name']):
                                            case 'stripe': ?>
                                                <div class="config-section">
                                                    <h6>Stripe Configuration</h6>
                                                    <div class="form-group">
                                                        <label>Publishable Key:</label>
                                                        <input type="text" name="config_publishable_key" class="form-control" 
                                                               value="<?php echo htmlspecialchars($config['publishable_key'] ?? ''); ?>"
                                                               placeholder="pk_test_...">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Secret Key:</label>
                                                        <input type="password" name="config_secret_key" class="form-control" 
                                                               value="<?php echo htmlspecialchars($config['secret_key'] ?? ''); ?>"
                                                               placeholder="sk_test_...">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Webhook Secret:</label>
                                                        <input type="password" name="config_webhook_secret" class="form-control" 
                                                               value="<?php echo htmlspecialchars($config['webhook_secret'] ?? ''); ?>"
                                                               placeholder="whsec_...">
                                                    </div>
                                                </div>
                                                <?php break;
                                            case 'paypal': ?>
                                                <div class="config-section">
                                                    <h6>PayPal Configuration</h6>
                                                    <div class="form-group">
                                                        <label>Client ID:</label>
                                                        <input type="text" name="config_client_id" class="form-control" 
                                                               value="<?php echo htmlspecialchars($config['client_id'] ?? ''); ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Client Secret:</label>
                                                        <input type="password" name="config_client_secret" class="form-control" 
                                                               value="<?php echo htmlspecialchars($config['client_secret'] ?? ''); ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Webhook ID:</label>
                                                        <input type="text" name="config_webhook_id" class="form-control" 
                                                               value="<?php echo htmlspecialchars($config['webhook_id'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                                <?php break;
                                            case 'razorpay': ?>
                                                <div class="config-section">
                                                    <h6>Razorpay Configuration</h6>
                                                    <div class="form-group">
                                                        <label>Key ID:</label>
                                                        <input type="text" name="config_key_id" class="form-control" 
                                                               value="<?php echo htmlspecialchars($config['key_id'] ?? ''); ?>"
                                                               placeholder="rzp_test_...">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Key Secret:</label>
                                                        <input type="password" name="config_key_secret" class="form-control" 
                                                               value="<?php echo htmlspecialchars($config['key_secret'] ?? ''); ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Webhook Secret:</label>
                                                        <input type="password" name="config_webhook_secret" class="form-control" 
                                                               value="<?php echo htmlspecialchars($config['webhook_secret'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                                <?php break;
                                        endswitch; ?>
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="is_enabled" 
                                                   id="enabled_<?php echo $method['id']; ?>" 
                                                   <?php echo $method['is_enabled'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="enabled_<?php echo $method['id']; ?>">
                                                Enable this payment method
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="is_sandbox" 
                                                   id="sandbox_<?php echo $method['id']; ?>" 
                                                   <?php echo $method['is_sandbox'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="sandbox_<?php echo $method['id']; ?>">
                                                Sandbox mode (testing)
                                            </label>
                                        </div>
                                        
                                        <div class="method-actions">
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-save"></i> Update
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                    onclick="testGateway(<?php echo $method['id']; ?>)">
                                                <i class="fas fa-vial"></i> Test
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Recent Transactions</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Transaction ID</th>
                                    <th>Order</th>
                                    <th>Gateway</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentTransactions)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No transactions found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentTransactions as $transaction): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y H:i', strtotime($transaction['created_at'])); ?></td>
                                            <td class="text-monospace"><?php echo htmlspecialchars($transaction['transaction_id']); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['order_number'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['display_name']); ?></td>
                                            <td>$<?php echo number_format($transaction['amount'], 2); ?></td>
                                            <td>
                                                <?php
                                                $statusClass = [
                                                    'completed' => 'success',
                                                    'pending' => 'warning',
                                                    'processing' => 'info',
                                                    'failed' => 'danger',
                                                    'cancelled' => 'secondary',
                                                    'refunded' => 'dark',
                                                    'partially_refunded' => 'warning'
                                                ];
                                                $class = $statusClass[$transaction['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge badge-<?php echo $class; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $transaction['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-info" 
                                                            onclick="viewTransaction('<?php echo $transaction['transaction_id']; ?>')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($transaction['status'] === 'completed' && 
                                                              $transaction['refunded_amount'] < $transaction['amount']): ?>
                                                        <button class="btn btn-outline-warning" 
                                                                onclick="showRefundModal('<?php echo $transaction['transaction_id']; ?>', <?php echo $transaction['amount'] - $transaction['refunded_amount']; ?>)">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
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

    <!-- Recent Refunds -->
    <?php if (!empty($recentRefunds)): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-undo"></i> Recent Refunds</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Refund ID</th>
                                    <th>Original Transaction</th>
                                    <th>Amount</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentRefunds as $refund): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y H:i', strtotime($refund['created_at'])); ?></td>
                                        <td class="text-monospace"><?php echo htmlspecialchars($refund['refund_id']); ?></td>
                                        <td class="text-monospace"><?php echo htmlspecialchars($refund['parent_transaction_id']); ?></td>
                                        <td>$<?php echo number_format($refund['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($refund['reason'] ?: 'No reason provided'); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = [
                                                'completed' => 'success',
                                                'pending' => 'warning',
                                                'processing' => 'info',
                                                'failed' => 'danger',
                                                'cancelled' => 'secondary'
                                            ];
                                            $class = $statusClass[$refund['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge badge-<?php echo $class; ?>">
                                                <?php echo ucfirst($refund['status']); ?>
                                            </span>
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
</div>

<!-- Refund Modal -->
<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Process Refund</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="process_refund">
                    <input type="hidden" name="transaction_id" id="refund_transaction_id">
                    
                    <div class="form-group">
                        <label for="refund_amount">Refund Amount ($):</label>
                        <input type="number" name="refund_amount" id="refund_amount" class="form-control" 
                               step="0.01" min="0.01" required>
                        <small class="form-text text-muted">Maximum available: $<span id="max_refund_amount"></span></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="refund_reason">Reason for Refund:</label>
                        <textarea name="refund_reason" id="refund_reason" class="form-control" rows="3" 
                                  placeholder="Optional reason for the refund..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Process Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.payment-method-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    transition: all 0.3s ease;
}

.payment-method-card.enabled {
    border-color: #28a745;
    background-color: #f8fff9;
}

.payment-method-card.disabled {
    border-color: #dc3545;
    background-color: #fff8f8;
    opacity: 0.8;
}

.method-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.method-status .badge {
    margin-left: 5px;
}

.config-section {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin: 15px 0;
}

.config-section h6 {
    margin-bottom: 15px;
    color: #495057;
}

.method-actions {
    text-align: center;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.method-actions .btn {
    margin: 0 5px;
}
</style>

<script>
function testGateway(gatewayId) {
    if (confirm('This will perform a test transaction. Continue?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="test_gateway">
            <input type="hidden" name="gateway_id" value="${gatewayId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function showRefundModal(transactionId, maxAmount) {
    document.getElementById('refund_transaction_id').value = transactionId;
    document.getElementById('refund_amount').max = maxAmount;
    document.getElementById('max_refund_amount').textContent = maxAmount.toFixed(2);
    $('#refundModal').modal('show');
}

function viewTransaction(transactionId) {
    // Implement transaction details view
    window.open(`<?php echo SITE_URL; ?>/admin/transaction_details.php?id=${transactionId}`, '_blank');
}

// Auto-save gateway configuration on change
document.querySelectorAll('.method-config-form input, .method-config-form select').forEach(element => {
    element.addEventListener('change', function() {
        this.closest('form').style.borderLeft = '4px solid #ffc107';
    });
});
</script>

<?php include '../admin/includes/admin_footer.php'; ?>
