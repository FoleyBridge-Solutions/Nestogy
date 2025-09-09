@props(['field', 'value' => null, 'errors' => []])

@php
    $fieldSlug = $field['field_slug'];
    $hasError = !empty($errors[$fieldSlug]);
    $uiConfig = $field['ui_config'] ?? [];
    $inputClass = 'form-control' . ($hasError ? ' is-invalid' : '');
    
    // Extract UI configuration
    $width = $uiConfig['width'] ?? 'full';
    $size = $uiConfig['size'] ?? 'default';
    $addon = $uiConfig['addon'] ?? null;
    $maxlength = $uiConfig['maxlength'] ?? null;
@endphp

<div class="form-group mb-3">
    <label for="{{ $fieldSlug }}" class="form-label">
        {{ $field['label'] }}
        @if($field['is_required'])
            <span class="text-danger">*</span>
        @endif
    </label>
    
    <div class="input-container">
        @if($addon)
            <div class="input-group">
                @if($addon['position'] === 'left')
                    <span class="input-group-text">{{ $addon['text'] }}</span>
                @endif
                
                <input 
                    type="text"
                    id="{{ $fieldSlug }}"
                    name="{{ $fieldSlug }}"
                    class="{{ $inputClass }}"
                    value="{{ old($fieldSlug, $value) }}"
                    placeholder="{{ $field['placeholder'] ?? '' }}"
                    @if($field['is_required']) required @endif
                    @if($maxlength) maxlength="{{ $maxlength }}" @endif
                    @if($size === 'small') style="height: 32px;" @endif
                    @if($size === 'large') style="height: 48px;" @endif
                />
                
                @if($addon['position'] === 'right')
                    <span class="input-group-text">{{ $addon['text'] }}</span>
                @endif
            </div>
        @else
            <input 
                type="text"
                id="{{ $fieldSlug }}"
                name="{{ $fieldSlug }}"
                class="{{ $inputClass }}"
                value="{{ old($fieldSlug, $value) }}"
                placeholder="{{ $field['placeholder'] ?? '' }}"
                @if($field['is_required']) required @endif
                @if($maxlength) maxlength="{{ $maxlength }}" @endif
                @if($size === 'small') style="height: 32px;" @endif
                @if($size === 'large') style="height: 48px;" @endif
            />
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