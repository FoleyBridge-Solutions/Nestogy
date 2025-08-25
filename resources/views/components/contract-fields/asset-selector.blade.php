@props(['field', 'value' => null, 'errors' => []])

@php
    $fieldSlug = $field['field_slug'];
    $hasError = !empty($errors[$fieldSlug]);
    $uiConfig = $field['ui_config'] ?? [];
    $selectClass = 'form-select' . ($hasError ? ' is-invalid' : '');
    
    // Extract UI configuration
    $placeholder = $field['placeholder'] ?? 'Search for assets...';
    $multiple = $uiConfig['multiple'] ?? true; // Assets are usually multi-select
    $ajaxUrl = $field['ajax_url'] ?? route('api.assets.search');
    $assetTypes = $uiConfig['asset_types'] ?? []; // Filter by specific asset types
    $clientId = $uiConfig['client_id'] ?? null; // Filter by client
    
    // Get selected assets if value is provided
    $selectedAssets = collect();
    if ($value) {
        $assetIds = is_array($value) ? $value : [$value];
        $selectedAssets = \App\Models\Asset::where('company_id', auth()->user()->company_id)
            ->whereIn('id', $assetIds)
            ->get();
    }
@endphp

<div class="form-group mb-3">
    <label for="{{ $fieldSlug }}" class="form-label">
        {{ $field['label'] }}
        @if($field['is_required'])
            <span class="text-danger">*</span>
        @endif
    </label>
    
    <div class="asset-selector-container">
        <select 
            id="{{ $fieldSlug }}"
            name="{{ $fieldSlug }}{{ $multiple ? '[]' : '' }}"
            class="{{ $selectClass }} asset-selector"
            @if($field['is_required']) required @endif
            @if($multiple) multiple @endif
            data-placeholder="{{ $placeholder }}"
            data-ajax-url="{{ $ajaxUrl }}"
            @if(!empty($assetTypes)) data-asset-types="{{ implode(',', $assetTypes) }}" @endif
            @if($clientId) data-client-id="{{ $clientId }}" @endif
        >
            @foreach($selectedAssets as $asset)
                <option value="{{ $asset->id }}" selected>
                    {{ $asset->name ?? $asset->hostname }}
                    @if($asset->ip_address)
                        ({{ $asset->ip_address }})
                    @endif
                </option>
            @endforeach
            
            @if(!$field['is_required'] && !$multiple)
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
        const assetSelect = document.getElementById('{{ $fieldSlug }}');
        const assetTypes = assetSelect.dataset.assetTypes ? assetSelect.dataset.assetTypes.split(',') : [];
        const clientId = assetSelect.dataset.clientId || null;
        
        new TomSelect(assetSelect, {
            valueField: 'id',
            labelField: 'name',
            searchField: ['name', 'hostname', 'ip_address'],
            placeholder: '{{ $placeholder }}',
            @if($multiple)
            plugins: ['remove_button'],
            @endif
            load: function(query, callback) {
                if (!query.length) return callback();
                
                let url = `{{ $ajaxUrl }}?search=${encodeURIComponent(query)}`;
                
                if (assetTypes.length > 0) {
                    url += `&asset_types=${assetTypes.join(',')}`;
                }
                
                if (clientId) {
                    url += `&client_id=${clientId}`;
                }
                
                fetch(url)
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
                            <div class="fw-medium">${escape(item.name || item.hostname)}</div>
                            <small class="text-muted">
                                ${item.ip_address ? escape(item.ip_address) : ''}
                                ${item.asset_type ? ` • ${escape(item.asset_type)}` : ''}
                                ${item.client_name ? ` • ${escape(item.client_name)}` : ''}
                            </small>
                        </div>
                        <div class="text-end">
                            ${item.is_online ? '<span class="badge badge-success">Online</span>' : '<span class="badge badge-secondary">Offline</span>'}
                        </div>
                    </div>`;
                },
                item: function(item, escape) {
                    return `<div>
                        ${escape(item.name || item.hostname)}
                        ${item.ip_address ? ` (${escape(item.ip_address)})` : ''}
                    </div>`;
                }
            }
        });
        
        // Update asset selector when client changes (if client selector exists)
        const clientSelector = document.querySelector('select[name="client_id"]');
        if (clientSelector) {
            clientSelector.addEventListener('change', function() {
                const tomSelect = assetSelect.tomselect;
                if (tomSelect) {
                    tomSelect.clear();
                    tomSelect.settings.load = function(query, callback) {
                        if (!query.length) return callback();
                        
                        let url = `{{ $ajaxUrl }}?search=${encodeURIComponent(query)}`;
                        
                        if (assetTypes.length > 0) {
                            url += `&asset_types=${assetTypes.join(',')}`;
                        }
                        
                        if (this.value) {
                            url += `&client_id=${this.value}`;
                        }
                        
                        fetch(url)
                            .then(response => response.json())
                            .then(json => {
                                callback(json.data || json);
                            })
                            .catch(() => {
                                callback();
                            });
                    }.bind(clientSelector);
                }
            });
        }
    });
</script>
@endpush