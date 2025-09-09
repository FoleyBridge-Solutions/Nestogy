/**
 * Advanced Financial Document Builder Component
 * Feature-rich Alpine.js component for creating quotes and invoices
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('financialDocumentBuilderAdvanced', (config = {}) => ({
        // Configuration
        type: config.type || 'quote', // 'quote' or 'invoice'
        mode: config.mode || 'create', // 'create' or 'edit'
        
        // Core document data
        document: {
            id: config.documentId || null,
            client_id: config.selectedClientId || '',
            category_id: config.categoryId || '',
            template_id: null,
            date: new Date().toISOString().split('T')[0],
            expire_date: '', // quotes only
            due_date: '', // invoices only
            currency_code: 'USD',
            exchange_rate: 1,
            status: 'Draft',
            approval_status: 'not_required',
            scope: '',
            note: '',
            internal_note: '',
            terms_conditions: '',
            discount_type: 'fixed',
            discount_amount: 0,
            tax_rate: 0,
            tax_type: 'exclusive', // exclusive or inclusive
            payment_terms: 'net30',
            items: [],
            attachments: [],
            tags: [],
            custom_fields: {},
            recurring: {
                enabled: false,
                frequency: 'monthly',
                interval: 1,
                start_date: null,
                end_date: null,
                next_date: null,
                occurrences: null
            }
        },

        // UI State
        loading: false,
        saving: false,
        errors: {},
        currentStep: 1,
        totalSteps: 4,
        activeTab: 'details',
        showPreview: false,
        showTemplateModal: false,
        showEmailModal: false,
        showPaymentModal: false,
        showHistoryModal: false,
        
        // Advanced Features
        features: {
            templates: true,
            recurring: config.type === 'invoice',
            approval: true,
            versioning: true,
            attachments: true,
            customFields: true,
            multiCurrency: true,
            automation: true,
            analytics: true
        },

        // Calculations
        subtotal: 0,
        taxAmount: 0,
        discountAmount: 0,
        total: 0,
        totalInBaseCurrency: 0,
        profitMargin: 0,

        // Available data
        clients: config.clients || [],
        categories: config.categories || [],
        taxes: config.taxes || [],
        templates: config.templates || [],
        currencies: config.currencies || [
            {code: 'USD', symbol: '$', name: 'US Dollar', rate: 1},
            {code: 'EUR', symbol: '€', name: 'Euro', rate: 0.85},
            {code: 'GBP', symbol: '£', name: 'British Pound', rate: 0.73},
            {code: 'CAD', symbol: 'C$', name: 'Canadian Dollar', rate: 1.25}
        ],
        paymentMethods: config.paymentMethods || [],
        
        // Template Management
        templateData: {
            search: '',
            category: '',
            favorites: [],
            recent: [],
            creating: false,
            newTemplateName: ''
        },
        
        // Email Settings
        emailSettings: {
            to: [],
            cc: [],
            bcc: [],
            subject: '',
            message: '',
            attachPdf: true,
            sendCopy: true,
            scheduleDate: null,
            reminderDays: [7, 3, 1]
        },
        
        // Payment Tracking
        payments: [],
        paymentData: {
            amount: 0,
            date: new Date().toISOString().split('T')[0],
            method: 'bank_transfer',
            reference: '',
            notes: ''
        },
        
        // History & Versioning
        history: [],
        versions: [],
        currentVersion: 1,
        
        // Analytics
        analytics: {
            conversionRate: 0,
            averagePaymentTime: 0,
            totalRevenue: 0,
            outstandingAmount: 0
        },

        // Initialize component
        init() {
            this.initializeDocument();
            this.loadTemplates();
            this.loadCurrencyRates();
            this.setupKeyboardShortcuts();
            this.setupAutoSave();
            this.calculateTotals();
            
            if (this.mode === 'edit') {
                this.loadDocument();
            }
            
            // Watch for changes
            this.$watch('document.items', () => this.calculateTotals(), { deep: true });
            this.$watch('document.discount_amount', () => this.calculateTotals());
            this.$watch('document.discount_type', () => this.calculateTotals());
            this.$watch('document.tax_rate', () => this.calculateTotals());
            this.$watch('document.currency_code', () => this.updateExchangeRate());
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
            
            // Load saved draft if exists
            this.loadDraft();
        },

        // Template System
        async loadTemplates() {
            try {
                const response = await fetch(`/api/financial/templates?type=${this.type}`);
                const data = await response.json();
                if (data.success) {
                    this.templates = data.templates;
                    this.templateData.favorites = data.templates.filter(t => t.is_favorite);
                    this.templateData.recent = data.recent || [];
                }
            } catch (error) {
                console.error('Failed to load templates:', error);
            }
        },
        
        applyTemplate(templateId) {
            const template = this.templates.find(t => t.id === templateId);
            if (template) {
                // Apply template data
                this.document.scope = template.scope || this.document.scope;
                this.document.terms_conditions = template.terms_conditions || this.document.terms_conditions;
                this.document.note = template.note || this.document.note;
                this.document.discount_type = template.discount_type || 'fixed';
                this.document.discount_amount = template.discount_amount || 0;
                this.document.tax_rate = template.tax_rate || 0;
                
                // Apply line items
                if (template.items && template.items.length > 0) {
                    this.document.items = template.items.map(item => ({
                        ...item,
                        id: Date.now() + Math.random()
                    }));
                }
                
                // Apply custom fields
                if (template.custom_fields) {
                    this.document.custom_fields = { ...template.custom_fields };
                }
                
                this.calculateTotals();
                this.showNotification('success', 'Template applied successfully');
            }
        },
        
        async saveAsTemplate() {
            if (!this.templateData.newTemplateName) {
                this.errors.template_name = 'Template name is required';
                return;
            }
            
            try {
                const response = await fetch('/api/financial/templates', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        name: this.templateData.newTemplateName,
                        type: this.type,
                        data: {
                            scope: this.document.scope,
                            terms_conditions: this.document.terms_conditions,
                            note: this.document.note,
                            discount_type: this.document.discount_type,
                            discount_amount: this.document.discount_amount,
                            tax_rate: this.document.tax_rate,
                            items: this.document.items.map(item => ({
                                description: item.description,
                                quantity: item.quantity,
                                rate: item.rate,
                                unit: item.unit,
                                tax_rate: item.tax_rate
                            })),
                            custom_fields: this.document.custom_fields
                        }
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    this.templates.push(data.template);
                    this.templateData.newTemplateName = '';
                    this.templateData.creating = false;
                    this.showNotification('success', 'Template saved successfully');
                }
            } catch (error) {
                console.error('Failed to save template:', error);
                this.showNotification('error', 'Failed to save template');
            }
        },

        // Client Management
        selectClient(clientId) {
            this.document.client_id = clientId;
            const client = this.clients.find(c => c.id == clientId);
            
            if (client) {
                // Set client defaults
                if (client.currency_code) {
                    this.document.currency_code = client.currency_code;
                }
                
                if (client.payment_terms) {
                    this.document.payment_terms = client.payment_terms;
                    this.updateDueDate();
                }
                
                if (client.tax_rate !== undefined) {
                    this.document.tax_rate = client.tax_rate;
                }
                
                // Load client-specific templates
                this.loadClientTemplates(clientId);
                
                // Load client payment history
                this.loadClientPaymentHistory(clientId);
                
                this.clearErrors(['client_id']);
                this.calculateTotals();
            }
        },
        
        async loadClientTemplates(clientId) {
            try {
                const response = await fetch(`/api/clients/${clientId}/templates`);
                const data = await response.json();
                if (data.success && data.templates) {
                    // Add client-specific templates to the list
                    this.templates = [...this.templates, ...data.templates];
                }
            } catch (error) {
                console.error('Failed to load client templates:', error);
            }
        },
        
        async loadClientPaymentHistory(clientId) {
            try {
                const response = await fetch(`/api/clients/${clientId}/payment-history`);
                const data = await response.json();
                if (data.success) {
                    this.analytics.averagePaymentTime = data.average_payment_time || 0;
                    this.analytics.totalRevenue = data.total_revenue || 0;
                    this.analytics.outstandingAmount = data.outstanding_amount || 0;
                }
            } catch (error) {
                console.error('Failed to load payment history:', error);
            }
        },

        // Line Item Management
        addLineItem(data = null) {
            const newItem = {
                id: Date.now() + Math.random(),
                description: data?.description || '',
                quantity: data?.quantity || 1,
                unit: data?.unit || 'hours',
                rate: data?.rate || 0,
                tax_rate: data?.tax_rate || this.document.tax_rate || 0,
                discount: data?.discount || 0,
                discount_type: data?.discount_type || 'fixed',
                amount: 0,
                cost: data?.cost || 0, // For profit calculation
                notes: data?.notes || '',
                category: data?.category || '',
                recurring: false
            };
            
            this.document.items.push(newItem);
            this.calculateLineItem(this.document.items.length - 1);
            
            // Focus on new item
            this.$nextTick(() => {
                const input = document.querySelector(`#item-description-${newItem.id}`);
                if (input) input.focus();
            });
        },
        
        removeLineItem(index) {
            if (this.document.items.length > 1) {
                this.document.items.splice(index, 1);
                this.calculateTotals();
            }
        },
        
        duplicateLineItem(index) {
            const item = this.document.items[index];
            const newItem = {
                ...item,
                id: Date.now() + Math.random(),
                description: `${item.description} (Copy)`
            };
            this.document.items.splice(index + 1, 0, newItem);
            this.calculateTotals();
        },
        
        // Bulk operations
        bulkUpdateItems(updates) {
            this.document.items = this.document.items.map(item => ({
                ...item,
                ...updates
            }));
            this.calculateTotals();
        },
        
        importItemsFromCSV(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const csv = e.target.result;
                const lines = csv.split('\n');
                const headers = lines[0].split(',');
                
                for (let i = 1; i < lines.length; i++) {
                    const values = lines[i].split(',');
                    if (values.length > 1) {
                        const item = {};
                        headers.forEach((header, index) => {
                            item[header.trim()] = values[index]?.trim();
                        });
                        this.addLineItem(item);
                    }
                }
                
                this.calculateTotals();
                this.showNotification('success', 'Items imported successfully');
            };
            reader.readAsText(file);
        },

        // Advanced Calculations
        calculateLineItem(index) {
            const item = this.document.items[index];
            if (!item) return;
            
            // Base calculation
            let baseAmount = (parseFloat(item.quantity) || 0) * (parseFloat(item.rate) || 0);
            
            // Apply line item discount
            if (item.discount) {
                if (item.discount_type === 'percentage') {
                    baseAmount -= baseAmount * (parseFloat(item.discount) / 100);
                } else {
                    baseAmount -= parseFloat(item.discount);
                }
            }
            
            // Apply tax if inclusive
            if (this.document.tax_type === 'inclusive' && item.tax_rate) {
                // Price includes tax, so we need to extract it
                const taxMultiplier = 1 + (parseFloat(item.tax_rate) / 100);
                item.amount = baseAmount / taxMultiplier;
                item.tax_amount = baseAmount - item.amount;
            } else {
                item.amount = baseAmount;
                item.tax_amount = baseAmount * (parseFloat(item.tax_rate) / 100 || 0);
            }
            
            // Calculate profit margin
            if (item.cost) {
                item.profit = item.amount - (parseFloat(item.cost) * parseFloat(item.quantity));
                item.profit_margin = item.amount > 0 ? (item.profit / item.amount) * 100 : 0;
            }
            
            this.calculateTotals();
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
            if (this.document.tax_type === 'inclusive') {
                // Tax is already included in the item amounts
                this.taxAmount = this.document.items.reduce((sum, item) => {
                    return sum + (parseFloat(item.tax_amount) || 0);
                }, 0);
            } else {
                // Tax is exclusive
                const taxableAmount = this.subtotal - this.discountAmount;
                this.taxAmount = this.document.items.reduce((sum, item) => {
                    const itemTotal = parseFloat(item.amount) || 0;
                    const itemTaxRate = parseFloat(item.tax_rate) || parseFloat(this.document.tax_rate) || 0;
                    return sum + (itemTotal * itemTaxRate / 100);
                }, 0);
            }

            // Calculate total
            this.total = this.subtotal - this.discountAmount + (this.document.tax_type === 'exclusive' ? this.taxAmount : 0);
            
            // Calculate in base currency
            this.totalInBaseCurrency = this.total * (this.document.exchange_rate || 1);
            
            // Calculate overall profit margin
            const totalCost = this.document.items.reduce((sum, item) => {
                return sum + ((parseFloat(item.cost) || 0) * (parseFloat(item.quantity) || 0));
            }, 0);
            
            if (totalCost > 0) {
                const totalProfit = this.subtotal - totalCost;
                this.profitMargin = (totalProfit / this.subtotal) * 100;
            }

            // Update document amount
            this.document.amount = this.total;
        },

        // Multi-Currency Support
        async loadCurrencyRates() {
            try {
                const response = await fetch('/api/currency/rates');
                const data = await response.json();
                if (data.success) {
                    this.currencies = data.rates;
                }
            } catch (error) {
                console.error('Failed to load currency rates:', error);
            }
        },
        
        updateExchangeRate() {
            const currency = this.currencies.find(c => c.code === this.document.currency_code);
            if (currency) {
                this.document.exchange_rate = currency.rate;
                this.calculateTotals();
            }
        },

        // Recurring Invoice Management
        setupRecurring() {
            if (!this.document.recurring.enabled) return;
            
            // Calculate next invoice date
            const startDate = new Date(this.document.recurring.start_date || this.document.date);
            const frequency = this.document.recurring.frequency;
            const interval = parseInt(this.document.recurring.interval) || 1;
            
            let nextDate = new Date(startDate);
            
            switch (frequency) {
                case 'daily':
                    nextDate.setDate(nextDate.getDate() + interval);
                    break;
                case 'weekly':
                    nextDate.setDate(nextDate.getDate() + (7 * interval));
                    break;
                case 'monthly':
                    nextDate.setMonth(nextDate.getMonth() + interval);
                    break;
                case 'quarterly':
                    nextDate.setMonth(nextDate.getMonth() + (3 * interval));
                    break;
                case 'yearly':
                    nextDate.setFullYear(nextDate.getFullYear() + interval);
                    break;
            }
            
            this.document.recurring.next_date = nextDate.toISOString().split('T')[0];
        },

        // Payment Management
        addPayment() {
            if (this.paymentData.amount <= 0) {
                this.errors.payment_amount = 'Amount must be greater than 0';
                return;
            }
            
            const payment = {
                ...this.paymentData,
                id: Date.now(),
                created_at: new Date().toISOString()
            };
            
            this.payments.push(payment);
            
            // Update document status if fully paid
            const totalPaid = this.payments.reduce((sum, p) => sum + parseFloat(p.amount), 0);
            if (totalPaid >= this.total) {
                this.document.status = 'Paid';
            } else if (totalPaid > 0) {
                this.document.status = 'Partially Paid';
            }
            
            // Reset payment form
            this.paymentData = {
                amount: 0,
                date: new Date().toISOString().split('T')[0],
                method: 'bank_transfer',
                reference: '',
                notes: ''
            };
            
            this.showPaymentModal = false;
            this.showNotification('success', 'Payment recorded successfully');
        },
        
        removePayment(index) {
            this.payments.splice(index, 1);
            this.updatePaymentStatus();
        },
        
        updatePaymentStatus() {
            const totalPaid = this.payments.reduce((sum, p) => sum + parseFloat(p.amount), 0);
            
            if (totalPaid >= this.total) {
                this.document.status = 'Paid';
            } else if (totalPaid > 0) {
                this.document.status = 'Partially Paid';
            } else {
                this.document.status = 'Sent';
            }
        },

        // Document Actions
        async save(action = 'save') {
            if (this.saving) return;

            if (!this.validateForm()) {
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
                    body: JSON.stringify({
                        ...this.document,
                        action: action,
                        payments: this.payments
                    })
                });

                const data = await response.json();

                if (data.success || response.ok) {
                    this.document.id = data.id || this.document.id;
                    
                    // Handle different actions
                    switch (action) {
                        case 'send':
                            this.document.status = 'Sent';
                            this.showNotification('success', `${this.type} sent successfully`);
                            break;
                        case 'approve':
                            this.document.approval_status = 'approved';
                            this.showNotification('success', `${this.type} approved`);
                            break;
                        case 'draft':
                            this.document.status = 'Draft';
                            this.showNotification('success', 'Saved as draft');
                            break;
                        default:
                            this.showNotification('success', `${this.type} saved successfully`);
                    }
                    
                    // Clear draft
                    this.clearDraft();
                    
                    // Redirect if needed
                    if (action === 'save_and_new') {
                        window.location.href = `/financial/${this.type}s/create`;
                    } else if (action !== 'draft') {
                        setTimeout(() => {
                            window.location.href = `/financial/${this.type}s/${data.id || this.document.id}`;
                        }, 1500);
                    }
                } else {
                    this.errors = data.errors || {};
                    this.showNotification('error', data.message || 'Failed to save');
                }
            } catch (error) {
                console.error('Save failed:', error);
                this.showNotification('error', 'Network error occurred');
            } finally {
                this.saving = false;
            }
        },
        
        saveAsDraft() {
            this.document.status = 'Draft';
            this.save('draft');
        },
        
        saveAndSend() {
            this.save('send');
        },
        
        saveAndNew() {
            this.save('save_and_new');
        },
        
        approve() {
            if (confirm('Approve this ' + this.type + '?')) {
                this.save('approve');
            }
        },
        
        reject() {
            if (confirm('Reject this ' + this.type + '?')) {
                this.document.approval_status = 'rejected';
                this.save('reject');
            }
        },

        // Email Functions
        async sendEmail() {
            if (!this.emailSettings.to.length) {
                this.errors.email_to = 'At least one recipient is required';
                return;
            }
            
            this.loading = true;
            
            try {
                const response = await fetch(`/api/financial/${this.type}s/${this.document.id}/send`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.emailSettings)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.document.status = 'Sent';
                    this.showEmailModal = false;
                    this.showNotification('success', 'Email sent successfully');
                } else {
                    this.showNotification('error', data.message || 'Failed to send email');
                }
            } catch (error) {
                console.error('Email send failed:', error);
                this.showNotification('error', 'Failed to send email');
            } finally {
                this.loading = false;
            }
        },
        
        scheduleEmail() {
            if (!this.emailSettings.scheduleDate) {
                this.errors.schedule_date = 'Schedule date is required';
                return;
            }
            
            // Save scheduled email
            this.document.scheduled_send = this.emailSettings.scheduleDate;
            this.save('schedule');
        },

        // PDF Functions
        async generatePDF() {
            this.loading = true;
            
            try {
                const response = await fetch(`/api/financial/${this.type}s/${this.document.id}/pdf`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.document)
                });
                
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                
                // Open in new tab
                window.open(url, '_blank');
                
                // Clean up
                setTimeout(() => window.URL.revokeObjectURL(url), 100);
            } catch (error) {
                console.error('PDF generation failed:', error);
                this.showNotification('error', 'Failed to generate PDF');
            } finally {
                this.loading = false;
            }
        },
        
        async previewPDF() {
            this.showPreview = true;
            await this.generatePDF();
        },

        // Versioning
        async loadVersions() {
            if (!this.document.id) return;
            
            try {
                const response = await fetch(`/api/financial/${this.type}s/${this.document.id}/versions`);
                const data = await response.json();
                
                if (data.success) {
                    this.versions = data.versions;
                    this.currentVersion = data.current_version;
                }
            } catch (error) {
                console.error('Failed to load versions:', error);
            }
        },
        
        async restoreVersion(versionId) {
            if (!confirm('Restore this version? Current changes will be lost.')) return;
            
            try {
                const response = await fetch(`/api/financial/${this.type}s/${this.document.id}/restore/${versionId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.document = data.document;
                    this.calculateTotals();
                    this.showNotification('success', 'Version restored successfully');
                }
            } catch (error) {
                console.error('Failed to restore version:', error);
                this.showNotification('error', 'Failed to restore version');
            }
        },

        // Automation
        setupAutomation() {
            // Setup payment reminders
            if (this.document.payment_reminder_enabled) {
                this.scheduleReminders();
            }
            
            // Setup recurring generation
            if (this.document.recurring.enabled) {
                this.setupRecurring();
            }
            
            // Setup approval workflow
            if (this.document.requires_approval) {
                this.initiateApprovalWorkflow();
            }
        },
        
        scheduleReminders() {
            const dueDate = new Date(this.document.due_date || this.document.expire_date);
            
            this.emailSettings.reminderDays.forEach(days => {
                const reminderDate = new Date(dueDate);
                reminderDate.setDate(reminderDate.getDate() - days);
                
                if (reminderDate > new Date()) {
                    // Schedule reminder
                    this.createReminder(reminderDate, days);
                }
            });
        },
        
        async createReminder(date, daysBefore) {
            try {
                await fetch('/api/reminders', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        type: 'payment_reminder',
                        document_type: this.type,
                        document_id: this.document.id,
                        send_date: date.toISOString(),
                        days_before: daysBefore
                    })
                });
            } catch (error) {
                console.error('Failed to create reminder:', error);
            }
        },

        // Validation
        validateForm() {
            this.errors = {};
            let isValid = true;

            // Basic validation
            if (!this.document.client_id) {
                this.errors.client_id = 'Client is required';
                isValid = false;
            }

            if (!this.document.category_id) {
                this.errors.category_id = 'Category is required';
                isValid = false;
            }

            if (this.document.items.length === 0) {
                this.errors.items = 'At least one line item is required';
                isValid = false;
            }

            // Validate line items
            this.document.items.forEach((item, index) => {
                if (!item.description) {
                    this.errors[`item_${index}_description`] = 'Description is required';
                    isValid = false;
                }
                if (item.quantity <= 0) {
                    this.errors[`item_${index}_quantity`] = 'Quantity must be greater than 0';
                    isValid = false;
                }
            });

            // Custom validation rules
            if (this.type === 'quote' && !this.document.expire_date) {
                this.errors.expire_date = 'Expiration date is required for quotes';
                isValid = false;
            }

            if (this.type === 'invoice' && !this.document.due_date) {
                this.errors.due_date = 'Due date is required for invoices';
                isValid = false;
            }

            return isValid;
        },

        // Draft Management
        saveDraft() {
            localStorage.setItem(`${this.type}_draft`, JSON.stringify(this.document));
            this.showNotification('info', 'Draft saved locally');
        },
        
        loadDraft() {
            const draft = localStorage.getItem(`${this.type}_draft`);
            if (draft) {
                try {
                    const draftData = JSON.parse(draft);
                    // Only load if it's a recent draft (less than 24 hours old)
                    const draftDate = new Date(draftData.date);
                    const now = new Date();
                    const hoursDiff = (now - draftDate) / (1000 * 60 * 60);
                    
                    if (hoursDiff < 24) {
                        if (confirm('Resume from saved draft?')) {
                            this.document = { ...this.document, ...draftData };
                            this.calculateTotals();
                        }
                    }
                } catch (error) {
                    console.error('Failed to load draft:', error);
                }
            }
        },
        
        clearDraft() {
            localStorage.removeItem(`${this.type}_draft`);
        },

        // Auto-save
        setupAutoSave() {
            setInterval(() => {
                if (this.hasChanges()) {
                    this.saveDraft();
                }
            }, 30000); // Auto-save every 30 seconds
        },
        
        hasChanges() {
            // Check if document has unsaved changes
            return this.document.items.length > 0 && 
                   (this.document.client_id || this.document.scope || 
                    this.document.items.some(item => item.description));
        },

        // Keyboard Shortcuts
        setupKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                // Ctrl/Cmd + S: Save
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    this.save();
                }
                
                // Ctrl/Cmd + Shift + S: Save and send
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 's') {
                    e.preventDefault();
                    this.saveAndSend();
                }
                
                // Ctrl/Cmd + P: Preview PDF
                if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                    e.preventDefault();
                    this.previewPDF();
                }
                
                // Ctrl/Cmd + N: Add new line item
                if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                    e.preventDefault();
                    this.addLineItem();
                }
                
                // Escape: Close modals
                if (e.key === 'Escape') {
                    this.closeAllModals();
                }
            });
        },

        // UI Helpers
        closeAllModals() {
            this.showTemplateModal = false;
            this.showEmailModal = false;
            this.showPaymentModal = false;
            this.showHistoryModal = false;
            this.showPreview = false;
        },
        
        showNotification(type, message) {
            // Dispatch Alpine event for notification
            this.$dispatch('notification', { type, message });
            
            // Also show browser notification if permitted
            if (Notification.permission === 'granted') {
                new Notification(`Nestogy ${this.type}`, {
                    body: message,
                    icon: '/favicon.ico'
                });
            }
        },
        
        formatCurrency(amount) {
            const currency = this.currencies.find(c => c.code === this.document.currency_code);
            const symbol = currency?.symbol || '$';
            return `${symbol}${parseFloat(amount || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}`;
        },
        
        formatDate(date) {
            if (!date) return '';
            return new Date(date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },
        
        addDays(date, days) {
            const result = new Date(date);
            result.setDate(result.getDate() + days);
            return result;
        },
        
        updateDueDate() {
            if (this.type !== 'invoice') return;
            
            const terms = this.document.payment_terms;
            const invoiceDate = new Date(this.document.date);
            let dueDate = new Date(invoiceDate);
            
            switch (terms) {
                case 'immediate':
                    // Due date is same as invoice date
                    break;
                case 'net15':
                    dueDate.setDate(dueDate.getDate() + 15);
                    break;
                case 'net30':
                    dueDate.setDate(dueDate.getDate() + 30);
                    break;
                case 'net45':
                    dueDate.setDate(dueDate.getDate() + 45);
                    break;
                case 'net60':
                    dueDate.setDate(dueDate.getDate() + 60);
                    break;
                case 'eom': // End of month
                    dueDate = new Date(dueDate.getFullYear(), dueDate.getMonth() + 1, 0);
                    break;
            }
            
            this.document.due_date = dueDate.toISOString().split('T')[0];
        },
        
        clearErrors(fields = null) {
            if (fields) {
                fields.forEach(field => delete this.errors[field]);
            } else {
                this.errors = {};
            }
        },

        // Export functions
        exportToCSV() {
            const headers = ['Description', 'Quantity', 'Unit', 'Rate', 'Tax', 'Amount'];
            const rows = this.document.items.map(item => [
                item.description,
                item.quantity,
                item.unit,
                item.rate,
                item.tax_rate,
                item.amount
            ]);
            
            let csv = headers.join(',') + '\n';
            rows.forEach(row => {
                csv += row.join(',') + '\n';
            });
            
            // Add totals
            csv += '\n';
            csv += `Subtotal,,,,, ${this.subtotal}\n`;
            csv += `Discount,,,,, ${this.discountAmount}\n`;
            csv += `Tax,,,,, ${this.taxAmount}\n`;
            csv += `Total,,,,, ${this.total}\n`;
            
            // Download
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${this.type}_${this.document.id || 'new'}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        },
        
        print() {
            window.print();
        }
    }));
});