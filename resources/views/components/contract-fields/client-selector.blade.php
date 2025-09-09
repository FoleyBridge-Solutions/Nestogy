@props(['field', 'value' => null, 'errors' => []])

@php
    $fieldSlug = $field['field_slug'];
    $hasError = !empty($errors[$fieldSlug]);
    $uiConfig = $field['ui_config'] ?? [];
    $selectClass = 'block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm' . ($hasError ? ' border-red-500' : '');
    
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

<div class="mb-4 mb-6">
    <label for="{{ $fieldSlug }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        {{ $field['label'] }}
        @if($field['is_required'])
            <span class="text-red-600 dark:text-red-400">*</span>
        @endif
    </label>
    
    <div class="client-selector-container mx-auto">
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
                    return `<div class="flex items-center">
                        <div class="flex-grow-1">
                            <div class="fw-medium">${escape(item.name)}</div>
                            ${item.email ? `<small class="text-gray-600 dark:text-gray-400">${escape(item.email)}</small>` : ''}
                        </div>
                        ${item.status ? `<span class="badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium-${item.status === 'active' ? 'success' : 'secondary'}">${escape(item.status)}</span>` : ''}
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
