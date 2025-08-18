/**
 * Quote System Integration
 * Connects all quote components to the Laravel application
 */

document.addEventListener('alpine:init', () => {
    // Create the productSelectorAdvanced function that the component expects
    window.productSelectorAdvanced = () => ({
        // Core data
        products: [],
        services: [],
        bundles: [],
        categories: [],
        selectedItems: [],
        loading: false,
        
        // Search and filters
        searchQuery: '',
        activeTab: 'products',
        viewMode: 'grid',
        showFilters: false,
        
        // Filters
        filters: {
            category: '',
            billingModel: '',
            priceRange: {
                min: null,
                max: null
            }
        },
        
        // Pagination
        currentPage: 1,
        totalPages: 1,
        itemsPerPage: 20,
        
        // Product modal
        showProductModal: false,
        selectedProduct: null,
        
        // Client context
        clientId: null,
        
        init() {
            this.loadInitialData();
            this.setupKeyboardShortcuts();
        },
        
        async loadInitialData() {
            this.loading = true;
            try {
                await Promise.all([
                    this.loadProducts(),
                    this.loadServices(),
                    this.loadBundles(),
                    this.loadCategories()
                ]);
            } catch (error) {
                console.error('Failed to load initial data:', error);
            } finally {
                this.loading = false;
            }
        },
        
        async loadProducts() {
            try {
                const response = await fetch('/products/search');
                if (response.ok) {
                    this.products = await response.json();
                }
            } catch (error) {
                console.error('Failed to load products:', error);
            }
        },
        
        async loadServices() {
            try {
                const response = await fetch('/api/services');
                if (response.ok) {
                    this.services = await response.json();
                }
            } catch (error) {
                console.error('Failed to load services:', error);
            }
        },
        
        async loadBundles() {
            try {
                const response = await fetch('/api/bundles');
                if (response.ok) {
                    this.bundles = await response.json();
                }
            } catch (error) {
                console.error('Failed to load bundles:', error);
            }
        },
        
        async loadCategories() {
            try {
                const response = await fetch('/api/categories');
                if (response.ok) {
                    const data = await response.json();
                    this.categories = data.map(cat => cat.name);
                }
            } catch (error) {
                console.error('Failed to load categories:', error);
            }
        },
        
        // Search functionality
        async quickSearch() {
            if (!this.searchQuery.trim()) {
                this.loadInitialData();
                return;
            }
            
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    search: this.searchQuery,
                    tab: this.activeTab
                });
                
                const response = await fetch(`/api/search/products?${params}`);
                if (response.ok) {
                    const data = await response.json();
                    this[this.activeTab] = data;
                }
            } catch (error) {
                console.error('Search failed:', error);
            } finally {
                this.loading = false;
            }
        },
        
        // Tab switching
        switchTab(tab) {
            this.activeTab = tab;
            this.currentPage = 1;
        },
        
        // Item selection
        toggleItem(item, type) {
            const existingIndex = this.selectedItems.findIndex(
                selected => selected.id === item.id && selected.type === type
            );
            
            if (existingIndex > -1) {
                this.selectedItems.splice(existingIndex, 1);
            } else {
                this.selectedItems.push({
                    ...item,
                    type: type,
                    quantity: 1,
                    unit_price: this.getPricing(item.id)?.unit_price || item.base_price,
                    subtotal: this.getPricing(item.id)?.unit_price || item.base_price
                });
            }
            
            this.updateParentComponent();
        },
        
        isSelected(itemId, type) {
            return this.selectedItems.some(
                item => item.id === itemId && item.type === type
            );
        },
        
        // Product details
        showProductDetails(product) {
            this.selectedProduct = product;
            this.showProductModal = true;
        },
        
        closeProductModal() {
            this.showProductModal = false;
            this.selectedProduct = null;
        },
        
        // Bundle handling
        addBundle(bundle) {
            // Navigate to bundle configuration
            this.$dispatch('configure-bundle', { bundle });
        },
        
        // Pricing
        getPricing(itemId) {
            // Return client-specific pricing if available
            return null; // Simplified for now
        },
        
        getDiscountBadge(item) {
            // Return discount badge text if applicable
            return null; // Simplified for now
        },
        
        // Filters
        clearFilters() {
            this.filters = {
                category: '',
                billingModel: '',
                priceRange: {
                    min: null,
                    max: null
                }
            };
        },
        
        get hasFiltersApplied() {
            return Object.values(this.filters).some(filter => 
                filter !== '' && filter !== null && 
                (typeof filter !== 'object' || Object.values(filter).some(v => v !== null))
            );
        },
        
        // Sorting
        sortBy: 'name',
        sortOrder: 'asc',
        
        changeSort(field) {
            if (this.sortBy === field) {
                this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = field;
                this.sortOrder = 'asc';
            }
            
            this.applySorting();
        },
        
        applySorting() {
            const data = this[this.activeTab];
            if (!data || !Array.isArray(data)) return;
            
            data.sort((a, b) => {
                let aVal, bVal;
                
                switch (this.sortBy) {
                    case 'name':
                        aVal = a.name || '';
                        bVal = b.name || '';
                        break;
                    case 'price':
                        aVal = a.base_price || 0;
                        bVal = b.base_price || 0;
                        break;
                    case 'newest':
                        aVal = new Date(a.created_at || 0);
                        bVal = new Date(b.created_at || 0);
                        break;
                    default:
                        return 0;
                }
                
                if (this.sortOrder === 'asc') {
                    return aVal > bVal ? 1 : -1;
                } else {
                    return aVal < bVal ? 1 : -1;
                }
            });
        },
        
        // Pagination
        get paginationRange() {
            const range = [];
            const start = Math.max(1, this.currentPage - 2);
            const end = Math.min(this.totalPages, this.currentPage + 2);
            
            for (let i = start; i <= end; i++) {
                range.push(i);
            }
            
            return range;
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
            this.currentPage = page;
        },
        
        // Selected items summary
        get selectedItemsCount() {
            return this.selectedItems.length;
        },
        
        get selectedItemsTotal() {
            const total = this.selectedItems.reduce((sum, item) => 
                sum + (item.subtotal || 0), 0
            );
            return this.formatPrice(total);
        },
        
        updateQuantity(itemId, quantity) {
            const item = this.selectedItems.find(item => item.id === itemId);
            if (item) {
                item.quantity = parseInt(quantity) || 1;
                item.subtotal = item.unit_price * item.quantity;
                this.updateParentComponent();
            }
        },
        
        // Utility functions
        formatPrice(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount || 0);
        },
        
        formatBillingCycle(cycle) {
            const cycles = {
                'monthly': 'Monthly',
                'yearly': 'Annual',
                'one_time': 'One-time',
                'usage_based': 'Usage-based'
            };
            return cycles[cycle] || cycle;
        },
        
        setupKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                if (e.key === '/' && !e.ctrlKey && !e.metaKey) {
                    e.preventDefault();
                    this.$refs.searchInput?.focus();
                }
            });
        },
        
        updateParentComponent() {
            this.$dispatch('products-selected', {
                items: this.selectedItems,
                subtotal: this.selectedItems.reduce((sum, item) => sum + item.subtotal, 0)
            });
        }
    });

    // Create pricing display component
    window.pricingDisplay = () => ({
        pricing: {
            subtotal: 0,
            discount: 0,
            tax: 0,
            total: 0,
            savings: 0
        },
        
        items: [],
        
        init() {
            this.$watch('items', () => {
                this.recalculate();
            });
        },
        
        recalculate() {
            this.pricing.subtotal = this.items.reduce((sum, item) => 
                sum + (item.subtotal || 0), 0
            );
            
            // Apply discounts, taxes, etc.
            this.pricing.total = this.pricing.subtotal + this.pricing.tax - this.pricing.discount;
            
            this.$dispatch('pricing-calculated', this.pricing);
        },
        
        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount || 0);
        }
    });

    // Create billing configuration component
    window.billingConfiguration = () => ({
        configuration: {
            billing_options: {
                model: 'one_time',
                cycle: 'monthly',
                paymentTerms: 30
            }
        },
        
        init() {
            this.$dispatch('billing-configured', this.configuration);
        }
    });
});