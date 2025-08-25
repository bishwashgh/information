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

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to use wishlist']);
    exit;
}

$action = $_POST['action'] ?? '';
$db = Database::getInstance()->getConnection();

switch ($action) {
    case 'add':
        addToWishlist();
        break;
    case 'remove':
        removeFromWishlist();
        break;
    case 'toggle':
        toggleWishlist();
        break;
    case 'clear':
        clearWishlist();
        break;
    case 'get':
        getWishlistItems();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function addToWishlist() {
    global $db;
    
    $productId = (int) ($_POST['product_id'] ?? 0);
    $userId = getCurrentUserId();
    
    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product']);
        return;
    }
    
    // Check if product exists
    $stmt = $db->prepare("SELECT id, name FROM products WHERE id = ? AND status = 'active'");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        return;
    }
    
    try {
        // Check if already in wishlist
        $stmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Product already in wishlist']);
            return;
        }
        
        // Add to wishlist
        $stmt = $db->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$userId, $productId]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Product added to wishlist',
            'product_name' => $product['name']
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to add product to wishlist']);
    }
}

function removeFromWishlist() {
    global $db;
    
    $productId = (int) ($_POST['product_id'] ?? 0);
    $userId = getCurrentUserId();
    
    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product']);
        return;
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        
        echo json_encode(['success' => true, 'message' => 'Product removed from wishlist']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to remove product from wishlist']);
    }
}

function toggleWishlist() {
    global $db;
    
    $productId = (int) ($_POST['product_id'] ?? 0);
    $userId = getCurrentUserId();
    
    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product']);
        return;
    }
    
    try {
        // Check if already in wishlist
        $stmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        
        if ($stmt->fetch()) {
            // Remove from wishlist
            $stmt = $db->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Product removed from wishlist',
                'action' => 'removed'
            ]);
        } else {
            // Add to wishlist
            $stmt = $db->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $stmt->execute([$userId, $productId]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Product added to wishlist',
                'action' => 'added'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update wishlist']);
    }
}

function clearWishlist() {
    global $db;
    
    $userId = getCurrentUserId();
    
    try {
        $stmt = $db->prepare("DELETE FROM wishlist WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        $deletedCount = $stmt->rowCount();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Wishlist cleared successfully',
            'deleted_count' => $deletedCount
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to clear wishlist']);
    }
}

function getWishlistItems() {
    global $db;
    
    $userId = getCurrentUserId();
    
    try {
        $stmt = $db->prepare("
            SELECT w.*, p.name, p.price, p.sale_price, p.slug,
                   (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image,
                   c.name as category_name
            FROM wishlist w 
            JOIN products p ON w.product_id = p.id 
            JOIN categories c ON p.category_id = c.id
            WHERE w.user_id = ? AND p.status = 'active'
            ORDER BY w.created_at DESC
        ");
        $stmt->execute([$userId]);
        
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'items' => $items,
            'count' => count($items)
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get wishlist items']);
    }
}
?>
