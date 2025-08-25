<?php
// API endpoints for payment management
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/payment_gateway.php';

header('Content-Type: application/json');

// Check authentication for most endpoints
$publicEndpoints = ['webhook', 'success', 'cancel'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (!in_array($action, $publicEndpoints)) {
    if (!isLoggedIn()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit();
    }
}

// Check admin access for admin endpoints
$adminEndpoints = ['configure_gateway', 'get_transactions', 'process_refund', 'export_transactions'];
if (in_array($action, $adminEndpoints) && !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit();
}

$paymentManager = new PaymentManager($conn);

switch ($action) {
    case 'create_payment':
        // Create a payment intent
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['amount']) || !isset($input['currency']) || !isset($input['gateway'])) {
            echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
            break;
        }
        
        try {
            $result = $paymentManager->createPayment(
                $input['amount'],
                $input['currency'],
                $input['gateway'],
                $_SESSION['user_id'],
                $input['description'] ?? 'Order payment',
                $input['metadata'] ?? []
            );
            
            echo json_encode(['success' => true, 'payment' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'process_payment':
        // Process a payment
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['payment_intent_id']) || !isset($input['payment_method'])) {
            echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
            break;
        }
        
        try {
            $result = $paymentManager->processPayment(
                $input['payment_intent_id'],
                $input['payment_method'],
                $input['billing_details'] ?? []
            );
            
            echo json_encode(['success' => true, 'result' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'get_payment_status':
        // Get payment status
        $paymentId = $_GET['payment_id'] ?? '';
        
        if (!$paymentId) {
            echo json_encode(['success' => false, 'error' => 'Payment ID required']);
            break;
        }
        
        try {
            $status = $paymentManager->getPaymentStatus($paymentId);
            echo json_encode(['success' => true, 'status' => $status]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'get_payment_methods':
        // Get available payment methods for gateway
        $gateway = $_GET['gateway'] ?? '';
        
        if (!$gateway) {
            echo json_encode(['success' => false, 'error' => 'Gateway required']);
            break;
        }
        
        try {
            $methods = $paymentManager->getPaymentMethods($gateway);
            echo json_encode(['success' => true, 'methods' => $methods]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'process_refund':
        // Process a refund (Admin only)
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['transaction_id'])) {
            echo json_encode(['success' => false, 'error' => 'Transaction ID required']);
            break;
        }
        
        try {
            $result = $paymentManager->processRefund(
                $input['transaction_id'],
                $input['amount'] ?? null,
                $input['reason'] ?? 'Admin refund'
            );
            
            echo json_encode(['success' => true, 'refund' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'configure_gateway':
        // Configure payment gateway (Admin only)
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['gateway']) || !isset($input['config'])) {
            echo json_encode(['success' => false, 'error' => 'Gateway and config required']);
            break;
        }
        
        try {
            $success = $paymentManager->configureGateway($input['gateway'], $input['config']);
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Gateway configured successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to configure gateway']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'get_transactions':
        // Get transaction history (Admin only)
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 50);
        $status = $_GET['status'] ?? '';
        $gateway = $_GET['gateway'] ?? '';
        
        try {
            $transactions = $paymentManager->getTransactions($page, $limit, $status, $gateway);
            echo json_encode(['success' => true, 'transactions' => $transactions]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'get_transaction_details':
        // Get detailed transaction information
        $transactionId = $_GET['transaction_id'] ?? '';
        
        if (!$transactionId) {
            echo json_encode(['success' => false, 'error' => 'Transaction ID required']);
            break;
        }
        
        try {
            $details = $paymentManager->getTransactionDetails($transactionId);
            echo json_encode(['success' => true, 'transaction' => $details]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'export_transactions':
        // Export transactions as CSV (Admin only)
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $status = $_GET['status'] ?? '';
        
        try {
            $stmt = $conn->prepare("
                SELECT t.*, u.username, u.email
                FROM payment_transactions t
                LEFT JOIN users u ON t.user_id = u.id
                WHERE DATE(t.created_at) BETWEEN ? AND ?
                " . ($status ? "AND t.status = ?" : "") . "
                ORDER BY t.created_at DESC
            ");
            
            $params = [$startDate, $endDate];
            if ($status) $params[] = $status;
            
            $stmt->execute($params);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Generate CSV
            $csv = "Date,Transaction ID,Username,Email,Amount,Currency,Gateway,Status,Payment Method\n";
            foreach ($transactions as $t) {
                $csv .= sprintf('"%s","%s","%s","%s","%.2f","%s","%s","%s","%s"' . "\n",
                    $t['created_at'],
                    $t['transaction_id'],
                    $t['username'] ?? 'Guest',
                    $t['email'] ?? '',
                    $t['amount'],
                    $t['currency'],
                    $t['gateway'],
                    $t['status'],
                    $t['payment_method'] ?? ''
                );
            }
            
            // Set headers for download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="transactions_' . $startDate . '_to_' . $endDate . '.csv"');
            header('Content-Length: ' . strlen($csv));
            
            echo $csv;
            exit();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Failed to export transactions']);
        }
        break;
        
    case 'webhook':
        // Handle payment gateway webhooks
        $gateway = $_GET['gateway'] ?? $_POST['gateway'] ?? '';
        
        if (!$gateway) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Gateway not specified']);
            break;
        }
        
        try {
            $payload = file_get_contents('php://input');
            $headers = getallheaders();
            
            $result = $paymentManager->handleWebhook($gateway, $payload, $headers);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Webhook processing failed']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'success':
        // Payment success callback
        $paymentId = $_GET['payment_id'] ?? '';
        $sessionId = $_GET['session_id'] ?? '';
        
        try {
            if ($paymentId) {
                $status = $paymentManager->getPaymentStatus($paymentId);
                echo json_encode(['success' => true, 'status' => $status]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Payment ID required']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'cancel':
        // Payment cancellation callback
        $paymentId = $_GET['payment_id'] ?? '';
        
        try {
            if ($paymentId) {
                // Mark payment as cancelled
                $stmt = $conn->prepare("UPDATE payment_transactions SET status = 'cancelled' WHERE payment_intent_id = ?");
                $stmt->execute([$paymentId]);
                
                echo json_encode(['success' => true, 'message' => 'Payment cancelled']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Payment ID required']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'validate_card':
        // Validate credit card details
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['card_number']) || !isset($input['exp_month']) || 
            !isset($input['exp_year']) || !isset($input['cvc'])) {
            echo json_encode(['success' => false, 'error' => 'Missing card details']);
            break;
        }
        
        // Basic validation (you might want to use a more robust validation library)
        $cardNumber = preg_replace('/\D/', '', $input['card_number']);
        $expMonth = intval($input['exp_month']);
        $expYear = intval($input['exp_year']);
        $cvc = $input['cvc'];
        
        $errors = [];
        
        // Validate card number (Luhn algorithm)
        if (!$paymentManager->validateCardNumber($cardNumber)) {
            $errors[] = 'Invalid card number';
        }
        
        // Validate expiry
        if ($expMonth < 1 || $expMonth > 12) {
            $errors[] = 'Invalid expiry month';
        }
        
        $currentYear = intval(date('Y'));
        $currentMonth = intval(date('m'));
        
        if ($expYear < $currentYear || ($expYear == $currentYear && $expMonth < $currentMonth)) {
            $errors[] = 'Card has expired';
        }
        
        // Validate CVC
        if (!preg_match('/^\d{3,4}$/', $cvc)) {
            $errors[] = 'Invalid CVC';
        }
        
        echo json_encode([
            'success' => empty($errors),
            'errors' => $errors,
            'card_type' => empty($errors) ? $paymentManager->getCardType($cardNumber) : null
        ]);
        break;
        
    case 'get_payment_stats':
        // Get payment statistics (Admin only)
        $days = intval($_GET['days'] ?? 30);
        
        try {
            $stats = $paymentManager->getPaymentStats($days);
            echo json_encode(['success' => true, 'stats' => $stats]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        break;
}
?>
