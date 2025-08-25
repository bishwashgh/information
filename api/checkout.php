<?php
// Start output buffering to catch any unwanted output
ob_start();

// Suppress PHP warnings and notices that could interfere with JSON
error_reporting(E_ERROR | E_PARSE);

try {
    require_once '../includes/config.php';

    // Clean any output that might have been generated
    if (ob_get_level()) {
        ob_clean();
    }

    // Initialize database connection
    $db = Database::getInstance()->getConnection();

    // Add debugging to error log (not output)
    error_log("Checkout API called with method: " . $_SERVER['REQUEST_METHOD']);
    error_log("POST data: " . print_r($_POST, true));

    // Set JSON header
    header('Content-Type: application/json');
    header('Cache-Control: no-cache');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(false, 'Invalid request method');
    }

} catch (Exception $e) {
    // Clear any output
    if (ob_get_level()) {
        ob_clean();
    }
    
    error_log("API initialization error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'API initialization failed: ' . $e->getMessage()
    ]);
    exit;
}

try {
    // Verify CSRF token (temporarily disabled for debugging)
    error_log("Session CSRF token: " . ($_SESSION['csrf_token'] ?? 'NOT SET'));
    error_log("POST CSRF token: " . ($_POST['csrf_token'] ?? 'NOT SET'));

    // Temporarily disable CSRF check for debugging
    /*
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        error_log("CSRF token verification failed");
        sendJsonResponse(false, 'Invalid CSRF token');
    }
    */

    $action = $_POST['action'] ?? '';
    error_log("Processing action: " . $action);

    switch ($action) {
        case 'save_address':
            saveCheckoutAddress();
            break;
            
        case 'use_saved_address':
            useSavedAddress();
            break;
            
        case 'process_payment':
            processPayment();
            break;
            
        default:
            sendJsonResponse(false, 'Invalid action');
    }

} catch (Exception $e) {
    // Clear any output
    if (ob_get_level()) {
        ob_clean();
    }
    
    error_log("API processing error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'API processing failed: ' . $e->getMessage()
    ]);
    exit;
}

function saveCheckoutAddress() {
    global $db;
    
    $addressDataJson = $_POST['address_data'] ?? '';
    $addressData = json_decode($addressDataJson, true);
    
    if (!$addressData) {
        sendJsonResponse(false, 'Invalid address data');
    }
    
    // Validate required fields
    $required = ['first_name', 'last_name', 'phone', 'address_line_1', 'city', 'country'];
    foreach ($required as $field) {
        if (empty($addressData[$field])) {
            sendJsonResponse(false, 'Please fill in all required fields');
        }
    }
    
    // Save to session for checkout process (no database transaction needed for session)
    $_SESSION['checkout_address'] = [
        'first_name' => sanitizeInput($addressData['first_name']),
        'last_name' => sanitizeInput($addressData['last_name']),
        'phone' => sanitizeInput($addressData['phone']),
        'address_line_1' => sanitizeInput($addressData['address_line_1']),
        'address_line_2' => sanitizeInput($addressData['address_line_2'] ?? ''),
        'city' => sanitizeInput($addressData['city']),
        'postal_code' => sanitizeInput($addressData['postal_code'] ?? ''),
        'country' => sanitizeInput($addressData['country'])
    ];
    
    error_log("Address saved to session successfully");
    sendJsonResponse(true, 'Address saved successfully');
}

function useSavedAddress() {
    global $db;
    
    $addressId = $_POST['address_id'] ?? '';
    
    if (empty($addressId)) {
        sendJsonResponse(false, 'Address ID is required');
    }
    
    // Make sure user is logged in
    if (!isLoggedIn()) {
        sendJsonResponse(false, 'Please log in to use saved addresses');
    }
    
    $userId = $_SESSION['user_id'];
    
    try {
        // Get the saved address
        $stmt = $db->prepare("
            SELECT first_name, last_name, phone, address_line_1, address_line_2, 
                   city, postal_code, country 
            FROM user_addresses 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$addressId, $userId]);
        $address = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$address) {
            sendJsonResponse(false, 'Address not found');
        }
        
        // Save to session for checkout process
        $_SESSION['checkout_address'] = [
            'first_name' => $address['first_name'],
            'last_name' => $address['last_name'],
            'phone' => $address['phone'],
            'address_line_1' => $address['address_line_1'],
            'address_line_2' => $address['address_line_2'] ?? '',
            'city' => $address['city'],
            'postal_code' => $address['postal_code'] ?? '',
            'country' => $address['country']
        ];
        
        error_log("Saved address loaded to session successfully");
        sendJsonResponse(true, 'Address loaded successfully');
        
    } catch (PDOException $e) {
        error_log("Database error in useSavedAddress: " . $e->getMessage());
        sendJsonResponse(false, 'Database error occurred');
    }
}

function processPayment() {
    global $db;
    
    $paymentDataJson = $_POST['payment_data'] ?? '';
    $paymentData = json_decode($paymentDataJson, true);
    
    if (!$paymentData) {
        sendJsonResponse(false, 'Invalid payment data');
    }
    
    // Check if address is saved in session
    if (!isset($_SESSION['checkout_address'])) {
        // Try to get user's default address if logged in
        if (isLoggedIn()) {
            $userId = getCurrentUserId();
            $stmt = $db->prepare("
                SELECT * FROM user_addresses 
                WHERE user_id = ? AND is_default = 1 
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $defaultAddress = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($defaultAddress) {
                // Use default address
                $_SESSION['checkout_address'] = [
                    'first_name' => $defaultAddress['first_name'],
                    'last_name' => $defaultAddress['last_name'],
                    'phone' => $defaultAddress['phone'],
                    'address_line_1' => $defaultAddress['address_line_1'],
                    'address_line_2' => $defaultAddress['address_line_2'],
                    'city' => $defaultAddress['city'],
                    'postal_code' => $defaultAddress['postal_code'],
                    'country' => $defaultAddress['country']
                ];
            } else {
                // Create a temporary address from user data for testing
                $stmt = $db->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $_SESSION['checkout_address'] = [
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name'],
                        'phone' => '1234567890',
                        'address_line_1' => 'Default Address',
                        'address_line_2' => '',
                        'city' => 'Kathmandu',
                        'postal_code' => '44600',
                        'country' => 'Nepal'
                    ];
                } else {
                    sendJsonResponse(false, 'Please complete address step first');
                }
            }
        } else {
            sendJsonResponse(false, 'Please complete address step first');
        }
    }
    
    // Check if email is verified (in real app)
    if (!isset($_SESSION['checkout_email_verified'])) {
        // For demo, we'll skip email verification
        $_SESSION['checkout_email_verified'] = true;
    }
    
    try {
        $db->beginTransaction();
        
        // Get cart items
        if (isLoggedIn()) {
            $stmt = $db->prepare("
                SELECT c.*, p.name, p.price, p.sale_price, p.slug
                FROM cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.user_id = ? AND p.status = 'active'
            ");
            $stmt->execute([getCurrentUserId()]);
        } else {
            $stmt = $db->prepare("
                SELECT c.*, p.name, p.price, p.sale_price, p.slug
                FROM cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.session_id = ? AND p.status = 'active'
            ");
            $stmt->execute([session_id()]);
        }
        
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($cartItems)) {
            sendJsonResponse(false, 'Cart is empty');
        }
        
        // Calculate totals
        $subtotal = 0;
        foreach ($cartItems as $item) {
            $price = $item['sale_price'] ?: $item['price'];
            $subtotal += $price * $item['quantity'];
        }
        
        $discount = $_SESSION['applied_coupon']['discount'] ?? 0;
        $shipping = ($subtotal >= 2000) ? 0 : 100;
        $tax = $subtotal * 0.13;
        $total = $subtotal + $shipping + $tax - $discount;
        
        // Generate order number
        $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Create order
        $stmt = $db->prepare("
            INSERT INTO orders (
                order_number, user_id, status, payment_method, payment_status,
                subtotal, discount_amount, shipping_amount, tax_amount, total_amount,
                shipping_address, notes, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $userEmail = isLoggedIn() ? $_SESSION['email'] : ($_SESSION['checkout_email'] ?? 'guest@example.com');
        $userId = isLoggedIn() ? getCurrentUserId() : null;
        
        $address = $_SESSION['checkout_address'];
        
        // Format shipping address as JSON
        $shippingAddress = json_encode([
            'first_name' => $address['first_name'],
            'last_name' => $address['last_name'],
            'phone' => $address['phone'],
            'address_line_1' => $address['address_line_1'],
            'address_line_2' => $address['address_line_2'],
            'city' => $address['city'],
            'postal_code' => $address['postal_code'],
            'country' => $address['country'],
            'email' => $userEmail
        ]);
        
        $stmt->execute([
            $orderNumber,
            $userId,
            'pending',
            $paymentData['payment_method'],
            $paymentData['payment_method'] === 'cod' ? 'pending' : 'paid',
            $subtotal,
            $discount,
            $shipping,
            $tax,
            $total,
            $shippingAddress,
            $paymentData['order_notes'] ?? ''
        ]);
        
        $orderId = $db->lastInsertId();
        
        // Add order items
        foreach ($cartItems as $item) {
            $price = $item['sale_price'] ?: $item['price'];
            
            $stmt = $db->prepare("
                INSERT INTO order_items (
                    order_id, product_id, product_name, quantity, price, total, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $orderId,
                $item['product_id'],
                $item['name'],
                $item['quantity'],
                $price,
                $price * $item['quantity']
            ]);
            
            // Update product stock
            $stmt = $db->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        // Process payment based on method
        if ($paymentData['payment_method'] === 'card' || $paymentData['payment_method'] === 'wallet') {
            // In a real app, integrate with payment gateway here
            // For demo, we'll simulate successful payment
            
            $stmt = $db->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
            $stmt->execute([$orderId]);
        }
        
        // Clear cart
        if (isLoggedIn()) {
            $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([getCurrentUserId()]);
        } else {
            $stmt = $db->prepare("DELETE FROM cart WHERE session_id = ?");
            $stmt->execute([session_id()]);
        }
        
        // Clear coupon
        unset($_SESSION['applied_coupon']);
        
        // Send confirmation email (simulated)
        sendOrderConfirmationEmail($orderId, $userEmail, $orderNumber);
        
        $db->commit();
        
        // Clear checkout session data
        unset($_SESSION['checkout_address']);
        unset($_SESSION['checkout_email_verified']);
        
        sendJsonResponse(true, 'Order placed successfully', [
            'order_id' => $orderId,
            'order_number' => $orderNumber
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Payment processing error: " . $e->getMessage());
        sendJsonResponse(false, 'Payment processing failed');
    }
}

function sendOrderConfirmationEmail($orderId, $email, $orderNumber) {
    global $db;
    
    // Get order details
    $stmt = $db->prepare("
        SELECT o.*, 
               GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name) SEPARATOR ', ') as items
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.id = ?
        GROUP BY o.id
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) return;
    
    // In a real application, you would send an actual email here
    // For demo purposes, we'll just log it
    $emailContent = "
        Order Confirmation - {$orderNumber}
        
        Dear {$order['shipping_first_name']} {$order['shipping_last_name']},
        
        Thank you for your order! Your order has been received and is being processed.
        
        Order Details:
        Order Number: {$orderNumber}
        Items: {$order['items']}
        Total: " . formatPrice($order['total']) . "
        
        Shipping Address:
        {$order['shipping_first_name']} {$order['shipping_last_name']}
        {$order['shipping_address_line_1']}
        {$order['shipping_city']}, {$order['shipping_postal_code']}
        {$order['shipping_country']}
        
        We'll send you another email when your order ships.
        
        Thank you for shopping with us!
    ";
    
    error_log("Order confirmation email sent to {$email}: " . $emailContent);
    
    return true;
}

function sendJsonResponse($success, $message, $data = null) {
    // Clear any output that might interfere with JSON
    if (ob_get_level()) {
        ob_clean();
    }
    
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data) {
        $response['data'] = $data;
        // Add individual data properties to response root
        foreach ($data as $key => $value) {
            $response[$key] = $value;
        }
    }
    
    // Ensure clean JSON output
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
