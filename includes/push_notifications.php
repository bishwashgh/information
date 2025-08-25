<?php
// Push notification configuration and utilities

class PushNotificationManager {
    private $conn;
    private $vapidKeys;
    
    public function __construct($database) {
        $this->conn = $database;
        $this->initializeDatabase();
        $this->vapidKeys = [
            'publicKey' => 'BIxJ8QfLrKqR7TJJw8Z8mYKW8ZJ5K8qR7TJJw8Z8mYKW8ZJ5K8qR7TJJw8Z8mYKW8ZJ5K8qR7TJJw8Z8mYKW8ZJ5',
            'privateKey' => 'your-private-vapid-key-here' // Replace with actual VAPID private key
        ];
    }
    
    private function initializeDatabase() {
        // Create push subscriptions table
        $sql = "CREATE TABLE IF NOT EXISTS push_subscriptions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            endpoint TEXT NOT NULL,
            p256dh_key TEXT NOT NULL,
            auth_key TEXT NOT NULL,
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_subscription (user_id, endpoint(255))
        )";
        $this->conn->exec($sql);
        
        // Create push notifications log table
        $sql = "CREATE TABLE IF NOT EXISTS push_notifications_log (
            id INT PRIMARY KEY AUTO_INCREMENT,
            subscription_id INT,
            title VARCHAR(255),
            message TEXT,
            data JSON,
            status ENUM('sent', 'failed', 'clicked') DEFAULT 'sent',
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            clicked_at TIMESTAMP NULL,
            error_message TEXT,
            FOREIGN KEY (subscription_id) REFERENCES push_subscriptions(id) ON DELETE CASCADE
        )";
        $this->conn->exec($sql);
        
        // Create notification templates table
        $sql = "CREATE TABLE IF NOT EXISTS notification_templates (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) UNIQUE,
            title VARCHAR(255),
            message TEXT,
            icon VARCHAR(255),
            badge VARCHAR(255),
            actions JSON,
            data JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $this->conn->exec($sql);
        
        $this->seedTemplates();
    }
    
    private function seedTemplates() {
        $templates = [
            [
                'name' => 'order_confirmed',
                'title' => 'Order Confirmed! 🎉',
                'message' => 'Your order #{order_id} has been confirmed and is being processed.',
                'icon' => '/assets/images/icons/order-confirmed.png',
                'badge' => '/assets/images/icons/badge.png',
                'actions' => json_encode([
                    ['action' => 'view', 'title' => 'View Order', 'icon' => '/assets/images/icons/view.png'],
                    ['action' => 'track', 'title' => 'Track Package', 'icon' => '/assets/images/icons/track.png']
                ]),
                'data' => json_encode(['type' => 'order', 'action' => 'confirmed'])
            ],
            [
                'name' => 'order_shipped',
                'title' => 'Package Shipped! 📦',
                'message' => 'Your order #{order_id} is on its way! Expected delivery: {delivery_date}',
                'icon' => '/assets/images/icons/shipped.png',
                'badge' => '/assets/images/icons/badge.png',
                'actions' => json_encode([
                    ['action' => 'track', 'title' => 'Track Package', 'icon' => '/assets/images/icons/track.png'],
                    ['action' => 'view', 'title' => 'View Order', 'icon' => '/assets/images/icons/view.png']
                ]),
                'data' => json_encode(['type' => 'order', 'action' => 'shipped'])
            ],
            [
                'name' => 'order_delivered',
                'title' => 'Order Delivered! ✅',
                'message' => 'Your order #{order_id} has been delivered. How was your experience?',
                'icon' => '/assets/images/icons/delivered.png',
                'badge' => '/assets/images/icons/badge.png',
                'actions' => json_encode([
                    ['action' => 'review', 'title' => 'Write Review', 'icon' => '/assets/images/icons/review.png'],
                    ['action' => 'reorder', 'title' => 'Order Again', 'icon' => '/assets/images/icons/reorder.png']
                ]),
                'data' => json_encode(['type' => 'order', 'action' => 'delivered'])
            ],
            [
                'name' => 'cart_abandoned',
                'title' => 'Don\'t forget your items! 🛒',
                'message' => 'You have {item_count} items waiting in your cart. Complete your purchase now!',
                'icon' => '/assets/images/icons/cart.png',
                'badge' => '/assets/images/icons/badge.png',
                'actions' => json_encode([
                    ['action' => 'checkout', 'title' => 'Complete Purchase', 'icon' => '/assets/images/icons/checkout.png'],
                    ['action' => 'view_cart', 'title' => 'View Cart', 'icon' => '/assets/images/icons/cart.png']
                ]),
                'data' => json_encode(['type' => 'cart', 'action' => 'abandoned'])
            ],
            [
                'name' => 'price_drop',
                'title' => 'Price Drop Alert! 💸',
                'message' => '{product_name} is now {new_price} (was {old_price}). Limited time offer!',
                'icon' => '/assets/images/icons/price-drop.png',
                'badge' => '/assets/images/icons/badge.png',
                'actions' => json_encode([
                    ['action' => 'buy_now', 'title' => 'Buy Now', 'icon' => '/assets/images/icons/buy.png'],
                    ['action' => 'view_product', 'title' => 'View Product', 'icon' => '/assets/images/icons/view.png']
                ]),
                'data' => json_encode(['type' => 'product', 'action' => 'price_drop'])
            ],
            [
                'name' => 'back_in_stock',
                'title' => 'Back in Stock! 📦',
                'message' => '{product_name} is back in stock! Get it before it sells out again.',
                'icon' => '/assets/images/icons/stock.png',
                'badge' => '/assets/images/icons/badge.png',
                'actions' => json_encode([
                    ['action' => 'buy_now', 'title' => 'Buy Now', 'icon' => '/assets/images/icons/buy.png'],
                    ['action' => 'add_to_cart', 'title' => 'Add to Cart', 'icon' => '/assets/images/icons/cart.png']
                ]),
                'data' => json_encode(['type' => 'product', 'action' => 'back_in_stock'])
            ],
            [
                'name' => 'welcome',
                'title' => 'Welcome to Our Store! 👋',
                'message' => 'Thanks for downloading our app! Get 10% off your first order with code WELCOME10.',
                'icon' => '/assets/images/icons/welcome.png',
                'badge' => '/assets/images/icons/badge.png',
                'actions' => json_encode([
                    ['action' => 'shop_now', 'title' => 'Shop Now', 'icon' => '/assets/images/icons/shop.png'],
                    ['action' => 'browse', 'title' => 'Browse Products', 'icon' => '/assets/images/icons/browse.png']
                ]),
                'data' => json_encode(['type' => 'promotion', 'action' => 'welcome'])
            ]
        ];
        
        foreach ($templates as $template) {
            $stmt = $this->conn->prepare("INSERT IGNORE INTO notification_templates 
                (name, title, message, icon, badge, actions, data) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $template['name'],
                $template['title'],
                $template['message'],
                $template['icon'],
                $template['badge'],
                $template['actions'],
                $template['data']
            ]);
        }
    }
    
    public function getVapidPublicKey() {
        return $this->vapidKeys['publicKey'];
    }
    
    public function subscribeUser($userId, $subscription) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO push_subscriptions 
                (user_id, endpoint, p256dh_key, auth_key, user_agent) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                p256dh_key = VALUES(p256dh_key),
                auth_key = VALUES(auth_key),
                last_used = CURRENT_TIMESTAMP,
                is_active = TRUE");
            
            $result = $stmt->execute([
                $userId,
                $subscription['endpoint'],
                $subscription['keys']['p256dh'],
                $subscription['keys']['auth'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            return $result;
        } catch (Exception $e) {
            error_log("Push subscription error: " . $e->getMessage());
            return false;
        }
    }
    
    public function unsubscribeUser($userId, $endpoint = null) {
        try {
            if ($endpoint) {
                $stmt = $this->conn->prepare("UPDATE push_subscriptions 
                    SET is_active = FALSE 
                    WHERE user_id = ? AND endpoint = ?");
                return $stmt->execute([$userId, $endpoint]);
            } else {
                $stmt = $this->conn->prepare("UPDATE push_subscriptions 
                    SET is_active = FALSE 
                    WHERE user_id = ?");
                return $stmt->execute([$userId]);
            }
        } catch (Exception $e) {
            error_log("Push unsubscription error: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendNotification($userId, $templateName, $data = []) {
        try {
            // Get notification template
            $stmt = $this->conn->prepare("SELECT * FROM notification_templates WHERE name = ?");
            $stmt->execute([$templateName]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$template) {
                throw new Exception("Template not found: " . $templateName);
            }
            
            // Get user subscriptions
            $stmt = $this->conn->prepare("SELECT * FROM push_subscriptions 
                WHERE user_id = ? AND is_active = TRUE");
            $stmt->execute([$userId]);
            $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($subscriptions)) {
                return false; // No active subscriptions
            }
            
            // Replace placeholders in template
            $title = $this->replacePlaceholders($template['title'], $data);
            $message = $this->replacePlaceholders($template['message'], $data);
            
            $payload = [
                'title' => $title,
                'body' => $message,
                'icon' => $template['icon'],
                'badge' => $template['badge'],
                'actions' => json_decode($template['actions'], true),
                'data' => array_merge(
                    json_decode($template['data'], true) ?? [],
                    $data,
                    ['timestamp' => time()]
                ),
                'tag' => $templateName . '_' . $userId,
                'requireInteraction' => in_array($templateName, ['order_confirmed', 'order_delivered']),
                'silent' => false
            ];
            
            $success = true;
            foreach ($subscriptions as $subscription) {
                $result = $this->sendPushNotification($subscription, $payload);
                if ($result) {
                    $this->logNotification($subscription['id'], $title, $message, $payload['data'], 'sent');
                } else {
                    $this->logNotification($subscription['id'], $title, $message, $payload['data'], 'failed');
                    $success = false;
                }
            }
            
            return $success;
        } catch (Exception $e) {
            error_log("Send notification error: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendPushNotification($subscription, $payload) {
        // This is a simplified version - in production, use a library like web-push-php
        // For now, we'll simulate the sending
        
        try {
            $endpoint = $subscription['endpoint'];
            $p256dh = $subscription['p256dh_key'];
            $auth = $subscription['auth_key'];
            
            // In a real implementation, you would:
            // 1. Encrypt the payload using the p256dh and auth keys
            // 2. Sign the request with VAPID keys
            // 3. Send HTTP POST to the endpoint
            
            // For demo purposes, we'll just log the attempt
            error_log("Push notification sent to: " . substr($endpoint, 0, 50) . "...");
            error_log("Payload: " . json_encode($payload));
            
            return true; // Simulate success
        } catch (Exception $e) {
            error_log("Push send error: " . $e->getMessage());
            return false;
        }
    }
    
    private function replacePlaceholders($text, $data) {
        foreach ($data as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }
        return $text;
    }
    
    private function logNotification($subscriptionId, $title, $message, $data, $status, $error = null) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO push_notifications_log 
                (subscription_id, title, message, data, status, error_message) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $subscriptionId,
                $title,
                $message,
                json_encode($data),
                $status,
                $error
            ]);
        } catch (Exception $e) {
            error_log("Log notification error: " . $e->getMessage());
        }
    }
    
    public function logNotificationClick($notificationId) {
        try {
            $stmt = $this->conn->prepare("UPDATE push_notifications_log 
                SET status = 'clicked', clicked_at = CURRENT_TIMESTAMP 
                WHERE id = ?");
            return $stmt->execute([$notificationId]);
        } catch (Exception $e) {
            error_log("Log click error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getNotificationStats($userId = null, $days = 30) {
        try {
            $whereClause = "WHERE pnl.sent_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            $params = [$days];
            
            if ($userId) {
                $whereClause .= " AND ps.user_id = ?";
                $params[] = $userId;
            }
            
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(*) as total_sent,
                    SUM(CASE WHEN pnl.status = 'clicked' THEN 1 ELSE 0 END) as total_clicked,
                    SUM(CASE WHEN pnl.status = 'failed' THEN 1 ELSE 0 END) as total_failed,
                    ROUND(
                        (SUM(CASE WHEN pnl.status = 'clicked' THEN 1 ELSE 0 END) * 100.0) / 
                        NULLIF(COUNT(*), 0), 2
                    ) as click_rate
                FROM push_notifications_log pnl
                JOIN push_subscriptions ps ON pnl.subscription_id = ps.id
                $whereClause
            ");
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get stats error: " . $e->getMessage());
            return null;
        }
    }
    
    public function getActiveSubscriptions($userId = null) {
        try {
            $whereClause = "WHERE is_active = TRUE";
            $params = [];
            
            if ($userId) {
                $whereClause .= " AND user_id = ?";
                $params[] = $userId;
            }
            
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM push_subscriptions $whereClause");
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        } catch (Exception $e) {
            error_log("Get subscriptions error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function cleanupInactiveSubscriptions($days = 90) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM push_subscriptions 
                WHERE last_used < DATE_SUB(NOW(), INTERVAL ? DAY) 
                AND is_active = FALSE");
            $stmt->execute([$days]);
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Cleanup error: " . $e->getMessage());
            return 0;
        }
    }
    
    // Automated notification triggers
    public function sendOrderConfirmation($userId, $orderId) {
        return $this->sendNotification($userId, 'order_confirmed', [
            'order_id' => $orderId,
            'url' => "/orders/view.php?id=" . $orderId
        ]);
    }
    
    public function sendOrderShipped($userId, $orderId, $trackingNumber, $deliveryDate) {
        return $this->sendNotification($userId, 'order_shipped', [
            'order_id' => $orderId,
            'tracking_number' => $trackingNumber,
            'delivery_date' => $deliveryDate,
            'url' => "/orders/track.php?id=" . $orderId
        ]);
    }
    
    public function sendOrderDelivered($userId, $orderId) {
        return $this->sendNotification($userId, 'order_delivered', [
            'order_id' => $orderId,
            'url' => "/orders/review.php?id=" . $orderId
        ]);
    }
    
    public function sendAbandonedCartReminder($userId, $itemCount) {
        return $this->sendNotification($userId, 'cart_abandoned', [
            'item_count' => $itemCount,
            'url' => "/cart.php"
        ]);
    }
    
    public function sendPriceDropAlert($userId, $productId, $productName, $oldPrice, $newPrice) {
        return $this->sendNotification($userId, 'price_drop', [
            'product_id' => $productId,
            'product_name' => $productName,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'url' => "/products/view.php?id=" . $productId
        ]);
    }
    
    public function sendBackInStockAlert($userId, $productId, $productName) {
        return $this->sendNotification($userId, 'back_in_stock', [
            'product_id' => $productId,
            'product_name' => $productName,
            'url' => "/products/view.php?id=" . $productId
        ]);
    }
    
    public function sendWelcomeNotification($userId) {
        return $this->sendNotification($userId, 'welcome', [
            'url' => "/products.php"
        ]);
    }
}

// Initialize the push notification manager
if (isset($conn)) {
    $pushManager = new PushNotificationManager($conn);
}
?>
