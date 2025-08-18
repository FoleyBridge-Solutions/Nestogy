/**
 * Cache Manager Component
 * Provides intelligent caching for templates, products, and other frequently accessed data
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('cacheManager', (config = {}) => ({
        // Configuration
        maxAge: config.maxAge || 5 * 60 * 1000, // 5 minutes default
        maxSize: config.maxSize || 100, // Max items in cache
        storagePrefix: config.storagePrefix || 'nestogy_cache_',
        enablePersistence: config.enablePersistence !== false,
        enableCompression: config.enableCompression !== false,
        
        // Cache state
        cache: new Map(),
        accessTimes: new Map(),
        hitCount: 0,
        missCount: 0,
        
        // Cache strategies
        strategies: {
            'templates': { maxAge: 10 * 60 * 1000, priority: 'high' },
            'products': { maxAge: 15 * 60 * 1000, priority: 'high' },
            'clients': { maxAge: 5 * 60 * 1000, priority: 'medium' },
            'categories': { maxAge: 30 * 60 * 1000, priority: 'low' },
            'pricing': { maxAge: 2 * 60 * 1000, priority: 'high' }
        },

        // Initialize cache manager
        init() {
            this.loadPersistedCache();
            this.setupEventListeners();
            this.startCleanupTimer();
        },

        // Setup event listeners
        setupEventListeners() {
            // Save cache before page unload
            window.addEventListener('beforeunload', () => {
                this.persistCache();
            });

            // Clean up on visibility change
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.persistCache();
                } else {
                    this.cleanExpired();
                }
            });

            // Listen for cache invalidation events
            document.addEventListener('cache-invalidate', (e) => {
                this.invalidate(e.detail.key || e.detail.pattern);
            });

            // Network status for cache behavior
            window.addEventListener('online', () => {
                this.onNetworkChange(true);
            });

            window.addEventListener('offline', () => {
                this.onNetworkChange(false);
            });
        },

        // Get item from cache
        get(key, defaultValue = null) {
            const item = this.cache.get(key);
            
            if (!item) {
                this.missCount++;
                return defaultValue;
            }

            // Check if expired
            if (this.isExpired(item)) {
                this.cache.delete(key);
                this.accessTimes.delete(key);
                this.missCount++;
                return defaultValue;
            }

            // Update access time
            this.accessTimes.set(key, Date.now());
            this.hitCount++;
            
            return item.data;
        },

        // Set item in cache
        set(key, data, options = {}) {
            const strategy = this.getStrategy(key);
            const maxAge = options.maxAge || strategy.maxAge || this.maxAge;
            
            const item = {
                key,
                data: this.shouldCompress(data) ? this.compress(data) : data,
                timestamp: Date.now(),
                maxAge,
                priority: options.priority || strategy.priority || 'medium',
                compressed: this.shouldCompress(data),
                size: this.calculateSize(data)
            };

            // Enforce cache size limit
            this.ensureCacheSize();
            
            this.cache.set(key, item);
            this.accessTimes.set(key, Date.now());

            // Persist if enabled
            if (this.enablePersistence && item.priority !== 'transient') {
                this.persistItem(key, item);
            }

            return item;
        },

        // Check if item exists and is valid
        has(key) {
            const item = this.cache.get(key);
            return item && !this.isExpired(item);
        },

        // Delete item from cache
        delete(key) {
            const deleted = this.cache.delete(key);
            this.accessTimes.delete(key);
            
            if (this.enablePersistence) {
                this.removePersisted(key);
            }
            
            return deleted;
        },

        // Clear entire cache
        clear() {
            this.cache.clear();
            this.accessTimes.clear();
            this.hitCount = 0;
            this.missCount = 0;
            
            if (this.enablePersistence) {
                this.clearPersisted();
            }
        },

        // Invalidate by key or pattern
        invalidate(keyOrPattern) {
            if (typeof keyOrPattern === 'string') {
                if (keyOrPattern.includes('*')) {
                    // Pattern matching
                    const pattern = keyOrPattern.replace(/\*/g, '.*');
                    const regex = new RegExp(pattern);
                    
                    Array.from(this.cache.keys()).forEach(key => {
                        if (regex.test(key)) {
                            this.delete(key);
                        }
                    });
                } else {
                    // Exact match
                    this.delete(keyOrPattern);
                }
            }
        },

        // Get with fallback to fetch function
        async getOrFetch(key, fetchFn, options = {}) {
            let data = this.get(key);
            
            if (data === null || (options.forceRefresh && navigator.onLine)) {
                try {
                    data = await fetchFn();
                    if (data !== null && data !== undefined) {
                        this.set(key, data, options);
                    }
                } catch (error) {
                    console.error(`Failed to fetch data for key: ${key}`, error);
                    
                    // Return stale data if fetch fails and we have it
                    const staleData = this.getStale(key);
                    if (staleData !== null) {
                        console.warn(`Returning stale data for key: ${key}`);
                        return staleData;
                    }
                    
                    throw error;
                }
            }
            
            return data;
        },

        // Get stale data (expired but still in cache)
        getStale(key) {
            const item = this.cache.get(key);
            return item ? (item.compressed ? this.decompress(item.data) : item.data) : null;
        },

        // Templates-specific methods
        async getTemplate(templateId, forceRefresh = false) {
            const key = `templates:${templateId}`;
            return this.getOrFetch(key, async () => {
                const response = await fetch(`/api/quote-templates/${templateId}`);
                if (!response.ok) throw new Error(`Failed to fetch template ${templateId}`);
                return response.json();
            }, { forceRefresh, priority: 'high' });
        },

        async getTemplates(filters = {}) {
            const key = `templates:list:${JSON.stringify(filters)}`;
            return this.getOrFetch(key, async () => {
                const params = new URLSearchParams(filters);
                const response = await fetch(`/api/quote-templates?${params}`);
                if (!response.ok) throw new Error('Failed to fetch templates');
                return response.json();
            }, { priority: 'high' });
        },

        // Products-specific methods
        async getProduct(productId, forceRefresh = false) {
            const key = `products:${productId}`;
            return this.getOrFetch(key, async () => {
                const response = await fetch(`/api/products/${productId}`);
                if (!response.ok) throw new Error(`Failed to fetch product ${productId}`);
                return response.json();
            }, { forceRefresh, priority: 'high' });
        },

        async getProducts(filters = {}) {
            const key = `products:list:${JSON.stringify(filters)}`;
            return this.getOrFetch(key, async () => {
                const params = new URLSearchParams(filters);
                const response = await fetch(`/api/products?${params}`);
                if (!response.ok) throw new Error('Failed to fetch products');
                return response.json();
            }, { priority: 'high' });
        },

        async searchProducts(query, filters = {}) {
            const key = `products:search:${query}:${JSON.stringify(filters)}`;
            return this.getOrFetch(key, async () => {
                const params = new URLSearchParams({ ...filters, q: query });
                const response = await fetch(`/api/products/search?${params}`);
                if (!response.ok) throw new Error('Failed to search products');
                return response.json();
            }, { maxAge: 2 * 60 * 1000 }); // Shorter cache for search results
        },

        async searchServices(query, filters = {}) {
            const key = `services:search:${query}:${JSON.stringify(filters)}`;
            return this.getOrFetch(key, async () => {
                const params = new URLSearchParams({ ...filters, q: query });
                const response = await fetch(`/api/services/search?${params}`);
                if (!response.ok) throw new Error('Failed to search services');
                return response.json();
            }, { maxAge: 2 * 60 * 1000 }); // Shorter cache for search results
        },

        // Categories-specific methods
        async getCategories(forceRefresh = false) {
            const key = 'categories:list';
            return this.getOrFetch(key, async () => {
                const response = await fetch('/api/categories');
                if (!response.ok) throw new Error('Failed to fetch categories');
                return response.json();
            }, { forceRefresh, priority: 'low' });
        },

        // Clients-specific methods
        async getClient(clientId, forceRefresh = false) {
            const key = `clients:${clientId}`;
            return this.getOrFetch(key, async () => {
                const response = await fetch(`/api/clients/${clientId}`);
                if (!response.ok) throw new Error(`Failed to fetch client ${clientId}`);
                return response.json();
            }, { forceRefresh });
        },

        async getClients(filters = {}) {
            const key = `clients:list:${JSON.stringify(filters)}`;
            return this.getOrFetch(key, async () => {
                const params = new URLSearchParams(filters);
                const response = await fetch(`/api/clients?${params}`);
                if (!response.ok) throw new Error('Failed to fetch clients');
                return response.json();
            });
        },

        // Pricing-specific methods
        async getPricing(items, forceRefresh = false) {
            const key = `pricing:${this.hashItems(items)}`;
            return this.getOrFetch(key, async () => {
                const response = await fetch('/api/pricing/calculate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ items })
                });
                if (!response.ok) throw new Error('Failed to calculate pricing');
                return response.json();
            }, { forceRefresh, maxAge: 2 * 60 * 1000 });
        },

        // Preload frequently used data
        async preloadEssentials() {
            const preloadTasks = [
                this.getCategories(),
                this.getTemplates({ active: true, limit: 10 }),
                this.getProducts({ featured: true, limit: 20 })
            ];

            try {
                await Promise.allSettled(preloadTasks);
            } catch (error) {
                console.error('Failed to preload essential data:', error);
            }
        },

        // Warm up cache with user-specific data
        async warmUpCache(userId) {
            try {
                // Get user's recent templates and clients
                await Promise.allSettled([
                    this.getTemplates({ user_id: userId, recent: true }),
                    this.getClients({ active: true, limit: 50 })
                ]);
            } catch (error) {
                console.error('Failed to warm up cache:', error);
            }
        },

        // Cache strategy helpers
        getStrategy(key) {
            const type = key.split(':')[0];
            return this.strategies[type] || { maxAge: this.maxAge, priority: 'medium' };
        },

        isExpired(item) {
            return Date.now() - item.timestamp > item.maxAge;
        },

        shouldCompress(data) {
            if (!this.enableCompression) return false;
            const size = this.calculateSize(data);
            return size > 1024; // Compress if larger than 1KB
        },

        compress(data) {
            // Simple compression - in production, use a proper compression library
            return JSON.stringify(data);
        },

        decompress(data) {
            return typeof data === 'string' ? JSON.parse(data) : data;
        },

        calculateSize(data) {
            return JSON.stringify(data).length;
        },

        // Cache maintenance
        ensureCacheSize() {
            if (this.cache.size < this.maxSize) return;

            // Remove oldest, lowest priority items
            const entries = Array.from(this.cache.entries());
            const sorted = entries.sort((a, b) => {
                const priorityOrder = { low: 1, medium: 2, high: 3, transient: 0 };
                const aPriority = priorityOrder[a[1].priority] || 2;
                const bPriority = priorityOrder[b[1].priority] || 2;
                
                if (aPriority !== bPriority) {
                    return aPriority - bPriority;
                }
                
                const aAccess = this.accessTimes.get(a[0]) || 0;
                const bAccess = this.accessTimes.get(b[0]) || 0;
                return aAccess - bAccess;
            });

            // Remove bottom 20%
            const toRemove = Math.ceil(this.cache.size * 0.2);
            for (let i = 0; i < toRemove; i++) {
                this.delete(sorted[i][0]);
            }
        },

        cleanExpired() {
            const now = Date.now();
            Array.from(this.cache.entries()).forEach(([key, item]) => {
                if (now - item.timestamp > item.maxAge) {
                    this.delete(key);
                }
            });
        },

        startCleanupTimer() {
            setInterval(() => {
                this.cleanExpired();
            }, 60000); // Clean every minute
        },

        // Persistence
        persistCache() {
            if (!this.enablePersistence) return;

            try {
                const persistentItems = {};
                this.cache.forEach((item, key) => {
                    if (item.priority !== 'transient') {
                        persistentItems[key] = item;
                    }
                });

                localStorage.setItem(
                    this.storagePrefix + 'items',
                    JSON.stringify(persistentItems)
                );

                localStorage.setItem(
                    this.storagePrefix + 'access_times',
                    JSON.stringify(Object.fromEntries(this.accessTimes))
                );
            } catch (error) {
                console.warn('Failed to persist cache:', error);
            }
        },

        persistItem(key, item) {
            if (!this.enablePersistence) return;

            try {
                localStorage.setItem(
                    this.storagePrefix + key,
                    JSON.stringify(item)
                );
            } catch (error) {
                console.warn(`Failed to persist cache item ${key}:`, error);
            }
        },

        loadPersistedCache() {
            if (!this.enablePersistence) return;

            try {
                const itemsData = localStorage.getItem(this.storagePrefix + 'items');
                const accessData = localStorage.getItem(this.storagePrefix + 'access_times');

                if (itemsData) {
                    const items = JSON.parse(itemsData);
                    Object.entries(items).forEach(([key, item]) => {
                        if (!this.isExpired(item)) {
                            this.cache.set(key, item);
                        }
                    });
                }

                if (accessData) {
                    const accessTimes = JSON.parse(accessData);
                    this.accessTimes = new Map(Object.entries(accessTimes));
                }
            } catch (error) {
                console.warn('Failed to load persisted cache:', error);
            }
        },

        removePersisted(key) {
            try {
                localStorage.removeItem(this.storagePrefix + key);
            } catch (error) {
                console.warn(`Failed to remove persisted item ${key}:`, error);
            }
        },

        clearPersisted() {
            try {
                Object.keys(localStorage).forEach(key => {
                    if (key.startsWith(this.storagePrefix)) {
                        localStorage.removeItem(key);
                    }
                });
            } catch (error) {
                console.warn('Failed to clear persisted cache:', error);
            }
        },

        // Network handling
        onNetworkChange(isOnline) {
            if (isOnline) {
                // Refresh critical cached data when back online
                this.refreshCriticalCache();
            } else {
                // Extend cache TTL when offline
                this.extendCacheTTL();
            }
        },

        async refreshCriticalCache() {
            const criticalKeys = Array.from(this.cache.keys()).filter(key => {
                const item = this.cache.get(key);
                return item && item.priority === 'high';
            });

            for (const key of criticalKeys) {
                try {
                    // Refresh in background
                    setTimeout(() => {
                        this.invalidate(key);
                    }, Math.random() * 5000); // Stagger refreshes
                } catch (error) {
                    console.warn(`Failed to refresh critical cache item ${key}:`, error);
                }
            }
        },

        extendCacheTTL() {
            this.cache.forEach((item, key) => {
                if (item.priority === 'high') {
                    item.maxAge *= 2; // Double TTL for high priority items when offline
                }
            });
        },

        // Utility methods
        hashItems(items) {
            const str = JSON.stringify(items.map(item => ({
                id: item.id,
                quantity: item.quantity,
                unit_price: item.unit_price
            })));
            
            let hash = 0;
            for (let i = 0; i < str.length; i++) {
                const char = str.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash;
            }
            return hash.toString();
        },

        // Statistics and debugging
        getStats() {
            const totalRequests = this.hitCount + this.missCount;
            return {
                hitRate: totalRequests > 0 ? (this.hitCount / totalRequests * 100).toFixed(2) + '%' : '0%',
                hitCount: this.hitCount,
                missCount: this.missCount,
                totalItems: this.cache.size,
                totalSize: Array.from(this.cache.values()).reduce((sum, item) => sum + item.size, 0),
                oldestItem: this.getOldestItem(),
                newestItem: this.getNewestItem()
            };
        },

        getOldestItem() {
            let oldest = null;
            this.cache.forEach((item, key) => {
                if (!oldest || item.timestamp < oldest.timestamp) {
                    oldest = { key, timestamp: item.timestamp };
                }
            });
            return oldest;
        },

        getNewestItem() {
            let newest = null;
            this.cache.forEach((item, key) => {
                if (!newest || item.timestamp > newest.timestamp) {
                    newest = { key, timestamp: item.timestamp };
                }
            });
            return newest;
        },

        // Public API methods
        enableCaching() {
            this.enablePersistence = true;
        },

        disableCaching() {
            this.enablePersistence = false;
            this.clear();
        },

        setMaxAge(seconds) {
            this.maxAge = seconds * 1000;
        },

        setMaxSize(size) {
            this.maxSize = size;
            this.ensureCacheSize();
        }
    }));

    // Global cache store
    Alpine.store('cache', {
        manager: null,
        
        init() {
            this.manager = Alpine.reactive({
                ...Alpine.evaluate(document.body, () => 
                    Alpine.raw(Alpine.inferComponent('cacheManager', {}))()
                )
            });
            this.manager.init();
        },

        get(key, defaultValue) {
            return this.manager?.get(key, defaultValue);
        },

        set(key, data, options) {
            return this.manager?.set(key, data, options);
        },

        invalidate(keyOrPattern) {
            return this.manager?.invalidate(keyOrPattern);
        },

        getTemplate(templateId, forceRefresh) {
            return this.manager?.getTemplate(templateId, forceRefresh);
        },

        getProduct(productId, forceRefresh) {
            return this.manager?.getProduct(productId, forceRefresh);
        },

        getStats() {
            return this.manager?.getStats() || {};
        }
    });
});