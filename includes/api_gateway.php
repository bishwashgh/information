<?php
// API Gateway and External Integrations
class APIGateway {
    private $conn;
    private $apiKeys;
    private $rateLimits;
    
    public function __construct($database) {
        $this->conn = $database;
        $this->apiKeys = [];
        $this->rateLimits = [
            'default' => ['requests' => 1000, 'window' => 3600], // 1000 requests per hour
            'premium' => ['requests' => 5000, 'window' => 3600],  // 5000 requests per hour
            'enterprise' => ['requests' => 10000, 'window' => 3600] // 10000 requests per hour
        ];
    }
    
    public function authenticate($apiKey, $secretKey = null) {
        try {
            $stmt = $this->conn->prepare("
                SELECT ak.*, u.role, u.email 
                FROM api_keys ak
                JOIN users u ON ak.user_id = u.id
                WHERE ak.api_key = ? AND ak.status = 'active'
                AND (ak.expires_at IS NULL OR ak.expires_at > NOW())
            ");
            $stmt->execute([$apiKey]);
            $keyData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$keyData) {
                return ['success' => false, 'error' => 'Invalid API key'];
            }
            
            // Verify secret key if provided
            if ($secretKey && !password_verify($secretKey, $keyData['secret_hash'])) {
                return ['success' => false, 'error' => 'Invalid secret key'];
            }
            
            // Check rate limits
            $rateLimitCheck = $this->checkRateLimit($keyData['id'], $keyData['rate_limit_tier']);
            if (!$rateLimitCheck['allowed']) {
                return [
                    'success' => false, 
                    'error' => 'Rate limit exceeded',
                    'retry_after' => $rateLimitCheck['retry_after']
                ];
            }
            
            // Log API usage
            $this->logAPIUsage($keyData['id'], $_SERVER['REQUEST_URI']);
            
            return ['success' => true, 'user_data' => $keyData];
            
        } catch (Exception $e) {
            error_log("API authentication error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Authentication failed'];
        }
    }
    
    private function checkRateLimit($apiKeyId, $tier = 'default') {
        try {
            $limits = $this->rateLimits[$tier] ?? $this->rateLimits['default'];
            $windowStart = date('Y-m-d H:i:s', time() - $limits['window']);
            
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as request_count
                FROM api_usage_logs
                WHERE api_key_id = ? AND created_at >= ?
            ");
            $stmt->execute([$apiKeyId, $windowStart]);
            $usage = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $requestCount = $usage['request_count'] ?? 0;
            $allowed = $requestCount < $limits['requests'];
            
            return [
                'allowed' => $allowed,
                'remaining' => max(0, $limits['requests'] - $requestCount),
                'reset_time' => time() + $limits['window'],
                'retry_after' => $allowed ? null : $limits['window']
            ];
            
        } catch (Exception $e) {
            error_log("Rate limit check error: " . $e->getMessage());
            return ['allowed' => false, 'remaining' => 0];
        }
    }
    
    private function logAPIUsage($apiKeyId, $endpoint, $method = null, $responseCode = null) {
        try {
            $method = $method ?: $_SERVER['REQUEST_METHOD'];
            $responseCode = $responseCode ?: 200;
            
            $stmt = $this->conn->prepare("
                INSERT INTO api_usage_logs (api_key_id, endpoint, method, response_code, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $apiKeyId,
                $endpoint,
                $method,
                $responseCode,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
        } catch (Exception $e) {
            error_log("API usage logging error: " . $e->getMessage());
        }
    }
    
    // Product API Endpoints
    public function getProducts($filters = []) {
        try {
            $whereConditions = ["p.status = 'active'"];
            $params = [];
            
            if (!empty($filters['category_id'])) {
                $whereConditions[] = "p.category_id = ?";
                $params[] = $filters['category_id'];
            }
            
            if (!empty($filters['search'])) {
                $whereConditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($filters['min_price'])) {
                $whereConditions[] = "p.price >= ?";
                $params[] = $filters['min_price'];
            }
            
            if (!empty($filters['max_price'])) {
                $whereConditions[] = "p.price <= ?";
                $params[] = $filters['max_price'];
            }
            
            $limit = isset($filters['limit']) ? min((int)$filters['limit'], 100) : 20;
            $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;
            
            $whereClause = implode(' AND ', $whereConditions);
            
            $stmt = $this->conn->prepare("
                SELECT 
                    p.id,
                    p.name,
                    p.slug,
                    p.description,
                    p.price,
                    p.sale_price,
                    p.sku,
                    p.stock_quantity,
                    p.weight,
                    p.dimensions,
                    p.status,
                    c.name as category_name,
                    c.slug as category_slug,
                    GROUP_CONCAT(pi.image_url) as images,
                    AVG(pr.rating) as average_rating,
                    COUNT(pr.id) as review_count
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_images pi ON p.id = pi.product_id
                LEFT JOIN product_reviews pr ON p.id = pr.product_id
                WHERE $whereClause
                GROUP BY p.id
                ORDER BY p.created_at DESC
                LIMIT $offset, $limit
            ");
            
            // Remove limit and offset from params
            // $params[] = $limit;
            // $params[] = $offset;
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format response
            foreach ($products as &$product) {
                $product['images'] = $product['images'] ? explode(',', $product['images']) : [];
                $product['average_rating'] = $product['average_rating'] ? (float)$product['average_rating'] : null;
                $product['review_count'] = (int)$product['review_count'];
                $product['price'] = (float)$product['price'];
                $product['sale_price'] = $product['sale_price'] ? (float)$product['sale_price'] : null;
                $product['stock_quantity'] = (int)$product['stock_quantity'];
            }
            
            return [
                'success' => true,
                'data' => $products,
                'meta' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'total' => $this->getProductCount($whereConditions, array_slice($params, 0, -2))
                ]
            ];
            
        } catch (Exception $e) {
            error_log("API get products error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to fetch products'];
        }
    }
    
    private function getProductCount($whereConditions, $params) {
        try {
            $whereClause = implode(' AND ', $whereConditions);
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM products p WHERE $whereClause");
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } catch (Exception $e) {
            return 0;
        }
    }
    
    public function getProduct($productId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    p.*,
                    c.name as category_name,
                    c.slug as category_slug,
                    AVG(pr.rating) as average_rating,
                    COUNT(pr.id) as review_count
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_reviews pr ON p.id = pr.product_id
                WHERE p.id = ? AND p.status = 'active'
                GROUP BY p.id
            ");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                return ['success' => false, 'error' => 'Product not found'];
            }
            
            // Get product images
            $stmt = $this->conn->prepare("SELECT image_url, alt_text FROM product_images WHERE product_id = ? ORDER BY sort_order");
            $stmt->execute([$productId]);
            $product['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get product attributes
            $stmt = $this->conn->prepare("
                SELECT pa.name, pav.value 
                FROM product_attribute_values pav
                JOIN product_attributes pa ON pav.attribute_id = pa.id
                WHERE pav.product_id = ?
            ");
            $stmt->execute([$productId]);
            $product['attributes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format numeric values
            $product['price'] = (float)$product['price'];
            $product['sale_price'] = $product['sale_price'] ? (float)$product['sale_price'] : null;
            $product['stock_quantity'] = (int)$product['stock_quantity'];
            $product['average_rating'] = $product['average_rating'] ? (float)$product['average_rating'] : null;
            $product['review_count'] = (int)$product['review_count'];
            
            return ['success' => true, 'data' => $product];
            
        } catch (Exception $e) {
            error_log("API get product error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to fetch product'];
        }
    }
    
    // Order API Endpoints
    public function createOrder($orderData, $userId) {
        try {
            $this->conn->beginTransaction();
            
            // Validate required fields
            $required = ['items', 'shipping_address', 'payment_method'];
            foreach ($required as $field) {
                if (!isset($orderData[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            // Calculate totals
            $subtotal = 0;
            $items = [];
            
            foreach ($orderData['items'] as $item) {
                $stmt = $this->conn->prepare("SELECT id, name, price, sale_price, stock_quantity FROM products WHERE id = ? AND status = 'active'");
                $stmt->execute([$item['product_id']]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$product) {
                    throw new Exception("Product not found: " . $item['product_id']);
                }
                
                if ($product['stock_quantity'] < $item['quantity']) {
                    throw new Exception("Insufficient stock for product: " . $product['name']);
                }
                
                $price = $product['sale_price'] ?: $product['price'];
                $itemTotal = $price * $item['quantity'];
                $subtotal += $itemTotal;
                
                $items[] = [
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'price' => $price,
                    'quantity' => $item['quantity'],
                    'total' => $itemTotal
                ];
            }
            
            // Calculate tax and shipping
            $taxRate = 0.18; // 18% GST
            $taxAmount = $subtotal * $taxRate;
            $shippingCost = $orderData['shipping_cost'] ?? 50;
            $discountAmount = $orderData['discount_amount'] ?? 0;
            $totalAmount = $subtotal + $taxAmount + $shippingCost - $discountAmount;
            
            // Create order
            $orderNumber = 'ORD-' . date('YmdHis') . '-' . rand(1000, 9999);
            
            $stmt = $this->conn->prepare("
                INSERT INTO orders (
                    order_number, user_id, subtotal, tax_amount, shipping_cost, 
                    discount_amount, total_amount, payment_method, payment_status,
                    shipping_address, billing_address, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $orderNumber,
                $userId,
                $subtotal,
                $taxAmount,
                $shippingCost,
                $discountAmount,
                $totalAmount,
                $orderData['payment_method'],
                'pending',
                json_encode($orderData['shipping_address']),
                json_encode($orderData['billing_address'] ?? $orderData['shipping_address']),
            ]);
            
            $orderId = $this->conn->lastInsertId();
            
            // Add order items
            foreach ($items as $item) {
                $stmt = $this->conn->prepare("
                    INSERT INTO order_items (order_id, product_id, product_name, price, quantity, total)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['product_name'],
                    $item['price'],
                    $item['quantity'],
                    $item['total']
                ]);
                
                // Update stock
                $stmt = $this->conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'data' => [
                    'order_id' => $orderId,
                    'order_number' => $orderNumber,
                    'total_amount' => $totalAmount,
                    'status' => 'pending'
                ]
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("API create order error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function getOrders($userId, $filters = []) {
        try {
            $whereConditions = ["o.user_id = ?"];
            $params = [$userId];
            
            if (!empty($filters['status'])) {
                $whereConditions[] = "o.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['from_date'])) {
                $whereConditions[] = "o.created_at >= ?";
                $params[] = $filters['from_date'];
            }
            
            if (!empty($filters['to_date'])) {
                $whereConditions[] = "o.created_at <= ?";
                $params[] = $filters['to_date'];
            }
            
            $limit = isset($filters['limit']) ? min((int)$filters['limit'], 50) : 20;
            $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;
            
            $whereClause = implode(' AND ', $whereConditions);
            
            $stmt = $this->conn->prepare("
                SELECT 
                    o.id,
                    o.order_number,
                    o.total_amount,
                    o.status,
                    o.payment_status,
                    o.payment_method,
                    o.created_at,
                    o.updated_at,
                    COUNT(oi.id) as item_count
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE $whereClause
                GROUP BY o.id
                ORDER BY o.created_at DESC
                LIMIT $offset, $limit
            ");
            
            // Remove limit and offset from params
            // $params[] = $limit;
            // $params[] = $offset;
            $stmt->execute($params);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format response
            foreach ($orders as &$order) {
                $order['total_amount'] = (float)$order['total_amount'];
                $order['item_count'] = (int)$order['item_count'];
            }
            
            return [
                'success' => true,
                'data' => $orders,
                'meta' => [
                    'limit' => $limit,
                    'offset' => $offset
                ]
            ];
            
        } catch (Exception $e) {
            error_log("API get orders error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to fetch orders'];
        }
    }
    
    // Webhook Management
    public function registerWebhook($url, $events, $secret = null) {
        try {
            $secret = $secret ?: bin2hex(random_bytes(32));
            
            $stmt = $this->conn->prepare("
                INSERT INTO webhooks (url, events, secret, status, created_at)
                VALUES (?, ?, ?, 'active', NOW())
            ");
            
            $stmt->execute([
                $url,
                json_encode($events),
                password_hash($secret, PASSWORD_ARGON2ID)
            ]);
            
            return [
                'success' => true,
                'data' => [
                    'webhook_id' => $this->conn->lastInsertId(),
                    'secret' => $secret
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Webhook registration error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to register webhook'];
        }
    }
    
    public function triggerWebhook($event, $data) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, url, secret FROM webhooks 
                WHERE JSON_CONTAINS(events, ?) AND status = 'active'
            ");
            $stmt->execute([json_encode($event)]);
            $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($webhooks as $webhook) {
                $this->sendWebhookRequest($webhook, $event, $data);
            }
            
        } catch (Exception $e) {
            error_log("Webhook trigger error: " . $e->getMessage());
        }
    }
    
    private function sendWebhookRequest($webhook, $event, $data) {
        try {
            $payload = [
                'event' => $event,
                'data' => $data,
                'timestamp' => time()
            ];
            
            $signature = hash_hmac('sha256', json_encode($payload), $webhook['secret']);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $webhook['url'],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-Webhook-Signature: sha256=' . $signature
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Log webhook delivery
            $stmt = $this->conn->prepare("
                INSERT INTO webhook_deliveries (webhook_id, event, payload, response_code, response_body, delivered_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $webhook['id'],
                $event,
                json_encode($payload),
                $httpCode,
                $response
            ]);
            
        } catch (Exception $e) {
            error_log("Webhook delivery error: " . $e->getMessage());
        }
    }
    
    public function generateAPIResponse($success, $data = null, $error = null, $meta = null) {
        $response = [
            'success' => $success,
            'timestamp' => time(),
            'version' => '1.0'
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        if ($error !== null) {
            $response['error'] = $error;
        }
        
        if ($meta !== null) {
            $response['meta'] = $meta;
        }
        
        header('Content-Type: application/json');
        return json_encode($response);
    }
}
?>
