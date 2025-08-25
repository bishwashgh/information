<?php
// API endpoints for PWA functionality
session_start();
require_once '../includes/config.php';
require_once '../includes/push_notifications.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'vapid_key':
        // Get VAPID public key for push notifications
        if (isset($pushManager)) {
            echo json_encode([
                'success' => true,
                'publicKey' => $pushManager->getVapidPublicKey()
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Push notifications not available'
            ]);
        }
        break;
        
    case 'subscribe':
        // Subscribe user to push notifications
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => false,
                'error' => 'User not logged in'
            ]);
            break;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['subscription'])) {
            echo json_encode([
                'success' => false,
                'error' => 'Invalid subscription data'
            ]);
            break;
        }
        
        $result = $pushManager->subscribeUser($_SESSION['user_id'], $input['subscription']);
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Subscribed successfully' : 'Subscription failed'
        ]);
        break;
        
    case 'unsubscribe':
        // Unsubscribe user from push notifications
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => false,
                'error' => 'User not logged in'
            ]);
            break;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $endpoint = $input['endpoint'] ?? null;
        
        $result = $pushManager->unsubscribeUser($_SESSION['user_id'], $endpoint);
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Unsubscribed successfully' : 'Unsubscription failed'
        ]);
        break;
        
    case 'notification_click':
        // Log notification click
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['notificationId'])) {
            echo json_encode([
                'success' => false,
                'error' => 'Invalid notification ID'
            ]);
            break;
        }
        
        $result = $pushManager->logNotificationClick($input['notificationId']);
        echo json_encode([
            'success' => $result
        ]);
        break;
        
    case 'sync_cart':
        // Sync offline cart with server
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => false,
                'error' => 'User not logged in'
            ]);
            break;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['cart'])) {
            echo json_encode([
                'success' => false,
                'error' => 'Invalid cart data'
            ]);
            break;
        }
        
        try {
            $userId = $_SESSION['user_id'];
            $cart = $input['cart'];
            
            // Clear existing cart
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Add items from offline cart
            foreach ($cart as $item) {
                $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $item['product_id'], $item['quantity']]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Cart synced successfully'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Cart sync failed: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'get_cart':
        // Get current cart for offline sync
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => false,
                'error' => 'User not logged in'
            ]);
            break;
        }
        
        try {
            $stmt = $conn->prepare("
                SELECT c.product_id, c.quantity, p.name, p.price, p.image 
                FROM cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.user_id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $cart = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'cart' => $cart
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to get cart: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'get_offline_data':
        // Get essential data for offline use
        try {
            $data = [];
            
            // Get user info if logged in
            if (isset($_SESSION['user_id'])) {
                $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $data['user'] = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Get user's cart
                $stmt = $conn->prepare("
                    SELECT c.product_id, c.quantity, p.name, p.price, p.image 
                    FROM cart c 
                    JOIN products p ON c.product_id = p.id 
                    WHERE c.user_id = ?
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $data['cart'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get user's wishlist
                $stmt = $conn->prepare("
                    SELECT w.product_id, p.name, p.price, p.image 
                    FROM wishlist w 
                    JOIN products p ON w.product_id = p.id 
                    WHERE w.user_id = ?
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $data['wishlist'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get recent orders
                $stmt = $conn->prepare("
                    SELECT id, total_amount, status, created_at 
                    FROM orders 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 10
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $data['orders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Get popular products
            $stmt = $conn->prepare("
                SELECT id, name, price, image, description 
                FROM products 
                WHERE status = 'active' 
                ORDER BY views DESC 
                LIMIT 20
            ");
            $stmt->execute();
            $data['popular_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get categories
            $stmt = $conn->prepare("SELECT id, name FROM categories WHERE status = 'active'");
            $stmt->execute();
            $data['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'timestamp' => time()
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to get offline data: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'background_sync':
        // Handle background sync events
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['type'])) {
            echo json_encode([
                'success' => false,
                'error' => 'Invalid sync data'
            ]);
            break;
        }
        
        try {
            switch ($input['type']) {
                case 'cart_update':
                    if (isset($_SESSION['user_id']) && isset($input['data']['cart'])) {
                        // Sync cart updates
                        $userId = $_SESSION['user_id'];
                        $cart = $input['data']['cart'];
                        
                        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                        $stmt->execute([$userId]);
                        
                        foreach ($cart as $item) {
                            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                            $stmt->execute([$userId, $item['product_id'], $item['quantity']]);
                        }
                    }
                    break;
                    
                case 'analytics_event':
                    if (isset($input['data']['event'])) {
                        // Log analytics event
                        $event = $input['data']['event'];
                        $stmt = $conn->prepare("INSERT INTO analytics_events (event_type, event_data, user_id, created_at) VALUES (?, ?, ?, NOW())");
                        $stmt->execute([
                            $event['type'] ?? 'unknown',
                            json_encode($event['data'] ?? []),
                            $_SESSION['user_id'] ?? null
                        ]);
                    }
                    break;
                    
                default:
                    throw new Exception('Unknown sync type: ' . $input['type']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Background sync completed'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Background sync failed: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'app_info':
        // Get app information for PWA
        echo json_encode([
            'success' => true,
            'app' => [
                'name' => 'E-Commerce Store',
                'version' => '1.0.0',
                'description' => 'Your favorite online shopping destination',
                'theme_color' => '#3b82f6',
                'background_color' => '#ffffff',
                'features' => [
                    'offline_browsing' => true,
                    'push_notifications' => true,
                    'background_sync' => true,
                    'install_prompt' => true
                ]
            ]
        ]);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Unknown action: ' . $action
        ]);
        break;
}
?>
