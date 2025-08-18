/**
 * Centralized Quote Store for Alpine.js
 * Manages all quote-related state and operations
 */

document.addEventListener('alpine:init', () => {
    Alpine.store('quote', {
        // === STATE ===
        document: {
            client_id: '',
            category_id: '',
            date: new Date().toISOString().split('T')[0],
            expire_date: '',
            currency_code: 'USD',
            scope: '',
            note: '',
            terms_conditions: '',
            discount_type: 'fixed',
            discount_amount: 0,
            status: 'draft',
            items: [],
            voip_config: null,
            pricing_model: null
        },

        selectedItems: [],
        
        pricing: {
            subtotal: 0,
            discount: 0,
            tax: 0,
            total: 0,
            savings: 0,
            recurring: {
                monthly: 0,
                annual: 0
            },
            appliedRules: []
        },

        billingConfig: {
            model: 'one_time',
            cycle: 'monthly',
            paymentTerms: 30,
            startDate: new Date().toISOString().split('T')[0],
            endDate: '',
            autoRenew: false
        },

        ui: {
            currentStep: 1,
            totalSteps: 3,
            loading: false,
            saving: false,
            errors: {},
            showAdvanced: false,
            quickMode: false,
            mobileView: false
        },

        templates: {
            available: [],
            favorites: [],
            recent: [],
            suggested: []
        },

        validation: {
            rules: {},
            errors: {},
            isValid: true
        },

        autoSave: {
            enabled: true,
            interval: 30000, // 30 seconds
            lastSaved: null,
            intervalId: null
        },

        // === COMPUTED PROPERTIES ===
        get isValidStep() {
            return this.validateCurrentStep();
        },

        get hasUnsavedChanges() {
            if (!this.autoSave.lastSaved) return true;
            return (Date.now() - this.autoSave.lastSaved.getTime()) > 60000;
        },

        get selectedItemsCount() {
            return this.selectedItems.length;
        },

        get canProceed() {
            return this.validateCurrentStep() && !this.ui.loading;
        },

        get totalSavings() {
            return this.pricing.discount + this.pricing.savings;
        },

        get isMobile() {
            return window.innerWidth <= 768;
        },

        // === DOCUMENT MANAGEMENT ===
        updateDocument(field, value) {
            this.document[field] = value;
            this.triggerReactiveUpdates();
            this.validateField(field);
            
            if (this.autoSave.enabled) {
                this.scheduleAutoSave();
            }
        },

        resetDocument() {
            this.document = {
                client_id: '',
                category_id: '',
                date: new Date().toISOString().split('T')[0],
                expire_date: '',
                currency_code: 'USD',
                scope: '',
                note: '',
                terms_conditions: '',
                discount_type: 'fixed',
                discount_amount: 0,
                status: 'draft',
                items: [],
                voip_config: null,
                pricing_model: null
            };
            this.selectedItems = [];
            this.resetPricing();
            this.resetValidation();
        },

        // === CLIENT MANAGEMENT ===
        selectClient(clientId, clientData = null) {
            this.updateDocument('client_id', clientId);
            
            if (clientData) {
                // Set currency from client default
                if (clientData.currency_code) {
                    this.updateDocument('currency_code', clientData.currency_code);
                }
                
                // Set default expiration (30 days)
                const expireDate = new Date();
                expireDate.setDate(expireDate.getDate() + 30);
                this.updateDocument('expire_date', expireDate.toISOString().split('T')[0]);
                
                // Load suggested templates
                this.loadClientSuggestions(clientId);
            }
            
            this.clearFieldError('client_id');
        },

        // === ITEM MANAGEMENT ===
        addItem(item) {
            const quoteItem = {
                id: `temp_${Date.now()}_${Math.random()}`,
                product_id: item.id || null,
                service_id: item.service_id || null,
                bundle_id: item.bundle_id || null,
                name: item.name,
                description: item.description || '',
                quantity: item.quantity || 1,
                unit_price: item.unit_price || item.base_price || 0,
                discount: item.discount || 0,
                tax_rate: item.tax_rate || 0,
                subtotal: 0,
                type: item.type || 'product',
                billing_cycle: item.billing_cycle || 'one_time',
                category: item.category || '',
                order: this.selectedItems.length + 1
            };

            quoteItem.subtotal = this.calculateItemSubtotal(quoteItem);
            this.selectedItems.push(quoteItem);
            this.updatePricing();
            this.validateItems();
        },

        removeItem(itemId) {
            const index = this.selectedItems.findIndex(item => item.id === itemId);
            if (index > -1) {
                this.selectedItems.splice(index, 1);
                this.reorderItems();
                this.updatePricing();
                this.validateItems();
            }
        },

        updateItem(itemId, field, value) {
            const item = this.selectedItems.find(item => item.id === itemId);
            if (item) {
                item[field] = value;
                if (['quantity', 'unit_price', 'discount'].includes(field)) {
                    item.subtotal = this.calculateItemSubtotal(item);
                    this.updatePricing();
                }
                this.validateItems();
            }
        },

        calculateItemSubtotal(item) {
            const baseAmount = (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
            const discountAmount = parseFloat(item.discount) || 0;
            return Math.max(0, baseAmount - discountAmount);
        },

        reorderItems() {
            this.selectedItems.forEach((item, index) => {
                item.order = index + 1;
            });
        },

        moveItem(fromIndex, toIndex) {
            const items = [...this.selectedItems];
            const [movedItem] = items.splice(fromIndex, 1);
            items.splice(toIndex, 0, movedItem);
            this.selectedItems = items;
            this.reorderItems();
        },

        // === BULK OPERATIONS ===
        bulkSelectItems(itemIds) {
            itemIds.forEach(id => {
                const item = this.selectedItems.find(item => item.id === id);
                if (item) item.selected = true;
            });
        },

        bulkDeleteSelected() {
            this.selectedItems = this.selectedItems.filter(item => !item.selected);
            this.reorderItems();
            this.updatePricing();
        },

        bulkApplyDiscount(discountPercent) {
            this.selectedItems.forEach(item => {
                if (item.selected && item.unit_price > 0) {
                    const discount = item.unit_price * (discountPercent / 100);
                    item.discount = (item.discount || 0) + discount;
                    item.subtotal = this.calculateItemSubtotal(item);
                }
            });
            this.updatePricing();
        },

        clearBulkSelection() {
            this.selectedItems.forEach(item => {
                delete item.selected;
            });
        },

        // === PRICING CALCULATIONS ===
        updatePricing() {
            // Calculate subtotal
            const subtotal = this.selectedItems.reduce((sum, item) => {
                return sum + (parseFloat(item.subtotal) || 0);
            }, 0);

            // Calculate discount
            let discountAmount = 0;
            if (this.document.discount_type === 'percentage') {
                discountAmount = subtotal * (parseFloat(this.document.discount_amount) || 0) / 100;
            } else {
                discountAmount = parseFloat(this.document.discount_amount) || 0;
            }

            // Calculate tax
            const taxAmount = this.selectedItems.reduce((sum, item) => {
                const itemTotal = parseFloat(item.subtotal) || 0;
                const taxRate = parseFloat(item.tax_rate) || 0;
                return sum + (itemTotal * taxRate / 100);
            }, 0);

            // Calculate total
            const total = subtotal - discountAmount + taxAmount;

            // Calculate recurring revenue
            const recurring = this.calculateRecurringRevenue();

            // Update pricing state
            this.pricing = {
                subtotal,
                discount: discountAmount,
                tax: taxAmount,
                total,
                savings: this.calculateSavings(),
                recurring,
                appliedRules: this.getAppliedPricingRules()
            };

            // Update document amount
            this.document.amount = total;
        },

        calculateRecurringRevenue() {
            let monthly = 0;
            let annual = 0;

            this.selectedItems.forEach(item => {
                const amount = parseFloat(item.subtotal) || 0;
                
                switch (item.billing_cycle) {
                    case 'monthly':
                        monthly += amount;
                        annual += amount * 12;
                        break;
                    case 'quarterly':
                        monthly += amount / 3;
                        annual += amount * 4;
                        break;
                    case 'semi_annually':
                        monthly += amount / 6;
                        annual += amount * 2;
                        break;
                    case 'annually':
                        monthly += amount / 12;
                        annual += amount;
                        break;
                }
            });

            return { monthly, annual };
        },

        calculateSavings() {
            // Calculate volume discounts and promotional savings
            return this.selectedItems.reduce((sum, item) => {
                return sum + (parseFloat(item.savings) || 0);
            }, 0);
        },

        getAppliedPricingRules() {
            // Return applied pricing rules for display
            return [];
        },

        resetPricing() {
            this.pricing = {
                subtotal: 0,
                discount: 0,
                tax: 0,
                total: 0,
                savings: 0,
                recurring: { monthly: 0, annual: 0 },
                appliedRules: []
            };
        },

        // === VALIDATION ===
        validateCurrentStep() {
            const step = this.ui.currentStep;
            this.validation.errors = {};
            let isValid = true;

            if (step >= 1) {
                // Basic details validation
                if (!this.document.client_id) {
                    this.setFieldError('client_id', 'Please select a client');
                    isValid = false;
                }
                if (!this.document.category_id) {
                    this.setFieldError('category_id', 'Please select a category');
                    isValid = false;
                }
            }

            if (step >= 2) {
                // Items validation
                if (this.selectedItems.length === 0) {
                    this.setFieldError('items', 'Please add at least one item');
                    isValid = false;
                }

                this.selectedItems.forEach((item, index) => {
                    if (!item.name || item.name.trim() === '') {
                        this.setFieldError(`item_${index}_name`, 'Item name is required');
                        isValid = false;
                    }
                    if (!item.quantity || item.quantity <= 0) {
                        this.setFieldError(`item_${index}_quantity`, 'Quantity must be greater than 0');
                        isValid = false;
                    }
                    if (item.unit_price < 0) {
                        this.setFieldError(`item_${index}_unit_price`, 'Unit price cannot be negative');
                        isValid = false;
                    }
                });
            }

            this.validation.isValid = isValid;
            return isValid;
        },

        validateField(field) {
            // Individual field validation
            switch (field) {
                case 'client_id':
                    if (!this.document.client_id) {
                        this.setFieldError(field, 'Please select a client');
                    } else {
                        this.clearFieldError(field);
                    }
                    break;
                case 'category_id':
                    if (!this.document.category_id) {
                        this.setFieldError(field, 'Please select a category');
                    } else {
                        this.clearFieldError(field);
                    }
                    break;
                case 'expire_date':
                    if (this.document.expire_date && this.document.expire_date <= this.document.date) {
                        this.setFieldError(field, 'Expiration date must be after quote date');
                    } else {
                        this.clearFieldError(field);
                    }
                    break;
                case 'discount_amount':
                    if (this.document.discount_type === 'percentage' && this.document.discount_amount > 100) {
                        this.setFieldError(field, 'Discount percentage cannot exceed 100%');
                    } else if (this.document.discount_amount < 0) {
                        this.setFieldError(field, 'Discount amount cannot be negative');
                    } else {
                        this.clearFieldError(field);
                    }
                    break;
            }
        },

        validateItems() {
            this.selectedItems.forEach((item, index) => {
                if (!item.name || item.name.trim() === '') {
                    this.setFieldError(`item_${index}_name`, 'Item name is required');
                } else {
                    this.clearFieldError(`item_${index}_name`);
                }

                if (!item.quantity || item.quantity <= 0) {
                    this.setFieldError(`item_${index}_quantity`, 'Quantity must be greater than 0');
                } else {
                    this.clearFieldError(`item_${index}_quantity`);
                }

                if (item.unit_price < 0) {
                    this.setFieldError(`item_${index}_unit_price`, 'Unit price cannot be negative');
                } else {
                    this.clearFieldError(`item_${index}_unit_price`);
                }
            });
        },

        setFieldError(field, message) {
            this.validation.errors[field] = message;
            this.ui.errors[field] = message;
        },

        clearFieldError(field) {
            delete this.validation.errors[field];
            delete this.ui.errors[field];
        },

        resetValidation() {
            this.validation = {
                rules: {},
                errors: {},
                isValid: true
            };
            this.ui.errors = {};
        },

        // === NAVIGATION ===
        nextStep() {
            if (this.validateCurrentStep() && this.ui.currentStep < this.ui.totalSteps) {
                this.ui.currentStep++;
            }
        },

        prevStep() {
            if (this.ui.currentStep > 1) {
                this.ui.currentStep--;
            }
        },

        goToStep(step) {
            if (step <= this.ui.currentStep || this.validateCurrentStep()) {
                this.ui.currentStep = step;
            }
        },

        // === AUTO-SAVE ===
        enableAutoSave() {
            if (!this.autoSave.enabled) {
                this.autoSave.enabled = true;
                this.startAutoSaveInterval();
            }
        },

        disableAutoSave() {
            this.autoSave.enabled = false;
            if (this.autoSave.intervalId) {
                clearInterval(this.autoSave.intervalId);
                this.autoSave.intervalId = null;
            }
        },

        startAutoSaveInterval() {
            if (this.autoSave.intervalId) {
                clearInterval(this.autoSave.intervalId);
            }
            
            this.autoSave.intervalId = setInterval(() => {
                if (this.hasUnsavedChanges && !this.ui.saving) {
                    this.performAutoSave();
                }
            }, this.autoSave.interval);
        },

        scheduleAutoSave() {
            // Debounced auto-save trigger
            clearTimeout(this._autoSaveTimeout);
            this._autoSaveTimeout = setTimeout(() => {
                if (this.autoSave.enabled && this.hasUnsavedChanges && !this.ui.saving) {
                    this.performAutoSave();
                }
            }, 2000); // Wait 2 seconds after last change
        },

        async performAutoSave() {
            if (this.ui.saving) return;
            
            try {
                this.ui.saving = true;
                
                const response = await fetch('/api/quotes/auto-save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        document: this.document,
                        items: this.selectedItems,
                        pricing: this.pricing,
                        billing_config: this.billingConfig,
                        draft: true
                    })
                });

                if (response.ok) {
                    this.autoSave.lastSaved = new Date();
                    console.log('Quote auto-saved successfully');
                }
            } catch (error) {
                console.error('Auto-save failed:', error);
            } finally {
                this.ui.saving = false;
            }
        },

        // === TEMPLATES ===
        loadTemplate(template) {
            if (!template) return;

            // Load basic information
            this.updateDocument('scope', template.scope || '');
            this.updateDocument('note', template.note || '');
            this.updateDocument('terms_conditions', template.terms_conditions || '');
            this.updateDocument('discount_type', template.discount_type || 'fixed');
            this.updateDocument('discount_amount', template.discount_amount || 0);

            // Load items
            if (template.items && template.items.length > 0) {
                this.selectedItems = template.items.map(item => ({
                    ...item,
                    id: `template_${Date.now()}_${Math.random()}`,
                    subtotal: this.calculateItemSubtotal(item)
                }));
            }

            // Update pricing
            this.updatePricing();
            
            // Add to recent templates
            this.addToRecentTemplates(template);
        },

        addToRecentTemplates(template) {
            const recent = this.templates.recent;
            const existingIndex = recent.findIndex(t => t.id === template.id);
            
            if (existingIndex > -1) {
                recent.splice(existingIndex, 1);
            }
            
            recent.unshift(template);
            
            // Keep only last 5
            if (recent.length > 5) {
                recent.pop();
            }
        },

        async loadClientSuggestions(clientId) {
            try {
                const response = await fetch(`/api/clients/${clientId}/quote-suggestions`);
                if (response.ok) {
                    const data = await response.json();
                    this.templates.suggested = data.suggestions || [];
                }
            } catch (error) {
                console.error('Failed to load client suggestions:', error);
            }
        },

        // === SAVE & SUBMIT ===
        async saveQuote(options = {}) {
            if (!this.validateCurrentStep()) {
                return { success: false, message: 'Please fix validation errors' };
            }

            try {
                this.ui.saving = true;
                this.ui.errors = {};

                const payload = {
                    ...this.document,
                    items: this.selectedItems,
                    pricing: this.pricing,
                    billing_config: this.billingConfig,
                    ...options
                };

                const response = await fetch('/api/quotes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (response.ok) {
                    this.autoSave.lastSaved = new Date();
                    return { success: true, data: data.data, message: data.message };
                } else {
                    if (data.errors) {
                        Object.assign(this.ui.errors, data.errors);
                    }
                    return { success: false, message: data.message || 'Failed to save quote' };
                }
            } catch (error) {
                console.error('Save failed:', error);
                return { success: false, message: 'Network error occurred' };
            } finally {
                this.ui.saving = false;
            }
        },

        // === UTILITY METHODS ===
        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: this.document.currency_code || 'USD'
            }).format(amount || 0);
        },

        triggerReactiveUpdates() {
            // Force Alpine.js reactivity updates
            this.$nextTick && this.$nextTick(() => {
                // Trigger any dependent computations
            });
        },

        // === INITIALIZATION ===
        init() {
            // Set up mobile detection
            this.ui.mobileView = this.isMobile;
            
            // Listen for window resize
            window.addEventListener('resize', () => {
                this.ui.mobileView = this.isMobile;
            });

            // Start auto-save if enabled
            if (this.autoSave.enabled) {
                this.startAutoSaveInterval();
            }

            // Set up reactive pricing updates
            this.$watch && this.$watch('selectedItems', () => {
                this.updatePricing();
            }, { deep: true });

            this.$watch && this.$watch('document.discount_amount', () => {
                this.updatePricing();
                this.validateField('discount_amount');
            });

            this.$watch && this.$watch('document.discount_type', () => {
                this.updatePricing();
            });
        },

        // === CLEANUP ===
        destroy() {
            this.disableAutoSave();
            clearTimeout(this._autoSaveTimeout);
        }
    });
});