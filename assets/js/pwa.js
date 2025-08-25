// PWA (Progressive Web App) functionality
class PWAManager {
    constructor() {
        this.deferredPrompt = null;
        this.isStandalone = false;
        this.notificationPermission = 'default';
        this.isOnline = navigator.onLine;
        
        this.init();
    }
    
    init() {
        this.checkStandaloneMode();
        this.registerServiceWorker();
        this.setupEventListeners();
        this.setupOfflineStorage();
        this.checkNotificationSupport();
        this.setupInstallPrompt();
        this.setupOfflineIndicator();
    }
    
    // Check if app is running in standalone mode
    checkStandaloneMode() {
        this.isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
                           window.navigator.standalone ||
                           document.referrer.includes('android-app://');
        
        if (this.isStandalone) {
            document.body.classList.add('standalone-mode');
            console.log('PWA: Running in standalone mode');
        }
    }
    
    // Register service worker
    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/sw.js', {
                    scope: '/'
                });
                
                console.log('PWA: Service Worker registered successfully');
                
                // Check for updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            this.showUpdateAvailableNotification();
                        }
                    });
                });
                
                return registration;
            } catch (error) {
                console.error('PWA: Service Worker registration failed', error);
            }
        }
    }
    
    // Setup event listeners
    setupEventListeners() {
        // Install prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
            this.showInstallButton();
        });
        
        // App installed
        window.addEventListener('appinstalled', () => {
            console.log('PWA: App installed successfully');
            this.hideInstallButton();
            this.trackEvent('pwa_installed');
        });
        
        // Online/offline status
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.handleOnlineStatus();
        });
        
        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.handleOfflineStatus();
        });
        
        // Visibility change (for push notifications)
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.clearNotificationBadge();
            }
        });
    }
    
    // Setup offline storage using IndexedDB
    setupOfflineStorage() {
        const request = indexedDB.open('OfflineActions', 1);
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            
            // Create object stores
            if (!db.objectStoreNames.contains('actions')) {
                const actionsStore = db.createObjectStore('actions', { keyPath: 'id', autoIncrement: true });
                actionsStore.createIndex('type', 'type', { unique: false });
                actionsStore.createIndex('timestamp', 'timestamp', { unique: false });
            }
            
            if (!db.objectStoreNames.contains('products')) {
                const productsStore = db.createObjectStore('products', { keyPath: 'id' });
                productsStore.createIndex('category', 'category', { unique: false });
            }
            
            if (!db.objectStoreNames.contains('cart')) {
                db.createObjectStore('cart', { keyPath: 'id', autoIncrement: true });
            }
        };
        
        request.onsuccess = () => {
            this.db = request.result;
            console.log('PWA: Offline storage initialized');
        };
        
        request.onerror = () => {
            console.error('PWA: Failed to initialize offline storage');
        };
    }
    
    // Check notification support and request permission
    async checkNotificationSupport() {
        if ('Notification' in window) {
            this.notificationPermission = Notification.permission;
            
            if (this.notificationPermission === 'default') {
                // Show notification permission request UI
                this.showNotificationPermissionPrompt();
            }
        }
    }
    
    // Request notification permission
    async requestNotificationPermission() {
        if ('Notification' in window) {
            try {
                const permission = await Notification.requestPermission();
                this.notificationPermission = permission;
                
                if (permission === 'granted') {
                    console.log('PWA: Notification permission granted');
                    this.subscribeToPushNotifications();
                    this.hideNotificationPermissionPrompt();
                } else {
                    console.log('PWA: Notification permission denied');
                }
                
                return permission;
            } catch (error) {
                console.error('PWA: Error requesting notification permission', error);
            }
        }
    }
    
    // Subscribe to push notifications
    async subscribeToPushNotifications() {
        try {
            const registration = await navigator.serviceWorker.ready;
            
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlB64ToUint8Array('YOUR_VAPID_PUBLIC_KEY') // Replace with actual VAPID key
            });
            
            // Send subscription to server
            await this.sendSubscriptionToServer(subscription);
            
            console.log('PWA: Push notification subscription successful');
        } catch (error) {
            console.error('PWA: Push notification subscription failed', error);
        }
    }
    
    // Setup install prompt
    setupInstallPrompt() {
        // Create install button
        const installButton = document.createElement('button');
        installButton.id = 'pwa-install-btn';
        installButton.className = 'pwa-install-button hidden';
        installButton.innerHTML = '<i class="fas fa-download"></i> Install App';
        installButton.addEventListener('click', () => this.installApp());
        
        // Add to page
        document.body.appendChild(installButton);
    }
    
    // Show install button
    showInstallButton() {
        const installButton = document.getElementById('pwa-install-btn');
        if (installButton) {
            installButton.classList.remove('hidden');
            
            // Also show in-page install prompts
            this.showInstallPromptBanner();
        }
    }
    
    // Hide install button
    hideInstallButton() {
        const installButton = document.getElementById('pwa-install-btn');
        if (installButton) {
            installButton.classList.add('hidden');
        }
        
        this.hideInstallPromptBanner();
    }
    
    // Install app
    async installApp() {
        if (this.deferredPrompt) {
            this.deferredPrompt.prompt();
            
            const { outcome } = await this.deferredPrompt.userChoice;
            console.log('PWA: Install prompt outcome:', outcome);
            
            if (outcome === 'accepted') {
                this.trackEvent('pwa_install_accepted');
            } else {
                this.trackEvent('pwa_install_declined');
            }
            
            this.deferredPrompt = null;
        }
    }
    
    // Setup offline indicator
    setupOfflineIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'offline-indicator';
        indicator.className = 'offline-indicator hidden';
        indicator.innerHTML = `
            <div class="offline-content">
                <i class="fas fa-wifi-slash"></i>
                <span>You're offline. Changes will sync when reconnected.</span>
            </div>
        `;
        
        document.body.appendChild(indicator);
    }
    
    // Handle online status
    handleOnlineStatus() {
        console.log('PWA: Back online');
        
        // Hide offline indicator
        const indicator = document.getElementById('offline-indicator');
        if (indicator) {
            indicator.classList.add('hidden');
        }
        
        // Sync offline actions
        this.syncOfflineActions();
        
        // Show notification
        this.showToast('You\'re back online! Syncing your changes...', 'success');
    }
    
    // Handle offline status
    handleOfflineStatus() {
        console.log('PWA: Gone offline');
        
        // Show offline indicator
        const indicator = document.getElementById('offline-indicator');
        if (indicator) {
            indicator.classList.remove('hidden');
        }
        
        // Show notification
        this.showToast('You\'re offline. Don\'t worry, your changes will be saved!', 'info');
    }
    
    // Sync offline actions when back online
    async syncOfflineActions() {
        if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
            try {
                const registration = await navigator.serviceWorker.ready;
                
                // Register background sync for different types
                await registration.sync.register('cart-sync');
                await registration.sync.register('wishlist-sync');
                await registration.sync.register('order-sync');
                
                console.log('PWA: Background sync registered');
            } catch (error) {
                console.error('PWA: Background sync registration failed', error);
            }
        }
    }
    
    // Store action for offline sync
    async storeOfflineAction(type, data) {
        if (this.db) {
            const transaction = this.db.transaction(['actions'], 'readwrite');
            const store = transaction.objectStore('actions');
            
            const action = {
                type: type,
                data: data,
                timestamp: Date.now()
            };
            
            await store.add(action);
            console.log('PWA: Offline action stored', type);
        }
    }
    
    // Show notification permission prompt
    showNotificationPermissionPrompt() {
        const prompt = document.createElement('div');
        prompt.id = 'notification-permission-prompt';
        prompt.className = 'notification-prompt';
        prompt.innerHTML = `
            <div class="prompt-content">
                <div class="prompt-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="prompt-text">
                    <h4>Stay Updated!</h4>
                    <p>Get notified about order updates and special offers</p>
                </div>
                <div class="prompt-actions">
                    <button class="btn btn-primary" onclick="pwaManager.requestNotificationPermission()">
                        Allow Notifications
                    </button>
                    <button class="btn btn-secondary" onclick="pwaManager.hideNotificationPermissionPrompt()">
                        Not Now
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(prompt);
    }
    
    // Hide notification permission prompt
    hideNotificationPermissionPrompt() {
        const prompt = document.getElementById('notification-permission-prompt');
        if (prompt) {
            prompt.remove();
        }
    }
    
    // Show install prompt banner
    showInstallPromptBanner() {
        const banner = document.createElement('div');
        banner.id = 'install-prompt-banner';
        banner.className = 'install-banner';
        banner.innerHTML = `
            <div class="banner-content">
                <div class="banner-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <div class="banner-text">
                    <h4>Install our App!</h4>
                    <p>Get a better shopping experience with our mobile app</p>
                </div>
                <div class="banner-actions">
                    <button class="btn btn-primary" onclick="pwaManager.installApp()">
                        Install
                    </button>
                    <button class="btn btn-secondary" onclick="pwaManager.hideInstallPromptBanner()">
                        Maybe Later
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(banner);
    }
    
    // Hide install prompt banner
    hideInstallPromptBanner() {
        const banner = document.getElementById('install-prompt-banner');
        if (banner) {
            banner.remove();
        }
    }
    
    // Show update available notification
    showUpdateAvailableNotification() {
        const notification = document.createElement('div');
        notification.id = 'update-notification';
        notification.className = 'update-notification';
        notification.innerHTML = `
            <div class="notification-content">
                <div class="notification-icon">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <div class="notification-text">
                    <h4>Update Available!</h4>
                    <p>A new version of the app is ready</p>
                </div>
                <div class="notification-actions">
                    <button class="btn btn-primary" onclick="pwaManager.updateApp()">
                        Update Now
                    </button>
                    <button class="btn btn-secondary" onclick="pwaManager.hideUpdateNotification()">
                        Later
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(notification);
    }
    
    // Update app
    updateApp() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(registrations => {
                registrations.forEach(registration => {
                    registration.update();
                });
            });
            
            // Reload page after update
            window.location.reload();
        }
    }
    
    // Hide update notification
    hideUpdateNotification() {
        const notification = document.getElementById('update-notification');
        if (notification) {
            notification.remove();
        }
    }
    
    // Clear notification badge
    clearNotificationBadge() {
        if ('setAppBadge' in navigator) {
            navigator.setAppBadge(0);
        }
    }
    
    // Send push notification subscription to server
    async sendSubscriptionToServer(subscription) {
        try {
            await fetch('/api/push_subscribe.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    subscription: subscription,
                    user_id: window.currentUserId || null
                })
            });
        } catch (error) {
            console.error('PWA: Failed to send subscription to server', error);
        }
    }
    
    // Utility function to convert VAPID key
    urlB64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');
        
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        
        return outputArray;
    }
    
    // Track PWA events
    trackEvent(eventName, eventData = {}) {
        // Send to analytics
        if (typeof gtag !== 'undefined') {
            gtag('event', eventName, eventData);
        }
        
        // Send to server
        fetch('/api/analytics.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                event: eventName,
                data: eventData,
                timestamp: Date.now()
            })
        }).catch(error => {
            console.error('PWA: Failed to track event', error);
        });
    }
    
    // Show toast notification
    showToast(message, type = 'info', duration = 5000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Show toast
        setTimeout(() => toast.classList.add('show'), 100);
        
        // Remove toast
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
}

// Enhanced offline cart functionality
class OfflineCart {
    constructor() {
        this.storeName = 'cart';
        this.init();
    }
    
    async init() {
        // Initialize IndexedDB for offline cart
        this.db = await this.openDB();
    }
    
    async openDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open('OfflineCart', 1);
            
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                if (!db.objectStoreNames.contains(this.storeName)) {
                    db.createObjectStore(this.storeName, { keyPath: 'product_id' });
                }
            };
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }
    
    async addItem(productId, quantity = 1, attributes = {}) {
        const transaction = this.db.transaction([this.storeName], 'readwrite');
        const store = transaction.objectStore(this.storeName);
        
        // Check if item already exists
        const existingItem = await store.get(productId);
        
        if (existingItem) {
            existingItem.quantity += quantity;
            await store.put(existingItem);
        } else {
            await store.add({
                product_id: productId,
                quantity: quantity,
                attributes: attributes,
                added_at: Date.now()
            });
        }
        
        // Store for sync when online
        if (!navigator.onLine) {
            await pwaManager.storeOfflineAction('cart_add', {
                product_id: productId,
                quantity: quantity,
                attributes: attributes
            });
        }
    }
    
    async removeItem(productId) {
        const transaction = this.db.transaction([this.storeName], 'readwrite');
        const store = transaction.objectStore(this.storeName);
        await store.delete(productId);
        
        // Store for sync when online
        if (!navigator.onLine) {
            await pwaManager.storeOfflineAction('cart_remove', {
                product_id: productId
            });
        }
    }
    
    async getItems() {
        const transaction = this.db.transaction([this.storeName], 'readonly');
        const store = transaction.objectStore(this.storeName);
        return await store.getAll();
    }
    
    async clearCart() {
        const transaction = this.db.transaction([this.storeName], 'readwrite');
        const store = transaction.objectStore(this.storeName);
        await store.clear();
    }
}

// Initialize PWA when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Initialize PWA Manager
    window.pwaManager = new PWAManager();
    
    // Initialize Offline Cart
    window.offlineCart = new OfflineCart();
    
    console.log('PWA: Initialized successfully');
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { PWAManager, OfflineCart };
}
