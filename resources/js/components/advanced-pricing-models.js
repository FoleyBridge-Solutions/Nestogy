/**
 * Advanced Pricing Models Component
 * Provides multiple pricing strategies and models for quote calculations
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('advancedPricingModels', (config = {}) => ({
        // Configuration
        enableTieredPricing: config.enableTieredPricing !== false,
        enableBundlePricing: config.enableBundlePricing !== false,
        enableDynamicPricing: config.enableDynamicPricing !== false,
        enableContractPricing: config.enableContractPricing !== false,
        
        // Current pricing model state
        currentModel: 'standard',
        availableModels: [
            {
                id: 'standard',
                name: 'Standard Pricing',
                description: 'Fixed unit prices with quantity-based calculations',
                icon: '💰',
                features: ['Fixed pricing', 'Quantity discounts', 'Simple calculations']
            },
            {
                id: 'tiered',
                name: 'Tiered Pricing',
                description: 'Volume-based pricing tiers with automatic discounts',
                icon: '📊',
                features: ['Volume discounts', 'Tier thresholds', 'Automatic pricing']
            },
            {
                id: 'bundle',
                name: 'Bundle Pricing',
                description: 'Package deals with bundled product pricing',
                icon: '📦',
                features: ['Package deals', 'Bundle discounts', 'Cross-selling']
            },
            {
                id: 'dynamic',
                name: 'Dynamic Pricing',
                description: 'Market-based pricing with real-time adjustments',
                icon: '⚡',
                features: ['Market rates', 'Real-time updates', 'Competitive pricing']
            },
            {
                id: 'contract',
                name: 'Contract Pricing',
                description: 'Client-specific contract rates and terms',
                icon: '📋',
                features: ['Custom rates', 'Contract terms', 'Client-specific']
            },
            {
                id: 'cost_plus',
                name: 'Cost Plus Pricing',
                description: 'Cost-based pricing with configurable markup',
                icon: '🧮',
                features: ['Cost tracking', 'Markup control', 'Transparent pricing']
            }
        ],
        
        // Pricing model configurations
        modelConfigs: {
            standard: {
                allowQuantityDiscounts: true,
                maxDiscountPercent: 20,
                minimumMargin: 15
            },
            tiered: {
                tiers: [
                    { min: 1, max: 10, discount: 0 },
                    { min: 11, max: 50, discount: 5 },
                    { min: 51, max: 100, discount: 10 },
                    { min: 101, max: null, discount: 15 }
                ],
                autoApplyTiers: true
            },
            bundle: {
                bundleDiscountPercent: 10,
                minimumBundleItems: 3,
                allowCustomBundles: true
            },
            dynamic: {
                updateInterval: 300000, // 5 minutes
                priceFluctuationLimit: 5, // 5% max change
                marketDataSources: ['competitor', 'market', 'demand']
            },
            contract: {
                allowOverrides: true,
                requireApproval: true,
                trackVariance: true
            },
            cost_plus: {
                defaultMarkup: 25,
                allowMarkupOverride: true,
                showCostBreakdown: true
            }
        },
        
        // UI State
        showModelSelector: false,
        showAdvancedOptions: false,
        calculationInProgress: false,
        
        // Pricing calculations
        calculations: {
            baseTotal: 0,
            discounts: 0,
            markups: 0,
            finalTotal: 0,
            marginPercent: 0,
            profitAmount: 0
        },
        
        // Tiered pricing state
        tieredPricing: {
            activeTiers: [],
            totalQuantity: 0,
            tierBreakdown: []
        },
        
        // Bundle pricing state
        bundlePricing: {
            availableBundles: [],
            selectedBundles: [],
            bundleDiscounts: new Map()
        },
        
        // Dynamic pricing state
        dynamicPricing: {
            lastUpdate: null,
            priceChanges: [],
            marketData: {},
            updateTimer: null
        },
        
        // Contract pricing state
        contractPricing: {
            clientContracts: new Map(),
            activeContract: null,
            customRates: new Map()
        },
        
        // Cost plus pricing state
        costPlusPricing: {
            costBreakdown: [],
            totalCost: 0,
            markupPercent: 25,
            showBreakdown: false
        },
        
        // Initialize pricing models
        init() {
            this.loadPricingPreferences();
            this.setupModelListeners();
            this.initializeCurrentModel();
            this.loadContractData();
        },
        
        // Setup event listeners
        setupModelListeners() {
            // Listen for quote item changes
            document.addEventListener('quote-items-updated', (e) => {
                this.recalculatePricing(e.detail.items);
            });
            
            // Listen for client changes
            document.addEventListener('quote-client-changed', (e) => {
                this.handleClientChange(e.detail.client);
            });
            
            // Listen for model changes
            this.$watch('currentModel', (newModel) => {
                this.switchPricingModel(newModel);
            });
        },
        
        // Initialize current pricing model
        initializeCurrentModel() {
            this.switchPricingModel(this.currentModel);
        },
        
        // Switch pricing model
        switchPricingModel(modelId) {
            const model = this.availableModels.find(m => m.id === modelId);
            if (!model) return;
            
            this.currentModel = modelId;
            
            // Reset calculations
            this.resetCalculations();
            
            // Setup model-specific features
            this.setupModelFeatures(modelId);
            
            // Recalculate with new model
            if (this.$store.quote && this.$store.quote.selectedItems.length > 0) {
                this.recalculatePricing(this.$store.quote.selectedItems);
            }
            
            // Save preference
            this.savePricingPreferences();
            
            // Dispatch model change event
            this.$dispatch('pricing-model-changed', {
                model: modelId,
                config: this.modelConfigs[modelId]
            });
        },
        
        // Setup model-specific features
        setupModelFeatures(modelId) {
            switch (modelId) {
                case 'tiered':
                    this.initializeTieredPricing();
                    break;
                case 'bundle':
                    this.initializeBundlePricing();
                    break;
                case 'dynamic':
                    this.initializeDynamicPricing();
                    break;
                case 'contract':
                    this.initializeContractPricing();
                    break;
                case 'cost_plus':
                    this.initializeCostPlusPricing();
                    break;
            }
        },
        
        // Initialize tiered pricing
        initializeTieredPricing() {
            this.tieredPricing.activeTiers = [...this.modelConfigs.tiered.tiers];
        },
        
        // Initialize bundle pricing
        async initializeBundlePricing() {
            try {
                const response = await fetch('/api/product-bundles');
                if (response.ok) {
                    this.bundlePricing.availableBundles = await response.json();
                }
            } catch (error) {
                console.error('Failed to load bundles:', error);
            }
        },
        
        // Initialize dynamic pricing
        initializeDynamicPricing() {
            this.startDynamicPriceUpdates();
        },
        
        // Initialize contract pricing
        async initializeContractPricing() {
            if (this.$store.quote?.document?.client_id) {
                await this.loadClientContract(this.$store.quote.document.client_id);
            }
        },
        
        // Initialize cost plus pricing
        initializeCostPlusPricing() {
            this.costPlusPricing.markupPercent = this.modelConfigs.cost_plus.defaultMarkup;
        },
        
        // Recalculate pricing with current model
        recalculatePricing(items) {
            if (!items || items.length === 0) {
                this.resetCalculations();
                return;
            }
            
            this.calculationInProgress = true;
            
            try {
                switch (this.currentModel) {
                    case 'standard':
                        this.calculateStandardPricing(items);
                        break;
                    case 'tiered':
                        this.calculateTieredPricing(items);
                        break;
                    case 'bundle':
                        this.calculateBundlePricing(items);
                        break;
                    case 'dynamic':
                        this.calculateDynamicPricing(items);
                        break;
                    case 'contract':
                        this.calculateContractPricing(items);
                        break;
                    case 'cost_plus':
                        this.calculateCostPlusPricing(items);
                        break;
                }
                
                this.updateCalculationSummary();
                
            } catch (error) {
                console.error('Pricing calculation error:', error);
            } finally {
                this.calculationInProgress = false;
            }
        },
        
        // Calculate standard pricing
        calculateStandardPricing(items) {
            let baseTotal = 0;
            let discounts = 0;
            
            items.forEach(item => {
                const itemTotal = item.unit_price * item.quantity;
                baseTotal += itemTotal;
                
                // Apply quantity discounts
                if (this.modelConfigs.standard.allowQuantityDiscounts && item.quantity >= 10) {
                    const discountPercent = Math.min(
                        Math.floor(item.quantity / 10) * 2,
                        this.modelConfigs.standard.maxDiscountPercent
                    );
                    discounts += itemTotal * (discountPercent / 100);
                }
            });
            
            this.calculations.baseTotal = baseTotal;
            this.calculations.discounts = discounts;
            this.calculations.finalTotal = baseTotal - discounts;
        },
        
        // Calculate tiered pricing
        calculateTieredPricing(items) {
            let baseTotal = 0;
            let totalDiscounts = 0;
            this.tieredPricing.tierBreakdown = [];
            
            items.forEach(item => {
                const itemTotal = item.unit_price * item.quantity;
                baseTotal += itemTotal;
                
                // Find applicable tier
                const tier = this.tieredPricing.activeTiers.find(t => 
                    item.quantity >= t.min && (t.max === null || item.quantity <= t.max)
                );
                
                if (tier && tier.discount > 0) {
                    const discount = itemTotal * (tier.discount / 100);
                    totalDiscounts += discount;
                    
                    this.tieredPricing.tierBreakdown.push({
                        item: item.name,
                        quantity: item.quantity,
                        tier: tier,
                        discount: discount
                    });
                }
            });
            
            this.calculations.baseTotal = baseTotal;
            this.calculations.discounts = totalDiscounts;
            this.calculations.finalTotal = baseTotal - totalDiscounts;
        },
        
        // Calculate bundle pricing
        calculateBundlePricing(items) {
            let baseTotal = 0;
            let bundleDiscounts = 0;
            
            // Calculate base total
            items.forEach(item => {
                baseTotal += item.unit_price * item.quantity;
            });
            
            // Check for bundle opportunities
            this.bundlePricing.selectedBundles.forEach(bundle => {
                const bundleItems = items.filter(item => 
                    bundle.product_ids.includes(item.product_id)
                );
                
                if (bundleItems.length >= this.modelConfigs.bundle.minimumBundleItems) {
                    const bundleValue = bundleItems.reduce((sum, item) => 
                        sum + (item.unit_price * item.quantity), 0
                    );
                    
                    const discount = bundleValue * (this.modelConfigs.bundle.bundleDiscountPercent / 100);
                    bundleDiscounts += discount;
                }
            });
            
            this.calculations.baseTotal = baseTotal;
            this.calculations.discounts = bundleDiscounts;
            this.calculations.finalTotal = baseTotal - bundleDiscounts;
        },
        
        // Calculate dynamic pricing
        async calculateDynamicPricing(items) {
            let baseTotal = 0;
            let adjustments = 0;
            
            for (const item of items) {
                const currentPrice = await this.getDynamicPrice(item);
                const originalPrice = item.unit_price;
                const adjustment = (currentPrice - originalPrice) * item.quantity;
                
                baseTotal += originalPrice * item.quantity;
                adjustments += adjustment;
                
                // Update item price
                item.dynamic_price = currentPrice;
            }
            
            this.calculations.baseTotal = baseTotal;
            this.calculations.markups = adjustments;
            this.calculations.finalTotal = baseTotal + adjustments;
        },
        
        // Calculate contract pricing
        calculateContractPricing(items) {
            let baseTotal = 0;
            let contractDiscounts = 0;
            
            items.forEach(item => {
                const standardPrice = item.unit_price * item.quantity;
                baseTotal += standardPrice;
                
                // Apply contract rates if available
                const contractRate = this.contractPricing.customRates.get(item.product_id);
                if (contractRate) {
                    const contractPrice = contractRate * item.quantity;
                    const savings = standardPrice - contractPrice;
                    contractDiscounts += savings;
                }
            });
            
            this.calculations.baseTotal = baseTotal;
            this.calculations.discounts = contractDiscounts;
            this.calculations.finalTotal = baseTotal - contractDiscounts;
        },
        
        // Calculate cost plus pricing
        calculateCostPlusPricing(items) {
            let totalCost = 0;
            this.costPlusPricing.costBreakdown = [];
            
            items.forEach(item => {
                const itemCost = (item.cost_price || item.unit_price * 0.7) * item.quantity;
                totalCost += itemCost;
                
                this.costPlusPricing.costBreakdown.push({
                    name: item.name,
                    quantity: item.quantity,
                    unitCost: item.cost_price || item.unit_price * 0.7,
                    totalCost: itemCost
                });
            });
            
            this.costPlusPricing.totalCost = totalCost;
            const markup = totalCost * (this.costPlusPricing.markupPercent / 100);
            
            this.calculations.baseTotal = totalCost;
            this.calculations.markups = markup;
            this.calculations.finalTotal = totalCost + markup;
            this.calculations.marginPercent = this.costPlusPricing.markupPercent;
        },
        
        // Get dynamic price for item
        async getDynamicPrice(item) {
            try {
                const response = await fetch(`/api/dynamic-pricing/${item.product_id}`);
                if (response.ok) {
                    const data = await response.json();
                    return data.current_price;
                }
            } catch (error) {
                console.error('Failed to get dynamic price:', error);
            }
            
            return item.unit_price; // Fallback to original price
        },
        
        // Start dynamic price updates
        startDynamicPriceUpdates() {
            if (this.dynamicPricing.updateTimer) {
                clearInterval(this.dynamicPricing.updateTimer);
            }
            
            this.dynamicPricing.updateTimer = setInterval(() => {
                this.updateDynamicPrices();
            }, this.modelConfigs.dynamic.updateInterval);
        },
        
        // Update dynamic prices
        async updateDynamicPrices() {
            if (this.currentModel !== 'dynamic') return;
            
            try {
                const response = await fetch('/api/dynamic-pricing/market-update');
                if (response.ok) {
                    const marketData = await response.json();
                    this.dynamicPricing.marketData = marketData;
                    this.dynamicPricing.lastUpdate = new Date();
                    
                    // Recalculate if we have items
                    if (this.$store.quote?.selectedItems?.length > 0) {
                        this.recalculatePricing(this.$store.quote.selectedItems);
                    }
                }
            } catch (error) {
                console.error('Failed to update dynamic prices:', error);
            }
        },
        
        // Load client contract data
        async loadClientContract(clientId) {
            try {
                const response = await fetch(`/api/clients/${clientId}/contracts`);
                if (response.ok) {
                    const contracts = await response.json();
                    
                    if (contracts.length > 0) {
                        this.contractPricing.activeContract = contracts[0];
                        this.contractPricing.customRates = new Map(
                            contracts[0].rates.map(rate => [rate.product_id, rate.price])
                        );
                    }
                }
            } catch (error) {
                console.error('Failed to load client contracts:', error);
            }
        },
        
        // Handle client change
        async handleClientChange(client) {
            if (this.currentModel === 'contract' && client) {
                await this.loadClientContract(client.id);
                if (this.$store.quote?.selectedItems?.length > 0) {
                    this.recalculatePricing(this.$store.quote.selectedItems);
                }
            }
        },
        
        // Update calculation summary
        updateCalculationSummary() {
            const { baseTotal, discounts, markups, finalTotal } = this.calculations;
            
            this.calculations.profitAmount = finalTotal - baseTotal;
            
            if (baseTotal > 0) {
                this.calculations.marginPercent = ((finalTotal - baseTotal) / finalTotal) * 100;
            }
            
            // Dispatch pricing update event
            this.$dispatch('pricing-calculated', {
                model: this.currentModel,
                calculations: this.calculations
            });
        },
        
        // Reset calculations
        resetCalculations() {
            this.calculations = {
                baseTotal: 0,
                discounts: 0,
                markups: 0,
                finalTotal: 0,
                marginPercent: 0,
                profitAmount: 0
            };
        },
        
        // Load pricing preferences
        loadPricingPreferences() {
            try {
                const prefs = localStorage.getItem('pricing-model-preferences');
                if (prefs) {
                    const preferences = JSON.parse(prefs);
                    this.currentModel = preferences.currentModel || 'standard';
                    this.showAdvancedOptions = preferences.showAdvancedOptions || false;
                }
            } catch (error) {
                console.warn('Failed to load pricing preferences:', error);
            }
        },
        
        // Save pricing preferences
        savePricingPreferences() {
            try {
                const preferences = {
                    currentModel: this.currentModel,
                    showAdvancedOptions: this.showAdvancedOptions
                };
                localStorage.setItem('pricing-model-preferences', JSON.stringify(preferences));
            } catch (error) {
                console.warn('Failed to save pricing preferences:', error);
            }
        },
        
        // Load contract data on init
        async loadContractData() {
            try {
                const response = await fetch('/api/contracts/active');
                if (response.ok) {
                    const contracts = await response.json();
                    contracts.forEach(contract => {
                        this.contractPricing.clientContracts.set(contract.client_id, contract);
                    });
                }
            } catch (error) {
                console.error('Failed to load contract data:', error);
            }
        },
        
        // Toggle advanced options
        toggleAdvancedOptions() {
            this.showAdvancedOptions = !this.showAdvancedOptions;
            this.savePricingPreferences();
        },
        
        // Apply bundle to current quote
        applyBundle(bundle) {
            if (!this.bundlePricing.selectedBundles.find(b => b.id === bundle.id)) {
                this.bundlePricing.selectedBundles.push(bundle);
                
                // Add bundle items to quote if not present
                bundle.products.forEach(product => {
                    if (!this.$store.quote.selectedItems.find(item => item.product_id === product.id)) {
                        this.$store.quote.addItem({
                            product_id: product.id,
                            name: product.name,
                            unit_price: product.price,
                            quantity: 1
                        });
                    }
                });
                
                this.recalculatePricing(this.$store.quote.selectedItems);
            }
        },
        
        // Remove bundle
        removeBundle(bundleId) {
            this.bundlePricing.selectedBundles = this.bundlePricing.selectedBundles.filter(
                b => b.id !== bundleId
            );
            this.recalculatePricing(this.$store.quote.selectedItems);
        },
        
        // Update markup percentage for cost-plus
        updateMarkup(percent) {
            this.costPlusPricing.markupPercent = percent;
            if (this.currentModel === 'cost_plus') {
                this.recalculatePricing(this.$store.quote.selectedItems);
            }
        },
        
        // Get model by ID
        getModelById(modelId) {
            return this.availableModels.find(m => m.id === modelId);
        },
        
        // Computed properties
        get currentModelConfig() {
            return this.modelConfigs[this.currentModel] || {};
        },
        
        get currentModelInfo() {
            return this.getModelById(this.currentModel);
        },
        
        get formattedBaseTotal() {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(this.calculations.baseTotal);
        },
        
        get formattedDiscounts() {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(this.calculations.discounts);
        },
        
        get formattedMarkups() {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(this.calculations.markups);
        },
        
        get formattedFinalTotal() {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(this.calculations.finalTotal);
        },
        
        get hasActiveDiscounts() {
            return this.calculations.discounts > 0;
        },
        
        get hasActiveMarkups() {
            return this.calculations.markups > 0;
        },
        
        get isDynamicPricingActive() {
            return this.currentModel === 'dynamic' && this.dynamicPricing.updateTimer;
        },
        
        get hasContractRates() {
            return this.contractPricing.customRates.size > 0;
        }
    }));
});