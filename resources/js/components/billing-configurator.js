// Billing Configurator Component for Flexible Billing Options
export default function billingConfigurator() {
    return {
        // State
        selectedItems: [],
        billingOptions: {
            model: 'one_time', // one_time, subscription, usage_based, hybrid
            cycle: 'monthly',
            startDate: '',
            endDate: '',
            autoRenew: true,
            paymentTerms: 30,
            lateFeePercentage: 0,
            earlyPaymentDiscount: 0,
            customTerms: ''
        },
        
        // Usage tracking
        usageTracking: {
            enabled: false,
            metric: 'hours',
            includedAmount: 0,
            overageRate: 0,
            trackingMethod: 'manual', // manual, api, automated
            billingThreshold: 0
        },
        
        // Subscription options
        subscription: {
            trialDays: 0,
            setupFee: 0,
            cancellationFee: 0,
            minimumTerm: 0,
            gracePeriodDays: 7,
            proration: true,
            upgradeDowngrade: 'immediate' // immediate, next_cycle, prorate
        },
        
        // Payment methods
        paymentMethods: {
            creditCard: true,
            ach: false,
            check: false,
            wire: false,
            custom: false
        },
        
        // Notifications
        notifications: {
            invoiceReminder: true,
            reminderDays: [7, 3, 1],
            paymentReceived: true,
            paymentFailed: true,
            usageAlerts: true,
            usageThresholds: [50, 75, 90, 100]
        },
        
        // Advanced options
        advanced: {
            multiCurrency: false,
            currencies: ['USD'],
            taxExempt: false,
            taxId: '',
            poNumber: '',
            contractRequired: false,
            approvalRequired: false,
            approvalThreshold: 0
        },
        
        // UI State
        activeSection: 'basic',
        showAdvanced: false,
        validationErrors: [],
        
        // Initialize
        init() {
            this.setupEventListeners();
            this.loadDefaults();
            this.validateConfiguration();
        },
        
        // Setup event listeners
        setupEventListeners() {
            // Listen for selected products
            window.addEventListener('products-selected', (e) => {
                this.selectedItems = e.detail.items || [];
                this.suggestBillingModel();
            });
            
            // Watch for changes
            this.$watch('billingOptions', () => {
                this.validateConfiguration();
                this.emitConfigurationChange();
            }, { deep: true });
            
            this.$watch('usageTracking', () => {
                if (this.usageTracking.enabled) {
                    this.billingOptions.model = 'usage_based';
                }
            }, { deep: true });
        },
        
        // Load default settings
        async loadDefaults() {
            try {
                const response = await fetch('/api/settings/billing-defaults');
                const data = await response.json();
                
                // Apply defaults
                this.billingOptions.paymentTerms = data.payment_terms || 30;
                this.billingOptions.lateFeePercentage = data.late_fee_percentage || 0;
                this.subscription.gracePeriodDays = data.grace_period_days || 7;
                this.notifications.reminderDays = data.reminder_days || [7, 3, 1];
            } catch (error) {
                console.error('Error loading defaults:', error);
            }
        },
        
        // Suggest billing model based on selected items
        suggestBillingModel() {
            if (this.selectedItems.length === 0) return;
            
            // Check if all items are subscriptions
            const allSubscriptions = this.selectedItems.every(item => 
                item.billing_model === 'subscription'
            );
            
            // Check if any are usage-based
            const hasUsageBased = this.selectedItems.some(item => 
                item.billing_model === 'usage_based'
            );
            
            // Check if all are one-time
            const allOneTime = this.selectedItems.every(item => 
                item.billing_model === 'one_time' || !item.billing_model
            );
            
            if (allSubscriptions) {
                this.billingOptions.model = 'subscription';
                this.suggestBillingCycle();
            } else if (hasUsageBased) {
                this.billingOptions.model = 'usage_based';
                this.usageTracking.enabled = true;
            } else if (allOneTime) {
                this.billingOptions.model = 'one_time';
            } else {
                this.billingOptions.model = 'hybrid';
            }
        },
        
        // Suggest billing cycle
        suggestBillingCycle() {
            const cycles = this.selectedItems
                .filter(item => item.billing_cycle)
                .map(item => item.billing_cycle);
            
            if (cycles.length === 0) return;
            
            // Find most common cycle
            const cycleCounts = {};
            cycles.forEach(cycle => {
                cycleCounts[cycle] = (cycleCounts[cycle] || 0) + 1;
            });
            
            const mostCommon = Object.entries(cycleCounts)
                .sort((a, b) => b[1] - a[1])[0];
            
            if (mostCommon) {
                this.billingOptions.cycle = mostCommon[0];
            }
        },
        
        // Validate configuration
        validateConfiguration() {
            this.validationErrors = [];
            
            // Validate dates
            if (this.billingOptions.startDate && this.billingOptions.endDate) {
                const start = new Date(this.billingOptions.startDate);
                const end = new Date(this.billingOptions.endDate);
                
                if (end <= start) {
                    this.validationErrors.push('End date must be after start date');
                }
            }
            
            // Validate subscription settings
            if (this.billingOptions.model === 'subscription') {
                if (this.subscription.minimumTerm < 0) {
                    this.validationErrors.push('Minimum term cannot be negative');
                }
                
                if (this.subscription.trialDays < 0) {
                    this.validationErrors.push('Trial days cannot be negative');
                }
            }
            
            // Validate usage settings
            if (this.usageTracking.enabled) {
                if (this.usageTracking.overageRate < 0) {
                    this.validationErrors.push('Overage rate cannot be negative');
                }
                
                if (this.usageTracking.includedAmount < 0) {
                    this.validationErrors.push('Included amount cannot be negative');
                }
            }
            
            // Validate payment terms
            if (this.billingOptions.paymentTerms < 0) {
                this.validationErrors.push('Payment terms cannot be negative');
            }
            
            return this.validationErrors.length === 0;
        },
        
        // Set billing model
        setBillingModel(model) {
            this.billingOptions.model = model;
            
            // Enable/disable related options
            if (model === 'usage_based') {
                this.usageTracking.enabled = true;
            } else if (model === 'subscription') {
                this.billingOptions.autoRenew = true;
            }
        },
        
        // Set billing cycle
        setBillingCycle(cycle) {
            this.billingOptions.cycle = cycle;
            
            // Adjust minimum term for annual
            if (cycle === 'annually') {
                this.subscription.minimumTerm = Math.max(this.subscription.minimumTerm, 12);
            }
        },
        
        // Toggle payment method
        togglePaymentMethod(method) {
            this.paymentMethods[method] = !this.paymentMethods[method];
        },
        
        // Add reminder day
        addReminderDay() {
            const day = prompt('Enter number of days before due date:');
            if (day && !isNaN(day)) {
                const days = parseInt(day);
                if (days > 0 && !this.notifications.reminderDays.includes(days)) {
                    this.notifications.reminderDays.push(days);
                    this.notifications.reminderDays.sort((a, b) => b - a);
                }
            }
        },
        
        // Remove reminder day
        removeReminderDay(day) {
            const index = this.notifications.reminderDays.indexOf(day);
            if (index > -1) {
                this.notifications.reminderDays.splice(index, 1);
            }
        },
        
        // Add usage threshold
        addUsageThreshold() {
            const threshold = prompt('Enter usage threshold percentage:');
            if (threshold && !isNaN(threshold)) {
                const percent = parseInt(threshold);
                if (percent > 0 && percent <= 100 && !this.notifications.usageThresholds.includes(percent)) {
                    this.notifications.usageThresholds.push(percent);
                    this.notifications.usageThresholds.sort((a, b) => a - b);
                }
            }
        },
        
        // Remove usage threshold
        removeUsageThreshold(threshold) {
            const index = this.notifications.usageThresholds.indexOf(threshold);
            if (index > -1) {
                this.notifications.usageThresholds.splice(index, 1);
            }
        },
        
        // Add currency
        addCurrency() {
            const currency = prompt('Enter currency code (e.g., EUR, GBP):');
            if (currency && currency.length === 3) {
                const code = currency.toUpperCase();
                if (!this.advanced.currencies.includes(code)) {
                    this.advanced.currencies.push(code);
                }
            }
        },
        
        // Remove currency
        removeCurrency(currency) {
            if (this.advanced.currencies.length > 1) {
                const index = this.advanced.currencies.indexOf(currency);
                if (index > -1) {
                    this.advanced.currencies.splice(index, 1);
                }
            }
        },
        
        // Calculate effective start date
        getEffectiveStartDate() {
            if (this.billingOptions.startDate) {
                return new Date(this.billingOptions.startDate);
            }
            
            const today = new Date();
            
            // Add trial period if applicable
            if (this.billingOptions.model === 'subscription' && this.subscription.trialDays > 0) {
                today.setDate(today.getDate() + this.subscription.trialDays);
            }
            
            return today;
        },
        
        // Calculate first billing date
        getFirstBillingDate() {
            const startDate = this.getEffectiveStartDate();
            
            if (this.billingOptions.model === 'one_time') {
                return startDate;
            }
            
            // For subscriptions, align to cycle
            if (this.billingOptions.cycle === 'monthly') {
                // Bill on same day each month
                return startDate;
            } else if (this.billingOptions.cycle === 'annually') {
                // Bill on anniversary
                return startDate;
            }
            
            return startDate;
        },
        
        // Generate billing preview
        generatePreview() {
            const preview = {
                model: this.billingOptions.model,
                cycle: this.billingOptions.cycle,
                startDate: this.getEffectiveStartDate(),
                firstBillingDate: this.getFirstBillingDate(),
                items: this.selectedItems,
                terms: {
                    payment: this.billingOptions.paymentTerms,
                    late_fee: this.billingOptions.lateFeePercentage,
                    early_discount: this.billingOptions.earlyPaymentDiscount
                }
            };
            
            if (this.billingOptions.model === 'subscription') {
                preview.subscription = {
                    trial_days: this.subscription.trialDays,
                    setup_fee: this.subscription.setupFee,
                    minimum_term: this.subscription.minimumTerm,
                    auto_renew: this.billingOptions.autoRenew
                };
            }
            
            if (this.usageTracking.enabled) {
                preview.usage = {
                    metric: this.usageTracking.metric,
                    included: this.usageTracking.includedAmount,
                    overage_rate: this.usageTracking.overageRate
                };
            }
            
            return preview;
        },
        
        // Show preview modal
        showPreview() {
            const preview = this.generatePreview();
            this.$dispatch('show-billing-preview', { preview });
        },
        
        // Save as template
        async saveAsTemplate() {
            const name = prompt('Enter template name:');
            if (!name) return;
            
            try {
                const response = await fetch('/api/billing-templates', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name: name,
                        configuration: this.getConfiguration()
                    })
                });
                
                if (response.ok) {
                    this.showNotification('Template saved successfully', 'success');
                }
            } catch (error) {
                console.error('Error saving template:', error);
                this.showNotification('Failed to save template', 'error');
            }
        },
        
        // Load template
        async loadTemplate(templateId) {
            try {
                const response = await fetch(`/api/billing-templates/${templateId}`);
                const data = await response.json();
                
                // Apply template configuration
                this.applyConfiguration(data.configuration);
                this.showNotification('Template loaded successfully', 'success');
            } catch (error) {
                console.error('Error loading template:', error);
                this.showNotification('Failed to load template', 'error');
            }
        },
        
        // Get configuration
        getConfiguration() {
            return {
                billing_options: this.billingOptions,
                usage_tracking: this.usageTracking,
                subscription: this.subscription,
                payment_methods: this.paymentMethods,
                notifications: this.notifications,
                advanced: this.advanced
            };
        },
        
        // Apply configuration
        applyConfiguration(config) {
            if (config.billing_options) {
                Object.assign(this.billingOptions, config.billing_options);
            }
            if (config.usage_tracking) {
                Object.assign(this.usageTracking, config.usage_tracking);
            }
            if (config.subscription) {
                Object.assign(this.subscription, config.subscription);
            }
            if (config.payment_methods) {
                Object.assign(this.paymentMethods, config.payment_methods);
            }
            if (config.notifications) {
                Object.assign(this.notifications, config.notifications);
            }
            if (config.advanced) {
                Object.assign(this.advanced, config.advanced);
            }
        },
        
        // Reset to defaults
        resetToDefaults() {
            if (confirm('Reset all billing configuration to defaults?')) {
                this.loadDefaults();
                this.showNotification('Configuration reset to defaults', 'info');
            }
        },
        
        // Emit configuration change
        emitConfigurationChange() {
            this.$dispatch('billing-configured', {
                configuration: this.getConfiguration(),
                valid: this.validationErrors.length === 0,
                preview: this.generatePreview()
            });
        },
        
        // Format helpers
        formatCycle(cycle) {
            const cycles = {
                'one_time': 'One-time',
                'weekly': 'Weekly',
                'monthly': 'Monthly',
                'quarterly': 'Quarterly',
                'semi_annually': 'Semi-Annually',
                'annually': 'Annually'
            };
            return cycles[cycle] || cycle;
        },
        
        formatDate(date) {
            if (!date) return '';
            return new Intl.DateTimeFormat('en-US').format(new Date(date));
        },
        
        // Show notification
        showNotification(message, type = 'info') {
            this.$dispatch('notify', { message, type });
        },
        
        // Computed properties
        get isValid() {
            return this.validationErrors.length === 0;
        },
        
        get hasSubscriptionItems() {
            return this.selectedItems.some(item => 
                item.billing_model === 'subscription'
            );
        },
        
        get hasUsageBasedItems() {
            return this.selectedItems.some(item => 
                item.billing_model === 'usage_based'
            );
        },
        
        get requiresContract() {
            return this.advanced.contractRequired || 
                   (this.subscription.minimumTerm > 0 && this.billingOptions.model === 'subscription');
        },
        
        get estimatedRevenue() {
            // Calculate estimated monthly revenue
            let monthly = 0;
            
            for (const item of this.selectedItems) {
                if (item.billing_model === 'subscription') {
                    if (item.billing_cycle === 'monthly') {
                        monthly += item.subtotal || 0;
                    } else if (item.billing_cycle === 'annually') {
                        monthly += (item.subtotal || 0) / 12;
                    }
                }
            }
            
            return monthly;
        }
    };
}