/**
 * Currency Conversion Component
 * Handles multi-currency support and real-time exchange rates
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('currencyConversion', (config = {}) => ({
        // Configuration
        defaultCurrency: config.defaultCurrency || 'USD',
        enableRealTimeRates: config.enableRealTimeRates !== false,
        updateInterval: config.updateInterval || 3600000, // 1 hour
        
        // Currency state
        baseCurrency: 'USD',
        selectedCurrency: 'USD',
        exchangeRates: new Map(),
        lastUpdateTime: null,
        
        // Supported currencies
        supportedCurrencies: [
            { code: 'USD', name: 'US Dollar', symbol: '$' },
            { code: 'EUR', name: 'Euro', symbol: '€' },
            { code: 'GBP', name: 'British Pound', symbol: '£' },
            { code: 'CAD', name: 'Canadian Dollar', symbol: 'C$' },
            { code: 'AUD', name: 'Australian Dollar', symbol: 'A$' },
            { code: 'JPY', name: 'Japanese Yen', symbol: '¥' }
        ],
        
        // Conversion state
        isLoading: false,
        error: null,
        
        init() {
            this.loadExchangeRates();
            this.setupPeriodicUpdates();
            this.loadUserPreferences();
        },
        
        async loadExchangeRates() {
            this.isLoading = true;
            this.error = null;
            
            try {
                const response = await fetch('/api/exchange-rates');
                if (response.ok) {
                    const data = await response.json();
                    this.processExchangeRates(data);
                } else {
                    throw new Error('Failed to load exchange rates');
                }
            } catch (error) {
                this.error = error.message;
                console.error('Currency conversion error:', error);
            } finally {
                this.isLoading = false;
            }
        },
        
        processExchangeRates(data) {
            this.exchangeRates = new Map(Object.entries(data.rates));
            this.baseCurrency = data.base;
            this.lastUpdateTime = new Date(data.timestamp);
        },
        
        convertAmount(amount, fromCurrency = null, toCurrency = null) {
            if (!amount) return 0;
            
            const from = fromCurrency || this.baseCurrency;
            const to = toCurrency || this.selectedCurrency;
            
            if (from === to) return amount;
            
            const fromRate = this.getExchangeRate(from);
            const toRate = this.getExchangeRate(to);
            
            return (amount / fromRate) * toRate;
        },
        
        getExchangeRate(currency) {
            if (currency === this.baseCurrency) return 1;
            return this.exchangeRates.get(currency) || 1;
        },
        
        formatCurrency(amount, currency = null) {
            const currencyCode = currency || this.selectedCurrency;
            const currencyInfo = this.supportedCurrencies.find(c => c.code === currencyCode);
            
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currencyCode,
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount);
        },
        
        setupPeriodicUpdates() {
            if (this.enableRealTimeRates) {
                setInterval(() => {
                    this.loadExchangeRates();
                }, this.updateInterval);
            }
        },
        
        loadUserPreferences() {
            const stored = localStorage.getItem('currency_preference');
            if (stored) {
                this.selectedCurrency = stored;
            }
        },
        
        changeCurrency(currency) {
            this.selectedCurrency = currency;
            localStorage.setItem('currency_preference', currency);
            
            this.$dispatch('currency-changed', {
                currency: currency,
                symbol: this.getCurrencySymbol(currency)
            });
        },
        
        getCurrencySymbol(currency) {
            const currencyInfo = this.supportedCurrencies.find(c => c.code === currency);
            return currencyInfo ? currencyInfo.symbol : currency;
        }
    }));
});