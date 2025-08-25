<?php
// Milestone 7: Progressive Web App (PWA) Service Worker
// Advanced PWA capabilities with offline functionality

class PWAManager {
    private $conn;
    private $cacheName = 'ecommerce-v1.0';
    private $offlineUrls = [
        '/',
        '/index.php',
        '/products.php',
        '/category.php',
        '/cart.php',
        '/login.php',
        '/register.php',
        '/offline.html',
        '/assets/css/style.css',
        '/assets/js/main.js',
        '/assets/images/logo.png'
    ];
    
    public function __construct($database) {
        $this->conn = $database;
    }
    
    public function generateServiceWorker() {
        $serviceWorkerContent = "
const CACHE_NAME = '{$this->cacheName}';
const OFFLINE_URLS = " . json_encode($this->offlineUrls) . ";

// Install event - cache resources
self.addEventListener('install', (event) => {
    console.log('Service Worker: Installing...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('Service Worker: Caching App Shell');
                return cache.addAll(OFFLINE_URLS);
            })
            .then(() => {
                console.log('Service Worker: Skip Waiting');
                return self.skipWaiting();
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('Service Worker: Activating...');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME) {
                        console.log('Service Worker: Clearing Old Cache');
                        return caches.delete(cache);
                    }
                })
            );
        }).then(() => {
            console.log('Service Worker: Claiming Clients');
            return self.clients.claim();
        })
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
    console.log('Service Worker: Fetching', event.request.url);
    
    if (event.request.url.indexOf('http') !== 0) return; // Skip non-http requests
    
    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                // Return cached version or fetch from network
                return response || fetch(event.request)
                    .then((fetchResponse) => {
                        // Check if we received a valid response
                        if (!fetchResponse || fetchResponse.status !== 200 || fetchResponse.type !== 'basic') {
                            return fetchResponse;
                        }
                        
                        // Clone the response
                        const responseToCache = fetchResponse.clone();
                        
                        // Cache the fetched response
                        caches.open(CACHE_NAME)
                            .then((cache) => {
                                cache.put(event.request, responseToCache);
                            });
                        
                        return fetchResponse;
                    })
                    .catch(() => {
                        // If both cache and network fail, show offline page
                        if (event.request.destination === 'document') {
                            return caches.match('/offline.html');
                        }
                        
                        // For images, return a placeholder
                        if (event.request.destination === 'image') {
                            return new Response('<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"300\" height=\"200\" viewBox=\"0 0 300 200\"><rect width=\"300\" height=\"200\" fill=\"#f0f0f0\"/><text x=\"50%\" y=\"50%\" text-anchor=\"middle\" dy=\".3em\" fill=\"#999\">Image Offline</text></svg>', {
                                headers: { 'Content-Type': 'image/svg+xml' }
                            });
                        }
                    });
            })
    );
});

// Push notification event
self.addEventListener('push', (event) => {
    console.log('Service Worker: Push Received');
    
    let notificationData = {};
    
    if (event.data) {
        notificationData = event.data.json();
    }
    
    const options = {
        body: notificationData.body || 'New notification from E-Commerce Store',
        icon: '/assets/images/icons/icon-192x192.png',
        badge: '/assets/images/icons/icon-72x72.png',
        image: notificationData.image || '/assets/images/notification-banner.jpg',
        vibrate: [200, 100, 200],
        data: {
            url: notificationData.url || '/',
            action: notificationData.action || 'view'
        },
        actions: [
            {
                action: 'view',
                title: 'View',
                icon: '/assets/images/icons/view-icon.png'
            },
            {
                action: 'close',
                title: 'Close',
                icon: '/assets/images/icons/close-icon.png'
            }
        ],
        requireInteraction: true,
        tag: notificationData.tag || 'general'
    };
    
    event.waitUntil(
        self.registration.showNotification(notificationData.title || 'E-Commerce Store', options)
    );
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
    console.log('Service Worker: Notification Click Received');
    
    event.notification.close();
    
    if (event.action === 'close') {
        return;
    }
    
    const urlToOpen = event.notification.data.url || '/';
    
    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then((clientList) => {
            // Check if there's already a window/tab open with the target URL
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            
            // If not, open a new window/tab with the target URL
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});

// Background sync event
self.addEventListener('sync', (event) => {
    console.log('Service Worker: Background Sync', event.tag);
    
    if (event.tag === 'background-cart-sync') {
        event.waitUntil(syncCartData());
    }
    
    if (event.tag === 'background-favorites-sync') {
        event.waitUntil(syncFavoritesData());
    }
});

// Sync cart data when back online
async function syncCartData() {
    try {
        const cartData = await getStoredCartData();
        if (cartData && cartData.length > 0) {
            const response = await fetch('/api/sync-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ cartItems: cartData })
            });
            
            if (response.ok) {
                await clearStoredCartData();
                console.log('Cart data synced successfully');
            }
        }
    } catch (error) {
        console.error('Failed to sync cart data:', error);
    }
}

// Sync favorites data when back online
async function syncFavoritesData() {
    try {
        const favoritesData = await getStoredFavoritesData();
        if (favoritesData && favoritesData.length > 0) {
            const response = await fetch('/api/sync-favorites.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ favorites: favoritesData })
            });
            
            if (response.ok) {
                await clearStoredFavoritesData();
                console.log('Favorites data synced successfully');
            }
        }
    } catch (error) {
        console.error('Failed to sync favorites data:', error);
    }
}

// Helper functions for IndexedDB operations
async function getStoredCartData() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('ecommerce-offline', 1);
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['cart'], 'readonly');
            const store = transaction.objectStore('cart');
            const getRequest = store.getAll();
            
            getRequest.onsuccess = () => resolve(getRequest.result);
            getRequest.onerror = () => reject(getRequest.error);
        };
        request.onerror = () => reject(request.error);
    });
}

async function getStoredFavoritesData() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('ecommerce-offline', 1);
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['favorites'], 'readonly');
            const store = transaction.objectStore('favorites');
            const getRequest = store.getAll();
            
            getRequest.onsuccess = () => resolve(getRequest.result);
            getRequest.onerror = () => reject(getRequest.error);
        };
        request.onerror = () => reject(request.error);
    });
}

async function clearStoredCartData() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('ecommerce-offline', 1);
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['cart'], 'readwrite');
            const store = transaction.objectStore('cart');
            const clearRequest = store.clear();
            
            clearRequest.onsuccess = () => resolve();
            clearRequest.onerror = () => reject(clearRequest.error);
        };
        request.onerror = () => reject(request.error);
    });
}

async function clearStoredFavoritesData() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('ecommerce-offline', 1);
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['favorites'], 'readwrite');
            const store = transaction.objectStore('favorites');
            const clearRequest = store.clear();
            
            clearRequest.onsuccess = () => resolve();
            clearRequest.onerror = () => reject(clearRequest.error);
        };
        request.onerror = () => reject(request.error);
    });
}

// Periodic background sync
self.addEventListener('periodicsync', (event) => {
    if (event.tag === 'content-sync') {
        event.waitUntil(updateContentCache());
    }
});

async function updateContentCache() {
    try {
        const cache = await caches.open(CACHE_NAME);
        const requests = OFFLINE_URLS.map(url => fetch(url));
        const responses = await Promise.all(requests);
        
        for (let i = 0; i < OFFLINE_URLS.length; i++) {
            if (responses[i].ok) {
                await cache.put(OFFLINE_URLS[i], responses[i]);
            }
        }
        
        console.log('Content cache updated');
    } catch (error) {
        console.error('Failed to update content cache:', error);
    }
}
";
        
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/WEB/sw.js', $serviceWorkerContent);
        return true;
    }
    
    public function registerPushSubscription($userId, $endpoint, $keys) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO push_subscriptions (user_id, endpoint, p256dh_key, auth_key, created_at)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                p256dh_key = VALUES(p256dh_key),
                auth_key = VALUES(auth_key),
                updated_at = NOW()
            ");
            
            return $stmt->execute([
                $userId,
                $endpoint,
                $keys['p256dh'],
                $keys['auth']
            ]);
            
        } catch (Exception $e) {
            error_log("Push subscription error: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendPushNotification($userId, $title, $body, $url = null, $image = null) {
        try {
            // Get user's push subscriptions
            $stmt = $this->conn->prepare("
                SELECT endpoint, p256dh_key, auth_key 
                FROM push_subscriptions 
                WHERE user_id = ? AND is_active = 1
            ");
            $stmt->execute([$userId]);
            $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($subscriptions as $subscription) {
                $this->sendPushToEndpoint($subscription, $title, $body, $url, $image);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Send push notification error: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendPushToEndpoint($subscription, $title, $body, $url = null, $image = null) {
        $vapidKeys = [
            'subject' => 'mailto:admin@yourdomain.com',
            'publicKey' => 'YOUR_VAPID_PUBLIC_KEY',
            'privateKey' => 'YOUR_VAPID_PRIVATE_KEY'
        ];
        
        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'image' => $image,
            'tag' => 'ecommerce-notification'
        ]);
        
        // This would typically use a library like web-push-php
        // For demo purposes, we'll log the notification
        error_log("Push notification sent: " . $payload);
        
        return true;
    }
    
    public function enableInstallPrompt() {
        return "
        let deferredPrompt;
        
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('PWA: Install prompt triggered');
            e.preventDefault();
            deferredPrompt = e;
            showInstallButton();
        });
        
        function showInstallButton() {
            const installButton = document.getElementById('install-button');
            if (installButton) {
                installButton.style.display = 'block';
                installButton.addEventListener('click', installApp);
            }
        }
        
        function installApp() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('PWA: User accepted the install prompt');
                    } else {
                        console.log('PWA: User dismissed the install prompt');
                    }
                    deferredPrompt = null;
                });
            }
        }
        
        window.addEventListener('appinstalled', (evt) => {
            console.log('PWA: App was installed');
            // Hide install button
            const installButton = document.getElementById('install-button');
            if (installButton) {
                installButton.style.display = 'none';
            }
        });
        ";
    }
    
    public function getOfflineSupport() {
        return [
            'product_cache' => $this->cacheProducts(),
            'category_cache' => $this->cacheCategories(),
            'user_preferences' => $this->cacheUserPreferences()
        ];
    }
    
    private function cacheProducts($limit = 50) {
        try {
            $stmt = $this->conn->prepare("
                SELECT p.*, pi.image_url, c.name as category_name
                FROM products p
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'active'
                ORDER BY p.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Cache products error: " . $e->getMessage());
            return [];
        }
    }
    
    private function cacheCategories() {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM categories 
                WHERE status = 'active' 
                ORDER BY sort_order, name
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Cache categories error: " . $e->getMessage());
            return [];
        }
    }
    
    private function cacheUserPreferences() {
        // Return default user preferences for offline use
        return [
            'currency' => 'INR',
            'language' => 'en',
            'theme' => 'light',
            'notifications' => true
        ];
    }
}
?>
