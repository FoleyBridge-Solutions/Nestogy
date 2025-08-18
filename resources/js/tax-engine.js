/**
 * Comprehensive Tax Engine JavaScript Class
 * 
 * Standalone class for tax calculations that can be used across the application
 */
class TaxEngine {
    constructor(config = {}) {
        this.config = {
            apiEndpoint: '/api/tax-engine',
            cacheTimeout: 5 * 60 * 1000, // 5 minutes
            debounceDelay: 500,
            ...config
        };
        
        this.cache = new Map();
        this.currentProfile = null;
        this.taxData = {};
        this.listeners = [];
    }
    
    /**
     * Calculate tax for given parameters
     */
    async calculate(params) {
        const cacheKey = this.getCacheKey(params);
        
        // Check cache first
        if (this.cache.has(cacheKey)) {
            const cached = this.cache.get(cacheKey);
            if (Date.now() - cached.timestamp < this.config.cacheTimeout) {
                return cached.data;
            }
        }
        
        try {
            const response = await fetch(`${this.config.apiEndpoint}/calculate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(params)
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                // Cache the result
                this.cache.set(cacheKey, {
                    data: result.data,
                    timestamp: Date.now()
                });
                
                // Notify listeners
                this.notifyListeners('calculated', result.data);
                
                return result.data;
            } else {
                throw new Error(result.error || 'Tax calculation failed');
            }
        } catch (error) {
            console.error('Tax calculation error:', error);
            this.notifyListeners('error', error);
            throw error;
        }
    }
    
    /**
     * Get tax profile for a category
     */
    async getProfile(categoryId, categoryType, productId) {
        const params = new URLSearchParams();
        if (categoryId) params.append('category_id', categoryId);
        if (categoryType) params.append('category_type', categoryType);
        if (productId) params.append('product_id', productId);
        
        try {
            const response = await fetch(`${this.config.apiEndpoint}/profile?${params}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.currentProfile = result.data;
                this.notifyListeners('profileLoaded', this.currentProfile);
                return this.currentProfile;
            }
        } catch (error) {
            console.error('Error loading tax profile:', error);
            this.notifyListeners('error', error);
        }
        
        return null;
    }
    
    /**
     * Get required fields for current profile or category
     */
    async getRequiredFields(categoryId, categoryType, productId) {
        const params = new URLSearchParams();
        if (categoryId) params.append('category_id', categoryId);
        if (categoryType) params.append('category_type', categoryType);
        if (productId) params.append('product_id', productId);
        
        try {
            const response = await fetch(`${this.config.apiEndpoint}/required-fields?${params}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                return result.data.required_fields;
            }
        } catch (error) {
            console.error('Error getting required fields:', error);
        }
        
        return {};
    }
    
    /**
     * Validate tax data against profile requirements
     */
    async validateTaxData(taxData, categoryId, categoryType, productId) {
        try {
            const response = await fetch(`${this.config.apiEndpoint}/validate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    tax_data: taxData,
                    category_id: categoryId,
                    category_type: categoryType,
                    product_id: productId
                })
            });
            
            const result = await response.json();
            return result.success;
        } catch (error) {
            console.error('Error validating tax data:', error);
            return false;
        }
    }
    
    /**
     * Get customer address
     */
    async getCustomerAddress(customerId) {
        try {
            const response = await fetch(`${this.config.apiEndpoint}/customer/${customerId}/address`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                return result.data.address;
            }
        } catch (error) {
            console.error('Error getting customer address:', error);
        }
        
        return null;
    }
    
    /**
     * Get all available tax profiles
     */
    async getAvailableProfiles() {
        try {
            const response = await fetch(`${this.config.apiEndpoint}/profiles`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                return result.data.profiles;
            }
        } catch (error) {
            console.error('Error getting profiles:', error);
        }
        
        return [];
    }
    
    /**
     * Get applicable tax types for a category
     */
    async getApplicableTaxTypes(categoryType) {
        try {
            const response = await fetch(`${this.config.apiEndpoint}/tax-types?category_type=${categoryType}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                return result.data.tax_types;
            }
        } catch (error) {
            console.error('Error getting tax types:', error);
        }
        
        return [];
    }
    
    /**
     * Format currency
     */
    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    }
    
    /**
     * Format percentage
     */
    formatPercentage(rate) {
        return new Intl.NumberFormat('en-US', {
            style: 'percent',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(rate / 100);
    }
    
    /**
     * Get CSRF token
     */
    getCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : '';
    }
    
    /**
     * Generate cache key
     */
    getCacheKey(params) {
        return JSON.stringify({
            price: params.base_price,
            category: params.category_id || params.category_type,
            customer: params.customer_id,
            address: params.customer_address
        });
    }
    
    /**
     * Add event listener
     */
    addEventListener(event, callback) {
        this.listeners.push({ event, callback });
    }
    
    /**
     * Remove event listener
     */
    removeEventListener(event, callback) {
        this.listeners = this.listeners.filter(
            listener => listener.event !== event || listener.callback !== callback
        );
    }
    
    /**
     * Notify listeners
     */
    notifyListeners(event, data) {
        this.listeners
            .filter(listener => listener.event === event)
            .forEach(listener => listener.callback(data));
    }
    
    /**
     * Clear cache
     */
    clearCache() {
        this.cache.clear();
    }
    
    /**
     * Create debounced function
     */
    debounce(func, delay) {
        let timeoutId;
        return function(...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    }
    
    /**
     * Quick calculation helper
     */
    async quickCalculate(price, customerId, categoryType = 'general') {
        const customerAddress = await this.getCustomerAddress(customerId);
        
        return this.calculate({
            base_price: price,
            quantity: 1,
            customer_id: customerId,
            customer_address: customerAddress,
            category_type: categoryType
        });
    }
    
    /**
     * Get tax summary from calculation
     */
    getTaxSummary(calculation) {
        if (!calculation) return null;
        
        return {
            subtotal: this.formatCurrency(calculation.subtotal),
            taxAmount: this.formatCurrency(calculation.tax_amount),
            taxRate: this.formatPercentage(calculation.tax_rate),
            total: this.formatCurrency(calculation.total),
            engine: calculation.engine_used,
            profile: calculation.tax_profile?.name
        };
    }
    
    /**
     * Get detailed tax breakdown
     */
    getTaxBreakdown(calculation) {
        if (!calculation || !calculation.tax_breakdown) return [];
        
        return Object.entries(calculation.tax_breakdown).map(([code, tax]) => ({
            code,
            name: tax.name,
            rate: this.formatPercentage(tax.rate),
            amount: this.formatCurrency(tax.amount),
            authority: tax.authority,
            recoverable: tax.is_recoverable
        }));
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TaxEngine;
}

// Make available globally
window.TaxEngine = TaxEngine;