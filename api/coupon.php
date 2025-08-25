<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verify CSRF token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$action = $_POST['action'] ?? '';
$db = Database::getInstance()->getConnection();

switch ($action) {
    case 'apply':
        applyCoupon();
        break;
    case 'remove':
        removeCoupon();
        break;
    case 'validate':
        validateCoupon();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function applyCoupon() {
    global $db;
    
    $couponCode = strtoupper(trim($_POST['coupon_code'] ?? ''));
    
    if (!$couponCode) {
        echo json_encode(['success' => false, 'message' => 'Please enter a coupon code']);
        return;
    }
    
    try {
        // Check if coupon exists and is valid
        $stmt = $db->prepare("
            SELECT * FROM coupons 
            WHERE code = ? AND is_active = 1 
            AND (start_date IS NULL OR start_date <= CURDATE())
            AND (end_date IS NULL OR end_date >= CURDATE())
            AND (maximum_uses IS NULL OR used_count < maximum_uses)
        ");
        $stmt->execute([$couponCode]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$coupon) {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired coupon code']);
            return;
        }
        
        // Get cart total
        $cartTotal = getCartSubtotal();
        
        if ($cartTotal < $coupon['minimum_amount']) {
            echo json_encode([
                'success' => false, 
                'message' => 'Minimum order amount of ' . formatPrice($coupon['minimum_amount']) . ' required for this coupon'
            ]);
            return;
        }
        
        // Calculate discount
        $discount = 0;
        if ($coupon['type'] === 'percentage') {
            $discount = ($cartTotal * $coupon['value']) / 100;
        } else {
            $discount = $coupon['value'];
        }
        
        // Ensure discount doesn't exceed cart total
        $discount = min($discount, $cartTotal);
        
        // Store coupon in session
        $_SESSION['applied_coupon'] = [
            'id' => $coupon['id'],
            'code' => $coupon['code'],
            'type' => $coupon['type'],
            'value' => $coupon['value'],
            'discount' => $discount
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Coupon applied successfully',
            'discount' => $discount,
            'coupon_code' => $coupon['code']
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to apply coupon']);
    }
}

function removeCoupon() {
    unset($_SESSION['applied_coupon']);
    echo json_encode(['success' => true, 'message' => 'Coupon removed']);
}

function validateCoupon() {
    global $db;
    
    $couponCode = strtoupper(trim($_POST['coupon_code'] ?? ''));
    
    if (!$couponCode) {
        echo json_encode(['success' => false, 'message' => 'Please enter a coupon code']);
        return;
    }
    
    try {
        $stmt = $db->prepare("
            SELECT * FROM coupons 
            WHERE code = ? AND is_active = 1 
            AND (start_date IS NULL OR start_date <= CURDATE())
            AND (end_date IS NULL OR end_date >= CURDATE())
            AND (maximum_uses IS NULL OR used_count < maximum_uses)
        ");
        $stmt->execute([$couponCode]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$coupon) {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired coupon code']);
            return;
        }
        
        $cartTotal = getCartSubtotal();
        
        if ($cartTotal < $coupon['minimum_amount']) {
            echo json_encode([
                'success' => false,
                'message' => 'Minimum order amount of ' . formatPrice($coupon['minimum_amount']) . ' required'
            ]);
            return;
        }
        
        $discount = 0;
        if ($coupon['type'] === 'percentage') {
            $discount = ($cartTotal * $coupon['value']) / 100;
        } else {
            $discount = $coupon['value'];
        }
        
        $discount = min($discount, $cartTotal);
        
        echo json_encode([
            'success' => true,
            'coupon' => $coupon,
            'discount' => $discount
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to validate coupon']);
    }
}

function getCartSubtotal() {
    global $db;
    
    $subtotal = 0;
    
    if (isLoggedIn()) {
        $stmt = $db->prepare("
            SELECT c.quantity, COALESCE(p.sale_price, p.price) as price
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ? AND p.status = 'active'
        ");
        $stmt->execute([getCurrentUserId()]);
    } else {
        $stmt = $db->prepare("
            SELECT c.quantity, COALESCE(p.sale_price, p.price) as price
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.session_id = ? AND p.status = 'active'
        ");
        $stmt->execute([session_id()]);
    }
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    return $subtotal;
}
?>
