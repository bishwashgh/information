<?php
// Inventory Management System with automation
class InventoryManager {
    private $conn;
    private $smsIntegration;
    private $lowStockThreshold = 10;
    private $criticalStockThreshold = 5;
    
    public function __construct($database, $smsIntegration = null) {
        $this->conn = $database;
        $this->smsIntegration = $smsIntegration;
    }
    
    public function updateStock($productId, $quantity, $operation = 'reduce', $reason = '', $orderId = null) {
        try {
            $this->conn->beginTransaction();
            
            // Get current stock
            $stmt = $this->conn->prepare("SELECT stock_quantity, name FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            if (!$product) {
                throw new Exception('Product not found');
            }
            
            $currentStock = $product['stock_quantity'];
            $newStock = $operation === 'reduce' ? $currentStock - $quantity : $currentStock + $quantity;
            
            // Prevent negative stock
            if ($newStock < 0) {
                throw new Exception('Insufficient stock');
            }
            
            // Update product stock
            $stmt = $this->conn->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
            $stmt->execute([$newStock, $productId]);
            
            // Log stock movement
            $this->logStockMovement($productId, $quantity, $operation, $currentStock, $newStock, $reason, $orderId);
            
            // Check for low stock alerts
            $this->checkLowStockAlert($productId, $newStock, $product['name']);
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'previous_stock' => $currentStock,
                'new_stock' => $newStock
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Stock update error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function bulkUpdateStock($stockUpdates) {
        try {
            $this->conn->beginTransaction();
            $results = [];
            
            foreach ($stockUpdates as $update) {
                $result = $this->updateStock(
                    $update['product_id'],
                    $update['quantity'],
                    $update['operation'] ?? 'set',
                    $update['reason'] ?? 'Bulk update',
                    $update['order_id'] ?? null
                );
                
                $results[] = [
                    'product_id' => $update['product_id'],
                    'result' => $result
                ];
                
                if (!$result['success']) {
                    throw new Exception("Failed to update stock for product {$update['product_id']}: {$result['error']}");
                }
            }
            
            $this->conn->commit();
            return ['success' => true, 'results' => $results];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Bulk stock update error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function logStockMovement($productId, $quantity, $operation, $previousStock, $newStock, $reason, $orderId) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO inventory_logs (
                    product_id, quantity_changed, operation, previous_stock, 
                    new_stock, reason, order_id, user_id, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $productId,
                $quantity,
                $operation,
                $previousStock,
                $newStock,
                $reason,
                $orderId,
                $_SESSION['user_id'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Inventory log error: " . $e->getMessage());
        }
    }
    
    private function checkLowStockAlert($productId, $currentStock, $productName) {
        try {
            if ($currentStock <= $this->criticalStockThreshold) {
                $this->sendCriticalStockAlert($productId, $currentStock, $productName);
            } elseif ($currentStock <= $this->lowStockThreshold) {
                $this->sendLowStockAlert($productId, $currentStock, $productName);
            }
        } catch (Exception $e) {
            error_log("Stock alert error: " . $e->getMessage());
        }
    }
    
    private function sendLowStockAlert($productId, $currentStock, $productName) {
        // Check if alert was already sent recently
        $stmt = $this->conn->prepare("
            SELECT id FROM stock_alerts 
            WHERE product_id = ? AND alert_type = 'low_stock' 
            AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([$productId]);
        
        if ($stmt->fetch()) {
            return; // Alert already sent in last 24 hours
        }
        
        // Create alert record
        $stmt = $this->conn->prepare("
            INSERT INTO stock_alerts (product_id, alert_type, current_stock, threshold_value, created_at)
            VALUES (?, 'low_stock', ?, ?, NOW())
        ");
        $stmt->execute([$productId, $currentStock, $this->lowStockThreshold]);
        
        // Send notifications
        $this->notifyLowStock($productId, $currentStock, $productName, 'low');
    }
    
    private function sendCriticalStockAlert($productId, $currentStock, $productName) {
        // Check if alert was already sent recently
        $stmt = $this->conn->prepare("
            SELECT id FROM stock_alerts 
            WHERE product_id = ? AND alert_type = 'critical_stock' 
            AND created_at > DATE_SUB(NOW(), INTERVAL 12 HOUR)
        ");
        $stmt->execute([$productId]);
        
        if ($stmt->fetch()) {
            return; // Alert already sent in last 12 hours
        }
        
        // Create alert record
        $stmt = $this->conn->prepare("
            INSERT INTO stock_alerts (product_id, alert_type, current_stock, threshold_value, created_at)
            VALUES (?, 'critical_stock', ?, ?, NOW())
        ");
        $stmt->execute([$productId, $currentStock, $this->criticalStockThreshold]);
        
        // Send notifications
        $this->notifyLowStock($productId, $currentStock, $productName, 'critical');
    }
    
    private function notifyLowStock($productId, $currentStock, $productName, $severity) {
        try {
            // Email notification to admin
            $this->sendStockAlertEmail($productId, $currentStock, $productName, $severity);
            
            // SMS notification if configured
            if ($this->smsIntegration) {
                $this->sendStockAlertSMS($productId, $currentStock, $productName, $severity);
            }
            
            // In-app notification
            $this->createInAppNotification($productId, $currentStock, $productName, $severity);
            
        } catch (Exception $e) {
            error_log("Stock notification error: " . $e->getMessage());
        }
    }
    
    private function sendStockAlertEmail($productId, $currentStock, $productName, $severity) {
        $subject = ($severity === 'critical' ? 'CRITICAL' : 'LOW') . " Stock Alert: {$productName}";
        $message = "
            <h3>{$subject}</h3>
            <p>Product: <strong>{$productName}</strong></p>
            <p>Current Stock: <strong>{$currentStock} units</strong></p>
            <p>Alert Level: <strong>" . strtoupper($severity) . "</strong></p>
            <p>Please restock this item immediately.</p>
            <p><a href='" . SITE_URL . "/admin/products.php?id={$productId}'>Manage Product</a></p>
        ";
        
        // Send to admin emails
        $this->sendAlertToAdmins($subject, $message);
    }
    
    private function sendStockAlertSMS($productId, $currentStock, $productName, $severity) {
        $message = ($severity === 'critical' ? 'CRITICAL' : 'LOW') . " STOCK: {$productName} has only {$currentStock} units left. Please restock immediately.";
        
        // Get admin phone numbers
        $stmt = $this->conn->prepare("
            SELECT phone FROM users 
            WHERE role = 'admin' AND phone IS NOT NULL 
            AND notification_preferences LIKE '%stock_alerts%'
        ");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($admins as $admin) {
            $this->smsIntegration->sendSMS($admin['phone'], $message);
        }
    }
    
    private function createInAppNotification($productId, $currentStock, $productName, $severity) {
        $stmt = $this->conn->prepare("
            INSERT INTO admin_notifications (
                type, title, message, severity, data, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $title = ($severity === 'critical' ? 'Critical' : 'Low') . " Stock Alert";
        $message = "{$productName} has only {$currentStock} units remaining";
        $data = json_encode(['product_id' => $productId, 'current_stock' => $currentStock]);
        
        $stmt->execute(['stock_alert', $title, $message, $severity, $data]);
    }
    
    public function getInventoryReport($days = 30) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    p.id,
                    p.name,
                    p.sku,
                    p.stock_quantity,
                    p.price,
                    p.stock_quantity * p.price as stock_value,
                    COALESCE(sales.total_sold, 0) as total_sold,
                    COALESCE(movements.total_movements, 0) as total_movements,
                    (p.stock_quantity + COALESCE(sales.total_sold, 0)) as initial_stock
                FROM products p
                LEFT JOIN (
                    SELECT 
                        oi.product_id,
                        SUM(oi.quantity) as total_sold
                    FROM order_items oi
                    JOIN orders o ON oi.order_id = o.id
                    WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    AND o.status IN ('completed', 'delivered')
                    GROUP BY oi.product_id
                ) sales ON p.id = sales.product_id
                LEFT JOIN (
                    SELECT 
                        product_id,
                        COUNT(*) as total_movements
                    FROM inventory_logs
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY product_id
                ) movements ON p.id = movements.product_id
                WHERE p.status = 'active'
                ORDER BY p.name
            ");
            
            $stmt->execute([$days, $days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Inventory report error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getLowStockProducts() {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, name, sku, stock_quantity, price
                FROM products 
                WHERE stock_quantity <= ? 
                AND status = 'active'
                ORDER BY stock_quantity ASC
            ");
            $stmt->execute([$this->lowStockThreshold]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Low stock products error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getCriticalStockProducts() {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, name, sku, stock_quantity, price
                FROM products 
                WHERE stock_quantity <= ? 
                AND status = 'active'
                ORDER BY stock_quantity ASC
            ");
            $stmt->execute([$this->criticalStockThreshold]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Critical stock products error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getStockMovements($productId = null, $days = 30) {
        try {
            $sql = "
                SELECT 
                    il.*,
                    p.name as product_name,
                    p.sku,
                    u.first_name,
                    u.last_name
                FROM inventory_logs il
                JOIN products p ON il.product_id = p.id
                LEFT JOIN users u ON il.user_id = u.id
                WHERE il.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ";
            
            $params = [$days];
            
            if ($productId) {
                $sql .= " AND il.product_id = ?";
                $params[] = $productId;
            }
            
            $sql .= " ORDER BY il.created_at DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Stock movements error: " . $e->getMessage());
            return [];
        }
    }
    
    public function setStockThresholds($lowStock, $criticalStock) {
        $this->lowStockThreshold = max(1, intval($lowStock));
        $this->criticalStockThreshold = max(1, intval($criticalStock));
        
        // Save to database
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO system_settings (setting_key, setting_value) 
                VALUES ('low_stock_threshold', ?), ('critical_stock_threshold', ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $stmt->execute([$this->lowStockThreshold, $this->criticalStockThreshold]);
        } catch (Exception $e) {
            error_log("Set stock thresholds error: " . $e->getMessage());
        }
    }
    
    public function getStockAlerts($limit = 50) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    sa.*,
                    p.name as product_name,
                    p.sku
                FROM stock_alerts sa
                JOIN products p ON sa.product_id = p.id
                ORDER BY sa.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Stock alerts error: " . $e->getMessage());
            return [];
        }
    }
    
    public function predictStockDepletion($productId) {
        try {
            // Calculate average daily sales for the product
            $stmt = $this->conn->prepare("
                SELECT 
                    AVG(daily_sales) as avg_daily_sales
                FROM (
                    SELECT 
                        DATE(o.created_at) as sale_date,
                        SUM(oi.quantity) as daily_sales
                    FROM order_items oi
                    JOIN orders o ON oi.order_id = o.id
                    WHERE oi.product_id = ?
                    AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    AND o.status IN ('completed', 'delivered')
                    GROUP BY DATE(o.created_at)
                ) daily_totals
            ");
            $stmt->execute([$productId]);
            $result = $stmt->fetch();
            
            if (!$result || !$result['avg_daily_sales']) {
                return null; // No sales data available
            }
            
            // Get current stock
            $stmt = $this->conn->prepare("SELECT stock_quantity FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            if (!$product) {
                return null;
            }
            
            $avgDailySales = floatval($result['avg_daily_sales']);
            $currentStock = intval($product['stock_quantity']);
            
            if ($avgDailySales > 0) {
                $daysUntilDepletion = $currentStock / $avgDailySales;
                
                return [
                    'current_stock' => $currentStock,
                    'avg_daily_sales' => $avgDailySales,
                    'days_until_depletion' => round($daysUntilDepletion, 1),
                    'predicted_depletion_date' => date('Y-m-d', strtotime("+{$daysUntilDepletion} days"))
                ];
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Stock prediction error: " . $e->getMessage());
            return null;
        }
    }
    
    private function sendAlertToAdmins($subject, $message) {
        try {
            $stmt = $this->conn->prepare("
                SELECT email FROM users 
                WHERE role = 'admin' AND email IS NOT NULL
            ");
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($admins as $admin) {
                // Use your email sending function here
                sendEmail($admin['email'], $subject, $message);
            }
        } catch (Exception $e) {
            error_log("Send admin alert error: " . $e->getMessage());
        }
    }
}

// Automated reorder system
class AutoReorderSystem {
    private $inventoryManager;
    private $conn;
    
    public function __construct($inventoryManager, $database) {
        $this->inventoryManager = $inventoryManager;
        $this->conn = $database;
    }
    
    public function checkAndCreateReorders() {
        try {
            $lowStockProducts = $this->inventoryManager->getLowStockProducts();
            
            foreach ($lowStockProducts as $product) {
                if ($this->shouldCreateReorder($product['id'])) {
                    $this->createReorderRequest($product);
                }
            }
        } catch (Exception $e) {
            error_log("Auto reorder check error: " . $e->getMessage());
        }
    }
    
    private function shouldCreateReorder($productId) {
        // Check if reorder already exists and is pending
        $stmt = $this->conn->prepare("
            SELECT id FROM reorder_requests 
            WHERE product_id = ? 
            AND status IN ('pending', 'ordered')
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$productId]);
        
        return !$stmt->fetch(); // Create reorder if none exists
    }
    
    private function createReorderRequest($product) {
        try {
            // Calculate suggested reorder quantity
            $prediction = $this->inventoryManager->predictStockDepletion($product['id']);
            $suggestedQuantity = $this->calculateReorderQuantity($product, $prediction);
            
            $stmt = $this->conn->prepare("
                INSERT INTO reorder_requests (
                    product_id, current_stock, suggested_quantity, 
                    status, created_at
                ) VALUES (?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $product['id'],
                $product['stock_quantity'],
                $suggestedQuantity
            ]);
            
            $this->notifyReorderNeeded($product, $suggestedQuantity);
            
        } catch (Exception $e) {
            error_log("Create reorder error: " . $e->getMessage());
        }
    }
    
    private function calculateReorderQuantity($product, $prediction) {
        if ($prediction && $prediction['avg_daily_sales'] > 0) {
            // Order enough for 30 days plus current deficit
            $monthlyNeed = ceil($prediction['avg_daily_sales'] * 30);
            $deficit = max(0, 20 - $product['stock_quantity']); // Assuming 20 as target stock
            return $monthlyNeed + $deficit;
        }
        
        // Default reorder quantity
        return 50;
    }
    
    private function notifyReorderNeeded($product, $quantity) {
        $title = "Reorder Required";
        $message = "Product '{$product['name']}' needs reordering. Suggested quantity: {$quantity} units.";
        
        $stmt = $this->conn->prepare("
            INSERT INTO admin_notifications (
                type, title, message, severity, data, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $data = json_encode([
            'product_id' => $product['id'],
            'suggested_quantity' => $quantity,
            'current_stock' => $product['stock_quantity']
        ]);
        
        $stmt->execute(['reorder_request', $title, $message, 'medium', $data]);
    }
}
?>
