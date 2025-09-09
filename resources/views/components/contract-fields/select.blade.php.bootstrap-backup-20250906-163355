@props(['field', 'value' => null, 'errors' => []])

@php
    $fieldSlug = $field['field_slug'];
    $hasError = !empty($errors[$fieldSlug]);
    $uiConfig = $field['ui_config'] ?? [];
    $selectClass = 'form-select' . ($hasError ? ' is-invalid' : '');
    
    // Extract UI configuration
    $searchable = $uiConfig['searchable'] ?? false;
    $multiple = $uiConfig['multiple'] ?? false;
    $placeholder = $field['placeholder'] ?? 'Select an option...';
    $options = $field['options'] ?? [];
@endphp

<div class="form-group mb-3">
    <label for="{{ $fieldSlug }}" class="form-label">
        {{ $field['label'] }}
        @if($field['is_required'])
            <span class="text-danger">*</span>
        @endif
    </label>
    
    <div class="select-container">
        @if($searchable)
            {{-- Use TomSelect for searchable dropdowns --}}
            <select 
                id="{{ $fieldSlug }}"
                name="{{ $fieldSlug }}{{ $multiple ? '[]' : '' }}"
                class="{{ $selectClass }} tom-select"
                @if($field['is_required']) required @endif
                @if($multiple) multiple @endif
                data-placeholder="{{ $placeholder }}"
            >
                @if(!$field['is_required'] && !$multiple)
                    <option value="">{{ $placeholder }}</option>
                @endif
                
                @foreach($options as $option)
                    @php
                        $optionValue = $option['value'] ?? $option;
                        $optionLabel = $option['label'] ?? $option['text'] ?? $optionValue;
                        $isSelected = false;
                        
                        if ($multiple && is_array($value)) {
                            $isSelected = in_array($optionValue, $value);
                        } else {
                            $isSelected = (string) $optionValue === (string) old($fieldSlug, $value);
                        }
                    @endphp
                    
                    <option 
                        value="{{ $optionValue }}"
                        @if($isSelected) selected @endif
                        @if(isset($option['color'])) data-color="{{ $option['color'] }}" @endif
                        @if(isset($option['icon'])) data-icon="{{ $option['icon'] }}" @endif
                    >
                        {{ $optionLabel }}
                    </option>
                @endforeach
            </select>
        @else
            {{-- Standard select dropdown --}}
            <select 
                id="{{ $fieldSlug }}"
                name="{{ $fieldSlug }}{{ $multiple ? '[]' : '' }}"
                class="{{ $selectClass }}"
                @if($field['is_required']) required @endif
                @if($multiple) multiple @endif
            >
                @if(!$field['is_required'] && !$multiple)
                    <option value="">{{ $placeholder }}</option>
                @endif
                
                @foreach($options as $option)
                    @php
                        $optionValue = $option['value'] ?? $option;
                        $optionLabel = $option['label'] ?? $option['text'] ?? $optionValue;
                        $isSelected = false;
                        
                        if ($multiple && is_array($value)) {
                            $isSelected = in_array($optionValue, $value);
                        } else {
                            $isSelected = (string) $optionValue === (string) old($fieldSlug, $value);
                        }
                    @endphp
                    
                    <option 
                        value="{{ $optionValue }}"
                        @if($isSelected) selected @endif
                    >
                        {{ $optionLabel }}
                    </option>
                @endforeach
            </select>
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

@if($searchable)
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new TomSelect('#{{ $fieldSlug }}', {
                placeholder: '{{ $placeholder }}',
                allowEmptyOption: {{ $field['is_required'] ? 'false' : 'true' }},
                @if($multiple)
                plugins: ['remove_button'],
                @endif
            });
        });
    </script>
    @endpush
@endif