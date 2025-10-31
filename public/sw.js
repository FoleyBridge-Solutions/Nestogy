"use strict";

const CACHE_NAME = "nestogy-cache-v4";
const OFFLINE_URL = '/offline.html';

const filesToCache = [
    OFFLINE_URL,
    '/logo.png'
];

// ===================================
// INSTALL EVENT
// ===================================
self.addEventListener("install", (event) => {
    console.log('[ServiceWorker] Installing...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[ServiceWorker] Caching offline page');
                return cache.addAll(filesToCache);
            })
            .then(() => self.skipWaiting()) // Activate immediately
    );
});

// ===================================
// ACTIVATE EVENT
// ===================================
self.addEventListener('activate', (event) => {
    console.log('[ServiceWorker] Activating...');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[ServiceWorker] Removing old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim()) // Take control immediately
    );
});

// ===================================
// FETCH EVENT (Offline Support)
// ===================================
self.addEventListener("fetch", (event) => {
    const url = new URL(event.request.url);
    
    // Skip Vite dev server requests (port 5173)
    if (url.port === '5173') {
        return; // Let the browser handle Vite requests directly
    }
    
    // Skip Vite-specific paths
    if (url.pathname.includes('/@vite/') || 
        url.pathname.includes('/@fs/') ||
        url.pathname.includes('/__vite_ping') ||
        url.pathname.includes('/node_modules/')) {
        return;
    }
    
    // Skip cross-origin requests (different domain/port than app)
    if (url.origin !== self.location.origin) {
        return;
    }
    
    // Skip Livewire requests - NEVER cache these!
    if (url.pathname.startsWith('/livewire/')) {
        return; // Let Livewire handle its own requests
    }
    
    // Skip API requests and dynamic content
    if (url.pathname.startsWith('/api/') || 
        event.request.method !== 'GET' ||
        event.request.headers.has('X-Livewire')) {
        return; // Don't cache dynamic content
    }

    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .catch(() => caches.match(OFFLINE_URL))
        );
    } else {
        // Only cache static assets (CSS, JS, images, fonts)
        const isStaticAsset = url.pathname.match(/\.(css|js|jpg|jpeg|png|gif|svg|woff|woff2|ttf|eot|ico)$/);
        
        if (isStaticAsset) {
            event.respondWith(
                caches.match(event.request)
                    .then((response) => response || fetch(event.request))
            );
        }
        // For everything else, always fetch fresh
    }
});

// ===================================
// PUSH NOTIFICATION HANDLERS
// ===================================

// Push received from server
self.addEventListener('push', function(event) {
    console.log('[ServiceWorker] Push notification received', event);
    
    if (!event.data) {
        console.log('[ServiceWorker] Push event has no data');
        return;
    }

    let data;
    try {
        data = event.data.json();
    } catch (e) {
        console.error('[ServiceWorker] Error parsing push data:', e);
        data = {
            title: 'Nestogy',
            body: event.data.text() || 'New notification'
        };
    }
    
    // Notification options
    const options = {
        body: data.body || 'New notification from Nestogy',
        icon: data.icon || '/logo.png',
        badge: data.badge || '/logo.png',
        image: data.image,
        tag: data.tag || 'nestogy-notification',
        requireInteraction: data.requireInteraction || false,
        silent: data.silent || false,
        vibrate: data.vibrate || [200, 100, 200],
        timestamp: Date.now(),
        data: {
            url: data.url || '/',
            ticket_id: data.ticket_id,
            notification_type: data.type,
            timestamp: Date.now(),
            ...data.data
        },
        actions: []
    };

    // Add context-specific actions
    if (data.type === 'ticket_assigned' || data.type === 'ticket_created') {
        options.actions = [
            { action: 'open', title: '👁️ View Ticket', icon: '/logo.png' },
            { action: 'dismiss', title: '✕ Dismiss', icon: '/logo.png' }
        ];
    } else if (data.type === 'ticket_comment_added') {
        options.actions = [
            { action: 'open', title: '💬 View Comment', icon: '/logo.png' },
            { action: 'dismiss', title: '✕ Dismiss', icon: '/logo.png' }
        ];
    } else if (data.type === 'sla_breach_warning' || data.type === 'sla_breached') {
        options.actions = [
            { action: 'open', title: '🚨 View Now', icon: '/logo.png' },
            { action: 'dismiss', title: '✕ Dismiss', icon: '/logo.png' }
        ];
        options.requireInteraction = true; // Critical notifications require interaction
    } else if (data.actions && Array.isArray(data.actions)) {
        options.actions = data.actions;
    }

    event.waitUntil(
        self.registration.showNotification(data.title || 'Nestogy', options)
            .then(() => {
                console.log('[ServiceWorker] Notification shown successfully');
            })
            .catch((error) => {
                console.error('[ServiceWorker] Error showing notification:', error);
            })
    );
});

// Notification clicked
self.addEventListener('notificationclick', function(event) {
    console.log('[ServiceWorker] Notification clicked:', event.action);
    
    event.notification.close();

    // Handle different actions
    if (event.action === 'dismiss') {
        // Just close the notification (already done above)
        return;
    }

    // Default action or "open" action
    const urlToOpen = new URL(event.notification.data.url || '/', self.location.origin).href;
    
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(function(clientList) {
                // Check if there's already a window open
                for (let i = 0; i < clientList.length; i++) {
                    const client = clientList[i];
                    const clientUrl = new URL(client.url);
                    const targetUrl = new URL(urlToOpen);
                    
                    // If same origin, focus and navigate
                    if (clientUrl.origin === targetUrl.origin && 'focus' in client) {
                        return client.focus().then(client => {
                            if ('navigate' in client) {
                                return client.navigate(urlToOpen);
                            }
                        });
                    }
                }
                
                // Open new window if none found
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
            .catch((error) => {
                console.error('[ServiceWorker] Error handling notification click:', error);
            })
    );
});

// Notification closed (dismissed without action)
self.addEventListener('notificationclose', function(event) {
    console.log('[ServiceWorker] Notification closed:', event.notification.tag);
    
    // Optional: Track dismissals for analytics
    // You could send analytics here
});

// ===================================
// BACKGROUND SYNC (Optional - for offline actions)
// ===================================
self.addEventListener('sync', function(event) {
    console.log('[ServiceWorker] Background sync event:', event.tag);
    
    if (event.tag === 'sync-notifications') {
        event.waitUntil(
            // Sync any pending notification interactions
            syncNotifications()
        );
    }
});

function syncNotifications() {
    // Implement if you need to sync notification interactions when coming back online
    return Promise.resolve();
}

// ===================================
// MESSAGE HANDLER (from clients)
// ===================================
self.addEventListener('message', function(event) {
    console.log('[ServiceWorker] Message received:', event.data);
    
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

console.log('[ServiceWorker] Service Worker loaded and ready');
