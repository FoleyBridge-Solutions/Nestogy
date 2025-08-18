/**
 * PDF Generator Component
 * Handles PDF generation, preview, and management for quotes
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('pdfGenerator', (config = {}) => ({
        // Configuration
        apiEndpoint: config.apiEndpoint || '/api/quotes',
        previewEndpoint: config.previewEndpoint || '/api/pdf/preview',
        templatesEndpoint: config.templatesEndpoint || '/api/pdf/templates',
        
        // State
        generating: false,
        previewing: false,
        showPreview: false,
        showTemplateModal: false,
        
        // PDF data
        currentPdf: {
            url: null,
            downloadUrl: null,
            filename: null,
            size: 0,
            pages: 0,
            createdAt: null
        },
        
        // Preview settings
        preview: {
            scale: 1.0,
            page: 1,
            totalPages: 1,
            rotation: 0,
            quality: 'high' // 'low', 'medium', 'high'
        },
        
        // PDF templates
        templates: [],
        selectedTemplate: null,
        customTemplate: {
            id: null,
            name: '',
            layout: 'standard', // 'standard', 'compact', 'detailed'
            colors: {
                primary: '#007bff',
                secondary: '#6c757d',
                accent: '#28a745'
            },
            branding: {
                logo: null,
                companyName: '',
                showWatermark: false,
                watermarkText: '',
                footerText: ''
            },
            sections: {
                showHeader: true,
                showItemDetails: true,
                showPricing: true,
                showTerms: true,
                showFooter: true,
                showTaxBreakdown: true,
                showDiscountDetails: true
            },
            formatting: {
                fontSize: 12,
                lineHeight: 1.5,
                margins: {
                    top: 50,
                    right: 50,
                    bottom: 50,
                    left: 50
                },
                pageSize: 'A4', // 'A4', 'Letter', 'Legal'
                orientation: 'portrait' // 'portrait', 'landscape'
            }
        },
        
        // Email settings
        email: {
            showModal: false,
            sending: false,
            recipients: [],
            subject: '',
            message: '',
            ccSelf: true,
            attachOriginal: true,
            sendCopy: false
        },
        
        // Digital signature
        signature: {
            enabled: false,
            showModal: false,
            provider: 'docusign', // 'docusign', 'hellosign', 'adobe'
            signers: [],
            template: null,
            expiresIn: 30 // days
        },
        
        // History
        generationHistory: [],
        
        // Error handling
        errors: {},

        // Initialize component
        init() {
            this.loadTemplates();
            this.loadGenerationHistory();
            this.setupEventListeners();
        },

        // Setup event listeners
        setupEventListeners() {
            // Listen for PDF generation requests
            document.addEventListener('generate-pdf', (e) => {
                this.generatePDF(e.detail);
            });

            // Listen for PDF preview requests
            document.addEventListener('preview-pdf', (e) => {
                this.previewPDF(e.detail);
            });

            // Listen for quote changes to invalidate PDF cache
            this.$watch('$store.quote.pricing.total', () => {
                this.invalidatePdfCache();
            });

            this.$watch('$store.quote.selectedItems', () => {
                this.invalidatePdfCache();
            }, { deep: true });
        },

        // Load PDF templates
        async loadTemplates() {
            try {
                const response = await fetch(this.templatesEndpoint);
                if (response.ok) {
                    const data = await response.json();
                    this.templates = data.templates || [];
                    
                    // Set default template if none selected
                    if (!this.selectedTemplate && this.templates.length > 0) {
                        this.selectedTemplate = this.templates.find(t => t.is_default) || this.templates[0];
                    }
                } else {
                    throw new Error('Failed to load PDF templates');
                }
            } catch (error) {
                console.error('Failed to load PDF templates:', error);
            }
        },

        // Load generation history
        async loadGenerationHistory() {
            try {
                const quoteId = this.$store.quote.quoteId;
                if (!quoteId) return;

                const response = await fetch(`${this.apiEndpoint}/${quoteId}/pdf-history`);
                if (response.ok) {
                    const data = await response.json();
                    this.generationHistory = data.history || [];
                }
            } catch (error) {
                console.error('Failed to load PDF history:', error);
            }
        },

        // Generate PDF
        async generatePDF(options = {}) {
            if (this.generating) return;

            try {
                this.generating = true;
                this.errors = {};

                // Validate quote data
                if (!this.validateQuoteForPDF()) {
                    return;
                }

                const payload = {
                    quote_data: {
                        document: this.$store.quote.document,
                        items: this.$store.quote.selectedItems,
                        pricing: this.$store.quote.pricing,
                        billing_config: this.$store.quote.billingConfig
                    },
                    template_id: this.selectedTemplate?.id,
                    options: {
                        quality: this.preview.quality,
                        format: options.format || 'pdf',
                        include_attachments: options.includeAttachments || false,
                        watermark: options.watermark || false,
                        ...options
                    }
                };

                const response = await fetch(`${this.apiEndpoint}/generate-pdf`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                });

                if (response.ok) {
                    const data = await response.json();
                    
                    this.currentPdf = {
                        url: data.preview_url,
                        downloadUrl: data.download_url,
                        filename: data.filename,
                        size: data.size,
                        pages: data.pages,
                        createdAt: new Date()
                    };

                    // Add to history
                    this.addToHistory({
                        ...this.currentPdf,
                        template: this.selectedTemplate?.name,
                        options: payload.options
                    });

                    this.$dispatch('notification', {
                        type: 'success',
                        message: 'PDF generated successfully'
                    });

                    return this.currentPdf;
                } else {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Failed to generate PDF');
                }
            } catch (error) {
                console.error('PDF generation failed:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: error.message || 'Failed to generate PDF'
                });
                throw error;
            } finally {
                this.generating = false;
            }
        },

        // Preview PDF
        async previewPDF(options = {}) {
            try {
                this.previewing = true;

                // Generate PDF first if not exists or outdated
                if (!this.currentPdf.url || this.isPdfOutdated()) {
                    await this.generatePDF({ ...options, preview: true });
                }

                this.showPreview = true;
                this.preview.page = 1;
                this.preview.scale = 1.0;
                this.preview.rotation = 0;
                
            } catch (error) {
                console.error('PDF preview failed:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: 'Failed to preview PDF'
                });
            } finally {
                this.previewing = false;
            }
        },

        // Download PDF
        async downloadPDF(filename = null) {
            try {
                if (!this.currentPdf.downloadUrl) {
                    await this.generatePDF();
                }

                // Create download link
                const link = document.createElement('a');
                link.href = this.currentPdf.downloadUrl;
                link.download = filename || this.currentPdf.filename || 'quote.pdf';
                link.style.display = 'none';
                
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                this.$dispatch('notification', {
                    type: 'success',
                    message: 'PDF download started'
                });
            } catch (error) {
                console.error('PDF download failed:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: 'Failed to download PDF'
                });
            }
        },

        // Email PDF
        async emailPDF() {
            if (this.email.sending) return;

            try {
                this.email.sending = true;

                // Validate email data
                if (!this.validateEmailData()) {
                    return;
                }

                // Generate PDF if needed
                if (!this.currentPdf.downloadUrl) {
                    await this.generatePDF();
                }

                const payload = {
                    pdf_url: this.currentPdf.downloadUrl,
                    recipients: this.email.recipients,
                    subject: this.email.subject,
                    message: this.email.message,
                    cc_self: this.email.ccSelf,
                    attach_original: this.email.attachOriginal,
                    send_copy: this.email.sendCopy
                };

                const response = await fetch(`${this.apiEndpoint}/email-pdf`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                });

                if (response.ok) {
                    this.email.showModal = false;
                    this.resetEmailForm();
                    
                    this.$dispatch('notification', {
                        type: 'success',
                        message: 'PDF sent successfully'
                    });
                } else {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Failed to send PDF');
                }
            } catch (error) {
                console.error('Email PDF failed:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: error.message || 'Failed to send PDF'
                });
            } finally {
                this.email.sending = false;
            }
        },

        // Digital signature
        async requestSignature() {
            if (!this.signature.enabled) return;

            try {
                // Generate PDF if needed
                if (!this.currentPdf.downloadUrl) {
                    await this.generatePDF();
                }

                const payload = {
                    pdf_url: this.currentPdf.downloadUrl,
                    provider: this.signature.provider,
                    signers: this.signature.signers,
                    template_id: this.signature.template,
                    expires_in: this.signature.expiresIn
                };

                const response = await fetch(`${this.apiEndpoint}/request-signature`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                });

                if (response.ok) {
                    const data = await response.json();
                    
                    this.$dispatch('notification', {
                        type: 'success',
                        message: 'Signature request sent successfully'
                    });

                    // Optionally redirect to signature provider
                    if (data.redirect_url) {
                        window.open(data.redirect_url, '_blank');
                    }
                } else {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Failed to request signature');
                }
            } catch (error) {
                console.error('Signature request failed:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: error.message || 'Failed to request signature'
                });
            }
        },

        // Template management
        selectTemplate(template) {
            this.selectedTemplate = template;
            this.invalidatePdfCache();
        },

        async saveCustomTemplate() {
            try {
                const payload = {
                    ...this.customTemplate,
                    type: 'quote'
                };

                const response = await fetch(this.templatesEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                });

                if (response.ok) {
                    const data = await response.json();
                    this.templates.push(data.template);
                    this.selectedTemplate = data.template;
                    this.showTemplateModal = false;
                    
                    this.$dispatch('notification', {
                        type: 'success',
                        message: 'Template saved successfully'
                    });
                } else {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Failed to save template');
                }
            } catch (error) {
                console.error('Template save failed:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: error.message || 'Failed to save template'
                });
            }
        },

        // Preview controls
        zoomIn() {
            this.preview.scale = Math.min(this.preview.scale + 0.25, 3.0);
        },

        zoomOut() {
            this.preview.scale = Math.max(this.preview.scale - 0.25, 0.25);
        },

        resetZoom() {
            this.preview.scale = 1.0;
        },

        rotatePDF() {
            this.preview.rotation = (this.preview.rotation + 90) % 360;
        },

        nextPage() {
            if (this.preview.page < this.preview.totalPages) {
                this.preview.page++;
            }
        },

        previousPage() {
            if (this.preview.page > 1) {
                this.preview.page--;
            }
        },

        goToPage(page) {
            if (page >= 1 && page <= this.preview.totalPages) {
                this.preview.page = page;
            }
        },

        // Validation
        validateQuoteForPDF() {
            const quote = this.$store.quote;
            
            if (!quote.document.client_id) {
                this.setError('client', 'Client is required for PDF generation');
                return false;
            }

            if (quote.selectedItems.length === 0) {
                this.setError('items', 'At least one item is required for PDF generation');
                return false;
            }

            return true;
        },

        validateEmailData() {
            if (this.email.recipients.length === 0) {
                this.setError('email_recipients', 'At least one recipient is required');
                return false;
            }

            if (!this.email.subject.trim()) {
                this.setError('email_subject', 'Subject is required');
                return false;
            }

            // Validate email addresses
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            for (const recipient of this.email.recipients) {
                if (!emailRegex.test(recipient)) {
                    this.setError('email_recipients', `Invalid email address: ${recipient}`);
                    return false;
                }
            }

            return true;
        },

        // Utility methods
        isPdfOutdated() {
            if (!this.currentPdf.createdAt) return true;
            
            // PDF is outdated if it's older than 5 minutes
            const fiveMinutes = 5 * 60 * 1000;
            return (Date.now() - this.currentPdf.createdAt.getTime()) > fiveMinutes;
        },

        invalidatePdfCache() {
            this.currentPdf = {
                url: null,
                downloadUrl: null,
                filename: null,
                size: 0,
                pages: 0,
                createdAt: null
            };
        },

        addToHistory(pdfData) {
            this.generationHistory.unshift({
                ...pdfData,
                id: Date.now(),
                timestamp: new Date()
            });

            // Keep only last 20 entries
            if (this.generationHistory.length > 20) {
                this.generationHistory = this.generationHistory.slice(0, 20);
            }
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        formatTimestamp(date) {
            return new Intl.DateTimeFormat('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }).format(new Date(date));
        },

        setError(field, message) {
            this.errors[field] = message;
        },

        clearError(field) {
            delete this.errors[field];
        },

        clearAllErrors() {
            this.errors = {};
        },

        // Email form management
        addRecipient(email) {
            if (email && !this.email.recipients.includes(email)) {
                this.email.recipients.push(email);
            }
        },

        removeRecipient(email) {
            const index = this.email.recipients.indexOf(email);
            if (index > -1) {
                this.email.recipients.splice(index, 1);
            }
        },

        resetEmailForm() {
            this.email.recipients = [];
            this.email.subject = '';
            this.email.message = '';
            this.email.ccSelf = true;
            this.email.attachOriginal = true;
            this.email.sendCopy = false;
        },

        // Modal management
        closePreview() {
            this.showPreview = false;
        },

        openEmailModal() {
            // Pre-populate with client email if available
            const clientId = this.$store.quote.document.client_id;
            if (clientId) {
                // This would typically come from the client data
                // this.email.recipients = [client.email];
            }
            
            // Pre-populate subject
            this.email.subject = `Quote from ${document.querySelector('meta[name="app-name"]')?.content || 'Company'}`;
            
            this.email.showModal = true;
        },

        closeEmailModal() {
            this.email.showModal = false;
            this.resetEmailForm();
        },

        openTemplateModal() {
            this.showTemplateModal = true;
        },

        closeTemplateModal() {
            this.showTemplateModal = false;
        },

        // Computed properties
        get hasCurrentPdf() {
            return this.currentPdf.url !== null;
        },

        get pdfPreviewUrl() {
            return this.currentPdf.url;
        },

        get canEmail() {
            return this.hasCurrentPdf || !this.generating;
        },

        get canDownload() {
            return this.hasCurrentPdf || !this.generating;
        },

        get previewScale() {
            return Math.round(this.preview.scale * 100);
        }
    }));
});