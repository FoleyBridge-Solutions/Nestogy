/**
 * Advanced Item Selector Component
 * Handles product/service selection with search, filtering, and catalog browsing
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('itemSelector', (config = {}) => ({
        // Configuration
        apiEndpoint: config.apiEndpoint || '/api/catalog',
        pageSize: config.pageSize || 20,
        enableVirtualization: config.enableVirtualization || false,
        
        // State
        loading: false,
        searchQuery: '',
        searchTimeout: null,
        selectedTab: 'products', // 'products', 'services', 'bundles'
        viewMode: 'grid', // 'grid', 'list'
        
        // Data
        products: [],
        services: [],
        bundles: [],
        categories: [],
        
        // Pagination
        currentPage: 1,
        totalPages: 1,
        totalItems: 0,
        
        // Filters
        filters: {
            category: '',
            priceRange: { min: '', max: '' },
            billingModel: '',
            availability: 'available',
            tags: []
        },
        showFilters: false,
        
        // Sorting
        sortBy: 'name',
        sortOrder: 'asc',
        sortOptions: [
            { key: 'name', label: 'Name' },
            { key: 'price', label: 'Price' },
            { key: 'created_at', label: 'Newest' },
            { key: 'popularity', label: 'Most Popular' }
        ],
        
        // Selection
        selectedItems: new Map(),
        
        // Cache
        cache: new Map(),
        cacheTimeout: 300000, // 5 minutes
        
        // Mobile optimization
        isMobile: false,
        touchStartY: 0,
        
        // Virtualization (for large catalogs)
        virtualizedItems: [],
        visibleStartIndex: 0,
        visibleEndIndex: 0,
        itemHeight: 200,
        containerHeight: 600,
        
        // Initialize component
        init() {
            this.detectMobile();
            this.setupEventListeners();
            this.loadInitialData();
            this.setupVirtualization();
        },

        // Mobile detection and setup
        detectMobile() {
            this.isMobile = window.innerWidth <= 768;
            window.addEventListener('resize', () => {
                this.isMobile = window.innerWidth <= 768;
                this.adjustForMobile();
            });
        },

        adjustForMobile() {
            if (this.isMobile) {
                this.viewMode = 'list'; // Force list view on mobile
                this.pageSize = 10; // Smaller page size for mobile
            }
        },

        // Event listeners setup
        setupEventListeners() {
            // Search debouncing
            this.$watch('searchQuery', (newQuery) => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.performSearch(newQuery);
                }, 300);
            });

            // Filter watching
            this.$watch('filters', () => {
                this.applyFilters();
            }, { deep: true });

            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.target.tagName === 'INPUT') return;
                
                switch (e.key) {
                    case '/':
                        e.preventDefault();
                        this.focusSearch();
                        break;
                    case 'Escape':
                        this.clearSearch();
                        break;
                }
            });

            // Infinite scroll for mobile
            if (this.isMobile) {
                this.setupInfiniteScroll();
            }
        },

        // Load initial data
        async loadInitialData() {
            await Promise.all([
                this.loadCategories(),
                this.loadItems()
            ]);
        },

        // Load categories (with caching)
        async loadCategories() {
            try {
                // Use cache manager if available
                if (this.$store.cache?.manager) {
                    this.categories = await this.$store.cache.manager.getCategories();
                } else {
                    // Fallback to direct fetch
                    const response = await fetch('/api/categories?type=product');
                    if (response.ok) {
                        const data = await response.json();
                        this.categories = data.data || data.categories || [];
                    }
                }
            } catch (error) {
                console.error('Failed to load categories:', error);
            }
        },

        // Load items based on current tab (with enhanced caching)
        async loadItems(page = 1) {
            const cacheKey = this.getCacheKey(page);
            
            try {
                this.loading = true;
                
                // Use cache manager if available
                if (this.$store.cache?.manager) {
                    const data = await this.$store.cache.manager.getOrFetch(
                        cacheKey,
                        async () => {
                            const params = this.buildQueryParams(page);
                            const response = await fetch(`${this.apiEndpoint}/${this.selectedTab}?${params}`);
                            if (!response.ok) throw new Error('Failed to load items');
                            return response.json();
                        },
                        { 
                            priority: 'high',
                            maxAge: this.cacheTimeout 
                        }
                    );
                    
                    this.applyItemData(data);
                } else {
                    // Fallback to local cache
                    if (this.cache.has(cacheKey)) {
                        const cached = this.cache.get(cacheKey);
                        if (Date.now() - cached.timestamp < this.cacheTimeout) {
                            this.applyItemData(cached.data);
                            return;
                        }
                    }

                    const params = this.buildQueryParams(page);
                    const response = await fetch(`${this.apiEndpoint}/${this.selectedTab}?${params}`);
                    
                    if (response.ok) {
                        const data = await response.json();
                        
                        // Cache the response locally
                        this.cache.set(cacheKey, {
                            data: data,
                            timestamp: Date.now()
                        });
                        
                        this.applyItemData(data);
                    } else {
                        throw new Error('Failed to load items');
                    }
                }
            } catch (error) {
                console.error('Failed to load items:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: 'Failed to load items'
                });
            } finally {
                this.loading = false;
            }
        },

        // Build query parameters
        buildQueryParams(page) {
            const params = new URLSearchParams({
                page: page,
                per_page: this.pageSize,
                sort_by: this.sortBy,
                sort_order: this.sortOrder
            });

            if (this.searchQuery) {
                params.append('search', this.searchQuery);
            }

            if (this.filters.category) {
                params.append('category', this.filters.category);
            }

            if (this.filters.priceRange.min) {
                params.append('price_min', this.filters.priceRange.min);
            }

            if (this.filters.priceRange.max) {
                params.append('price_max', this.filters.priceRange.max);
            }

            if (this.filters.billingModel) {
                params.append('billing_model', this.filters.billingModel);
            }

            if (this.filters.availability) {
                params.append('availability', this.filters.availability);
            }

            if (this.filters.tags.length > 0) {
                params.append('tags', this.filters.tags.join(','));
            }

            return params.toString();
        },

        // Apply loaded item data
        applyItemData(data) {
            const items = data.data || data.items || [];
            
            if (this.currentPage === 1) {
                // Replace items for new search/filter
                this[this.selectedTab] = items;
            } else {
                // Append items for pagination
                this[this.selectedTab] = [...this[this.selectedTab], ...items];
            }

            this.currentPage = data.current_page || this.currentPage;
            this.totalPages = data.last_page || Math.ceil((data.total || items.length) / this.pageSize);
            this.totalItems = data.total || items.length;

            // Update virtualization if enabled
            if (this.enableVirtualization) {
                this.updateVirtualization();
            }
        },

        // Cache management
        getCacheKey(page) {
            return `${this.selectedTab}_${page}_${this.searchQuery}_${JSON.stringify(this.filters)}_${this.sortBy}_${this.sortOrder}`;
        },

        clearCache() {
            this.cache.clear();
        },

        // Search functionality (with caching)
        performSearch(query) {
            this.currentPage = 1;
            
            // If using cache manager, use the search method
            if (this.$store.cache?.manager && query.trim()) {
                this.performCachedSearch(query);
            } else {
                this.clearCache();
                this.loadItems();
            }
        },

        // Cached search for products
        async performCachedSearch(query) {
            if (this.selectedTab !== 'products' && this.selectedTab !== 'services') {
                this.clearCache();
                this.loadItems();
                return;
            }

            try {
                this.loading = true;
                
                const searchMethod = this.selectedTab === 'products' ? 'searchProducts' : 'searchServices';
                const data = await this.$store.cache.manager[searchMethod](query, this.filters);
                
                this.applyItemData(data);
            } catch (error) {
                console.error('Cached search failed, falling back to regular search:', error);
                this.clearCache();
                this.loadItems();
            } finally {
                this.loading = false;
            }
        },

        focusSearch() {
            this.$refs.searchInput?.focus();
        },

        clearSearch() {
            this.searchQuery = '';
            this.focusSearch();
        },

        // Tab switching
        switchTab(tab) {
            if (this.selectedTab === tab) return;
            
            this.selectedTab = tab;
            this.currentPage = 1;
            this.loadItems();
        },

        // View mode
        toggleViewMode() {
            this.viewMode = this.viewMode === 'grid' ? 'list' : 'grid';
        },

        // Filtering
        applyFilters() {
            this.currentPage = 1;
            this.clearCache();
            this.loadItems();
        },

        clearFilters() {
            this.filters = {
                category: '',
                priceRange: { min: '', max: '' },
                billingModel: '',
                availability: 'available',
                tags: []
            };
        },

        get hasFiltersApplied() {
            return Object.values(this.filters).some(value => {
                if (typeof value === 'object' && value !== null) {
                    return Object.values(value).some(v => v !== '');
                }
                return value !== '' && value !== 'available';
            });
        },

        // Sorting
        changeSort(sortBy) {
            if (this.sortBy === sortBy) {
                this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = sortBy;
                this.sortOrder = 'asc';
            }
            
            this.currentPage = 1;
            this.clearCache();
            this.loadItems();
        },

        // Pagination
        goToPage(page) {
            if (page < 1 || page > this.totalPages || page === this.currentPage) return;
            
            this.currentPage = page;
            this.loadItems(page);
        },

        nextPage() {
            this.goToPage(this.currentPage + 1);
        },

        previousPage() {
            this.goToPage(this.currentPage - 1);
        },

        get paginationRange() {
            const range = [];
            const showPages = 5;
            let start = Math.max(1, this.currentPage - Math.floor(showPages / 2));
            let end = Math.min(this.totalPages, start + showPages - 1);
            
            if (end - start < showPages - 1) {
                start = Math.max(1, end - showPages + 1);
            }
            
            for (let i = start; i <= end; i++) {
                range.push(i);
            }
            
            return range;
        },

        // Infinite scroll (mobile)
        setupInfiniteScroll() {
            let scrollContainer = null;
            
            this.$nextTick(() => {
                scrollContainer = this.$refs.itemsContainer;
                if (scrollContainer) {
                    scrollContainer.addEventListener('scroll', this.handleScroll.bind(this));
                }
            });
        },

        handleScroll(e) {
            const container = e.target;
            const scrollTop = container.scrollTop;
            const scrollHeight = container.scrollHeight;
            const clientHeight = container.clientHeight;
            
            // Load more when 200px from bottom
            if (scrollTop + clientHeight >= scrollHeight - 200) {
                if (!this.loading && this.currentPage < this.totalPages) {
                    this.nextPage();
                }
            }
        },

        // Item selection
        toggleItem(item) {
            const itemKey = `${item.type}_${item.id}`;
            
            if (this.selectedItems.has(itemKey)) {
                this.selectedItems.delete(itemKey);
                this.$store.quote.removeItem(this.getQuoteItemId(item));
            } else {
                this.selectedItems.set(itemKey, item);
                this.addItemToQuote(item);
            }
        },

        isSelected(itemId, itemType = 'product') {
            const itemKey = `${itemType}_${itemId}`;
            return this.selectedItems.has(itemKey);
        },

        addItemToQuote(item) {
            // Add item to quote store
            this.$store.quote.addItem({
                id: item.id,
                product_id: item.type === 'product' ? item.id : null,
                service_id: item.type === 'service' ? item.id : null,
                bundle_id: item.type === 'bundle' ? item.id : null,
                name: item.name,
                description: item.description || '',
                quantity: 1,
                unit_price: this.getItemPrice(item),
                billing_cycle: item.billing_cycle || 'one_time',
                category: item.category,
                type: item.type || 'product'
            });
        },

        getQuoteItemId(item) {
            // Find the corresponding quote item ID
            const quoteItem = this.$store.quote.selectedItems.find(qi => 
                (qi.product_id && qi.product_id === item.id && item.type === 'product') ||
                (qi.service_id && qi.service_id === item.id && item.type === 'service') ||
                (qi.bundle_id && qi.bundle_id === item.id && item.type === 'bundle')
            );
            return quoteItem ? quoteItem.id : null;
        },

        // Item details and actions
        showItemDetails(item) {
            this.$dispatch('show-item-details', { item });
        },

        getItemPrice(item) {
            // Get client-specific pricing if available
            const clientId = this.$store.quote.document.client_id;
            if (clientId && item.client_pricing && item.client_pricing[clientId]) {
                return item.client_pricing[clientId];
            }
            
            // Return base price or promotional price
            return item.promotional_price || item.base_price || item.price || 0;
        },

        formatPrice(price) {
            return this.$store.quote.formatCurrency(price);
        },

        formatBillingCycle(cycle) {
            const cycles = {
                'one_time': 'One-time',
                'monthly': 'Monthly',
                'quarterly': 'Quarterly',
                'semi_annually': 'Semi-annually',
                'annually': 'Annually'
            };
            return cycles[cycle] || cycle;
        },

        getDiscountBadge(item) {
            if (item.promotional_price && item.base_price) {
                const discount = Math.round(((item.base_price - item.promotional_price) / item.base_price) * 100);
                return discount > 0 ? `${discount}% OFF` : null;
            }
            return null;
        },

        // Bundle operations
        addBundle(bundle) {
            this.$dispatch('configure-bundle', { bundle });
        },

        // Virtualization (for large catalogs)
        setupVirtualization() {
            if (!this.enableVirtualization) return;
            
            this.$watch('selectedTab', () => {
                this.updateVirtualization();
            });
        },

        updateVirtualization() {
            if (!this.enableVirtualization) return;
            
            const items = this[this.selectedTab] || [];
            const visibleCount = Math.ceil(this.containerHeight / this.itemHeight);
            
            this.visibleStartIndex = Math.max(0, Math.floor(this.scrollTop / this.itemHeight) - 5);
            this.visibleEndIndex = Math.min(items.length, this.visibleStartIndex + visibleCount + 10);
            
            this.virtualizedItems = items.slice(this.visibleStartIndex, this.visibleEndIndex);
        },

        getVirtualizedStyle(index) {
            const actualIndex = this.visibleStartIndex + index;
            return {
                transform: `translateY(${actualIndex * this.itemHeight}px)`,
                height: `${this.itemHeight}px`
            };
        },

        // Touch interactions (mobile)
        handleTouchStart(e, item) {
            if (!this.isMobile) return;
            this.touchStartY = e.touches[0].clientY;
        },

        handleTouchEnd(e, item) {
            if (!this.isMobile) return;
            
            const touchEndY = e.changedTouches[0].clientY;
            const deltaY = Math.abs(touchEndY - this.touchStartY);
            
            // If minimal movement, treat as tap
            if (deltaY < 10) {
                this.toggleItem(item);
            }
        },

        // Quick actions
        addMultipleItems(items) {
            items.forEach(item => {
                if (!this.isSelected(item.id, item.type)) {
                    this.toggleItem(item);
                }
            });
        },

        // Smart suggestions
        getSuggestedItems() {
            const clientId = this.$store.quote.document.client_id;
            const categoryId = this.$store.quote.document.category_id;
            
            if (!clientId) return [];
            
            // Filter items based on client history and category
            return this[this.selectedTab].filter(item => {
                if (categoryId && item.category_id === categoryId) return true;
                if (item.frequently_ordered_by?.includes(clientId)) return true;
                return false;
            }).slice(0, 5);
        },

        // Export selected items
        exportSelection() {
            const selection = Array.from(this.selectedItems.values());
            const csvContent = this.generateCSV(selection);
            this.downloadCSV(csvContent, 'selected-items.csv');
        },

        generateCSV(items) {
            const headers = ['Name', 'Type', 'Price', 'Billing Cycle'];
            const rows = items.map(item => [
                item.name,
                item.type,
                this.getItemPrice(item),
                this.formatBillingCycle(item.billing_cycle)
            ]);
            
            return [headers, ...rows].map(row => row.join(',')).join('\n');
        },

        downloadCSV(content, filename) {
            const blob = new Blob([content], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            URL.revokeObjectURL(url);
        },

        // Performance optimization
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        // Cleanup
        destroy() {
            clearTimeout(this.searchTimeout);
            this.clearCache();
            
            // Remove event listeners
            if (this.$refs.itemsContainer) {
                this.$refs.itemsContainer.removeEventListener('scroll', this.handleScroll);
            }
        }
    }));
});