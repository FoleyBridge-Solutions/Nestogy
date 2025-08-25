@props(['field', 'value' => null, 'errors' => []])

@php
    $fieldSlug = $field['field_slug'];
    $hasError = !empty($errors[$fieldSlug]);
    $uiConfig = $field['ui_config'] ?? [];
    $inputClass = 'form-control' . ($hasError ? ' is-invalid' : '');
    
    // Extract UI configuration
    $min = $uiConfig['min'] ?? 0;
    $max = $uiConfig['max'] ?? 100;
    $step = $uiConfig['step'] ?? 1;
    $precision = $uiConfig['precision'] ?? 2; // Decimal places
    $showSlider = $uiConfig['show_slider'] ?? true; // Show range slider
    $showInput = $uiConfig['show_input'] ?? true; // Show number input
    $colorCoded = $uiConfig['color_coded'] ?? false; // Color code based on value
    $thresholds = $uiConfig['thresholds'] ?? []; // Color thresholds: [['value' => 25, 'color' => 'danger'], ...]
    $showProgress = $uiConfig['show_progress'] ?? false; // Show as progress bar
    $suffix = $uiConfig['suffix'] ?? '%'; // Display suffix
    
    // Default color thresholds if color coding is enabled but no thresholds provided
    if ($colorCoded && empty($thresholds)) {
        $thresholds = [
            ['value' => 25, 'color' => 'danger', 'label' => 'Low'],
            ['value' => 50, 'color' => 'warning', 'label' => 'Medium'],
            ['value' => 75, 'color' => 'info', 'label' => 'Good'],
            ['value' => 100, 'color' => 'success', 'label' => 'Excellent']
        ];
    }
    
    // Format value for display
    $displayValue = $value ?? 0;
    if ($displayValue && is_numeric($displayValue)) {
        $displayValue = round($displayValue, $precision);
    }
    
    // Get current color based on value
    function getCurrentColor($value, $thresholds) {
        if (empty($thresholds)) return 'primary';
        
        foreach ($thresholds as $threshold) {
            if ($value <= $threshold['value']) {
                return $threshold['color'];
            }
        }
        
        return end($thresholds)['color'];
    }
    
    $currentColor = $colorCoded ? getCurrentColor($displayValue, $thresholds) : 'primary';
@endphp

<div class="form-group mb-3">
    <label for="{{ $fieldSlug }}" class="form-label">
        {{ $field['label'] }}
        @if($field['is_required'])
            <span class="text-danger">*</span>
        @endif
        @if($colorCoded && !empty($thresholds))
            <span class="percentage-indicator badge bg-{{ $currentColor }} ms-2" id="{{ $fieldSlug }}_indicator">
                {{ $displayValue }}{{ $suffix }}
            </span>
        @endif
    </label>
    
    <div class="percentage-input-container">
        @if($showSlider && $showInput)
            {{-- Combined slider and input --}}
            <div class="row g-3">
                <div class="col-8">
                    <input 
                        type="range"
                        id="{{ $fieldSlug }}_slider"
                        class="form-range percentage-slider"
                        min="{{ $min }}"
                        max="{{ $max }}"
                        step="{{ $step }}"
                        value="{{ old($fieldSlug, $displayValue) }}"
                        data-field="{{ $fieldSlug }}"
                    />
                </div>
                <div class="col-4">
                    <div class="input-group">
                        <input 
                            type="number"
                            id="{{ $fieldSlug }}"
                            name="{{ $fieldSlug }}"
                            class="{{ $inputClass }} percentage-input"
                            value="{{ old($fieldSlug, $displayValue) }}"
                            placeholder="{{ $field['placeholder'] ?? '0' }}"
                            @if($field['is_required']) required @endif
                            min="{{ $min }}"
                            max="{{ $max }}"
                            step="{{ $step }}"
                            data-precision="{{ $precision }}"
                        />
                        <span class="input-group-text">{{ $suffix }}</span>
                    </div>
                </div>
            </div>
        @elseif($showSlider)
            {{-- Slider only --}}
            <div class="slider-container">
                <input 
                    type="range"
                    id="{{ $fieldSlug }}"
                    name="{{ $fieldSlug }}"
                    class="form-range percentage-slider"
                    min="{{ $min }}"
                    max="{{ $max }}"
                    step="{{ $step }}"
                    value="{{ old($fieldSlug, $displayValue) }}"
                    @if($field['is_required']) required @endif
                />
                <div class="slider-labels d-flex justify-content-between">
                    <small class="text-muted">{{ $min }}{{ $suffix }}</small>
                    <small class="text-muted slider-current-value" id="{{ $fieldSlug }}_current">
                        {{ $displayValue }}{{ $suffix }}
                    </small>
                    <small class="text-muted">{{ $max }}{{ $suffix }}</small>
                </div>
            </div>
        @else
            {{-- Input only --}}
            <div class="input-group">
                <input 
                    type="number"
                    id="{{ $fieldSlug }}"
                    name="{{ $fieldSlug }}"
                    class="{{ $inputClass }} percentage-input"
                    value="{{ old($fieldSlug, $displayValue) }}"
                    placeholder="{{ $field['placeholder'] ?? '0' }}"
                    @if($field['is_required']) required @endif
                    min="{{ $min }}"
                    max="{{ $max }}"
                    step="{{ $step }}"
                    data-precision="{{ $precision }}"
                />
                <span class="input-group-text">{{ $suffix }}</span>
            </div>
        @endif
        
        @if($showProgress)
            {{-- Progress bar visualization --}}
            <div class="progress mt-2" style="height: 8px;">
                <div 
                    class="progress-bar bg-{{ $currentColor }}" 
                    id="{{ $fieldSlug }}_progress"
                    role="progressbar" 
                    style="width: {{ ($displayValue / $max) * 100 }}%"
                    aria-valuenow="{{ $displayValue }}"
                    aria-valuemin="{{ $min }}"
                    aria-valuemax="{{ $max }}"
                ></div>
            </div>
        @endif
        
        @if($colorCoded && !empty($thresholds))
            {{-- Color threshold legend --}}
            <div class="percentage-legend mt-2">
                <small class="text-muted d-block mb-1">Ranges:</small>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($thresholds as $index => $threshold)
                        @php
                            $prevValue = $index > 0 ? $thresholds[$index - 1]['value'] + 1 : $min;
                            $rangeText = $prevValue === $threshold['value'] ? 
                                "{$threshold['value']}{$suffix}" : 
                                "{$prevValue}-{$threshold['value']}{$suffix}";
                        @endphp
                        <span class="badge bg-{{ $threshold['color'] }} bg-opacity-25 text-{{ $threshold['color'] }}">
                            {{ $rangeText }} {{ $threshold['label'] ?? '' }}
                        </span>
                    @endforeach
                </div>
            </div>
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
    .percentage-input-container .form-range {
        background: transparent;
    }
    
    .percentage-input-container .form-range::-webkit-slider-track {
        background: linear-gradient(to right, 
            @if($colorCoded && !empty($thresholds))
                @foreach($thresholds as $index => $threshold)
                    var(--bs-{{ $threshold['color'] }}) {{ ($threshold['value'] / $max) * 100 }}%{{ $index < count($thresholds) - 1 ? ',' : '' }}
                @endforeach
            @else
                var(--bs-primary)
            @endif
        );
        height: 8px;
        border-radius: 4px;
    }
    
    .percentage-input-container .form-range::-moz-range-track {
        background: linear-gradient(to right, 
            @if($colorCoded && !empty($thresholds))
                @foreach($thresholds as $index => $threshold)
                    var(--bs-{{ $threshold['color'] }}) {{ ($threshold['value'] / $max) * 100 }}%{{ $index < count($thresholds) - 1 ? ',' : '' }}
                @endforeach
            @else
                var(--bs-primary)
            @endif
        );
        height: 8px;
        border-radius: 4px;
        border: none;
    }
    
    .percentage-input-container .form-range::-webkit-slider-thumb {
        background: white;
        border: 2px solid var(--bs-{{ $currentColor }});
        width: 20px;
        height: 20px;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        transition: all 0.2s ease;
    }
    
    .percentage-input-container .form-range::-webkit-slider-thumb:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    }
    
    .percentage-input-container .form-range::-moz-range-thumb {
        background: white;
        border: 2px solid var(--bs-{{ $currentColor }});
        width: 20px;
        height: 20px;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        transition: all 0.2s ease;
    }
    
    .slider-labels {
        margin-top: 0.5rem;
    }
    
    .slider-current-value {
        font-weight: 600;
        color: var(--bs-{{ $currentColor }}) !important;
    }
    
    .percentage-indicator {
        transition: all 0.3s ease;
    }
    
    .percentage-legend .badge {
        font-size: 0.75rem;
    }
    
    /* Animated progress bar */
    .progress-bar {
        transition: width 0.3s ease, background-color 0.3s ease;
    }
    
    /* Number input styling */
    .percentage-input:focus {
        border-color: var(--bs-{{ $currentColor }});
        box-shadow: 0 0 0 0.2rem rgba(var(--bs-{{ $currentColor }}-rgb), 0.25);
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const percentageInput = document.getElementById('{{ $fieldSlug }}');
        const slider = document.getElementById('{{ $fieldSlug }}_slider');
        const indicator = document.getElementById('{{ $fieldSlug }}_indicator');
        const currentValue = document.getElementById('{{ $fieldSlug }}_current');
        const progressBar = document.getElementById('{{ $fieldSlug }}_progress');
        
        const min = {{ $min }};
        const max = {{ $max }};
        const step = {{ $step }};
        const precision = {{ $precision }};
        const suffix = '{{ $suffix }}';
        const colorCoded = {{ $colorCoded ? 'true' : 'false' }};
        const thresholds = @json($thresholds);
        
        // Get color based on value
        function getColorByValue(value) {
            if (!colorCoded || thresholds.length === 0) return 'primary';
            
            for (let threshold of thresholds) {
                if (value <= threshold.value) {
                    return threshold.color;
                }
            }
            
            return thresholds[thresholds.length - 1].color;
        }
        
        // Update all visual elements
        function updateVisuals(value) {
            const numValue = parseFloat(value) || 0;
            const clampedValue = Math.max(min, Math.min(max, numValue));
            const color = getColorByValue(clampedValue);
            
            // Update indicator
            if (indicator) {
                indicator.textContent = clampedValue.toFixed(precision) + suffix;
                indicator.className = `percentage-indicator badge bg-${color} ms-2`;
            }
            
            // Update current value display
            if (currentValue) {
                currentValue.textContent = clampedValue.toFixed(precision) + suffix;
                currentValue.className = `text-muted slider-current-value text-${color}`;
            }
            
            // Update progress bar
            if (progressBar) {
                const percentage = ((clampedValue - min) / (max - min)) * 100;
                progressBar.style.width = percentage + '%';
                progressBar.className = `progress-bar bg-${color}`;
                progressBar.setAttribute('aria-valuenow', clampedValue);
            }
            
            // Update slider thumb color
            if (slider) {
                slider.style.setProperty('--thumb-color', `var(--bs-${color})`);
            }
            
            // Update input border color
            if (percentageInput && colorCoded) {
                percentageInput.style.setProperty('--focus-color', `var(--bs-${color})`);
            }
        }
        
        // Sync slider and input
        if (slider && percentageInput) {
            slider.addEventListener('input', function() {
                const value = parseFloat(this.value);
                percentageInput.value = value.toFixed(precision);
                updateVisuals(value);
                percentageInput.dispatchEvent(new Event('input', { bubbles: true }));
            });
            
            percentageInput.addEventListener('input', function() {
                let value = parseFloat(this.value) || 0;
                
                // Clamp value to range
                value = Math.max(min, Math.min(max, value));
                
                if (slider) {
                    slider.value = value;
                }
                
                updateVisuals(value);
            });
        } else if (slider) {
            // Slider only mode
            slider.addEventListener('input', function() {
                updateVisuals(this.value);
            });
        } else if (percentageInput) {
            // Input only mode
            percentageInput.addEventListener('input', function() {
                let value = parseFloat(this.value) || 0;
                value = Math.max(min, Math.min(max, value));
                updateVisuals(value);
            });
        }
        
        // Validation
        function validatePercentage() {
            const value = parseFloat(percentageInput.value);
            const container = percentageInput.closest('.form-group');
            let errorDiv = container.querySelector('.percentage-validation-error');
            const errors = [];
            
            if (isNaN(value)) {
                errors.push('Please enter a valid number');
            } else {
                if (value < min) {
                    errors.push(`Value must be at least ${min}${suffix}`);
                }
                if (value > max) {
                    errors.push(`Value must be at most ${max}${suffix}`);
                }
            }
            
            // Display validation errors
            if (errors.length > 0) {
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'percentage-validation-error text-danger small mt-1';
                    container.appendChild(errorDiv);
                }
                errorDiv.innerHTML = errors.map(error => `<i class="fas fa-exclamation-circle"></i> ${error}`).join('<br>');
                percentageInput.classList.add('is-invalid');
            } else {
                if (errorDiv) {
                    errorDiv.remove();
                }
                percentageInput.classList.remove('is-invalid');
            }
        }
        
        // Validate on blur
        percentageInput?.addEventListener('blur', function() {
            validatePercentage();
            
            // Format value
            const value = parseFloat(this.value);
            if (!isNaN(value)) {
                const clampedValue = Math.max(min, Math.min(max, value));
                this.value = clampedValue.toFixed(precision);
                updateVisuals(clampedValue);
            }
        });
        
        // Keyboard shortcuts for quick values
        percentageInput?.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                let newValue = null;
                
                switch(e.key) {
                    case '0':
                        newValue = min;
                        break;
                    case '5':
                        newValue = (min + max) / 2;
                        break;
                    case '9':
                        newValue = max;
                        break;
                }
                
                if (newValue !== null) {
                    e.preventDefault();
                    this.value = newValue.toFixed(precision);
                    if (slider) slider.value = newValue;
                    updateVisuals(newValue);
                    this.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
        });
        
        // Mouse wheel support for fine adjustment
        percentageInput?.addEventListener('wheel', function(e) {
            if (this === document.activeElement) {
                e.preventDefault();
                
                const currentValue = parseFloat(this.value) || 0;
                const delta = e.deltaY > 0 ? -step : step;
                const newValue = Math.max(min, Math.min(max, currentValue + delta));
                
                this.value = newValue.toFixed(precision);
                if (slider) slider.value = newValue;
                updateVisuals(newValue);
                this.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
        
        // Form submission validation
        const form = percentageInput?.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                validatePercentage();
                
                const errorDiv = percentageInput.closest('.form-group').querySelector('.percentage-validation-error');
                if (errorDiv) {
                    e.preventDefault();
                    percentageInput.focus();
                    return false;
                }
            });
        }
        
        // Initialize visuals
        const initialValue = parseFloat(percentageInput?.value || slider?.value || 0);
        updateVisuals(initialValue);
    });
</script>
@endpush