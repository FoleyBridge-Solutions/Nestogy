@props(['field', 'value' => null, 'errors' => []])

@php
    $fieldSlug = $field['field_slug'];
    $hasError = !empty($errors[$fieldSlug]);
    $uiConfig = $field['ui_config'] ?? [];
    $inputClass = 'block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm' . ($hasError ? ' border-red-500' : '');
    
    // Extract UI configuration
    $multiple = $uiConfig['multiple'] ?? false; // Allow multiple comma-separated emails
    $validateDomain = $uiConfig['validate_domain'] ?? false; // Validate domain exists
    $allowedDomains = $uiConfig['allowed_domains'] ?? []; // Restrict to specific domains
    $blockedDomains = $uiConfig['blocked_domains'] ?? []; // Block specific domains
    $showValidation = $uiConfig['show_validation'] ?? true; // Show real-time validation
    $maxEmails = $uiConfig['max_emails'] ?? 5; // Max emails when multiple is true
    
    // Format value for display
    $displayValue = $value;
    if ($multiple && is_array($value)) {
        $displayValue = implode(', ', $value);
    }
@endphp

<div class="mb-4 mb-6">
    <label for="{{ $fieldSlug }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        {{ $field['label'] }}
        @if($field['is_required'])
            <span class="text-red-600 dark:text-red-400">*</span>
        @endif
        @if($multiple)
            <small class="text-gray-600 dark:text-gray-400">(separate multiple emails with commas)</small>
        @endif
    </label>
    
    <div class="email-input-container mx-auto position-relative">
        @if($multiple)
            <div class="email-tags-input">
                <div class="email-tags" id="{{ $fieldSlug }}_tags"></div>
                <input 
                    type="text"
                    id="{{ $fieldSlug }}_input"
                    class="{{ $inputClass }} email-tag-input"
                    placeholder="{{ $field['placeholder'] ?? 'Enter email address and press comma or enter...' }}"
                    autocomplete="email"
                />
            </div>
            <input 
                type="hidden"
                id="{{ $fieldSlug }}"
                name="{{ $fieldSlug }}"
                value="{{ old($fieldSlug, $displayValue) }}"
            />
        @else
            <div class="flex">
                <span class="flex-text">
                    <i class="fas fa-envelope"></i>
                </span>
                <input 
                    type="email"
                    id="{{ $fieldSlug }}"
                    name="{{ $fieldSlug }}"
                    class="{{ $inputClass }} single-email-input"
                    value="{{ old($fieldSlug, $displayValue) }}"
                    placeholder="{{ $field['placeholder'] ?? 'Enter email address...' }}"
                    @if($field['is_required']) required @endif
                    autocomplete="email"
                />
                @if($showValidation)
                    <span class="flex-text email-validation-indicator">
                        <i class="fas fa-circle text-gray-600 dark:text-gray-400" id="{{ $fieldSlug }}_indicator"></i>
                    </span>
                @endif
            </div>
        @endif
    </div>
    
    <div class="email-validation-messages mt-1" id="{{ $fieldSlug }}_messages"></div>
    
    @if($field['help_text'])
        <small class="form-text text-gray-600 dark:text-gray-400">{{ $field['help_text'] }}</small>
    @endif
    
    @if($hasError)
        <div class="text-red-600 text-sm mt-1">
            @foreach($errors[$fieldSlug] as $error)
                {{ $error }}
            @endforeach
        </div>
    @endif
</div>

@push('styles')
<style>
    .email-tags-input {
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        padding: 0.375rem;
        min-height: 2.5rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.25rem;
        background: white;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    .email-tags-input:focus-within {
        border-color: #80bdff;
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    
    .email-tag-input {
        border: none;
        outline: none;
        flex: 1;
        min-width: 120px;
        background: transparent;
    }
    
    .email-tag {
        background: #e9ecef;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        padding: 0.25rem 0.5rem;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.875rem;
    }
    
    .email-tag.valid {
        background: #d4edda;
        border-color: #c3e6cb;
        color: #155724;
    }
    
    .email-tag.invalid {
        background: #f8d7da;
        border-color: #f1aeb5;
        color: #721c24;
    }
    
    .email-tag-remove {
        background: none;
        border: none;
        color: inherit;
        cursor: pointer;
        padding: 0;
        margin-left: 0.25rem;
        opacity: 0.7;
    }
    
    .email-tag-remove:hover {
        opacity: 1;
    }
    
    .email-validation-messages {
        font-size: 0.875rem;
    }
    
    .validation-message {
        display: block;
        margin-bottom: 0.25rem;
    }
    
    .validation-message.success {
        color: #28a745;
    }
    
    .validation-message.warning {
        color: #ffc107;
    }
    
    .validation-message.error {
        color: #dc3545;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const emailInput = document.getElementById('{{ $fieldSlug }}{{ $multiple ? "_input" : "" }}');
        const hiddenInput = document.getElementById('{{ $fieldSlug }}');
        const messagesContainer = document.getElementById('{{ $fieldSlug }}_messages');
        const indicator = document.getElementById('{{ $fieldSlug }}_indicator');
        
        const allowedDomains = @json($allowedDomains);
        const blockedDomains = @json($blockedDomains);
        const validateDomain = {{ $validateDomain ? 'true' : 'false' }};
        const showValidation = {{ $showValidation ? 'true' : 'false' }};
        
        // Email validation regex
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        // Validate single email
        function validateEmail(email) {
            const errors = [];
            const warnings = [];
            
            // Basic format validation
            if (!emailRegex.test(email)) {
                errors.push('Invalid email format');
                return { valid: false, errors, warnings };
            }
            
            const domain = email.split('@')[1].toLowerCase();
            
            // Check allowed domains
            if (allowedDomains.length > 0 && !allowedDomains.includes(domain)) {
                errors.push(`Domain "${domain}" is not allowed`);
            }
            
            // Check blocked domains
            if (blockedDomains.includes(domain)) {
                errors.push(`Domain "${domain}" is blocked`);
            }
            
            // Domain validation (simplified - real implementation would check MX records)
            if (validateDomain) {
                const commonDomains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com'];
                if (!commonDomains.includes(domain) && !domain.includes('.')) {
                    warnings.push('Domain may not exist');
                }
            }
            
            return {
                valid: errors.length === 0,
                errors,
                warnings
            };
        }
        
        // Show validation messages
        function showValidationMessages(messages, type = 'error') {
            messagesContainer.innerHTML = '';
            messages.forEach(message => {
                const div = document.createElement('div');
                div.className = `validation-message ${type}`;
                div.innerHTML = `<i class="fas fa-${type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'check-circle'}"></i> ${message}`;
                messagesContainer.appendChild(div);
            });
        }
        
        // Update validation indicator
        function updateIndicator(valid, hasWarnings = false) {
            if (!indicator) return;
            
            if (valid && !hasWarnings) {
                indicator.className = 'fas fa-check-circle text-green-600 dark:text-green-400';
            } else if (valid && hasWarnings) {
                indicator.className = 'fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400';
            } else {
                indicator.className = 'fas fa-times-circle text-red-600 dark:text-red-400';
            }
        }
        
        @if($multiple)
            const tagsContainer = document.getElementById('{{ $fieldSlug }}_tags');
            let emails = [];
            
            // Initialize with existing emails
            const existingValue = hiddenInput.value;
            if (existingValue) {
                emails = existingValue.split(',').map(email => email.trim()).filter(Boolean);
                renderTags();
            }
            
            // Render email tags
            function renderTags() {
                tagsContainer.innerHTML = '';
                emails.forEach((email, index) => {
                    const validation = validateEmail(email);
                    const tag = document.createElement('div');
                    tag.className = `email-tag ${validation.valid ? 'valid' : 'invalid'}`;
                    tag.innerHTML = `
                        ${email}
                        <button type="button" class="email-tag-remove" data-index="${index}">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    tagsContainer.appendChild(tag);
                });
                
                // Update hidden input
                hiddenInput.value = emails.join(', ');
            }
            
            // Add email tag
            function addEmail(email) {
                email = email.trim();
                if (!email || emails.includes(email)) return;
                
                if (emails.length >= {{ $maxEmails }}) {
                    showValidationMessages([`Maximum ${{{ $maxEmails }}} emails allowed`], 'warning');
                    return;
                }
                
                emails.push(email);
                renderTags();
                emailInput.value = '';
                messagesContainer.innerHTML = '';
            }
            
            // Remove email tag
            tagsContainer.addEventListener('click', function(e) {
                if (e.target.closest('.email-tag-remove')) {
                    const index = parseInt(e.target.closest('.email-tag-remove').dataset.index);
                    emails.splice(index, 1);
                    renderTags();
                }
            });
            
            // Handle input
            emailInput.addEventListener('keydown', function(e) {
                if (e.key === ',' || e.key === 'Enter' || e.key === 'Tab') {
                    e.preventDefault();
                    const email = this.value.trim();
                    if (email) {
                        const validation = validateEmail(email);
                        if (validation.valid) {
                            addEmail(email);
                        } else {
                            showValidationMessages(validation.errors, 'error');
                        }
                    }
                } else if (e.key === 'Backspace' && !this.value && emails.length > 0) {
                    emails.pop();
                    renderTags();
                }
            });
            
            // Handle paste
            emailInput.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                const pastedEmails = pastedText.split(/[,;\s]+/).filter(Boolean);
                
                pastedEmails.forEach(email => {
                    const validation = validateEmail(email);
                    if (validation.valid) {
                        addEmail(email);
                    }
                });
            });
            
        @else
            // Single email validation
            let validationTimeout;
            
            function validateSingleEmail() {
                const email = emailInput.value.trim();
                
                if (!email) {
                    messagesContainer.innerHTML = '';
                    if (indicator) indicator.className = 'fas fa-circle text-gray-600 dark:text-gray-400';
                    return;
                }
                
                const validation = validateEmail(email);
                
                if (showValidation) {
                    if (validation.errors.length > 0) {
                        showValidationMessages(validation.errors, 'error');
                        updateIndicator(false);
                    } else if (validation.warnings.length > 0) {
                        showValidationMessages(validation.warnings, 'warning');
                        updateIndicator(true, true);
                    } else {
                        messagesContainer.innerHTML = '';
                        updateIndicator(true);
                    }
                }
            }
            
            emailInput.addEventListener('input', function() {
                clearTimeout(validationTimeout);
                validationTimeout = setTimeout(validateSingleEmail, 500);
            });
            
            emailInput.addEventListener('blur', validateSingleEmail);
        @endif
    });
</script>
@endpush
