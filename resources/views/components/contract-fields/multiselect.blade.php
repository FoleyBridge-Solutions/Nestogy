@props(['field', 'value' => null, 'errors' => []])

@php
    $fieldSlug = $field['field_slug'];
    $hasError = !empty($errors[$fieldSlug]);
    $uiConfig = $field['ui_config'] ?? [];
    $selectClass = 'form-select' . ($hasError ? ' is-invalid' : '');
    
    // Extract UI configuration
    $searchable = $uiConfig['searchable'] ?? true; // Always searchable for multiselect
    $placeholder = $field['placeholder'] ?? 'Select options...';
    $options = $field['options'] ?? [];
    $maxItems = $uiConfig['max_items'] ?? null; // Limit number of selections
    $minItems = $uiConfig['min_items'] ?? null; // Minimum number of selections
    $closeAfterSelect = $uiConfig['close_after_select'] ?? false;
    $showSelectAll = $uiConfig['show_select_all'] ?? true;
    $showClearAll = $uiConfig['show_clear_all'] ?? true;
    $groupOptions = $uiConfig['group_options'] ?? false; // Group options by category
    $sortable = $uiConfig['sortable'] ?? false; // Allow drag-and-drop reordering
    $showCount = $uiConfig['show_count'] ?? true; // Show selection count
    
    // Normalize options format
    $formattedOptions = [];
    foreach ($options as $option) {
        if (is_string($option)) {
            $formattedOptions[] = [
                'value' => $option,
                'label' => $option,
                'group' => null,
                'color' => null,
                'icon' => null,
                'disabled' => false
            ];
        } else {
            $formattedOptions[] = [
                'value' => $option['value'] ?? $option,
                'label' => $option['label'] ?? $option['text'] ?? $option['value'] ?? $option,
                'group' => $option['group'] ?? null,
                'color' => $option['color'] ?? null,
                'icon' => $option['icon'] ?? null,
                'disabled' => $option['disabled'] ?? false
            ];
        }
    }
    
    // Group options if needed
    $groupedOptions = [];
    if ($groupOptions) {
        foreach ($formattedOptions as $option) {
            $group = $option['group'] ?? 'Other';
            if (!isset($groupedOptions[$group])) {
                $groupedOptions[$group] = [];
            }
            $groupedOptions[$group][] = $option;
        }
    } else {
        $groupedOptions[''] = $formattedOptions;
    }
    
    // Handle selected values
    $selectedValues = [];
    if ($value) {
        if (is_array($value)) {
            $selectedValues = $value;
        } elseif (is_string($value)) {
            $selectedValues = explode(',', $value);
        }
    }
    $selectedValues = array_map('trim', $selectedValues);
@endphp

<div class="form-group mb-3">
    <label for="{{ $fieldSlug }}" class="form-label">
        {{ $field['label'] }}
        @if($field['is_required'])
            <span class="text-danger">*</span>
        @endif
        @if($showCount)
            <span class="selection-count badge bg-secondary ms-2" id="{{ $fieldSlug }}_count">0 selected</span>
        @endif
    </label>
    
    <div class="multiselect-container">
        @if($showSelectAll || $showClearAll)
            <div class="multiselect-controls mb-2">
                @if($showSelectAll)
                    <button type="button" class="btn btn-sm btn-outline-primary me-2" id="{{ $fieldSlug }}_select_all">
                        <i class="fas fa-check-double"></i> Select All
                    </button>
                @endif
                @if($showClearAll)
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="{{ $fieldSlug }}_clear_all">
                        <i class="fas fa-times"></i> Clear All
                    </button>
                @endif
            </div>
        @endif
        
        <select 
            id="{{ $fieldSlug }}"
            name="{{ $fieldSlug }}[]"
            class="{{ $selectClass }} multiselect-field"
            multiple
            @if($field['is_required']) required @endif
            data-placeholder="{{ $placeholder }}"
            @if($maxItems) data-max-items="{{ $maxItems }}" @endif
            @if($minItems) data-min-items="{{ $minItems }}" @endif
            @if($closeAfterSelect) data-close-after-select="true" @endif
            @if($sortable) data-sortable="true" @endif
        >
            @foreach($groupedOptions as $groupName => $groupItems)
                @if($groupOptions && $groupName)
                    <optgroup label="{{ $groupName }}">
                @endif
                
                @foreach($groupItems as $option)
                    <option 
                        value="{{ $option['value'] }}"
                        @if(in_array($option['value'], $selectedValues)) selected @endif
                        @if($option['disabled']) disabled @endif
                        @if($option['color']) data-color="{{ $option['color'] }}" @endif
                        @if($option['icon']) data-icon="{{ $option['icon'] }}" @endif
                    >
                        {{ $option['label'] }}
                    </option>
                @endforeach
                
                @if($groupOptions && $groupName)
                    </optgroup>
                @endif
            @endforeach
        </select>
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
        const selectElement = document.getElementById('{{ $fieldSlug }}');
        const selectAllBtn = document.getElementById('{{ $fieldSlug }}_select_all');
        const clearAllBtn = document.getElementById('{{ $fieldSlug }}_clear_all');
        const countElement = document.getElementById('{{ $fieldSlug }}_count');
        
        const maxItems = {{ $maxItems ?? 'null' }};
        const minItems = {{ $minItems ?? 'null' }};
        const closeAfterSelect = {{ $closeAfterSelect ? 'true' : 'false' }};
        const sortable = {{ $sortable ? 'true' : 'false' }};
        
        // Initialize TomSelect
        const tomSelect = new TomSelect(selectElement, {
            plugins: [
                'remove_button',
                ...(sortable ? ['drag_drop'] : []),
                'checkbox_options'
            ],
            placeholder: '{{ $placeholder }}',
            persist: false,
            createOnBlur: false,
            create: false,
            maxItems: maxItems,
            closeAfterSelect: closeAfterSelect,
            render: {
                option: function(item, escape) {
                    let html = '<div class="d-flex align-items-center">';
                    
                    // Icon
                    if (item.icon) {
                        html += `<i class="${escape(item.icon)} me-2"></i>`;
                    }
                    
                    // Color indicator
                    if (item.color) {
                        html += `<span class="color-indicator me-2" style="background-color: ${escape(item.color)}; width: 12px; height: 12px; border-radius: 2px; display: inline-block;"></span>`;
                    }
                    
                    // Label
                    html += `<span class="flex-grow-1">${escape(item.text || item.label)}</span>`;
                    
                    html += '</div>';
                    return html;
                },
                item: function(item, escape) {
                    let html = '<div class="d-flex align-items-center">';
                    
                    if (item.icon) {
                        html += `<i class="${escape(item.icon)} me-1"></i>`;
                    }
                    
                    if (item.color) {
                        html += `<span class="color-indicator me-1" style="background-color: ${escape(item.color)}; width: 8px; height: 8px; border-radius: 2px; display: inline-block;"></span>`;
                    }
                    
                    html += `<span>${escape(item.text || item.label)}</span>`;
                    html += '</div>';
                    return html;
                }
            },
            onItemAdd: function() {
                updateSelectionCount();
                validateSelection();
            },
            onItemRemove: function() {
                updateSelectionCount();
                validateSelection();
            },
            onInitialize: function() {
                updateSelectionCount();
            }
        });
        
        // Update selection count
        function updateSelectionCount() {
            @if($showCount)
                const count = tomSelect.items.length;
                if (countElement) {
                    countElement.textContent = `${count} selected`;
                    countElement.className = `selection-count badge ms-2 ${count > 0 ? 'bg-primary' : 'bg-secondary'}`;
                }
            @endif
        }
        
        // Validate selection constraints
        function validateSelection() {
            const count = tomSelect.items.length;
            const container = selectElement.closest('.form-group');
            let errorDiv = container.querySelector('.multiselect-validation-errors');
            const errors = [];
            
            // Check minimum items
            if (minItems && count < minItems) {
                errors.push(`Please select at least ${minItems} option${minItems > 1 ? 's' : ''}`);
            }
            
            // Check maximum items
            if (maxItems && count > maxItems) {
                errors.push(`You can select at most ${maxItems} option${maxItems > 1 ? 's' : ''}`);
            }
            
            // Display validation errors
            if (errors.length > 0) {
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'multiselect-validation-errors text-danger small mt-1';
                    container.appendChild(errorDiv);
                }
                errorDiv.innerHTML = errors.map(error => `<i class="fas fa-exclamation-circle"></i> ${error}`).join('<br>');
                selectElement.classList.add('is-invalid');
            } else {
                if (errorDiv) {
                    errorDiv.remove();
                }
                selectElement.classList.remove('is-invalid');
            }
        }
        
        // Select All functionality
        selectAllBtn?.addEventListener('click', function() {
            const availableOptions = Array.from(selectElement.options)
                .filter(option => !option.disabled)
                .slice(0, maxItems || undefined);
                
            tomSelect.clear();
            availableOptions.forEach(option => {
                tomSelect.addItem(option.value, true);
            });
            
            updateSelectionCount();
            validateSelection();
        });
        
        // Clear All functionality
        clearAllBtn?.addEventListener('click', function() {
            tomSelect.clear();
            updateSelectionCount();
            validateSelection();
        });
        
        // Keyboard shortcuts
        selectElement.addEventListener('keydown', function(e) {
            // Ctrl+A or Cmd+A to select all
            if ((e.ctrlKey || e.metaKey) && e.key === 'a' && selectAllBtn) {
                e.preventDefault();
                selectAllBtn.click();
            }
            
            // Escape to clear all
            if (e.key === 'Escape' && clearAllBtn) {
                clearAllBtn.click();
            }
        });
        
        // Form validation integration
        const form = selectElement.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                validateSelection();
                
                // Check if there are validation errors
                const errorDiv = selectElement.closest('.form-group').querySelector('.multiselect-validation-errors');
                if (errorDiv) {
                    e.preventDefault();
                    selectElement.focus();
                    return false;
                }
            });
        }
        
        // Initialize count and validation
        updateSelectionCount();
        validateSelection();
    });
</script>
@endpush