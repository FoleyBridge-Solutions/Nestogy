/**
 * Simplified Financial Document Builder Component
 * Clean Alpine.js component for creating quotes and invoices
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('financialDocumentBuilder', (config = {}) => ({
        // Configuration
        type: config.type || 'quote', // 'quote' or 'invoice'
        mode: config.mode || 'create', // 'create' or 'edit'
        
        // Core data
        document: {
            client_id: config.selectedClientId || '',
            category_id: config.categoryId || '',
            date: new Date().toISOString().split('T')[0],
            expire_date: '', // quotes only
            due_date: '', // invoices only
            currency_code: 'USD',
            status: 'Draft',
            scope: '',
            note: '',
            terms_conditions: '',
            discount_type: 'fixed',
            discount_amount: 0,
            tax_rate: 0,
            items: []
        },

        // UI State
        loading: false,
        saving: false,
        errors: {},
        currentStep: 1,
        totalSteps: 3,

        // Calculations
        subtotal: 0,
        taxAmount: 0,
        discountAmount: 0,
        total: 0,

        // Available data
        clients: config.clients || [],
        categories: config.categories || [],
        taxes: config.taxes || [],

        // Initialize component
        init() {
            this.initializeDocument();
            this.calculateTotals();
        },

        // Initialize document with defaults
        initializeDocument() {
            // Set default dates
            if (this.type === 'quote') {
                this.document.expire_date = this.addDays(new Date(), 30).toISOString().split('T')[0];
            } else if (this.type === 'invoice') {
                this.document.due_date = this.addDays(new Date(), 30).toISOString().split('T')[0];
            }

            // Add default line item if none exist
            if (this.document.items.length === 0) {
                this.addLineItem();
            }
        },

        // Client selection
        selectClient(clientId) {
            this.document.client_id = clientId;
            const client = this.clients.find(c => c.id == clientId);
            
            if (client) {
                // Set currency from client default
                if (client.currency_code) {
                    this.document.currency_code = client.currency_code;
                }
                
                // Set payment terms for invoices
                if (this.type === 'invoice' && client.net_terms) {
                    this.document.due_date = this.addDays(new Date(this.document.date), client.net_terms).toISOString().split('T')[0];
                }

                this.clearErrors(['client_id']);
                this.calculateTotals();
            }
        },

        // Line item management
        addLineItem() {
            const newItem = {
                id: Date.now(), // Temporary ID
                description: '',
                quantity: 1,
                rate: 0,
                amount: 0
            };
            
            this.document.items.push(newItem);
            this.calculateTotals();
        },

        removeLineItem(index) {
            if (this.document.items.length > 1) {
                this.document.items.splice(index, 1);
                this.calculateTotals();
            }
        },

        duplicateLineItem(index) {
            const item = this.document.items[index];
            const duplicatedItem = {
                ...item,
                id: Date.now() + Math.random(),
                description: `${item.description} (Copy)`
            };
            
            this.document.items.splice(index + 1, 0, duplicatedItem);
            this.calculateTotals();
        },

        // Real-time calculations
        calculateLineItem(index) {
            const item = this.document.items[index];
            if (item) {
                item.amount = (parseFloat(item.quantity) || 0) * (parseFloat(item.rate) || 0);
                this.calculateTotals();
            }
        },

        calculateTotals() {
            // Calculate subtotal
            this.subtotal = this.document.items.reduce((sum, item) => {
                return sum + (parseFloat(item.amount) || 0);
            }, 0);

            // Calculate discount
            if (this.document.discount_type === 'percentage') {
                this.discountAmount = this.subtotal * (parseFloat(this.document.discount_amount) || 0) / 100;
            } else {
                this.discountAmount = parseFloat(this.document.discount_amount) || 0;
            }

            // Calculate tax
            const taxRate = parseFloat(this.document.tax_rate) || 0;
            const taxableAmount = this.subtotal - this.discountAmount;
            this.taxAmount = taxableAmount * taxRate / 100;

            // Calculate total
            this.total = this.subtotal - this.discountAmount + this.taxAmount;

            // Update document amount
            this.document.amount = this.total;
        },

        // Form validation
        validateStep(step) {
            this.errors = {};
            let isValid = true;

            if (step >= 1) {
                if (!this.document.client_id) {
                    this.errors.client_id = 'Please select a client';
                    isValid = false;
                }
                if (!this.document.category_id) {
                    this.errors.category_id = 'Please select a category';
                    isValid = false;
                }
            }

            if (step >= 2) {
                if (this.document.items.length === 0) {
                    this.errors.items = 'Please add at least one line item';
                    isValid = false;
                }

                this.document.items.forEach((item, index) => {
                    if (!item.description) {
                        this.errors[`item_${index}_description`] = 'Description is required';
                        isValid = false;
                    }
                });
            }

            return isValid;
        },

        // Navigation
        nextStep() {
            if (this.validateStep(this.currentStep) && this.currentStep < this.totalSteps) {
                this.currentStep++;
            }
        },

        prevStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
            }
        },

        goToStep(step) {
            if (step <= this.currentStep || this.validateStep(this.currentStep)) {
                this.currentStep = step;
            }
        },

        // Save document
        async save() {
            if (this.saving) return;

            if (!this.validateStep(this.totalSteps)) {
                this.currentStep = 1; // Go to first invalid step
                return;
            }

            this.saving = true;
            this.errors = {};

            try {
                const url = this.mode === 'edit' 
                    ? `/financial/${this.type}s/${this.document.id}` 
                    : `/financial/${this.type}s`;
                    
                const method = this.mode === 'edit' ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.document)
                });

                const data = await response.json();

                if (data.success || response.ok) {
                    // Success notification
                    window.showAlert('success', 'Success', `${this.type.charAt(0).toUpperCase() + this.type.slice(1)} saved successfully`);

                    // Redirect
                    setTimeout(() => {
                        window.location.href = data.redirect_url || `/financial/${this.type}s`;
                    }, 1500);
                } else {
                    this.errors = data.errors || {};
                    window.showAlert('error', 'Error', data.message || 'Failed to save document');
                }
            } catch (error) {
                console.error('Save failed:', error);
                window.showAlert('error', 'Error', 'Network error occurred. Please try again.');
            } finally {
                this.saving = false;
            }
        },

        // Save as draft
        async saveAsDraft() {
            this.document.status = 'Draft';
            await this.save();
        },

        // Utility methods
        addDays(date, days) {
            const result = new Date(date);
            result.setDate(result.getDate() + days);
            return result;
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: this.document.currency_code || 'USD'
            }).format(amount || 0);
        },

        clearErrors(fields = null) {
            if (fields) {
                fields.forEach(field => delete this.errors[field]);
            } else {
                this.errors = {};
            }
        },

        getClientName() {
            const client = this.clients.find(c => c.id == this.document.client_id);
            return client ? client.display_name || client.name : '';
        }
    }));
});