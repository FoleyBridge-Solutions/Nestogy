/**
 * Template Manager Component
 * Handles quote template operations including creation, loading, and management
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('templateManager', (config = {}) => ({
        // Configuration
        apiEndpoint: config.apiEndpoint || '/api/quote-templates',
        maxTemplates: config.maxTemplates || 50,
        enableSharing: config.enableSharing || false,
        
        // State
        loading: false,
        saving: false,
        showModal: false,
        showShareModal: false,
        
        // Templates data
        templates: [],
        favorites: [],
        recent: [],
        categories: [],
        
        // Current template being edited
        currentTemplate: {
            id: null,
            name: '',
            description: '',
            category_id: '',
            scope: '',
            note: '',
            terms_conditions: '',
            discount_type: 'fixed',
            discount_amount: 0,
            items: [],
            tags: [],
            is_public: false,
            is_favorite: false
        },
        
        // UI state
        activeTab: 'my-templates', // 'my-templates', 'favorites', 'public', 'recent'
        viewMode: 'grid', // 'grid', 'list'
        searchQuery: '',
        selectedCategory: '',
        sortBy: 'updated_at',
        sortOrder: 'desc',
        
        // Template creation state
        createMode: 'blank', // 'blank', 'from-current', 'duplicate'
        sourceTemplateId: null,
        
        // Sharing
        shareSettings: {
            is_public: false,
            allowed_users: [],
            allowed_companies: [],
            permissions: 'view' // 'view', 'edit', 'manage'
        },
        
        // Filters
        filters: {
            dateRange: 'all', // 'week', 'month', 'quarter', 'year', 'all'
            usage: 'all', // 'frequent', 'recent', 'unused', 'all'
            author: 'all', // 'me', 'team', 'company', 'all'
            status: 'active' // 'active', 'archived', 'all'
        },

        // Initialize component
        init() {
            this.loadTemplates();
            this.loadCategories();
            this.setupEventListeners();
            this.restoreUserPreferences();
        },

        // Setup event listeners
        setupEventListeners() {
            // Listen for template load requests
            document.addEventListener('load-template', (e) => {
                this.loadTemplate(e.detail.templateId);
            });

            // Listen for template save requests
            document.addEventListener('save-current-as-template', (e) => {
                this.saveCurrentQuoteAsTemplate(e.detail);
            });

            // Search debouncing
            this.$watch('searchQuery', (newQuery) => {
                clearTimeout(this._searchTimeout);
                this._searchTimeout = setTimeout(() => {
                    this.filterTemplates();
                }, 300);
            });

            // Auto-save preferences
            this.$watch('activeTab', () => this.saveUserPreferences());
            this.$watch('viewMode', () => this.saveUserPreferences());
            this.$watch('sortBy', () => this.saveUserPreferences());
        },

        // Load all templates (with caching)
        async loadTemplates() {
            try {
                this.loading = true;
                
                // Use cache manager if available
                if (this.$store.cache?.manager) {
                    const data = await this.$store.cache.manager.getOrFetch(
                        'templates:list:all',
                        async () => {
                            const response = await fetch(`${this.apiEndpoint}?include=favorites,recent,public`);
                            if (!response.ok) throw new Error('Failed to load templates');
                            return response.json();
                        },
                        { priority: 'high' }
                    );
                    
                    this.templates = data.templates || [];
                    this.favorites = data.favorites || [];
                    this.recent = data.recent || [];
                } else {
                    // Fallback to direct fetch
                    const response = await fetch(`${this.apiEndpoint}?include=favorites,recent,public`);
                    if (response.ok) {
                        const data = await response.json();
                        this.templates = data.templates || [];
                        this.favorites = data.favorites || [];
                        this.recent = data.recent || [];
                    } else {
                        throw new Error('Failed to load templates');
                    }
                }
                
                this.filterTemplates();
            } catch (error) {
                console.error('Failed to load templates:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: 'Failed to load templates'
                });
            } finally {
                this.loading = false;
            }
        },

        // Load categories for filtering (with caching)
        async loadCategories() {
            try {
                // Use cache manager if available
                if (this.$store.cache?.manager) {
                    this.categories = await this.$store.cache.manager.getCategories();
                } else {
                    // Fallback to direct fetch
                    const response = await fetch('/api/categories?type=template');
                    if (response.ok) {
                        const data = await response.json();
                        this.categories = data.data || data.categories || [];
                    }
                }
            } catch (error) {
                console.error('Failed to load categories:', error);
            }
        },

        // Filter and sort templates
        filterTemplates() {
            let filtered = [...this.templates];
            
            // Filter by active tab
            switch (this.activeTab) {
                case 'favorites':
                    filtered = this.favorites;
                    break;
                case 'recent':
                    filtered = this.recent;
                    break;
                case 'public':
                    filtered = filtered.filter(t => t.is_public);
                    break;
                case 'my-templates':
                default:
                    filtered = filtered.filter(t => t.created_by_me);
                    break;
            }
            
            // Search filter
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(t => 
                    t.name.toLowerCase().includes(query) ||
                    t.description?.toLowerCase().includes(query) ||
                    t.tags?.some(tag => tag.toLowerCase().includes(query))
                );
            }
            
            // Category filter
            if (this.selectedCategory) {
                filtered = filtered.filter(t => t.category_id === this.selectedCategory);
            }
            
            // Date range filter
            if (this.filters.dateRange !== 'all') {
                const now = new Date();
                const cutoffDate = new Date();
                
                switch (this.filters.dateRange) {
                    case 'week':
                        cutoffDate.setDate(now.getDate() - 7);
                        break;
                    case 'month':
                        cutoffDate.setMonth(now.getMonth() - 1);
                        break;
                    case 'quarter':
                        cutoffDate.setMonth(now.getMonth() - 3);
                        break;
                    case 'year':
                        cutoffDate.setFullYear(now.getFullYear() - 1);
                        break;
                }
                
                filtered = filtered.filter(t => new Date(t.updated_at) >= cutoffDate);
            }
            
            // Usage filter
            switch (this.filters.usage) {
                case 'frequent':
                    filtered = filtered.filter(t => (t.usage_count || 0) >= 5);
                    break;
                case 'recent':
                    filtered = filtered.filter(t => t.last_used_at && 
                        new Date(t.last_used_at) >= new Date(Date.now() - 7 * 24 * 60 * 60 * 1000));
                    break;
                case 'unused':
                    filtered = filtered.filter(t => !t.last_used_at || (t.usage_count || 0) === 0);
                    break;
            }
            
            // Status filter
            if (this.filters.status !== 'all') {
                filtered = filtered.filter(t => t.status === this.filters.status);
            }
            
            // Sort templates
            filtered.sort((a, b) => {
                let aValue = a[this.sortBy];
                let bValue = b[this.sortBy];
                
                // Handle different data types
                if (this.sortBy.includes('_at')) {
                    aValue = new Date(aValue || 0);
                    bValue = new Date(bValue || 0);
                } else if (typeof aValue === 'string') {
                    aValue = aValue.toLowerCase();
                    bValue = bValue.toLowerCase();
                }
                
                if (this.sortOrder === 'desc') {
                    return bValue > aValue ? 1 : -1;
                } else {
                    return aValue > bValue ? 1 : -1;
                }
            });
            
            this.filteredTemplates = filtered;
        },

        // Create new template
        createNewTemplate(mode = 'blank') {
            this.createMode = mode;
            this.resetCurrentTemplate();
            
            if (mode === 'from-current') {
                this.loadCurrentQuoteData();
            }
            
            this.showModal = true;
        },

        // Duplicate existing template
        duplicateTemplate(templateId) {
            const template = this.templates.find(t => t.id === templateId);
            if (template) {
                this.currentTemplate = {
                    ...template,
                    id: null,
                    name: `${template.name} (Copy)`,
                    is_favorite: false,
                    created_at: null,
                    updated_at: null
                };
                this.showModal = true;
            }
        },

        // Edit existing template
        editTemplate(templateId) {
            const template = this.templates.find(t => t.id === templateId);
            if (template) {
                this.currentTemplate = { ...template };
                this.showModal = true;
            }
        },

        // Load current quote data into template
        loadCurrentQuoteData() {
            const quote = this.$store.quote;
            
            this.currentTemplate.scope = quote.document.scope || '';
            this.currentTemplate.note = quote.document.note || '';
            this.currentTemplate.terms_conditions = quote.document.terms_conditions || '';
            this.currentTemplate.discount_type = quote.document.discount_type || 'fixed';
            this.currentTemplate.discount_amount = quote.document.discount_amount || 0;
            
            // Copy items
            this.currentTemplate.items = quote.selectedItems.map(item => ({
                name: item.name,
                description: item.description,
                quantity: item.quantity,
                unit_price: item.unit_price,
                tax_rate: item.tax_rate || 0,
                category: item.category,
                type: item.type
            }));
        },

        // Save template
        async saveTemplate() {
            if (!this.validateTemplate()) return;
            
            try {
                this.saving = true;
                
                const payload = {
                    ...this.currentTemplate,
                    tags: Array.isArray(this.currentTemplate.tags) 
                        ? this.currentTemplate.tags 
                        : this.currentTemplate.tags.split(',').map(t => t.trim()).filter(t => t)
                };
                
                const url = this.currentTemplate.id 
                    ? `${this.apiEndpoint}/${this.currentTemplate.id}`
                    : this.apiEndpoint;
                
                const method = this.currentTemplate.id ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                });
                
                if (response.ok) {
                    const data = await response.json();
                    
                    // Update local templates array
                    if (this.currentTemplate.id) {
                        const index = this.templates.findIndex(t => t.id === this.currentTemplate.id);
                        if (index > -1) {
                            this.templates[index] = data.template;
                        }
                    } else {
                        this.templates.unshift(data.template);
                    }
                    
                    this.filterTemplates();
                    this.closeModal();
                    
                    this.$dispatch('notification', {
                        type: 'success',
                        message: `Template "${this.currentTemplate.name}" saved successfully`
                    });
                } else {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Failed to save template');
                }
            } catch (error) {
                console.error('Failed to save template:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: error.message || 'Failed to save template'
                });
            } finally {
                this.saving = false;
            }
        },

        // Validate template before saving
        validateTemplate() {
            if (!this.currentTemplate.name.trim()) {
                this.$dispatch('notification', {
                    type: 'error',
                    message: 'Template name is required'
                });
                return false;
            }
            
            if (this.currentTemplate.name.length > 100) {
                this.$dispatch('notification', {
                    type: 'error',
                    message: 'Template name must be 100 characters or less'
                });
                return false;
            }
            
            return true;
        },

        // Load template into current quote (with caching)
        async loadTemplate(templateId) {
            try {
                this.loading = true;
                
                let template;
                
                // Use cache manager if available
                if (this.$store.cache?.manager) {
                    template = await this.$store.cache.manager.getTemplate(templateId);
                } else {
                    // Fallback to direct fetch
                    const response = await fetch(`${this.apiEndpoint}/${templateId}`);
                    if (response.ok) {
                        const data = await response.json();
                        template = data.template || data;
                    } else {
                        throw new Error('Failed to load template');
                    }
                }
                
                // Load template into quote store
                this.$store.quote.loadTemplate(template);
                
                // Track usage
                this.trackTemplateUsage(templateId);
                
                // Add to recent templates
                this.addToRecent(template);
                
                // Invalidate templates cache to reflect usage updates
                if (this.$store.cache?.manager) {
                    this.$store.cache.manager.invalidate('templates:*');
                }
                
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

        // Toggle template favorite status
        async toggleFavorite(templateId) {
            try {
                const response = await fetch(`${this.apiEndpoint}/${templateId}/favorite`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    
                    // Update template in arrays
                    const template = this.templates.find(t => t.id === templateId);
                    if (template) {
                        template.is_favorite = data.is_favorite;
                    }
                    
                    // Update favorites array
                    if (data.is_favorite) {
                        if (!this.favorites.find(t => t.id === templateId)) {
                            this.favorites.push(template);
                        }
                    } else {
                        this.favorites = this.favorites.filter(t => t.id !== templateId);
                    }
                    
                    this.filterTemplates();
                } else {
                    throw new Error('Failed to toggle favorite');
                }
            } catch (error) {
                console.error('Failed to toggle favorite:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: 'Failed to update favorite status'
                });
            }
        },

        // Delete template
        async deleteTemplate(templateId) {
            if (!confirm('Are you sure you want to delete this template? This action cannot be undone.')) {
                return;
            }
            
            try {
                const response = await fetch(`${this.apiEndpoint}/${templateId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                if (response.ok) {
                    // Remove from all arrays
                    this.templates = this.templates.filter(t => t.id !== templateId);
                    this.favorites = this.favorites.filter(t => t.id !== templateId);
                    this.recent = this.recent.filter(t => t.id !== templateId);
                    
                    this.filterTemplates();
                    
                    this.$dispatch('notification', {
                        type: 'success',
                        message: 'Template deleted successfully'
                    });
                } else {
                    throw new Error('Failed to delete template');
                }
            } catch (error) {
                console.error('Failed to delete template:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: 'Failed to delete template'
                });
            }
        },

        // Share template
        shareTemplate(templateId) {
            const template = this.templates.find(t => t.id === templateId);
            if (template) {
                this.currentTemplate = template;
                this.shareSettings = {
                    is_public: template.is_public || false,
                    allowed_users: template.allowed_users || [],
                    allowed_companies: template.allowed_companies || [],
                    permissions: template.permissions || 'view'
                };
                this.showShareModal = true;
            }
        },

        // Save sharing settings
        async saveSharingSettings() {
            try {
                const response = await fetch(`${this.apiEndpoint}/${this.currentTemplate.id}/sharing`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.shareSettings)
                });
                
                if (response.ok) {
                    const template = this.templates.find(t => t.id === this.currentTemplate.id);
                    if (template) {
                        Object.assign(template, this.shareSettings);
                    }
                    
                    this.showShareModal = false;
                    
                    this.$dispatch('notification', {
                        type: 'success',
                        message: 'Sharing settings updated successfully'
                    });
                } else {
                    throw new Error('Failed to update sharing settings');
                }
            } catch (error) {
                console.error('Failed to update sharing settings:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: 'Failed to update sharing settings'
                });
            }
        },

        // Track template usage
        async trackTemplateUsage(templateId) {
            try {
                await fetch(`${this.apiEndpoint}/${templateId}/usage`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
            } catch (error) {
                // Silent fail for usage tracking
                console.warn('Failed to track template usage:', error);
            }
        },

        // Add template to recent list
        addToRecent(template) {
            // Remove if already exists
            this.recent = this.recent.filter(t => t.id !== template.id);
            
            // Add to beginning
            this.recent.unshift(template);
            
            // Keep only last 10
            if (this.recent.length > 10) {
                this.recent = this.recent.slice(0, 10);
            }
        },

        // Export templates
        async exportTemplates(templateIds = null) {
            try {
                const ids = templateIds || this.templates.map(t => t.id);
                const params = new URLSearchParams();
                ids.forEach(id => params.append('ids[]', id));
                
                const response = await fetch(`${this.apiEndpoint}/export?${params.toString()}`);
                if (response.ok) {
                    const blob = await response.blob();
                    const url = URL.createObjectURL(blob);
                    
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `quote-templates-${Date.now()}.json`;
                    a.click();
                    
                    URL.revokeObjectURL(url);
                } else {
                    throw new Error('Failed to export templates');
                }
            } catch (error) {
                console.error('Failed to export templates:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: 'Failed to export templates'
                });
            }
        },

        // Import templates
        async importTemplates(file) {
            try {
                const formData = new FormData();
                formData.append('file', file);
                
                const response = await fetch(`${this.apiEndpoint}/import`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });
                
                if (response.ok) {
                    const data = await response.json();
                    
                    // Reload templates
                    await this.loadTemplates();
                    
                    this.$dispatch('notification', {
                        type: 'success',
                        message: `Imported ${data.count} templates successfully`
                    });
                } else {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Failed to import templates');
                }
            } catch (error) {
                console.error('Failed to import templates:', error);
                this.$dispatch('notification', {
                    type: 'error',
                    message: error.message || 'Failed to import templates'
                });
            }
        },

        // Reset current template
        resetCurrentTemplate() {
            this.currentTemplate = {
                id: null,
                name: '',
                description: '',
                category_id: '',
                scope: '',
                note: '',
                terms_conditions: '',
                discount_type: 'fixed',
                discount_amount: 0,
                items: [],
                tags: [],
                is_public: false,
                is_favorite: false
            };
        },

        // Close modal
        closeModal() {
            this.showModal = false;
            this.showShareModal = false;
            this.resetCurrentTemplate();
        },

        // User preferences
        saveUserPreferences() {
            const preferences = {
                activeTab: this.activeTab,
                viewMode: this.viewMode,
                sortBy: this.sortBy,
                sortOrder: this.sortOrder
            };
            
            localStorage.setItem('template-manager-preferences', JSON.stringify(preferences));
        },

        restoreUserPreferences() {
            try {
                const stored = localStorage.getItem('template-manager-preferences');
                if (stored) {
                    const preferences = JSON.parse(stored);
                    Object.assign(this, preferences);
                }
            } catch (error) {
                console.warn('Failed to restore user preferences:', error);
            }
        },

        // Utility methods
        formatDate(dateString) {
            return new Intl.DateTimeFormat('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            }).format(new Date(dateString));
        },

        formatRelativeTime(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);
            
            if (diffInSeconds < 60) return 'Just now';
            if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minutes ago`;
            if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`;
            if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)} days ago`;
            
            return this.formatDate(dateString);
        },

        getCategoryName(categoryId) {
            const category = this.categories.find(c => c.id === categoryId);
            return category ? category.name : 'Uncategorized';
        },

        getTemplatePreview(template) {
            const itemCount = template.items?.length || 0;
            const hasDiscount = template.discount_amount > 0;
            
            return {
                itemCount,
                hasDiscount,
                summary: `${itemCount} items${hasDiscount ? ', with discount' : ''}`
            };
        },

        // Computed properties
        get filteredTemplates() {
            return this._filteredTemplates || [];
        },

        set filteredTemplates(value) {
            this._filteredTemplates = value;
        },

        get hasFilters() {
            return this.searchQuery || 
                   this.selectedCategory || 
                   this.filters.dateRange !== 'all' ||
                   this.filters.usage !== 'all' ||
                   this.filters.author !== 'all' ||
                   this.filters.status !== 'active';
        },

        get templateStats() {
            return {
                total: this.templates.length,
                favorites: this.favorites.length,
                public: this.templates.filter(t => t.is_public).length,
                recent: this.recent.length
            };
        }
    }));
});