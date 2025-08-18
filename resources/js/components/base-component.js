// Base Alpine.js component with common functionality
export default function baseComponent(config = {}) {
    return {
        // Common data properties
        loading: false,
        errors: {},
        success: false,
        
        // Pagination
        currentPage: 1,
        perPage: 25,
        total: 0,
        
        // Filtering and searching
        search: '',
        filters: {},
        sortBy: 'created_at',
        sortDirection: 'desc',
        
        // Selection
        selected: [],
        selectAll: false,
        
        // Modal state
        showModal: false,
        modalTitle: '',
        modalContent: '',
        
        // Initialize component
        init() {
            this.initializeData();
            this.setupEventListeners();
            if (config.init) {
                config.init.call(this);
            }
        },
        
        initializeData() {
            if (config.initialData) {
                Object.assign(this, config.initialData);
            }
        },
        
        setupEventListeners() {
            // Listen for global events
            document.addEventListener('refresh-data', () => {
                this.refreshData();
            });
            
            document.addEventListener('clear-selection', () => {
                this.clearSelection();
            });
        },
        
        // Loading state management
        setLoading(state) {
            this.loading = state;
        },
        
        // Error handling
        setError(field, message) {
            this.errors[field] = message;
        },
        
        clearErrors() {
            this.errors = {};
        },
        
        hasError(field) {
            return this.errors.hasOwnProperty(field);
        },
        
        getError(field) {
            return this.errors[field] || '';
        },
        
        // Success handling
        setSuccess(message) {
            this.success = message;
            setTimeout(() => {
                this.success = false;
            }, 5000);
        },
        
        // HTTP requests with CSRF and error handling
        async makeRequest(url, options = {}) {
            this.setLoading(true);
            this.clearErrors();
            
            const defaultOptions = {
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            };
            
            const mergedOptions = {
                ...defaultOptions,
                ...options,
                headers: {
                    ...defaultOptions.headers,
                    ...options.headers
                }
            };
            
            try {
                const response = await fetch(url, mergedOptions);
                const data = await response.json();
                
                if (!response.ok) {
                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            this.setError(field, data.errors[field][0]);
                        });
                    } else {
                        this.setError('general', data.message || 'An error occurred');
                    }
                    throw new Error(data.message || 'Request failed');
                }
                
                return data;
            } catch (error) {
                if (!this.hasError('general')) {
                    this.setError('general', error.message);
                }
                throw error;
            } finally {
                this.setLoading(false);
            }
        },
        
        // Data management
        async refreshData() {
            if (config.dataUrl) {
                try {
                    const data = await this.makeRequest(config.dataUrl);
                    this.handleDataResponse(data);
                } catch (error) {
                    console.error('Failed to refresh data:', error);
                }
            }
        },
        
        handleDataResponse(data) {
            if (config.handleData) {
                config.handleData.call(this, data);
            }
        },
        
        // Filtering and searching
        updateSearch() {
            this.currentPage = 1;
            this.applyFilters();
        },
        
        updateFilter(key, value) {
            this.filters[key] = value;
            this.currentPage = 1;
            this.applyFilters();
        },
        
        clearFilters() {
            this.search = '';
            this.filters = {};
            this.currentPage = 1;
            this.applyFilters();
        },
        
        applyFilters() {
            if (config.onFilter) {
                config.onFilter.call(this);
            } else {
                this.refreshData();
            }
        },
        
        // Sorting
        sort(field) {
            if (this.sortBy === field) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = field;
                this.sortDirection = 'asc';
            }
            this.applyFilters();
        },
        
        getSortIcon(field) {
            if (this.sortBy !== field) return '';
            return this.sortDirection === 'asc' ? '↑' : '↓';
        },
        
        // Selection management
        toggleSelection(id) {
            const index = this.selected.indexOf(id);
            if (index > -1) {
                this.selected.splice(index, 1);
            } else {
                this.selected.push(id);
            }
            this.updateSelectAll();
        },
        
        toggleSelectAll() {
            if (this.selectAll) {
                this.selected = [];
            } else {
                this.selected = this.getAllIds();
            }
            this.selectAll = !this.selectAll;
        },
        
        updateSelectAll() {
            const allIds = this.getAllIds();
            this.selectAll = allIds.length > 0 && allIds.every(id => this.selected.includes(id));
        },
        
        clearSelection() {
            this.selected = [];
            this.selectAll = false;
        },
        
        getAllIds() {
            return config.getAllIds ? config.getAllIds.call(this) : [];
        },
        
        isSelected(id) {
            return this.selected.includes(id);
        },
        
        getSelectedCount() {
            return this.selected.length;
        },
        
        // Modal management
        openModal(title, content) {
            this.modalTitle = title;
            this.modalContent = content;
            this.showModal = true;
        },
        
        closeModal() {
            this.showModal = false;
            this.modalTitle = '';
            this.modalContent = '';
        },
        
        // Pagination
        changePage(page) {
            this.currentPage = page;
            this.refreshData();
        },
        
        nextPage() {
            if (this.currentPage < this.getLastPage()) {
                this.changePage(this.currentPage + 1);
            }
        },
        
        previousPage() {
            if (this.currentPage > 1) {
                this.changePage(this.currentPage - 1);
            }
        },
        
        getLastPage() {
            return Math.ceil(this.total / this.perPage);
        },
        
        // Utility methods
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        formatDate(date) {
            return new Date(date).toLocaleDateString();
        },
        
        formatDateTime(date) {
            return new Date(date).toLocaleString();
        },
        
        formatCurrency(amount, currency = 'USD') {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            }).format(amount);
        },
        
        truncate(str, length = 50) {
            return str.length > length ? str.substring(0, length) + '...' : str;
        },
        
        // Event emission
        emit(event, data = {}) {
            document.dispatchEvent(new CustomEvent(event, { detail: data }));
        },
        
        // Merge with config overrides
        ...config
    };
}