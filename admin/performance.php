<?php
require_once '../includes/config.php';
requireLogin();
requireAdmin();

$db = Database::getInstance()->getConnection();

// Get performance metrics
$performance = [
    'database' => [],
    'cache' => [],
    'system' => []
];

// Database performance metrics
$stmt = $db->query("SHOW STATUS LIKE 'Queries'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$performance['database']['total_queries'] = $result['Value'] ?? 0;

$stmt = $db->query("SHOW STATUS LIKE 'Questions'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$performance['database']['questions'] = $result['Value'] ?? 0;

$stmt = $db->query("SHOW STATUS LIKE 'Uptime'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$performance['database']['uptime'] = $result['Value'] ?? 0;

$stmt = $db->query("SHOW STATUS LIKE 'Connections'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$performance['database']['connections'] = $result['Value'] ?? 0;

// Slow query analysis
$stmt = $db->query("
    SELECT 
        table_name,
        table_rows,
        data_length,
        index_length,
        (data_length + index_length) as total_size
    FROM information_schema.tables 
    WHERE table_schema = DATABASE()
    ORDER BY total_size DESC
");
$performance['database']['table_sizes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// System performance
$performance['system']['php_version'] = PHP_VERSION;
$performance['system']['memory_limit'] = ini_get('memory_limit');
$performance['system']['max_execution_time'] = ini_get('max_execution_time');
$performance['system']['upload_max_filesize'] = ini_get('upload_max_filesize');

// Check if opcache is enabled
$performance['system']['opcache_enabled'] = function_exists('opcache_get_status') ? 'Yes' : 'No';

// Get memory usage
$performance['system']['memory_usage'] = memory_get_usage(true);
$performance['system']['peak_memory'] = memory_get_peak_usage(true);

// Page load times (last 24 hours)
$stmt = $db->query("
    SELECT 
        page_url,
        AVG(load_time) as avg_load_time,
        MAX(load_time) as max_load_time,
        MIN(load_time) as min_load_time,
        COUNT(*) as page_views
    FROM page_analytics 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY page_url
    ORDER BY avg_load_time DESC
    LIMIT 10
");
$performance['pages'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../admin/includes/admin_header.php';
?>

<style>
.performance-dashboard {
    padding: var(--spacing-6);
}

.performance-header {
    margin-bottom: var(--spacing-8);
}

.performance-header h1 {
    margin-bottom: var(--spacing-3);
    color: var(--gray-900);
    font-size: var(--font-size-2xl);
}

.performance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: var(--spacing-6);
    margin-bottom: var(--spacing-8);
}

.performance-card {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-6);
    box-shadow: var(--shadow);
    border: 1px solid var(--gray-200);
}

.performance-card h3 {
    font-size: var(--font-size-lg);
    font-weight: 600;
    margin-bottom: var(--spacing-4);
    color: var(--gray-900);
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
}

.metric-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-3) 0;
    border-bottom: 1px solid var(--gray-100);
}

.metric-row:last-child {
    border-bottom: none;
}

.metric-label {
    color: var(--gray-600);
    font-weight: 500;
}

.metric-value {
    font-weight: 600;
    color: var(--gray-900);
}

.metric-value.good {
    color: var(--success-color);
}

.metric-value.warning {
    color: var(--warning-color);
}

.metric-value.danger {
    color: var(--danger-color);
}

.performance-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: var(--spacing-4);
}

.performance-table th,
.performance-table td {
    padding: var(--spacing-3);
    text-align: left;
    border-bottom: 1px solid var(--gray-200);
}

.performance-table th {
    background: var(--gray-50);
    font-weight: 600;
    color: var(--gray-900);
}

.optimization-suggestions {
    background: var(--primary-50);
    border: 1px solid var(--primary-200);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-6);
    margin-top: var(--spacing-6);
}

.optimization-suggestions h3 {
    color: var(--primary-700);
    margin-bottom: var(--spacing-4);
}

.suggestion-list {
    list-style: none;
    padding: 0;
}

.suggestion-list li {
    padding: var(--spacing-2) 0;
    color: var(--primary-600);
}

.suggestion-list li:before {
    content: "💡 ";
    margin-right: var(--spacing-2);
}

.refresh-btn {
    background: var(--primary-color);
    color: var(--white);
    border: none;
    padding: var(--spacing-3) var(--spacing-4);
    border-radius: var(--border-radius);
    font-weight: 500;
    cursor: pointer;
    margin-bottom: var(--spacing-6);
}

.refresh-btn:hover {
    background: var(--primary-dark);
}

@media (max-width: 768px) {
    .performance-dashboard {
        padding: var(--spacing-4);
    }
    
    .performance-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="performance-dashboard">
    <div class="performance-header">
        <h1><i class="fas fa-tachometer-alt"></i> Performance Monitoring</h1>
        <p>Real-time system performance metrics and optimization insights</p>
        <button class="refresh-btn" onclick="location.reload()">
            <i class="fas fa-sync-alt"></i> Refresh Metrics
        </button>
    </div>

    <div class="performance-grid">
        <!-- Database Performance -->
        <div class="performance-card">
            <h3><i class="fas fa-database"></i> Database Performance</h3>
            <div class="metric-row">
                <span class="metric-label">Total Queries</span>
                <span class="metric-value"><?php echo number_format($performance['database']['total_queries']); ?></span>
            </div>
            <div class="metric-row">
                <span class="metric-label">Questions</span>
                <span class="metric-value"><?php echo number_format($performance['database']['questions']); ?></span>
            </div>
            <div class="metric-row">
                <span class="metric-label">Uptime</span>
                <span class="metric-value"><?php echo gmdate('H:i:s', $performance['database']['uptime']); ?></span>
            </div>
            <div class="metric-row">
                <span class="metric-label">Connections</span>
                <span class="metric-value"><?php echo number_format($performance['database']['connections']); ?></span>
            </div>
        </div>

        <!-- System Performance -->
        <div class="performance-card">
            <h3><i class="fas fa-server"></i> System Information</h3>
            <div class="metric-row">
                <span class="metric-label">PHP Version</span>
                <span class="metric-value good"><?php echo $performance['system']['php_version']; ?></span>
            </div>
            <div class="metric-row">
                <span class="metric-label">Memory Limit</span>
                <span class="metric-value"><?php echo $performance['system']['memory_limit']; ?></span>
            </div>
            <div class="metric-row">
                <span class="metric-label">Current Memory Usage</span>
                <span class="metric-value"><?php echo round($performance['system']['memory_usage'] / 1024 / 1024, 2); ?> MB</span>
            </div>
            <div class="metric-row">
                <span class="metric-label">Peak Memory</span>
                <span class="metric-value"><?php echo round($performance['system']['peak_memory'] / 1024 / 1024, 2); ?> MB</span>
            </div>
            <div class="metric-row">
                <span class="metric-label">OPcache Enabled</span>
                <span class="metric-value <?php echo $performance['system']['opcache_enabled'] === 'Yes' ? 'good' : 'warning'; ?>">
                    <?php echo $performance['system']['opcache_enabled']; ?>
                </span>
            </div>
        </div>

        <!-- Page Performance -->
        <div class="performance-card">
            <h3><i class="fas fa-stopwatch"></i> Page Load Times (24h)</h3>
            <?php if (!empty($performance['pages'])): ?>
            <table class="performance-table">
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>Avg Load</th>
                        <th>Views</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($performance['pages'], 0, 5) as $page): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($page['page_url']); ?></td>
                        <td>
                            <span class="metric-value <?php echo $page['avg_load_time'] > 2 ? 'danger' : ($page['avg_load_time'] > 1 ? 'warning' : 'good'); ?>">
                                <?php echo number_format($page['avg_load_time'], 3); ?>s
                            </span>
                        </td>
                        <td><?php echo number_format($page['page_views']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="metric-value warning">No page analytics data available</p>
            <p><small>Page analytics will be tracked automatically once implemented.</small></p>
            <?php endif; ?>
        </div>

        <!-- Database Table Sizes -->
        <div class="performance-card">
            <h3><i class="fas fa-chart-pie"></i> Database Storage</h3>
            <table class="performance-table">
                <thead>
                    <tr>
                        <th>Table</th>
                        <th>Rows</th>
                        <th>Size</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($performance['database']['table_sizes'], 0, 8) as $table): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($table['table_name']); ?></td>
                        <td><?php echo number_format($table['table_rows'] ?: 0); ?></td>
                        <td><?php echo formatBytes($table['total_size'] ?: 0); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Optimization Suggestions -->
    <div class="optimization-suggestions">
        <h3><i class="fas fa-lightbulb"></i> Performance Optimization Suggestions</h3>
        <ul class="suggestion-list">
            <?php if ($performance['system']['opcache_enabled'] === 'No'): ?>
            <li>Enable OPcache to improve PHP performance by caching compiled scripts</li>
            <?php endif; ?>
            
            <?php if ($performance['system']['memory_usage'] / 1024 / 1024 > 64): ?>
            <li>Consider optimizing memory usage - current usage is <?php echo round($performance['system']['memory_usage'] / 1024 / 1024, 2); ?> MB</li>
            <?php endif; ?>
            
            <li>Implement Redis or Memcached for session storage and database query caching</li>
            <li>Add image compression and lazy loading for better page load times</li>
            <li>Consider implementing a Content Delivery Network (CDN) for static assets</li>
            <li>Enable GZIP compression on your web server</li>
            <li>Implement database query optimization and indexing</li>
            <li>Add browser caching headers for static resources</li>
        </ul>
    </div>
</div>

<script>
// Auto-refresh performance data every 30 seconds
setInterval(function() {
    // Only refresh if user is actively viewing the page
    if (document.visibilityState === 'visible') {
        const refreshTime = document.createElement('div');
        refreshTime.style.position = 'fixed';
        refreshTime.style.top = '20px';
        refreshTime.style.right = '20px';
        refreshTime.style.background = '#10b981';
        refreshTime.style.color = 'white';
        refreshTime.style.padding = '10px';
        refreshTime.style.borderRadius = '4px';
        refreshTime.style.zIndex = '9999';
        refreshTime.textContent = 'Performance data updated';
        
        document.body.appendChild(refreshTime);
        
        setTimeout(() => {
            refreshTime.remove();
        }, 3000);
        
        // In a real implementation, you would fetch updated data via AJAX
        // location.reload();
    }
}, 30000);
</script>

<?php
// Helper function to format bytes
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

include '../admin/includes/admin_footer.php';
?>
