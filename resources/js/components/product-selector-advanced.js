// Advanced Product Selector Component with Search, Filtering, and Smart Selection
export default function productSelectorAdvanced() {
    const component = {
        // State
        products: [],
        services: [],
        bundles: [],
        selectedItems: [],
        searchQuery: '',
        activeTab: 'products',
        filters: {
            category: '',
            billingModel: '',
            priceRange: { min: 0, max: 10000 },
            type: ''
        },
        
        // UI State
        loading: false,
        showFilters: false,
        viewMode: 'grid', // grid or list
        sortBy: 'name',
        sortOrder: 'asc',
        
        // Pagination
        currentPage: 1,
        perPage: 12,
        totalItems: 0,
        
        // Product Details
        selectedProduct: null,
        showProductModal: false,
        
        // Service Configuration
        configurationItem: null,
        showConfigModal: false,
        
        // Pricing
        pricingCache: {},
        clientId: null,
        
        // Categories
        categories: [],
        
        // Sorting state
        productSort: { field: 'name', order: 'asc' },
        serviceSort: { field: 'name', order: 'asc' },
        bundleSort: { field: 'name', order: 'asc' },
        
        // Initialization flag to prevent multiple inits
        _initialized: false,
        
        // Initialize
        init() {
            // Prevent multiple initializations
            if (this._initialized) {
                return;
            }
            
            this._initialized = true;
            
            // Ensure modal is closed on init
            this.showProductModal = false;
            this.selectedProduct = null;
            
            this.loadProducts();
            this.loadCategories();
            this.setupEventListeners();
            
            // Watch for client changes
            this.$watch('clientId', () => {
                this.refreshPricing();
                this.loadProducts(); // Reload products when client changes
            });
            
            // Watch for filter changes
            this.$watch('filters', () => {
                this.applyFilters();
            }, { deep: true });
        },
        
        // Setup event listeners
        setupEventListeners() {
            // Listen for client selection from parent
            window.addEventListener('client-selected', (e) => {
                this.clientId = e.detail.clientId;
            });
            
            // Listen for copied items sync
            window.addEventListener('sync-copied-items', (e) => {
                this.handleCopiedItemsSync(e.detail);
            });
            
            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.key === '/' && !this.isInputFocused()) {
                    e.preventDefault();
                    this.$refs.searchInput?.focus();
                }
                
                if (e.key === 'Escape' && this.showProductModal) {
                    this.closeProductModal();
                }
            });
        },
        
        // Load products from API
        async loadProducts() {
            this.loading = true;
            
            // Ensure modal stays closed during data loading
            this.forceCloseModal();
            
            try {
                const params = new URLSearchParams({
                    search: this.searchQuery,
                    category: this.filters.category,
                    billing_model: this.filters.billingModel,
                    min_price: this.filters.priceRange.min,
                    max_price: this.filters.priceRange.max,
                    sort_by: this.sortBy,
                    sort_order: this.sortOrder,
                    page: this.currentPage,
                    per_page: this.perPage,
                    client_id: this.clientId || '',
                    type: this.activeTab === 'products' ? 'product' : 
                      this.activeTab === 'services' ? 'service' : 
                      this.activeTab === 'bundles' ? 'bundle' : this.activeTab
                });
                
                console.log('Product Selector: Making request to:', `/products/search?${params}`);
                console.log('Product Selector: Active tab:', this.activeTab);
                console.log('Product Selector: Current products count:', this.products.length);
                
                const response = await fetch(`/products/search?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                
                console.log('Product Selector: Response status:', response.status);
                const data = await response.json();
                console.log('Product Selector: Response data:', data);
                
                if (this.activeTab === 'products') {
                    this.products = data.products || [];
                    this.totalItems = data.total || 0;
                } else if (this.activeTab === 'services') {
                    this.services = data.services || [];
                    this.totalItems = data.total || 0;
                } else if (this.activeTab === 'bundles') {
                    this.bundles = data.bundles || [];
                    this.totalItems = data.total || 0;
                }
                
                // Update pricing for loaded items
                await this.loadPricing(data.products || []);
                
                // Ensure modal is still closed after loading
                this.forceCloseModal();
            } catch (error) {
                console.error('Error loading products:', error);
                this.showNotification('Failed to load products', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        // Load categories
        async loadCategories() {
            try {
                console.log('Product Selector: Loading categories...');
                const response = await fetch('/products/categories', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                console.log('Product Selector: Categories response status:', response.status);
                const data = await response.json();
                console.log('Product Selector: Categories response data:', data);
                this.categories = data.categories || [];
                console.log('Product Selector: Categories set to:', this.categories);
            } catch (error) {
                console.error('Error loading categories:', error);
                this.categories = []; // Ensure it's always an array
            }
        },
        
        // Load pricing for products
        async loadPricing(products) {
            if (!this.clientId || products.length === 0) return;
            
            const productIds = products.map(p => p.id);
            const uncachedIds = productIds.filter(id => !this.pricingCache[id]);
            
            if (uncachedIds.length === 0) return;
            
            try {
                const response = await fetch('/api/products/pricing', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        product_ids: uncachedIds,
                        client_id: this.clientId
                    })
                });
                
                const data = await response.json();
                
                // Cache pricing data
                Object.assign(this.pricingCache, data.pricing);
            } catch (error) {
                console.error('Error loading pricing:', error);
            }
        },
        
        // Quick search
        async quickSearch() {
            if (this.searchQuery.length < 2) {
                this.loadProducts();
                return;
            }
            
            this.loading = true;
            
            try {
                const response = await fetch(`/api/products/quick-search?q=${encodeURIComponent(this.searchQuery)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                const data = await response.json();
                
                this.products = data.products || [];
                this.services = data.services || [];
                this.bundles = data.bundles || [];
                
                // Auto-switch to tab with results
                if (this.products.length > 0) {
                    this.activeTab = 'products';
                } else if (this.services.length > 0) {
                    this.activeTab = 'services';
                } else if (this.bundles.length > 0) {
                    this.activeTab = 'bundles';
                }
            } catch (error) {
                console.error('Error in quick search:', error);
            } finally {
                this.loading = false;
            }
        },
        
        // Select/deselect item
        toggleItem(item, type = 'product') {
            const index = this.selectedItems.findIndex(i => 
                i.id === item.id && i.type === type
            );
            
            if (index > -1) {
                this.selectedItems.splice(index, 1);
            } else {
                // Get pricing if available
                const pricing = this.getPricing(item.id);
                
                // Determine service type for tax calculations
                const serviceType = this.determineServiceType(item);
                
                const selectedItem = {
                    id: item.id,
                    type: type,
                    name: item.name,
                    sku: item.sku,
                    quantity: 1,
                    base_price: item.base_price,
                    unit_price: pricing?.unit_price || item.base_price,
                    subtotal: pricing?.subtotal || item.base_price,
                    billing_model: item.billing_model,
                    billing_cycle: item.billing_cycle,
                    service_type: serviceType,
                    service_data: this.getDefaultServiceData(serviceType),
                    category_id: item.category_id,
                    product_id: item.id,
                    // Initialize tax-related properties for advanced tax engine
                    tax_amount: 0,
                    tax_rate: 0,
                    tax_breakdown: [],
                    total: pricing?.subtotal || item.base_price,
                    engine_used: null,
                    jurisdictions: [],
                    ...item
                };
                
                this.selectedItems.push(selectedItem);
                
                // Calculate advanced taxes immediately for the new item
                if (this.clientId) {
                    this.recalculatePricing(selectedItem);
                }
                
                // Show service configuration modal if needed
                if (this.needsServiceConfiguration(serviceType)) {
                    this.showServiceConfigModal(selectedItem);
                }
            }
            
            this.emitSelectionChange();
        },
        
        // Check if item is selected
        isSelected(itemId, type = 'product') {
            return this.selectedItems.some(i => 
                i.id === itemId && i.type === type
            );
        },
        
        // Update quantity for selected item
        updateQuantity(itemId, quantity) {
            const item = this.selectedItems.find(i => i.id === itemId);
            if (!item) return;
            
            item.quantity = Math.max(1, parseInt(quantity) || 1);
            
            // Recalculate pricing
            this.recalculatePricing(item);
            this.emitSelectionChange();
        },
        
        // Recalculate pricing for an item using advanced tax engine
        async recalculatePricing(item) {
            if (!this.clientId) {
                item.subtotal = item.base_price * item.quantity;
                item.tax_amount = 0;
                item.total = item.subtotal;
                return;
            }
            
            try {
                // Prepare tax data based on service type and category
                const taxData = this.prepareTaxData(item);
                
                const response = await fetch('/api/tax-engine/calculate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        base_price: item.base_price,
                        quantity: item.quantity,
                        product_id: item.product_id || item.id,
                        category_id: item.category_id,
                        category_type: item.service_type || 'general',
                        customer_id: this.clientId,
                        tax_data: taxData
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const data = result.data;
                    
                    // Update item with advanced tax calculation results
                    item.unit_price = data.base_price / data.quantity;
                    item.subtotal = data.subtotal;
                    item.tax_amount = data.tax_amount;
                    item.tax_rate = data.tax_rate;
                    item.tax_breakdown = data.tax_breakdown;
                    item.total = data.total;
                    item.engine_used = data.engine_used;
                    item.jurisdictions = data.jurisdictions;
                    
                    // Update cache with full calculation result
                    this.pricingCache[item.id] = data;
                    
                    console.log(`Advanced tax calculation completed for ${item.name}:`, {
                        subtotal: data.subtotal,
                        tax_amount: data.tax_amount,
                        tax_rate: data.tax_rate,
                        total: data.total,
                        engine_used: data.engine_used
                    });
                } else {
                    console.error('Tax calculation failed:', result.error);
                    // Fallback to basic calculation
                    item.subtotal = item.base_price * item.quantity;
                    item.tax_amount = 0;
                    item.total = item.subtotal;
                }
            } catch (error) {
                console.error('Error recalculating price with advanced tax engine:', error);
                // Fallback to basic calculation
                item.subtotal = item.base_price * item.quantity;
                item.tax_amount = 0;
                item.total = item.subtotal;
            }
        },
        
        // Show product details modal
        async showProductDetails(product) {
            // Show modal immediately with loading state
            this.showProductModal = true;
            this.selectedProduct = null;
            
            // Load additional details
            try {
                const response = await fetch(`/products/${product.id}/details`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                this.selectedProduct = {
                    ...product,
                    ...data.product,
                    compatible_products: data.compatible_products,
                    pricing_tiers: data.pricing_tiers,
                    availability: data.availability
                };
            } catch (error) {
                console.error('Error loading product details:', error);
                this.showNotification('Failed to load product details. Please try again.', 'error');
                
                // Close modal on error
                this.closeProductModal();
            }
        },
        
        // Close product modal
        closeProductModal() {
            this.showProductModal = false;
            this.selectedProduct = null;
            
            // Defensive cleanup in case there are any lingering states
            const modalBackdrop = document.querySelector('.modal-backdrop');
            if (modalBackdrop) {
                modalBackdrop.remove();
            }
            
            // Ensure body doesn't have modal-open class stuck
            document.body.classList.remove('modal-open');
            
            // Clear any overflow hidden that might be stuck
            document.body.style.overflow = '';
        },
        
        // Force close modal - more defensive version
        forceCloseModal() {
            this.showProductModal = false;
            this.selectedProduct = null;
            
            // Remove any modal-related classes or elements
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.style.display = 'none';
                modal.classList.remove('show', 'd-block');
            });
            
            // Remove backdrops
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());
            
            // Clean up body
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        },
        
        // Apply filters
        applyFilters() {
            this.currentPage = 1;
            this.loadProducts();
        },
        
        // Clear filters
        clearFilters() {
            this.filters = {
                category: '',
                billingModel: '',
                priceRange: { min: 0, max: 10000 },
                type: ''
            };
            this.searchQuery = '';
            this.loadProducts();
        },
        
        // Change sort
        changeSort(field) {
            if (this.sortBy === field) {
                this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = field;
                this.sortOrder = 'asc';
            }
            this.loadProducts();
        },
        
        // Switch tab
        switchTab(tab) {
            this.activeTab = tab;
            this.currentPage = 1;
            this.loadProducts();
        },
        
        // Add bundle to selection
        async addBundle(bundle) {
            // Load bundle details with products
            try {
                const response = await fetch(`/api/bundles/${bundle.id}/details`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                const data = await response.json();
                
                // Show bundle configuration modal
                this.showBundleConfiguration(data.bundle);
            } catch (error) {
                console.error('Error loading bundle:', error);
                this.showNotification('Failed to load bundle details', 'error');
            }
        },
        
        // Show bundle configuration
        showBundleConfiguration(bundle) {
            // This would open a modal for configuring the bundle
            this.$dispatch('show-bundle-config', { bundle });
        },
        
        // Get pricing for a product
        getPricing(productId) {
            return this.pricingCache[productId] || null;
        },
        
        // Get selected item data for display purposes
        getSelectedItem(itemId, type = 'product') {
            return this.selectedItems.find(item => 
                item.id === itemId && item.type === type
            );
        },
        
        // Format price
        formatPrice(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount || 0);
        },
        
        // Sort products
        sortProducts(field) {
            this.changeSort(field);
        },
        
        // Sort services
        sortServices(field) {
            this.changeSort(field);
        },
        
        // Change page
        changePage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
                this.loadProducts();
            }
        },
        
        // Check if item is selected
        isSelected(itemId, type) {
            return this.selectedItems.some(item => 
                (type === 'product' && item.product_id == itemId) ||
                (type === 'service' && item.service_id == itemId) ||
                (type === 'bundle' && item.bundle_id == itemId)
            );
        },
        
        // Show product details
        showProductDetails(product) {
            this.selectedProduct = product;
            this.showProductModal = true;
        },
        
        // Format billing cycle
        formatBillingCycle(cycle) {
            const cycles = {
                'one_time': 'One-time',
                'hourly': 'Per Hour',
                'daily': 'Per Day',
                'weekly': 'Per Week',
                'monthly': 'Per Month',
                'quarterly': 'Per Quarter',
                'semi_annually': 'Semi-Annually',
                'annually': 'Per Year'
            };
            return cycles[cycle] || cycle;
        },
        
        // Get discount badge
        getDiscountBadge(item) {
            const pricing = this.getPricing(item.id);
            if (!pricing || !pricing.savings_percentage) return null;
            
            const percentage = Math.round(pricing.savings_percentage);
            if (percentage > 0) {
                return `${percentage}% OFF`;
            }
            return null;
        },
        
        // Determine service type from item
        determineServiceType(item) {
            const category = item.category?.toLowerCase() || '';
            const name = item.name?.toLowerCase() || '';
            
            // Check category first
            if (category.includes('voip') || category.includes('hosted') || category.includes('pbx')) {
                return 'voip';
            }
            if (category.includes('telecom') || category.includes('sip') || category.includes('did')) {
                return 'telecom';
            }
            if (category.includes('cloud') || category.includes('hosting')) {
                return 'cloud';
            }
            if (category.includes('saas') || category.includes('software')) {
                return 'saas';
            }
            
            // Check name as fallback
            if (name.includes('voip') || name.includes('hosted') || name.includes('pbx')) {
                return 'voip';
            }
            if (name.includes('telecom') || name.includes('phone') || name.includes('sip')) {
                return 'telecom';
            }
            
            return 'general';
        },
        
        // Prepare tax data for advanced tax engine calculation
        prepareTaxData(item) {
            const serviceType = item.service_type || this.determineServiceType(item);
            const serviceData = item.service_data || {};
            
            // Base tax data
            const taxData = {
                service_type: serviceType
            };
            
            // Add service-specific tax data based on service type
            switch (serviceType) {
                case 'voip':
                case 'telecom':
                    taxData.line_count = serviceData.line_count || 1;
                    taxData.extensions = serviceData.extensions || 1;
                    taxData.minutes = serviceData.minutes || 0;
                    break;
                    
                case 'cloud':
                case 'hosting':
                    taxData.storage_gb = serviceData.storage_gb || 0;
                    taxData.bandwidth_gb = serviceData.bandwidth_gb || 0;
                    taxData.user_count = serviceData.user_count || 1;
                    break;
                    
                case 'saas':
                case 'software':
                    taxData.user_count = serviceData.user_count || 1;
                    taxData.features = serviceData.features || [];
                    break;
                    
                default:
                    // For general products, we may still need basic tax data
                    if (serviceData.user_count) {
                        taxData.user_count = serviceData.user_count;
                    }
                    break;
            }
            
            // Add item-specific metadata that might affect tax calculation
            if (item.billing_model) {
                taxData.billing_model = item.billing_model;
            }
            if (item.billing_cycle) {
                taxData.billing_cycle = item.billing_cycle;
            }
            
            return taxData;
        },
        
        // Get default service data based on service type
        getDefaultServiceData(serviceType) {
            switch (serviceType) {
                case 'voip':
                case 'telecom':
                    return {
                        line_count: 1,
                        extensions: 1,
                        minutes: 0
                    };
                case 'cloud':
                    return {
                        storage_gb: 0,
                        bandwidth_gb: 0,
                        user_count: 1
                    };
                case 'saas':
                    return {
                        user_count: 1,
                        features: []
                    };
                default:
                    return {};
            }
        },
        
        // Check if service needs configuration
        needsServiceConfiguration(serviceType) {
            return ['voip', 'telecom'].includes(serviceType);
        },
        
        // Show service configuration modal
        showServiceConfigModal(item) {
            this.configurationItem = item;
            this.showConfigModal = true;
        },
        
        // Update service data for an item
        updateServiceData(itemId, serviceData) {
            const item = this.selectedItems.find(i => i.id === itemId);
            if (item) {
                item.service_data = { ...item.service_data, ...serviceData };
                this.emitSelectionChange();
            }
        },
        
        // Emit selection change event
        emitSelectionChange() {
            this.$dispatch('products-selected', {
                items: this.selectedItems,
                total: this.calculateTotal()
            });
        },
        
        // Handle copied items sync from quote builder
        handleCopiedItemsSync(eventData) {
            if (!eventData || !eventData.items) return;
            
            console.log('Syncing copied items with product selector:', eventData.items);
            
            // Clear existing selected items
            this.selectedItems = [];
            
            // Add copied items to selected items with proper categorization
            eventData.items.forEach(item => {
                // Create a proper selected item structure
                const selectedItem = {
                    id: item.id,
                    type: item.type || 'product',
                    name: item.name,
                    sku: item.sku || '',
                    quantity: item.quantity || 1,
                    base_price: item.base_price || item.unit_price || item.price || 0,
                    unit_price: item.unit_price || item.price || 0,
                    subtotal: item.subtotal || (item.quantity * (item.unit_price || item.price || 0)),
                    billing_model: item.billing_model || 'one_time',
                    billing_cycle: item.billing_cycle || 'monthly',
                    service_type: item.service_type || 'general',
                    service_data: item.service_data || null,
                    category_id: item.category_id || null,
                    product_id: item.product_id || null,
                    service_id: item.service_id || null,
                    bundle_id: item.bundle_id || null,
                    description: item.description || ''
                };
                
                this.selectedItems.push(selectedItem);
            });
            
            // Update the appropriate arrays based on item types
            this.categorizeSelectedItems();
            
            // Emit the selection change so quote builder stays in sync
            this.emitSelectionChange();
        },
        
        // Categorize selected items into products, services, and bundles arrays
        categorizeSelectedItems() {
            // Add selected items to the appropriate category arrays so they show as selected
            this.selectedItems.forEach(item => {
                if (item.type === 'service' && item.service_id) {
                    // Add to services array if not already there
                    if (!this.services.find(s => s.id === item.service_id)) {
                        this.services.push({
                            id: item.service_id,
                            name: item.name,
                            sku: item.sku,
                            base_price: item.base_price,
                            category_id: item.category_id,
                            service_type: item.service_type,
                            description: item.description
                        });
                    }
                } else if (item.type === 'bundle' && item.bundle_id) {
                    // Add to bundles array if not already there
                    if (!this.bundles.find(b => b.id === item.bundle_id)) {
                        this.bundles.push({
                            id: item.bundle_id,
                            name: item.name,
                            sku: item.sku,
                            base_price: item.base_price,
                            category_id: item.category_id,
                            description: item.description
                        });
                    }
                } else if (item.type === 'product' && item.product_id) {
                    // Add to products array if not already there
                    if (!this.products.find(p => p.id === item.product_id)) {
                        this.products.push({
                            id: item.product_id,
                            name: item.name,
                            sku: item.sku,
                            base_price: item.base_price,
                            category_id: item.category_id,
                            description: item.description
                        });
                    }
                }
            });
        },
        
        // Calculate total
        calculateTotal() {
            return this.selectedItems.reduce((sum, item) => {
                return sum + (item.subtotal || 0);
            }, 0);
        },
        
        // Refresh pricing for all selected items
        async refreshPricing() {
            if (!this.clientId || this.selectedItems.length === 0) return;
            
            for (const item of this.selectedItems) {
                await this.recalculatePricing(item);
            }
            
            this.emitSelectionChange();
        },
        
        // Check if input is focused
        isInputFocused() {
            const activeElement = document.activeElement;
            return activeElement && (
                activeElement.tagName === 'INPUT' ||
                activeElement.tagName === 'TEXTAREA' ||
                activeElement.contentEditable === 'true'
            );
        },
        
        // Show notification
        showNotification(message, type = 'info') {
            this.$dispatch('notify', { message, type });
        },
        
        // Pagination methods
        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.loadProducts();
            }
        },
        
        previousPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadProducts();
            }
        },
        
        goToPage(page) {
            this.currentPage = page;
            this.loadProducts();
        },
        
        // Computed properties
        get totalPages() {
            return Math.ceil(this.totalItems / this.perPage);
        },
        
        get paginationRange() {
            const range = [];
            const start = Math.max(1, this.currentPage - 2);
            const end = Math.min(this.totalPages, this.currentPage + 2);
            
            for (let i = start; i <= end; i++) {
                range.push(i);
            }
            
            return range;
        },
        
        get hasFiltersApplied() {
            return this.filters.category || 
                   this.filters.billingModel || 
                   this.filters.type ||
                   this.filters.priceRange.min > 0 ||
                   this.filters.priceRange.max < 10000;
        },
        
        get selectedItemsCount() {
            return this.selectedItems.length;
        },
        
        get selectedItemsTotal() {
            return this.formatPrice(this.calculateTotal());
        },
        
        get paginatedProducts() {
            return this.products || [];
        },
        
        get paginatedServices() {
            return this.services || [];
        },
        
        get totalPages() {
            return Math.ceil(this.totalItems / this.perPage);
        },
        
        get visiblePages() {
            const current = this.currentPage;
            const total = this.totalPages;
            const range = 2;
            
            let start = Math.max(1, current - range);
            let end = Math.min(total, current + range);
            
            const pages = [];
            for (let i = start; i <= end; i++) {
                pages.push(i);
            }
            
            return pages;
        },
        
        get hasFiltersApplied() {
            return this.filters.category || 
                   this.filters.billingModel || 
                   this.filters.priceRange.min > 0 || 
                   this.filters.priceRange.max < 10000;
        }
    };
    
    // Auto-initialize when component is created
    setTimeout(() => {
        if (component.init) {
            component.init();
        }
    }, 0);
    
    return component;
}