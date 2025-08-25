@props(['field', 'value' => null, 'errors' => []])

@php
    $fieldSlug = $field['field_slug'];
    $hasError = !empty($errors[$fieldSlug]);
    $uiConfig = $field['ui_config'] ?? [];
    
    // Extract UI configuration
    $radioStyle = $uiConfig['style'] ?? 'default'; // default, button, card
    $inline = $uiConfig['inline'] ?? false; // Display options inline
    $columns = $uiConfig['columns'] ?? 1; // Number of columns for layout
    $color = $uiConfig['color'] ?? 'primary'; // Bootstrap color theme
    $size = $uiConfig['size'] ?? 'normal'; // sm, normal, lg
    $showOther = $uiConfig['show_other'] ?? false; // Show "Other" option with text input
    
    // Get options
    $options = $field['options'] ?? [];
    
    // Normalize options
    $formattedOptions = [];
    foreach ($options as $option) {
        if (is_string($option)) {
            $formattedOptions[] = [
                'value' => $option,
                'label' => $option,
                'description' => null,
                'disabled' => false
            ];
        } else {
            $formattedOptions[] = [
                'value' => $option['value'] ?? $option,
                'label' => $option['label'] ?? $option['text'] ?? $option['value'] ?? $option,
                'description' => $option['description'] ?? null,
                'disabled' => $option['disabled'] ?? false
            ];
        }
    }
    
    // Add "Other" option if enabled
    if ($showOther) {
        $formattedOptions[] = [
            'value' => '_other_',
            'label' => 'Other',
            'description' => null,
            'disabled' => false
        ];
    }
    
    // Handle selected value
    $selectedValue = old($fieldSlug, $value);
    $otherValue = old($fieldSlug . '_other', '');
    $isOtherSelected = $selectedValue === '_other_' || (!in_array($selectedValue, array_column($formattedOptions, 'value')) && $selectedValue);
    
    if ($isOtherSelected && $selectedValue !== '_other_') {
        $otherValue = $selectedValue;
        $selectedValue = '_other_';
    }
    
    // CSS classes based on configuration
    $containerClass = 'radio-container';
    if ($inline) $containerClass .= ' radio-inline';
    if ($columns > 1) $containerClass .= ' radio-columns';
    
    $radioClass = 'form-check-input';
    if ($size === 'sm') $radioClass .= ' form-check-input-sm';
    if ($size === 'lg') $radioClass .= ' form-check-input-lg';
    if ($hasError) $radioClass .= ' is-invalid';
@endphp

<div class="form-group mb-3">
    <label class="form-label">
        {{ $field['label'] }}
        @if($field['is_required'])
            <span class="text-danger">*</span>
        @endif
    </label>
    
    <div class="{{ $containerClass }}" 
         @if($columns > 1) style="column-count: {{ $columns }}; column-gap: 1.5rem;" @endif>
        
        @foreach($formattedOptions as $index => $option)
            @php
                $optionId = $fieldSlug . '_' . $index;
                $isSelected = (string) $option['value'] === (string) $selectedValue;
                $isOther = $option['value'] === '_other_';
            @endphp
            
            @if($radioStyle === 'button')
                {{-- Button style radio --}}
                <div class="form-check-button">
                    <input 
                        type="radio"
                        id="{{ $optionId }}"
                        name="{{ $fieldSlug }}"
                        class="btn-check radio-option"
                        value="{{ $option['value'] }}"
                        @if($isSelected) checked @endif
                        @if($option['disabled']) disabled @endif
                        @if($field['is_required']) required @endif
                        autocomplete="off"
                    />
                    <label class="btn btn-outline-{{ $color }} {{ $size === 'sm' ? 'btn-sm' : ($size === 'lg' ? 'btn-lg' : '') }}" for="{{ $optionId }}">
                        {{ $option['label'] }}
                    </label>
                </div>
            @elseif($radioStyle === 'card')
                {{-- Card style radio --}}
                <div class="form-check-card">
                    <input 
                        type="radio"
                        id="{{ $optionId }}"
                        name="{{ $fieldSlug }}"
                        class="form-check-input radio-option"
                        value="{{ $option['value'] }}"
                        @if($isSelected) checked @endif
                        @if($option['disabled']) disabled @endif
                        @if($field['is_required']) required @endif
                    />
                    <label class="card {{ $isSelected ? 'border-' . $color : '' }}" for="{{ $optionId }}">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <div class="form-check-input-placeholder me-2"></div>
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">{{ $option['label'] }}</h6>
                                    @if($option['description'])
                                        <p class="card-text text-muted small">{{ $option['description'] }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </label>
                </div>
            @else
                {{-- Standard radio --}}
                <div class="form-check {{ $inline ? 'form-check-inline' : '' }}">
                    <input 
                        type="radio"
                        id="{{ $optionId }}"
                        name="{{ $fieldSlug }}"
                        class="{{ $radioClass }} radio-option"
                        value="{{ $option['value'] }}"
                        @if($isSelected) checked @endif
                        @if($option['disabled']) disabled @endif
                        @if($field['is_required']) required @endif
                    />
                    <label class="form-check-label" for="{{ $optionId }}">
                        {{ $option['label'] }}
                        @if($option['description'])
                            <small class="form-text text-muted d-block">{{ $option['description'] }}</small>
                        @endif
                    </label>
                </div>
            @endif
            
            {{-- Other input field --}}
            @if($isOther && $showOther)
                <div class="other-input-container mt-2 ms-4" id="{{ $fieldSlug }}_other_container" style="{{ $isSelected ? '' : 'display: none;' }}">
                    <input 
                        type="text"
                        id="{{ $fieldSlug }}_other"
                        name="{{ $fieldSlug }}_other"
                        class="form-control form-control-sm"
                        placeholder="Please specify..."
                        value="{{ $otherValue }}"
                    />
                </div>
            @endif
        @endforeach
    </div>
    
    @if($field['help_text'])
        <small class="form-text text-muted">{{ $field['help_text'] }}</small>
    @endif
    
    @if($hasError)
        <div class="invalid-feedback d-block">
            @foreach($errors[$fieldSlug] as $error)
                {{ $error }}
            @endforeach
        </div>
    @endif
</div>

@push('styles')
<style>
    .radio-inline .form-check {
        display: inline-block;
        margin-right: 1rem;
        margin-bottom: 0.5rem;
    }
    
    .radio-columns {
        column-fill: balance;
    }
    
    .radio-columns .form-check {
        break-inside: avoid;
        margin-bottom: 0.5rem;
    }
    
    .form-check-button {
        display: inline-block;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .form-check-card {
        margin-bottom: 1rem;
    }
    
    .form-check-card .card {
        cursor: pointer;
        transition: all 0.2s ease;
        border-width: 2px;
    }
    
    .form-check-card .card:hover {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .form-check-card input[type="radio"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    
    .form-check-card .form-check-input-placeholder {
        width: 20px;
        height: 20px;
        border: 2px solid #dee2e6;
        border-radius: 50%;
        position: relative;
        margin-top: 2px;
    }
    
    .form-check-card input[type="radio"]:checked + label .form-check-input-placeholder {
        border-color: var(--bs-primary);
        background-color: var(--bs-primary);
    }
    
    .form-check-card input[type="radio"]:checked + label .form-check-input-placeholder::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: white;
    }
    
    .form-check-input-sm {
        font-size: 0.875rem;
    }
    
    .form-check-input-lg {
        font-size: 1.25rem;
    }
    
    /* Animated radio styles */
    .form-check-input:checked {
        animation: radioPulse 0.3s ease;
    }
    
    @keyframes radioPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    .other-input-container {
        animation: fadeInDown 0.3s ease;
    }
    
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const radioContainer = document.querySelector('.radio-container');
        const radioOptions = radioContainer.querySelectorAll('.radio-option');
        const otherContainer = document.getElementById('{{ $fieldSlug }}_other_container');
        const otherInput = document.getElementById('{{ $fieldSlug }}_other');
        const hiddenInput = document.createElement('input');
        
        // Create hidden input for other value
        if (otherInput) {
            hiddenInput.type = 'hidden';
            hiddenInput.name = '{{ $fieldSlug }}_original';
            radioContainer.appendChild(hiddenInput);
        }
        
        // Handle radio option changes
        radioOptions.forEach(radio => {
            radio.addEventListener('change', function() {
                if (otherContainer) {
                    if (this.value === '_other_') {
                        // Show other input
                        otherContainer.style.display = 'block';
                        otherInput.focus();
                        otherInput.required = {{ $field['is_required'] ? 'true' : 'false' }};
                        
                        // Update the radio value with other input content
                        updateOtherValue();
                    } else {
                        // Hide other input
                        otherContainer.style.display = 'none';
                        otherInput.required = false;
                        otherInput.value = '';
                    }
                }
                
                // Update card styles for card layout
                @if($radioStyle === 'card')
                updateCardStyles();
                @endif
            });
        });
        
        // Handle other input changes
        if (otherInput) {
            otherInput.addEventListener('input', updateOtherValue);
            otherInput.addEventListener('blur', updateOtherValue);
        }
        
        function updateOtherValue() {
            const otherRadio = document.querySelector('input[name="{{ $fieldSlug }}"][value="_other_"]');
            if (otherRadio && otherRadio.checked) {
                const otherText = otherInput.value.trim();
                if (otherText) {
                    // Store original value
                    hiddenInput.value = otherText;
                    // Update radio value
                    otherRadio.value = otherText;
                } else {
                    otherRadio.value = '_other_';
                    hiddenInput.value = '';
                }
            }
        }
        
        @if($radioStyle === 'card')
        function updateCardStyles() {
            const cards = radioContainer.querySelectorAll('.form-check-card .card');
            cards.forEach((card, index) => {
                const radio = radioOptions[index];
                if (radio.checked) {
                    card.classList.add('border-{{ $color }}');
                    card.style.backgroundColor = 'rgba(var(--bs-{{ $color }}-rgb), 0.1)';
                } else {
                    card.classList.remove('border-{{ $color }}');
                    card.style.backgroundColor = '';
                }
            });
        }
        
        // Initialize card styles
        updateCardStyles();
        @endif
        
        // Form validation
        const form = radioContainer.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                @if($field['is_required'])
                const selectedRadio = radioContainer.querySelector('input[name="{{ $fieldSlug }}"]:checked');
                
                if (!selectedRadio) {
                    e.preventDefault();
                    
                    // Show validation error
                    const container = radioContainer.closest('.form-group');
                    let errorDiv = container.querySelector('.radio-required-error');
                    
                    if (!errorDiv) {
                        errorDiv = document.createElement('div');
                        errorDiv.className = 'radio-required-error invalid-feedback d-block';
                        errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please select an option.';
                        container.appendChild(errorDiv);
                    }
                    
                    // Focus first radio
                    if (radioOptions.length > 0) {
                        radioOptions[0].focus();
                    }
                    
                    return false;
                } else if (selectedRadio.value === '_other_' && otherInput && !otherInput.value.trim()) {
                    e.preventDefault();
                    
                    // Show other field validation error
                    otherInput.classList.add('is-invalid');
                    otherInput.focus();
                    
                    return false;
                } else {
                    // Remove validation errors
                    const container = radioContainer.closest('.form-group');
                    const errorDiv = container.querySelector('.radio-required-error');
                    if (errorDiv) {
                        errorDiv.remove();
                    }
                    
                    if (otherInput) {
                        otherInput.classList.remove('is-invalid');
                    }
                    
                    // Ensure other value is updated
                    updateOtherValue();
                }
                @endif
            });
        }
        
        // Keyboard navigation
        radioOptions.forEach((radio, index) => {
            radio.addEventListener('keydown', function(e) {
                let nextIndex;
                
                switch(e.key) {
                    case 'ArrowDown':
                    case 'ArrowRight':
                        e.preventDefault();
                        nextIndex = (index + 1) % radioOptions.length;
                        radioOptions[nextIndex].focus();
                        break;
                        
                    case 'ArrowUp':
                    case 'ArrowLeft':
                        e.preventDefault();
                        nextIndex = index === 0 ? radioOptions.length - 1 : index - 1;
                        radioOptions[nextIndex].focus();
                        break;
                        
                    case 'Home':
                        e.preventDefault();
                        radioOptions[0].focus();
                        break;
                        
                    case 'End':
                        e.preventDefault();
                        radioOptions[radioOptions.length - 1].focus();
                        break;
                }
            });
        });
        
        // Initialize other input visibility
        const checkedRadio = radioContainer.querySelector('input[name="{{ $fieldSlug }}"]:checked');
        if (checkedRadio && checkedRadio.value === '_other_' && otherContainer) {
            otherContainer.style.display = 'block';
            otherInput.required = {{ $field['is_required'] ? 'true' : 'false' }};
        }
    });
</script>
@endpush