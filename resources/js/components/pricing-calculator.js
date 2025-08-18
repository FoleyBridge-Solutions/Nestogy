/**
 * Advanced Pricing Calculator Component
 * Handles complex pricing calculations, discounts, taxes, and real-time updates
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('pricingCalculator', (config = {}) => ({
        // Configuration
        taxApiEndpoint: config.taxApiEndpoint || '/api/tax/calculate',
        currencyApiEndpoint: config.currencyApiEndpoint || '/api/currency/convert',
        enableRealTimeRates: config.enableRealTimeRates || false,
        enableTaxIntegration: config.enableTaxIntegration || true,
        
        // State
        calculating: false,
        lastCalculation: null,
        calculationHistory: [],
        
        // Pricing data
        pricing: {
            subtotal: 0,
            itemDiscounts: 0,
            globalDiscount: 0,
            totalDiscount: 0,
            taxableAmount: 0,
            taxAmount: 0,
            total: 0,
            savings: 0,
            recurring: {
                monthly: 0,
                quarterly: 0,
                annual: 0
            }
        },
        
        // Tax configuration
        tax: {
            enabled: true,
            inclusive: false,
            exemptions: [],
            rates: new Map(),
            jurisdiction: '',
            customRates: {}
        },
        
        // Discount engine
        discounts: {
            global: {
                type: 'fixed', // 'fixed', 'percentage'
                amount: 0,
                conditions: []
            },
            volume: {
                enabled: false,
                tiers: []
            },
            promotional: {
                enabled: false,
                codes: [],
                active: []
            },
            loyalty: {
                enabled: false,
                clientLevel: 'standard',
                multiplier: 1.0
            }
        },
        
        // Currency handling
        currency: {
            base: 'USD',
            display: 'USD',
            rates: new Map(),
            lastUpdate: null
        },
        
        // Analytics
        analytics: {
            margin: 0,
            profitability: 0,
            competitivePosition: 'unknown',
            priceRecommendations: []
        },

        // Initialize component
        init() {
            this.setupWatchers();
            this.loadTaxConfiguration();
            this.loadCurrencyRates();
            this.setupRealTimeUpdates();
        },
        
        // Setup reactive watchers
        setupWatchers() {
            // Watch for item changes
            this.$watch('$store.quote.selectedItems', () => {
                this.calculatePricing();
            }, { deep: true });

            // Watch for global discount changes
            this.$watch('$store.quote.document.discount_amount', () => {
                this.calculatePricing();
            });

            this.$watch('$store.quote.document.discount_type', () => {
                this.calculatePricing();
            });

            // Watch for currency changes
            this.$watch('$store.quote.document.currency_code', (newCurrency) => {
                this.handleCurrencyChange(newCurrency);
            });

            // Watch for client changes (affects tax jurisdiction)
            this.$watch('$store.quote.document.client_id', (clientId) => {
                this.updateTaxJurisdiction(clientId);
            });
        },
        
        // Load tax settings
        async loadTaxSettings() {
            try {
                const response = await fetch('/api/settings/tax');
                const data = await response.json();
                
                this.taxRate = data.default_tax_rate || 0;
                this.taxInclusive = data.tax_inclusive || false;
            } catch (error) {
                console.error('Error loading tax settings:', error);
            }
        },
        
        // Load client-specific settings
        async loadClientSettings() {
            if (!this.client) return;
            
            try {
                const response = await fetch(`/api/clients/${this.client.id}/billing-settings`);
                const data = await response.json();
                
                if (data.tax_rate !== null) {
                    this.taxRate = data.tax_rate;
                }
                
                if (data.is_tax_exempt) {
                    this.taxRate = 0;
                }
                
                this.currency = data.currency || 'USD';
            } catch (error) {
                console.error('Error loading client settings:', error);
            }
        },
        
        // Main pricing calculation
        async calculatePricing() {
            if (this.calculating) return;
            
            try {
                this.calculating = true;
                const items = this.$store.quote.selectedItems;
                
                // Step 1: Calculate item subtotals
                const itemTotals = await this.calculateItemTotals(items);
                
                // Step 2: Apply item-level discounts
                const itemDiscountResults = this.applyItemDiscounts(itemTotals);
                
                // Step 3: Calculate subtotal
                const subtotal = itemDiscountResults.reduce((sum, item) => sum + item.subtotal, 0);
                
                // Step 4: Apply global discounts
                const globalDiscountResult = this.applyGlobalDiscounts(subtotal);
                
                // Step 5: Calculate taxes
                const taxResult = await this.calculateTaxes(globalDiscountResult.taxableAmount, items);
                
                // Step 6: Calculate final total
                const total = globalDiscountResult.taxableAmount + taxResult.totalTax;
                
                // Step 7: Calculate recurring revenue
                const recurringRevenue = this.calculateRecurringRevenue(items);
                
                // Step 8: Update pricing object
                this.updatePricingResult({
                    subtotal,
                    itemDiscounts: itemDiscountResults.reduce((sum, item) => sum + item.discounts, 0),
                    globalDiscount: globalDiscountResult.discountAmount,
                    totalDiscount: globalDiscountResult.discountAmount + itemDiscountResults.reduce((sum, item) => sum + item.discounts, 0),
                    taxableAmount: globalDiscountResult.taxableAmount,
                    taxAmount: taxResult.totalTax,
                    total,
                    savings: this.calculateSavings(itemTotals, itemDiscountResults),
                    recurring: recurringRevenue,
                    breakdown: {
                        items: itemDiscountResults,
                        taxes: taxResult.breakdown,
                        discounts: globalDiscountResult.breakdown
                    }
                });
                
                // Step 9: Calculate analytics
                this.calculateAnalytics();
                
                // Step 10: Check for pricing recommendations
                this.generatePriceRecommendations();
                
            } catch (error) {
                console.error('Pricing calculation failed:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: 'Failed to calculate pricing'
                });
            } finally {
                this.calculating = false;
            }
        },
        
        // Calculate individual item totals
        async calculateItemTotals(items) {
            return Promise.all(items.map(async (item) => {
                let baseAmount = parseFloat(item.quantity) * parseFloat(item.unit_price);
                
                // Apply advanced pricing models if applicable
                if (item.pricingModel && item.pricingModel !== 'standard') {
                    baseAmount = await this.applyAdvancedPricing(item, baseAmount);
                }
                
                return {
                    id: item.id,
                    baseAmount,
                    quantity: parseFloat(item.quantity),
                    unitPrice: parseFloat(item.unit_price),
                    pricingModel: item.pricingModel || 'standard',
                    category: item.category,
                    taxable: item.taxable !== false,
                    taxRate: parseFloat(item.tax_rate || 0)
                };
            }));
        },

        // Apply advanced pricing models
        async applyAdvancedPricing(item, baseAmount) {
            switch (item.pricingModel) {
                case 'tiered':
                    return this.calculateTieredPricing(item);
                case 'usage':
                    return this.calculateUsagePricing(item);
                case 'time':
                    return this.calculateTimePricing(item);
                case 'value':
                    return this.calculateValuePricing(item);
                default:
                    return baseAmount;
            }
        },

        // Apply item-level discounts
        applyItemDiscounts(itemTotals) {
            return itemTotals.map(item => {
                let discounts = 0;
                let subtotal = item.baseAmount;
                
                // Volume discounts
                if (this.discounts.volume.enabled) {
                    const volumeDiscount = this.calculateVolumeDiscount(item);
                    discounts += volumeDiscount;
                    subtotal -= volumeDiscount;
                }
                
                return {
                    ...item,
                    discounts,
                    subtotal: Math.max(0, subtotal)
                };
            });
        },

        // Apply global discounts
        applyGlobalDiscounts(subtotal) {
            // Get global discount from store
            const discountAmount = parseFloat(this.$store.quote.document.discount_amount) || 0;
            const discountType = this.$store.quote.document.discount_type || 'fixed';
            
            let globalDiscountAmount = 0;
            
            if (discountType === 'percentage') {
                globalDiscountAmount = subtotal * (discountAmount / 100);
            } else {
                globalDiscountAmount = Math.min(discountAmount, subtotal);
            }
            
            const taxableAmount = Math.max(0, subtotal - globalDiscountAmount);
            
            return {
                discountAmount: globalDiscountAmount,
                taxableAmount,
                breakdown: {
                    type: discountType,
                    rate: discountAmount
                }
            };
        },

        // Calculate volume discounts
        calculateVolumeDiscount(item) {
            const tiers = this.discounts.volume.tiers;
            if (!tiers || tiers.length === 0) return 0;
            
            const quantity = item.quantity;
            const applicableTier = tiers
                .filter(tier => quantity >= tier.minQuantity)
                .sort((a, b) => b.minQuantity - a.minQuantity)[0];
            
            if (!applicableTier) return 0;
            
            const discountRate = applicableTier.discountPercentage / 100;
            return item.baseAmount * discountRate;
        },
        
        // Calculate taxes
        async calculateTaxes(taxableAmount, items) {
            if (!this.tax.enabled || taxableAmount === 0) {
                return { totalTax: 0, breakdown: [] };
            }
            
            try {
                // Use external tax service if available
                if (this.enableTaxIntegration && this.tax.jurisdiction) {
                    return await this.calculateTaxesExternal(taxableAmount, items);
                }
                
                // Use internal tax calculation
                return this.calculateTaxesInternal(taxableAmount, items);
                
            } catch (error) {
                console.error('Tax calculation failed:', error);
                // Fallback to simple tax calculation
                return this.calculateSimpleTax(taxableAmount);
            }
        },

        // Internal tax calculation
        calculateTaxesInternal(taxableAmount, items) {
            const taxBreakdown = [];
            let totalTax = 0;
            
            // Calculate tax for each item if item-level tax rates exist
            items.forEach(item => {
                if (item.taxable && item.taxRate > 0) {
                    const itemTax = item.subtotal * (item.taxRate / 100);
                    totalTax += itemTax;
                    
                    taxBreakdown.push({
                        item_id: item.id,
                        tax_rate: item.taxRate,
                        tax_amount: itemTax,
                        jurisdiction: 'item_level'
                    });
                }
            });
            
            // Apply jurisdiction-level taxes if no item-level taxes
            if (totalTax === 0 && this.tax.jurisdiction) {
                const jurisdictionRate = this.tax.rates.get(this.tax.jurisdiction) || 0;
                if (jurisdictionRate > 0) {
                    totalTax = taxableAmount * (jurisdictionRate / 100);
                    
                    taxBreakdown.push({
                        jurisdiction: this.tax.jurisdiction,
                        tax_rate: jurisdictionRate,
                        tax_amount: totalTax,
                        taxable_amount: taxableAmount
                    });
                }
            }
            
            return { totalTax, breakdown: taxBreakdown };
        },

        // Simple fallback tax calculation
        calculateSimpleTax(taxableAmount) {
            const defaultTaxRate = 8.25; // Default tax rate
            const totalTax = taxableAmount * (defaultTaxRate / 100);
            
            return {
                totalTax,
                breakdown: [{
                    jurisdiction: 'default',
                    tax_rate: defaultTaxRate,
                    tax_amount: totalTax,
                    taxable_amount: taxableAmount
                }]
            };
        },

        // Calculate recurring revenue
        calculateRecurringRevenue(items) {
            const recurring = {
                monthly: 0,
                quarterly: 0,
                annual: 0
            };
            
            items.forEach(item => {
                const amount = item.subtotal || 0;
                
                switch (item.billing_cycle) {
                    case 'monthly':
                        recurring.monthly += amount;
                        recurring.quarterly += amount * 3;
                        recurring.annual += amount * 12;
                        break;
                    case 'quarterly':
                        recurring.monthly += amount / 3;
                        recurring.quarterly += amount;
                        recurring.annual += amount * 4;
                        break;
                    case 'semi_annually':
                        recurring.monthly += amount / 6;
                        recurring.quarterly += amount / 2;
                        recurring.annual += amount * 2;
                        break;
                    case 'annually':
                        recurring.monthly += amount / 12;
                        recurring.quarterly += amount / 4;
                        recurring.annual += amount;
                        break;
                }
            });
            
            return recurring;
        },

        // Calculate savings
        calculateSavings(originalTotals, discountedTotals) {
            const originalTotal = originalTotals.reduce((sum, item) => sum + item.baseAmount, 0);
            const discountedTotal = discountedTotals.reduce((sum, item) => sum + item.subtotal, 0);
            return Math.max(0, originalTotal - discountedTotal);
        },

        // Update pricing result
        updatePricingResult(newPricing) {
            this.pricing = { ...this.pricing, ...newPricing };
            this.lastCalculation = new Date();
            
            // Update store
            this.$store.quote.pricing = this.pricing;
            
            // Add to calculation history
            this.calculationHistory.unshift({
                timestamp: this.lastCalculation,
                pricing: { ...this.pricing }
            });
            
            // Keep only last 10 calculations
            if (this.calculationHistory.length > 10) {
                this.calculationHistory = this.calculationHistory.slice(0, 10);
            }
            
            // Dispatch pricing update event
            this.$dispatch('pricing-calculated', this.pricing);
        },
        
        // Convert amount to monthly equivalent
        convertToMonthly(amount, cycle) {
            const conversions = {
                'weekly': amount * 4.33,
                'monthly': amount,
                'quarterly': amount / 3,
                'semi_annually': amount / 6,
                'annually': amount / 12
            };
            return conversions[cycle] || amount;
        },
        
        // Calculate discounts
        calculateDiscounts() {
            let discountAmount = 0;
            
            // Item-level discounts (already applied in subtotals)
            // These are handled by the pricing service
            
            // Manual discount
            if (this.manualDiscount.value > 0) {
                if (this.manualDiscount.type === 'percentage') {
                    discountAmount += this.subtotal * (this.manualDiscount.value / 100);
                } else {
                    discountAmount += this.manualDiscount.value;
                }
            }
            
            // Promo code discounts
            for (const promo of this.appliedPromoCodes) {
                discountAmount += promo.discount_amount || 0;
            }
            
            return Math.min(discountAmount, this.subtotal); // Don't exceed subtotal
        },
        
        // Calculate tax
        calculateTax(amount) {
            if (this.taxRate === 0) return 0;
            
            if (this.taxInclusive) {
                // Tax is included in the price
                return amount - (amount / (1 + this.taxRate / 100));
            } else {
                // Tax is added to the price
                return amount * (this.taxRate / 100);
            }
        },
        
        // Apply promo code
        async applyPromoCode() {
            if (!this.promoCode) return;
            
            // Check if already applied
            if (this.appliedPromoCodes.some(p => p.code === this.promoCode)) {
                this.showNotification('Promo code already applied', 'warning');
                return;
            }
            
            try {
                const response = await fetch('/api/promo-codes/validate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        code: this.promoCode,
                        items: this.items.map(i => ({
                            product_id: i.id,
                            quantity: i.quantity
                        })),
                        client_id: this.client?.id
                    })
                });
                
                const data = await response.json();
                
                if (data.valid) {
                    this.appliedPromoCodes.push({
                        code: this.promoCode,
                        description: data.description,
                        discount_amount: data.discount_amount,
                        discount_type: data.discount_type
                    });
                    
                    this.promoCode = '';
                    this.recalculate();
                    this.showNotification('Promo code applied successfully', 'success');
                } else {
                    this.showNotification(data.message || 'Invalid promo code', 'error');
                }
            } catch (error) {
                console.error('Error applying promo code:', error);
                this.showNotification('Failed to apply promo code', 'error');
            }
        },
        
        // Remove promo code
        removePromoCode(code) {
            const index = this.appliedPromoCodes.findIndex(p => p.code === code);
            if (index > -1) {
                this.appliedPromoCodes.splice(index, 1);
                this.recalculate();
            }
        },
        
        // Generate billing schedule
        generateBillingSchedule(recurringItems) {
            this.billingSchedule = [];
            const startDate = new Date();
            
            for (let i = 0; i < 12; i++) {
                const scheduleDate = new Date(startDate);
                scheduleDate.setMonth(startDate.getMonth() + i);
                
                let monthTotal = 0;
                const items = [];
                
                for (const item of recurringItems) {
                    const shouldBill = this.shouldBillInMonth(item.billing_cycle, i);
                    
                    if (shouldBill) {
                        monthTotal += item.subtotal || (item.base_price * item.quantity);
                        items.push({
                            name: item.name,
                            amount: item.subtotal || (item.base_price * item.quantity),
                            cycle: item.billing_cycle
                        });
                    }
                }
                
                if (monthTotal > 0) {
                    this.billingSchedule.push({
                        date: scheduleDate,
                        amount: monthTotal,
                        items: items
                    });
                }
            }
        },
        
        // Check if item should be billed in a specific month
        shouldBillInMonth(cycle, monthIndex) {
            switch (cycle) {
                case 'monthly':
                    return true;
                case 'quarterly':
                    return monthIndex % 3 === 0;
                case 'semi_annually':
                    return monthIndex % 6 === 0;
                case 'annually':
                    return monthIndex === 0;
                default:
                    return false;
            }
        },
        
        // Update item quantity
        updateItemQuantity(itemId, quantity) {
            const item = this.items.find(i => i.id === itemId);
            if (!item) return;
            
            item.quantity = Math.max(1, parseInt(quantity) || 1);
            this.recalculateItem(item);
        },
        
        // Recalculate single item
        async recalculateItem(item) {
            if (!this.client) {
                item.subtotal = item.base_price * item.quantity;
                this.recalculate();
                return;
            }
            
            try {
                const response = await fetch('/api/products/calculate-price', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        product_id: item.id,
                        client_id: this.client.id,
                        quantity: item.quantity
                    })
                });
                
                const data = await response.json();
                
                item.unit_price = data.unit_price;
                item.subtotal = data.subtotal;
                item.savings = data.savings;
                
                this.recalculate();
            } catch (error) {
                console.error('Error recalculating item:', error);
                item.subtotal = item.base_price * item.quantity;
                this.recalculate();
            }
        },
        
        // Remove item
        removeItem(itemId) {
            const index = this.items.findIndex(i => i.id === itemId);
            if (index > -1) {
                this.items.splice(index, 1);
                this.recalculate();
            }
        },
        
        // Format currency
        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: this.currency
            }).format(amount || 0);
        },
        
        // Format percentage
        formatPercentage(value) {
            return `${(value || 0).toFixed(2)}%`;
        },
        
        // Format date
        formatDate(date) {
            return new Intl.DateTimeFormat('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            }).format(date);
        },
        
        // Get savings amount
        get totalSavings() {
            return this.items.reduce((sum, item) => {
                return sum + (item.savings || 0);
            }, 0) + this.discount;
        },
        
        // Get savings percentage
        get savingsPercentage() {
            const originalTotal = this.items.reduce((sum, item) => {
                return sum + (item.base_price * item.quantity);
            }, 0);
            
            if (originalTotal === 0) return 0;
            
            return (this.totalSavings / originalTotal) * 100;
        },
        
        // Check if has recurring items
        get hasRecurringItems() {
            return this.items.some(i => i.billing_model === 'subscription');
        },
        
        // Check if has one-time items
        get hasOneTimeItems() {
            return this.items.some(i => 
                i.billing_model === 'one_time' || !i.billing_model
            );
        },
        
        // Get monthly recurring revenue
        get monthlyRecurringRevenue() {
            return this.recurringTotal;
        },
        
        // Get annual recurring revenue
        get annualRecurringRevenue() {
            return this.recurringTotal * 12;
        },
        
        // Emit calculation result
        emitCalculation() {
            this.$dispatch('pricing-calculated', {
                subtotal: this.subtotal,
                discount: this.discount,
                tax: this.tax,
                total: this.total,
                savings: this.totalSavings,
                items: this.items,
                recurring: {
                    monthly: this.monthlyRecurringRevenue,
                    annual: this.annualRecurringRevenue
                },
                schedule: this.billingSchedule
            });
        },
        
        // Export pricing breakdown
        exportBreakdown() {
            const breakdown = {
                date: new Date().toISOString(),
                client: this.client,
                items: this.items,
                pricing: {
                    subtotal: this.subtotal,
                    discount: this.discount,
                    tax: this.tax,
                    total: this.total,
                    savings: this.totalSavings
                },
                recurring: this.hasRecurringItems ? {
                    monthly: this.monthlyRecurringRevenue,
                    annual: this.annualRecurringRevenue,
                    schedule: this.billingSchedule
                } : null
            };
            
            // Download as JSON
            const blob = new Blob([JSON.stringify(breakdown, null, 2)], {
                type: 'application/json'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `pricing-breakdown-${Date.now()}.json`;
            a.click();
            URL.revokeObjectURL(url);
        },
        
        // Currency handling
        async handleCurrencyChange(newCurrency) {
            if (newCurrency === this.currency.display) return;
            
            try {
                if (this.enableRealTimeRates) {
                    await this.updateCurrencyRates(newCurrency);
                }
                
                this.currency.display = newCurrency;
                this.calculatePricing(); // Recalculate with new currency
                
            } catch (error) {
                console.error('Currency change failed:', error);
            }
        },

        async loadCurrencyRates() {
            if (!this.enableRealTimeRates) return;
            
            try {
                const response = await fetch('/api/currency/rates');
                if (response.ok) {
                    const data = await response.json();
                    data.rates.forEach(rate => {
                        this.currency.rates.set(rate.currency, rate.rate);
                    });
                    this.currency.lastUpdate = new Date(data.last_update);
                }
            } catch (error) {
                console.error('Failed to load currency rates:', error);
            }
        },

        // Tax configuration
        async loadTaxConfiguration() {
            try {
                const response = await fetch('/api/tax/configuration');
                if (response.ok) {
                    const data = await response.json();
                    this.tax = { ...this.tax, ...data };
                }
            } catch (error) {
                console.error('Failed to load tax configuration:', error);
            }
        },

        async updateTaxJurisdiction(clientId) {
            if (!clientId) return;
            
            try {
                const response = await fetch(`/api/clients/${clientId}/tax-jurisdiction`);
                if (response.ok) {
                    const data = await response.json();
                    this.tax.jurisdiction = data.jurisdiction;
                    this.calculatePricing(); // Recalculate with new jurisdiction
                }
            } catch (error) {
                console.error('Failed to update tax jurisdiction:', error);
            }
        },

        // Analytics
        calculateAnalytics() {
            const items = this.$store.quote.selectedItems;
            let totalCost = 0;
            let totalRevenue = this.pricing.total;
            
            // Calculate costs (if available)
            items.forEach(item => {
                if (item.cost) {
                    totalCost += parseFloat(item.cost) * parseFloat(item.quantity);
                }
            });
            
            // Calculate margin
            if (totalCost > 0) {
                this.analytics.margin = ((totalRevenue - totalCost) / totalRevenue) * 100;
                this.analytics.profitability = totalRevenue - totalCost;
            }
        },

        generatePriceRecommendations() {
            const recommendations = [];
            
            // Volume discount recommendation
            if (this.pricing.subtotal > 10000 && !this.discounts.volume.enabled) {
                recommendations.push({
                    type: 'volume_discount',
                    message: 'Consider offering volume discount for large orders',
                    impact: 'positive'
                });
            }
            
            // Margin recommendation
            if (this.analytics.margin < 20) {
                recommendations.push({
                    type: 'low_margin',
                    message: 'Profit margin is below recommended 20%',
                    impact: 'warning'
                });
            }
            
            this.analytics.priceRecommendations = recommendations;
        },

        // Real-time updates
        setupRealTimeUpdates() {
            // Setup WebSocket or polling for real-time price updates
            if (this.enableRealTimeRates) {
                setInterval(() => {
                    this.loadCurrencyRates();
                }, 300000); // Update every 5 minutes
            }
        },

        // Utility methods
        formatCurrency(amount, currency = null) {
            const displayCurrency = currency || this.currency.display || this.$store.quote.document.currency_code || 'USD';
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: displayCurrency
            }).format(amount || 0);
        },

        formatPercentage(value) {
            return `${(value || 0).toFixed(2)}%`;
        },

        // Export pricing breakdown
        exportPricingBreakdown() {
            const breakdown = {
                timestamp: new Date().toISOString(),
                quote_id: this.$store.quote.quoteId,
                pricing: this.pricing,
                items: this.$store.quote.selectedItems,
                analytics: this.analytics
            };
            
            const dataStr = JSON.stringify(breakdown, null, 2);
            const blob = new Blob([dataStr], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = `pricing-breakdown-${Date.now()}.json`;
            a.click();
            
            URL.revokeObjectURL(url);
        },

        // Legacy method compatibility
        recalculate() {
            return this.calculatePricing();
        },

        // Backward compatibility getters
        get subtotal() { return this.pricing.subtotal; },
        get discount() { return this.pricing.totalDiscount; },
        get tax() { return this.pricing.taxAmount; },
        get total() { return this.pricing.total; },
        get totalSavings() { return this.pricing.savings; },
        get monthlyRecurringRevenue() { return this.pricing.recurring.monthly; },
        get annualRecurringRevenue() { return this.pricing.recurring.annual; }
    }));
});