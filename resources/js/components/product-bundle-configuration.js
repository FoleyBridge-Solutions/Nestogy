/**
 * Product Bundle Configuration Component
 * Manages creation and configuration of product bundles for quotes
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('productBundleConfiguration', (config = {}) => ({
        // Configuration
        maxBundleItems: config.maxBundleItems || 20,
        enableBundleDiscounts: config.enableBundleDiscounts !== false,
        enableConditionalPricing: config.enableConditionalPricing !== false,
        enableBundleTemplates: config.enableBundleTemplates !== false,
        
        // Bundle state
        currentBundle: {
            id: null,
            name: '',
            description: '',
            type: 'fixed', // 'fixed', 'flexible', 'tiered'
            discount_type: 'percentage', // 'percentage', 'fixed', 'none'
            discount_value: 0,
            min_quantity: 1,
            max_quantity: null,
            is_active: true,
            items: [],
            conditions: [],
            pricing_rules: []
        },
        
        // Available items for bundling
        availableProducts: [],
        availableServices: [],
        searchQuery: '',
        categoryFilter: '',
        
        // Bundle types
        bundleTypes: [
            {
                id: 'fixed',
                name: 'Fixed Bundle',
                description: 'Predefined set of products with fixed quantities',
                icon: '📦'
            },
            {
                id: 'flexible',
                name: 'Flexible Bundle',
                description: 'Choose from a selection of products with customizable quantities',
                icon: '🔧'
            },
            {
                id: 'tiered',
                name: 'Tiered Bundle',
                description: 'Different pricing tiers based on quantity or value',
                icon: '📊'
            },
            {
                id: 'subscription',
                name: 'Subscription Bundle',
                description: 'Recurring bundle with scheduled deliveries',
                icon: '🔄'
            }
        ],
        
        // Bundle validation rules
        validationRules: {
            name: { required: true, minLength: 3 },
            items: { required: true, minItems: 2 },
            discount_value: { min: 0, max: 100 }
        },
        
        // UI state
        showBundleBuilder: false,
        showItemSelector: false,
        showPricingRules: false,
        currentStep: 1,
        totalSteps: 4,
        
        // Existing bundles
        existingBundles: [],
        selectedBundles: new Set(),
        
        // Bundle templates
        bundleTemplates: [],
        
        // Conditions for conditional pricing
        availableConditions: [
            {
                type: 'quantity',
                name: 'Quantity Based',
                operators: ['>=', '<=', '=', 'between']
            },
            {
                type: 'value',
                name: 'Value Based',
                operators: ['>=', '<=', '=', 'between']
            },
            {
                type: 'category',
                name: 'Category Based',
                operators: ['includes', 'excludes']
            },
            {
                type: 'customer_tier',
                name: 'Customer Tier',
                operators: ['=', 'in']
            }
        ],
        
        // Validation errors
        errors: {},
        
        // Initialize bundle configuration
        init() {
            this.loadExistingBundles();
            this.loadBundleTemplates();
            this.loadAvailableItems();
            this.setupEventListeners();
        },
        
        // Setup event listeners
        setupEventListeners() {
            // Watch for bundle type changes
            this.$watch('currentBundle.type', (newType) => {
                this.handleBundleTypeChange(newType);
            });
            
            // Watch for item changes
            this.$watch('currentBundle.items', () => {
                this.calculateBundlePricing();
            }, { deep: true });
            
            // Watch for discount changes
            this.$watch('currentBundle.discount_value', () => {
                this.calculateBundlePricing();
            });
            
            this.$watch('currentBundle.discount_type', () => {
                this.calculateBundlePricing();
            });
        },
        
        // Load existing bundles
        async loadExistingBundles() {
            try {
                const response = await fetch('/api/product-bundles');
                if (response.ok) {
                    this.existingBundles = await response.json();
                }
            } catch (error) {
                console.error('Failed to load existing bundles:', error);
            }
        },
        
        // Load bundle templates
        async loadBundleTemplates() {
            if (!this.enableBundleTemplates) return;
            
            try {
                const response = await fetch('/api/product-bundles/templates');
                if (response.ok) {
                    this.bundleTemplates = await response.json();
                }
            } catch (error) {
                console.error('Failed to load bundle templates:', error);
            }
        },
        
        // Load available items
        async loadAvailableItems() {
            try {
                const [productsResponse, servicesResponse] = await Promise.all([
                    fetch('/api/products?bundle_eligible=true'),
                    fetch('/api/services?bundle_eligible=true')
                ]);
                
                if (productsResponse.ok) {
                    this.availableProducts = await productsResponse.json();
                }
                
                if (servicesResponse.ok) {
                    this.availableServices = await servicesResponse.json();
                }
            } catch (error) {
                console.error('Failed to load available items:', error);
            }
        },
        
        // Start bundle creation
        startBundleCreation() {
            this.resetCurrentBundle();
            this.showBundleBuilder = true;
            this.currentStep = 1;
            this.errors = {};
        },
        
        // Edit existing bundle
        editBundle(bundle) {
            this.currentBundle = { ...bundle };
            this.showBundleBuilder = true;
            this.currentStep = 1;
            this.errors = {};
        },
        
        // Reset current bundle
        resetCurrentBundle() {
            this.currentBundle = {
                id: null,
                name: '',
                description: '',
                type: 'fixed',
                discount_type: 'percentage',
                discount_value: 0,
                min_quantity: 1,
                max_quantity: null,
                is_active: true,
                items: [],
                conditions: [],
                pricing_rules: []
            };
        },
        
        // Handle bundle type change
        handleBundleTypeChange(newType) {
            // Reset items and conditions when changing type
            this.currentBundle.items = [];
            this.currentBundle.conditions = [];
            this.currentBundle.pricing_rules = [];
            
            // Set default values based on type
            switch (newType) {
                case 'fixed':
                    this.currentBundle.discount_type = 'percentage';
                    break;
                case 'flexible':
                    this.currentBundle.min_quantity = 2;
                    this.currentBundle.max_quantity = 10;
                    break;
                case 'tiered':
                    this.setupDefaultTiers();
                    break;
                case 'subscription':
                    this.currentBundle.discount_type = 'percentage';
                    this.currentBundle.discount_value = 10;
                    break;
            }
        },
        
        // Setup default pricing tiers
        setupDefaultTiers() {
            this.currentBundle.pricing_rules = [
                {
                    id: 1,
                    name: 'Tier 1',
                    condition: { type: 'quantity', operator: 'between', min: 1, max: 5 },
                    discount: { type: 'percentage', value: 5 }
                },
                {
                    id: 2,
                    name: 'Tier 2',
                    condition: { type: 'quantity', operator: 'between', min: 6, max: 10 },
                    discount: { type: 'percentage', value: 10 }
                },
                {
                    id: 3,
                    name: 'Tier 3',
                    condition: { type: 'quantity', operator: '>=', value: 11 },
                    discount: { type: 'percentage', value: 15 }
                }
            ];
        },
        
        // Add item to bundle
        addItemToBundle(item) {
            if (this.currentBundle.items.length >= this.maxBundleItems) {
                this.showError('Maximum number of items reached');
                return;
            }
            
            const existingItem = this.currentBundle.items.find(bundleItem => 
                bundleItem.product_id === item.id && bundleItem.type === item.type
            );
            
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                this.currentBundle.items.push({
                    id: Date.now(),
                    product_id: item.id,
                    name: item.name,
                    type: item.type || 'product',
                    unit_price: item.price || item.unit_price,
                    quantity: 1,
                    required: this.currentBundle.type === 'fixed',
                    min_quantity: 1,
                    max_quantity: this.currentBundle.type === 'flexible' ? 10 : 1
                });
            }
            
            this.calculateBundlePricing();
        },
        
        // Remove item from bundle
        removeItemFromBundle(itemId) {
            this.currentBundle.items = this.currentBundle.items.filter(item => item.id !== itemId);
            this.calculateBundlePricing();
        },
        
        // Update item quantity
        updateItemQuantity(itemId, quantity) {
            const item = this.currentBundle.items.find(item => item.id === itemId);
            if (item) {
                item.quantity = Math.max(item.min_quantity || 1, parseInt(quantity) || 1);
                if (item.max_quantity) {
                    item.quantity = Math.min(item.quantity, item.max_quantity);
                }
                this.calculateBundlePricing();
            }
        },
        
        // Calculate bundle pricing
        calculateBundlePricing() {
            let subtotal = 0;
            let discount = 0;
            
            // Calculate base subtotal
            this.currentBundle.items.forEach(item => {
                subtotal += item.unit_price * item.quantity;
            });
            
            // Apply bundle discount
            if (this.currentBundle.discount_type === 'percentage') {
                discount = subtotal * (this.currentBundle.discount_value / 100);
            } else if (this.currentBundle.discount_type === 'fixed') {
                discount = this.currentBundle.discount_value;
            }
            
            // Apply pricing rules for tiered bundles
            if (this.currentBundle.type === 'tiered') {
                const tierDiscount = this.calculateTierDiscount(subtotal);
                discount = Math.max(discount, tierDiscount);
            }
            
            this.currentBundle._pricing = {
                subtotal: subtotal,
                discount: discount,
                total: subtotal - discount,
                savings_percent: subtotal > 0 ? (discount / subtotal) * 100 : 0
            };
        },
        
        // Calculate tier-based discount
        calculateTierDiscount(subtotal) {
            const totalQuantity = this.currentBundle.items.reduce((sum, item) => sum + item.quantity, 0);
            let tierDiscount = 0;
            
            this.currentBundle.pricing_rules.forEach(rule => {
                if (this.evaluateCondition(rule.condition, { quantity: totalQuantity, value: subtotal })) {
                    const ruleDiscount = rule.discount.type === 'percentage' 
                        ? subtotal * (rule.discount.value / 100)
                        : rule.discount.value;
                    
                    tierDiscount = Math.max(tierDiscount, ruleDiscount);
                }
            });
            
            return tierDiscount;
        },
        
        // Evaluate pricing condition
        evaluateCondition(condition, context) {
            const { type, operator, value, min, max } = condition;
            const contextValue = context[type];
            
            switch (operator) {
                case '>=':
                    return contextValue >= value;
                case '<=':
                    return contextValue <= value;
                case '=':
                    return contextValue === value;
                case 'between':
                    return contextValue >= min && contextValue <= max;
                default:
                    return false;
            }
        },
        
        // Add pricing rule
        addPricingRule() {
            const newRule = {
                id: Date.now(),
                name: `Tier ${this.currentBundle.pricing_rules.length + 1}`,
                condition: {
                    type: 'quantity',
                    operator: '>=',
                    value: 1
                },
                discount: {
                    type: 'percentage',
                    value: 5
                }
            };
            
            this.currentBundle.pricing_rules.push(newRule);
        },
        
        // Remove pricing rule
        removePricingRule(ruleId) {
            this.currentBundle.pricing_rules = this.currentBundle.pricing_rules.filter(
                rule => rule.id !== ruleId
            );
            this.calculateBundlePricing();
        },
        
        // Validate current step
        validateStep(step) {
            this.errors = {};
            
            switch (step) {
                case 1: // Basic info
                    if (!this.currentBundle.name) {
                        this.errors.name = 'Bundle name is required';
                    }
                    if (this.currentBundle.name.length < 3) {
                        this.errors.name = 'Bundle name must be at least 3 characters';
                    }
                    break;
                    
                case 2: // Items
                    if (this.currentBundle.items.length < 2) {
                        this.errors.items = 'Bundle must contain at least 2 items';
                    }
                    break;
                    
                case 3: // Pricing
                    if (this.currentBundle.discount_value < 0 || this.currentBundle.discount_value > 100) {
                        this.errors.discount_value = 'Discount value must be between 0 and 100';
                    }
                    break;
            }
            
            return Object.keys(this.errors).length === 0;
        },
        
        // Navigate to next step
        nextStep() {
            if (this.validateStep(this.currentStep)) {
                if (this.currentStep < this.totalSteps) {
                    this.currentStep++;
                }
            }
        },
        
        // Navigate to previous step
        previousStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
            }
        },
        
        // Save bundle
        async saveBundle() {
            if (!this.validateStep(this.currentStep)) {
                return;
            }
            
            try {
                const bundleData = {
                    ...this.currentBundle,
                    pricing: this.currentBundle._pricing
                };
                
                const url = bundleData.id 
                    ? `/api/product-bundles/${bundleData.id}`
                    : '/api/product-bundles';
                
                const method = bundleData.id ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(bundleData)
                });
                
                if (response.ok) {
                    const savedBundle = await response.json();
                    
                    // Update existing bundles list
                    if (bundleData.id) {
                        const index = this.existingBundles.findIndex(b => b.id === bundleData.id);
                        if (index > -1) {
                            this.existingBundles[index] = savedBundle;
                        }
                    } else {
                        this.existingBundles.push(savedBundle);
                    }
                    
                    this.showBundleBuilder = false;
                    this.showSuccess('Bundle saved successfully');
                    
                    // Dispatch bundle created/updated event
                    this.$dispatch('bundle-saved', { bundle: savedBundle });
                    
                } else {
                    throw new Error('Failed to save bundle');
                }
                
            } catch (error) {
                console.error('Failed to save bundle:', error);
                this.showError('Failed to save bundle');
            }
        },
        
        // Delete bundle
        async deleteBundle(bundleId) {
            if (!confirm('Are you sure you want to delete this bundle?')) {
                return;
            }
            
            try {
                const response = await fetch(`/api/product-bundles/${bundleId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                if (response.ok) {
                    this.existingBundles = this.existingBundles.filter(b => b.id !== bundleId);
                    this.showSuccess('Bundle deleted successfully');
                } else {
                    throw new Error('Failed to delete bundle');
                }
                
            } catch (error) {
                console.error('Failed to delete bundle:', error);
                this.showError('Failed to delete bundle');
            }
        },
        
        // Apply bundle template
        applyTemplate(template) {
            this.currentBundle = {
                ...this.currentBundle,
                name: template.name,
                description: template.description,
                type: template.type,
                discount_type: template.discount_type,
                discount_value: template.discount_value,
                items: [...template.items],
                pricing_rules: [...(template.pricing_rules || [])]
            };
            
            this.calculateBundlePricing();
        },
        
        // Add bundle to quote
        addBundleToQuote(bundle) {
            // Dispatch event to add bundle to current quote
            this.$dispatch('add-bundle-to-quote', { bundle });
        },
        
        // Clone bundle
        cloneBundle(bundle) {
            this.currentBundle = {
                ...bundle,
                id: null,
                name: `${bundle.name} (Copy)`,
                items: [...bundle.items],
                pricing_rules: [...(bundle.pricing_rules || [])]
            };
            
            this.showBundleBuilder = true;
            this.currentStep = 1;
        },
        
        // Show success message
        showSuccess(message) {
            this.$dispatch('notification', {
                type: 'success',
                message: message
            });
        },
        
        // Show error message
        showError(message) {
            this.$dispatch('notification', {
                type: 'error',
                message: message
            });
        },
        
        // Get filtered available items
        getFilteredItems(items) {
            return items.filter(item => {
                const matchesSearch = !this.searchQuery || 
                    item.name.toLowerCase().includes(this.searchQuery.toLowerCase());
                
                const matchesCategory = !this.categoryFilter || 
                    item.category_id === this.categoryFilter;
                
                return matchesSearch && matchesCategory;
            });
        },
        
        // Format currency
        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount || 0);
        },
        
        // Computed properties
        get stepTitle() {
            const titles = {
                1: 'Bundle Information',
                2: 'Select Items',
                3: 'Configure Pricing',
                4: 'Review & Save'
            };
            return titles[this.currentStep] || '';
        },
        
        get canProceedToNext() {
            return this.validateStep(this.currentStep);
        },
        
        get bundleSubtotal() {
            return this.currentBundle._pricing?.subtotal || 0;
        },
        
        get bundleDiscount() {
            return this.currentBundle._pricing?.discount || 0;
        },
        
        get bundleTotal() {
            return this.currentBundle._pricing?.total || 0;
        },
        
        get bundleSavings() {
            return this.currentBundle._pricing?.savings_percent || 0;
        },
        
        get totalBundleItems() {
            return this.currentBundle.items.reduce((sum, item) => sum + item.quantity, 0);
        },
        
        get availableItems() {
            return [
                ...this.getFilteredItems(this.availableProducts),
                ...this.getFilteredItems(this.availableServices)
            ];
        }
    }));
});