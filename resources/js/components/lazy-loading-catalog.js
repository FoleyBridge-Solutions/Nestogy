/**
 * Lazy Loading Catalog Component
 * Implements efficient lazy loading for large product and service catalogs
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('lazyLoadingCatalog', (config = {}) => ({
        // Configuration
        pageSize: config.pageSize || 25,
        preloadThreshold: config.preloadThreshold || 5,
        cacheTimeout: config.cacheTimeout || 300000, // 5 minutes
        enableVirtualization: config.enableVirtualization !== false,
        enableImageLazyLoading: config.enableImageLazyLoading !== false,
        
        // Catalog state
        items: [],
        totalItems: 0,
        currentPage: 1,
        isLoading: false,
        hasMoreItems: true,
        loadedPages: new Set(),
        
        // Virtual scrolling state
        virtualScrolling: {
            enabled: false,
            itemHeight: 80,
            visibleStart: 0,
            visibleEnd: 0,
            scrollTop: 0,
            containerHeight: 400,
            bufferSize: 5
        },
        
        // Search and filter state
        searchQuery: '',
        activeFilters: new Map(),
        categoryFilter: '',
        priceRange: { min: 0, max: 0 },
        sortBy: 'name',
        sortOrder: 'asc',
        
        // Cache management
        cache: new Map(),
        imageCache: new Map(),
        metadataCache: new Map(),
        
        // Performance tracking
        loadTimes: [],
        renderTimes: [],
        
        // Intersection observers
        itemObserver: null,
        imageObserver: null,
        
        // Loading states
        itemLoadingStates: new Map(),
        
        // Error handling
        errors: [],
        retryAttempts: new Map(),
        maxRetries: 3,
        
        // Initialize lazy loading catalog
        init() {
            this.setupIntersectionObservers();
            this.setupVirtualScrolling();
            this.loadInitialData();
            this.setupEventListeners();
            this.initializeCache();
        },
        
        // Setup intersection observers for lazy loading
        setupIntersectionObservers() {
            // Observer for item loading
            this.itemObserver = new IntersectionObserver(
                this.handleItemIntersection.bind(this),
                {
                    root: null,
                    rootMargin: '200px',
                    threshold: 0.1
                }
            );
            
            // Observer for image lazy loading
            if (this.enableImageLazyLoading) {
                this.imageObserver = new IntersectionObserver(
                    this.handleImageIntersection.bind(this),
                    {
                        root: null,
                        rootMargin: '50px',
                        threshold: 0.1
                    }
                );
            }
        },
        
        // Setup virtual scrolling if enabled
        setupVirtualScrolling() {
            if (!this.enableVirtualization) return;
            
            this.virtualScrolling.enabled = true;
            
            // Calculate visible range based on container
            this.$nextTick(() => {
                const container = this.$refs.catalogContainer;
                if (container) {
                    this.virtualScrolling.containerHeight = container.clientHeight;
                    this.updateVisibleRange();
                }
            });
        },
        
        // Setup event listeners
        setupEventListeners() {
            // Search input debouncing
            this.$watch('searchQuery', debounce((query) => {
                this.handleSearch(query);
            }, 300));
            
            // Filter changes
            this.$watch('categoryFilter', () => {
                this.resetAndReload();
            });
            
            this.$watch('sortBy', () => {
                this.resetAndReload();
            });
            
            this.$watch('sortOrder', () => {
                this.resetAndReload();
            });
            
            // Virtual scroll handling
            if (this.virtualScrolling.enabled) {
                this.$watch('virtualScrolling.scrollTop', () => {
                    this.updateVisibleRange();
                });
            }
            
            // Window resize for virtual scrolling
            window.addEventListener('resize', debounce(() => {
                if (this.virtualScrolling.enabled) {
                    this.updateContainerDimensions();
                }
            }, 150));
        },
        
        // Initialize cache
        initializeCache() {
            // Load cached data if available
            this.loadCachedData();
            
            // Setup cache cleanup
            setInterval(() => {
                this.cleanupExpiredCache();
            }, 60000); // Every minute
        },
        
        // Load initial data
        async loadInitialData() {
            await this.loadPage(1);
            
            // Preload next page if items are visible
            if (this.items.length < this.virtualScrolling.containerHeight / this.virtualScrolling.itemHeight) {
                await this.loadPage(2);
            }
        },
        
        // Load specific page
        async loadPage(page, force = false) {
            if (this.loadedPages.has(page) && !force) {
                return this.getCachedPage(page);
            }
            
            if (this.isLoading) return;
            
            const startTime = performance.now();
            this.isLoading = true;
            this.errors = [];
            
            try {
                const cacheKey = this.generateCacheKey(page);
                
                // Check cache first
                if (!force && this.cache.has(cacheKey)) {
                    const cached = this.cache.get(cacheKey);
                    if (Date.now() - cached.timestamp < this.cacheTimeout) {
                        this.processCachedData(cached.data, page);
                        return cached.data;
                    }
                }
                
                // Fetch from API
                const response = await this.fetchPageData(page);
                
                if (response.success) {
                    // Cache the response
                    this.cache.set(cacheKey, {
                        data: response.data,
                        timestamp: Date.now()
                    });
                    
                    this.processPageData(response.data, page);
                    this.loadedPages.add(page);
                    
                    // Track performance
                    const loadTime = performance.now() - startTime;
                    this.loadTimes.push(loadTime);
                    
                    return response.data;
                } else {
                    throw new Error(response.message || 'Failed to load data');
                }
                
            } catch (error) {
                this.handleLoadError(error, page);
                return null;
            } finally {
                this.isLoading = false;
            }
        },
        
        // Fetch page data from API
        async fetchPageData(page) {
            const params = new URLSearchParams({
                page: page,
                per_page: this.pageSize,
                search: this.searchQuery,
                category: this.categoryFilter,
                sort_by: this.sortBy,
                sort_order: this.sortOrder,
                min_price: this.priceRange.min,
                max_price: this.priceRange.max
            });
            
            // Add active filters
            this.activeFilters.forEach((value, key) => {
                params.append(`filter[${key}]`, value);
            });
            
            const response = await fetch(`/api/catalog/products?${params.toString()}`);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            return {
                success: true,
                data: data
            };
        },
        
        // Process page data
        processPageData(data, page) {
            if (page === 1) {
                // Reset for new search/filter
                this.items = [];
                this.totalItems = data.total || 0;
            }
            
            // Add new items
            const startIndex = (page - 1) * this.pageSize;
            data.items.forEach((item, index) => {
                const globalIndex = startIndex + index;
                this.items[globalIndex] = this.processItem(item);
            });
            
            // Update state
            this.currentPage = Math.max(this.currentPage, page);
            this.hasMoreItems = this.items.length < this.totalItems;
            
            // Setup lazy loading for new items
            this.$nextTick(() => {
                this.setupItemLazyLoading();
            });
        },
        
        // Process individual item
        processItem(item) {
            return {
                ...item,
                _loaded: false,
                _loading: false,
                _error: null,
                _imageLoaded: false,
                _metadata: null
            };
        },
        
        // Process cached data
        processCachedData(data, page) {
            this.processPageData(data, page);
        },
        
        // Handle item intersection (for detailed loading)
        handleItemIntersection(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const itemId = entry.target.dataset.itemId;
                    if (itemId) {
                        this.loadItemDetails(itemId);
                    }
                }
            });
        },
        
        // Handle image intersection (for image lazy loading)
        handleImageIntersection(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    const src = img.dataset.src;
                    
                    if (src && !img.src) {
                        this.loadImage(img, src);
                    }
                }
            });
        },
        
        // Load item details on demand
        async loadItemDetails(itemId) {
            const item = this.items.find(i => i.id == itemId);
            if (!item || item._loaded || item._loading) return;
            
            item._loading = true;
            
            try {
                const cacheKey = `item_${itemId}`;
                
                // Check cache
                if (this.metadataCache.has(cacheKey)) {
                    const cached = this.metadataCache.get(cacheKey);
                    if (Date.now() - cached.timestamp < this.cacheTimeout) {
                        Object.assign(item, cached.data);
                        item._loaded = true;
                        item._loading = false;
                        return;
                    }
                }
                
                // Fetch details
                const response = await fetch(`/api/catalog/products/${itemId}/details`);
                if (response.ok) {
                    const details = await response.json();
                    
                    // Cache the details
                    this.metadataCache.set(cacheKey, {
                        data: details,
                        timestamp: Date.now()
                    });
                    
                    // Update item
                    Object.assign(item, details);
                    item._loaded = true;
                }
                
            } catch (error) {
                item._error = error.message;
                console.error('Failed to load item details:', error);
            } finally {
                item._loading = false;
            }
        },
        
        // Load image with lazy loading
        async loadImage(img, src) {
            try {
                // Check image cache
                if (this.imageCache.has(src)) {
                    img.src = this.imageCache.get(src);
                    img.classList.add('loaded');
                    return;
                }
                
                // Create new image for preloading
                const preloadImg = new Image();
                preloadImg.onload = () => {
                    this.imageCache.set(src, src);
                    img.src = src;
                    img.classList.add('loaded');
                    this.imageObserver.unobserve(img);
                };
                
                preloadImg.onerror = () => {
                    img.classList.add('error');
                    this.imageObserver.unobserve(img);
                };
                
                preloadImg.src = src;
                
            } catch (error) {
                console.error('Failed to load image:', error);
                img.classList.add('error');
            }
        },
        
        // Setup lazy loading for items
        setupItemLazyLoading() {
            const itemElements = document.querySelectorAll('[data-item-id]:not([data-observed])');
            
            itemElements.forEach(element => {
                element.setAttribute('data-observed', 'true');
                this.itemObserver.observe(element);
                
                // Also observe images within items
                if (this.enableImageLazyLoading) {
                    const images = element.querySelectorAll('img[data-src]');
                    images.forEach(img => {
                        this.imageObserver.observe(img);
                    });
                }
            });
        },
        
        // Handle search
        async handleSearch(query) {
            this.searchQuery = query;
            await this.resetAndReload();
        },
        
        // Reset and reload catalog
        async resetAndReload() {
            this.items = [];
            this.currentPage = 1;
            this.loadedPages.clear();
            this.hasMoreItems = true;
            this.clearCache();
            
            await this.loadPage(1);
        },
        
        // Load more items (infinite scroll)
        async loadMoreItems() {
            if (this.isLoading || !this.hasMoreItems) return;
            
            const nextPage = this.currentPage + 1;
            await this.loadPage(nextPage);
        },
        
        // Check if should load more items
        shouldLoadMore() {
            if (!this.hasMoreItems || this.isLoading) return false;
            
            // Check if user is near the end
            const container = this.$refs.catalogContainer;
            if (!container) return false;
            
            const scrollPosition = container.scrollTop + container.clientHeight;
            const scrollHeight = container.scrollHeight;
            const threshold = scrollHeight - (this.preloadThreshold * this.virtualScrolling.itemHeight);
            
            return scrollPosition >= threshold;
        },
        
        // Update visible range for virtual scrolling
        updateVisibleRange() {
            if (!this.virtualScrolling.enabled) return;
            
            const itemHeight = this.virtualScrolling.itemHeight;
            const bufferSize = this.virtualScrolling.bufferSize;
            const containerHeight = this.virtualScrolling.containerHeight;
            const scrollTop = this.virtualScrolling.scrollTop;
            
            const visibleStart = Math.floor(scrollTop / itemHeight);
            const visibleCount = Math.ceil(containerHeight / itemHeight);
            
            this.virtualScrolling.visibleStart = Math.max(0, visibleStart - bufferSize);
            this.virtualScrolling.visibleEnd = Math.min(
                this.items.length - 1,
                visibleStart + visibleCount + bufferSize
            );
        },
        
        // Update container dimensions
        updateContainerDimensions() {
            const container = this.$refs.catalogContainer;
            if (container) {
                this.virtualScrolling.containerHeight = container.clientHeight;
                this.updateVisibleRange();
            }
        },
        
        // Generate cache key
        generateCacheKey(page) {
            const filters = Array.from(this.activeFilters.entries()).sort();
            return `page_${page}_${this.searchQuery}_${this.categoryFilter}_${this.sortBy}_${this.sortOrder}_${JSON.stringify(filters)}`;
        },
        
        // Get cached page data
        getCachedPage(page) {
            const cacheKey = this.generateCacheKey(page);
            const cached = this.cache.get(cacheKey);
            
            if (cached && Date.now() - cached.timestamp < this.cacheTimeout) {
                return cached.data;
            }
            
            return null;
        },
        
        // Load cached data on init
        loadCachedData() {
            try {
                const savedCache = localStorage.getItem('catalog_cache');
                if (savedCache) {
                    const cacheData = JSON.parse(savedCache);
                    
                    // Restore non-expired cache entries
                    Object.entries(cacheData).forEach(([key, value]) => {
                        if (Date.now() - value.timestamp < this.cacheTimeout) {
                            this.cache.set(key, value);
                        }
                    });
                }
            } catch (error) {
                console.warn('Failed to load cached data:', error);
            }
        },
        
        // Save cache to localStorage
        saveCacheData() {
            try {
                const cacheData = {};
                this.cache.forEach((value, key) => {
                    cacheData[key] = value;
                });
                
                localStorage.setItem('catalog_cache', JSON.stringify(cacheData));
            } catch (error) {
                console.warn('Failed to save cache data:', error);
            }
        },
        
        // Clear cache
        clearCache() {
            this.cache.clear();
            this.imageCache.clear();
            this.metadataCache.clear();
            localStorage.removeItem('catalog_cache');
        },
        
        // Cleanup expired cache entries
        cleanupExpiredCache() {
            const now = Date.now();
            
            // Cleanup main cache
            this.cache.forEach((value, key) => {
                if (now - value.timestamp > this.cacheTimeout) {
                    this.cache.delete(key);
                }
            });
            
            // Cleanup metadata cache
            this.metadataCache.forEach((value, key) => {
                if (now - value.timestamp > this.cacheTimeout) {
                    this.metadataCache.delete(key);
                }
            });
            
            // Save cleaned cache
            this.saveCacheData();
        },
        
        // Handle load errors
        handleLoadError(error, page) {
            console.error(`Failed to load page ${page}:`, error);
            
            this.errors.push({
                page,
                message: error.message,
                timestamp: Date.now()
            });
            
            // Implement retry logic
            const retryKey = `page_${page}`;
            const attempts = this.retryAttempts.get(retryKey) || 0;
            
            if (attempts < this.maxRetries) {
                this.retryAttempts.set(retryKey, attempts + 1);
                
                // Retry after delay
                setTimeout(() => {
                    this.loadPage(page, true);
                }, Math.pow(2, attempts) * 1000); // Exponential backoff
            }
        },
        
        // Add filter
        addFilter(key, value) {
            this.activeFilters.set(key, value);
            this.resetAndReload();
        },
        
        // Remove filter
        removeFilter(key) {
            this.activeFilters.delete(key);
            this.resetAndReload();
        },
        
        // Clear all filters
        clearFilters() {
            this.activeFilters.clear();
            this.categoryFilter = '';
            this.searchQuery = '';
            this.resetAndReload();
        },
        
        // Get visible items for virtual scrolling
        getVisibleItems() {
            if (!this.virtualScrolling.enabled) {
                return this.items;
            }
            
            return this.items.slice(
                this.virtualScrolling.visibleStart,
                this.virtualScrolling.visibleEnd + 1
            );
        },
        
        // Calculate total height for virtual scrolling
        getTotalHeight() {
            return this.items.length * this.virtualScrolling.itemHeight;
        },
        
        // Calculate offset for virtual scrolling
        getOffsetY() {
            return this.virtualScrolling.visibleStart * this.virtualScrolling.itemHeight;
        },
        
        // Computed properties
        get hasItems() {
            return this.items.length > 0;
        },
        
        get isSearching() {
            return this.searchQuery.length > 0;
        },
        
        get hasFilters() {
            return this.activeFilters.size > 0 || this.categoryFilter;
        },
        
        get loadingProgress() {
            return this.totalItems > 0 ? (this.items.length / this.totalItems) * 100 : 0;
        },
        
        get averageLoadTime() {
            return this.loadTimes.length > 0 
                ? this.loadTimes.reduce((a, b) => a + b, 0) / this.loadTimes.length 
                : 0;
        },
        
        get cacheHitRate() {
            const totalRequests = this.loadTimes.length;
            const cacheHits = this.cache.size;
            return totalRequests > 0 ? (cacheHits / totalRequests) * 100 : 0;
        }
    }));
    
    // Utility function for debouncing
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
});