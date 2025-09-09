/**
 * Quick Quote Component
 * Provides a streamlined interface for creating simple quotes quickly
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('quickQuote', (config = {}) => ({
        // Configuration
        maxItems: config.maxItems || 10,
        enableAutocomplete: config.enableAutocomplete !== false,
        autoCalculate: config.autoCalculate !== false,
        
        // Quick Quote State
        show: false,
        mode: 'simple', // 'simple', 'template', 'duplicate'
        step: 1,
        maxSteps: 3,
        
        // Form Data
        quickForm: {
            clientId: '',
            clientName: '',
            clientEmail: '',
            category: '',
            title: '',
            description: '',
            items: [
                { name: '', price: 0, quantity: 1, total: 0 }
            ],
            discount: 0,
            discountType: 'fixed', // 'fixed', 'percentage'
            tax: 0,
            notes: '',
            validUntil: '',
            sendEmail: false
        },
        
        // Calculations
        totals: {
            subtotal: 0,
            discountAmount: 0,
            taxAmount: 0,
            total: 0
        },
        
        // UI State
        loading: false,
        saving: false,
        errors: {},
        success: false,
        
        // Quick suggestions
        suggestions: {
            clients: [],
            items: [],
            templates: []
        },
        
        // Recent data for quick access
        recentClients: [],
        recentItems: [],
        
        // Validation rules
        validationRules: {
            clientName: { required: true, min: 2 },
            clientEmail: { required: true, email: true },
            title: { required: true, min: 3 },
            'items.*.name': { required: true, min: 2 },
            'items.*.price': { required: true, min: 0 },
            'items.*.quantity': { required: true, min: 1 }
        },

        // Initialize Quick Quote
        init() {
            this.loadRecentData();
            this.setupEventListeners();
            this.setupAutocomplete();
            this.setDefaultValidUntil();
        },

        // Setup event listeners
        setupEventListeners() {
            // Listen for quick quote triggers
            document.addEventListener('open-quick-quote', (e) => {
                this.openQuickQuote(e.detail?.mode || 'simple');
            });

            // Watch for form changes to auto-calculate
            this.$watch('quickForm.items', () => {
                if (this.autoCalculate) {
                    this.calculateTotals();
                }
            }, { deep: true });

            this.$watch('quickForm.discount', () => {
                if (this.autoCalculate) {
                    this.calculateTotals();
                }
            });

            this.$watch('quickForm.discountType', () => {
                if (this.autoCalculate) {
                    this.calculateTotals();
                }
            });

            this.$watch('quickForm.tax', () => {
                if (this.autoCalculate) {
                    this.calculateTotals();
                }
            });

            // Auto-save to localStorage
            this.$watch('quickForm', () => {
                this.autoSaveForm();
            }, { deep: true });
        },

        // Open Quick Quote modal
        openQuickQuote(mode = 'simple') {
            this.mode = mode;
            this.resetForm();
            
            if (mode === 'template') {
                this.loadQuickTemplates();
            } else if (mode === 'duplicate') {
                this.loadRecentQuotes();
            }
            
            this.show = true;
            this.step = 1;
            
            // Focus first input
            this.$nextTick(() => {
                const firstInput = document.querySelector('.quick-quote-modal input:not([disabled])');
                if (firstInput) firstInput.focus();
            });
        },

        // Close Quick Quote modal
        closeQuickQuote() {
            this.show = false;
            this.clearAutoSave();
            
            // Reset after animation
            setTimeout(() => {
                this.resetForm();
                this.errors = {};
                this.success = false;
            }, 300);
        },

        // Navigate steps
        nextStep() {
            if (this.validateCurrentStep()) {
                if (this.step < this.maxSteps) {
                    this.step++;
                } else {
                    this.submitQuickQuote();
                }
            }
        },

        previousStep() {
            if (this.step > 1) {
                this.step--;
            }
        },

        goToStep(targetStep) {
            if (targetStep >= 1 && targetStep <= this.maxSteps) {
                // Validate previous steps
                let canProceed = true;
                for (let i = 1; i < targetStep; i++) {
                    if (!this.validateStep(i)) {
                        canProceed = false;
                        break;
                    }
                }
                
                if (canProceed) {
                    this.step = targetStep;
                }
            }
        },

        // Form management
        resetForm() {
            this.quickForm = {
                clientId: '',
                clientName: '',
                clientEmail: '',
                category: '',
                title: '',
                description: '',
                items: [
                    { name: '', price: 0, quantity: 1, total: 0 }
                ],
                discount: 0,
                discountType: 'fixed',
                tax: 0,
                notes: '',
                validUntil: '',
                sendEmail: false
            };
            
            this.setDefaultValidUntil();
            this.calculateTotals();
        },

        setDefaultValidUntil() {
            const date = new Date();
            date.setDate(date.getDate() + 30); // 30 days from now
            this.quickForm.validUntil = date.toISOString().split('T')[0];
        },

        // Item management
        addItem() {
            if (this.quickForm.items.length < this.maxItems) {
                this.quickForm.items.push({
                    name: '',
                    price: 0,
                    quantity: 1,
                    total: 0
                });
            }
        },

        removeItem(index) {
            if (this.quickForm.items.length > 1) {
                this.quickForm.items.splice(index, 1);
                this.calculateTotals();
            }
        },

        duplicateItem(index) {
            if (this.quickForm.items.length < this.maxItems) {
                const item = { ...this.quickForm.items[index] };
                this.quickForm.items.splice(index + 1, 0, item);
            }
        },

        // Calculations
        calculateTotals() {
            // Calculate subtotal
            this.totals.subtotal = this.quickForm.items.reduce((sum, item) => {
                const itemTotal = (item.price || 0) * (item.quantity || 1);
                item.total = itemTotal;
                return sum + itemTotal;
            }, 0);

            // Calculate discount
            if (this.quickForm.discountType === 'percentage') {
                this.totals.discountAmount = this.totals.subtotal * (this.quickForm.discount / 100);
            } else {
                this.totals.discountAmount = Math.min(this.quickForm.discount || 0, this.totals.subtotal);
            }

            // Calculate tax
            const afterDiscount = this.totals.subtotal - this.totals.discountAmount;
            this.totals.taxAmount = afterDiscount * (this.quickForm.tax / 100);

            // Calculate total
            this.totals.total = afterDiscount + this.totals.taxAmount;
        },

        // Validation
        validateCurrentStep() {
            this.errors = {};
            return this.validateStep(this.step);
        },

        validateStep(stepNumber) {
            const errors = {};
            
            switch (stepNumber) {
                case 1: // Client & Basic Info
                    if (!this.quickForm.clientName.trim()) {
                        errors.clientName = 'Client name is required';
                    }
                    if (!this.quickForm.clientEmail.trim()) {
                        errors.clientEmail = 'Client email is required';
                    } else if (!this.isValidEmail(this.quickForm.clientEmail)) {
                        errors.clientEmail = 'Please enter a valid email address';
                    }
                    if (!this.quickForm.title.trim()) {
                        errors.title = 'Quote title is required';
                    }
                    break;

                case 2: // Items
                    this.quickForm.items.forEach((item, index) => {
                        if (!item.name.trim()) {
                            errors[`items.${index}.name`] = 'Item name is required';
                        }
                        if (!item.price || item.price < 0) {
                            errors[`items.${index}.price`] = 'Valid price is required';
                        }
                        if (!item.quantity || item.quantity < 1) {
                            errors[`items.${index}.quantity`] = 'Quantity must be at least 1';
                        }
                    });
                    break;

                case 3: // Review
                    // Final validation
                    if (this.totals.total <= 0) {
                        errors.total = 'Quote total must be greater than zero';
                    }
                    break;
            }

            this.errors = errors;
            return Object.keys(errors).length === 0;
        },

        isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },

        // Load recent data
        async loadRecentData() {
            try {
                const [clients, items] = await Promise.all([
                    this.fetchRecentClients(),
                    this.fetchRecentItems()
                ]);

                this.recentClients = clients;
                this.recentItems = items;
            } catch (error) {
                console.error('Failed to load recent data:', error);
            }
        },

        async fetchRecentClients() {
            try {
                const response = await fetch('/api/clients/recent?limit=10');
                if (response.ok) {
                    return await response.json();
                }
            } catch (error) {
                console.error('Failed to fetch recent clients:', error);
            }
            return [];
        },

        async fetchRecentItems() {
            try {
                const response = await fetch('/api/products/recent?limit=20');
                if (response.ok) {
                    return await response.json();
                }
            } catch (error) {
                console.error('Failed to fetch recent items:', error);
            }
            return [];
        },

        // Setup autocomplete
        setupAutocomplete() {
            if (!this.enableAutocomplete) return;

            // Client autocomplete
            this.$watch('quickForm.clientName', (value) => {
                if (value.length >= 2) {
                    this.searchClients(value);
                }
            });

            // Item name autocomplete
            this.quickForm.items.forEach((item, index) => {
                this.$watch(`quickForm.items.${index}.name`, (value) => {
                    if (value.length >= 2) {
                        this.searchItems(value, index);
                    }
                });
            });
        },

        async searchClients(query) {
            try {
                const response = await fetch(`/api/clients/search?q=${encodeURIComponent(query)}&limit=5`);
                if (response.ok) {
                    this.suggestions.clients = await response.json();
                }
            } catch (error) {
                console.error('Failed to search clients:', error);
            }
        },

        async searchItems(query, itemIndex) {
            try {
                const response = await fetch(`/api/products/search?q=${encodeURIComponent(query)}&limit=5`);
                if (response.ok) {
                    const items = await response.json();
                    this.suggestions.items = items.map(item => ({ ...item, itemIndex }));
                }
            } catch (error) {
                console.error('Failed to search items:', error);
            }
        },

        // Select suggestions
        selectClient(client) {
            this.quickForm.clientId = client.id;
            this.quickForm.clientName = client.name;
            this.quickForm.clientEmail = client.email;
            this.suggestions.clients = [];
        },

        selectItem(item) {
            const index = item.itemIndex;
            this.quickForm.items[index].name = item.name;
            this.quickForm.items[index].price = item.unit_price || item.price || 0;
            this.suggestions.items = [];
            this.calculateTotals();
        },

        // Template loading
        async loadQuickTemplates() {
            try {
                const response = await fetch('/api/quote-templates/quick?limit=5');
                if (response.ok) {
                    this.suggestions.templates = await response.json();
                }
            } catch (error) {
                console.error('Failed to load quick templates:', error);
            }
        },

        applyTemplate(template) {
            this.quickForm.title = template.name;
            this.quickForm.description = template.description;
            this.quickForm.items = template.items.map(item => ({
                name: item.name,
                price: item.unit_price,
                quantity: item.quantity,
                total: item.unit_price * item.quantity
            }));
            this.quickForm.discount = template.discount_amount || 0;
            this.quickForm.discountType = template.discount_type || 'fixed';
            
            this.calculateTotals();
            this.step = 2; // Move to items step
        },

        // Recent quotes for duplication
        async loadRecentQuotes() {
            try {
                const response = await fetch('/api/quotes/recent?limit=5');
                if (response.ok) {
                    this.suggestions.templates = await response.json();
                }
            } catch (error) {
                console.error('Failed to load recent quotes:', error);
            }
        },

        // Submit quote
        async submitQuickQuote() {
            if (!this.validateCurrentStep()) {
                return;
            }

            try {
                this.saving = true;

                const quoteData = {
                    client_name: this.quickForm.clientName,
                    client_email: this.quickForm.clientEmail,
                    client_id: this.quickForm.clientId || null,
                    title: this.quickForm.title,
                    description: this.quickForm.description,
                    items: this.quickForm.items.map(item => ({
                        name: item.name,
                        unit_price: item.price,
                        quantity: item.quantity
                    })),
                    discount_amount: this.quickForm.discount,
                    discount_type: this.quickForm.discountType,
                    tax_rate: this.quickForm.tax,
                    notes: this.quickForm.notes,
                    expire_date: this.quickForm.validUntil,
                    send_email: this.quickForm.sendEmail,
                    quick_quote: true
                };

                const response = await fetch('/api/quotes/quick', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(quoteData)
                });

                if (response.ok) {
                    const result = await response.json();
                    this.success = true;
                    this.clearAutoSave();

                    // Show success and redirect option
                    setTimeout(() => {
                        if (confirm('Quote created successfully! Would you like to view it?')) {
                            window.location.href = `/quotes/${result.data.id}`;
                        } else {
                            this.closeQuickQuote();
                        }
                    }, 1000);

                } else {
                    const error = await response.json();
                    throw new Error(error.message || 'Failed to create quote');
                }

            } catch (error) {
                console.error('Failed to submit quick quote:', error);
                
                this.$dispatch('notification', {
                    type: 'error',
                    message: error.message || 'Failed to create quote. Please try again.'
                });

            } finally {
                this.saving = false;
            }
        },

        // Auto-save functionality
        autoSaveForm() {
            if (this.show) {
                try {
                    localStorage.setItem('quick_quote_draft', JSON.stringify({
                        form: this.quickForm,
                        timestamp: Date.now()
                    }));
                } catch (error) {
                    console.warn('Failed to auto-save quick quote:', error);
                }
            }
        },

        loadAutoSavedForm() {
            try {
                const saved = localStorage.getItem('quick_quote_draft');
                if (saved) {
                    const data = JSON.parse(saved);
                    
                    // Only load if recent (within last hour)
                    if (Date.now() - data.timestamp < 3600000) {
                        if (confirm('Found a previously unsaved quick quote. Would you like to restore it?')) {
                            this.quickForm = data.form;
                            this.calculateTotals();
                        }
                    }
                }
            } catch (error) {
                console.warn('Failed to load auto-saved quick quote:', error);
            }
        },

        clearAutoSave() {
            try {
                localStorage.removeItem('quick_quote_draft');
            } catch (error) {
                console.warn('Failed to clear auto-saved quick quote:', error);
            }
        },

        // Utility methods
        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount || 0);
        },

        // Computed properties
        get canAddItem() {
            return this.quickForm.items.length < this.maxItems;
        },

        get canRemoveItem() {
            return this.quickForm.items.length > 1;
        },

        get progressPercentage() {
            return (this.step / this.maxSteps) * 100;
        },

        get currentStepTitle() {
            const titles = {
                1: 'Client & Details',
                2: 'Add Items',
                3: 'Review & Submit'
            };
            return titles[this.step] || '';
        },

        get isLastStep() {
            return this.step === this.maxSteps;
        },

        get formattedSubtotal() {
            return this.formatCurrency(this.totals.subtotal);
        },

        get formattedDiscount() {
            return this.formatCurrency(this.totals.discountAmount);
        },

        get formattedTax() {
            return this.formatCurrency(this.totals.taxAmount);
        },

        get formattedTotal() {
            return this.formatCurrency(this.totals.total);
        },

        get hasItems() {
            return this.quickForm.items.some(item => item.name.trim());
        }
    }));

    // Quick Quote trigger directive
    Alpine.directive('quick-quote', (el, { expression }, { evaluate }) => {
        el.addEventListener('click', () => {
            const mode = evaluate(expression) || 'simple';
            document.dispatchEvent(new CustomEvent('open-quick-quote', {
                detail: { mode }
            }));
        });
    });
});