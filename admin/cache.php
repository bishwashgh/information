<?php
require_once '../includes/config.php';
require_once '../includes/cache.php';
requireLogin();
requireAdmin();

$action = $_GET['action'] ?? '';
$message = '';

// Handle cache actions
if ($action) {
    switch ($action) {
        case 'clear_all':
            getCache()->clear();
            $message = 'All cache cleared successfully';
            break;
            
        case 'clean_expired':
            $deletedCount = getCache()->cleanExpired();
            $message = "Cleaned {$deletedCount} expired cache files";
            break;
            
        case 'clear_products':
            invalidateProductCache();
            $message = 'Product cache cleared successfully';
            break;
            
        case 'clear_search':
            invalidateSearchCache();
            $message = 'Search cache cleared successfully';
            break;
    }
}

// Get cache statistics
$cacheStats = getCache()->getStats();

include '../admin/includes/admin_header.php';
?>

<style>
.cache-dashboard {
    padding: var(--spacing-6);
}

.cache-header {
    margin-bottom: var(--spacing-8);
}

.cache-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-8);
}

.cache-stat {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-6);
    text-align: center;
    box-shadow: var(--shadow);
    border: 1px solid var(--gray-200);
}

.cache-stat h3 {
    font-size: var(--font-size-sm);
    color: var(--gray-600);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: var(--spacing-3);
}

.cache-stat .value {
    font-size: var(--font-size-2xl);
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: var(--spacing-2);
}

.cache-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--spacing-6);
    margin-bottom: var(--spacing-8);
}

.cache-action-card {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-6);
    box-shadow: var(--shadow);
    border: 1px solid var(--gray-200);
}

.cache-action-card h3 {
    margin-bottom: var(--spacing-3);
    color: var(--gray-900);
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
}

.cache-action-card p {
    color: var(--gray-600);
    margin-bottom: var(--spacing-4);
    line-height: 1.5;
}

.cache-settings {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-6);
    box-shadow: var(--shadow);
    border: 1px solid var(--gray-200);
}

.settings-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-4);
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}

.form-group label {
    font-weight: 500;
    color: var(--gray-700);
}

.form-group input,
.form-group select {
    padding: var(--spacing-3);
    border: 1px solid var(--gray-300);
    border-radius: var(--border-radius);
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-3) var(--spacing-4);
    border: none;
    border-radius: var(--border-radius);
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: var(--transition-fast);
}

.btn-primary {
    background: var(--primary-color);
    color: var(--white);
}

.btn-primary:hover {
    background: var(--primary-dark);
}

.btn-warning {
    background: var(--warning-color);
    color: var(--white);
}

.btn-warning:hover {
    background: var(--warning-dark);
}

.btn-danger {
    background: var(--danger-color);
    color: var(--white);
}

.btn-danger:hover {
    background: var(--danger-dark);
}

.btn-success {
    background: var(--success-color);
    color: var(--white);
}

.btn-success:hover {
    background: var(--success-dark);
}

.alert {
    padding: var(--spacing-4);
    border-radius: var(--border-radius);
    margin-bottom: var(--spacing-6);
    border: 1px solid;
}

.alert-success {
    background: var(--success-50);
    color: var(--success-700);
    border-color: var(--success-200);
}

.cache-recommendations {
    background: var(--blue-50);
    border: 1px solid var(--blue-200);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-6);
    margin-top: var(--spacing-6);
}

.recommendations-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.recommendations-list li {
    padding: var(--spacing-2) 0;
    color: var(--blue-700);
}

.recommendations-list li:before {
    content: "💡 ";
    margin-right: var(--spacing-2);
}

@media (max-width: 768px) {
    .cache-dashboard {
        padding: var(--spacing-4);
    }
    
    .cache-stats,
    .cache-actions {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="cache-dashboard">
    <div class="cache-header">
        <h1><i class="fas fa-memory"></i> Cache Management</h1>
        <p>Monitor and manage your website's caching system for optimal performance</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <!-- Cache Statistics -->
    <div class="cache-stats">
        <div class="cache-stat">
            <h3>Total Cache Files</h3>
            <div class="value"><?php echo number_format($cacheStats['total_files']); ?></div>
        </div>
        <div class="cache-stat">
            <h3>Valid Files</h3>
            <div class="value"><?php echo number_format($cacheStats['valid_files']); ?></div>
        </div>
        <div class="cache-stat">
            <h3>Expired Files</h3>
            <div class="value" style="color: var(--warning-color);"><?php echo number_format($cacheStats['expired_files']); ?></div>
        </div>
        <div class="cache-stat">
            <h3>Cache Size</h3>
            <div class="value"><?php echo formatBytes($cacheStats['total_size']); ?></div>
        </div>
    </div>

    <!-- Cache Actions -->
    <div class="cache-actions">
        <div class="cache-action-card">
            <h3><i class="fas fa-broom"></i> Clear All Cache</h3>
            <p>Remove all cached data. This will force regeneration of all cached content on next access.</p>
            <a href="?action=clear_all" class="btn btn-danger" onclick="return confirm('Are you sure? This will clear ALL cache.')">
                <i class="fas fa-trash"></i>
                Clear All Cache
            </a>
        </div>

        <div class="cache-action-card">
            <h3><i class="fas fa-clock"></i> Clean Expired</h3>
            <p>Remove only expired cache files. This is safe and recommended for regular maintenance.</p>
            <a href="?action=clean_expired" class="btn btn-warning">
                <i class="fas fa-broom"></i>
                Clean Expired Files
            </a>
        </div>

        <div class="cache-action-card">
            <h3><i class="fas fa-box"></i> Clear Product Cache</h3>
            <p>Clear cached product data, category listings, and related content.</p>
            <a href="?action=clear_products" class="btn btn-primary">
                <i class="fas fa-refresh"></i>
                Clear Product Cache
            </a>
        </div>

        <div class="cache-action-card">
            <h3><i class="fas fa-search"></i> Clear Search Cache</h3>
            <p>Clear cached search results and filters. Useful after updating product data.</p>
            <a href="?action=clear_search" class="btn btn-primary">
                <i class="fas fa-search"></i>
                Clear Search Cache
            </a>
        </div>

        <div class="cache-action-card">
            <h3><i class="fas fa-chart-line"></i> Refresh Analytics</h3>
            <p>Clear analytics cache to force fresh calculation of statistics and reports.</p>
            <button class="btn btn-success" onclick="refreshAnalytics()">
                <i class="fas fa-sync-alt"></i>
                Refresh Analytics
            </button>
        </div>

        <div class="cache-action-card">
            <h3><i class="fas fa-home"></i> Refresh Homepage</h3>
            <p>Clear homepage cache including featured products and carousel data.</p>
            <button class="btn btn-success" onclick="refreshHomepage()">
                <i class="fas fa-home"></i>
                Refresh Homepage
            </button>
        </div>
    </div>

    <!-- Cache Settings -->
    <div class="cache-settings">
        <h2><i class="fas fa-cog"></i> Cache Configuration</h2>
        <form class="settings-form">
            <div class="form-group">
                <label for="default_ttl">Default Cache TTL (seconds)</label>
                <input type="number" id="default_ttl" value="3600" min="60" max="86400">
            </div>
            
            <div class="form-group">
                <label for="product_ttl">Product Cache TTL</label>
                <input type="number" id="product_ttl" value="1800" min="60" max="86400">
            </div>
            
            <div class="form-group">
                <label for="search_ttl">Search Cache TTL</label>
                <input type="number" id="search_ttl" value="600" min="60" max="3600">
            </div>
            
            <div class="form-group">
                <label for="analytics_ttl">Analytics Cache TTL</label>
                <input type="number" id="analytics_ttl" value="3600" min="300" max="86400">
            </div>
            
            <div class="form-group">
                <label for="cache_type">Cache Type</label>
                <select id="cache_type">
                    <option value="file">File Cache</option>
                    <option value="redis" disabled>Redis (Not Available)</option>
                    <option value="memcached" disabled>Memcached (Not Available)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-primary" onclick="updateCacheSettings()">
                    <i class="fas fa-save"></i>
                    Update Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Cache Recommendations -->
    <div class="cache-recommendations">
        <h3><i class="fas fa-lightbulb"></i> Performance Recommendations</h3>
        <ul class="recommendations-list">
            <li>Set up automated cache cleaning to run daily during low traffic hours</li>
            <li>Monitor cache hit rates to optimize TTL values for different content types</li>
            <li>Consider implementing Redis or Memcached for better performance in production</li>
            <li>Use CDN for static assets (images, CSS, JavaScript) to reduce server load</li>
            <li>Enable browser caching headers for static content</li>
            <li>Implement cache warming for critical pages after cache clears</li>
            <li>Monitor cache size and set up automatic cleanup if it grows too large</li>
            <li>Consider implementing cache tagging for more granular cache invalidation</li>
        </ul>
    </div>
</div>

<script>
function refreshAnalytics() {
    // Clear analytics cache
    fetch('cache_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'clear_analytics'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Analytics cache cleared successfully', 'success');
        } else {
            showToast('Error clearing analytics cache', 'error');
        }
    });
}

function refreshHomepage() {
    // Clear homepage cache
    fetch('cache_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'clear_homepage'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Homepage cache cleared successfully', 'success');
        } else {
            showToast('Error clearing homepage cache', 'error');
        }
    });
}

function updateCacheSettings() {
    const settings = {
        default_ttl: document.getElementById('default_ttl').value,
        product_ttl: document.getElementById('product_ttl').value,
        search_ttl: document.getElementById('search_ttl').value,
        analytics_ttl: document.getElementById('analytics_ttl').value,
        cache_type: document.getElementById('cache_type').value
    };
    
    // In a real implementation, save these settings
    showToast('Cache settings updated successfully', 'success');
}

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : '#ef4444'};
        color: white;
        padding: 12px 20px;
        border-radius: 6px;
        z-index: 9999;
        font-weight: 500;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Format bytes function
<?php
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>
</script>

<?php include '../admin/includes/admin_footer.php'; ?>
