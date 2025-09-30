// Minimal Quote Integration - Safe fallbacks only
// Make functions immediately available to avoid timing issues

// Enhanced product selector with basic API integration (fallback)
window.productSelectorAdvancedFallback = function() {
    return {
        products: [],
        services: [],
        bundles: [],
        categories: [],
        selectedItems: [],
        loading: false,
        searchQuery: '',
        activeTab: 'products',
        viewMode: 'grid',
        showFilters: false,
        filters: {
            category: '',
            billingModel: '',
            priceRange: { min: null, max: null }
        },
        currentPage: 1,
        totalPages: 1,
        showProductModal: false,
        selectedProduct: null,
        clientId: null,
        
        async init() {
            console.log('Product selector initialized');
            await this.loadData();
        },
        
        async loadData() {
            this.loading = true;
            try {
                // Load products
                const productsResponse = await fetch('/api/products');
                if (productsResponse.ok) {
                    this.products = await productsResponse.json();
                }
                
                // Load bundles
                const bundlesResponse = await fetch('/api/bundles');
                if (bundlesResponse.ok) {
                    this.bundles = await bundlesResponse.json();
                }
            } catch (error) {
                console.error('Error loading product data:', error);
            } finally {
                this.loading = false;
            }
        },
        
        // Enhanced methods
        quickSearch() {
            // Filter products based on search query
            // This would be more sophisticated in real implementation
        },
        switchTab(tab) { 
            this.activeTab = tab; 
        },
        toggleItem(item, type = null) {
            // Support both old and new calling patterns
            const itemType = type || (this.activeTab === 'products' ? 'product' : this.activeTab === 'bundles' ? 'bundle' : 'service');
            const existingIndex = this.selectedItems.findIndex(selected => 
                selected.id === item.id && (selected.type === itemType || !type)
            );
            
            if (existingIndex >= 0) {
                this.selectedItems.splice(existingIndex, 1);
            } else {
                const selectedItem = {
                    id: item.id,
                    name: item.name,
                    type: itemType,
                    quantity: 1,
                    unit_price: item.base_price || item.fixed_price || 0,
                    subtotal: item.base_price || item.fixed_price || 0,
                    description: item.description || '',
                    billing_cycle: item.billing_cycle || 'one_time',
                    sku: item.sku || '',
                    category: item.category || ''
                };
                this.selectedItems.push(selectedItem);
                
                // Integrate with quote store if available
                if (this.$store && this.$store.quote) {
                    this.$store.quote.addItem(selectedItem);
                }
            }
            this.calculatePricing();
        },
        isSelected(itemId, type = null) { 
            if (type) {
                return this.selectedItems.some(selected => selected.id === itemId && selected.type === type);
            }
            return this.selectedItems.some(selected => selected.id === itemId);
        },
        
        calculatePricing() {
            const subtotal = this.selectedItems.reduce((sum, item) => sum + (item.subtotal || 0), 0);
            const tax = subtotal * 0.1; // 10% tax for demo
            const total = subtotal + tax;
            
            const pricing = {
                subtotal: subtotal,
                discount: 0,
                tax: tax,
                total: total
            };
            
            // Dispatch pricing update event
            this.$dispatch('products-selected', {
                items: this.selectedItems,
                ...pricing
            });
        },
        showProductDetails(product) { 
            this.selectedProduct = product;
            this.showProductModal = true; 
        },
        closeProductModal() { 
            this.showProductModal = false; 
            this.selectedProduct = null;
        },
        clearFilters() { 
            this.filters = {
                category: '',
                billingModel: '',
                priceRange: { min: null, max: null }
            };
            this.searchQuery = '';
        },
        changeSort(field) { 
            // Basic sorting implementation
            console.log('Sorting by:', field);
        },
        previousPage() { 
            if (this.currentPage > 1) {
                this.currentPage--;
            }
        },
        nextPage() { 
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
            }
        },
        goToPage(page) { 
            this.currentPage = Math.max(1, Math.min(this.totalPages, page));
        },
        updateQuantity(itemId, quantity) { 
            const item = this.selectedItems.find(item => item.id === itemId);
            if (item) {
                item.quantity = Math.max(1, parseInt(quantity) || 1);
                item.subtotal = item.unit_price * item.quantity;
                this.calculatePricing();
                
                // Update store if available
                if (this.$store && this.$store.quote) {
                    this.$store.quote.updateItem(itemId, 'quantity', item.quantity);
                }
            }
        },
        addBundle(bundle) {
            console.log('Adding bundle:', bundle);
            // For now, treat bundles like regular products
            this.toggleItem(bundle, 'bundle');
        },
        formatPrice(amount) { 
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount || 0);
        },
        formatBillingCycle(cycle) { 
            const cycles = {
                'one_time': 'One-time',
                'monthly': 'Monthly',
                'quarterly': 'Quarterly',
                'annually': 'Annual'
            };
            return cycles[cycle] || cycle;
        },
        getPricing(itemId) {
            // Return client-specific pricing if available
            return null; // Simplified for now
        },
        getDiscountBadge(item) {
            // Return discount badge text if applicable
            return null; // Simplified for now
        },
        
        // Computed properties
        get hasFiltersApplied() { 
            return !!(this.filters.category || this.filters.billingModel || 
                     this.filters.priceRange.min || this.filters.priceRange.max ||
                     this.searchQuery);
        },
        get selectedItemsCount() { 
            return this.selectedItems.length;
        },
        get selectedItemsTotal() { 
            const total = this.selectedItems.reduce((sum, item) => sum + (item.subtotal || 0), 0);
            return this.formatPrice(total);
        },
        get paginationRange() { 
            const range = [];
            const start = Math.max(1, this.currentPage - 2);
            const end = Math.min(this.totalPages, this.currentPage + 2);
            for (let i = start; i <= end; i++) {
                range.push(i);
            }
            return range.length ? range : [1];
        }
    };
};

// Enhanced pricing display
window.pricingDisplay = function() {
    return {
        pricing: {
            subtotal: 0,
            discount: 0,
            savings: 0,
            tax: 0,
            total: 0,
            recurring: {
                monthly: 0,
                annual: 0
            },
            appliedRules: []
        },
        showDetails: false,
        itemCount: 0,
        
        init() {
            console.log('Pricing display initialized');
            // Watch store for pricing changes
            if (this.$store && this.$store.quote) {
                this.$watch('$store.quote.pricing', (newPricing) => {
                    if (newPricing) {
                        this.pricing = { ...newPricing };
                    }
                }, { deep: true });
                
                this.$watch('$store.quote.selectedItemsCount', (count) => {
                    this.itemCount = count || 0;
                });
                
                // Initial load
                this.pricing = { ...this.$store.quote.pricing };
                this.itemCount = this.$store.quote.selectedItemsCount || 0;
            }
        },
        
        get totalSavings() {
            return (this.pricing.discount || 0) + (this.pricing.savings || 0);
        },
        
        updateFromStore(pricingData) {
            if (pricingData) {
                this.pricing = { ...pricingData };
            }
        },
        
        formatCurrency(amount) {
            const currency = (this.$store && this.$store.quote) ? 
                this.$store.quote.document.currency_code : 'USD';
                
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency || 'USD'
            }).format(amount || 0);
        },
        
        applyDiscount() {
            // Dispatch event to parent for discount application
            this.$dispatch('apply-discount', {
                current: this.pricing
            });
        },
        
        recalculate() {
            // Force recalculation via store
            if (this.$store && this.$store.quote) {
                this.$store.quote.updatePricing();
            }
            
            // Dispatch event to parent
            this.$dispatch('recalculate-pricing', {
                pricing: this.pricing
            });
        }
    };
};

// Pricing Summary component
window.pricingSummary = function() {
    return {
        pricing: {
            subtotal: 0,
            discount: 0,
            savings: 0,
            tax: 0,
            total: 0,
            recurring: {
                monthly: 0,
                annual: 0
            },
            appliedRules: []
        },
        selectedItems: [],
        itemCount: 0,
        warnings: [],
        
        init() {
            console.log('Pricing summary initialized');
            // Watch store for pricing and items changes
            if (this.$store && this.$store.quote) {
                this.$watch('$store.quote.pricing', (newPricing) => {
                    if (newPricing) {
                        this.pricing = { ...newPricing };
                        this.validateQuote();
                    }
                }, { deep: true });
                
                this.$watch('$store.quote.selectedItems', (items) => {
                    if (items) {
                        this.selectedItems = [...items];
                        this.itemCount = items.length;
                        this.validateQuote();
                    }
                }, { deep: true });
                
                // Initial load from store
                this.pricing = { ...this.$store.quote.pricing };
                this.selectedItems = [...(this.$store.quote.selectedItems || [])];
                this.itemCount = this.selectedItems.length;
                this.validateQuote();
            }
        },
        
        get totalSavings() {
            return (this.pricing.discount || 0) + (this.pricing.savings || 0);
        },
        
        get canFinalize() {
            return this.selectedItems.length > 0 && 
                   this.pricing.total > 0 && 
                   this.warnings.length === 0;
        },
        
        updateFromStore(pricingData) {
            if (pricingData) {
                this.pricing = { ...pricingData };
                this.validateQuote();
            }
        },
        
        validateQuote() {
            this.warnings = [];
            
            if (this.selectedItems.length === 0) {
                this.warnings.push('No items selected for this quote');
            }
            
            if (this.pricing.total <= 0) {
                this.warnings.push('Quote total must be greater than $0');
            }
            
            // Check for missing client selection (from parent component)
            if (this.$store?.quote?.document?.client_id === null) {
                this.warnings.push('Client must be selected before creating quote');
            }
            
            // Check for subscription items without billing configuration
            const hasSubscriptions = this.selectedItems.some(item => 
                item.billing_cycle && item.billing_cycle !== 'one_time'
            );
            
            if (hasSubscriptions && !this.$store?.quote?.billingConfig) {
                this.warnings.push('Billing configuration required for subscription items');
            }
        },
        
        formatCurrency(amount) {
            const currency = (this.$store && this.$store.quote) ? 
                this.$store.quote.document.currency_code : 'USD';
                
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency || 'USD'
            }).format(amount || 0);
        },
        
        formatBillingCycle(cycle) {
            const cycles = {
                'one_time': 'One-time',
                'monthly': 'Monthly',
                'quarterly': 'Quarterly',
                'annually': 'Annual'
            };
            return cycles[cycle] || cycle;
        },
        
        async saveAsDraft() {
            if (this.selectedItems.length === 0) return;
            
            // Dispatch event to parent component
            this.$dispatch('save-quote-draft', {
                items: this.selectedItems,
                pricing: this.pricing,
                billing: this.$store?.quote?.billingConfig
            });
        },
        
        async finalizeQuote() {
            if (!this.canFinalize) return;
            
            // Dispatch event to parent component for quote creation
            this.$dispatch('finalize-quote', {
                items: this.selectedItems,
                pricing: this.pricing,
                billing: this.$store?.quote?.billingConfig,
                document: this.$store?.quote?.document
            });
        },
        
        async exportQuote(format) {
            if (this.selectedItems.length === 0) return;
            
            // Dispatch export event
            this.$dispatch('export-quote', {
                format: format,
                items: this.selectedItems,
                pricing: this.pricing
            });
        }
    };
};

// Minimal billing configuration
window.billingConfiguration = function() {
    return {
        configuration: {
            billing_options: {
                model: 'one_time',
                cycle: 'monthly',
                paymentTerms: 30
            }
        },
        
        init() {
            console.log('Billing configuration initialized');
        }
    };
};

document.addEventListener('DOMContentLoaded', function() {
    console.log('Quote integration functions created and ready');
});