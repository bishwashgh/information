<?php
require_once '../includes/config.php';
requireLogin();
requireAdmin();

// Set content type based on export type
$exportType = $_GET['type'] ?? 'pdf';
$period = $_GET['period'] ?? 30;
$category = $_GET['category'] ?? '';

$db = Database::getInstance()->getConnection();

// Build date condition
$dateCondition = "created_at >= DATE_SUB(NOW(), INTERVAL {$period} DAY)";

// Build category condition
$categoryCondition = '';
if ($category) {
    $categoryCondition = "AND p.category = '" . $db->quote($category) . "'";
}

// Get comprehensive analytics data
$analytics = [];

// Sales summary
$stmt = $db->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as avg_order_value,
        MIN(total_amount) as min_order,
        MAX(total_amount) as max_order
    FROM orders 
    WHERE {$dateCondition} AND payment_status = 'paid'
");
$analytics['sales_summary'] = $stmt->fetch(PDO::FETCH_ASSOC);

// Daily sales data
$stmt = $db->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as orders,
        SUM(total_amount) as revenue,
        AVG(total_amount) as avg_order
    FROM orders 
    WHERE {$dateCondition} AND payment_status = 'paid'
    GROUP BY DATE(created_at)
    ORDER BY date
");
$analytics['daily_sales'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Product performance
$stmt = $db->query("
    SELECT 
        p.name,
        p.category,
        p.price,
        SUM(oi.quantity) as total_sold,
        SUM(oi.price * oi.quantity) as total_revenue,
        AVG(oi.price) as avg_price
    FROM products p
    JOIN order_items oi ON p.id = oi.product_id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.{$dateCondition} AND o.payment_status = 'paid' {$categoryCondition}
    GROUP BY p.id, p.name, p.category, p.price
    ORDER BY total_revenue DESC
");
$analytics['product_performance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Customer analysis
$stmt = $db->query("
    SELECT 
        COUNT(DISTINCT o.user_id) as unique_customers,
        COUNT(*) as total_orders,
        AVG(orders_per_customer) as avg_orders_per_customer
    FROM (
        SELECT user_id, COUNT(*) as orders_per_customer
        FROM orders 
        WHERE {$dateCondition} AND payment_status = 'paid'
        GROUP BY user_id
    ) as customer_orders
    JOIN orders o ON o.user_id = customer_orders.user_id
    WHERE o.{$dateCondition} AND o.payment_status = 'paid'
");
$analytics['customer_analysis'] = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle different export types
switch ($exportType) {
    case 'pdf':
        exportToPDF($analytics, $period);
        break;
    case 'excel':
        exportToExcel($analytics, $period);
        break;
    case 'csv':
        exportToCSV($analytics, $period);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid export type']);
        exit;
}

function exportToPDF($analytics, $period) {
    // Simple HTML to PDF conversion (in a real app, use libraries like TCPDF or DOMPDF)
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="analytics_report_' . date('Y-m-d') . '.pdf"');
    
    // For now, output HTML that can be converted to PDF
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Analytics Report</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .summary { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; }
            .metric { display: inline-block; margin: 10px 20px; text-align: center; }
            .metric-value { font-size: 24px; font-weight: bold; color: #007bff; }
            .metric-label { font-size: 12px; color: #666; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background-color: #f8f9fa; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Analytics Report</h1>
            <p>Period: Last <?php echo $period; ?> days</p>
            <p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
        
        <div class="summary">
            <h2>Sales Summary</h2>
            <div class="metric">
                <div class="metric-value"><?php echo number_format($analytics['sales_summary']['total_orders']); ?></div>
                <div class="metric-label">Total Orders</div>
            </div>
            <div class="metric">
                <div class="metric-value">$<?php echo number_format($analytics['sales_summary']['total_revenue'], 2); ?></div>
                <div class="metric-label">Total Revenue</div>
            </div>
            <div class="metric">
                <div class="metric-value">$<?php echo number_format($analytics['sales_summary']['avg_order_value'], 2); ?></div>
                <div class="metric-label">Avg Order Value</div>
            </div>
        </div>
        
        <h2>Daily Sales Performance</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Orders</th>
                    <th>Revenue</th>
                    <th>Avg Order</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($analytics['daily_sales'] as $day): ?>
                <tr>
                    <td><?php echo $day['date']; ?></td>
                    <td><?php echo number_format($day['orders']); ?></td>
                    <td>$<?php echo number_format($day['revenue'], 2); ?></td>
                    <td>$<?php echo number_format($day['avg_order'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h2>Top Performing Products</h2>
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Units Sold</th>
                    <th>Revenue</th>
                    <th>Avg Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($analytics['product_performance'], 0, 20) as $product): ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo ucfirst($product['category']); ?></td>
                    <td><?php echo number_format($product['total_sold']); ?></td>
                    <td>$<?php echo number_format($product['total_revenue'], 2); ?></td>
                    <td>$<?php echo number_format($product['avg_price'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
}

function exportToExcel($analytics, $period) {
    // Set headers for Excel file
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="analytics_report_' . date('Y-m-d') . '.xls"');
    
    echo '<table border="1">';
    echo '<tr><th colspan="4">Analytics Report - Last ' . $period . ' days</th></tr>';
    echo '<tr><th colspan="4">Generated: ' . date('Y-m-d H:i:s') . '</th></tr>';
    echo '<tr><th colspan="4"></th></tr>';
    
    // Sales Summary
    echo '<tr><th colspan="4">Sales Summary</th></tr>';
    echo '<tr><td>Total Orders</td><td>' . number_format($analytics['sales_summary']['total_orders']) . '</td><td></td><td></td></tr>';
    echo '<tr><td>Total Revenue</td><td>$' . number_format($analytics['sales_summary']['total_revenue'], 2) . '</td><td></td><td></td></tr>';
    echo '<tr><td>Average Order Value</td><td>$' . number_format($analytics['sales_summary']['avg_order_value'], 2) . '</td><td></td><td></td></tr>';
    echo '<tr><td></td><td></td><td></td><td></td></tr>';
    
    // Daily Sales
    echo '<tr><th>Date</th><th>Orders</th><th>Revenue</th><th>Avg Order</th></tr>';
    foreach ($analytics['daily_sales'] as $day) {
        echo '<tr>';
        echo '<td>' . $day['date'] . '</td>';
        echo '<td>' . number_format($day['orders']) . '</td>';
        echo '<td>$' . number_format($day['revenue'], 2) . '</td>';
        echo '<td>$' . number_format($day['avg_order'], 2) . '</td>';
        echo '</tr>';
    }
    
    echo '<tr><td></td><td></td><td></td><td></td></tr>';
    echo '<tr><th>Product Name</th><th>Category</th><th>Units Sold</th><th>Revenue</th></tr>';
    
    // Product Performance
    foreach ($analytics['product_performance'] as $product) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($product['name']) . '</td>';
        echo '<td>' . ucfirst($product['category']) . '</td>';
        echo '<td>' . number_format($product['total_sold']) . '</td>';
        echo '<td>$' . number_format($product['total_revenue'], 2) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
}

function exportToCSV($analytics, $period) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="analytics_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers and data
    fputcsv($output, ['Analytics Report - Last ' . $period . ' days']);
    fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // Sales Summary
    fputcsv($output, ['Sales Summary']);
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Total Orders', number_format($analytics['sales_summary']['total_orders'])]);
    fputcsv($output, ['Total Revenue', '$' . number_format($analytics['sales_summary']['total_revenue'], 2)]);
    fputcsv($output, ['Average Order Value', '$' . number_format($analytics['sales_summary']['avg_order_value'], 2)]);
    fputcsv($output, []);
    
    // Daily Sales
    fputcsv($output, ['Daily Sales Performance']);
    fputcsv($output, ['Date', 'Orders', 'Revenue', 'Avg Order']);
    foreach ($analytics['daily_sales'] as $day) {
        fputcsv($output, [
            $day['date'],
            number_format($day['orders']),
            '$' . number_format($day['revenue'], 2),
            '$' . number_format($day['avg_order'], 2)
        ]);
    }
    
    fputcsv($output, []);
    
    // Product Performance
    fputcsv($output, ['Product Performance']);
    fputcsv($output, ['Product Name', 'Category', 'Units Sold', 'Revenue', 'Avg Price']);
    foreach ($analytics['product_performance'] as $product) {
        fputcsv($output, [
            $product['name'],
            ucfirst($product['category']),
            number_format($product['total_sold']),
            '$' . number_format($product['total_revenue'], 2),
            '$' . number_format($product['avg_price'], 2)
        ]);
    }
    
    fclose($output);
}
?>
