/**
 * Enhanced Tax Calculation Engine Component
 * Handles complex tax calculations for quotes and invoices with real-time previews
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('taxCalculationEngine', (config = {}) => ({
        // Configuration
        defaultTaxRate: config.defaultTaxRate || 0,
        enableComplexTax: config.enableComplexTax !== false,
        taxJurisdictions: config.taxJurisdictions || [],
        enableRealTimePreview: config.enableRealTimePreview !== false,
        debounceDelay: config.debounceDelay || 300,
        
        // API endpoints
        endpoints: {
            calculate: '/api/tax-engine/calculate',
            calculateBulk: '/api/tax-engine/calculate-bulk',
            previewQuote: '/api/tax-engine/preview-quote',
            profile: '/api/tax-engine/profile',
            statistics: '/api/tax-engine/statistics',
        },
        
        // Tax state
        taxRules: new Map(),
        taxExemptions: new Map(),
        currentTaxProfile: null,
        isCalculating: false,
        lastCalculationTime: null,
        
        // Performance tracking
        performance: {
            averageCalculationTime: 0,
            calculationsCount: 0,
            cacheHitRate: 0,
        },
        
        // Tax calculation results
        taxBreakdown: {
            subtotal: 0,
            taxableAmount: 0,
            exemptAmount: 0,
            totalTax: 0,
            grandTotal: 0,
            taxes: [],
            performance: null,
            calculationId: null,
        },
        
        init() {
            this.setupEventListeners();
            this.loadPerformanceStatistics();
            
            // Setup debounced calculation for real-time preview
            this.debouncedCalculate = this.debounce(this.calculateTaxFromAPI.bind(this), this.debounceDelay);
        },
        
        setupEventListeners() {
            document.addEventListener('quote-items-changed', (e) => {
                if (this.enableRealTimePreview) {
                    this.debouncedCalculate(e.detail.items, e.detail.client);
                } else {
                    this.calculateTaxFromAPI(e.detail.items, e.detail.client);
                }
            });
            
            document.addEventListener('client-changed', (e) => {
                this.updateTaxProfile(e.detail.client);
            });
            
            document.addEventListener('item-price-changed', (e) => {
                if (this.enableRealTimePreview) {
                    this.debouncedCalculate(e.detail.items, e.detail.client);
                }
            });
        },
        
        async loadPerformanceStatistics() {
            try {
                const response = await fetch(this.endpoints.statistics);
                if (response.ok) {
                    const data = await response.json();
                    this.performance = {
                        ...this.performance,
                        ...data.data,
                    };
                }
            } catch (error) {
                console.warn('Failed to load performance statistics:', error);
            }
        },
        
        async calculateTaxFromAPI(items, client = null) {
            if (!items || items.length === 0) {
                this.resetTaxBreakdown();
                return;
            }
            
            this.isCalculating = true;
            const startTime = performance.now();
            
            try {
                const payload = {
                    items: items.map(item => ({
                        base_price: parseFloat(item.price || 0),
                        quantity: parseFloat(item.quantity || 1),
                        name: item.name || item.description || 'Item',
                        category_id: item.category_id || null,
                        product_id: item.product_id || null,
                        tax_data: item.tax_data || {},
                    })),
                    customer_id: client?.id || null,
                    calculation_type: 'preview'
                };
                
                const response = await fetch(this.endpoints.calculateBulk, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    },
                    body: JSON.stringify(payload)
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    this.processTaxResults(data.data);
                    this.updatePerformanceMetrics(data.data.performance);
                    
                    // Dispatch event for other components
                    document.dispatchEvent(new CustomEvent('tax-calculated', {
                        detail: {
                            breakdown: this.taxBreakdown,
                            items: data.data.items,
                            performance: data.data.performance,
                        }
                    }));
                } else {
                    throw new Error(data.error || 'Tax calculation failed');
                }
                
            } catch (error) {
                console.error('Tax calculation error:', error);
                this.handleCalculationError(error);
            } finally {
                this.isCalculating = false;
                const endTime = performance.now();
                this.lastCalculationTime = endTime - startTime;
            }
        },
        
        processTaxResults(data) {
            this.taxBreakdown = {
                subtotal: data.summary?.subtotal || 0,
                taxableAmount: data.summary?.subtotal || 0,
                exemptAmount: 0, // Calculate from breakdown if needed
                totalTax: data.summary?.total_tax || 0,
                grandTotal: data.summary?.total_amount || 0,
                taxes: this.extractTaxDetails(data.items),
                performance: data.performance,
                calculationId: data.calculation_id,
                effectiveTaxRate: data.summary?.effective_tax_rate || 0,
                itemDetails: data.items,
            };
        },
        
        extractTaxDetails(items) {
            const taxMap = new Map();
            
            items.forEach(item => {
                if (item.tax_breakdown) {
                    Object.entries(item.tax_breakdown).forEach(([taxKey, taxInfo]) => {
                        if (!taxMap.has(taxKey)) {
                            taxMap.set(taxKey, {
                                name: taxInfo.name || taxKey,
                                type: taxInfo.type || 'tax',
                                authority: taxInfo.authority || 'Unknown',
                                rate: taxInfo.rate || 0,
                                amount: 0,
                                items: [],
                            });
                        }
                        
                        const existingTax = taxMap.get(taxKey);
                        existingTax.amount += taxInfo.amount || 0;
                        existingTax.items.push({
                            itemId: item.item_id,
                            amount: taxInfo.amount || 0,
                        });
                    });
                }
            });
            
            return Array.from(taxMap.values());
        },
        
        async calculateQuotePreview(quoteData) {
            this.isCalculating = true;
            
            try {
                const response = await fetch(this.endpoints.previewQuote, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    },
                    body: JSON.stringify({ quote_data: quoteData })
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    return data.data;
                } else {
                    throw new Error(data.error || 'Quote preview failed');
                }
                
            } catch (error) {
                console.error('Quote preview error:', error);
                throw error;
            } finally {
                this.isCalculating = false;
            }
        },
        
        // Utility methods
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
        
        updatePerformanceMetrics(performanceData) {
            if (performanceData) {
                this.performance.calculationsCount++;
                this.performance.averageCalculationTime = 
                    (this.performance.averageCalculationTime * (this.performance.calculationsCount - 1) + 
                     performanceData.calculation_time_ms) / this.performance.calculationsCount;
            }
        },
        
        handleCalculationError(error) {
            // Reset to basic calculation or show error
            this.resetTaxBreakdown();
            this.taxBreakdown.error = error.message;
            
            // Dispatch error event
            document.dispatchEvent(new CustomEvent('tax-calculation-error', {
                detail: { error: error.message }
            }));
        },
        
        resetTaxBreakdown() {
            this.taxBreakdown = {
                subtotal: 0,
                taxableAmount: 0,
                exemptAmount: 0,
                totalTax: 0,
                grandTotal: 0,
                taxes: [],
                performance: null,
                calculationId: null,
                effectiveTaxRate: 0,
                itemDetails: [],
                error: null,
            };
        },
        
        async updateTaxProfile(client) {
            if (!client) {
                this.currentTaxProfile = null;
                return;
            }
            
            try {
                const response = await fetch(`${this.endpoints.profile}?customer_id=${client.id}`);
                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        this.currentTaxProfile = data.data;
                    }
                }
            } catch (error) {
                console.warn('Failed to load tax profile:', error);
            }
        },
        
        // Public API methods
        async calculateSingle(itemData, client = null) {
            try {
                const response = await fetch(this.endpoints.calculate, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    },
                    body: JSON.stringify({
                        base_price: parseFloat(itemData.price || 0),
                        quantity: parseFloat(itemData.quantity || 1),
                        category_id: itemData.category_id || null,
                        product_id: itemData.product_id || null,
                        customer_id: client?.id || null,
                        tax_data: itemData.tax_data || {},
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                return data.success ? data.data : null;
                
            } catch (error) {
                console.error('Single tax calculation error:', error);
                return null;
            }
        },
        
        getFormattedTaxBreakdown() {
            const formatter = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            });
            
            return {
                subtotal: formatter.format(this.taxBreakdown.subtotal),
                totalTax: formatter.format(this.taxBreakdown.totalTax),
                grandTotal: formatter.format(this.taxBreakdown.grandTotal),
                effectiveTaxRate: `${this.taxBreakdown.effectiveTaxRate.toFixed(2)}%`,
                taxes: this.taxBreakdown.taxes.map(tax => ({
                    ...tax,
                    formattedAmount: formatter.format(tax.amount),
                    formattedRate: `${tax.rate.toFixed(2)}%`,
                })),
            };
        },
        
        isVoIPService(item) {
            return item.service_type && 
                   ['voip', 'telecom', 'hosted_pbx', 'sip_trunking'].includes(item.service_type);
        },
        
        requiresTaxData(item) {
            return this.isVoIPService(item) || 
                   (this.currentTaxProfile && this.currentTaxProfile.required_fields.length > 0);
        },
        
        // Legacy methods for backward compatibility  
        calculateTax(items, client = null) {
            // Redirect to new API-based method
            this.calculateTaxFromAPI(items, client);
        },
        
        groupItemsByTaxCategory(items) {
            const groups = new Map();
            
            items.forEach(item => {
                const category = item.tax_category || 'standard';
                if (!groups.has(category)) {
                    groups.set(category, []);
                }
                groups.get(category).push(item);
            });
            
            return groups;
        }
        
        // End of component
    }));
});