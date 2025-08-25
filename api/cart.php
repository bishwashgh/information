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
    case 'add':
        addToCart();
        break;
    case 'update':
        updateCartItem();
        break;
    case 'remove':
        removeFromCart();
        break;
    case 'clear':
        clearCart();
        break;
    case 'get':
        getCartItems();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function addToCart() {
    global $db;
    
    $productId = (int) ($_POST['product_id'] ?? 0);
    $quantity = (int) ($_POST['quantity'] ?? 1);
    $attributes = $_POST['attributes'] ?? '{}';
    
    if ($productId <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
        return;
    }
    
    // Check if product exists and has enough stock
    $stmt = $db->prepare("SELECT id, name, price, stock_quantity, manage_stock FROM products WHERE id = ? AND status = 'active'");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        return;
    }
    
    if ($product['manage_stock'] && $product['stock_quantity'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
        return;
    }
    
    try {
        if (isLoggedIn()) {
            $userId = getCurrentUserId();
            $sessionId = null;
            
            // Check if item already exists in cart
            $stmt = $db->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND attributes = ?");
            $stmt->execute([$userId, $productId, $attributes]);
            $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingItem) {
                // Update quantity
                $newQuantity = $existingItem['quantity'] + $quantity;
                $stmt = $db->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newQuantity, $existingItem['id']]);
            } else {
                // Add new item
                $stmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity, attributes) VALUES (?, ?, ?, ?)");
                $stmt->execute([$userId, $productId, $quantity, $attributes]);
            }
        } else {
            $sessionId = session_id();
            $userId = null;
            
            // Check if item already exists in cart
            $stmt = $db->prepare("SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ? AND attributes = ?");
            $stmt->execute([$sessionId, $productId, $attributes]);
            $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingItem) {
                // Update quantity
                $newQuantity = $existingItem['quantity'] + $quantity;
                $stmt = $db->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newQuantity, $existingItem['id']]);
            } else {
                // Add new item
                $stmt = $db->prepare("INSERT INTO cart (session_id, product_id, quantity, attributes) VALUES (?, ?, ?, ?)");
                $stmt->execute([$sessionId, $productId, $quantity, $attributes]);
            }
        }
        
        // Get updated cart count
        $cartCount = getCartCount();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Product added to cart',
            'cart_count' => $cartCount
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to add product to cart']);
    }
}

function updateCartItem() {
    global $db;
    
    $cartId = (int) ($_POST['cart_id'] ?? 0);
    $quantity = (int) ($_POST['quantity'] ?? 1);
    
    if ($cartId <= 0 || $quantity < 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid cart item or quantity']);
        return;
    }
    
    try {
        if ($quantity === 0) {
            // Remove item if quantity is 0
            $stmt = $db->prepare("DELETE FROM cart WHERE id = ?");
            $stmt->execute([$cartId]);
        } else {
            // Update quantity
            $stmt = $db->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$quantity, $cartId]);
        }
        
        $cartCount = getCartCount();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Cart updated successfully',
            'cart_count' => $cartCount
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
    }
}

function removeFromCart() {
    global $db;
    
    $cartId = (int) ($_POST['cart_id'] ?? 0);
    
    if ($cartId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
        return;
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM cart WHERE id = ?");
        $stmt->execute([$cartId]);
        
        $cartCount = getCartCount();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Item removed from cart',
            'cart_count' => $cartCount
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to remove item from cart']);
    }
}

function clearCart() {
    global $db;
    
    try {
        if (isLoggedIn()) {
            $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([getCurrentUserId()]);
        } else {
            $stmt = $db->prepare("DELETE FROM cart WHERE session_id = ?");
            $stmt->execute([session_id()]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Cart cleared successfully']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to clear cart']);
    }
}

function getCartItems() {
    global $db;
    
    try {
        if (isLoggedIn()) {
            $stmt = $db->prepare("
                SELECT c.*, p.name, p.price, p.sale_price, p.slug, 
                       (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
                FROM cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.user_id = ? 
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([getCurrentUserId()]);
        } else {
            $stmt = $db->prepare("
                SELECT c.*, p.name, p.price, p.sale_price, p.slug,
                       (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
                FROM cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.session_id = ? 
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([session_id()]);
        }
        
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate totals
        $subtotal = 0;
        foreach ($items as &$item) {
            $price = $item['sale_price'] ?: $item['price'];
            $item['unit_price'] = $price;
            $item['total_price'] = $price * $item['quantity'];
            $subtotal += $item['total_price'];
        }
        
        echo json_encode([
            'success' => true,
            'items' => $items,
            'subtotal' => $subtotal,
            'count' => array_sum(array_column($items, 'quantity'))
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get cart items']);
    }
}
?>
