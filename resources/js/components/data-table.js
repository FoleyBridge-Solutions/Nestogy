// Reusable DataTable Alpine.js component
import baseComponent from './base-component.js';

export default function dataTable(config = {}) {
    return baseComponent({
        // Data table specific properties
        items: [],
        columns: [],
        
        // DataTable specific configuration
        serverSide: true,
        searchDelay: 500,
        
        init() {
            this.columns = config.columns || [];
            this.setupSearch();
            this.loadData();
        },
        
        setupSearch() {
            // Debounced search
            this.debouncedSearch = this.debounce(() => {
                this.updateSearch();
            }, this.searchDelay);
        },
        
        async loadData() {
            if (!config.dataUrl) return;
            
            const params = new URLSearchParams({
                page: this.currentPage,
                per_page: this.perPage,
                search: this.search,
                sort: this.sortBy,
                direction: this.sortDirection,
                ...this.filters
            });
            
            try {
                const response = await this.makeRequest(`${config.dataUrl}?${params}`);
                this.handleDataResponse(response);
            } catch (error) {
                console.error('Failed to load data:', error);
            }
        },
        
        handleDataResponse(data) {
            if (data.data) {
                // Laravel pagination response
                this.items = data.data;
                this.total = data.total;
                this.currentPage = data.current_page;
                this.perPage = data.per_page;
            } else {
                // Simple array response
                this.items = data;
            }
            this.updateSelectAll();
        },
        
        getAllIds() {
            return this.items.map(item => item.id);
        },
        
        // Search with debouncing
        onSearchInput() {
            this.debouncedSearch();
        },
        
        // Column helpers
        getColumnValue(item, column) {
            if (column.accessor) {
                return column.accessor(item);
            }
            
            const keys = column.key.split('.');
            let value = item;
            for (const key of keys) {
                value = value?.[key];
            }
            return value;
        },
        
        renderColumnValue(item, column) {
            const value = this.getColumnValue(item, column);
            
            if (column.render) {
                return column.render(value, item);
            }
            
            if (column.type === 'date') {
                return value ? this.formatDate(value) : '';
            }
            
            if (column.type === 'datetime') {
                return value ? this.formatDateTime(value) : '';
            }
            
            if (column.type === 'currency') {
                return value ? this.formatCurrency(value, column.currency) : '';
            }
            
            if (column.type === 'boolean') {
                return value ? 'Yes' : 'No';
            }
            
            if (column.type === 'truncate') {
                return this.truncate(value, column.length || 50);
            }
            
            return value || '';
        },
        
        // Actions
        async performAction(action, item = null) {
            if (config.actions && config.actions[action]) {
                try {
                    await config.actions[action].call(this, item);
                } catch (error) {
                    console.error(`Action ${action} failed:`, error);
                }
            }
        },
        
        async bulkAction(action) {
            if (this.selected.length === 0) {
                this.setError('selection', 'Please select items first');
                return;
            }
            
            if (config.bulkActions && config.bulkActions[action]) {
                try {
                    await config.bulkActions[action].call(this, this.selected);
                    this.clearSelection();
                    this.refreshData();
                } catch (error) {
                    console.error(`Bulk action ${action} failed:`, error);
                }
            }
        },
        
        // Export functionality
        async exportData(format = 'csv') {
            const params = new URLSearchParams({
                search: this.search,
                format: format,
                ...this.filters
            });
            
            try {
                const response = await fetch(`${config.exportUrl}?${params}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    }
                });
                
                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `export-${new Date().toISOString().split('T')[0]}.${format}`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                }
            } catch (error) {
                this.setError('export', 'Export failed');
            }
        },
        
        // Override base methods
        applyFilters() {
            this.loadData();
        },
        
        refreshData() {
            this.loadData();
        },
        
        changePage(page) {
            this.currentPage = page;
            this.loadData();
        },
        
        // Table styling helpers
        getRowClass(item) {
            let classes = [];
            
            if (this.isSelected(item.id)) {
                classes.push('bg-blue-50');
            }
            
            if (config.rowClass) {
                classes.push(config.rowClass(item));
            }
            
            return classes.join(' ');
        },
        
        getCellClass(item, column) {
            let classes = ['px-6', 'py-4', 'whitespace-nowrap'];
            
            if (column.class) {
                classes.push(column.class);
            }
            
            if (column.align) {
                classes.push(`text-${column.align}`);
            }
            
            return classes.join(' ');
        },
        
        // Merge with config
        ...config
    });
}

// Usage example:
/*
Alpine.data('assetsTable', () => dataTable({
    dataUrl: '/api/assets',
    exportUrl: '/api/assets/export',
    columns: [
        { key: 'name', label: 'Name', sortable: true },
        { key: 'type', label: 'Type', sortable: true },
        { key: 'status', label: 'Status', sortable: true },
        { key: 'client.name', label: 'Client', sortable: false },
        { key: 'created_at', label: 'Created', type: 'date', sortable: true }
    ],
    actions: {
        edit: async function(item) {
            window.location.href = `/assets/${item.id}/edit`;
        },
        delete: async function(item) {
            if (confirm('Are you sure?')) {
                await this.makeRequest(`/assets/${item.id}`, { method: 'DELETE' });
                this.refreshData();
            }
        }
    },
    bulkActions: {
        archive: async function(ids) {
            await this.makeRequest('/assets/bulk-archive', {
                method: 'POST',
                body: JSON.stringify({ ids })
            });
        }
    }
}));
*/