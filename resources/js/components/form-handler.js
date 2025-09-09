// Reusable Form Handler Alpine.js component
import baseComponent from './base-component.js';

export default function formHandler(config = {}) {
    return baseComponent({
        // Form specific properties
        formData: {},
        originalData: {},
        isDirty: false,
        isValid: true,
        
        // Validation
        validationRules: {},
        
        init() {
            this.initializeForm();
            this.setupValidation();
            this.setupAutosave();
        },
        
        initializeForm() {
            this.formData = config.initialData || {};
            this.originalData = { ...this.formData };
            this.validationRules = config.validationRules || {};
        },
        
        setupValidation() {
            // Watch for changes to mark form as dirty
            this.$watch('formData', () => {
                this.isDirty = this.checkIfDirty();
                this.validateForm();
            }, { deep: true });
        },
        
        setupAutosave() {
            if (config.autosave) {
                setInterval(() => {
                    if (this.isDirty && this.isValid && !this.loading) {
                        this.autosave();
                    }
                }, config.autosaveInterval || 30000); // 30 seconds default
            }
        },
        
        checkIfDirty() {
            return JSON.stringify(this.formData) !== JSON.stringify(this.originalData);
        },
        
        // Validation methods
        validateForm() {
            this.clearErrors();
            this.isValid = true;
            
            Object.keys(this.validationRules).forEach(field => {
                this.validateField(field);
            });
            
            return this.isValid;
        },
        
        validateField(field) {
            const rules = this.validationRules[field];
            const value = this.getFieldValue(field);
            
            if (!rules) return true;
            
            for (const rule of rules) {
                const result = this.applyValidationRule(field, value, rule);
                if (!result.valid) {
                    this.setError(field, result.message);
                    this.isValid = false;
                    return false;
                }
            }
            
            return true;
        },
        
        applyValidationRule(field, value, rule) {
            if (typeof rule === 'string') {
                return this.applyBuiltInRule(field, value, rule);
            }
            
            if (typeof rule === 'function') {
                return rule(value, this.formData);
            }
            
            if (typeof rule === 'object') {
                return this.applyBuiltInRule(field, value, rule.type, rule.params);
            }
            
            return { valid: true };
        },
        
        applyBuiltInRule(field, value, ruleType, params = {}) {
            switch (ruleType) {
                case 'required':
                    if (!value || (typeof value === 'string' && value.trim() === '')) {
                        return { valid: false, message: `${this.getFieldLabel(field)} is required` };
                    }
                    break;
                    
                case 'email':
                    if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                        return { valid: false, message: `${this.getFieldLabel(field)} must be a valid email` };
                    }
                    break;
                    
                case 'min':
                    if (value && value.length < params.length) {
                        return { valid: false, message: `${this.getFieldLabel(field)} must be at least ${params.length} characters` };
                    }
                    break;
                    
                case 'max':
                    if (value && value.length > params.length) {
                        return { valid: false, message: `${this.getFieldLabel(field)} must not exceed ${params.length} characters` };
                    }
                    break;
                    
                case 'numeric':
                    if (value && isNaN(value)) {
                        return { valid: false, message: `${this.getFieldLabel(field)} must be a number` };
                    }
                    break;
                    
                case 'url':
                    if (value && !/^https?:\/\/.+/.test(value)) {
                        return { valid: false, message: `${this.getFieldLabel(field)} must be a valid URL` };
                    }
                    break;
            }
            
            return { valid: true };
        },
        
        getFieldLabel(field) {
            return config.fieldLabels?.[field] || field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        },
        
        getFieldValue(field) {
            const keys = field.split('.');
            let value = this.formData;
            for (const key of keys) {
                value = value?.[key];
            }
            return value;
        },
        
        setFieldValue(field, value) {
            const keys = field.split('.');
            let obj = this.formData;
            for (let i = 0; i < keys.length - 1; i++) {
                const key = keys[i];
                if (!(key in obj)) {
                    obj[key] = {};
                }
                obj = obj[key];
            }
            obj[keys[keys.length - 1]] = value;
        },
        
        // Form submission
        async submitForm() {
            if (!this.validateForm()) {
                return false;
            }
            
            const url = config.submitUrl || this.getFormAction();
            const method = config.method || 'POST';
            
            try {
                const response = await this.makeRequest(url, {
                    method: method,
                    body: JSON.stringify(this.formData)
                });
                
                this.handleSubmitSuccess(response);
                return true;
            } catch (error) {
                this.handleSubmitError(error);
                return false;
            }
        },
        
        handleSubmitSuccess(response) {
            this.originalData = { ...this.formData };
            this.isDirty = false;
            this.setSuccess(response.message || 'Form submitted successfully');
            
            if (config.onSuccess) {
                config.onSuccess.call(this, response);
            }
            
            if (config.redirectUrl) {
                setTimeout(() => {
                    window.location.href = config.redirectUrl;
                }, 1000);
            }
        },
        
        handleSubmitError(error) {
            if (config.onError) {
                config.onError.call(this, error);
            }
        },
        
        getFormAction() {
            const form = this.$el.closest('form');
            return form?.action || window.location.href;
        },
        
        // Autosave functionality
        async autosave() {
            if (!config.autosaveUrl) return;
            
            try {
                await this.makeRequest(config.autosaveUrl, {
                    method: 'POST',
                    body: JSON.stringify(this.formData)
                });
                
                this.originalData = { ...this.formData };
                this.isDirty = false;
                
                // Show brief autosave indicator
                this.emit('autosaved');
            } catch (error) {
                console.error('Autosave failed:', error);
            }
        },
        
        // Form reset
        resetForm() {
            this.formData = { ...this.originalData };
            this.clearErrors();
            this.isDirty = false;
        },
        
        // Unsaved changes warning
        beforeUnload(event) {
            if (this.isDirty) {
                event.preventDefault();
                event.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return event.returnValue;
            }
        },
        
        // File upload handling
        async uploadFile(field, file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('field', field);
            
            try {
                const response = await this.makeRequest(config.uploadUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    }
                });
                
                this.setFieldValue(field, response.url);
                return response;
            } catch (error) {
                this.setError(field, 'File upload failed');
                throw error;
            }
        },
        
        // Dynamic field management
        addArrayItem(field, item = {}) {
            const currentArray = this.getFieldValue(field) || [];
            currentArray.push(item);
            this.setFieldValue(field, currentArray);
        },
        
        removeArrayItem(field, index) {
            const currentArray = this.getFieldValue(field) || [];
            currentArray.splice(index, 1);
            this.setFieldValue(field, currentArray);
        },
        
        // Conditional field display
        shouldShowField(field) {
            const condition = config.fieldConditions?.[field];
            if (!condition) return true;
            
            if (typeof condition === 'function') {
                return condition(this.formData);
            }
            
            return true;
        },
        
        // Field helpers
        getFieldClass(field) {
            let classes = ['form-input'];
            
            if (this.hasError(field)) {
                classes.push('border-red-500');
            }
            
            if (config.fieldClasses?.[field]) {
                classes.push(config.fieldClasses[field]);
            }
            
            return classes.join(' ');
        },
        
        // Setup form event listeners
        mounted() {
            // Add beforeunload listener for unsaved changes warning
            if (config.warnUnsavedChanges !== false) {
                window.addEventListener('beforeunload', this.beforeUnload.bind(this));
            }
        },
        
        destroyed() {
            window.removeEventListener('beforeunload', this.beforeUnload.bind(this));
        },
        
        // Merge with config
        ...config
    });
}

// Usage example:
/*
Alpine.data('clientForm', () => formHandler({
    submitUrl: '/clients',
    autosave: true,
    autosaveUrl: '/clients/autosave',
    initialData: {
        name: '',
        email: '',
        phone: ''
    },
    validationRules: {
        name: ['required', { type: 'min', params: { length: 2 } }],
        email: ['required', 'email'],
        phone: [(value) => {
            if (value && !/^\d{10}$/.test(value)) {
                return { valid: false, message: 'Phone must be 10 digits' };
            }
            return { valid: true };
        }]
    },
    onSuccess: function(response) {
        window.location.href = `/clients/${response.id}`;
    }
}));
*/