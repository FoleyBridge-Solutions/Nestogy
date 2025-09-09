@props(['field', 'value' => null, 'errors' => []])

@php
    $fieldSlug = $field['field_slug'];
    $hasError = !empty($errors[$fieldSlug]);
    $uiConfig = $field['ui_config'] ?? [];
    $inputClass = 'form-control' . ($hasError ? ' is-invalid' : '');
    
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

<div class="form-group mb-3">
    <label for="{{ $fieldSlug }}" class="form-label">
        {{ $field['label'] }}
        @if($field['is_required'])
            <span class="text-danger">*</span>
        @endif
    </label>
    
    <div class="input-group">
        <span class="input-group-text">{{ $symbol }}</span>
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
        <span class="input-group-text">{{ $currency }}</span>
    </div>
    
    @if($field['help_text'])
        <small class="form-text text-muted">{{ $field['help_text'] }}</small>
    @endif
    
    @if($hasError)
        <div class="invalid-feedback">
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