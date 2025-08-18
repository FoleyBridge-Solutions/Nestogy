/**
 * Discount and Promotion Engine Component
 * Manages complex discount rules and promotional campaigns
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('discountPromotionEngine', (config = {}) => ({
        // Configuration
        enableStackableDiscounts: config.enableStackableDiscounts !== false,
        maxDiscountPercent: config.maxDiscountPercent || 50,
        enableAutomaticPromotions: config.enableAutomaticPromotions !== false,
        
        // Discount state
        availableDiscounts: [],
        appliedDiscounts: [],
        promotionalCampaigns: [],
        
        // Discount types
        discountTypes: [
            { id: 'percentage', name: 'Percentage', symbol: '%' },
            { id: 'fixed_amount', name: 'Fixed Amount', symbol: '$' },
            { id: 'buy_x_get_y', name: 'Buy X Get Y', symbol: 'BXGY' },
            { id: 'tiered_volume', name: 'Volume Discount', symbol: 'VOL' },
            { id: 'bundle', name: 'Bundle Discount', symbol: 'BDL' }
        ],
        
        // Promotion conditions
        conditionTypes: [
            { id: 'minimum_amount', name: 'Minimum Order Amount' },
            { id: 'minimum_quantity', name: 'Minimum Quantity' },
            { id: 'client_tier', name: 'Client Tier' },
            { id: 'product_category', name: 'Product Category' },
            { id: 'date_range', name: 'Date Range' },
            { id: 'first_time_client', name: 'First Time Client' }
        ],
        
        // Current calculation
        discountCalculation: {
            subtotal: 0,
            totalDiscounts: 0,
            finalAmount: 0,
            appliedPromotions: [],
            savings: 0
        },
        
        init() {
            this.loadDiscountRules();
            this.loadPromotionalCampaigns();
            this.setupEventListeners();
        },
        
        setupEventListeners() {
            document.addEventListener('quote-items-changed', (e) => {
                this.calculateDiscounts(e.detail.items, e.detail.client);
            });
            
            document.addEventListener('client-changed', (e) => {
                this.evaluateClientSpecificPromotions(e.detail.client);
            });
        },
        
        async loadDiscountRules() {
            try {
                const response = await fetch('/api/discount-rules');
                if (response.ok) {
                    this.availableDiscounts = await response.json();
                }
            } catch (error) {
                console.error('Failed to load discount rules:', error);
            }
        },
        
        async loadPromotionalCampaigns() {
            try {
                const response = await fetch('/api/promotional-campaigns/active');
                if (response.ok) {
                    this.promotionalCampaigns = await response.json();
                }
            } catch (error) {
                console.error('Failed to load promotional campaigns:', error);
            }
        },
        
        calculateDiscounts(items, client = null) {
            this.resetDiscountCalculation(items);
            
            if (this.enableAutomaticPromotions) {
                this.evaluateAutomaticPromotions(items, client);
            }
            
            this.applyManualDiscounts(items);
            this.finalizeDiscountCalculation();
        },
        
        resetDiscountCalculation(items) {
            const subtotal = items.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
            
            this.discountCalculation = {
                subtotal: subtotal,
                totalDiscounts: 0,
                finalAmount: subtotal,
                appliedPromotions: [],
                savings: 0
            };
        },
        
        evaluateAutomaticPromotions(items, client) {
            this.promotionalCampaigns.forEach(campaign => {
                if (this.evaluatePromotionConditions(campaign, items, client)) {
                    this.applyPromotion(campaign, items);
                }
            });
        },
        
        evaluatePromotionConditions(campaign, items, client) {
            return campaign.conditions.every(condition => {
                return this.evaluateCondition(condition, items, client);
            });
        },
        
        evaluateCondition(condition, items, client) {
            switch (condition.type) {
                case 'minimum_amount':
                    return this.discountCalculation.subtotal >= condition.value;
                
                case 'minimum_quantity':
                    const totalQuantity = items.reduce((sum, item) => sum + item.quantity, 0);
                    return totalQuantity >= condition.value;
                
                case 'client_tier':
                    return client && client.tier === condition.value;
                
                case 'product_category':
                    return items.some(item => item.category_id === condition.value);
                
                case 'date_range':
                    const now = new Date();
                    const start = new Date(condition.start_date);
                    const end = new Date(condition.end_date);
                    return now >= start && now <= end;
                
                case 'first_time_client':
                    return client && client.is_first_time;
                
                default:
                    return false;
            }
        },
        
        applyPromotion(campaign, items) {
            const discount = this.calculatePromotionDiscount(campaign, items);
            
            if (discount.amount > 0) {
                this.discountCalculation.appliedPromotions.push({
                    id: campaign.id,
                    name: campaign.name,
                    type: campaign.discount_type,
                    amount: discount.amount,
                    description: campaign.description
                });
                
                this.discountCalculation.totalDiscounts += discount.amount;
            }
        },
        
        calculatePromotionDiscount(campaign, items) {
            switch (campaign.discount_type) {
                case 'percentage':
                    return {
                        amount: this.discountCalculation.subtotal * (campaign.discount_value / 100),
                        type: 'percentage'
                    };
                
                case 'fixed_amount':
                    return {
                        amount: Math.min(campaign.discount_value, this.discountCalculation.subtotal),
                        type: 'fixed'
                    };
                
                case 'buy_x_get_y':
                    return this.calculateBuyXGetYDiscount(campaign, items);
                
                case 'tiered_volume':
                    return this.calculateTieredVolumeDiscount(campaign, items);
                
                case 'bundle':
                    return this.calculateBundleDiscount(campaign, items);
                
                default:
                    return { amount: 0, type: 'none' };
            }
        },
        
        calculateBuyXGetYDiscount(campaign, items) {
            const { buy_quantity, get_quantity, target_product_id } = campaign.parameters;
            
            const targetItems = items.filter(item => 
                target_product_id ? item.product_id === target_product_id : true
            );
            
            const totalQuantity = targetItems.reduce((sum, item) => sum + item.quantity, 0);
            const freeItems = Math.floor(totalQuantity / buy_quantity) * get_quantity;
            
            if (freeItems > 0) {
                const avgPrice = targetItems.reduce((sum, item) => sum + item.unit_price, 0) / targetItems.length;
                return {
                    amount: freeItems * avgPrice,
                    type: 'buy_x_get_y',
                    freeItems: freeItems
                };
            }
            
            return { amount: 0, type: 'buy_x_get_y' };
        },
        
        calculateTieredVolumeDiscount(campaign, items) {
            const totalQuantity = items.reduce((sum, item) => sum + item.quantity, 0);
            const tier = campaign.tiers.find(t => totalQuantity >= t.min_quantity);
            
            if (tier) {
                return {
                    amount: this.discountCalculation.subtotal * (tier.discount_percent / 100),
                    type: 'tiered_volume',
                    tier: tier.name
                };
            }
            
            return { amount: 0, type: 'tiered_volume' };
        },
        
        calculateBundleDiscount(campaign, items) {
            const requiredProducts = campaign.bundle_products;
            const hasAllProducts = requiredProducts.every(productId => 
                items.some(item => item.product_id === productId)
            );
            
            if (hasAllProducts) {
                const bundleValue = requiredProducts.reduce((sum, productId) => {
                    const item = items.find(i => i.product_id === productId);
                    return sum + (item ? item.unit_price * item.quantity : 0);
                }, 0);
                
                return {
                    amount: bundleValue * (campaign.discount_value / 100),
                    type: 'bundle'
                };
            }
            
            return { amount: 0, type: 'bundle' };
        },
        
        applyManualDiscounts(items) {
            this.appliedDiscounts.forEach(discount => {
                const amount = this.calculateManualDiscount(discount, items);
                this.discountCalculation.totalDiscounts += amount;
            });
        },
        
        calculateManualDiscount(discount, items) {
            switch (discount.type) {
                case 'percentage':
                    return this.discountCalculation.subtotal * (discount.value / 100);
                case 'fixed_amount':
                    return Math.min(discount.value, this.discountCalculation.subtotal);
                default:
                    return 0;
            }
        },
        
        finalizeDiscountCalculation() {
            // Apply maximum discount limit
            const maxDiscount = this.discountCalculation.subtotal * (this.maxDiscountPercent / 100);
            this.discountCalculation.totalDiscounts = Math.min(
                this.discountCalculation.totalDiscounts, 
                maxDiscount
            );
            
            this.discountCalculation.finalAmount = 
                this.discountCalculation.subtotal - this.discountCalculation.totalDiscounts;
            
            this.discountCalculation.savings = 
                (this.discountCalculation.totalDiscounts / this.discountCalculation.subtotal) * 100;
            
            this.$dispatch('discounts-calculated', {
                calculation: this.discountCalculation
            });
        },
        
        addManualDiscount(discount) {
            if (this.canAddDiscount(discount)) {
                this.appliedDiscounts.push({
                    id: Date.now(),
                    ...discount
                });
                
                // Recalculate if we have items
                if (this.$store.quote?.selectedItems) {
                    this.calculateDiscounts(this.$store.quote.selectedItems);
                }
            }
        },
        
        removeManualDiscount(discountId) {
            this.appliedDiscounts = this.appliedDiscounts.filter(d => d.id !== discountId);
            
            // Recalculate
            if (this.$store.quote?.selectedItems) {
                this.calculateDiscounts(this.$store.quote.selectedItems);
            }
        },
        
        canAddDiscount(discount) {
            if (!this.enableStackableDiscounts && this.appliedDiscounts.length > 0) {
                return false;
            }
            
            // Check if discount already exists
            return !this.appliedDiscounts.some(d => d.code === discount.code);
        },
        
        validateDiscountCode(code) {
            const discount = this.availableDiscounts.find(d => d.code === code);
            
            if (!discount) {
                return { valid: false, message: 'Invalid discount code' };
            }
            
            if (!discount.active) {
                return { valid: false, message: 'Discount code is no longer active' };
            }
            
            if (discount.expiry_date && new Date() > new Date(discount.expiry_date)) {
                return { valid: false, message: 'Discount code has expired' };
            }
            
            if (discount.usage_limit && discount.usage_count >= discount.usage_limit) {
                return { valid: false, message: 'Discount code usage limit reached' };
            }
            
            return { valid: true, discount: discount };
        },
        
        formatDiscount(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount);
        },
        
        get totalSavings() {
            return this.discountCalculation.totalDiscounts;
        },
        
        get savingsPercentage() {
            return this.discountCalculation.savings;
        },
        
        get hasDiscounts() {
            return this.discountCalculation.totalDiscounts > 0;
        }
    }));
});