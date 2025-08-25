@props(['field', 'value' => null, 'errors' => []])

@php
    $fieldSlug = $field['field_slug'];
    $hasError = !empty($errors[$fieldSlug]);
    $uiConfig = $field['ui_config'] ?? [];
    $selectClass = 'form-select' . ($hasError ? ' is-invalid' : '');
    
    // Extract UI configuration
    $placeholder = $field['placeholder'] ?? 'Search for a client...';
    $multiple = $uiConfig['multiple'] ?? false;
    $ajaxUrl = $field['ajax_url'] ?? route('api.clients.search');
    
    // Get selected client if value is provided
    $selectedClient = null;
    if ($value && !$multiple) {
        $selectedClient = \App\Models\Client::where('company_id', auth()->user()->company_id)
            ->find($value);
    }
@endphp

<div class="form-group mb-3">
    <label for="{{ $fieldSlug }}" class="form-label">
        {{ $field['label'] }}
        @if($field['is_required'])
            <span class="text-danger">*</span>
        @endif
    </label>
    
    <div class="client-selector-container">
        <select 
            id="{{ $fieldSlug }}"
            name="{{ $fieldSlug }}{{ $multiple ? '[]' : '' }}"
            class="{{ $selectClass }} client-selector"
            @if($field['is_required']) required @endif
            @if($multiple) multiple @endif
            data-placeholder="{{ $placeholder }}"
            data-ajax-url="{{ $ajaxUrl }}"
        >
            @if($selectedClient)
                <option value="{{ $selectedClient->id }}" selected>
                    {{ $selectedClient->name }}
                    @if($selectedClient->email)
                        ({{ $selectedClient->email }})
                    @endif
                </option>
            @elseif(!$field['is_required'])
                <option value="">{{ $placeholder }}</option>
            @endif
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
        const clientSelect = document.getElementById('{{ $fieldSlug }}');
        
        new TomSelect(clientSelect, {
            valueField: 'id',
            labelField: 'name',
            searchField: ['name', 'email'],
            placeholder: '{{ $placeholder }}',
            @if($multiple)
            plugins: ['remove_button'],
            @endif
            load: function(query, callback) {
                if (!query.length) return callback();
                
                fetch(`{{ $ajaxUrl }}?search=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(json => {
                        callback(json.data || json);
                    })
                    .catch(() => {
                        callback();
                    });
            },
            render: {
                option: function(item, escape) {
                    return `<div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="fw-medium">${escape(item.name)}</div>
                            ${item.email ? `<small class="text-muted">${escape(item.email)}</small>` : ''}
                        </div>
                        ${item.status ? `<span class="badge badge-${item.status === 'active' ? 'success' : 'secondary'}">${escape(item.status)}</span>` : ''}
                    </div>`;
                },
                item: function(item, escape) {
                    return `<div>
                        ${escape(item.name)}
                        ${item.email ? ` (${escape(item.email)})` : ''}
                    </div>`;
                }
            }
        });
    });
</script>
@endpush