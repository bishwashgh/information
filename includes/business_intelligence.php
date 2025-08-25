<?php
// Business Intelligence and Advanced Reporting
class BusinessIntelligence {
    private $conn;
    
    public function __construct($database) {
        $this->conn = $database;
    }
    
    public function getAdvancedSalesAnalytics($startDate, $endDate, $granularity = 'daily') {
        try {
            switch ($granularity) {
                case 'hourly':
                    $dateFormat = '%Y-%m-%d %H:00:00';
                    $groupBy = 'DATE_FORMAT(o.created_at, "%Y-%m-%d %H")';
                    break;
                case 'weekly':
                    $dateFormat = '%Y-%u';
                    $groupBy = 'YEARWEEK(o.created_at)';
                    break;
                case 'monthly':
                    $dateFormat = '%Y-%m';
                    $groupBy = 'DATE_FORMAT(o.created_at, "%Y-%m")';
                    break;
                default:
                    $dateFormat = '%Y-%m-%d';
                    $groupBy = 'DATE(o.created_at)';
            }
            
            $stmt = $this->conn->prepare("
                SELECT 
                    DATE_FORMAT(o.created_at, ?) as period,
                    COUNT(DISTINCT o.id) as order_count,
                    COUNT(DISTINCT o.user_id) as unique_customers,
                    SUM(o.total_amount) as total_revenue,
                    AVG(o.total_amount) as avg_order_value,
                    SUM(oi.quantity) as total_items_sold,
                    AVG(oi.quantity) as avg_items_per_order
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                WHERE o.created_at BETWEEN ? AND ?
                AND o.status IN ('completed', 'delivered')
                GROUP BY $groupBy
                ORDER BY period
            ");
            
            $stmt->execute([$dateFormat, $startDate, $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Sales analytics error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getCustomerSegmentation() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    customer_segment,
                    COUNT(*) as customer_count,
                    AVG(total_spent) as avg_spent,
                    AVG(order_count) as avg_orders,
                    SUM(total_spent) as segment_revenue
                FROM (
                    SELECT 
                        u.id,
                        u.first_name,
                        u.last_name,
                        u.email,
                        COALESCE(customer_stats.total_spent, 0) as total_spent,
                        COALESCE(customer_stats.order_count, 0) as order_count,
                        CASE 
                            WHEN COALESCE(customer_stats.total_spent, 0) >= 10000 THEN 'VIP'
                            WHEN COALESCE(customer_stats.total_spent, 0) >= 5000 THEN 'Premium'
                            WHEN COALESCE(customer_stats.total_spent, 0) >= 1000 THEN 'Regular'
                            WHEN COALESCE(customer_stats.order_count, 0) > 0 THEN 'New'
                            ELSE 'Inactive'
                        END as customer_segment
                    FROM users u
                    LEFT JOIN (
                        SELECT 
                            o.user_id,
                            SUM(o.total_amount) as total_spent,
                            COUNT(o.id) as order_count,
                            MAX(o.created_at) as last_order_date
                        FROM orders o
                        WHERE o.status IN ('completed', 'delivered')
                        GROUP BY o.user_id
                    ) customer_stats ON u.id = customer_stats.user_id
                    WHERE u.role = 'customer'
                ) segmented_customers
                GROUP BY customer_segment
                ORDER BY avg_spent DESC
            ");
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Customer segmentation error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getProductPerformanceAnalysis($days = 30) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    p.id,
                    p.name,
                    p.sku,
                    p.price,
                    p.stock_quantity,
                    p.category_id,
                    c.name as category_name,
                    COALESCE(sales_data.total_sold, 0) as total_sold,
                    COALESCE(sales_data.total_revenue, 0) as total_revenue,
                    COALESCE(sales_data.order_count, 0) as order_count,
                    COALESCE(views_data.total_views, 0) as total_views,
                    COALESCE(cart_data.cart_additions, 0) as cart_additions,
                    COALESCE(wishlist_data.wishlist_additions, 0) as wishlist_additions,
                    
                    -- Performance metrics
                    CASE 
                        WHEN COALESCE(views_data.total_views, 0) > 0 
                        THEN ROUND((COALESCE(sales_data.order_count, 0) / views_data.total_views) * 100, 2)
                        ELSE 0 
                    END as conversion_rate,
                    
                    CASE 
                        WHEN COALESCE(cart_data.cart_additions, 0) > 0 
                        THEN ROUND((COALESCE(sales_data.order_count, 0) / cart_data.cart_additions) * 100, 2)
                        ELSE 0 
                    END as cart_conversion_rate,
                    
                    -- Stock performance
                    CASE 
                        WHEN p.stock_quantity <= 5 THEN 'Critical'
                        WHEN p.stock_quantity <= 10 THEN 'Low'
                        WHEN p.stock_quantity <= 20 THEN 'Medium'
                        ELSE 'High'
                    END as stock_status,
                    
                    -- Velocity score (sales per day)
                    ROUND(COALESCE(sales_data.total_sold, 0) / ?, 2) as daily_velocity
                    
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                
                LEFT JOIN (
                    SELECT 
                        oi.product_id,
                        SUM(oi.quantity) as total_sold,
                        SUM(oi.price * oi.quantity) as total_revenue,
                        COUNT(DISTINCT oi.order_id) as order_count
                    FROM order_items oi
                    JOIN orders o ON oi.order_id = o.id
                    WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    AND o.status IN ('completed', 'delivered')
                    GROUP BY oi.product_id
                ) sales_data ON p.id = sales_data.product_id
                
                LEFT JOIN (
                    SELECT 
                        product_id,
                        COUNT(*) as total_views
                    FROM product_views
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY product_id
                ) views_data ON p.id = views_data.product_id
                
                LEFT JOIN (
                    SELECT 
                        product_id,
                        COUNT(*) as cart_additions
                    FROM cart_items
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY product_id
                ) cart_data ON p.id = cart_data.product_id
                
                LEFT JOIN (
                    SELECT 
                        product_id,
                        COUNT(*) as wishlist_additions
                    FROM wishlist_items
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY product_id
                ) wishlist_data ON p.id = wishlist_data.product_id
                
                WHERE p.status = 'active'
                ORDER BY total_revenue DESC, total_sold DESC
            ");
            
            $stmt->execute([$days, $days, $days, $days, $days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Product performance analysis error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getCohortAnalysis($months = 12) {
        try {
            $stmt = $this->conn->prepare("
                WITH customer_cohorts AS (
                    SELECT 
                        user_id,
                        DATE_FORMAT(MIN(created_at), '%Y-%m') as cohort_month,
                        MIN(created_at) as first_order_date
                    FROM orders
                    WHERE status IN ('completed', 'delivered')
                    AND created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                    GROUP BY user_id
                ),
                cohort_data AS (
                    SELECT 
                        cc.cohort_month,
                        PERIOD_DIFF(DATE_FORMAT(o.created_at, '%Y%m'), DATE_FORMAT(cc.first_order_date, '%Y%m')) as period_number,
                        COUNT(DISTINCT o.user_id) as customers
                    FROM customer_cohorts cc
                    JOIN orders o ON cc.user_id = o.user_id
                    WHERE o.status IN ('completed', 'delivered')
                    GROUP BY cc.cohort_month, period_number
                ),
                cohort_sizes AS (
                    SELECT 
                        cohort_month,
                        COUNT(DISTINCT user_id) as cohort_size
                    FROM customer_cohorts
                    GROUP BY cohort_month
                )
                SELECT 
                    cd.cohort_month,
                    cd.period_number,
                    cd.customers,
                    cs.cohort_size,
                    ROUND((cd.customers / cs.cohort_size) * 100, 2) as retention_rate
                FROM cohort_data cd
                JOIN cohort_sizes cs ON cd.cohort_month = cs.cohort_month
                ORDER BY cd.cohort_month, cd.period_number
            ");
            
            $stmt->execute([$months]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Cohort analysis error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getRevenueAnalysis($startDate, $endDate) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    -- Revenue breakdown
                    SUM(o.total_amount) as total_revenue,
                    SUM(o.subtotal) as gross_revenue,
                    SUM(o.discount_amount) as total_discounts,
                    SUM(o.tax_amount) as total_tax,
                    SUM(o.shipping_cost) as total_shipping,
                    
                    -- Order metrics
                    COUNT(o.id) as total_orders,
                    COUNT(DISTINCT o.user_id) as unique_customers,
                    AVG(o.total_amount) as avg_order_value,
                    
                    -- Payment method breakdown
                    COUNT(CASE WHEN o.payment_method = 'credit_card' THEN 1 END) as credit_card_orders,
                    COUNT(CASE WHEN o.payment_method = 'debit_card' THEN 1 END) as debit_card_orders,
                    COUNT(CASE WHEN o.payment_method = 'upi' THEN 1 END) as upi_orders,
                    COUNT(CASE WHEN o.payment_method = 'cod' THEN 1 END) as cod_orders,
                    
                    SUM(CASE WHEN o.payment_method = 'credit_card' THEN o.total_amount ELSE 0 END) as credit_card_revenue,
                    SUM(CASE WHEN o.payment_method = 'debit_card' THEN o.total_amount ELSE 0 END) as debit_card_revenue,
                    SUM(CASE WHEN o.payment_method = 'upi' THEN o.total_amount ELSE 0 END) as upi_revenue,
                    SUM(CASE WHEN o.payment_method = 'cod' THEN o.total_amount ELSE 0 END) as cod_revenue,
                    
                    -- Status breakdown
                    COUNT(CASE WHEN o.status = 'completed' THEN 1 END) as completed_orders,
                    COUNT(CASE WHEN o.status = 'delivered' THEN 1 END) as delivered_orders,
                    COUNT(CASE WHEN o.status = 'cancelled' THEN 1 END) as cancelled_orders,
                    COUNT(CASE WHEN o.status = 'refunded' THEN 1 END) as refunded_orders
                    
                FROM orders o
                WHERE o.created_at BETWEEN ? AND ?
            ");
            
            $stmt->execute([$startDate, $endDate]);
            $revenueData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get category-wise revenue
            $stmt = $this->conn->prepare("
                SELECT 
                    c.name as category_name,
                    SUM(oi.price * oi.quantity) as category_revenue,
                    COUNT(oi.id) as items_sold,
                    COUNT(DISTINCT oi.order_id) as orders_containing_category
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN categories c ON p.category_id = c.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.created_at BETWEEN ? AND ?
                AND o.status IN ('completed', 'delivered')
                GROUP BY c.id, c.name
                ORDER BY category_revenue DESC
            ");
            
            $stmt->execute([$startDate, $endDate]);
            $categoryRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'overview' => $revenueData,
                'by_category' => $categoryRevenue
            ];
            
        } catch (Exception $e) {
            error_log("Revenue analysis error: " . $e->getMessage());
            return ['overview' => [], 'by_category' => []];
        }
    }
    
    public function getCustomerLifetimeValue() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    u.id,
                    CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                    u.email,
                    u.created_at as registration_date,
                    customer_metrics.total_spent,
                    customer_metrics.order_count,
                    customer_metrics.avg_order_value,
                    customer_metrics.first_order_date,
                    customer_metrics.last_order_date,
                    customer_metrics.days_since_last_order,
                    customer_metrics.customer_lifetime_days,
                    
                    -- CLV Calculation
                    ROUND(
                        (customer_metrics.total_spent / customer_metrics.customer_lifetime_days) * 365, 2
                    ) as estimated_annual_value,
                    
                    -- Customer status
                    CASE 
                        WHEN customer_metrics.days_since_last_order > 365 THEN 'Churned'
                        WHEN customer_metrics.days_since_last_order > 180 THEN 'At Risk'
                        WHEN customer_metrics.days_since_last_order > 90 THEN 'Inactive'
                        WHEN customer_metrics.order_count >= 10 THEN 'Loyal'
                        WHEN customer_metrics.total_spent >= 5000 THEN 'High Value'
                        ELSE 'Active'
                    END as customer_status
                    
                FROM users u
                JOIN (
                    SELECT 
                        o.user_id,
                        SUM(o.total_amount) as total_spent,
                        COUNT(o.id) as order_count,
                        AVG(o.total_amount) as avg_order_value,
                        MIN(o.created_at) as first_order_date,
                        MAX(o.created_at) as last_order_date,
                        DATEDIFF(NOW(), MAX(o.created_at)) as days_since_last_order,
                        DATEDIFF(MAX(o.created_at), MIN(o.created_at)) + 1 as customer_lifetime_days
                    FROM orders o
                    WHERE o.status IN ('completed', 'delivered')
                    GROUP BY o.user_id
                    HAVING customer_lifetime_days > 0
                ) customer_metrics ON u.id = customer_metrics.user_id
                
                WHERE u.role = 'customer'
                ORDER BY customer_metrics.total_spent DESC
            ");
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Customer lifetime value error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getInventoryAnalysis() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    p.id,
                    p.name,
                    p.sku,
                    p.price,
                    p.stock_quantity,
                    p.stock_quantity * p.price as stock_value,
                    c.name as category_name,
                    
                    -- Sales velocity (last 30 days)
                    COALESCE(sales_30d.total_sold, 0) as sold_last_30d,
                    ROUND(COALESCE(sales_30d.total_sold, 0) / 30, 2) as daily_velocity,
                    
                    -- Stock status
                    CASE 
                        WHEN p.stock_quantity <= 0 THEN 'Out of Stock'
                        WHEN p.stock_quantity <= 5 THEN 'Critical'
                        WHEN p.stock_quantity <= 10 THEN 'Low'
                        WHEN p.stock_quantity <= 20 THEN 'Medium'
                        ELSE 'High'
                    END as stock_status,
                    
                    -- Days of stock remaining
                    CASE 
                        WHEN COALESCE(sales_30d.total_sold, 0) > 0 
                        THEN ROUND(p.stock_quantity / (sales_30d.total_sold / 30), 1)
                        ELSE 999
                    END as days_stock_remaining,
                    
                    -- Reorder recommendations
                    CASE 
                        WHEN p.stock_quantity <= 5 THEN 'Urgent Reorder'
                        WHEN p.stock_quantity <= 10 THEN 'Reorder Soon'
                        WHEN COALESCE(sales_30d.total_sold, 0) > 0 
                             AND p.stock_quantity / (sales_30d.total_sold / 30) <= 7 
                        THEN 'Monitor Closely'
                        ELSE 'Stock OK'
                    END as reorder_status
                    
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN (
                    SELECT 
                        oi.product_id,
                        SUM(oi.quantity) as total_sold
                    FROM order_items oi
                    JOIN orders o ON oi.order_id = o.id
                    WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    AND o.status IN ('completed', 'delivered')
                    GROUP BY oi.product_id
                ) sales_30d ON p.id = sales_30d.product_id
                
                WHERE p.status = 'active'
                ORDER BY 
                    CASE 
                        WHEN p.stock_quantity <= 0 THEN 1
                        WHEN p.stock_quantity <= 5 THEN 2
                        WHEN p.stock_quantity <= 10 THEN 3
                        ELSE 4
                    END,
                    p.stock_quantity ASC
            ");
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Inventory analysis error: " . $e->getMessage());
            return [];
        }
    }
    
    public function exportDataToCSV($data, $filename, $headers = null) {
        try {
            if (empty($data)) {
                return false;
            }
            
            $csvContent = '';
            
            // Add headers
            if ($headers) {
                $csvContent .= implode(',', array_map(function($header) {
                    return '"' . str_replace('"', '""', $header) . '"';
                }, $headers)) . "\n";
            } else {
                // Use first row keys as headers
                $headers = array_keys($data[0]);
                $csvContent .= implode(',', array_map(function($header) {
                    return '"' . str_replace('"', '""', $header) . '"';
                }, $headers)) . "\n";
            }
            
            // Add data rows
            foreach ($data as $row) {
                $csvRow = array_map(function($value) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }, array_values($row));
                $csvContent .= implode(',', $csvRow) . "\n";
            }
            
            // Set headers for download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($csvContent));
            
            echo $csvContent;
            return true;
            
        } catch (Exception $e) {
            error_log("CSV export error: " . $e->getMessage());
            return false;
        }
    }
    
    public function generateExecutiveSummary($startDate, $endDate) {
        try {
            $summary = [];
            
            // Revenue summary
            $revenueData = $this->getRevenueAnalysis($startDate, $endDate);
            $summary['revenue'] = $revenueData['overview'];
            
            // Top products
            $productData = $this->getProductPerformanceAnalysis(30);
            $summary['top_products'] = array_slice($productData, 0, 10);
            
            // Customer segments
            $summary['customer_segments'] = $this->getCustomerSegmentation();
            
            // Key metrics
            $stmt = $this->conn->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM products WHERE status = 'active') as total_products,
                    (SELECT COUNT(*) FROM users WHERE role = 'customer') as total_customers,
                    (SELECT COUNT(*) FROM orders WHERE created_at BETWEEN ? AND ?) as period_orders,
                    (SELECT AVG(total_amount) FROM orders WHERE created_at BETWEEN ? AND ? AND status IN ('completed', 'delivered')) as avg_order_value
            ");
            $stmt->execute([$startDate, $endDate, $startDate, $endDate]);
            $summary['key_metrics'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $summary;
            
        } catch (Exception $e) {
            error_log("Executive summary error: " . $e->getMessage());
            return [];
        }
    }
}
?>
