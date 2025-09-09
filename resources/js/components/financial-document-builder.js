/**
 * Financial Document Builder Component
 * Modern Alpine.js component for creating quotes and invoices with enhanced UX
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('financialDocumentBuilder', (config = {}) => ({
        // Configuration
        type: config.type || 'quote', // 'quote' or 'invoice'
        mode: config.mode || 'create', // 'create' or 'edit'
        
        // Core data
        document: {
            client_id: config.selectedClientId || '',
            category_id: '',
            date: new Date().toISOString().split('T')[0],
            expire_date: '', // quotes only
            due_date: '', // invoices only
            currency_code: 'USD',
            status: 'draft',
            scope: '',
            note: '',
            terms_conditions: '',
            discount_type: 'fixed',
            discount_amount: 0,
            items: []
        },

        // UI State
        loading: false,
        saving: false,
        errors: {},
        showPreview: false,
        currentStep: 1,
        totalSteps: 3,
        autoSaveInterval: null,
        lastSaved: null,

        // Calculations
        subtotal: 0,
        taxAmount: 0,
        discountAmount: 0,
        total: 0,

        // Available data
        clients: config.clients || [],
        categories: config.categories || [],
        templates: config.templates || [],
        taxRates: config.taxRates || [],

        // Initialize component
        init() {
            this.initializeDocument();
            this.setupAutoSave();
            this.setupKeyboardShortcuts();
            this.initMobile();
            this.calculateTotals();
        },

        // Initialize document with smart defaults
        initializeDocument() {
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

        // Setup auto-save functionality
        setupAutoSave() {
            this.autoSaveInterval = setInterval(() => {
                if (this.hasUnsavedChanges()) {
                    this.autoSave();
                }
            }, 30000); // Auto-save every 30 seconds
        },

        // Setup keyboard shortcuts
        setupKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                if (e.ctrlKey || e.metaKey) {
                    switch (e.key) {
                        case 's':
                            e.preventDefault();
                            this.save();
                            break;
                        case 'Enter':
                            if (e.target.classList.contains('line-item-description')) {
                                e.preventDefault();
                                this.addLineItem();
                            }
                            break;
                    }
                }
            });
        },

        // Client selection with smart defaults
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
        addLineItem(template = null) {
            const newItem = {
                id: Date.now(), // Temporary ID
                description: template?.description || '',
                quantity: template?.quantity || 1,
                rate: template?.rate || 0,
                tax_rate: template?.tax_rate || 0,
                amount: 0
            };
            
            this.document.items.push(newItem);
            this.calculateTotals();
            
            // Focus on the new item's description field
            this.$nextTick(() => {
                const lastIndex = this.document.items.length - 1;
                const descField = this.$refs[`item-description-${lastIndex}`];
                if (descField) descField.focus();
            });
        },

        removeLineItem(index) {
            if (this.document.items.length > 1) {
                this.document.items.splice(index, 1);
                this.calculateTotals();
            }
        },

        moveLineItem(fromIndex, toIndex) {
            const items = [...this.document.items];
            const [movedItem] = items.splice(fromIndex, 1);
            items.splice(toIndex, 0, movedItem);
            this.document.items = items;
            this.calculateTotals();
        },

        // Drag and Drop functionality
        dragState: {
            draggedIndex: null,
            dragOverIndex: null,
            isDragging: false
        },

        handleDragStart(event, index) {
            this.dragState.draggedIndex = index;
            this.dragState.isDragging = true;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/html', event.target.outerHTML);
            event.target.style.opacity = '0.5';
        },

        handleDragEnd(event) {
            event.target.style.opacity = '1';
            this.dragState.draggedIndex = null;
            this.dragState.dragOverIndex = null;
            this.dragState.isDragging = false;
        },

        handleDragOver(event, index) {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            this.dragState.dragOverIndex = index;
        },

        handleDragEnter(event, index) {
            event.preventDefault();
            this.dragState.dragOverIndex = index;
        },

        handleDragLeave(event) {
            // Only clear if we're leaving the container, not just moving between children
            if (!event.currentTarget.contains(event.relatedTarget)) {
                this.dragState.dragOverIndex = null;
            }
        },

        handleDrop(event, dropIndex) {
            event.preventDefault();
            
            if (this.dragState.draggedIndex !== null && this.dragState.draggedIndex !== dropIndex) {
                this.moveLineItem(this.dragState.draggedIndex, dropIndex);
            }
            
            this.dragState.draggedIndex = null;
            this.dragState.dragOverIndex = null;
            this.dragState.isDragging = false;
        },

        getDragIndicatorClass(index) {
            if (!this.dragState.isDragging) return '';
            
            if (this.dragState.dragOverIndex === index) {
                return this.dragState.draggedIndex < index ? 'drag-over-bottom' : 'drag-over-top';
            }
            
            return '';
        },

        // Line item duplication
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

        // Bulk line item operations
        bulkActions: {
            selectedItems: [],
            showBulkActions: false
        },

        toggleItemSelection(index) {
            const selectedIndex = this.bulkActions.selectedItems.indexOf(index);
            if (selectedIndex > -1) {
                this.bulkActions.selectedItems.splice(selectedIndex, 1);
            } else {
                this.bulkActions.selectedItems.push(index);
            }
            this.bulkActions.showBulkActions = this.bulkActions.selectedItems.length > 0;
        },

        selectAllItems() {
            this.bulkActions.selectedItems = this.document.items.map((_, index) => index);
            this.bulkActions.showBulkActions = true;
        },

        clearSelection() {
            this.bulkActions.selectedItems = [];
            this.bulkActions.showBulkActions = false;
        },

        bulkDeleteItems() {
            // Sort in descending order to avoid index shifting issues
            const sortedIndices = [...this.bulkActions.selectedItems].sort((a, b) => b - a);
            
            // Ensure we don't delete all items
            if (sortedIndices.length >= this.document.items.length) {
                // Keep the last item
                sortedIndices.pop();
            }
            
            sortedIndices.forEach(index => {
                this.document.items.splice(index, 1);
            });
            
            this.clearSelection();
            this.calculateTotals();
        },

        bulkApplyDiscount(discountPercent) {
            this.bulkActions.selectedItems.forEach(index => {
                const item = this.document.items[index];
                if (item && item.rate > 0) {
                    const discount = item.rate * (discountPercent / 100);
                    item.rate = Math.max(0, item.rate - discount);
                    this.calculateLineItem(index);
                }
            });
            
            this.clearSelection();
        },

        // Real-time calculations
        calculateLineItem(index) {
            const item = this.document.items[index];
            if (item) {
                // Use advanced pricing if available
                if (item.pricingModel && item.pricingModel !== 'standard') {
                    this.calculateAdvancedPricing(item, index);
                } else {
                    item.amount = (parseFloat(item.quantity) || 0) * (parseFloat(item.rate) || 0);
                    this.calculateTotals();
                }
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
            this.taxAmount = this.document.items.reduce((sum, item) => {
                const itemTotal = (parseFloat(item.amount) || 0);
                const taxRate = parseFloat(item.tax_rate) || 0;
                return sum + (itemTotal * taxRate / 100);
            }, 0);

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
                    if (!item.quantity || item.quantity <= 0) {
                        this.errors[`item_${index}_quantity`] = 'Quantity must be greater than 0';
                        isValid = false;
                    }
                    if (!item.rate || item.rate < 0) {
                        this.errors[`item_${index}_rate`] = 'Rate must be 0 or greater';
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

        // Auto-save functionality
        autoSave() {
            if (this.saving) return;
            
            this.saving = true;
            
            fetch(`/api/${this.type}s/auto-save`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    document: this.document,
                    draft: true
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.lastSaved = new Date();
                }
            })
            .catch(error => {
                console.error('Auto-save failed:', error);
            })
            .finally(() => {
                this.saving = false;
            });
        },

        // Save document
        async save(options = {}) {
            if (this.saving) return;

            if (!this.validateStep(this.totalSteps)) {
                this.currentStep = 1; // Go to first invalid step
                return;
            }

            this.saving = true;
            this.errors = {};

            try {
                const response = await fetch(`/financial/${this.type}s`, {
                    method: this.mode === 'edit' ? 'PUT' : 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        ...this.document,
                        ...options
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Success notification
                    this.$dispatch('notification', {
                        type: 'success',
                        message: `${this.type.charAt(0).toUpperCase() + this.type.slice(1)} saved successfully`
                    });

                    // Redirect or update URL
                    if (options.redirect !== false) {
                        window.location.href = data.redirect_url || `/financial/${this.type}s/${data.id}`;
                    }
                } else {
                    this.errors = data.errors || {};
                    this.$dispatch('notification', {
                        type: 'error',
                        message: data.message || 'Failed to save document'
                    });
                }
            } catch (error) {
                console.error('Save failed:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: 'Network error occurred. Please try again.'
                });
            } finally {
                this.saving = false;
            }
        },

        // Enhanced Template Management
        templateManager: {
            isCreating: false,
            currentTemplateName: '',
            showTemplateModal: false,
            savedTemplates: [],
            recentTemplates: [],
            favoriteTemplates: []
        },

        loadTemplate(templateId) {
            const template = this.templates.find(t => t.id == templateId);
            if (template) {
                // Load basic information
                this.document.scope = template.scope || '';
                this.document.note = template.note || '';
                this.document.terms_conditions = template.terms_conditions || '';
                this.document.discount_type = template.discount_type || 'fixed';
                this.document.discount_amount = template.discount_amount || 0;
                
                // Load line items with enhanced properties
                if (template.items && template.items.length > 0) {
                    this.document.items = template.items.map(item => ({
                        ...item,
                        id: Date.now() + Math.random(),
                        amount: (parseFloat(item.quantity) || 1) * (parseFloat(item.rate) || 0),
                        tax_rate: item.tax_rate || 0
                    }));
                }

                // Add to recent templates
                this.addToRecentTemplates(template);
                this.calculateTotals();
                
                // Show success notification
                this.$dispatch('notification', {
                    type: 'success',
                    message: `Template "${template.name}" loaded successfully`
                });
            }
        },

        // Save current document as template
        saveAsTemplate() {
            if (!this.templateManager.currentTemplateName.trim()) {
                this.errors.template_name = 'Template name is required';
                return;
            }

            const templateData = {
                name: this.templateManager.currentTemplateName,
                scope: this.document.scope,
                note: this.document.note,
                terms_conditions: this.document.terms_conditions,
                discount_type: this.document.discount_type,
                discount_amount: this.document.discount_amount,
                items: this.document.items.map(item => ({
                    description: item.description,
                    quantity: item.quantity,
                    rate: item.rate,
                    tax_rate: item.tax_rate || 0
                })),
                type: this.type
            };

            fetch('/api/document-templates', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(templateData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.templates.push(data.template);
                    this.templateManager.currentTemplateName = '';
                    this.templateManager.isCreating = false;
                    this.clearErrors(['template_name']);
                    
                    this.$dispatch('notification', {
                        type: 'success',
                        message: 'Template saved successfully'
                    });
                } else {
                    this.errors.template_name = data.message || 'Failed to save template';
                }
            })
            .catch(error => {
                console.error('Template save failed:', error);
                this.errors.template_name = 'Network error occurred';
            });
        },

        // Template favorites
        toggleTemplateFavorite(templateId) {
            const template = this.templates.find(t => t.id == templateId);
            if (!template) return;

            fetch(`/api/document-templates/${templateId}/favorite`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    template.is_favorite = !template.is_favorite;
                    this.updateFavoriteTemplates();
                }
            })
            .catch(error => console.error('Failed to toggle favorite:', error));
        },

        // Smart template suggestions based on client and category
        getSmartTemplateSuggestions() {
            if (!this.document.client_id || !this.document.category_id) return [];
            
            return this.templates.filter(template => {
                // Match by category or client history
                return template.category_id === this.document.category_id ||
                       template.recent_clients?.includes(this.document.client_id);
            }).slice(0, 3);
        },

        // Template search and filtering
        templateFilters: {
            search: '',
            category: '',
            type: '',
            sortBy: 'recent' // recent, name, usage
        },

        getFilteredTemplates() {
            let filtered = [...this.templates];

            // Search filter
            if (this.templateFilters.search) {
                const search = this.templateFilters.search.toLowerCase();
                filtered = filtered.filter(t => 
                    t.name.toLowerCase().includes(search) ||
                    t.scope?.toLowerCase().includes(search)
                );
            }

            // Category filter
            if (this.templateFilters.category) {
                filtered = filtered.filter(t => t.category_id === this.templateFilters.category);
            }

            // Type filter
            if (this.templateFilters.type) {
                filtered = filtered.filter(t => t.type === this.templateFilters.type);
            }

            // Sort
            switch (this.templateFilters.sortBy) {
                case 'name':
                    filtered.sort((a, b) => a.name.localeCompare(b.name));
                    break;
                case 'usage':
                    filtered.sort((a, b) => (b.usage_count || 0) - (a.usage_count || 0));
                    break;
                case 'recent':
                default:
                    filtered.sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at));
                    break;
            }

            return filtered;
        },

        // Template analytics
        addToRecentTemplates(template) {
            const recent = this.templateManager.recentTemplates;
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

        updateFavoriteTemplates() {
            this.templateManager.favoriteTemplates = this.templates.filter(t => t.is_favorite);
        },

        // PDF Preview and Generation
        pdfPreview: {
            isVisible: false,
            isGenerating: false,
            previewUrl: null,
            error: null,
            downloadUrl: null
        },

        async generatePDFPreview() {
            if (this.pdfPreview.isGenerating) return;
            
            this.pdfPreview.isGenerating = true;
            this.pdfPreview.error = null;

            try {
                const response = await fetch(`/api/${this.type}s/preview-pdf`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        document: this.document,
                        preview: true
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.pdfPreview.previewUrl = data.preview_url;
                    this.pdfPreview.downloadUrl = data.download_url;
                    this.pdfPreview.isVisible = true;
                } else {
                    this.pdfPreview.error = data.message || 'Failed to generate PDF preview';
                }
            } catch (error) {
                console.error('PDF preview generation failed:', error);
                this.pdfPreview.error = 'Network error occurred while generating preview';
            } finally {
                this.pdfPreview.isGenerating = false;
            }
        },

        closePDFPreview() {
            this.pdfPreview.isVisible = false;
            this.pdfPreview.previewUrl = null;
            this.pdfPreview.error = null;
        },

        downloadPDF() {
            if (this.pdfPreview.downloadUrl) {
                window.open(this.pdfPreview.downloadUrl, '_blank');
            }
        },

        // Email PDF functionality
        emailPDF: {
            isVisible: false,
            isSending: false,
            recipientEmail: '',
            subject: '',
            message: '',
            ccSelf: true
        },

        async sendPDFByEmail() {
            if (!this.emailPDF.recipientEmail) {
                this.errors.recipient_email = 'Recipient email is required';
                return;
            }

            this.emailPDF.isSending = true;
            this.clearErrors(['recipient_email']);

            try {
                const response = await fetch(`/api/${this.type}s/email-pdf`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        document: this.document,
                        recipient_email: this.emailPDF.recipientEmail,
                        subject: this.emailPDF.subject || `${this.type.charAt(0).toUpperCase() + this.type.slice(1)} from ${this.getClientName()}`,
                        message: this.emailPDF.message,
                        cc_self: this.emailPDF.ccSelf
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.$dispatch('notification', {
                        type: 'success',
                        message: `${this.type.charAt(0).toUpperCase() + this.type.slice(1)} sent successfully`
                    });
                    this.emailPDF.isVisible = false;
                    this.resetEmailForm();
                } else {
                    this.errors.recipient_email = data.message || 'Failed to send email';
                }
            } catch (error) {
                console.error('Email send failed:', error);
                this.errors.recipient_email = 'Network error occurred';
            } finally {
                this.emailPDF.isSending = false;
            }
        },

        resetEmailForm() {
            this.emailPDF.recipientEmail = '';
            this.emailPDF.subject = '';
            this.emailPDF.message = '';
            this.emailPDF.ccSelf = true;
        },

        getClientName() {
            const client = this.clients.find(c => c.id == this.document.client_id);
            return client ? client.display_name : 'Unknown Client';
        },

        // Print functionality
        async printDocument() {
            await this.generatePDFPreview();
            if (this.pdfPreview.previewUrl) {
                const printWindow = window.open(this.pdfPreview.previewUrl, '_blank');
                printWindow.onload = () => {
                    printWindow.print();
                };
            }
        },

        // Mobile responsiveness utilities
        isMobile() {
            return window.innerWidth <= 768;
        },

        isTouch() {
            return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        },

        // Mobile-specific line item rendering
        shouldUseMobileLayout() {
            return this.isMobile();
        },

        // Mobile drag and drop alternative (reorder buttons)
        moveItemUp(index) {
            if (index > 0) {
                this.moveLineItem(index, index - 1);
            }
        },

        moveItemDown(index) {
            if (index < this.document.items.length - 1) {
                this.moveLineItem(index, index + 1);
            }
        },

        // Mobile-friendly step navigation
        canGoToStep(step) {
            if (step <= this.currentStep) return true;
            return this.validateStep(this.currentStep);
        },

        // Touch-friendly interactions
        handleTouchStart(event, index) {
            if (!this.isTouch()) return;
            
            this.touchState = {
                startY: event.touches[0].clientY,
                startTime: Date.now(),
                index: index
            };
        },

        handleTouchMove(event, index) {
            if (!this.touchState || this.touchState.index !== index) return;
            
            const currentY = event.touches[0].clientY;
            const deltaY = currentY - this.touchState.startY;
            
            // Visual feedback for drag direction
            if (Math.abs(deltaY) > 20) {
                const direction = deltaY > 0 ? 'down' : 'up';
                this.touchState.direction = direction;
            }
        },

        handleTouchEnd(event, index) {
            if (!this.touchState || this.touchState.index !== index) return;
            
            const touchDuration = Date.now() - this.touchState.startTime;
            
            // If it's a quick touch and there's significant movement, treat as reorder
            if (touchDuration < 500 && this.touchState.direction) {
                if (this.touchState.direction === 'up') {
                    this.moveItemUp(index);
                } else {
                    this.moveItemDown(index);
                }
            }
            
            this.touchState = null;
        },

        touchState: null,

        // Mobile viewport management
        adjustForMobile() {
            if (this.isMobile()) {
                // Adjust step indicator for mobile
                this.mobileStepIndicator = true;
                
                // Ensure form fields are properly sized
                this.adjustFormFieldsForMobile();
            }
        },

        adjustFormFieldsForMobile() {
            this.$nextTick(() => {
                const inputs = document.querySelectorAll('.line-item-row input, .line-item-row textarea');
                inputs.forEach(input => {
                    input.style.fontSize = '16px'; // Prevents zoom on iOS
                });
            });
        },

        // Mobile-specific initialization
        initMobile() {
            if (this.isMobile()) {
                // Add mobile-specific event listeners
                window.addEventListener('orientationchange', () => {
                    setTimeout(() => this.adjustForMobile(), 100);
                });
                
                // Adjust layout immediately
                this.adjustForMobile();
            }
        },

        mobileStepIndicator: false,

        // Advanced Pricing Models - Phase 3
        pricingModels: {
            standard: 'Standard flat rate',
            tiered: 'Tiered volume pricing',
            usage: 'Usage-based pricing',
            time: 'Time-based pricing',
            value: 'Value-based pricing'
        },

        // Tiered pricing support
        tieredPricing: {
            enabled: false,
            tiers: []
        },

        // Usage-based pricing
        usagePricing: {
            enabled: false,
            baseRate: 0,
            usageRate: 0,
            minimumCharge: 0,
            usageUnits: 'hours'
        },

        // Time-based pricing
        timePricing: {
            enabled: false,
            regularRate: 0,
            overtimeRate: 0,
            regularHours: 40,
            rushMultiplier: 1.5
        },

        // Value-based pricing
        valuePricing: {
            enabled: false,
            baseValue: 0,
            successMultiplier: 1,
            riskAdjustment: 0
        },

        // Advanced pricing calculations
        calculateAdvancedPricing(item, index) {
            if (!item.pricingModel || item.pricingModel === 'standard') {
                // Standard calculation
                item.amount = (parseFloat(item.quantity) || 0) * (parseFloat(item.rate) || 0);
                return;
            }

            switch (item.pricingModel) {
                case 'tiered':
                    this.calculateTieredPricing(item);
                    break;
                case 'usage':
                    this.calculateUsagePricing(item);
                    break;
                case 'time':
                    this.calculateTimePricing(item);
                    break;
                case 'value':
                    this.calculateValuePricing(item);
                    break;
                default:
                    item.amount = (parseFloat(item.quantity) || 0) * (parseFloat(item.rate) || 0);
            }

            this.calculateTotals();
        },

        // Tiered pricing calculation
        calculateTieredPricing(item) {
            if (!item.tiers || item.tiers.length === 0) {
                item.amount = (parseFloat(item.quantity) || 0) * (parseFloat(item.rate) || 0);
                return;
            }

            const quantity = parseFloat(item.quantity) || 0;
            let total = 0;
            let remaining = quantity;

            // Sort tiers by min quantity
            const sortedTiers = [...item.tiers].sort((a, b) => a.minQuantity - b.minQuantity);

            for (let i = 0; i < sortedTiers.length && remaining > 0; i++) {
                const tier = sortedTiers[i];
                const tierMin = parseFloat(tier.minQuantity) || 0;
                const tierMax = parseFloat(tier.maxQuantity) || Infinity;
                const tierRate = parseFloat(tier.rate) || 0;

                if (quantity >= tierMin) {
                    const tierQuantity = Math.min(remaining, tierMax - tierMin + 1);
                    total += tierQuantity * tierRate;
                    remaining -= tierQuantity;
                }
            }

            item.amount = total;
            item.calculationDetails = `Tiered pricing applied across ${sortedTiers.length} tiers`;
        },

        // Usage-based pricing calculation
        calculateUsagePricing(item) {
            const baseRate = parseFloat(item.baseRate) || 0;
            const usageRate = parseFloat(item.usageRate) || 0;
            const usage = parseFloat(item.usage) || 0;
            const minimumCharge = parseFloat(item.minimumCharge) || 0;

            const calculated = baseRate + (usage * usageRate);
            item.amount = Math.max(calculated, minimumCharge);
            item.calculationDetails = `Base: ${this.formatCurrency(baseRate)} + Usage: ${usage} × ${this.formatCurrency(usageRate)}`;
        },

        // Time-based pricing calculation
        calculateTimePricing(item) {
            const regularRate = parseFloat(item.regularRate) || 0;
            const overtimeRate = parseFloat(item.overtimeRate) || regularRate * 1.5;
            const totalHours = parseFloat(item.quantity) || 0;
            const regularHours = Math.min(totalHours, parseFloat(item.regularHours) || 40);
            const overtimeHours = Math.max(0, totalHours - regularHours);
            
            const rushMultiplier = item.isRush ? (parseFloat(item.rushMultiplier) || 1.5) : 1;
            
            const regularAmount = regularHours * regularRate * rushMultiplier;
            const overtimeAmount = overtimeHours * overtimeRate * rushMultiplier;
            
            item.amount = regularAmount + overtimeAmount;
            item.calculationDetails = `Regular: ${regularHours}h × ${this.formatCurrency(regularRate)}${item.isRush ? ' × Rush' : ''}, OT: ${overtimeHours}h × ${this.formatCurrency(overtimeRate)}`;
        },

        // Value-based pricing calculation
        calculateValuePricing(item) {
            const baseValue = parseFloat(item.baseValue) || 0;
            const successMultiplier = parseFloat(item.successMultiplier) || 1;
            const riskAdjustment = parseFloat(item.riskAdjustment) || 0;
            
            item.amount = (baseValue * successMultiplier) + riskAdjustment;
            item.calculationDetails = `Value: ${this.formatCurrency(baseValue)} × Success: ${successMultiplier} + Risk: ${this.formatCurrency(riskAdjustment)}`;
        },

        // Add pricing tier
        addPricingTier(itemIndex) {
            if (!this.document.items[itemIndex].tiers) {
                this.document.items[itemIndex].tiers = [];
            }
            
            this.document.items[itemIndex].tiers.push({
                minQuantity: 1,
                maxQuantity: 10,
                rate: 0,
                description: ''
            });
        },

        // Remove pricing tier
        removePricingTier(itemIndex, tierIndex) {
            this.document.items[itemIndex].tiers.splice(tierIndex, 1);
            this.calculateAdvancedPricing(this.document.items[itemIndex], itemIndex);
        },

        // Pricing model templates
        applyPricingTemplate(itemIndex, template) {
            const item = this.document.items[itemIndex];
            
            switch (template) {
                case 'volume_discount':
                    item.pricingModel = 'tiered';
                    item.tiers = [
                        { minQuantity: 1, maxQuantity: 10, rate: item.rate || 100, description: '1-10 units' },
                        { minQuantity: 11, maxQuantity: 50, rate: (item.rate || 100) * 0.9, description: '11-50 units (10% off)' },
                        { minQuantity: 51, maxQuantity: Infinity, rate: (item.rate || 100) * 0.8, description: '51+ units (20% off)' }
                    ];
                    break;
                case 'saas_usage':
                    item.pricingModel = 'usage';
                    item.baseRate = 29;
                    item.usageRate = 0.10;
                    item.minimumCharge = 29;
                    item.usageUnits = 'API calls';
                    break;
                case 'consulting_time':
                    item.pricingModel = 'time';
                    item.regularRate = item.rate || 150;
                    item.overtimeRate = (item.rate || 150) * 1.5;
                    item.regularHours = 40;
                    item.rushMultiplier = 1.5;
                    break;
                case 'performance_based':
                    item.pricingModel = 'value';
                    item.baseValue = item.rate * item.quantity || 1000;
                    item.successMultiplier = 1.25;
                    item.riskAdjustment = 0;
                    break;
            }
            
            this.calculateAdvancedPricing(item, itemIndex);
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

        hasUnsavedChanges() {
            // Basic implementation - could be more sophisticated
            return this.lastSaved === null || (Date.now() - this.lastSaved.getTime()) > 60000;
        },

        clearErrors(fields = null) {
            if (fields) {
                fields.forEach(field => delete this.errors[field]);
            } else {
                this.errors = {};
            }
        },

        // Cleanup
        destroy() {
            if (this.autoSaveInterval) {
                clearInterval(this.autoSaveInterval);
            }
        }
    }));
});