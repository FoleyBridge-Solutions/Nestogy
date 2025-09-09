@props(['field', 'value' => null, 'errors' => []])

@php
    $fieldSlug = $field['field_slug'];
    $hasError = !empty($errors[$fieldSlug]);
    $uiConfig = $field['ui_config'] ?? [];
    $selectClass = 'block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm' . ($hasError ? ' border-red-500' : '');
    
    // Extract UI configuration
    $searchable = $uiConfig['searchable'] ?? false;
    $multiple = $uiConfig['multiple'] ?? false;
    $placeholder = $field['placeholder'] ?? 'Select an option...';
    $options = $field['options'] ?? [];
@endphp

<div class="mb-4 mb-6">
    <label for="{{ $fieldSlug }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        {{ $field['label'] }}
        @if($field['is_required'])
            <span class="text-red-600 dark:text-red-400">*</span>
        @endif
    </label>
    
    <div class="select-container mx-auto">
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
