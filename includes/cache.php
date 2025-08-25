<?php
/**
 * Simple File-based Caching System
 * This can be extended to support Redis or Memcached
 */

class CacheManager {
    private $cacheDir;
    private $defaultTTL;
    
    public function __construct($cacheDir = null, $defaultTTL = 3600) {
        $this->cacheDir = $cacheDir ?: __DIR__ . '/../cache/';
        $this->defaultTTL = $defaultTTL;
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Get cached data
     */
    public function get($key) {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $data = file_get_contents($filename);
        $cache = json_decode($data, true);
        
        if (!$cache || !isset($cache['expires']) || $cache['expires'] < time()) {
            $this->delete($key);
            return null;
        }
        
        return $cache['data'];
    }
    
    /**
     * Store data in cache
     */
    public function set($key, $data, $ttl = null) {
        $ttl = $ttl ?: $this->defaultTTL;
        $filename = $this->getCacheFilename($key);
        
        $cache = [
            'data' => $data,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        return file_put_contents($filename, json_encode($cache)) !== false;
    }
    
    /**
     * Delete cached data
     */
    public function delete($key) {
        $filename = $this->getCacheFilename($key);
        
        if (file_exists($filename)) {
            return unlink($filename);
        }
        
        return true;
    }
    
    /**
     * Clear all cache
     */
    public function clear() {
        $files = glob($this->cacheDir . '*.cache');
        
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
    
    /**
     * Get cache statistics
     */
    public function getStats() {
        $files = glob($this->cacheDir . '*.cache');
        $totalSize = 0;
        $validFiles = 0;
        $expiredFiles = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            
            $data = file_get_contents($file);
            $cache = json_decode($data, true);
            
            if ($cache && isset($cache['expires'])) {
                if ($cache['expires'] >= time()) {
                    $validFiles++;
                } else {
                    $expiredFiles++;
                }
            }
        }
        
        return [
            'total_files' => count($files),
            'valid_files' => $validFiles,
            'expired_files' => $expiredFiles,
            'total_size' => $totalSize,
            'cache_dir' => $this->cacheDir
        ];
    }
    
    /**
     * Clean expired cache files
     */
    public function cleanExpired() {
        $files = glob($this->cacheDir . '*.cache');
        $deletedCount = 0;
        
        foreach ($files as $file) {
            $data = file_get_contents($file);
            $cache = json_decode($data, true);
            
            if (!$cache || !isset($cache['expires']) || $cache['expires'] < time()) {
                unlink($file);
                $deletedCount++;
            }
        }
        
        return $deletedCount;
    }
    
    /**
     * Get cache filename for a key
     */
    private function getCacheFilename($key) {
        return $this->cacheDir . md5($key) . '.cache';
    }
    
    /**
     * Cache a database query result
     */
    public function query($key, $callback, $ttl = null) {
        $data = $this->get($key);
        
        if ($data === null) {
            $data = $callback();
            $this->set($key, $data, $ttl);
        }
        
        return $data;
    }
}

/**
 * Global cache instance
 */
function getCache() {
    static $cache = null;
    
    if ($cache === null) {
        $cache = new CacheManager();
    }
    
    return $cache;
}

/**
 * Cache product data
 */
function cacheProduct($productId, $productData, $ttl = 1800) {
    return getCache()->set("product_{$productId}", $productData, $ttl);
}

/**
 * Get cached product data
 */
function getCachedProduct($productId) {
    return getCache()->get("product_{$productId}");
}

/**
 * Cache category products
 */
function cacheCategoryProducts($category, $products, $ttl = 900) {
    return getCache()->set("category_products_{$category}", $products, $ttl);
}

/**
 * Get cached category products
 */
function getCachedCategoryProducts($category) {
    return getCache()->get("category_products_{$category}");
}

/**
 * Cache search results
 */
function cacheSearchResults($query, $filters, $results, $ttl = 600) {
    $cacheKey = "search_" . md5($query . serialize($filters));
    return getCache()->set($cacheKey, $results, $ttl);
}

/**
 * Get cached search results
 */
function getCachedSearchResults($query, $filters) {
    $cacheKey = "search_" . md5($query . serialize($filters));
    return getCache()->get($cacheKey);
}

/**
 * Cache homepage data
 */
function cacheHomepageData($data, $ttl = 1800) {
    return getCache()->set('homepage_data', $data, $ttl);
}

/**
 * Get cached homepage data
 */
function getCachedHomepageData() {
    return getCache()->get('homepage_data');
}

/**
 * Cache analytics data
 */
function cacheAnalyticsData($key, $data, $ttl = 3600) {
    return getCache()->set("analytics_{$key}", $data, $ttl);
}

/**
 * Get cached analytics data
 */
function getCachedAnalyticsData($key) {
    return getCache()->get("analytics_{$key}");
}

/**
 * Invalidate product-related cache
 */
function invalidateProductCache($productId = null, $category = null) {
    $cache = getCache();
    
    if ($productId) {
        $cache->delete("product_{$productId}");
    }
    
    if ($category) {
        $cache->delete("category_products_{$category}");
    }
    
    // Clear homepage cache as it might contain product data
    $cache->delete('homepage_data');
    
    // Clear related search caches (in a real app, you'd track which caches to clear)
    $cache->delete('featured_products');
    $cache->delete('recent_products');
}

/**
 * Invalidate search cache
 */
function invalidateSearchCache() {
    $cache = getCache();
    $files = glob($cache->cacheDir . '*.cache');
    
    foreach ($files as $file) {
        $filename = basename($file, '.cache');
        if (strpos($filename, md5('search_')) === 0) {
            unlink($file);
        }
    }
}

/**
 * Minify HTML output
 */
function minifyHTML($html) {
    // Remove HTML comments (except IE conditionals)
    $html = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $html);
    
    // Remove unnecessary whitespace
    $html = preg_replace('/\s+/', ' ', $html);
    $html = preg_replace('/>\s+</', '><', $html);
    
    // Remove whitespace around block elements
    $blockElements = 'div|p|h1|h2|h3|h4|h5|h6|ul|ol|li|section|article|header|footer|nav|main|aside';
    $html = preg_replace('/\s*(<\/?(?:' . $blockElements . ')[^>]*>)\s*/', '$1', $html);
    
    return trim($html);
}

/**
 * Enable output compression
 */
function enableCompression() {
    if (!ob_get_level()) {
        ob_start();
    }
    
    // Enable gzip compression if available
    if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
        if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && 
            strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
            ob_start('ob_gzhandler');
        }
    }
}

/**
 * Set browser caching headers
 */
function setBrowserCache($type = 'static', $maxAge = 86400) {
    switch ($type) {
        case 'static': // CSS, JS, Images
            header('Cache-Control: public, max-age=' . $maxAge);
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
            break;
            
        case 'dynamic': // Dynamic pages with some caching
            header('Cache-Control: public, max-age=300, must-revalidate');
            break;
            
        case 'private': // User-specific content
            header('Cache-Control: private, max-age=0, must-revalidate');
            break;
            
        case 'no-cache': // Always fresh
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            break;
    }
    
    // Add ETag for better caching
    if ($type !== 'no-cache') {
        $etag = md5($_SERVER['REQUEST_URI'] . filemtime(__FILE__));
        header('ETag: "' . $etag . '"');
        
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && 
            $_SERVER['HTTP_IF_NONE_MATCH'] === '"' . $etag . '"') {
            http_response_code(304);
            exit;
        }
    }
}

/**
 * Database query caching wrapper
 */
function cachedQuery($db, $sql, $params = [], $cacheKey = null, $ttl = 900) {
    if (!$cacheKey) {
        $cacheKey = 'query_' . md5($sql . serialize($params));
    }
    
    return getCache()->query($cacheKey, function() use ($db, $sql, $params) {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }, $ttl);
}

/**
 * Image optimization and caching
 */
function optimizeImage($imagePath, $maxWidth = 800, $quality = 85) {
    $cacheKey = 'optimized_' . md5($imagePath . $maxWidth . $quality);
    $cache = getCache();
    
    $cachedPath = $cache->get($cacheKey);
    if ($cachedPath && file_exists($cachedPath)) {
        return $cachedPath;
    }
    
    // Create optimized image directory
    $optimizedDir = dirname($imagePath) . '/optimized/';
    if (!is_dir($optimizedDir)) {
        mkdir($optimizedDir, 0755, true);
    }
    
    $optimizedPath = $optimizedDir . basename($imagePath);
    
    // Simple image resizing (in a real app, use libraries like Intervention Image)
    if (extension_loaded('gd')) {
        $info = getimagesize($imagePath);
        if ($info && $info[0] > $maxWidth) {
            // Image resizing logic would go here
            // For demo, just copy the file
            copy($imagePath, $optimizedPath);
        } else {
            copy($imagePath, $optimizedPath);
        }
    } else {
        copy($imagePath, $optimizedPath);
    }
    
    // Cache the optimized path
    $cache->set($cacheKey, $optimizedPath, 86400); // Cache for 24 hours
    
    return $optimizedPath;
}
?>
