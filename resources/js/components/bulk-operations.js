/**
 * Bulk Operations Component
 * Handles bulk operations for line items (select all, delete, discount, etc.)
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('bulkOperations', (config = {}) => ({
        // Configuration
        enableMultiSelect: config.enableMultiSelect !== false,
        enableBulkActions: config.enableBulkActions !== false,
        
        // State
        selectedItems: new Set(),
        selectAll: false,
        showBulkPanel: false,
        bulkAction: '',
        bulkDiscountType: 'percentage',
        bulkDiscountValue: 0,
        bulkTaxRate: 0,
        bulkCategory: '',
        processing: false,
        
        // History for undo
        operationHistory: [],
        maxHistorySize: 10,

        // Initialize component
        init() {
            this.setupEventListeners();
            this.watchItemChanges();
        },

        // Setup event listeners
        setupEventListeners() {
            // Listen for item selection events
            document.addEventListener('item-selected', (e) => {
                this.handleItemSelection(e.detail.itemId, e.detail.selected);
            });

            // Listen for keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.ctrlKey || e.metaKey) {
                    switch (e.key) {
                        case 'a':
                            if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
                                e.preventDefault();
                                this.selectAllItems();
                            }
                            break;
                        case 'd':
                            if (this.hasSelection) {
                                e.preventDefault();
                                this.deleteSelected();
                            }
                            break;
                    }
                }
                
                if (e.key === 'Delete' && this.hasSelection) {
                    this.deleteSelected();
                }
            });

            // Click outside to hide bulk panel
            document.addEventListener('click', (e) => {
                if (this.showBulkPanel && !e.target.closest('.bulk-operations-panel')) {
                    this.showBulkPanel = false;
                }
            });
        },

        // Watch for item changes
        watchItemChanges() {
            this.$watch('$store.quote.selectedItems', (newItems) => {
                // Remove selections for items that no longer exist
                const itemIds = new Set(newItems.map(item => item.id || item.temp_id));
                this.selectedItems.forEach(id => {
                    if (!itemIds.has(id)) {
                        this.selectedItems.delete(id);
                    }
                });
                
                this.updateSelectAllState();
            }, { deep: true });
        },

        // Handle individual item selection
        handleItemSelection(itemId, selected) {
            if (selected) {
                this.selectedItems.add(itemId);
            } else {
                this.selectedItems.delete(itemId);
            }
            
            this.updateSelectAllState();
            this.updateBulkPanelVisibility();
        },

        // Select/deselect all items
        selectAllItems() {
            const items = this.$store.quote.selectedItems;
            
            if (this.selectAll) {
                // Deselect all
                this.selectedItems.clear();
                this.selectAll = false;
            } else {
                // Select all
                items.forEach(item => {
                    this.selectedItems.add(item.id || item.temp_id);
                });
                this.selectAll = true;
            }
            
            this.updateItemSelectionUI();
            this.updateBulkPanelVisibility();
        },

        // Update select all checkbox state
        updateSelectAllState() {
            const totalItems = this.$store.quote.selectedItems.length;
            const selectedCount = this.selectedItems.size;
            
            if (selectedCount === 0) {
                this.selectAll = false;
            } else if (selectedCount === totalItems) {
                this.selectAll = true;
            } else {
                this.selectAll = 'indeterminate';
            }
        },

        // Update bulk panel visibility
        updateBulkPanelVisibility() {
            if (this.selectedItems.size > 0 && this.enableBulkActions) {
                this.showBulkPanel = true;
            } else {
                this.showBulkPanel = false;
            }
        },

        // Update item selection UI
        updateItemSelectionUI() {
            const items = this.$store.quote.selectedItems;
            items.forEach(item => {
                const itemId = item.id || item.temp_id;
                const checkbox = document.querySelector(`[data-item-checkbox="${itemId}"]`);
                if (checkbox) {
                    checkbox.checked = this.selectedItems.has(itemId);
                }
                
                const row = document.querySelector(`[data-item-row="${itemId}"]`);
                if (row) {
                    row.classList.toggle('selected', this.selectedItems.has(itemId));
                }
            });
        },

        // Get selected item objects
        getSelectedItemObjects() {
            const items = this.$store.quote.selectedItems;
            return items.filter(item => 
                this.selectedItems.has(item.id || item.temp_id)
            );
        },

        // Bulk operations
        async performBulkAction() {
            if (!this.bulkAction || this.processing) return;

            try {
                this.processing = true;
                
                // Save current state for undo
                this.saveStateForUndo();

                switch (this.bulkAction) {
                    case 'delete':
                        await this.deleteSelected();
                        break;
                    case 'duplicate':
                        await this.duplicateSelected();
                        break;
                    case 'apply_discount':
                        await this.applyBulkDiscount();
                        break;
                    case 'apply_tax':
                        await this.applyBulkTax();
                        break;
                    case 'change_category':
                        await this.changeBulkCategory();
                        break;
                    case 'update_prices':
                        await this.updateBulkPrices();
                        break;
                    case 'export_selected':
                        await this.exportSelected();
                        break;
                }

                this.showNotification(`Bulk ${this.bulkAction.replace('_', ' ')} completed successfully`, 'success');
                
            } catch (error) {
                console.error('Bulk operation failed:', error);
                this.showNotification(`Bulk operation failed: ${error.message}`, 'error');
            } finally {
                this.processing = false;
                this.bulkAction = '';
            }
        },

        // Delete selected items
        async deleteSelected() {
            if (this.selectedItems.size === 0) return;

            const confirmed = confirm(`Delete ${this.selectedItems.size} selected item(s)?`);
            if (!confirmed) return;

            const selectedItems = this.getSelectedItemObjects();
            
            selectedItems.forEach(item => {
                this.$store.quote.removeItem(item.id || item.temp_id);
            });

            this.selectedItems.clear();
            this.updateSelectAllState();
            this.showBulkPanel = false;
        },

        // Duplicate selected items
        async duplicateSelected() {
            const selectedItems = this.getSelectedItemObjects();
            
            selectedItems.forEach(item => {
                const duplicatedItem = {
                    ...item,
                    id: undefined,
                    temp_id: 'temp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
                    name: item.name + ' (Copy)'
                };
                
                this.$store.quote.addItem(duplicatedItem);
            });

            this.selectedItems.clear();
            this.updateSelectAllState();
        },

        // Apply bulk discount
        async applyBulkDiscount() {
            const selectedItems = this.getSelectedItemObjects();
            
            selectedItems.forEach(item => {
                if (this.bulkDiscountType === 'percentage') {
                    item.discount = (item.unit_price * item.quantity) * (this.bulkDiscountValue / 100);
                } else {
                    item.discount = this.bulkDiscountValue;
                }
            });

            this.$store.quote.recalculate();
        },

        // Apply bulk tax rate
        async applyBulkTax() {
            const selectedItems = this.getSelectedItemObjects();
            
            selectedItems.forEach(item => {
                item.tax_rate = this.bulkTaxRate;
            });

            this.$store.quote.recalculate();
        },

        // Change bulk category
        async changeBulkCategory() {
            const selectedItems = this.getSelectedItemObjects();
            
            selectedItems.forEach(item => {
                item.category = this.bulkCategory;
            });
        },

        // Update bulk prices
        async updateBulkPrices() {
            const selectedItems = this.getSelectedItemObjects();
            const adjustment = prompt('Price adjustment (e.g., +10, -5, *1.1, /2):');
            
            if (!adjustment) return;

            const operation = adjustment.charAt(0);
            const value = parseFloat(adjustment.slice(1));

            if (isNaN(value)) {
                throw new Error('Invalid adjustment value');
            }

            selectedItems.forEach(item => {
                switch (operation) {
                    case '+':
                        item.unit_price += value;
                        break;
                    case '-':
                        item.unit_price = Math.max(0, item.unit_price - value);
                        break;
                    case '*':
                        item.unit_price *= value;
                        break;
                    case '/':
                        if (value !== 0) {
                            item.unit_price /= value;
                        }
                        break;
                    default:
                        item.unit_price = value;
                }
                
                item.unit_price = Math.round(item.unit_price * 100) / 100; // Round to 2 decimals
            });

            this.$store.quote.recalculate();
        },

        // Export selected items
        async exportSelected() {
            const selectedItems = this.getSelectedItemObjects();
            
            const csvContent = this.generateCSV(selectedItems);
            this.downloadCSV(csvContent, `quote-items-${Date.now()}.csv`);
        },

        // Generate CSV content
        generateCSV(items) {
            const headers = ['Name', 'Description', 'Quantity', 'Unit Price', 'Discount', 'Tax Rate', 'Category', 'Total'];
            const rows = items.map(item => [
                item.name,
                item.description || '',
                item.quantity,
                item.unit_price,
                item.discount || 0,
                item.tax_rate || 0,
                item.category || '',
                (item.quantity * item.unit_price) - (item.discount || 0)
            ]);

            return [headers, ...rows]
                .map(row => row.map(cell => `"${cell}"`).join(','))
                .join('\n');
        },

        // Download CSV file
        downloadCSV(content, filename) {
            const blob = new Blob([content], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.style.display = 'none';
            
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            
            URL.revokeObjectURL(url);
        },

        // Undo/Redo functionality
        saveStateForUndo() {
            const state = {
                items: JSON.parse(JSON.stringify(this.$store.quote.selectedItems)),
                timestamp: Date.now()
            };

            this.operationHistory.push(state);
            
            // Limit history size
            if (this.operationHistory.length > this.maxHistorySize) {
                this.operationHistory.shift();
            }
        },

        undoLastOperation() {
            if (this.operationHistory.length === 0) return;

            const previousState = this.operationHistory.pop();
            this.$store.quote.selectedItems = previousState.items;
            this.$store.quote.recalculate();
            
            this.selectedItems.clear();
            this.updateSelectAllState();
            
            this.showNotification('Last operation undone', 'info');
        },

        // Quick selection methods
        selectByCategory(category) {
            this.selectedItems.clear();
            
            this.$store.quote.selectedItems.forEach(item => {
                if (item.category === category) {
                    this.selectedItems.add(item.id || item.temp_id);
                }
            });
            
            this.updateSelectAllState();
            this.updateItemSelectionUI();
            this.updateBulkPanelVisibility();
        },

        selectByPriceRange(min, max) {
            this.selectedItems.clear();
            
            this.$store.quote.selectedItems.forEach(item => {
                const price = item.unit_price;
                if (price >= min && price <= max) {
                    this.selectedItems.add(item.id || item.temp_id);
                }
            });
            
            this.updateSelectAllState();
            this.updateItemSelectionUI();
            this.updateBulkPanelVisibility();
        },

        selectByType(type) {
            this.selectedItems.clear();
            
            this.$store.quote.selectedItems.forEach(item => {
                if (item.type === type) {
                    this.selectedItems.add(item.id || item.temp_id);
                }
            });
            
            this.updateSelectAllState();
            this.updateItemSelectionUI();
            this.updateBulkPanelVisibility();
        },

        // Advanced operations
        distributeDiscount(totalDiscount) {
            const selectedItems = this.getSelectedItemObjects();
            const totalValue = selectedItems.reduce((sum, item) => 
                sum + (item.quantity * item.unit_price), 0);
            
            if (totalValue === 0) return;

            selectedItems.forEach(item => {
                const itemValue = item.quantity * item.unit_price;
                const itemProportion = itemValue / totalValue;
                item.discount = totalDiscount * itemProportion;
            });

            this.$store.quote.recalculate();
        },

        applyVolumeDiscount() {
            const selectedItems = this.getSelectedItemObjects();
            const totalQuantity = selectedItems.reduce((sum, item) => sum + item.quantity, 0);
            
            // Volume discount tiers (configurable)
            const tiers = [
                { min: 10, discount: 0.05 },
                { min: 50, discount: 0.10 },
                { min: 100, discount: 0.15 },
                { min: 500, discount: 0.20 }
            ];
            
            const applicableTier = tiers
                .filter(tier => totalQuantity >= tier.min)
                .sort((a, b) => b.min - a.min)[0];
            
            if (applicableTier) {
                selectedItems.forEach(item => {
                    const itemValue = item.quantity * item.unit_price;
                    item.discount = itemValue * applicableTier.discount;
                });
                
                this.$store.quote.recalculate();
                this.showNotification(`Volume discount of ${applicableTier.discount * 100}% applied`, 'success');
            }
        },

        // Validation
        validateBulkOperation() {
            if (this.selectedItems.size === 0) {
                throw new Error('No items selected');
            }

            if (this.bulkAction === 'apply_discount' && (this.bulkDiscountValue < 0 || this.bulkDiscountValue > 100)) {
                throw new Error('Discount value must be between 0 and 100');
            }

            if (this.bulkAction === 'apply_tax' && (this.bulkTaxRate < 0 || this.bulkTaxRate > 100)) {
                throw new Error('Tax rate must be between 0 and 100');
            }
        },

        // Utility methods
        showNotification(message, type = 'info') {
            this.$dispatch('notification', { message, type });
        },

        clearSelection() {
            this.selectedItems.clear();
            this.selectAll = false;
            this.updateItemSelectionUI();
            this.showBulkPanel = false;
        },

        // Computed properties
        get hasSelection() {
            return this.selectedItems.size > 0;
        },

        get selectionCount() {
            return this.selectedItems.size;
        },

        get selectedItemsValue() {
            return this.getSelectedItemObjects().reduce((sum, item) => 
                sum + (item.quantity * item.unit_price), 0);
        },

        get canUndo() {
            return this.operationHistory.length > 0;
        },

        get availableCategories() {
            const categories = new Set();
            this.$store.quote.selectedItems.forEach(item => {
                if (item.category) {
                    categories.add(item.category);
                }
            });
            return Array.from(categories);
        },

        get bulkOperationOptions() {
            return [
                { value: 'delete', label: 'Delete Selected', icon: '🗑️' },
                { value: 'duplicate', label: 'Duplicate Selected', icon: '📄' },
                { value: 'apply_discount', label: 'Apply Discount', icon: '💰' },
                { value: 'apply_tax', label: 'Apply Tax Rate', icon: '📊' },
                { value: 'change_category', label: 'Change Category', icon: '📁' },
                { value: 'update_prices', label: 'Update Prices', icon: '💲' },
                { value: 'export_selected', label: 'Export Selected', icon: '📤' }
            ];
        }
    }));

    // Global bulk operations store
    Alpine.store('bulkOperations', {
        enabled: true,
        selectedItems: new Set(),
        
        selectItem(itemId) {
            this.selectedItems.add(itemId);
        },
        
        deselectItem(itemId) {
            this.selectedItems.delete(itemId);
        },
        
        clearSelection() {
            this.selectedItems.clear();
        },
        
        get hasSelection() {
            return this.selectedItems.size > 0;
        }
    });
});