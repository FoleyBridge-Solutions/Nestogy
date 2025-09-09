export function productCreateForm(productType = 'product') {
    return {
        // Product type
        type: productType,
        
        // Form data
        name: '',
        sku: '',
        description: '',
        shortDescription: '',
        basePrice: '',
        cost: '',
        categoryId: '',
        unitType: 'units',
        pricingModel: 'fixed',
        billingModel: 'one_time',
        billingCycle: 'one_time',
        billingInterval: 1,
        
        // Form state
        isActive: true,
        isFeatured: false,
        trackInventory: false,
        isTaxable: true,
        taxInclusive: false,
        allowDiscounts: true,
        requiresApproval: false,
        
        // Inventory fields
        currentStock: 0,
        minStockLevel: 0,
        reorderLevel: '',
        maxQuantityPerOrder: '',
        sortOrder: 0,
        
        // Loading states
        submitting: false,
        generating: false,
        
        init() {
            // Auto-generate SKU when name changes
            this.$watch('name', (value) => {
                if (!this.sku && value) {
                    this.generateSku();
                }
            });
            
            // Show/hide inventory fields
            this.$watch('trackInventory', (value) => {
                this.toggleInventoryFields();
            });
            
            // Handle billing model changes
            this.$watch('billingModel', (value) => {
                this.handleBillingModelChange(value);
            });
        },
        
        // Auto-generate SKU from product name
        async generateSku() {
            if (!this.name || this.generating) return;
            
            this.generating = true;
            
            try {
                // Simple client-side SKU generation
                let sku = this.name
                    .toUpperCase()
                    .replace(/[^A-Z0-9\s]/g, '') // Remove special chars
                    .replace(/\s+/g, '-') // Replace spaces with hyphens
                    .substring(0, 20); // Limit length
                
                // Add type prefix if it's a service
                if (this.type === 'service') {
                    sku = 'SVC-' + sku;
                } else {
                    sku = 'PRD-' + sku;
                }
                
                this.sku = sku;
            } catch (error) {
                console.error('Error generating SKU:', error);
            } finally {
                this.generating = false;
            }
        },
        
        // Toggle inventory field visibility
        toggleInventoryFields() {
            const inventoryFields = document.getElementById('inventory-fields');
            if (inventoryFields) {
                inventoryFields.style.display = this.trackInventory ? 'block' : 'none';
            }
        },
        
        // Handle billing model changes
        handleBillingModelChange(value) {
            const subscriptionFields = document.getElementById('subscription-billing-fields');
            if (subscriptionFields) {
                subscriptionFields.style.display = (value === 'subscription') ? 'block' : 'none';
            }
            
            // Auto-adjust billing cycle for subscription
            if (value === 'subscription' && this.billingCycle === 'one_time') {
                this.billingCycle = 'monthly';
            } else if (value === 'one_time' && this.billingCycle !== 'one_time') {
                this.billingCycle = 'one_time';
            }
        },
        
        // Form validation
        get isFormValid() {
            return this.name && 
                   this.name.trim().length > 0 &&
                   this.basePrice && 
                   parseFloat(this.basePrice) >= 0 &&
                   this.categoryId;
        },
        
        // Calculate margin if cost is provided
        get marginPercentage() {
            if (!this.basePrice || !this.cost) return null;
            
            const price = parseFloat(this.basePrice);
            const cost = parseFloat(this.cost);
            
            if (cost === 0) return null;
            
            return ((price - cost) / cost * 100).toFixed(1);
        },
        
        // Get pricing display text
        get pricingDisplay() {
            if (!this.basePrice) return '';
            
            const price = parseFloat(this.basePrice);
            const unit = this.unitType === 'units' ? 'each' : `per ${this.unitType}`;
            
            return `$${price.toFixed(2)} ${unit}`;
        },
        
        // Form submission
        async submitForm(event) {
            if (!this.isFormValid) {
                event.preventDefault();
                this.showError('Please fill in all required fields');
                return false;
            }
            
            // Clean up empty numeric fields before submission
            this.cleanupNumericFields();
            
            this.submitting = true;
            // Form will submit naturally
        },
        
        // Clean up empty numeric fields to prevent validation errors
        cleanupNumericFields() {
            const form = event.target;
            
            // Optional numeric fields that can be removed if empty
            const optionalNumericFields = [
                'reorder_level',
                'max_quantity_per_order'
            ];
            
            optionalNumericFields.forEach(fieldName => {
                const field = form.querySelector(`input[name="${fieldName}"]`);
                if (field && (field.value === '' || field.value === null)) {
                    field.removeAttribute('name');
                }
            });
            
            // Required numeric fields that should have default values
            const requiredNumericFields = [
                { name: 'billing_interval', defaultValue: 1 },
                { name: 'current_stock', defaultValue: 0 },
                { name: 'min_stock_level', defaultValue: 0 },
                { name: 'sort_order', defaultValue: 0 }
            ];
            
            requiredNumericFields.forEach(({ name, defaultValue }) => {
                const field = form.querySelector(`input[name="${name}"]`);
                if (field && (field.value === '' || field.value === null)) {
                    field.value = defaultValue;
                }
            });
        },
        
        // Show error message
        showError(message) {
            if (window.showAlert) {
                window.showAlert('error', 'Error', message);
            } else {
                alert('Error: ' + message);
            }
        },
        
        // Show success message
        showSuccess(message) {
            if (window.showAlert) {
                window.showAlert('success', 'Success', message);
            } else {
                alert(message);
            }
        },
        
        // Reset form
        resetForm() {
            this.name = '';
            this.sku = '';
            this.description = '';
            this.shortDescription = '';
            this.basePrice = '';
            this.cost = '';
            this.categoryId = '';
            this.unitType = 'units';
            this.pricingModel = 'fixed';
            this.billingModel = 'one_time';
            this.billingCycle = 'one_time';
            this.isActive = true;
            this.isFeatured = false;
            this.trackInventory = false;
            this.isTaxable = true;
            this.taxInclusive = false;
            this.allowDiscounts = true;
            this.requiresApproval = false;
        }
    };
}