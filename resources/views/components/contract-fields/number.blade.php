@props(['field', 'value' => null, 'errors' => []])

@php
    $fieldSlug = $field['field_slug'];
    $hasError = !empty($errors[$fieldSlug]);
    $uiConfig = $field['ui_config'] ?? [];
    $inputClass = 'form-control' . ($hasError ? ' is-invalid' : '');
    
    // Extract UI configuration
    $min = $uiConfig['min'] ?? null;
    $max = $uiConfig['max'] ?? null;
    $step = $uiConfig['step'] ?? 1;
    $precision = $uiConfig['precision'] ?? 0; // Decimal places
    $prefix = $uiConfig['prefix'] ?? null; // Display prefix like "#", "Qty:"
    $suffix = $uiConfig['suffix'] ?? null; // Display suffix like "units", "items"
    $thousandsSeparator = $uiConfig['thousands_separator'] ?? false;
    $showSpinner = $uiConfig['show_spinner'] ?? true;
    
    // Format value for display
    $displayValue = $value;
    if ($value !== null && is_numeric($value)) {
        if ($precision > 0) {
            $displayValue = number_format($value, $precision, '.', $thousandsSeparator ? ',' : '');
        } else {
            $displayValue = $thousandsSeparator ? number_format($value, 0, '.', ',') : $value;
        }
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
        @if($prefix)
            <span class="input-group-text">{{ $prefix }}</span>
        @endif
        
        <div class="number-input-container position-relative">
            <input 
                type="number"
                id="{{ $fieldSlug }}"
                name="{{ $fieldSlug }}"
                class="{{ $inputClass }} number-input"
                value="{{ old($fieldSlug, $displayValue) }}"
                placeholder="{{ $field['placeholder'] ?? '0' }}"
                @if($field['is_required']) required @endif
                @if($min !== null) min="{{ $min }}" @endif
                @if($max !== null) max="{{ $max }}" @endif
                step="{{ $step }}"
                data-precision="{{ $precision }}"
                @if($thousandsSeparator) data-thousands-separator="true" @endif
                autocomplete="off"
            />
            
            @if($showSpinner)
                <div class="number-spinner">
                    <button type="button" class="number-increment" tabindex="-1">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                    <button type="button" class="number-decrement" tabindex="-1">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
            @endif
        </div>
        
        @if($suffix)
            <span class="input-group-text">{{ $suffix }}</span>
        @endif
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

@push('styles')
<style>
    .number-input-container {
        flex: 1;
    }
    
    .number-input {
        padding-right: 2.5rem;
    }
    
    .number-spinner {
        position: absolute;
        right: 0.5rem;
        top: 50%;
        transform: translateY(-50%);
        display: flex;
        flex-direction: column;
        gap: 1px;
    }
    
    .number-increment,
    .number-decrement {
        background: #f8f9fa;
        border: 1px solid #ced4da;
        width: 1.5rem;
        height: 0.8rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 0.7rem;
        color: #6c757d;
        transition: all 0.2s ease;
    }
    
    .number-increment:hover,
    .number-decrement:hover {
        background: #e9ecef;
        color: #495057;
    }
    
    .number-increment {
        border-top-left-radius: 0.25rem;
        border-top-right-radius: 0.25rem;
        border-bottom: none;
    }
    
    .number-decrement {
        border-bottom-left-radius: 0.25rem;
        border-bottom-right-radius: 0.25rem;
        border-top: none;
    }
    
    /* Hide browser default spinner */
    .number-input::-webkit-outer-spin-button,
    .number-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    
    .number-input[type=number] {
        -moz-appearance: textfield;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const numberInput = document.getElementById('{{ $fieldSlug }}');
        const precision = parseInt(numberInput.dataset.precision) || 0;
        const thousandsSeparator = numberInput.hasAttribute('data-thousands-separator');
        const min = parseFloat(numberInput.getAttribute('min'));
        const max = parseFloat(numberInput.getAttribute('max'));
        const step = parseFloat(numberInput.getAttribute('step')) || 1;
        
        // Format number for display
        function formatNumber(value) {
            if (!value || isNaN(value)) return '';
            
            const num = parseFloat(value);
            
            if (precision > 0) {
                return thousandsSeparator ? 
                    num.toLocaleString(undefined, { minimumFractionDigits: precision, maximumFractionDigits: precision }) :
                    num.toFixed(precision);
            } else {
                return thousandsSeparator ? num.toLocaleString() : num.toString();
            }
        }
        
        // Parse formatted number back to raw value
        function parseNumber(value) {
            if (!value) return '';
            return value.replace(/,/g, '');
        }
        
        // Validate number constraints
        function validateNumber(value) {
            if (!value || isNaN(value)) return value;
            
            let num = parseFloat(value);
            
            if (!isNaN(min) && num < min) num = min;
            if (!isNaN(max) && num > max) num = max;
            
            return num;
        }
        
        // Handle input formatting
        let isFormatting = false;
        numberInput.addEventListener('input', function() {
            if (isFormatting) return;
            
            isFormatting = true;
            const rawValue = parseNumber(this.value);
            const validValue = validateNumber(rawValue);
            
            if (rawValue !== this.value && rawValue !== '') {
                this.value = formatNumber(validValue);
            }
            isFormatting = false;
        });
        
        // Handle blur - final formatting
        numberInput.addEventListener('blur', function() {
            const rawValue = parseNumber(this.value);
            const validValue = validateNumber(rawValue);
            
            if (validValue !== '' && !isNaN(validValue)) {
                this.value = formatNumber(validValue);
            }
        });
        
        // Handle focus - show raw value for editing
        numberInput.addEventListener('focus', function() {
            if (thousandsSeparator) {
                this.value = parseNumber(this.value);
            }
        });
        
        // Custom spinner controls
        @if($showSpinner)
            const incrementBtn = numberInput.parentNode.querySelector('.number-increment');
            const decrementBtn = numberInput.parentNode.querySelector('.number-decrement');
            
            incrementBtn?.addEventListener('click', function(e) {
                e.preventDefault();
                const currentValue = parseFloat(parseNumber(numberInput.value)) || 0;
                const newValue = validateNumber(currentValue + step);
                numberInput.value = formatNumber(newValue);
                numberInput.dispatchEvent(new Event('input', { bubbles: true }));
                numberInput.focus();
            });
            
            decrementBtn?.addEventListener('click', function(e) {
                e.preventDefault();
                const currentValue = parseFloat(parseNumber(numberInput.value)) || 0;
                const newValue = validateNumber(currentValue - step);
                numberInput.value = formatNumber(newValue);
                numberInput.dispatchEvent(new Event('input', { bubbles: true }));
                numberInput.focus();
            });
        @endif
        
        // Prevent invalid characters
        numberInput.addEventListener('keypress', function(e) {
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
            
            // Allow decimal point for precision > 0
            if (precision > 0 && char === '.' && currentValue.indexOf('.') === -1) {
                return;
            }
            
            // Allow minus sign at the beginning (if min allows negative)
            if (char === '-' && currentValue.length === 0 && (isNaN(min) || min < 0)) {
                return;
            }
            
            // Allow comma for thousands separator
            if (thousandsSeparator && char === ',') {
                return;
            }
            
            // Prevent all other characters
            e.preventDefault();
        });
        
        // Handle form submission - ensure raw numeric value
        const form = numberInput.closest('form');
        if (form) {
            form.addEventListener('submit', function() {
                const rawValue = parseNumber(numberInput.value);
                if (rawValue !== '' && !isNaN(rawValue)) {
                    numberInput.value = parseFloat(rawValue);
                }
            });
        }
    });
</script>
@endpush