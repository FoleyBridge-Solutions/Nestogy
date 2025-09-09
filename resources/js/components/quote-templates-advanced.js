/**
 * Advanced Quote Templates Component
 * Manages sophisticated template system with customization options
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('quoteTemplatesAdvanced', (config = {}) => ({
        // Configuration
        enableCustomFields: config.enableCustomFields !== false,
        enableConditionalSections: config.enableConditionalSections !== false,
        maxCustomFields: config.maxCustomFields || 20,
        
        // Template state
        templates: [],
        currentTemplate: null,
        templateEditor: {
            show: false,
            mode: 'create', // create, edit, duplicate
            template: null
        },
        
        // Template structure
        templateStructure: {
            basic: {
                id: null,
                name: '',
                description: '',
                category: 'general',
                is_default: false,
                is_active: true
            },
            layout: {
                header: { enabled: true, content: '', style: {} },
                footer: { enabled: true, content: '', style: {} },
                sections: []
            },
            styling: {
                theme: 'professional',
                colors: {
                    primary: '#2563eb',
                    secondary: '#64748b',
                    accent: '#10b981'
                },
                fonts: {
                    heading: 'Inter',
                    body: 'Inter'
                }
            },
            fields: {
                standard: [],
                custom: []
            },
            conditions: [],
            variables: new Map()
        },
        
        // Available sections
        availableSections: [
            {
                id: 'company_info',
                name: 'Company Information',
                type: 'static',
                required: true,
                fields: ['name', 'address', 'phone', 'email', 'website']
            },
            {
                id: 'client_info',
                name: 'Client Information',
                type: 'dynamic',
                required: true,
                fields: ['name', 'company', 'address', 'contact']
            },
            {
                id: 'quote_summary',
                name: 'Quote Summary',
                type: 'dynamic',
                required: true,
                fields: ['quote_number', 'date', 'expiry', 'total']
            },
            {
                id: 'items_table',
                name: 'Items Table',
                type: 'dynamic',
                required: true,
                customizable: true
            },
            {
                id: 'terms_conditions',
                name: 'Terms & Conditions',
                type: 'static',
                required: false,
                editable: true
            },
            {
                id: 'payment_info',
                name: 'Payment Information',
                type: 'static',
                required: false,
                fields: ['methods', 'terms', 'details']
            },
            {
                id: 'signatures',
                name: 'Signature Section',
                type: 'static',
                required: false,
                fields: ['client_signature', 'company_signature', 'date']
            }
        ],
        
        // Template themes
        availableThemes: [
            { id: 'professional', name: 'Professional', preview: '/images/themes/professional.png' },
            { id: 'modern', name: 'Modern', preview: '/images/themes/modern.png' },
            { id: 'classic', name: 'Classic', preview: '/images/themes/classic.png' },
            { id: 'minimal', name: 'Minimal', preview: '/images/themes/minimal.png' }
        ],
        
        // Field types for custom fields
        fieldTypes: [
            { id: 'text', name: 'Text Input', icon: '📝' },
            { id: 'textarea', name: 'Text Area', icon: '📄' },
            { id: 'number', name: 'Number', icon: '🔢' },
            { id: 'date', name: 'Date', icon: '📅' },
            { id: 'select', name: 'Dropdown', icon: '📋' },
            { id: 'checkbox', name: 'Checkbox', icon: '☑️' },
            { id: 'radio', name: 'Radio Button', icon: '🔘' },
            { id: 'file', name: 'File Upload', icon: '📎' }
        ],
        
        // Validation
        errors: {},
        isValid: false,
        
        init() {
            this.loadTemplates();
            this.initializeTemplateStructure();
        },
        
        async loadTemplates() {
            try {
                const response = await fetch('/api/quote-templates/advanced');
                if (response.ok) {
                    this.templates = await response.json();
                }
            } catch (error) {
                console.error('Failed to load templates:', error);
            }
        },
        
        initializeTemplateStructure() {
            // Set up default template structure
            this.templateStructure.layout.sections = this.availableSections
                .filter(section => section.required)
                .map(section => ({
                    ...section,
                    enabled: true,
                    order: section.id === 'company_info' ? 1 :
                           section.id === 'client_info' ? 2 :
                           section.id === 'quote_summary' ? 3 :
                           section.id === 'items_table' ? 4 : 5
                }));
        },
        
        createNewTemplate() {
            this.templateEditor.mode = 'create';
            this.templateEditor.template = JSON.parse(JSON.stringify(this.templateStructure));
            this.templateEditor.show = true;
            this.errors = {};
        },
        
        editTemplate(template) {
            this.templateEditor.mode = 'edit';
            this.templateEditor.template = JSON.parse(JSON.stringify(template));
            this.templateEditor.show = true;
            this.errors = {};
        },
        
        duplicateTemplate(template) {
            this.templateEditor.mode = 'duplicate';
            this.templateEditor.template = JSON.parse(JSON.stringify(template));
            this.templateEditor.template.basic.id = null;
            this.templateEditor.template.basic.name += ' (Copy)';
            this.templateEditor.show = true;
            this.errors = {};
        },
        
        addSection(sectionId) {
            const sectionDef = this.availableSections.find(s => s.id === sectionId);
            if (!sectionDef) return;
            
            const existingSection = this.templateEditor.template.layout.sections
                .find(s => s.id === sectionId);
            
            if (existingSection) {
                existingSection.enabled = true;
                return;
            }
            
            const newSection = {
                ...sectionDef,
                enabled: true,
                order: this.templateEditor.template.layout.sections.length + 1,
                content: '',
                style: {},
                conditions: []
            };
            
            this.templateEditor.template.layout.sections.push(newSection);
            this.sortSections();
        },
        
        removeSection(sectionId) {
            const section = this.templateEditor.template.layout.sections
                .find(s => s.id === sectionId);
            
            if (section) {
                if (section.required) {
                    section.enabled = false;
                } else {
                    this.templateEditor.template.layout.sections = 
                        this.templateEditor.template.layout.sections
                        .filter(s => s.id !== sectionId);
                }
            }
        },
        
        sortSections() {
            this.templateEditor.template.layout.sections.sort((a, b) => a.order - b.order);
        },
        
        addCustomField() {
            if (this.templateEditor.template.fields.custom.length >= this.maxCustomFields) {
                this.showError('Maximum custom fields limit reached');
                return;
            }
            
            const newField = {
                id: `custom_${Date.now()}`,
                name: '',
                label: '',
                type: 'text',
                required: false,
                placeholder: '',
                default_value: '',
                options: [],
                validation: {},
                conditions: []
            };
            
            this.templateEditor.template.fields.custom.push(newField);
        },
        
        removeCustomField(fieldId) {
            this.templateEditor.template.fields.custom = 
                this.templateEditor.template.fields.custom
                .filter(f => f.id !== fieldId);
        },
        
        addFieldOption(fieldId) {
            const field = this.templateEditor.template.fields.custom
                .find(f => f.id === fieldId);
            
            if (field && ['select', 'radio', 'checkbox'].includes(field.type)) {
                field.options.push({
                    value: '',
                    label: '',
                    default: false
                });
            }
        },
        
        addCondition(targetType, targetId) {
            const condition = {
                id: `condition_${Date.now()}`,
                type: 'show_if', // show_if, hide_if, require_if
                field: '',
                operator: 'equals',
                value: '',
                logic: 'and' // and, or
            };
            
            if (targetType === 'section') {
                const section = this.templateEditor.template.layout.sections
                    .find(s => s.id === targetId);
                if (section) {
                    section.conditions = section.conditions || [];
                    section.conditions.push(condition);
                }
            } else if (targetType === 'field') {
                const field = this.templateEditor.template.fields.custom
                    .find(f => f.id === targetId);
                if (field) {
                    field.conditions = field.conditions || [];
                    field.conditions.push(condition);
                }
            }
        },
        
        updateTheme(themeId) {
            this.templateEditor.template.styling.theme = themeId;
            this.applyThemeDefaults(themeId);
        },
        
        applyThemeDefaults(themeId) {
            const themeDefaults = {
                professional: {
                    colors: { primary: '#2563eb', secondary: '#64748b', accent: '#10b981' },
                    fonts: { heading: 'Inter', body: 'Inter' }
                },
                modern: {
                    colors: { primary: '#8b5cf6', secondary: '#6b7280', accent: '#f59e0b' },
                    fonts: { heading: 'Poppins', body: 'Roboto' }
                },
                classic: {
                    colors: { primary: '#1f2937', secondary: '#9ca3af', accent: '#dc2626' },
                    fonts: { heading: 'Times New Roman', body: 'Georgia' }
                },
                minimal: {
                    colors: { primary: '#000000', secondary: '#6b7280', accent: '#059669' },
                    fonts: { heading: 'Helvetica', body: 'Arial' }
                }
            };
            
            if (themeDefaults[themeId]) {
                Object.assign(this.templateEditor.template.styling, themeDefaults[themeId]);
            }
        },
        
        validateTemplate() {
            this.errors = {};
            
            // Validate basic info
            if (!this.templateEditor.template.basic.name.trim()) {
                this.errors.name = 'Template name is required';
            }
            
            // Validate sections
            const enabledSections = this.templateEditor.template.layout.sections
                .filter(s => s.enabled);
            
            if (enabledSections.length === 0) {
                this.errors.sections = 'At least one section must be enabled';
            }
            
            // Validate custom fields
            this.templateEditor.template.fields.custom.forEach((field, index) => {
                if (!field.name.trim()) {
                    this.errors[`custom_field_${index}_name`] = 'Field name is required';
                }
                if (!field.label.trim()) {
                    this.errors[`custom_field_${index}_label`] = 'Field label is required';
                }
            });
            
            this.isValid = Object.keys(this.errors).length === 0;
            return this.isValid;
        },
        
        async saveTemplate() {
            if (!this.validateTemplate()) {
                return;
            }
            
            try {
                const url = this.templateEditor.mode === 'edit' && this.templateEditor.template.basic.id
                    ? `/api/quote-templates/${this.templateEditor.template.basic.id}`
                    : '/api/quote-templates';
                
                const method = this.templateEditor.mode === 'edit' ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.templateEditor.template)
                });
                
                if (response.ok) {
                    const savedTemplate = await response.json();
                    
                    if (this.templateEditor.mode === 'edit') {
                        const index = this.templates.findIndex(t => t.id === savedTemplate.id);
                        if (index > -1) {
                            this.templates[index] = savedTemplate;
                        }
                    } else {
                        this.templates.push(savedTemplate);
                    }
                    
                    this.closeTemplateEditor();
                    this.showSuccess('Template saved successfully');
                } else {
                    throw new Error('Failed to save template');
                }
                
            } catch (error) {
                console.error('Template save error:', error);
                this.showError('Failed to save template');
            }
        },
        
        async deleteTemplate(templateId) {
            if (!confirm('Are you sure you want to delete this template?')) {
                return;
            }
            
            try {
                const response = await fetch(`/api/quote-templates/${templateId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                if (response.ok) {
                    this.templates = this.templates.filter(t => t.id !== templateId);
                    this.showSuccess('Template deleted successfully');
                } else {
                    throw new Error('Failed to delete template');
                }
                
            } catch (error) {
                console.error('Template delete error:', error);
                this.showError('Failed to delete template');
            }
        },
        
        previewTemplate(template) {
            // Open template preview in new window
            const previewUrl = `/quote-templates/${template.id}/preview`;
            window.open(previewUrl, '_blank', 'width=800,height=1000');
        },
        
        closeTemplateEditor() {
            this.templateEditor.show = false;
            this.templateEditor.template = null;
            this.errors = {};
        },
        
        showSuccess(message) {
            this.$dispatch('notification', {
                type: 'success',
                message: message
            });
        },
        
        showError(message) {
            this.$dispatch('notification', {
                type: 'error',
                message: message
            });
        },
        
        // Computed properties
        get enabledSections() {
            return this.templateEditor.template?.layout.sections.filter(s => s.enabled) || [];
        },
        
        get availableSectionsToAdd() {
            const enabledSectionIds = this.enabledSections.map(s => s.id);
            return this.availableSections.filter(s => !enabledSectionIds.includes(s.id));
        },
        
        get customFieldsCount() {
            return this.templateEditor.template?.fields.custom.length || 0;
        },
        
        get canAddCustomField() {
            return this.customFieldsCount < this.maxCustomFields;
        }
    }));
});