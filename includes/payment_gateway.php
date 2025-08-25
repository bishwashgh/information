<?php
// Payment Gateway Integration Manager

class PaymentManager {
    private $conn;
    private $config;
    private $gateways;
    
    public function __construct($database) {
        $this->conn = $database;
        $this->initializePaymentTables();
        $this->loadPaymentGateways();
        $this->config = [
            'default_currency' => 'USD',
            'sandbox_mode' => true,
            'webhook_timeout' => 30,
            'max_payment_amount' => 10000.00,
            'min_payment_amount' => 0.50
        ];
    }
    
    private function initializePaymentTables() {
        // Payment methods configuration
        $sql = "CREATE TABLE IF NOT EXISTS payment_methods (
            id INT PRIMARY KEY AUTO_INCREMENT,
            gateway_name VARCHAR(50) NOT NULL,
            display_name VARCHAR(100) NOT NULL,
            is_enabled BOOLEAN DEFAULT TRUE,
            is_sandbox BOOLEAN DEFAULT TRUE,
            configuration JSON,
            supported_currencies JSON,
            supported_countries JSON,
            min_amount DECIMAL(10,2) DEFAULT 0.50,
            max_amount DECIMAL(10,2) DEFAULT 10000.00,
            processing_fee_percentage DECIMAL(5,4) DEFAULT 0.0000,
            processing_fee_fixed DECIMAL(10,2) DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $this->conn->exec($sql);
        
        // Payment transactions
        $sql = "CREATE TABLE IF NOT EXISTS payment_transactions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            order_id INT NOT NULL,
            user_id INT NULL,
            payment_method_id INT NOT NULL,
            gateway_name VARCHAR(50) NOT NULL,
            transaction_id VARCHAR(255) UNIQUE NOT NULL,
            gateway_transaction_id VARCHAR(255),
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'USD',
            status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded', 'partially_refunded') DEFAULT 'pending',
            gateway_status VARCHAR(100),
            gateway_response JSON,
            payment_method_details JSON,
            fees_charged DECIMAL(10,2) DEFAULT 0.00,
            net_amount DECIMAL(10,2) NOT NULL,
            gateway_fee DECIMAL(10,2) DEFAULT 0.00,
            refunded_amount DECIMAL(10,2) DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            INDEX idx_order_id (order_id),
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_gateway_transaction_id (gateway_transaction_id),
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id)
        )";
        $this->conn->exec($sql);
        
        // Payment webhooks log
        $sql = "CREATE TABLE IF NOT EXISTS payment_webhooks (
            id INT PRIMARY KEY AUTO_INCREMENT,
            gateway_name VARCHAR(50) NOT NULL,
            webhook_id VARCHAR(255),
            event_type VARCHAR(100) NOT NULL,
            transaction_id VARCHAR(255),
            payload JSON NOT NULL,
            signature VARCHAR(255),
            is_verified BOOLEAN DEFAULT FALSE,
            is_processed BOOLEAN DEFAULT FALSE,
            processing_result JSON,
            received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            processed_at TIMESTAMP NULL,
            INDEX idx_gateway_name (gateway_name),
            INDEX idx_transaction_id (transaction_id),
            INDEX idx_is_processed (is_processed)
        )";
        $this->conn->exec($sql);
        
        // Saved payment methods for users
        $sql = "CREATE TABLE IF NOT EXISTS user_payment_methods (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            payment_method_id INT NOT NULL,
            token VARCHAR(255) NOT NULL,
            is_default BOOLEAN DEFAULT FALSE,
            card_last_four VARCHAR(4),
            card_brand VARCHAR(20),
            card_exp_month INT,
            card_exp_year INT,
            billing_address JSON,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id)
        )";
        $this->conn->exec($sql);
        
        // Payment refunds
        $sql = "CREATE TABLE IF NOT EXISTS payment_refunds (
            id INT PRIMARY KEY AUTO_INCREMENT,
            transaction_id INT NOT NULL,
            refund_id VARCHAR(255) UNIQUE NOT NULL,
            gateway_refund_id VARCHAR(255),
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'USD',
            reason TEXT,
            status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
            gateway_response JSON,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            INDEX idx_transaction_id (transaction_id),
            FOREIGN KEY (transaction_id) REFERENCES payment_transactions(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        )";
        $this->conn->exec($sql);
        
        // Payment disputes/chargebacks
        $sql = "CREATE TABLE IF NOT EXISTS payment_disputes (
            id INT PRIMARY KEY AUTO_INCREMENT,
            transaction_id INT NOT NULL,
            dispute_id VARCHAR(255) UNIQUE NOT NULL,
            gateway_dispute_id VARCHAR(255),
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'USD',
            reason_code VARCHAR(50),
            reason_description TEXT,
            status ENUM('warning_needs_response', 'warning_under_review', 'warning_closed', 'needs_response', 'under_review', 'charge_refunded', 'won', 'lost') DEFAULT 'needs_response',
            evidence_due_by TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_transaction_id (transaction_id),
            INDEX idx_status (status),
            FOREIGN KEY (transaction_id) REFERENCES payment_transactions(id) ON DELETE CASCADE
        )";
        $this->conn->exec($sql);
        
        $this->seedPaymentMethods();
    }
    
    private function seedPaymentMethods() {
        $methods = [
            [
                'gateway_name' => 'stripe',
                'display_name' => 'Credit/Debit Card (Stripe)',
                'is_enabled' => true,
                'is_sandbox' => true,
                'configuration' => json_encode([
                    'publishable_key' => 'pk_test_...',
                    'secret_key' => 'sk_test_...',
                    'webhook_secret' => 'whsec_...'
                ]),
                'supported_currencies' => json_encode(['USD', 'EUR', 'GBP', 'CAD', 'AUD']),
                'supported_countries' => json_encode(['US', 'CA', 'GB', 'AU', 'DE', 'FR', 'IT', 'ES']),
                'processing_fee_percentage' => 0.029,
                'processing_fee_fixed' => 0.30
            ],
            [
                'gateway_name' => 'paypal',
                'display_name' => 'PayPal',
                'is_enabled' => true,
                'is_sandbox' => true,
                'configuration' => json_encode([
                    'client_id' => 'your_paypal_client_id',
                    'client_secret' => 'your_paypal_client_secret',
                    'webhook_id' => 'your_webhook_id'
                ]),
                'supported_currencies' => json_encode(['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY']),
                'supported_countries' => json_encode(['US', 'CA', 'GB', 'AU', 'DE', 'FR', 'IT', 'ES', 'JP']),
                'processing_fee_percentage' => 0.0349,
                'processing_fee_fixed' => 0.49
            ],
            [
                'gateway_name' => 'razorpay',
                'display_name' => 'Razorpay (UPI, Cards, Net Banking)',
                'is_enabled' => true,
                'is_sandbox' => true,
                'configuration' => json_encode([
                    'key_id' => 'rzp_test_...',
                    'key_secret' => 'your_razorpay_secret',
                    'webhook_secret' => 'your_webhook_secret'
                ]),
                'supported_currencies' => json_encode(['INR']),
                'supported_countries' => json_encode(['IN']),
                'processing_fee_percentage' => 0.0200,
                'processing_fee_fixed' => 0.00
            ],
            [
                'gateway_name' => 'square',
                'display_name' => 'Square',
                'is_enabled' => false,
                'is_sandbox' => true,
                'configuration' => json_encode([
                    'application_id' => 'your_square_app_id',
                    'access_token' => 'your_square_access_token',
                    'location_id' => 'your_location_id'
                ]),
                'supported_currencies' => json_encode(['USD', 'CAD', 'GBP', 'AUD', 'EUR', 'JPY']),
                'supported_countries' => json_encode(['US', 'CA', 'GB', 'AU', 'IE', 'ES', 'FR', 'JP']),
                'processing_fee_percentage' => 0.0265,
                'processing_fee_fixed' => 0.10
            ]
        ];
        
        foreach ($methods as $method) {
            $stmt = $this->conn->prepare("
                INSERT IGNORE INTO payment_methods 
                (gateway_name, display_name, is_enabled, is_sandbox, configuration, supported_currencies, supported_countries, processing_fee_percentage, processing_fee_fixed) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $method['gateway_name'],
                $method['display_name'],
                $method['is_enabled'],
                $method['is_sandbox'],
                $method['configuration'],
                $method['supported_currencies'],
                $method['supported_countries'],
                $method['processing_fee_percentage'],
                $method['processing_fee_fixed']
            ]);
        }
    }
    
    private function loadPaymentGateways() {
        // Initialize payment gateway handlers
        $this->gateways = [
            'stripe' => new StripeGateway($this->conn),
            'paypal' => new PayPalGateway($this->conn),
            'razorpay' => new RazorpayGateway($this->conn),
            'square' => new SquareGateway($this->conn)
        ];
    }
    
    public function getAvailablePaymentMethods($amount = null, $currency = 'USD', $country = 'US') {
        try {
            $sql = "SELECT * FROM payment_methods WHERE is_enabled = TRUE";
            $params = [];
            
            if ($amount !== null) {
                $sql .= " AND min_amount <= ? AND max_amount >= ?";
                $params[] = $amount;
                $params[] = $amount;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Filter by currency and country support
            $availableMethods = [];
            foreach ($methods as $method) {
                $supportedCurrencies = json_decode($method['supported_currencies'], true) ?: [];
                $supportedCountries = json_decode($method['supported_countries'], true) ?: [];
                
                if (in_array($currency, $supportedCurrencies) && in_array($country, $supportedCountries)) {
                    $availableMethods[] = $method;
                }
            }
            
            return $availableMethods;
        } catch (Exception $e) {
            error_log("Get payment methods error: " . $e->getMessage());
            return [];
        }
    }
    
    public function createPaymentIntent($orderId, $amount, $currency, $paymentMethodId, $paymentData = []) {
        try {
            // Get payment method details
            $stmt = $this->conn->prepare("SELECT * FROM payment_methods WHERE id = ? AND is_enabled = TRUE");
            $stmt->execute([$paymentMethodId]);
            $paymentMethod = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$paymentMethod) {
                throw new Exception("Payment method not found or disabled");
            }
            
            // Validate amount
            if ($amount < $this->config['min_payment_amount'] || $amount > $this->config['max_payment_amount']) {
                throw new Exception("Payment amount is outside allowed limits");
            }
            
            // Calculate fees
            $fees = $this->calculateFees($amount, $paymentMethod);
            $netAmount = $amount - $fees['total'];
            
            // Generate unique transaction ID
            $transactionId = $this->generateTransactionId();
            
            // Create transaction record
            $stmt = $this->conn->prepare("
                INSERT INTO payment_transactions 
                (order_id, user_id, payment_method_id, gateway_name, transaction_id, amount, currency, net_amount, fees_charged, payment_method_details, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $orderId,
                $_SESSION['user_id'] ?? null,
                $paymentMethodId,
                $paymentMethod['gateway_name'],
                $transactionId,
                $amount,
                $currency,
                $netAmount,
                $fees['total'],
                json_encode($paymentData)
            ]);
            
            // Create payment intent with gateway
            $gateway = $this->gateways[$paymentMethod['gateway_name']];
            $intentResult = $gateway->createPaymentIntent($transactionId, $amount, $currency, $paymentData);
            
            if ($intentResult['success']) {
                // Update transaction with gateway details
                $stmt = $this->conn->prepare("
                    UPDATE payment_transactions 
                    SET gateway_transaction_id = ?, gateway_response = ?, status = 'processing' 
                    WHERE transaction_id = ?
                ");
                $stmt->execute([
                    $intentResult['gateway_transaction_id'],
                    json_encode($intentResult['response']),
                    $transactionId
                ]);
                
                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'gateway_data' => $intentResult['response'],
                    'fees' => $fees
                ];
            } else {
                // Update transaction status to failed
                $stmt = $this->conn->prepare("
                    UPDATE payment_transactions 
                    SET status = 'failed', gateway_response = ? 
                    WHERE transaction_id = ?
                ");
                $stmt->execute([
                    json_encode($intentResult['error']),
                    $transactionId
                ]);
                
                return [
                    'success' => false,
                    'error' => $intentResult['error'],
                    'transaction_id' => $transactionId
                ];
            }
        } catch (Exception $e) {
            error_log("Create payment intent error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function confirmPayment($transactionId, $confirmationData = []) {
        try {
            // Get transaction details
            $stmt = $this->conn->prepare("SELECT * FROM payment_transactions WHERE transaction_id = ?");
            $stmt->execute([$transactionId]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                throw new Exception("Transaction not found");
            }
            
            if ($transaction['status'] !== 'processing') {
                throw new Exception("Transaction cannot be confirmed in current status: " . $transaction['status']);
            }
            
            // Confirm payment with gateway
            $gateway = $this->gateways[$transaction['gateway_name']];
            $confirmResult = $gateway->confirmPayment($transaction['gateway_transaction_id'], $confirmationData);
            
            if ($confirmResult['success']) {
                // Update transaction status
                $stmt = $this->conn->prepare("
                    UPDATE payment_transactions 
                    SET status = 'completed', gateway_response = ?, completed_at = NOW() 
                    WHERE transaction_id = ?
                ");
                $stmt->execute([
                    json_encode($confirmResult['response']),
                    $transactionId
                ]);
                
                // Update order payment status
                $stmt = $this->conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
                $stmt->execute([$transaction['order_id']]);
                
                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'gateway_data' => $confirmResult['response']
                ];
            } else {
                // Update transaction status to failed
                $stmt = $this->conn->prepare("
                    UPDATE payment_transactions 
                    SET status = 'failed', gateway_response = ? 
                    WHERE transaction_id = ?
                ");
                $stmt->execute([
                    json_encode($confirmResult['error']),
                    $transactionId
                ]);
                
                return [
                    'success' => false,
                    'error' => $confirmResult['error'],
                    'transaction_id' => $transactionId
                ];
            }
        } catch (Exception $e) {
            error_log("Confirm payment error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function refundPayment($transactionId, $amount = null, $reason = '') {
        try {
            // Get transaction details
            $stmt = $this->conn->prepare("SELECT * FROM payment_transactions WHERE transaction_id = ?");
            $stmt->execute([$transactionId]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$transaction || $transaction['status'] !== 'completed') {
                throw new Exception("Transaction not found or cannot be refunded");
            }
            
            $refundAmount = $amount ?? $transaction['amount'];
            
            // Check if refund amount is valid
            if ($refundAmount > ($transaction['amount'] - $transaction['refunded_amount'])) {
                throw new Exception("Refund amount exceeds available balance");
            }
            
            // Generate refund ID
            $refundId = $this->generateRefundId();
            
            // Create refund record
            $stmt = $this->conn->prepare("
                INSERT INTO payment_refunds 
                (transaction_id, refund_id, amount, currency, reason, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $transaction['id'],
                $refundId,
                $refundAmount,
                $transaction['currency'],
                $reason,
                $_SESSION['user_id'] ?? null
            ]);
            
            // Process refund with gateway
            $gateway = $this->gateways[$transaction['gateway_name']];
            $refundResult = $gateway->refundPayment($transaction['gateway_transaction_id'], $refundAmount, $reason);
            
            if ($refundResult['success']) {
                // Update refund record
                $stmt = $this->conn->prepare("
                    UPDATE payment_refunds 
                    SET status = 'completed', gateway_refund_id = ?, gateway_response = ?, completed_at = NOW() 
                    WHERE refund_id = ?
                ");
                $stmt->execute([
                    $refundResult['gateway_refund_id'],
                    json_encode($refundResult['response']),
                    $refundId
                ]);
                
                // Update transaction refunded amount
                $stmt = $this->conn->prepare("
                    UPDATE payment_transactions 
                    SET refunded_amount = refunded_amount + ?, 
                        status = CASE WHEN refunded_amount + ? >= amount THEN 'refunded' ELSE 'partially_refunded' END 
                    WHERE id = ?
                ");
                $stmt->execute([$refundAmount, $refundAmount, $transaction['id']]);
                
                return [
                    'success' => true,
                    'refund_id' => $refundId,
                    'amount' => $refundAmount,
                    'gateway_data' => $refundResult['response']
                ];
            } else {
                // Update refund status to failed
                $stmt = $this->conn->prepare("
                    UPDATE payment_refunds 
                    SET status = 'failed', gateway_response = ? 
                    WHERE refund_id = ?
                ");
                $stmt->execute([
                    json_encode($refundResult['error']),
                    $refundId
                ]);
                
                return [
                    'success' => false,
                    'error' => $refundResult['error'],
                    'refund_id' => $refundId
                ];
            }
        } catch (Exception $e) {
            error_log("Refund payment error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function handleWebhook($gatewayName, $payload, $signature = null) {
        try {
            // Log webhook
            $stmt = $this->conn->prepare("
                INSERT INTO payment_webhooks (gateway_name, event_type, payload, signature) 
                VALUES (?, ?, ?, ?)
            ");
            $eventType = $payload['type'] ?? $payload['event_type'] ?? 'unknown';
            $stmt->execute([$gatewayName, $eventType, json_encode($payload), $signature]);
            $webhookId = $this->conn->lastInsertId();
            
            // Verify webhook signature
            $gateway = $this->gateways[$gatewayName];
            $isVerified = $gateway->verifyWebhookSignature($payload, $signature);
            
            // Update verification status
            $stmt = $this->conn->prepare("UPDATE payment_webhooks SET is_verified = ? WHERE id = ?");
            $stmt->execute([$isVerified, $webhookId]);
            
            if (!$isVerified) {
                throw new Exception("Webhook signature verification failed");
            }
            
            // Process webhook
            $result = $gateway->processWebhook($payload);
            
            // Update processing status
            $stmt = $this->conn->prepare("
                UPDATE payment_webhooks 
                SET is_processed = TRUE, processing_result = ?, processed_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([json_encode($result), $webhookId]);
            
            return $result;
        } catch (Exception $e) {
            error_log("Webhook handling error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function calculateFees($amount, $paymentMethod) {
        $percentage = $paymentMethod['processing_fee_percentage'];
        $fixed = $paymentMethod['processing_fee_fixed'];
        
        $percentageFee = $amount * $percentage;
        $total = $percentageFee + $fixed;
        
        return [
            'percentage_fee' => round($percentageFee, 2),
            'fixed_fee' => $fixed,
            'total' => round($total, 2)
        ];
    }
    
    private function generateTransactionId() {
        return 'txn_' . uniqid() . '_' . random_int(1000, 9999);
    }
    
    private function generateRefundId() {
        return 'ref_' . uniqid() . '_' . random_int(1000, 9999);
    }
    
    public function getTransactionHistory($userId = null, $orderId = null, $limit = 50) {
        try {
            $sql = "
                SELECT pt.*, pm.display_name, o.order_number 
                FROM payment_transactions pt
                JOIN payment_methods pm ON pt.payment_method_id = pm.id
                JOIN orders o ON pt.order_id = o.id
                WHERE 1=1
            ";
            $params = [];
            
            if ($userId) {
                $sql .= " AND pt.user_id = ?";
                $params[] = $userId;
            }
            
            if ($orderId) {
                $sql .= " AND pt.order_id = ?";
                $params[] = $orderId;
            }
            
            $sql .= " ORDER BY pt.created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get transaction history error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getPaymentStats($days = 30) {
        try {
            $stats = [];
            
            // Revenue and transaction stats
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(*) as total_transactions,
                    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_transactions,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_transactions,
                    SUM(CASE WHEN status = 'refunded' OR status = 'partially_refunded' THEN refunded_amount ELSE 0 END) as total_refunded,
                    AVG(CASE WHEN status = 'completed' THEN amount ELSE NULL END) as average_transaction_amount
                FROM payment_transactions 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$days]);
            $stats['overview'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Payment method breakdown
            $stmt = $this->conn->prepare("
                SELECT pm.display_name, COUNT(*) as transaction_count, SUM(pt.amount) as total_amount
                FROM payment_transactions pt
                JOIN payment_methods pm ON pt.payment_method_id = pm.id
                WHERE pt.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                AND pt.status = 'completed'
                GROUP BY pm.id
                ORDER BY total_amount DESC
            ");
            $stmt->execute([$days]);
            $stats['by_method'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
        } catch (Exception $e) {
            error_log("Get payment stats error: " . $e->getMessage());
            return [];
        }
    }
}

// Base Gateway Interface
abstract class PaymentGateway {
    protected $conn;
    protected $config;
    
    public function __construct($database) {
        $this->conn = $database;
        $this->loadConfig();
    }
    
    abstract protected function loadConfig();
    abstract public function createPaymentIntent($transactionId, $amount, $currency, $paymentData);
    abstract public function confirmPayment($gatewayTransactionId, $confirmationData);
    abstract public function refundPayment($gatewayTransactionId, $amount, $reason);
    abstract public function verifyWebhookSignature($payload, $signature);
    abstract public function processWebhook($payload);
}

// Stripe Gateway Implementation (Demo)
class StripeGateway extends PaymentGateway {
    protected function loadConfig() {
        $stmt = $this->conn->prepare("SELECT configuration FROM payment_methods WHERE gateway_name = 'stripe'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->config = $result ? json_decode($result['configuration'], true) : [];
    }
    
    public function createPaymentIntent($transactionId, $amount, $currency, $paymentData) {
        // Demo implementation - replace with actual Stripe API calls
        return [
            'success' => true,
            'gateway_transaction_id' => 'pi_demo_' . uniqid(),
            'response' => [
                'client_secret' => 'pi_demo_' . uniqid() . '_secret_demo',
                'status' => 'requires_payment_method'
            ]
        ];
    }
    
    public function confirmPayment($gatewayTransactionId, $confirmationData) {
        // Demo implementation
        return [
            'success' => true,
            'response' => [
                'status' => 'succeeded',
                'payment_method' => $confirmationData['payment_method'] ?? 'card_demo'
            ]
        ];
    }
    
    public function refundPayment($gatewayTransactionId, $amount, $reason) {
        // Demo implementation
        return [
            'success' => true,
            'gateway_refund_id' => 're_demo_' . uniqid(),
            'response' => [
                'status' => 'succeeded',
                'amount' => $amount * 100 // Stripe uses cents
            ]
        ];
    }
    
    public function verifyWebhookSignature($payload, $signature) {
        // Demo implementation - always return true
        return true;
    }
    
    public function processWebhook($payload) {
        // Demo implementation
        return ['success' => true, 'processed' => true];
    }
}

// PayPal Gateway Implementation (Demo)
class PayPalGateway extends PaymentGateway {
    protected function loadConfig() {
        $stmt = $this->conn->prepare("SELECT configuration FROM payment_methods WHERE gateway_name = 'paypal'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->config = $result ? json_decode($result['configuration'], true) : [];
    }
    
    public function createPaymentIntent($transactionId, $amount, $currency, $paymentData) {
        // Demo implementation
        return [
            'success' => true,
            'gateway_transaction_id' => 'PAYID-DEMO-' . strtoupper(uniqid()),
            'response' => [
                'id' => 'PAYID-DEMO-' . strtoupper(uniqid()),
                'status' => 'CREATED',
                'links' => [
                    ['rel' => 'approve', 'href' => 'https://sandbox.paypal.com/demo-approval-url']
                ]
            ]
        ];
    }
    
    public function confirmPayment($gatewayTransactionId, $confirmationData) {
        // Demo implementation
        return [
            'success' => true,
            'response' => [
                'status' => 'COMPLETED',
                'payer' => ['email_address' => 'demo@example.com']
            ]
        ];
    }
    
    public function refundPayment($gatewayTransactionId, $amount, $reason) {
        // Demo implementation
        return [
            'success' => true,
            'gateway_refund_id' => 'REFUND-DEMO-' . strtoupper(uniqid()),
            'response' => [
                'status' => 'COMPLETED'
            ]
        ];
    }
    
    public function verifyWebhookSignature($payload, $signature) {
        // Demo implementation
        return true;
    }
    
    public function processWebhook($payload) {
        // Demo implementation
        return ['success' => true, 'processed' => true];
    }
}

// Razorpay Gateway Implementation (Demo)
class RazorpayGateway extends PaymentGateway {
    protected function loadConfig() {
        $stmt = $this->conn->prepare("SELECT configuration FROM payment_methods WHERE gateway_name = 'razorpay'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->config = $result ? json_decode($result['configuration'], true) : [];
    }
    
    public function createPaymentIntent($transactionId, $amount, $currency, $paymentData) {
        // Demo implementation
        return [
            'success' => true,
            'gateway_transaction_id' => 'order_demo_' . uniqid(),
            'response' => [
                'id' => 'order_demo_' . uniqid(),
                'status' => 'created',
                'amount' => $amount * 100 // Razorpay uses paise
            ]
        ];
    }
    
    public function confirmPayment($gatewayTransactionId, $confirmationData) {
        // Demo implementation
        return [
            'success' => true,
            'response' => [
                'status' => 'captured',
                'method' => $confirmationData['method'] ?? 'card'
            ]
        ];
    }
    
    public function refundPayment($gatewayTransactionId, $amount, $reason) {
        // Demo implementation
        return [
            'success' => true,
            'gateway_refund_id' => 'rfnd_demo_' . uniqid(),
            'response' => [
                'status' => 'processed'
            ]
        ];
    }
    
    public function verifyWebhookSignature($payload, $signature) {
        // Demo implementation
        return true;
    }
    
    public function processWebhook($payload) {
        // Demo implementation
        return ['success' => true, 'processed' => true];
    }
}

// Square Gateway Implementation (Demo)
class SquareGateway extends PaymentGateway {
    protected function loadConfig() {
        $stmt = $this->conn->prepare("SELECT configuration FROM payment_methods WHERE gateway_name = 'square'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->config = $result ? json_decode($result['configuration'], true) : [];
    }
    
    public function createPaymentIntent($transactionId, $amount, $currency, $paymentData) {
        // Demo implementation
        return [
            'success' => true,
            'gateway_transaction_id' => 'sq_demo_' . uniqid(),
            'response' => [
                'payment' => [
                    'id' => 'sq_demo_' . uniqid(),
                    'status' => 'PENDING'
                ]
            ]
        ];
    }
    
    public function confirmPayment($gatewayTransactionId, $confirmationData) {
        // Demo implementation
        return [
            'success' => true,
            'response' => [
                'payment' => [
                    'status' => 'COMPLETED'
                ]
            ]
        ];
    }
    
    public function refundPayment($gatewayTransactionId, $amount, $reason) {
        // Demo implementation
        return [
            'success' => true,
            'gateway_refund_id' => 'refund_demo_' . uniqid(),
            'response' => [
                'refund' => [
                    'status' => 'COMPLETED'
                ]
            ]
        ];
    }
    
    public function verifyWebhookSignature($payload, $signature) {
        // Demo implementation
        return true;
    }
    
    public function processWebhook($payload) {
        // Demo implementation
        return ['success' => true, 'processed' => true];
    }
}

// Initialize payment manager
if (isset($conn)) {
    $paymentManager = new PaymentManager($conn);
}
?>
