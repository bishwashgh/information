// Service Worker for PWA functionality
const CACHE_NAME = 'ecommerce-store-v1.0.0';
const STATIC_CACHE = 'static-v1';
const DYNAMIC_CACHE = 'dynamic-v1';
const IMAGE_CACHE = 'images-v1';

// Assets to cache immediately
const STATIC_ASSETS = [
  '/',
  '/index.php',
  '/products.php',
  '/cart.php',
  '/login.php',
  '/register.php',
  '/assets/css/style.css',
  '/assets/js/main.js',
  '/assets/js/pwa.js',
  '/assets/images/logo.png',
  '/assets/images/placeholder.jpg',
  '/manifest.json',
  'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
  'https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js'
];

// Assets to cache on demand
const DYNAMIC_ASSETS = [
  '/api/',
  '/user/',
  '/admin/'
];

// Install event - cache static assets
self.addEventListener('install', event => {
  console.log('Service Worker: Installing...');
  
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => {
        console.log('Service Worker: Caching static assets');
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => {
        console.log('Service Worker: Static assets cached');
        return self.skipWaiting();
      })
      .catch(error => {
        console.error('Service Worker: Error caching static assets', error);
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  console.log('Service Worker: Activating...');
  
  event.waitUntil(
    caches.keys()
      .then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => {
            if (cacheName !== STATIC_CACHE && 
                cacheName !== DYNAMIC_CACHE && 
                cacheName !== IMAGE_CACHE) {
              console.log('Service Worker: Deleting old cache', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      })
      .then(() => {
        console.log('Service Worker: Activated');
        return self.clients.claim();
      })
  );
});

// Fetch event - serve from cache or network
self.addEventListener('fetch', event => {
  const { request } = event;
  
  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }
  
  // Skip chrome-extension requests
  if (request.url.startsWith('chrome-extension://')) {
    return;
  }
  
  event.respondWith(
    caches.match(request)
      .then(cachedResponse => {
        // Return cached version if available
        if (cachedResponse) {
          console.log('Service Worker: Serving from cache', request.url);
          return cachedResponse;
        }
        
        // Otherwise fetch from network
        return fetch(request)
          .then(response => {
            // Check if response is valid
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }
            
            // Clone response for caching
            const responseToCache = response.clone();
            
            // Determine cache strategy
            if (isStaticAsset(request.url)) {
              cacheResponse(STATIC_CACHE, request, responseToCache);
            } else if (isImageAsset(request.url)) {
              cacheResponse(IMAGE_CACHE, request, responseToCache);
            } else if (isDynamicAsset(request.url)) {
              cacheResponse(DYNAMIC_CACHE, request, responseToCache);
            }
            
            return response;
          })
          .catch(error => {
            console.log('Service Worker: Fetch failed, serving offline fallback');
            
            // Serve offline fallback for HTML pages
            if (request.headers.get('accept').includes('text/html')) {
              return caches.match('/offline.html');
            }
            
            // Serve placeholder for images
            if (request.headers.get('accept').includes('image')) {
              return caches.match('/assets/images/offline-placeholder.png');
            }
            
            return new Response('Offline content not available', {
              status: 503,
              statusText: 'Service Unavailable',
              headers: new Headers({
                'Content-Type': 'text/plain'
              })
            });
          });
      })
  );
});

// Helper functions
function isStaticAsset(url) {
  return url.includes('.css') || 
         url.includes('.js') || 
         url.includes('fonts.googleapis.com') ||
         url.includes('cdnjs.cloudflare.com');
}

function isImageAsset(url) {
  return url.includes('.jpg') || 
         url.includes('.jpeg') || 
         url.includes('.png') || 
         url.includes('.gif') || 
         url.includes('.webp') ||
         url.includes('.svg');
}

function isDynamicAsset(url) {
  return url.includes('/api/') || 
         url.includes('/user/') || 
         url.includes('/admin/') ||
         url.includes('product.php') ||
         url.includes('products.php');
}

function cacheResponse(cacheName, request, response) {
  caches.open(cacheName)
    .then(cache => {
      console.log('Service Worker: Caching', request.url);
      cache.put(request, response);
    })
    .catch(error => {
      console.error('Service Worker: Error caching', error);
    });
}

// Background sync for offline actions
self.addEventListener('sync', event => {
  console.log('Service Worker: Background sync triggered', event.tag);
  
  if (event.tag === 'cart-sync') {
    event.waitUntil(syncCart());
  } else if (event.tag === 'order-sync') {
    event.waitUntil(syncOrders());
  } else if (event.tag === 'wishlist-sync') {
    event.waitUntil(syncWishlist());
  }
});

// Push notification handling
self.addEventListener('push', event => {
  console.log('Service Worker: Push notification received');
  
  let notificationData = {
    title: 'E-Commerce Store',
    body: 'You have a new notification!',
    icon: '/assets/images/icons/icon-192x192.png',
    badge: '/assets/images/icons/badge-72x72.png',
    tag: 'general',
    requireInteraction: false,
    actions: [
      {
        action: 'view',
        title: 'View',
        icon: '/assets/images/icons/view-icon.png'
      },
      {
        action: 'dismiss',
        title: 'Dismiss',
        icon: '/assets/images/icons/dismiss-icon.png'
      }
    ]
  };
  
  if (event.data) {
    try {
      notificationData = { ...notificationData, ...event.data.json() };
    } catch (error) {
      console.error('Service Worker: Error parsing push data', error);
    }
  }
  
  event.waitUntil(
    self.registration.showNotification(notificationData.title, notificationData)
  );
});

// Notification click handling
self.addEventListener('notificationclick', event => {
  console.log('Service Worker: Notification clicked', event.notification.tag);
  
  event.notification.close();
  
  if (event.action === 'view') {
    event.waitUntil(
      clients.openWindow(event.notification.data?.url || '/')
    );
  } else if (event.action === 'dismiss') {
    // Just close the notification
    return;
  } else {
    // Default action - open the app
    event.waitUntil(
      clients.matchAll({ type: 'window' })
        .then(clientList => {
          // If app is already open, focus it
          for (let i = 0; i < clientList.length; i++) {
            const client = clientList[i];
            if (client.url === '/' && 'focus' in client) {
              return client.focus();
            }
          }
          
          // Otherwise open new window
          if (clients.openWindow) {
            return clients.openWindow('/');
          }
        })
    );
  }
});

// Sync functions for offline actions
async function syncCart() {
  try {
    const offlineActions = await getOfflineActions('cart');
    
    for (const action of offlineActions) {
      await fetch('/api/cart.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(action.data)
      });
    }
    
    await clearOfflineActions('cart');
    console.log('Service Worker: Cart synced successfully');
  } catch (error) {
    console.error('Service Worker: Cart sync failed', error);
  }
}

async function syncOrders() {
  try {
    const offlineActions = await getOfflineActions('orders');
    
    for (const action of offlineActions) {
      await fetch('/api/orders.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(action.data)
      });
    }
    
    await clearOfflineActions('orders');
    console.log('Service Worker: Orders synced successfully');
  } catch (error) {
    console.error('Service Worker: Orders sync failed', error);
  }
}

async function syncWishlist() {
  try {
    const offlineActions = await getOfflineActions('wishlist');
    
    for (const action of offlineActions) {
      await fetch('/api/wishlist.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(action.data)
      });
    }
    
    await clearOfflineActions('wishlist');
    console.log('Service Worker: Wishlist synced successfully');
  } catch (error) {
    console.error('Service Worker: Wishlist sync failed', error);
  }
}

// IndexedDB helpers for offline storage
async function getOfflineActions(type) {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('OfflineActions', 1);
    
    request.onsuccess = event => {
      const db = event.target.result;
      const transaction = db.transaction(['actions'], 'readonly');
      const store = transaction.objectStore('actions');
      const getRequest = store.getAll();
      
      getRequest.onsuccess = () => {
        const actions = getRequest.result.filter(action => action.type === type);
        resolve(actions);
      };
      
      getRequest.onerror = () => reject(getRequest.error);
    };
    
    request.onerror = () => reject(request.error);
  });
}

async function clearOfflineActions(type) {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('OfflineActions', 1);
    
    request.onsuccess = event => {
      const db = event.target.result;
      const transaction = db.transaction(['actions'], 'readwrite');
      const store = transaction.objectStore('actions');
      
      // Clear all actions of this type
      const getRequest = store.getAll();
      getRequest.onsuccess = () => {
        const actions = getRequest.result.filter(action => action.type === type);
        actions.forEach(action => {
          store.delete(action.id);
        });
        resolve();
      };
      
      getRequest.onerror = () => reject(getRequest.error);
    };
    
    request.onerror = () => reject(request.error);
  });
}

console.log('Service Worker: Script loaded successfully');
