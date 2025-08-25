@props(['field', 'value' => null, 'errors' => []])

@php
    $fieldSlug = $field['field_slug'];
    $hasError = !empty($errors[$fieldSlug]);
    $uiConfig = $field['ui_config'] ?? [];
    $inputClass = 'form-control' . ($hasError ? ' is-invalid' : '');
    
    // Extract UI configuration
    $format = $uiConfig['format'] ?? 'Y-m-d';
    $minDate = $uiConfig['min_date'] ?? null;
    $maxDate = $uiConfig['max_date'] ?? null;
    $disabledDates = $uiConfig['disabled_dates'] ?? [];
    $enableTime = $uiConfig['enable_time'] ?? false;
    
    // Format value for display
    $displayValue = $value;
    if ($value && !is_string($value)) {
        $displayValue = $value->format($format);
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
        <input 
            type="date"
            id="{{ $fieldSlug }}"
            name="{{ $fieldSlug }}"
            class="{{ $inputClass }} flatpickr-date"
            value="{{ old($fieldSlug, $displayValue) }}"
            placeholder="{{ $field['placeholder'] ?? 'Select date...' }}"
            @if($field['is_required']) required @endif
            @if($minDate) min="{{ $minDate }}" @endif
            @if($maxDate) max="{{ $maxDate }}" @endif
            data-format="{{ $format }}"
            @if($enableTime) data-enable-time="true" @endif
            @if(!empty($disabledDates)) data-disabled-dates="{{ json_encode($disabledDates) }}" @endif
        />
        <span class="input-group-text">
            <i class="fas fa-calendar"></i>
        </span>
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
        const dateInput = document.getElementById('{{ $fieldSlug }}');
        
        const config = {
            dateFormat: '{{ $format }}',
            @if($enableTime)
            enableTime: true,
            time_24hr: true,
            @endif
            @if($minDate)
            minDate: '{{ $minDate }}',
            @endif
            @if($maxDate)
            maxDate: '{{ $maxDate }}',
            @endif
            @if(!empty($disabledDates))
            disable: {!! json_encode($disabledDates) !!},
            @endif
            allowInput: true,
            clickOpens: true,
        };
        
        flatpickr(dateInput, config);
    });
</script>
@endpush