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
    echo json_encode(['success' => false, 'message' => 'Please login to submit a review']);
    exit;
}

$action = $_POST['action'] ?? '';
$db = Database::getInstance()->getConnection();

switch ($action) {
    case 'submit':
        submitReview();
        break;
    case 'approve':
        approveReview();
        break;
    case 'reject':
        rejectReview();
        break;
    case 'delete':
        deleteReview();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function submitReview() {
    global $db;
    
    $productId = (int) ($_POST['product_id'] ?? 0);
    $rating = (int) ($_POST['rating'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    $userId = getCurrentUserId();
    
    // Validation
    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product']);
        return;
    }
    
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
        return;
    }
    
    if (empty($comment)) {
        echo json_encode(['success' => false, 'message' => 'Please write a review comment']);
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
    
    // Check if user already reviewed this product
    $stmt = $db->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You have already reviewed this product']);
        return;
    }
    
    try {
        // Insert review
        $stmt = $db->prepare("
            INSERT INTO reviews (user_id, product_id, rating, title, comment, is_approved, created_at)
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        ");
        
        $stmt->execute([$userId, $productId, $rating, $title, $comment]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Review submitted successfully! It will be published after approval.',
            'product_name' => $product['name']
        ]);
        
    } catch (Exception $e) {
        error_log("Review submission error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
    }
}

function approveReview() {
    global $db;
    
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    $reviewId = (int) ($_POST['review_id'] ?? 0);
    
    if ($reviewId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid review ID']);
        return;
    }
    
    try {
        $stmt = $db->prepare("UPDATE reviews SET is_approved = 1 WHERE id = ?");
        $stmt->execute([$reviewId]);
        
        echo json_encode(['success' => true, 'message' => 'Review approved successfully']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to approve review']);
    }
}

function rejectReview() {
    global $db;
    
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    $reviewId = (int) ($_POST['review_id'] ?? 0);
    
    if ($reviewId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid review ID']);
        return;
    }
    
    try {
        $stmt = $db->prepare("UPDATE reviews SET is_approved = 0 WHERE id = ?");
        $stmt->execute([$reviewId]);
        
        echo json_encode(['success' => true, 'message' => 'Review rejected']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to reject review']);
    }
}

function deleteReview() {
    global $db;
    
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    $reviewId = (int) ($_POST['review_id'] ?? 0);
    
    if ($reviewId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid review ID']);
        return;
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$reviewId]);
        
        echo json_encode(['success' => true, 'message' => 'Review deleted successfully']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete review']);
    }
}
?>
