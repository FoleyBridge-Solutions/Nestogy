/**
 * Real-time Form Validation Component
 * Provides inline validation with error display for quote forms
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('formValidator', (config = {}) => ({
        // Configuration
        apiEndpoint: config.apiEndpoint || '/api/validate',
        debounceDelay: config.debounceDelay || 300,
        showValidIcon: config.showValidIcon || true,
        
        // State
        validating: false,
        touched: new Set(),
        errors: {},
        validFields: new Set(),
        serverErrors: {},
        
        // Rules
        rules: {
            client_id: {
                required: true,
                message: 'Please select a client'
            },
            category_id: {
                required: true,
                message: 'Please select a category'
            },
            date: {
                required: true,
                type: 'date',
                message: 'Please enter a valid date'
            },
            expire_date: {
                required: false,
                type: 'date',
                after: 'date',
                message: 'Expiry date must be after quote date'
            },
            scope: {
                required: true,
                minLength: 3,
                maxLength: 500,
                message: 'Scope must be between 3 and 500 characters'
            },
            discount_amount: {
                required: false,
                type: 'number',
                min: 0,
                max: 100,
                message: 'Discount must be between 0 and 100'
            },
            'items.*.name': {
                required: true,
                minLength: 2,
                message: 'Item name is required (min 2 characters)'
            },
            'items.*.quantity': {
                required: true,
                type: 'number',
                min: 0.01,
                message: 'Quantity must be greater than 0'
            },
            'items.*.unit_price': {
                required: true,
                type: 'number',
                min: 0,
                message: 'Unit price must be 0 or greater'
            }
        },

        // Initialize validator
        init() {
            this.setupWatchers();
            this.setupEventListeners();
        },

        // Setup field watchers
        setupWatchers() {
            // Watch quote store changes
            this.$watch('$store.quote.document', (newData, oldData) => {
                Object.keys(newData).forEach(field => {
                    if (this.touched.has(field) && newData[field] !== oldData?.[field]) {
                        this.validateField(field, newData[field]);
                    }
                });
            }, { deep: true });

            this.$watch('$store.quote.selectedItems', (newItems) => {
                if (this.touched.has('items')) {
                    this.validateItems(newItems);
                }
            }, { deep: true });
        },

        // Setup event listeners
        setupEventListeners() {
            // Mark fields as touched on blur
            document.addEventListener('focusout', (e) => {
                const field = e.target.name || e.target.dataset.field;
                if (field) {
                    this.markAsTouched(field);
                    this.validateField(field, e.target.value);
                }
            });

            // Real-time validation on input
            document.addEventListener('input', (e) => {
                const field = e.target.name || e.target.dataset.field;
                if (field && this.touched.has(field)) {
                    clearTimeout(this._debounceTimeout);
                    this._debounceTimeout = setTimeout(() => {
                        this.validateField(field, e.target.value);
                    }, this.debounceDelay);
                }
            });

            // Form submission validation
            document.addEventListener('submit', (e) => {
                if (e.target.dataset.validateForm === 'true') {
                    if (!this.validateAll()) {
                        e.preventDefault();
                        this.showFirstError();
                    }
                }
            });
        },

        // Mark field as touched
        markAsTouched(field) {
            this.touched.add(field);
        },

        // Validate single field
        validateField(field, value) {
            const rule = this.rules[field];
            if (!rule) return true;

            const errors = [];

            // Required validation
            if (rule.required && this.isEmpty(value)) {
                errors.push(rule.message || `${this.getFieldLabel(field)} is required`);
            }

            // Skip other validations if field is empty and not required
            if (!rule.required && this.isEmpty(value)) {
                this.clearFieldError(field);
                return true;
            }

            // Type validation
            if (rule.type && !this.validateType(value, rule.type)) {
                errors.push(rule.message || `${this.getFieldLabel(field)} must be a valid ${rule.type}`);
            }

            // Length validation
            if (rule.minLength && value.length < rule.minLength) {
                errors.push(rule.message || `${this.getFieldLabel(field)} must be at least ${rule.minLength} characters`);
            }

            if (rule.maxLength && value.length > rule.maxLength) {
                errors.push(rule.message || `${this.getFieldLabel(field)} must be no more than ${rule.maxLength} characters`);
            }

            // Number validation
            if (rule.type === 'number') {
                const numValue = parseFloat(value);
                if (rule.min !== undefined && numValue < rule.min) {
                    errors.push(rule.message || `${this.getFieldLabel(field)} must be at least ${rule.min}`);
                }
                if (rule.max !== undefined && numValue > rule.max) {
                    errors.push(rule.message || `${this.getFieldLabel(field)} must be no more than ${rule.max}`);
                }
            }

            // Date validation
            if (rule.after && rule.type === 'date') {
                const afterValue = this.$store.quote.document[rule.after];
                if (afterValue && new Date(value) <= new Date(afterValue)) {
                    errors.push(rule.message || `${this.getFieldLabel(field)} must be after ${this.getFieldLabel(rule.after)}`);
                }
            }

            // Custom validation
            if (rule.custom && typeof rule.custom === 'function') {
                const customError = rule.custom(value, this.$store.quote);
                if (customError) {
                    errors.push(customError);
                }
            }

            // Set or clear errors
            if (errors.length > 0) {
                this.setFieldError(field, errors[0]);
                return false;
            } else {
                this.clearFieldError(field);
                this.validFields.add(field);
                return true;
            }
        },

        // Validate items array
        validateItems(items) {
            let hasErrors = false;

            items.forEach((item, index) => {
                Object.keys(item).forEach(prop => {
                    const fieldKey = `items.${index}.${prop}`;
                    const ruleKey = `items.*.${prop}`;
                    
                    if (this.rules[ruleKey]) {
                        if (!this.validateField(fieldKey, item[prop])) {
                            hasErrors = true;
                        }
                    }
                });
            });

            if (!hasErrors) {
                this.clearFieldError('items');
            }

            return !hasErrors;
        },

        // Validate entire form
        validateAll() {
            let isValid = true;
            const quote = this.$store.quote;

            // Validate document fields
            Object.keys(this.rules).forEach(field => {
                if (!field.includes('items.*')) {
                    this.markAsTouched(field);
                    const value = this.getNestedValue(quote.document, field);
                    if (!this.validateField(field, value)) {
                        isValid = false;
                    }
                }
            });

            // Validate items
            if (quote.selectedItems.length === 0) {
                this.setFieldError('items', 'At least one item is required');
                isValid = false;
            } else {
                if (!this.validateItems(quote.selectedItems)) {
                    isValid = false;
                }
            }

            return isValid;
        },

        // Server-side validation
        async validateOnServer(data) {
            if (this.validating) return;

            try {
                this.validating = true;
                this.serverErrors = {};

                const response = await fetch(this.apiEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (!response.ok) {
                    if (result.errors) {
                        this.serverErrors = result.errors;
                        Object.keys(result.errors).forEach(field => {
                            this.setFieldError(field, result.errors[field][0]);
                        });
                    }
                    return false;
                }

                return true;

            } catch (error) {
                console.error('Server validation failed:', error);
                return false;
            } finally {
                this.validating = false;
            }
        },

        // Set field error
        setFieldError(field, message) {
            this.errors[field] = message;
            this.validFields.delete(field);
            this.updateFieldDisplay(field, 'error');
        },

        // Clear field error
        clearFieldError(field) {
            delete this.errors[field];
            this.validFields.add(field);
            this.updateFieldDisplay(field, 'valid');
        },

        // Update field display
        updateFieldDisplay(field, state) {
            const elements = document.querySelectorAll(`[name="${field}"], [data-field="${field}"]`);
            
            elements.forEach(element => {
                // Remove previous state classes
                element.classList.remove('is-valid', 'is-invalid', 'border-green-500', 'border-red-500');
                
                // Add new state classes
                if (state === 'error') {
                    element.classList.add('is-invalid', 'border-red-500');
                } else if (state === 'valid' && this.showValidIcon) {
                    element.classList.add('is-valid', 'border-green-500');
                }
            });

            // Update error message display
            this.updateErrorDisplay(field);
        },

        // Update error message display
        updateErrorDisplay(field) {
            const errorElements = document.querySelectorAll(`[data-error="${field}"]`);
            const message = this.errors[field];

            errorElements.forEach(element => {
                if (message) {
                    element.textContent = message;
                    element.style.display = 'block';
                    element.classList.add('text-red-500', 'text-sm', 'mt-1');
                } else {
                    element.style.display = 'none';
                }
            });
        },

        // Show first error
        showFirstError() {
            const firstErrorField = Object.keys(this.errors)[0];
            if (firstErrorField) {
                const element = document.querySelector(`[name="${firstErrorField}"], [data-field="${firstErrorField}"]`);
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    element.focus();
                }
            }
        },

        // Utility methods
        isEmpty(value) {
            return value === null || value === undefined || value === '' || 
                   (Array.isArray(value) && value.length === 0);
        },

        validateType(value, type) {
            switch (type) {
                case 'email':
                    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                case 'number':
                    return !isNaN(parseFloat(value)) && isFinite(value);
                case 'date':
                    return !isNaN(Date.parse(value));
                case 'url':
                    try {
                        new URL(value);
                        return true;
                    } catch {
                        return false;
                    }
                default:
                    return true;
            }
        },

        getNestedValue(obj, path) {
            return path.split('.').reduce((current, key) => {
                return current && current[key] !== undefined ? current[key] : '';
            }, obj);
        },

        getFieldLabel(field) {
            const labels = {
                client_id: 'Client',
                category_id: 'Category',
                date: 'Date',
                expire_date: 'Expiry Date',
                scope: 'Scope',
                discount_amount: 'Discount Amount',
                'items.*.name': 'Item Name',
                'items.*.quantity': 'Quantity',
                'items.*.unit_price': 'Unit Price'
            };
            return labels[field] || field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        },

        // Computed properties
        get hasErrors() {
            return Object.keys(this.errors).length > 0;
        },

        get isValid() {
            return !this.hasErrors && this.touched.size > 0;
        },

        get errorCount() {
            return Object.keys(this.errors).length;
        },

        get validFieldCount() {
            return this.validFields.size;
        },

        // Form state methods
        resetValidation() {
            this.errors = {};
            this.validFields.clear();
            this.touched.clear();
            this.serverErrors = {};
            
            // Reset field displays
            document.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
                el.classList.remove('is-valid', 'is-invalid', 'border-green-500', 'border-red-500');
            });
            
            document.querySelectorAll('[data-error]').forEach(el => {
                el.style.display = 'none';
            });
        },

        // Add custom rule
        addRule(field, rule) {
            this.rules[field] = rule;
        },

        // Remove rule
        removeRule(field) {
            delete this.rules[field];
        }
    }));

    // Auto-validation directive
    Alpine.directive('validate', (el, { expression }, { evaluate }) => {
        const config = evaluate(expression) || {};
        const validator = Alpine.$data(el).formValidator || 
                         Alpine.reactive(Alpine.store('formValidator') || {});

        // Auto-add validation attributes
        const field = el.name || el.dataset.field;
        if (field && validator.rules && validator.rules[field]) {
            const rule = validator.rules[field];
            
            if (rule.required) {
                el.setAttribute('required', '');
            }
            
            if (rule.type) {
                el.setAttribute('type', rule.type);
            }
            
            if (rule.minLength) {
                el.setAttribute('minlength', rule.minLength);
            }
            
            if (rule.maxLength) {
                el.setAttribute('maxlength', rule.maxLength);
            }
        }
    });
});