/**
 * Quote Form Component
 * Handles basic quote form operations and validation
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('quoteForm', (config = {}) => ({
        // Configuration
        mode: config.mode || 'create', // 'create' or 'edit'
        quoteId: config.quoteId || null,
        
        // Form state
        loading: false,
        saving: false,
        errors: {},
        showValidation: false,
        
        // Available data
        clients: config.clients || [],
        categories: config.categories || [],
        templates: config.templates || [],
        
        // Auto-save state
        autoSaveEnabled: true,
        autoSaveInterval: null,
        lastAutoSave: null,
        autoSaveDelay: 30000, // 30 seconds
        
        // Validation rules
        validationRules: {
            client_id: {
                required: true,
                message: 'Please select a client'
            },
            category_id: {
                required: true,
                message: 'Please select a category'
            },
            date: {
                required: true,
                message: 'Quote date is required'
            },
            expire_date: {
                custom: (value, data) => {
                    if (value && data.date && new Date(value) <= new Date(data.date)) {
                        return 'Expiration date must be after quote date';
                    }
                    return null;
                }
            },
            discount_amount: {
                custom: (value, data) => {
                    if (value < 0) return 'Discount amount cannot be negative';
                    if (data.discount_type === 'percentage' && value > 100) {
                        return 'Discount percentage cannot exceed 100%';
                    }
                    return null;
                }
            }
        },

        // Initialize component
        init() {
            this.setupFormWatchers();
            this.setupAutoSave();
            this.setupKeyboardShortcuts();
            this.loadExistingQuote();
        },

        // Setup reactive form watchers
        setupFormWatchers() {
            // Watch for changes in the quote store
            this.$watch('$store.quote.document', () => {
                this.validateForm();
                this.scheduleAutoSave();
            }, { deep: true });

            // Watch for client changes
            this.$watch('$store.quote.document.client_id', (clientId) => {
                if (clientId) {
                    this.handleClientChange(clientId);
                }
            });

            // Watch for discount changes
            this.$watch('$store.quote.document.discount_amount', () => {
                this.$store.quote.updatePricing();
            });

            this.$watch('$store.quote.document.discount_type', () => {
                this.$store.quote.updatePricing();
            });
        },

        // Handle client selection changes
        handleClientChange(clientId) {
            const client = this.clients.find(c => c.id == clientId);
            if (client) {
                // Update currency if client has a default
                if (client.currency_code && client.currency_code !== this.$store.quote.document.currency_code) {
                    this.$store.quote.updateDocument('currency_code', client.currency_code);
                }

                // Set default expiration date (30 days from now)
                if (!this.$store.quote.document.expire_date) {
                    const expireDate = new Date();
                    expireDate.setDate(expireDate.getDate() + 30);
                    this.$store.quote.updateDocument('expire_date', expireDate.toISOString().split('T')[0]);
                }

                // Load client-specific template suggestions
                this.loadClientSuggestions(clientId);

                // Clear client validation error
                this.clearFieldError('client_id');
            }
        },

        // Load client-specific template suggestions
        async loadClientSuggestions(clientId) {
            try {
                const response = await fetch(`/api/clients/${clientId}/quote-suggestions`);
                if (response.ok) {
                    const data = await response.json();
                    this.$store.quote.templates.suggested = data.suggestions || [];
                }
            } catch (error) {
                console.error('Failed to load client suggestions:', error);
            }
        },

        // Form validation
        validateForm() {
            if (!this.showValidation) return true;

            const document = this.$store.quote.document;
            const newErrors = {};
            let isValid = true;

            // Validate each field
            Object.keys(this.validationRules).forEach(field => {
                const rule = this.validationRules[field];
                const value = document[field];

                // Required field validation
                if (rule.required && (!value || value === '')) {
                    newErrors[field] = rule.message;
                    isValid = false;
                    return;
                }

                // Custom validation
                if (rule.custom && value) {
                    const customError = rule.custom(value, document);
                    if (customError) {
                        newErrors[field] = customError;
                        isValid = false;
                    }
                }
            });

            this.errors = newErrors;
            this.$store.quote.ui.errors = { ...this.$store.quote.ui.errors, ...newErrors };
            
            return isValid;
        },

        // Validate specific field
        validateField(fieldName) {
            const rule = this.validationRules[fieldName];
            if (!rule) return true;

            const document = this.$store.quote.document;
            const value = document[fieldName];

            // Required validation
            if (rule.required && (!value || value === '')) {
                this.setFieldError(fieldName, rule.message);
                return false;
            }

            // Custom validation
            if (rule.custom) {
                const customError = rule.custom(value, document);
                if (customError) {
                    this.setFieldError(fieldName, customError);
                    return false;
                }
            }

            // Field is valid
            this.clearFieldError(fieldName);
            return true;
        },

        // Error management
        setFieldError(field, message) {
            this.errors[field] = message;
            this.$store.quote.setFieldError(field, message);
        },

        clearFieldError(field) {
            delete this.errors[field];
            this.$store.quote.clearFieldError(field);
        },

        clearAllErrors() {
            this.errors = {};
            this.$store.quote.resetValidation();
        },

        // Template operations
        async loadTemplate(template) {
            try {
                this.loading = true;

                // Load template data into the store
                this.$store.quote.loadTemplate(template);

                // Show success notification
                this.$dispatch('notification', {
                    type: 'success',
                    message: `Template "${template.name}" loaded successfully`
                });

            } catch (error) {
                console.error('Failed to load template:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: 'Failed to load template'
                });
            } finally {
                this.loading = false;
            }
        },

        // Get suggested templates for current client/category
        getSuggestedTemplates() {
            if (!this.$store.quote.document.client_id || !this.$store.quote.document.category_id) {
                return [];
            }

            return this.templates.filter(template => {
                return template.category_id === this.$store.quote.document.category_id ||
                       template.recent_clients?.includes(this.$store.quote.document.client_id);
            }).slice(0, 3);
        },

        // Auto-save functionality
        setupAutoSave() {
            if (!this.autoSaveEnabled) return;

            this.autoSaveInterval = setInterval(() => {
                if (this.shouldAutoSave()) {
                    this.performAutoSave();
                }
            }, this.autoSaveDelay);
        },

        shouldAutoSave() {
            if (this.saving || !this.autoSaveEnabled) return false;
            if (!this.$store.quote.document.client_id) return false; // Don't save without client
            if (!this.lastAutoSave) return true;

            // Only auto-save if there have been changes and it's been at least 2 minutes
            const timeSinceLastSave = Date.now() - this.lastAutoSave.getTime();
            return timeSinceLastSave > 120000; // 2 minutes
        },

        async performAutoSave() {
            try {
                const result = await this.saveQuote({ 
                    status: 'draft', 
                    auto_save: true 
                });

                if (result.success) {
                    this.lastAutoSave = new Date();
                    console.log('Quote auto-saved successfully');
                }
            } catch (error) {
                console.error('Auto-save failed:', error);
            }
        },

        scheduleAutoSave() {
            if (!this.autoSaveEnabled) return;

            clearTimeout(this._autoSaveTimeout);
            this._autoSaveTimeout = setTimeout(() => {
                if (this.shouldAutoSave()) {
                    this.performAutoSave();
                }
            }, 5000); // Delay auto-save by 5 seconds after last change
        },

        disableAutoSave() {
            this.autoSaveEnabled = false;
            if (this.autoSaveInterval) {
                clearInterval(this.autoSaveInterval);
                this.autoSaveInterval = null;
            }
            clearTimeout(this._autoSaveTimeout);
        },

        // Save operations
        async saveQuote(options = {}) {
            // Enable validation for save
            this.showValidation = true;

            if (!this.validateForm()) {
                this.$dispatch('notification', {
                    type: 'error',
                    message: 'Please fix validation errors before saving'
                });
                return { success: false, message: 'Validation failed' };
            }

            try {
                this.saving = true;
                this.clearAllErrors();

                const payload = {
                    ...this.$store.quote.document,
                    items: this.$store.quote.selectedItems.map(item => ({
                        id: item.id.toString().startsWith('temp_') ? null : item.id,
                        product_id: item.product_id,
                        service_id: item.service_id,
                        bundle_id: item.bundle_id,
                        name: item.name,
                        description: item.description,
                        quantity: item.quantity,
                        price: item.unit_price, // Backend expects 'price' not 'unit_price'
                        discount: item.discount || 0,
                        tax_rate: item.tax_rate || 0,
                        order: item.order
                    })),
                    ...options
                };

                const url = this.mode === 'edit' ? `/api/quotes/${this.quoteId}` : '/api/quotes';
                const method = this.mode === 'edit' ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (response.ok) {
                    this.lastAutoSave = new Date();
                    
                    // Update quote ID if this was a create operation
                    if (this.mode === 'create' && data.data?.id) {
                        this.quoteId = data.data.id;
                        this.mode = 'edit';
                    }

                    return { 
                        success: true, 
                        data: data.data, 
                        message: data.message 
                    };
                } else {
                    // Handle validation errors
                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            this.setFieldError(field, data.errors[field][0] || data.errors[field]);
                        });
                    }

                    return { 
                        success: false, 
                        message: data.message || 'Failed to save quote',
                        errors: data.errors 
                    };
                }

            } catch (error) {
                console.error('Save failed:', error);
                return { 
                    success: false, 
                    message: 'Network error occurred. Please try again.' 
                };
            } finally {
                this.saving = false;
            }
        },

        async saveAsDraft() {
            const result = await this.saveQuote({ status: 'draft' });
            
            if (result.success) {
                this.$dispatch('notification', {
                    type: 'success',
                    message: 'Quote saved as draft successfully'
                });
            } else {
                this.$dispatch('notification', {
                    type: 'error',
                    message: result.message
                });
            }

            return result;
        },

        async submitQuote() {
            const result = await this.saveQuote({ status: 'pending_approval' });
            
            if (result.success) {
                // Redirect to quote view
                window.location.href = `/financial/quotes/${result.data.id}`;
            } else {
                this.$dispatch('notification', {
                    type: 'error',
                    message: result.message
                });
            }

            return result;
        },

        // Load existing quote (for edit mode)
        async loadExistingQuote() {
            if (this.mode !== 'edit' || !this.quoteId) return;

            try {
                this.loading = true;

                const response = await fetch(`/api/quotes/${this.quoteId}`);
                if (response.ok) {
                    const data = await response.json();
                    const quote = data.data || data.quote;

                    // Load quote data into store
                    Object.keys(quote).forEach(key => {
                        if (key !== 'items' && this.$store.quote.document.hasOwnProperty(key)) {
                            this.$store.quote.document[key] = quote[key];
                        }
                    });

                    // Load items
                    if (quote.items && quote.items.length > 0) {
                        this.$store.quote.selectedItems = quote.items.map(item => ({
                            id: item.id,
                            product_id: item.product_id,
                            service_id: item.service_id,
                            bundle_id: item.bundle_id,
                            name: item.name,
                            description: item.description,
                            quantity: parseFloat(item.quantity),
                            unit_price: parseFloat(item.unit_price || item.price),
                            discount: parseFloat(item.discount || 0),
                            tax_rate: parseFloat(item.tax_rate || 0),
                            subtotal: parseFloat(item.subtotal || 0),
                            order: item.order,
                            type: item.type || 'product'
                        }));
                    }

                    // Update pricing
                    this.$store.quote.updatePricing();

                } else {
                    throw new Error('Failed to load quote');
                }

            } catch (error) {
                console.error('Failed to load quote:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: 'Failed to load quote data'
                });
            } finally {
                this.loading = false;
            }
        },

        // Keyboard shortcuts
        setupKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                // Only handle shortcuts when this component is active
                if (!this.$el.contains(document.activeElement)) return;

                if (e.ctrlKey || e.metaKey) {
                    switch (e.key) {
                        case 's':
                            e.preventDefault();
                            this.saveAsDraft();
                            break;
                        case 'Enter':
                            e.preventDefault();
                            this.submitQuote();
                            break;
                    }
                }

                // Navigation shortcuts
                if (e.altKey) {
                    switch (e.key) {
                        case 'n':
                            e.preventDefault();
                            this.goToStep(1);
                            break;
                        case 'i':
                            e.preventDefault();
                            this.goToStep(2);
                            break;
                        case 'r':
                            e.preventDefault();
                            this.goToStep(3);
                            break;
                    }
                }
            });
        },

        // Navigation helpers
        goToStep(step) {
            this.$store.quote.goToStep(step);
        },

        canProceedToStep(step) {
            // Validate current step before proceeding
            this.showValidation = true;
            return this.validateForm();
        },

        // Utility methods
        formatCurrency(amount) {
            return this.$store.quote.formatCurrency(amount);
        },

        getClientName(clientId) {
            const client = this.clients.find(c => c.id == clientId);
            return client ? client.display_name || client.name : '';
        },

        getCategoryName(categoryId) {
            const category = this.categories.find(c => c.id == categoryId);
            return category ? category.name : '';
        },

        // Cleanup
        destroy() {
            this.disableAutoSave();
            if (this.autoSaveInterval) {
                clearInterval(this.autoSaveInterval);
            }
            clearTimeout(this._autoSaveTimeout);
        }
    }));
});