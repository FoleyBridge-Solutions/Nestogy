/**
 * Settings Components for Nestogy
 * Provides robust, expandable Alpine.js components for settings pages
 */

// Settings API Service
window.SettingsAPI = {
    async save(url, data, method = 'POST') {
        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || 'Failed to save settings');
            }
            
            return { success: true, data: result };
        } catch (error) {
            console.error('Settings save error:', error);
            return { success: false, error: error.message };
        }
    },
    
    async import(url, file, options = {}) {
        const formData = new FormData();
        formData.append('settings_file', file);
        
        Object.keys(options).forEach(key => {
            formData.append(key, options[key]);
        });
        
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: formData
            });
            
            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || 'Failed to import settings');
            }
            
            return { success: true, data: result };
        } catch (error) {
            console.error('Import error:', error);
            return { success: false, error: error.message };
        }
    }
};

// Base Settings Component
window.settingsBase = (initialData = {}) => ({
    // Core properties
    formData: { ...initialData },
    originalData: JSON.stringify(initialData),
    loading: false,
    isDirty: false,
    errors: {},
    
    // Lifecycle
    init() {
        // Track changes
        this.$watch('formData', () => {
            this.isDirty = JSON.stringify(this.formData) !== this.originalData;
        }, { deep: true });
        
        // Warn before leaving with unsaved changes
        window.addEventListener('beforeunload', (e) => {
            if (this.isDirty && !this.loading) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
        
        // Custom initialization
        if (this.onInit) {
            this.onInit();
        }
    },
    
    // Core methods
    async saveSettings(url, method = 'POST') {
        if (!this.validateForm()) {
            this.$dispatch('notify', { 
                type: 'error', 
                message: 'Please fix the validation errors' 
            });
            return;
        }
        
        this.loading = true;
        this.errors = {};
        
        const result = await SettingsAPI.save(url, this.formData, method);
        
        if (result.success) {
            this.originalData = JSON.stringify(this.formData);
            this.isDirty = false;
            this.$dispatch('notify', { 
                type: 'success', 
                message: result.data.message || 'Settings saved successfully' 
            });
            
            // Optional callback
            if (this.onSaveSuccess) {
                this.onSaveSuccess(result.data);
            } else {
                // Default: reload after delay
                setTimeout(() => window.location.reload(), 1500);
            }
        } else {
            this.errors = result.errors || {};
            this.$dispatch('notify', { 
                type: 'error', 
                message: result.error || 'Failed to save settings' 
            });
            
            if (this.onSaveError) {
                this.onSaveError(result);
            }
        }
        
        this.loading = false;
    },
    
    resetForm() {
        this.formData = JSON.parse(this.originalData);
        this.isDirty = false;
        this.errors = {};
    },
    
    // Validation (override in extended components)
    validateForm() {
        return true;
    },
    
    // Field helpers
    getFieldError(field) {
        return this.errors[field]?.[0] || '';
    },
    
    hasFieldError(field) {
        return !!this.errors[field];
    },
    
    clearFieldError(field) {
        delete this.errors[field];
    },
    
    // Utility methods
    updateField(field, value) {
        this.formData[field] = value;
        this.clearFieldError(field);
    }
});

// General Settings Component
window.generalSettingsComponent = (initialData = {}) => ({
    // Extend base
    ...window.settingsBase(initialData),
    
    // Page-specific properties
    activeTab: 'company',
    showImportModal: false,
    selectedFile: null,
    selectedFileName: '',
    dragOver: false,
    importing: false,
    
    // Tabs configuration
    tabs: [
        { id: 'company', label: 'Company Information' },
        { id: 'localization', label: 'Localization' },
        { id: 'system', label: 'System Preferences' }
    ],
    
    // Override validation
    validateForm() {
        let isValid = true;
        this.errors = {};
        
        // Company tab validation
        if (!this.formData.company_name?.trim()) {
            this.errors.company_name = ['Company name is required'];
            isValid = false;
        }
        
        if (this.formData.business_email && !this.isValidEmail(this.formData.business_email)) {
            this.errors.business_email = ['Please enter a valid email address'];
            isValid = false;
        }
        
        if (this.formData.website && !this.isValidUrl(this.formData.website)) {
            this.errors.website = ['Please enter a valid URL'];
            isValid = false;
        }
        
        // System tab validation
        if (this.formData.session_timeout && (this.formData.session_timeout < 5 || this.formData.session_timeout > 1440)) {
            this.errors.session_timeout = ['Session timeout must be between 5 and 1440 minutes'];
            isValid = false;
        }
        
        if (this.formData.max_file_size && (this.formData.max_file_size < 1 || this.formData.max_file_size > 100)) {
            this.errors.max_file_size = ['Max file size must be between 1 and 100 MB'];
            isValid = false;
        }
        
        return isValid;
    },
    
    // Import modal methods
    handleFileSelect(event) {
        const file = event.target.files[0];
        this.validateAndSetFile(file);
    },
    
    handleFileDrop(event) {
        this.dragOver = false;
        const file = event.dataTransfer.files[0];
        this.validateAndSetFile(file);
    },
    
    validateAndSetFile(file) {
        if (!file) return;
        
        if (!file.name.endsWith('.json')) {
            this.$dispatch('notify', { 
                type: 'error', 
                message: 'Please select a valid JSON file' 
            });
            return;
        }
        
        if (file.size > 2 * 1024 * 1024) {
            this.$dispatch('notify', { 
                type: 'error', 
                message: 'File size must be less than 2MB' 
            });
            return;
        }
        
        this.selectedFile = file;
        this.selectedFileName = file.name;
    },
    
    clearFileSelection() {
        this.selectedFile = null;
        this.selectedFileName = '';
        const fileInput = document.getElementById('settings_file');
        if (fileInput) fileInput.value = '';
    },
    
    async submitImport(event) {
        event.preventDefault();
        
        if (!this.selectedFile) {
            this.$dispatch('notify', { 
                type: 'error', 
                message: 'Please select a file to import' 
            });
            return;
        }
        
        this.importing = true;
        
        const form = event.target;
        const options = {
            backup_current: form.backup_current?.checked ? 1 : 0,
            validate_only: form.validate_only?.checked ? 1 : 0
        };
        
        const result = await SettingsAPI.import(form.action, this.selectedFile, options);
        
        if (result.success) {
            this.$dispatch('notify', { 
                type: 'success', 
                message: result.data.message || 'Settings imported successfully' 
            });
            
            if (!options.validate_only) {
                setTimeout(() => window.location.reload(), 1500);
            }
            
            this.showImportModal = false;
            this.clearFileSelection();
        } else {
            this.$dispatch('notify', { 
                type: 'error', 
                message: result.error || 'Failed to import settings' 
            });
        }
        
        this.importing = false;
    },
    
    // Validation helpers
    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },
    
    isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    },
    
    // Tab management
    switchTab(tabId) {
        // Validate current tab before switching
        if (this.isDirty) {
            const shouldSwitch = confirm('You have unsaved changes. Do you want to continue?');
            if (!shouldSwitch) return;
        }
        this.activeTab = tabId;
    }
});

// Security Settings Component
window.securitySettingsComponent = (initialData = {}) => ({
    // Extend base
    ...window.settingsBase(initialData),
    
    // Page-specific properties
    activeTab: 'authentication',
    showTestModal: false,
    showResetModal: false,
    testResults: null,
    
    // Tabs configuration
    tabs: [
        { id: 'authentication', label: 'Authentication' },
        { id: 'password', label: 'Password Policy' },
        { id: 'sessions', label: 'Sessions & Lockout' },
        { id: 'oauth', label: 'OAuth & SSO' },
        { id: 'audit', label: 'Audit & Logging' }
    ],
    
    // Security-specific methods
    async testSecuritySettings() {
        this.loading = true;
        
        try {
            const response = await fetch('/api/settings/security/test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(this.formData)
            });
            
            this.testResults = await response.json();
            this.showTestModal = true;
        } catch (error) {
            this.$dispatch('notify', { 
                type: 'error', 
                message: 'Failed to test security settings' 
            });
        }
        
        this.loading = false;
    },
    
    async resetAllSessions() {
        if (!confirm('This will log out all users. Are you sure?')) return;
        
        this.loading = true;
        
        try {
            const response = await fetch('/api/settings/security/reset-sessions', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            
            if (response.ok) {
                this.$dispatch('notify', { 
                    type: 'success', 
                    message: 'All sessions have been reset' 
                });
            }
        } catch (error) {
            this.$dispatch('notify', { 
                type: 'error', 
                message: 'Failed to reset sessions' 
            });
        }
        
        this.loading = false;
        this.showResetModal = false;
    },
    
    // Validation
    validateForm() {
        let isValid = true;
        this.errors = {};
        
        // Password policy validation
        if (this.formData.password_min_length && (this.formData.password_min_length < 6 || this.formData.password_min_length > 32)) {
            this.errors.password_min_length = ['Password length must be between 6 and 32 characters'];
            isValid = false;
        }
        
        // Session validation
        if (this.formData.session_lifetime && this.formData.idle_timeout) {
            if (this.formData.idle_timeout > this.formData.session_lifetime) {
                this.errors.idle_timeout = ['Idle timeout cannot be greater than session lifetime'];
                isValid = false;
            }
        }
        
        // OAuth validation
        if (this.formData.oauth_google_enabled && !this.formData.oauth_google_client_id) {
            this.errors.oauth_google_client_id = ['Google Client ID is required when OAuth is enabled'];
            isValid = false;
        }
        
        if (this.formData.oauth_microsoft_enabled && !this.formData.oauth_microsoft_client_id) {
            this.errors.oauth_microsoft_client_id = ['Microsoft Client ID is required when OAuth is enabled'];
            isValid = false;
        }
        
        return isValid;
    }
});

// Export for use and register with Alpine
window.SettingsComponents = {
    settingsBase: window.settingsBase,
    generalSettingsComponent: window.generalSettingsComponent,
    securitySettingsComponent: window.securitySettingsComponent
};

// Register Alpine components when Alpine is ready
document.addEventListener('alpine:init', () => {
    // Make components available to Alpine
    Alpine.data('settingsBase', window.settingsBase);
    Alpine.data('generalSettingsComponent', window.generalSettingsComponent);
    Alpine.data('securitySettingsComponent', window.securitySettingsComponent);
});