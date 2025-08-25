/**
 * Service Worker for Nestogy Quote System
 * Provides offline functionality and caching for PWA features
 */

const CACHE_NAME = 'nestogy-quotes-v1.0.0';
const OFFLINE_URL = '/offline.html';

// Assets to cache for offline functionality
const CACHE_ASSETS = [
    '/',
    '/offline.html',
    '/css/app.css',
    '/js/app.js',
    '/js/components/cache-manager.js',
    '/js/components/quote-form.js',
    '/js/components/item-selector.js',
    '/js/components/pricing-calculator.js',
    '/js/components/template-manager.js',
    '/js/components/form-validator.js',
    '/js/components/auto-save.js',
    '/js/components/keyboard-shortcuts.js',
    '/js/components/bulk-operations.js',
    '/js/components/drag-drop.js',
    '/favicon.ico',
    '/images/logo.png',
    '/images/icons/icon-192.png',
    '/images/icons/icon-512.png'
];

// Cache strategies
const CACHE_STRATEGIES = {
    // Cache first, then network for static assets
    CACHE_FIRST: 'cache-first',
    // Network first, then cache for dynamic content
    NETWORK_FIRST: 'network-first',
    // Stale while revalidate for frequently updated content
    STALE_WHILE_REVALIDATE: 'stale-while-revalidate'
};

// Install event - cache essential assets
self.addEventListener('install', (event) => {
    console.log('Service Worker: Installing');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('Service Worker: Caching essential assets');
                return cache.addAll(CACHE_ASSETS);
            })
            .then(() => {
                console.log('Service Worker: Installation complete');
                return self.skipWaiting(); // Activate immediately
            })
            .catch((error) => {
                console.error('Service Worker: Installation failed', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('Service Worker: Activating');
    
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (cacheName !== CACHE_NAME) {
                            console.log('Service Worker: Deleting old cache', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('Service Worker: Activation complete');
                return self.clients.claim(); // Take control immediately
            })
    );
});

// Fetch event - handle all network requests
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Skip non-HTTP requests
    if (!request.url.startsWith('http')) {
        return;
    }

    // Handle different types of requests
    if (isQuoteDataRequest(url)) {
        event.respondWith(handleQuoteDataRequest(request));
    } else if (isStaticAssetRequest(url)) {
        event.respondWith(handleStaticAssetRequest(request));
    } else if (isAPIRequest(url)) {
        event.respondWith(handleAPIRequest(request));
    } else if (isNavigationRequest(request)) {
        event.respondWith(handleNavigationRequest(request));
    } else {
        event.respondWith(handleDefaultRequest(request));
    }
});

// Handle quote data requests (network first with offline fallback)
function handleQuoteDataRequest(request) {
    return fetch(request)
        .then((response) => {
            if (response.ok) {
                // Cache successful responses
                const responseClone = response.clone();
                caches.open(CACHE_NAME)
                    .then((cache) => cache.put(request, responseClone));
            }
            return response;
        })
        .catch(() => {
            // Return cached data if network fails
            return caches.match(request)
                .then((cachedResponse) => {
                    if (cachedResponse) {
                        // Add header to indicate stale data
                        const headers = new Headers(cachedResponse.headers);
                        headers.set('X-Served-From-Cache', 'true');
                        headers.set('X-Cache-Date', new Date().toISOString());
                        
                        return new Response(cachedResponse.body, {
                            status: cachedResponse.status,
                            statusText: cachedResponse.statusText,
                            headers: headers
                        });
                    }
                    
                    // Return offline indicator if no cache available
                    return new Response(JSON.stringify({
                        error: 'Offline',
                        message: 'This data is not available offline',
                        timestamp: new Date().toISOString()
                    }), {
                        status: 503,
                        headers: { 'Content-Type': 'application/json' }
                    });
                });
        });
}

// Handle static assets (cache first)
function handleStaticAssetRequest(request) {
    return caches.match(request)
        .then((cachedResponse) => {
            if (cachedResponse) {
                return cachedResponse;
            }
            
            return fetch(request)
                .then((response) => {
                    if (response.ok) {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME)
                            .then((cache) => cache.put(request, responseClone));
                    }
                    return response;
                });
        });
}

// Handle API requests (network first with smart caching)
function handleAPIRequest(request) {
    const url = new URL(request.url);
    
    // GET requests can be cached
    if (request.method === 'GET') {
        return fetch(request)
            .then((response) => {
                if (response.ok) {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME)
                        .then((cache) => {
                            // Cache with TTL metadata
                            const cacheResponse = responseClone.clone();
                            const headers = new Headers(cacheResponse.headers);
                            headers.set('X-Cache-Time', Date.now().toString());
                            headers.set('X-Cache-TTL', '300000'); // 5 minutes
                            
                            cache.put(request, new Response(cacheResponse.body, {
                                status: cacheResponse.status,
                                statusText: cacheResponse.statusText,
                                headers: headers
                            }));
                        });
                }
                return response;
            })
            .catch(() => {
                return caches.match(request)
                    .then((cachedResponse) => {
                        if (cachedResponse) {
                            const cacheTime = parseInt(cachedResponse.headers.get('X-Cache-Time')) || 0;
                            const cacheTTL = parseInt(cachedResponse.headers.get('X-Cache-TTL')) || 300000;
                            const isStale = Date.now() - cacheTime > cacheTTL;
                            
                            if (isStale) {
                                const headers = new Headers(cachedResponse.headers);
                                headers.set('X-Served-From-Cache', 'true');
                                headers.set('X-Cache-Stale', 'true');
                                
                                return new Response(cachedResponse.body, {
                                    status: cachedResponse.status,
                                    statusText: cachedResponse.statusText,
                                    headers: headers
                                });
                            }
                            
                            return cachedResponse;
                        }
                        
                        return offlineResponse(request);
                    });
            });
    }
    
    // POST/PUT/DELETE requests - try network, queue if offline
    return fetch(request)
        .catch(() => {
            // Queue write operations for later sync
            if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(request.method)) {
                queueOfflineRequest(request);
                
                return new Response(JSON.stringify({
                    success: true,
                    message: 'Request queued for when online',
                    queued: true,
                    timestamp: new Date().toISOString()
                }), {
                    status: 202,
                    headers: { 'Content-Type': 'application/json' }
                });
            }
            
            return offlineResponse(request);
        });
}

// Handle navigation requests (app shell pattern)
function handleNavigationRequest(request) {
    return fetch(request)
        .catch(() => {
            // Return cached page or offline page
            return caches.match(request)
                .then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    
                    // Return offline page for navigation
                    return caches.match(OFFLINE_URL);
                });
        });
}

// Handle other requests
function handleDefaultRequest(request) {
    return fetch(request)
        .catch(() => {
            return caches.match(request)
                .then((cachedResponse) => {
                    return cachedResponse || offlineResponse(request);
                });
        });
}

// Helper functions
function isQuoteDataRequest(url) {
    return url.pathname.includes('/api/quotes') || 
           url.pathname.includes('/quotes/') ||
           url.pathname.includes('/quote-templates');
}

function isStaticAssetRequest(url) {
    return url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$/);
}

function isAPIRequest(url) {
    return url.pathname.startsWith('/api/');
}

function isNavigationRequest(request) {
    return request.mode === 'navigate' || 
           (request.method === 'GET' && request.headers.get('accept').includes('text/html'));
}

function offlineResponse(request) {
    return new Response(JSON.stringify({
        error: 'Offline',
        message: 'You are currently offline. Please check your connection.',
        url: request.url,
        timestamp: new Date().toISOString()
    }), {
        status: 503,
        headers: { 'Content-Type': 'application/json' }
    });
}

// Offline request queue management
function queueOfflineRequest(request) {
    // Store in IndexedDB for persistence
    const requestData = {
        url: request.url,
        method: request.method,
        headers: Object.fromEntries(request.headers.entries()),
        body: request.body,
        timestamp: Date.now()
    };
    
    // Use IDB to store the queued request
    // This would integrate with the existing auto-save system
    return new Promise((resolve) => {
        // Simplified storage - in production, use IndexedDB properly
        const queuedRequests = JSON.parse(localStorage.getItem('offline_queue') || '[]');
        queuedRequests.push(requestData);
        localStorage.setItem('offline_queue', JSON.stringify(queuedRequests));
        resolve();
    });
}

// Background sync for processing queued requests
self.addEventListener('sync', (event) => {
    if (event.tag === 'background-sync') {
        event.waitUntil(processOfflineQueue());
    }
});

async function processOfflineQueue() {
    try {
        const queuedRequests = JSON.parse(localStorage.getItem('offline_queue') || '[]');
        const processedRequests = [];
        
        for (const requestData of queuedRequests) {
            try {
                const response = await fetch(requestData.url, {
                    method: requestData.method,
                    headers: requestData.headers,
                    body: requestData.body
                });
                
                if (response.ok) {
                    processedRequests.push(requestData);
                    
                    // Notify main thread of successful sync
                    self.clients.matchAll().then(clients => {
                        clients.forEach(client => {
                            client.postMessage({
                                type: 'OFFLINE_REQUEST_SYNCED',
                                request: requestData,
                                response: response.status
                            });
                        });
                    });
                }
            } catch (error) {
                console.error('Failed to sync request:', requestData, error);
            }
        }
        
        // Remove processed requests from queue
        const remainingRequests = queuedRequests.filter(req => 
            !processedRequests.includes(req)
        );
        localStorage.setItem('offline_queue', JSON.stringify(remainingRequests));
        
    } catch (error) {
        console.error('Error processing offline queue:', error);
    }
}

// Handle messages from main thread
self.addEventListener('message', (event) => {
    // Always verify the origin of the received message for security
    const expectedOrigin = self.location.origin;
    
    // For MessageEvent from postMessage(), check event.origin
    // For ExtendableMessageEvent from ServiceWorker context, the origin should always match
    if (event.origin !== undefined && event.origin !== expectedOrigin) {
        console.warn('Service Worker: Blocked message from untrusted origin:', event.origin);
        return;
    }
    
    // Additional security check: ensure the source is from a controlled client
    if (event.source && typeof event.source.url === 'string') {
        const sourceUrl = new URL(event.source.url);
        if (sourceUrl.origin !== expectedOrigin) {
            console.warn('Service Worker: Blocked message from untrusted source:', sourceUrl.origin);
            return;
        }
    }
    
    const { data } = event;
    
    // Validate that data exists and has expected structure
    if (!data || typeof data !== 'object') {
        console.warn('Service Worker: Invalid message data received');
        return;
    }
    
    switch (data.type) {
        case 'SKIP_WAITING':
            self.skipWaiting();
            break;
            
        case 'GET_CACHE_STATUS':
            caches.has(CACHE_NAME).then(hasCache => {
                event.ports[0].postMessage({
                    type: 'CACHE_STATUS',
                    hasCache,
                    cacheName: CACHE_NAME
                });
            });
            break;
            
        case 'CLEAR_CACHE':
            caches.delete(CACHE_NAME).then(success => {
                event.ports[0].postMessage({
                    type: 'CACHE_CLEARED',
                    success
                });
            });
            break;
            
        case 'FORCE_UPDATE':
            self.registration.update();
            break;
    }
});

// Push notification support for quote updates
self.addEventListener('push', (event) => {
    if (!event.data) return;
    
    const data = event.data.json();
    const options = {
        body: data.body || 'You have a new quote update',
        icon: '/images/icons/icon-192.png',
        badge: '/images/icons/badge-72.png',
        data: data,
        actions: [
            {
                action: 'view',
                title: 'View Quote',
                icon: '/images/icons/view.png'
            },
            {
                action: 'dismiss',
                title: 'Dismiss',
                icon: '/images/icons/dismiss.png'
            }
        ],
        requireInteraction: true,
        tag: `quote-${data.quote_id}`
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title || 'Quote Update', options)
    );
});

// Handle notification clicks
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    const action = event.action;
    const data = event.notification.data;
    
    if (action === 'view' && data.quote_id) {
        event.waitUntil(
            clients.openWindow(`/quotes/${data.quote_id}`)
        );
    } else if (action === 'dismiss') {
        // Just close the notification
        return;
    } else {
        // Default click action
        event.waitUntil(
            clients.openWindow('/')
        );
    }
});

console.log('Service Worker: Loaded and ready');