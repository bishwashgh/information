<?php
require_once '../includes/config.php';
requireLogin();
requireAdmin();

$db = Database::getInstance()->getConnection();

// Get analytics data
$analytics = [
    'overview' => [],
    'sales' => [],
    'products' => [],
    'users' => [],
    'traffic' => []
];

// Overview metrics
$stmt = $db->query("SELECT COUNT(*) as total_orders FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$analytics['overview']['orders_30d'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT SUM(total_amount) as revenue FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND payment_status = 'paid'");
$analytics['overview']['revenue_30d'] = $stmt->fetchColumn() ?: 0;

$stmt = $db->query("SELECT COUNT(*) as new_users FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$analytics['overview']['new_users_30d'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT AVG(total_amount) as avg_order FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND payment_status = 'paid'");
$analytics['overview']['avg_order_value'] = $stmt->fetchColumn() ?: 0;

// Sales analytics by day (last 30 days)
$stmt = $db->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as orders,
        SUM(total_amount) as revenue
    FROM orders 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND payment_status = 'paid'
    GROUP BY DATE(created_at)
    ORDER BY date
");
$analytics['sales']['daily'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top selling products
$stmt = $db->query("
    SELECT 
        p.name,
        p.id,
        SUM(oi.quantity) as total_sold,
        SUM(oi.price * oi.quantity) as total_revenue
    FROM products p
    JOIN order_items oi ON p.id = oi.product_id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND o.payment_status = 'paid'
    GROUP BY p.id, p.name
    ORDER BY total_sold DESC
    LIMIT 10
");
$analytics['products']['top_selling'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// User registration trends
$stmt = $db->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as registrations
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date
");
$analytics['users']['registration_trend'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Order status distribution
$stmt = $db->query("
    SELECT 
        status,
        COUNT(*) as count
    FROM orders 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY status
");
$analytics['overview']['order_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page header
include '../admin/includes/admin_header.php';
?>

<style>
.analytics-dashboard {
    padding: var(--spacing-6);
}

.analytics-header {
    margin-bottom: var(--spacing-8);
}

.analytics-header h1 {
    margin-bottom: var(--spacing-3);
    color: var(--gray-900);
    font-size: var(--font-size-2xl);
}

.analytics-subtitle {
    color: var(--gray-600);
    font-size: var(--font-size-lg);
}

.analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--spacing-6);
    margin-bottom: var(--spacing-8);
}

.analytics-card {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-6);
    box-shadow: var(--shadow);
    border: 1px solid var(--gray-200);
}

.analytics-card h3 {
    font-size: var(--font-size-lg);
    font-weight: 600;
    margin-bottom: var(--spacing-4);
    color: var(--gray-900);
}

.metric-value {
    font-size: var(--font-size-3xl);
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: var(--spacing-2);
}

.metric-label {
    color: var(--gray-600);
    font-size: var(--font-size-sm);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.chart-container {
    position: relative;
    height: 300px;
    margin-top: var(--spacing-4);
}

.chart-small {
    height: 200px;
}

.analytics-filters {
    display: flex;
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-6);
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}

.filter-group label {
    font-weight: 500;
    color: var(--gray-700);
    font-size: var(--font-size-sm);
}

.filter-group select,
.filter-group input {
    padding: var(--spacing-2) var(--spacing-3);
    border: 1px solid var(--gray-300);
    border-radius: var(--border-radius);
    font-size: var(--font-size-sm);
}

.export-buttons {
    display: flex;
    gap: var(--spacing-3);
    margin-bottom: var(--spacing-6);
}

.export-btn {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-3) var(--spacing-4);
    background: var(--white);
    border: 1px solid var(--gray-300);
    border-radius: var(--border-radius);
    color: var(--gray-700);
    text-decoration: none;
    font-size: var(--font-size-sm);
    font-weight: 500;
    transition: var(--transition-fast);
}

.export-btn:hover {
    background: var(--gray-50);
    border-color: var(--gray-400);
}

.export-btn.primary {
    background: var(--primary-color);
    color: var(--white);
    border-color: var(--primary-color);
}

.export-btn.primary:hover {
    background: var(--primary-dark);
}

.products-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: var(--spacing-4);
}

.products-table th,
.products-table td {
    padding: var(--spacing-3);
    text-align: left;
    border-bottom: 1px solid var(--gray-200);
}

.products-table th {
    background: var(--gray-50);
    font-weight: 600;
    color: var(--gray-900);
}

.products-table tr:hover {
    background: var(--gray-50);
}

@media (max-width: 768px) {
    .analytics-dashboard {
        padding: var(--spacing-4);
    }
    
    .analytics-grid {
        grid-template-columns: 1fr;
        gap: var(--spacing-4);
    }
    
    .analytics-filters {
        flex-direction: column;
    }
    
    .export-buttons {
        flex-direction: column;
    }
}
</style>

<div class="analytics-dashboard">
    <div class="analytics-header">
        <h1><i class="fas fa-chart-line"></i> Advanced Analytics Dashboard</h1>
        <p class="analytics-subtitle">Comprehensive insights into your business performance</p>
    </div>

    <!-- Export Options -->
    <div class="export-buttons">
        <a href="export_analytics.php?type=pdf&period=30" class="export-btn primary">
            <i class="fas fa-file-pdf"></i>
            Export PDF Report
        </a>
        <a href="export_analytics.php?type=excel&period=30" class="export-btn">
            <i class="fas fa-file-excel"></i>
            Export Excel
        </a>
        <a href="export_analytics.php?type=csv&period=30" class="export-btn">
            <i class="fas fa-file-csv"></i>
            Export CSV
        </a>
        <button class="export-btn" onclick="scheduleReport()">
            <i class="fas fa-calendar"></i>
            Schedule Report
        </button>
    </div>

    <!-- Analytics Filters -->
    <div class="analytics-filters">
        <div class="filter-group">
            <label for="dateRange">Date Range</label>
            <select id="dateRange" onchange="updateAnalytics()">
                <option value="7">Last 7 days</option>
                <option value="30" selected>Last 30 days</option>
                <option value="90">Last 90 days</option>
                <option value="365">Last year</option>
                <option value="custom">Custom range</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="startDate">Start Date</label>
            <input type="date" id="startDate" style="display: none;">
        </div>
        <div class="filter-group">
            <label for="endDate">End Date</label>
            <input type="date" id="endDate" style="display: none;">
        </div>
        <div class="filter-group">
            <label for="category">Category</label>
            <select id="category" onchange="updateAnalytics()">
                <option value="">All Categories</option>
                <option value="clothing">Clothing</option>
                <option value="cafe">Cafe</option>
            </select>
        </div>
    </div>

    <!-- Overview Metrics -->
    <div class="analytics-grid">
        <div class="analytics-card">
            <h3><i class="fas fa-shopping-cart"></i> Total Orders</h3>
            <div class="metric-value"><?php echo number_format($analytics['overview']['orders_30d']); ?></div>
            <div class="metric-label">Last 30 Days</div>
        </div>

        <div class="analytics-card">
            <h3><i class="fas fa-dollar-sign"></i> Revenue</h3>
            <div class="metric-value">$<?php echo number_format($analytics['overview']['revenue_30d'], 2); ?></div>
            <div class="metric-label">Last 30 Days</div>
        </div>

        <div class="analytics-card">
            <h3><i class="fas fa-users"></i> New Customers</h3>
            <div class="metric-value"><?php echo number_format($analytics['overview']['new_users_30d']); ?></div>
            <div class="metric-label">Last 30 Days</div>
        </div>

        <div class="analytics-card">
            <h3><i class="fas fa-chart-bar"></i> Avg Order Value</h3>
            <div class="metric-value">$<?php echo number_format($analytics['overview']['avg_order_value'], 2); ?></div>
            <div class="metric-label">Last 30 Days</div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="analytics-grid">
        <!-- Revenue Trend Chart -->
        <div class="analytics-card">
            <h3><i class="fas fa-line-chart"></i> Revenue Trend</h3>
            <div class="chart-container">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Order Status Distribution -->
        <div class="analytics-card">
            <h3><i class="fas fa-pie-chart"></i> Order Status</h3>
            <div class="chart-container chart-small">
                <canvas id="orderStatusChart"></canvas>
            </div>
        </div>

        <!-- User Registration Trend -->
        <div class="analytics-card">
            <h3><i class="fas fa-user-plus"></i> User Growth</h3>
            <div class="chart-container">
                <canvas id="userGrowthChart"></canvas>
            </div>
        </div>

        <!-- Top Products -->
        <div class="analytics-card">
            <h3><i class="fas fa-trophy"></i> Top Selling Products</h3>
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($analytics['products']['top_selling'], 0, 5) as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo number_format($product['total_sold']); ?></td>
                        <td>$<?php echo number_format($product['total_revenue'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Initialize charts when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
});

function initializeCharts() {
    // Revenue trend chart
    const revenueData = <?php echo json_encode($analytics['sales']['daily']); ?>;
    const revenueChart = new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: revenueData.map(item => item.date),
            datasets: [{
                label: 'Revenue',
                data: revenueData.map(item => parseFloat(item.revenue)),
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toFixed(2);
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Order status chart
    const orderStatusData = <?php echo json_encode($analytics['overview']['order_status']); ?>;
    const orderStatusChart = new Chart(document.getElementById('orderStatusChart'), {
        type: 'doughnut',
        data: {
            labels: orderStatusData.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1)),
            datasets: [{
                data: orderStatusData.map(item => item.count),
                backgroundColor: [
                    '#3b82f6',
                    '#10b981',
                    '#f59e0b',
                    '#ef4444',
                    '#8b5cf6'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // User growth chart
    const userGrowthData = <?php echo json_encode($analytics['users']['registration_trend']); ?>;
    const userGrowthChart = new Chart(document.getElementById('userGrowthChart'), {
        type: 'bar',
        data: {
            labels: userGrowthData.map(item => item.date),
            datasets: [{
                label: 'New Users',
                data: userGrowthData.map(item => item.registrations),
                backgroundColor: '#10b981',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

function updateAnalytics() {
    const dateRange = document.getElementById('dateRange').value;
    const category = document.getElementById('category').value;
    
    if (dateRange === 'custom') {
        document.getElementById('startDate').style.display = 'block';
        document.getElementById('endDate').style.display = 'block';
    } else {
        document.getElementById('startDate').style.display = 'none';
        document.getElementById('endDate').style.display = 'none';
    }
    
    // Reload page with new filters
    const params = new URLSearchParams();
    if (dateRange !== '30') params.set('period', dateRange);
    if (category) params.set('category', category);
    
    const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    window.location.href = newUrl;
}

function scheduleReport() {
    // Open modal for scheduling reports
    alert('Report scheduling feature coming soon!');
}
</script>

<?php include '../admin/includes/admin_footer.php'; ?>
