/**
 * Real-Time PDF Preview Component
 * Provides live PDF preview updates as users modify quotes
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('pdfPreviewRealtime', (config = {}) => ({
        // Configuration
        updateDelay: config.updateDelay || 1000,
        enableLivePreview: config.enableLivePreview !== false,
        previewQuality: config.previewQuality || 'medium',
        
        // Preview state
        previewUrl: null,
        isGenerating: false,
        lastUpdateTime: null,
        updateTimer: null,
        
        // PDF generation queue
        generationQueue: [],
        isProcessingQueue: false,
        
        // Preview options
        previewOptions: {
            scale: 1.0,
            showGrid: false,
            showMargins: false,
            format: 'A4',
            orientation: 'portrait'
        },
        
        // Error handling
        errors: [],
        retryAttempts: 0,
        maxRetries: 3,
        
        init() {
            this.setupEventListeners();
            this.loadInitialPreview();
        },
        
        setupEventListeners() {
            // Listen for quote changes
            document.addEventListener('quote-updated', (e) => {
                this.schedulePreviewUpdate(e.detail);
            });
            
            document.addEventListener('quote-items-changed', (e) => {
                this.schedulePreviewUpdate({ items: e.detail.items });
            });
            
            document.addEventListener('quote-client-changed', (e) => {
                this.schedulePreviewUpdate({ client: e.detail.client });
            });
            
            // Listen for template changes
            document.addEventListener('template-changed', (e) => {
                this.schedulePreviewUpdate({ template: e.detail.template });
            });
        },
        
        schedulePreviewUpdate(changes) {
            if (!this.enableLivePreview) return;
            
            // Clear existing timer
            if (this.updateTimer) {
                clearTimeout(this.updateTimer);
            }
            
            // Schedule new update
            this.updateTimer = setTimeout(() => {
                this.updatePreview(changes);
            }, this.updateDelay);
        },
        
        async updatePreview(changes = {}) {
            if (this.isGenerating) {
                // Add to queue if already generating
                this.generationQueue.push(changes);
                return;
            }
            
            this.isGenerating = true;
            this.errors = [];
            
            try {
                const previewData = this.preparePreviewData(changes);
                const newPreviewUrl = await this.generatePreview(previewData);
                
                if (newPreviewUrl) {
                    this.previewUrl = newPreviewUrl;
                    this.lastUpdateTime = Date.now();
                    this.retryAttempts = 0;
                    
                    this.$dispatch('pdf-preview-updated', {
                        url: this.previewUrl,
                        timestamp: this.lastUpdateTime
                    });
                }
                
            } catch (error) {
                this.handlePreviewError(error, changes);
            } finally {
                this.isGenerating = false;
                this.processQueue();
            }
        },
        
        preparePreviewData(changes) {
            const quoteData = this.$store.quote ? {
                document: this.$store.quote.document,
                selectedItems: this.$store.quote.selectedItems,
                calculations: this.$store.quote.calculations
            } : {};
            
            return {
                ...quoteData,
                ...changes,
                preview_options: this.previewOptions,
                timestamp: Date.now()
            };
        },
        
        async generatePreview(data) {
            const response = await fetch('/api/quotes/preview/pdf', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) {
                throw new Error(`Preview generation failed: ${response.status}`);
            }
            
            const result = await response.json();
            return result.preview_url;
        },
        
        async loadInitialPreview() {
            if (!this.$store.quote?.document?.id) return;
            
            try {
                const response = await fetch(`/api/quotes/${this.$store.quote.document.id}/preview`);
                if (response.ok) {
                    const result = await response.json();
                    this.previewUrl = result.preview_url;
                    this.lastUpdateTime = Date.now();
                }
            } catch (error) {
                console.error('Failed to load initial preview:', error);
            }
        },
        
        processQueue() {
            if (this.generationQueue.length > 0 && !this.isProcessingQueue) {
                this.isProcessingQueue = true;
                
                // Get the latest changes from queue
                const latestChanges = this.generationQueue.pop();
                this.generationQueue = []; // Clear queue
                
                this.isProcessingQueue = false;
                this.updatePreview(latestChanges);
            }
        },
        
        handlePreviewError(error, changes) {
            console.error('PDF preview error:', error);
            
            this.errors.push({
                message: error.message,
                timestamp: Date.now(),
                changes: changes
            });
            
            // Retry logic
            if (this.retryAttempts < this.maxRetries) {
                this.retryAttempts++;
                
                setTimeout(() => {
                    this.updatePreview(changes);
                }, 2000 * this.retryAttempts); // Exponential backoff
            }
        },
        
        // Preview control methods
        zoomIn() {
            this.previewOptions.scale = Math.min(this.previewOptions.scale + 0.1, 2.0);
            this.updatePreview();
        },
        
        zoomOut() {
            this.previewOptions.scale = Math.max(this.previewOptions.scale - 0.1, 0.5);
            this.updatePreview();
        },
        
        resetZoom() {
            this.previewOptions.scale = 1.0;
            this.updatePreview();
        },
        
        toggleGrid() {
            this.previewOptions.showGrid = !this.previewOptions.showGrid;
            this.updatePreview();
        },
        
        toggleMargins() {
            this.previewOptions.showMargins = !this.previewOptions.showMargins;
            this.updatePreview();
        },
        
        changeFormat(format) {
            this.previewOptions.format = format;
            this.updatePreview();
        },
        
        changeOrientation(orientation) {
            this.previewOptions.orientation = orientation;
            this.updatePreview();
        },
        
        // Download methods
        async downloadPDF() {
            if (!this.previewUrl) return;
            
            try {
                const response = await fetch(this.previewUrl);
                const blob = await response.blob();
                
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `quote-${this.$store.quote?.document?.quote_number || 'preview'}.pdf`;
                a.click();
                
                window.URL.revokeObjectURL(url);
            } catch (error) {
                console.error('Download failed:', error);
            }
        },
        
        async emailPDF() {
            if (!this.previewUrl) return;
            
            this.$dispatch('open-email-modal', {
                attachmentUrl: this.previewUrl,
                quote: this.$store.quote?.document
            });
        },
        
        // Utility methods
        formatTimestamp(timestamp) {
            return new Intl.DateTimeFormat('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            }).format(new Date(timestamp));
        },
        
        // Computed properties
        get hasPreview() {
            return !!this.previewUrl;
        },
        
        get isLoading() {
            return this.isGenerating;
        },
        
        get hasErrors() {
            return this.errors.length > 0;
        },
        
        get lastUpdateText() {
            return this.lastUpdateTime 
                ? `Last updated: ${this.formatTimestamp(this.lastUpdateTime)}`
                : 'No preview available';
        },
        
        get zoomPercentage() {
            return Math.round(this.previewOptions.scale * 100);
        }
    }));
});