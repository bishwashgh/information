<?php
require_once '../includes/config.php';
requireLogin();
requireAdmin();

include '../admin/includes/admin_header.php';

$db = Database::getInstance()->getConnection();

// Get SEO data
$seoData = [
    'pages_analyzed' => 0,
    'avg_score' => 0,
    'total_issues' => 0,
    'pages' => []
];

// Analyze key pages
$pagesToAnalyze = [
    ['url' => SITE_URL, 'name' => 'Homepage'],
    ['url' => SITE_URL . '/products.php', 'name' => 'Products'],
    ['url' => SITE_URL . '/products.php?category=clothing', 'name' => 'Clothing Category'],
    ['url' => SITE_URL . '/products.php?category=cafe', 'name' => 'Cafe Category'],
];

// Add product pages
$stmt = $db->query("SELECT id, name FROM products WHERE status = 'active' LIMIT 5");
while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $pagesToAnalyze[] = [
        'url' => SITE_URL . '/product.php?id=' . $product['id'],
        'name' => 'Product: ' . $product['name']
    ];
}

// Analyze each page (in a real implementation, you'd fetch the actual content)
foreach ($pagesToAnalyze as $page) {
    // Simulate SEO analysis (in real implementation, you'd fetch and analyze actual content)
    $mockContent = '<title>' . $page['name'] . ' - E-Commerce Store</title>
                   <meta name="description" content="' . $page['name'] . ' page description that is perfectly optimized for search engines and users.">
                   <h1>' . $page['name'] . '</h1>
                   <img src="image.jpg" alt="Product image">';
    
    require_once '../includes/seo.php';
    $analysis = getSEOAnalysis($page['url'], $mockContent);
    
    $seoData['pages'][] = [
        'name' => $page['name'],
        'url' => $page['url'],
        'score' => $analysis['score'],
        'issues' => $analysis['issues'],
        'recommendations' => $analysis['recommendations']
    ];
    
    $seoData['pages_analyzed']++;
    $seoData['avg_score'] += $analysis['score'];
    $seoData['total_issues'] += count($analysis['issues']);
}

if ($seoData['pages_analyzed'] > 0) {
    $seoData['avg_score'] = round($seoData['avg_score'] / $seoData['pages_analyzed']);
}
?>

<style>
.seo-dashboard {
    padding: var(--spacing-6);
}

.seo-header {
    margin-bottom: var(--spacing-8);
}

.seo-header h1 {
    margin-bottom: var(--spacing-3);
    color: var(--gray-900);
    font-size: var(--font-size-2xl);
}

.seo-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-6);
    margin-bottom: var(--spacing-8);
}

.seo-metric {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-6);
    text-align: center;
    box-shadow: var(--shadow);
    border: 1px solid var(--gray-200);
}

.seo-metric h3 {
    font-size: var(--font-size-sm);
    color: var(--gray-600);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: var(--spacing-3);
}

.seo-metric .value {
    font-size: var(--font-size-3xl);
    font-weight: 700;
    margin-bottom: var(--spacing-2);
}

.seo-metric .value.excellent {
    color: var(--success-color);
}

.seo-metric .value.good {
    color: var(--primary-color);
}

.seo-metric .value.warning {
    color: var(--warning-color);
}

.seo-metric .value.poor {
    color: var(--danger-color);
}

.seo-tools {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--spacing-6);
    margin-bottom: var(--spacing-8);
}

.seo-tool {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-6);
    box-shadow: var(--shadow);
    border: 1px solid var(--gray-200);
}

.seo-tool h3 {
    margin-bottom: var(--spacing-4);
    color: var(--gray-900);
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
}

.tool-button {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    padding: var(--spacing-3) var(--spacing-4);
    background: var(--primary-color);
    color: var(--white);
    border: none;
    border-radius: var(--border-radius);
    text-decoration: none;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition-fast);
}

.tool-button:hover {
    background: var(--primary-dark);
}

.tool-button.secondary {
    background: var(--white);
    color: var(--primary-color);
    border: 1px solid var(--primary-color);
}

.tool-button.secondary:hover {
    background: var(--primary-50);
}

.pages-analysis {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-6);
    box-shadow: var(--shadow);
    border: 1px solid var(--gray-200);
}

.pages-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: var(--spacing-4);
}

.pages-table th,
.pages-table td {
    padding: var(--spacing-3);
    text-align: left;
    border-bottom: 1px solid var(--gray-200);
}

.pages-table th {
    background: var(--gray-50);
    font-weight: 600;
    color: var(--gray-900);
}

.score-badge {
    display: inline-block;
    padding: var(--spacing-1) var(--spacing-3);
    border-radius: var(--border-radius-full);
    font-size: var(--font-size-sm);
    font-weight: 600;
    text-align: center;
    min-width: 60px;
}

.score-excellent {
    background: var(--success-100);
    color: var(--success-700);
}

.score-good {
    background: var(--primary-100);
    color: var(--primary-700);
}

.score-warning {
    background: var(--warning-100);
    color: var(--warning-700);
}

.score-poor {
    background: var(--danger-100);
    color: var(--danger-700);
}

.issues-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.issues-list li {
    padding: var(--spacing-1) 0;
    color: var(--danger-600);
    font-size: var(--font-size-sm);
}

.issues-list li:before {
    content: "⚠️ ";
    margin-right: var(--spacing-1);
}

.recommendations-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.recommendations-list li {
    padding: var(--spacing-1) 0;
    color: var(--primary-600);
    font-size: var(--font-size-sm);
}

.recommendations-list li:before {
    content: "💡 ";
    margin-right: var(--spacing-1);
}

.expandable-content {
    display: none;
}

.expandable-content.show {
    display: block;
}

.expand-button {
    background: none;
    border: none;
    color: var(--primary-color);
    cursor: pointer;
    font-size: var(--font-size-sm);
    text-decoration: underline;
}

@media (max-width: 768px) {
    .seo-dashboard {
        padding: var(--spacing-4);
    }
    
    .seo-overview,
    .seo-tools {
        grid-template-columns: 1fr;
    }
    
    .pages-table {
        font-size: var(--font-size-sm);
    }
}
</style>

<div class="seo-dashboard">
    <div class="seo-header">
        <h1><i class="fas fa-search"></i> SEO Optimization Dashboard</h1>
        <p>Monitor and improve your website's search engine optimization</p>
    </div>

    <!-- SEO Overview -->
    <div class="seo-overview">
        <div class="seo-metric">
            <h3>Average SEO Score</h3>
            <div class="value <?php 
                echo $seoData['avg_score'] >= 80 ? 'excellent' : 
                    ($seoData['avg_score'] >= 60 ? 'good' : 
                    ($seoData['avg_score'] >= 40 ? 'warning' : 'poor')); 
            ?>">
                <?php echo $seoData['avg_score']; ?>%
            </div>
        </div>

        <div class="seo-metric">
            <h3>Pages Analyzed</h3>
            <div class="value good"><?php echo $seoData['pages_analyzed']; ?></div>
        </div>

        <div class="seo-metric">
            <h3>Total Issues</h3>
            <div class="value <?php echo $seoData['total_issues'] > 10 ? 'poor' : ($seoData['total_issues'] > 5 ? 'warning' : 'good'); ?>">
                <?php echo $seoData['total_issues']; ?>
            </div>
        </div>

        <div class="seo-metric">
            <h3>SEO Status</h3>
            <div class="value <?php echo $seoData['avg_score'] >= 70 ? 'excellent' : 'warning'; ?>">
                <?php echo $seoData['avg_score'] >= 70 ? 'Good' : 'Needs Work'; ?>
            </div>
        </div>
    </div>

    <!-- SEO Tools -->
    <div class="seo-tools">
        <div class="seo-tool">
            <h3><i class="fas fa-sitemap"></i> XML Sitemap</h3>
            <p>Generate and manage your website's XML sitemap for search engines.</p>
            <a href="sitemap.php" class="tool-button" target="_blank">
                <i class="fas fa-external-link-alt"></i>
                View Sitemap
            </a>
            <button class="tool-button secondary" onclick="generateSitemap()">
                <i class="fas fa-sync-alt"></i>
                Regenerate
            </button>
        </div>

        <div class="seo-tool">
            <h3><i class="fas fa-robot"></i> Robots.txt</h3>
            <p>Configure search engine crawling permissions for your website.</p>
            <a href="robots.php" class="tool-button" target="_blank">
                <i class="fas fa-external-link-alt"></i>
                View Robots.txt
            </a>
            <button class="tool-button secondary" onclick="editRobots()">
                <i class="fas fa-edit"></i>
                Edit
            </button>
        </div>

        <div class="seo-tool">
            <h3><i class="fas fa-chart-line"></i> Google Analytics</h3>
            <p>Track website performance and user behavior with Google Analytics.</p>
            <button class="tool-button" onclick="setupAnalytics()">
                <i class="fas fa-cog"></i>
                Setup Analytics
            </button>
        </div>

        <div class="seo-tool">
            <h3><i class="fas fa-search-plus"></i> Google Search Console</h3>
            <p>Monitor search performance and submit sitemaps to Google.</p>
            <button class="tool-button" onclick="setupSearchConsole()">
                <i class="fas fa-external-link-alt"></i>
                Open Console
            </button>
        </div>
    </div>

    <!-- Pages Analysis -->
    <div class="pages-analysis">
        <h2><i class="fas fa-file-alt"></i> Page-by-Page SEO Analysis</h2>
        <table class="pages-table">
            <thead>
                <tr>
                    <th>Page</th>
                    <th>SEO Score</th>
                    <th>Issues</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($seoData['pages'] as $index => $page): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($page['name']); ?></strong>
                        <br>
                        <small style="color: var(--gray-500);"><?php echo htmlspecialchars($page['url']); ?></small>
                    </td>
                    <td>
                        <span class="score-badge score-<?php 
                            echo $page['score'] >= 80 ? 'excellent' : 
                                ($page['score'] >= 60 ? 'good' : 
                                ($page['score'] >= 40 ? 'warning' : 'poor')); 
                        ?>">
                            <?php echo $page['score']; ?>%
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($page['issues'])): ?>
                            <button class="expand-button" onclick="toggleContent('issues-<?php echo $index; ?>')">
                                <?php echo count($page['issues']); ?> issues
                            </button>
                            <div id="issues-<?php echo $index; ?>" class="expandable-content">
                                <ul class="issues-list">
                                    <?php foreach ($page['issues'] as $issue): ?>
                                    <li><?php echo htmlspecialchars($issue); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php else: ?>
                            <span style="color: var(--success-600);">No issues</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="tool-button secondary" onclick="analyzePage('<?php echo htmlspecialchars($page['url']); ?>')">
                            <i class="fas fa-search"></i>
                            Re-analyze
                        </button>
                    </td>
                </tr>
                <?php if (!empty($page['recommendations'])): ?>
                <tr>
                    <td colspan="4">
                        <button class="expand-button" onclick="toggleContent('recommendations-<?php echo $index; ?>')">
                            View <?php echo count($page['recommendations']); ?> recommendations
                        </button>
                        <div id="recommendations-<?php echo $index; ?>" class="expandable-content">
                            <ul class="recommendations-list">
                                <?php foreach ($page['recommendations'] as $recommendation): ?>
                                <li><?php echo htmlspecialchars($recommendation); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleContent(id) {
    const content = document.getElementById(id);
    content.classList.toggle('show');
}

function generateSitemap() {
    // Open sitemap generation in new tab
    window.open('sitemap.php?generate=1', '_blank');
    showToast('Sitemap generation started', 'info');
}

function editRobots() {
    // In a real implementation, this would open a modal to edit robots.txt
    alert('Robots.txt editor would open here');
}

function setupAnalytics() {
    // In a real implementation, this would guide through Analytics setup
    alert('Google Analytics setup wizard would start here');
}

function setupSearchConsole() {
    // Open Google Search Console
    window.open('https://search.google.com/search-console', '_blank');
}

function analyzePage(url) {
    showToast('Re-analyzing page: ' + url, 'info');
    // In a real implementation, this would trigger a new analysis
    setTimeout(() => {
        showToast('Page analysis complete', 'success');
    }, 2000);
}

// Show toast function (assuming it exists in main.js)
function showToast(message, type) {
    // Simple toast implementation
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
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
</script>

<?php include '../admin/includes/admin_footer.php'; ?>
