@props(['field', 'value' => null, 'errors' => []])

@php
    $fieldSlug = $field['field_slug'];
    $hasError = !empty($errors[$fieldSlug]);
    $uiConfig = $field['ui_config'] ?? [];
    
    // Extract UI configuration
    $checkboxStyle = $uiConfig['style'] ?? 'default'; // default, switch, button
    $inline = $uiConfig['inline'] ?? false; // Display options inline
    $columns = $uiConfig['columns'] ?? 1; // Number of columns for layout
    $selectAll = $uiConfig['select_all'] ?? false; // Show select all option
    $color = $uiConfig['color'] ?? 'primary'; // Bootstrap color theme
    $size = $uiConfig['size'] ?? 'normal'; // sm, normal, lg
    
    // Handle single checkbox vs checkbox group
    $options = $field['options'] ?? [];
    $isSingle = empty($options);
    
    // Normalize options for checkbox group
    $formattedOptions = [];
    if (!$isSingle) {
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
    }
    
    // Handle selected values
    $selectedValues = [];
    if ($isSingle) {
        $isChecked = (bool) old($fieldSlug, $value);
    } else {
        if ($value) {
            if (is_array($value)) {
                $selectedValues = $value;
            } elseif (is_string($value)) {
                $selectedValues = explode(',', $value);
            }
        }
        $selectedValues = array_map('trim', $selectedValues);
    }
    
    // CSS classes based on configuration
    $containerClass = 'checkbox-container';
    if ($inline) $containerClass .= ' checkbox-inline';
    if ($columns > 1) $containerClass .= ' checkbox-columns';
    
    $checkboxClass = 'form-check-input';
    if ($size === 'sm') $checkboxClass .= ' form-check-input-sm';
    if ($size === 'lg') $checkboxClass .= ' form-check-input-lg';
    if ($hasError) $checkboxClass .= ' is-invalid';
@endphp

<div class="form-group mb-3">
    @if(!$isSingle)
        <label class="form-label">
            {{ $field['label'] }}
            @if($field['is_required'])
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif
    
    <div class="{{ $containerClass }}" 
         @if($columns > 1) style="column-count: {{ $columns }}; column-gap: 1.5rem;" @endif>
        
        @if($selectAll && !$isSingle)
            {{-- Select All option --}}
            <div class="form-check {{ $checkboxStyle === 'switch' ? 'form-switch' : '' }} select-all-option">
                <input 
                    type="checkbox"
                    id="{{ $fieldSlug }}_select_all"
                    class="form-check-input select-all-checkbox"
                />
                <label class="form-check-label fw-bold" for="{{ $fieldSlug }}_select_all">
                    Select All
                </label>
            </div>
            <hr class="my-2">
        @endif
        
        @if($isSingle)
            {{-- Single checkbox --}}
            <div class="form-check {{ $checkboxStyle === 'switch' ? 'form-switch' : '' }}">
                <input 
                    type="hidden"
                    name="{{ $fieldSlug }}"
                    value="0"
                />
                <input 
                    type="checkbox"
                    id="{{ $fieldSlug }}"
                    name="{{ $fieldSlug }}"
                    class="{{ $checkboxClass }}"
                    value="1"
                    @if($isChecked) checked @endif
                    @if($field['is_required']) required @endif
                />
                <label class="form-check-label" for="{{ $fieldSlug }}">
                    {{ $field['label'] }}
                    @if($field['is_required'])
                        <span class="text-danger">*</span>
                    @endif
                </label>
            </div>
        @else
            {{-- Checkbox group --}}
            @foreach($formattedOptions as $index => $option)
                @php
                    $optionId = $fieldSlug . '_' . $index;
                    $isSelected = in_array($option['value'], $selectedValues);
                @endphp
                
                @if($checkboxStyle === 'button')
                    {{-- Button style checkbox --}}
                    <div class="form-check-button">
                        <input 
                            type="checkbox"
                            id="{{ $optionId }}"
                            name="{{ $fieldSlug }}[]"
                            class="btn-check checkbox-option"
                            value="{{ $option['value'] }}"
                            @if($isSelected) checked @endif
                            @if($option['disabled']) disabled @endif
                            autocomplete="off"
                        />
                        <label class="btn btn-outline-{{ $color }} {{ $size === 'sm' ? 'btn-sm' : ($size === 'lg' ? 'btn-lg' : '') }}" for="{{ $optionId }}">
                            {{ $option['label'] }}
                        </label>
                    </div>
                @else
                    {{-- Standard or switch checkbox --}}
                    <div class="form-check {{ $checkboxStyle === 'switch' ? 'form-switch' : '' }} {{ $inline ? 'form-check-inline' : '' }}">
                        <input 
                            type="checkbox"
                            id="{{ $optionId }}"
                            name="{{ $fieldSlug }}[]"
                            class="{{ $checkboxClass }} checkbox-option"
                            value="{{ $option['value'] }}"
                            @if($isSelected) checked @endif
                            @if($option['disabled']) disabled @endif
                        />
                        <label class="form-check-label" for="{{ $optionId }}">
                            {{ $option['label'] }}
                            @if($option['description'])
                                <small class="form-text text-muted d-block">{{ $option['description'] }}</small>
                            @endif
                        </label>
                    </div>
                @endif
            @endforeach
        @endif
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
    .checkbox-inline .form-check {
        display: inline-block;
        margin-right: 1rem;
        margin-bottom: 0.5rem;
    }
    
    .checkbox-columns {
        column-fill: balance;
    }
    
    .checkbox-columns .form-check {
        break-inside: avoid;
        margin-bottom: 0.5rem;
    }
    
    .form-check-button {
        display: inline-block;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .form-check-input-sm {
        font-size: 0.875rem;
    }
    
    .form-check-input-lg {
        font-size: 1.25rem;
    }
    
    .select-all-option {
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    /* Animated checkbox styles */
    .form-check-input:checked {
        animation: checkboxPulse 0.3s ease;
    }
    
    @keyframes checkboxPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    /* Custom switch colors */
    @foreach(['primary', 'secondary', 'success', 'danger', 'warning', 'info'] as $colorName)
    .form-switch-{{ $colorName }} .form-check-input:checked {
        background-color: var(--bs-{{ $colorName }});
        border-color: var(--bs-{{ $colorName }});
    }
    @endforeach
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if(!$isSingle)
            const checkboxContainer = document.querySelector('#{{ $fieldSlug }}').closest('.checkbox-container');
            const selectAllCheckbox = document.getElementById('{{ $fieldSlug }}_select_all');
            const optionCheckboxes = checkboxContainer.querySelectorAll('.checkbox-option');
            
            // Update select all state
            function updateSelectAllState() {
                if (!selectAllCheckbox) return;
                
                const checkedOptions = Array.from(optionCheckboxes).filter(cb => cb.checked && !cb.disabled);
                const enabledOptions = Array.from(optionCheckboxes).filter(cb => !cb.disabled);
                
                if (checkedOptions.length === 0) {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = false;
                } else if (checkedOptions.length === enabledOptions.length) {
                    selectAllCheckbox.checked = true;
                    selectAllCheckbox.indeterminate = false;
                } else {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = true;
                }
            }
            
            // Handle select all
            selectAllCheckbox?.addEventListener('change', function() {
                const shouldCheck = this.checked;
                optionCheckboxes.forEach(checkbox => {
                    if (!checkbox.disabled) {
                        checkbox.checked = shouldCheck;
                        // Trigger change event for each checkbox
                        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
                updateSelectAllState();
            });
            
            // Handle individual checkbox changes
            optionCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateSelectAllState();
                    
                    // Validate required field
                    @if($field['is_required'])
                    validateRequired();
                    @endif
                });
            });
            
            // Required field validation
            @if($field['is_required'])
            function validateRequired() {
                const checkedCount = Array.from(optionCheckboxes).filter(cb => cb.checked).length;
                const container = checkboxContainer.closest('.form-group');
                let errorDiv = container.querySelector('.checkbox-required-error');
                
                if (checkedCount === 0) {
                    if (!errorDiv) {
                        errorDiv = document.createElement('div');
                        errorDiv.className = 'checkbox-required-error invalid-feedback d-block';
                        errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please select at least one option.';
                        container.appendChild(errorDiv);
                    }
                    
                    // Add invalid class to checkboxes
                    optionCheckboxes.forEach(cb => cb.classList.add('is-invalid'));
                } else {
                    if (errorDiv) {
                        errorDiv.remove();
                    }
                    
                    // Remove invalid class from checkboxes
                    optionCheckboxes.forEach(cb => cb.classList.remove('is-invalid'));
                }
            }
            
            // Initial validation
            validateRequired();
            @endif
            
            // Form submission validation
            const form = checkboxContainer.closest('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    @if($field['is_required'])
                    const checkedCount = Array.from(optionCheckboxes).filter(cb => cb.checked).length;
                    if (checkedCount === 0) {
                        e.preventDefault();
                        validateRequired();
                        
                        // Focus first checkbox
                        if (optionCheckboxes.length > 0) {
                            optionCheckboxes[0].focus();
                        }
                        return false;
                    }
                    @endif
                });
            }
            
            // Initialize select all state
            updateSelectAllState();
        @endif
        
        // Keyboard navigation
        const checkboxes = document.querySelectorAll('input[name="{{ $fieldSlug }}{{ $isSingle ? "" : "[]" }}"]');
        checkboxes.forEach((checkbox, index) => {
            checkbox.addEventListener('keydown', function(e) {
                let nextIndex;
                
                switch(e.key) {
                    case 'ArrowDown':
                    case 'ArrowRight':
                        e.preventDefault();
                        nextIndex = (index + 1) % checkboxes.length;
                        checkboxes[nextIndex].focus();
                        break;
                        
                    case 'ArrowUp':
                    case 'ArrowLeft':
                        e.preventDefault();
                        nextIndex = index === 0 ? checkboxes.length - 1 : index - 1;
                        checkboxes[nextIndex].focus();
                        break;
                        
                    case 'Home':
                        e.preventDefault();
                        checkboxes[0].focus();
                        break;
                        
                    case 'End':
                        e.preventDefault();
                        checkboxes[checkboxes.length - 1].focus();
                        break;
                }
            });
        });
    });
</script>
@endpush