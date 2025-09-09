@props(['field', 'value' => null, 'errors' => []])

@php
    $fieldSlug = $field['field_slug'];
    $hasError = !empty($errors[$fieldSlug]);
    $uiConfig = $field['ui_config'] ?? [];
    $inputClass = 'block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm' . ($hasError ? ' border-red-500' : '');
    
    // Extract UI configuration
    $currency = $uiConfig['currency'] ?? 'USD';
    $symbol = $uiConfig['symbol'] ?? '$';
    $precision = $uiConfig['precision'] ?? 2;
    $min = $uiConfig['min'] ?? 0;
    $max = $uiConfig['max'] ?? null;
    $step = $uiConfig['step'] ?? 0.01;
    
    // Format value for display
    $displayValue = $value;
    if ($value && is_numeric($value)) {
        $displayValue = number_format($value, $precision, '.', '');
    }
@endphp

<div class="mb-4 mb-6">
    <label for="{{ $fieldSlug }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        {{ $field['label'] }}
        @if($field['is_required'])
            <span class="text-red-600 dark:text-red-400">*</span>
        @endif
    </label>
    
    <div class="flex">
        <span class="flex-text">{{ $symbol }}</span>
        <input 
            type="number"
            id="{{ $fieldSlug }}"
            name="{{ $fieldSlug }}"
            class="{{ $inputClass }} currency-input"
            value="{{ old($fieldSlug, $displayValue) }}"
            placeholder="{{ $field['placeholder'] ?? '0.00' }}"
            @if($field['is_required']) required @endif
            min="{{ $min }}"
            @if($max) max="{{ $max }}" @endif
            step="{{ $step }}"
            data-currency="{{ $currency }}"
            data-symbol="{{ $symbol }}"
            data-precision="{{ $precision }}"
        />
        <span class="flex-text">{{ $currency }}</span>
    </div>
    
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const currencyInput = document.getElementById('{{ $fieldSlug }}');
        
        // Format currency input as user types
        currencyInput.addEventListener('input', function() {
            let value = this.value.replace(/[^\d.-]/g, '');
            
            if (value && !isNaN(value)) {
                const numValue = parseFloat(value);
                if (numValue >= 0) {
                    // Update the raw value for form submission
                    this.value = numValue.toFixed({{ $precision }});
                }
            }
        });
        
        // Format on blur
        currencyInput.addEventListener('blur', function() {
            let value = this.value.replace(/[^\d.-]/g, '');
            
            if (value && !isNaN(value)) {
                const numValue = parseFloat(value);
                if (numValue >= 0) {
                    this.value = numValue.toFixed({{ $precision }});
                }
            } else if (!this.required) {
                this.value = '';
            }
        });
        
        // Prevent invalid characters
        currencyInput.addEventListener('keypress', function(e) {
            const char = String.fromCharCode(e.which);
            const currentValue = this.value;
            
            // Allow control keys
            if (e.ctrlKey || e.metaKey || e.which === 8 || e.which === 9 || e.which === 46) {
                return;
            }
            
            // Allow digits
            if (char >= '0' && char <= '9') {
                return;
            }
            
            // Allow decimal point (only one)
            if (char === '.' && currentValue.indexOf('.') === -1) {
                return;
            }
            
            // Allow minus sign at the beginning (if min allows negative)
            if (char === '-' && currentValue.length === 0 && {{ $min }} < 0) {
                return;
            }
            
            // Prevent all other characters
            e.preventDefault();
        });
    });
</script>
@endpush
