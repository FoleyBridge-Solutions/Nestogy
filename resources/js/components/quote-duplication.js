/**
 * Quote Duplication and Cloning Component
 * Handles intelligent quote duplication with customization options
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('quoteDuplication', (config = {}) => ({
        // Configuration
        enableSmartDuplication: config.enableSmartDuplication !== false,
        enableBulkDuplication: config.enableBulkDuplication !== false,
        maxDuplicates: config.maxDuplicates || 50,
        
        // Duplication state
        sourceQuote: null,
        duplicateOptions: {
            includeItems: true,
            includePricing: true,
            includeNotes: false,
            includeAttachments: false,
            updatePricing: true,
            newClient: null,
            newQuoteNumber: '',
            duplicateCount: 1,
            prefix: '',
            suffix: ''
        },
        
        // Bulk duplication
        bulkDuplication: {
            enabled: false,
            sourceQuotes: [],
            targetClients: [],
            options: {
                preserveClientMapping: true,
                updateQuoteNumbers: true,
                applyTemplate: null
            }
        },
        
        // Smart duplication features
        smartFeatures: {
            priceUpdates: true,
            clientSuggestions: true,
            templateMatching: true,
            autoExpiry: true
        },
        
        // UI state
        showDuplicationModal: false,
        showBulkModal: false,
        isProcessing: false,
        progress: {
            current: 0,
            total: 0,
            status: ''
        },
        
        // Results
        duplicatedQuotes: [],
        errors: [],
        
        init() {
            this.setupEventListeners();
        },
        
        setupEventListeners() {
            document.addEventListener('duplicate-quote', (e) => {
                this.initiateDuplication(e.detail.quote);
            });
            
            document.addEventListener('bulk-duplicate-quotes', (e) => {
                this.initiateBulkDuplication(e.detail.quotes);
            });
        },
        
        initiateDuplication(quote) {
            this.sourceQuote = quote;
            this.resetDuplicationOptions();
            this.showDuplicationModal = true;
            
            if (this.smartFeatures.clientSuggestions) {
                this.loadClientSuggestions();
            }
        },
        
        initiateBulkDuplication(quotes) {
            this.bulkDuplication.sourceQuotes = quotes;
            this.showBulkModal = true;
        },
        
        resetDuplicationOptions() {
            this.duplicateOptions = {
                includeItems: true,
                includePricing: true,
                includeNotes: false,
                includeAttachments: false,
                updatePricing: true,
                newClient: null,
                newQuoteNumber: '',
                duplicateCount: 1,
                prefix: '',
                suffix: ''
            };
        },
        
        async loadClientSuggestions() {
            if (!this.sourceQuote) return;
            
            try {
                const response = await fetch(`/api/quotes/${this.sourceQuote.id}/client-suggestions`);
                if (response.ok) {
                    this.clientSuggestions = await response.json();
                }
            } catch (error) {
                console.error('Failed to load client suggestions:', error);
            }
        },
        
        async duplicateQuote() {
            if (!this.sourceQuote || this.isProcessing) return;
            
            this.isProcessing = true;
            this.errors = [];
            this.duplicatedQuotes = [];
            
            try {
                const duplicateData = this.prepareDuplicationData();
                
                if (this.duplicateOptions.duplicateCount === 1) {
                    const result = await this.createSingleDuplicate(duplicateData);
                    if (result) {
                        this.duplicatedQuotes.push(result);
                    }
                } else {
                    await this.createMultipleDuplicates(duplicateData);
                }
                
                this.handleDuplicationSuccess();
                
            } catch (error) {
                this.handleDuplicationError(error);
            } finally {
                this.isProcessing = false;
            }
        },
        
        prepareDuplicationData() {
            const data = {
                source_quote_id: this.sourceQuote.id,
                options: { ...this.duplicateOptions },
                smart_features: this.smartFeatures
            };
            
            // Apply smart pricing updates
            if (this.duplicateOptions.updatePricing && this.smartFeatures.priceUpdates) {
                data.apply_current_pricing = true;
            }
            
            // Apply automatic expiry
            if (this.smartFeatures.autoExpiry) {
                data.auto_set_expiry = true;
                data.expiry_days = 30;
            }
            
            return data;
        },
        
        async createSingleDuplicate(data) {
            const response = await fetch('/api/quotes/duplicate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) {
                throw new Error(`Duplication failed: ${response.status}`);
            }
            
            return await response.json();
        },
        
        async createMultipleDuplicates(data) {
            this.progress.total = this.duplicateOptions.duplicateCount;
            this.progress.current = 0;
            
            for (let i = 0; i < this.duplicateOptions.duplicateCount; i++) {
                try {
                    this.progress.current = i + 1;
                    this.progress.status = `Creating duplicate ${i + 1} of ${this.duplicateOptions.duplicateCount}`;
                    
                    // Modify data for each duplicate
                    const duplicateData = {
                        ...data,
                        options: {
                            ...data.options,
                            newQuoteNumber: this.generateQuoteNumber(i + 1),
                            prefix: this.duplicateOptions.prefix,
                            suffix: this.duplicateOptions.suffix
                        }
                    };
                    
                    const result = await this.createSingleDuplicate(duplicateData);
                    if (result) {
                        this.duplicatedQuotes.push(result);
                    }
                    
                    // Small delay to prevent overwhelming the server
                    await new Promise(resolve => setTimeout(resolve, 100));
                    
                } catch (error) {
                    this.errors.push({
                        duplicate: i + 1,
                        error: error.message
                    });
                }
            }
        },
        
        generateQuoteNumber(index) {
            const base = this.sourceQuote.quote_number;
            const prefix = this.duplicateOptions.prefix;
            const suffix = this.duplicateOptions.suffix;
            
            let newNumber = base;
            
            if (prefix) {
                newNumber = `${prefix}-${newNumber}`;
            }
            
            if (suffix) {
                newNumber = `${newNumber}-${suffix}`;
            }
            
            if (this.duplicateOptions.duplicateCount > 1) {
                newNumber = `${newNumber}-${index}`;
            }
            
            return newNumber;
        },
        
        async performBulkDuplication() {
            if (!this.bulkDuplication.sourceQuotes.length || this.isProcessing) return;
            
            this.isProcessing = true;
            this.errors = [];
            this.duplicatedQuotes = [];
            
            this.progress.total = this.bulkDuplication.sourceQuotes.length;
            this.progress.current = 0;
            
            try {
                for (const quote of this.bulkDuplication.sourceQuotes) {
                    this.progress.current++;
                    this.progress.status = `Duplicating quote ${quote.quote_number}`;
                    
                    try {
                        const duplicateData = {
                            source_quote_id: quote.id,
                            options: this.bulkDuplication.options
                        };
                        
                        const result = await this.createSingleDuplicate(duplicateData);
                        if (result) {
                            this.duplicatedQuotes.push(result);
                        }
                        
                    } catch (error) {
                        this.errors.push({
                            quote: quote.quote_number,
                            error: error.message
                        });
                    }
                    
                    await new Promise(resolve => setTimeout(resolve, 200));
                }
                
                this.handleBulkDuplicationSuccess();
                
            } catch (error) {
                this.handleDuplicationError(error);
            } finally {
                this.isProcessing = false;
            }
        },
        
        // Template-based duplication
        async duplicateWithTemplate(templateId) {
            if (!this.sourceQuote) return;
            
            try {
                const response = await fetch('/api/quotes/duplicate-with-template', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        source_quote_id: this.sourceQuote.id,
                        template_id: templateId,
                        options: this.duplicateOptions
                    })
                });
                
                if (response.ok) {
                    const result = await response.json();
                    this.duplicatedQuotes.push(result);
                    this.handleDuplicationSuccess();
                }
                
            } catch (error) {
                this.handleDuplicationError(error);
            }
        },
        
        // Recurring quote creation
        async createRecurringQuotes(schedule) {
            if (!this.sourceQuote) return;
            
            try {
                const response = await fetch('/api/quotes/create-recurring', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        source_quote_id: this.sourceQuote.id,
                        schedule: schedule,
                        options: this.duplicateOptions
                    })
                });
                
                if (response.ok) {
                    const result = await response.json();
                    this.duplicatedQuotes = result.quotes;
                    this.handleDuplicationSuccess();
                }
                
            } catch (error) {
                this.handleDuplicationError(error);
            }
        },
        
        handleDuplicationSuccess() {
            this.showDuplicationModal = false;
            this.showBulkModal = false;
            
            this.$dispatch('quotes-duplicated', {
                duplicates: this.duplicatedQuotes,
                source: this.sourceQuote
            });
            
            this.showSuccessMessage();
        },
        
        handleBulkDuplicationSuccess() {
            this.showBulkModal = false;
            
            this.$dispatch('bulk-quotes-duplicated', {
                duplicates: this.duplicatedQuotes,
                sources: this.bulkDuplication.sourceQuotes,
                errors: this.errors
            });
            
            this.showBulkSuccessMessage();
        },
        
        handleDuplicationError(error) {
            console.error('Duplication error:', error);
            this.errors.push({
                general: error.message
            });
        },
        
        showSuccessMessage() {
            const count = this.duplicatedQuotes.length;
            const message = count === 1 
                ? 'Quote duplicated successfully'
                : `${count} quotes created successfully`;
            
            this.$dispatch('notification', {
                type: 'success',
                message: message
            });
        },
        
        showBulkSuccessMessage() {
            const successCount = this.duplicatedQuotes.length;
            const errorCount = this.errors.length;
            
            let message = `${successCount} quotes duplicated successfully`;
            if (errorCount > 0) {
                message += `, ${errorCount} failed`;
            }
            
            this.$dispatch('notification', {
                type: successCount > 0 ? 'success' : 'error',
                message: message
            });
        },
        
        closeDuplicationModal() {
            this.showDuplicationModal = false;
            this.sourceQuote = null;
            this.duplicatedQuotes = [];
            this.errors = [];
        },
        
        closeBulkModal() {
            this.showBulkModal = false;
            this.bulkDuplication.sourceQuotes = [];
            this.duplicatedQuotes = [];
            this.errors = [];
        },
        
        // Quick duplicate (single click)
        async quickDuplicate(quote) {
            this.sourceQuote = quote;
            this.resetDuplicationOptions();
            
            // Use default options for quick duplicate
            this.duplicateOptions.includeNotes = false;
            this.duplicateOptions.includeAttachments = false;
            
            await this.duplicateQuote();
        },
        
        // Computed properties
        get canDuplicate() {
            return this.sourceQuote && !this.isProcessing;
        },
        
        get canBulkDuplicate() {
            return this.bulkDuplication.sourceQuotes.length > 0 && !this.isProcessing;
        },
        
        get duplicateCountValid() {
            return this.duplicateOptions.duplicateCount >= 1 && 
                   this.duplicateOptions.duplicateCount <= this.maxDuplicates;
        },
        
        get progressPercentage() {
            return this.progress.total > 0 
                ? (this.progress.current / this.progress.total) * 100 
                : 0;
        },
        
        get hasErrors() {
            return this.errors.length > 0;
        },
        
        get hasSuccessfulDuplicates() {
            return this.duplicatedQuotes.length > 0;
        }
    }));
});