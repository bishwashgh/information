<?php
require_once 'includes/seo.php';

// Set content type to XML
header('Content-Type: application/xml; charset=utf-8');

// Generate sitemap if requested
if (isset($_GET['generate'])) {
    generateSitemap();
    exit;
}

// Otherwise, show current sitemap
generateSitemap();
?>
