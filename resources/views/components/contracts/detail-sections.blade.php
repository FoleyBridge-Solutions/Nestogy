@props(['sections', 'contract', 'typeConfig'])

@php
    // Field type formatting functions
    function formatFieldValue($value, $field, $contract) {
        if ($value === null || $value === '') {
            return '<span class="text-muted">—</span>';
        }
        
        $type = $field['type'] ?? 'text';
        
        switch ($type) {
            case 'currency':
                return '$' . number_format($value, 2);
                
            case 'date':
                return \Carbon\Carbon::parse($value)->format('M j, Y');
                
            case 'datetime':
                return \Carbon\Carbon::parse($value)->format('M j, Y g:i A');
                
            case 'boolean':
                return $value ? 
                    '<span class="badge bg-success">Yes</span>' : 
                    '<span class="badge bg-secondary">No</span>';
                    
            case 'status':
                $statusConfig = $typeConfig['statuses'][$value] ?? [];
                $color = $statusConfig['color'] ?? 'secondary';
                $label = $statusConfig['label'] ?? $value;
                return '<span class="badge bg-' . $color . '">' . $label . '</span>';
                
            case 'progress':
                $percentage = is_numeric($value) ? $value : 0;
                $color = $percentage >= 75 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger');
                return '
                    <div class="d-flex align-items-center">
                        <div class="progress me-2" style="width: 100px; height: 8px;">
                            <div class="progress-bar bg-' . $color . '" style="width: ' . $percentage . '%"></div>
                        </div>
                        <small>' . $percentage . '%</small>
                    </div>';
                    
            case 'percentage':
                return $value . '%';
                
            case 'number':
                return number_format($value);
                
            case 'email':
                return '<a href="mailto:' . $value . '">' . $value . '</a>';
                
            case 'url':
                return '<a href="' . $value . '" target="_blank">' . $value . '</a>';
                
            case 'phone':
                return '<a href="tel:' . $value . '">' . $value . '</a>';
                
            case 'file':
                if (is_string($value)) {
                    $filename = basename($value);
                    return '<a href="' . $value . '" target="_blank"><i class="fas fa-file"></i> ' . $filename . '</a>';
                }
                return $value;
                
            case 'json':
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    if ($decoded) {
                        return '<pre class="small bg-light p-2 rounded">' . json_encode($decoded, JSON_PRETTY_PRINT) . '</pre>';
                    }
                }
                return '<code>' . htmlspecialchars($value) . '</code>';
                
            case 'textarea':
                return '<div class="preserved-whitespace">' . nl2br(htmlspecialchars($value)) . '</div>';
                
            case 'client':
                if ($contract->client) {
                    return '<a href="' . route('clients.show', $contract->client->id) . '">' . $contract->client->name . '</a>';
                }
                return $value;
                
            case 'user':
                if (is_numeric($value)) {
                    $user = \App\Models\User::find($value);
                    if ($user) {
                        return '<div class="d-flex align-items-center">
                                    <img src="' . ($user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&size=24') . '" 
                                         class="rounded-circle me-2" width="24" height="24" alt="' . $user->name . '">
                                    ' . $user->name . '
                                </div>';
                    }
                }
                return $value;
                
            case 'tags':
                if (is_array($value)) {
                    return implode(' ', array_map(function($tag) {
                        return '<span class="badge bg-light text-dark border">' . $tag . '</span>';
                    }, $value));
                } elseif (is_string($value)) {
                    $tags = explode(',', $value);
                    return implode(' ', array_map(function($tag) {
                        return '<span class="badge bg-light text-dark border">' . trim($tag) . '</span>';
                    }, $tags));
                }
                return $value;
                
            case 'multiselect':
                if (is_array($value)) {
                    return implode(', ', $value);
                }
                return $value;
                
            default:
                return htmlspecialchars($value);
        }
    }
@endphp

@foreach($sections as $sectionKey => $section)
    <div class="contract-section mb-4" id="section-{{ $sectionKey }}">
        <div class="card">
            <div class="card-header {{ $section['collapsible'] ?? false ? 'cursor-pointer' : '' }}" 
                 @if($section['collapsible'] ?? false) 
                     data-bs-toggle="collapse" 
                     data-bs-target="#collapse-{{ $sectionKey }}"
                 @endif>
                <h6 class="card-title mb-0 d-flex align-items-center">
                    @if($section['icon'] ?? false)
                        <i class="{{ $section['icon'] }} me-2"></i>
                    @endif
                    {{ $section['title'] }}
                    
                    @if($section['collapsible'] ?? false)
                        <i class="fas fa-chevron-down ms-auto"></i>
                    @endif
                    
                    @if($section['badge'] ?? false)
                        <span class="badge bg-{{ $section['badge']['color'] ?? 'primary' }} ms-2">
                            {{ $section['badge']['text'] }}
                        </span>
                    @endif
                </h6>
            </div>
            
            <div class="{{ $section['collapsible'] ?? false ? 'collapse show' : '' }}" 
                 @if($section['collapsible'] ?? false) id="collapse-{{ $sectionKey }}" @endif>
                <div class="card-body">
                    @if($section['description'] ?? false)
                        <p class="text-muted mb-3">{{ $section['description'] }}</p>
                    @endif
                    
                    @if($section['layout'] ?? 'grid' === 'table')
                        {{-- Table Layout --}}
                        <div class="table-responsive">
                            <table class="table table-sm">
                                @foreach($section['fields'] as $fieldConfig)
                                    @php
                                        $fieldKey = is_string($fieldConfig) ? $fieldConfig : $fieldConfig['key'];
                                        $fieldLabel = is_string($fieldConfig) ? 
                                            ucfirst(str_replace('_', ' ', $fieldKey)) : 
                                            ($fieldConfig['label'] ?? ucfirst(str_replace('_', ' ', $fieldKey)));
                                        $fieldValue = data_get($contract, $fieldKey);
                                        $fieldType = is_array($fieldConfig) ? ($fieldConfig['type'] ?? 'text') : 'text';
                                        
                                        // Skip empty fields if configured
                                        if (($section['hide_empty'] ?? false) && ($fieldValue === null || $fieldValue === '')) {
                                            continue;
                                        }
                                    @endphp
                                    
                                    <tr>
                                        <td class="fw-medium text-muted" style="width: 30%;">{{ $fieldLabel }}:</td>
                                        <td>
                                            {!! formatFieldValue($fieldValue, is_array($fieldConfig) ? $fieldConfig : ['type' => $fieldType], $contract) !!}
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    @elseif($section['layout'] ?? 'grid' === 'list')
                        {{-- List Layout --}}
                        <div class="list-group list-group-flush">
                            @foreach($section['fields'] as $fieldConfig)
                                @php
                                    $fieldKey = is_string($fieldConfig) ? $fieldConfig : $fieldConfig['key'];
                                    $fieldLabel = is_string($fieldConfig) ? 
                                        ucfirst(str_replace('_', ' ', $fieldKey)) : 
                                        ($fieldConfig['label'] ?? ucfirst(str_replace('_', ' ', $fieldKey)));
                                    $fieldValue = data_get($contract, $fieldKey);
                                    $fieldType = is_array($fieldConfig) ? ($fieldConfig['type'] ?? 'text') : 'text';
                                    
                                    if (($section['hide_empty'] ?? false) && ($fieldValue === null || $fieldValue === '')) {
                                        continue;
                                    }
                                @endphp
                                
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">{{ $fieldLabel }}</h6>
                                            <div class="field-value">
                                                {!! formatFieldValue($fieldValue, is_array($fieldConfig) ? $fieldConfig : ['type' => $fieldType], $contract) !!}
                                            </div>
                                        </div>
                                        @if(is_array($fieldConfig) && ($fieldConfig['actions'] ?? false))
                                            <div class="field-actions">
                                                @foreach($fieldConfig['actions'] as $action)
                                                    <a href="{{ $action['url'] ?? '#' }}" 
                                                       class="btn btn-sm btn-outline-{{ $action['color'] ?? 'primary' }}"
                                                       title="{{ $action['label'] }}">
                                                        <i class="{{ $action['icon'] ?? 'fas fa-link' }}"></i>
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        {{-- Grid Layout (Default) --}}
                        <div class="row g-3">
                            @foreach($section['fields'] as $fieldConfig)
                                @php
                                    $fieldKey = is_string($fieldConfig) ? $fieldConfig : $fieldConfig['key'];
                                    $fieldLabel = is_string($fieldConfig) ? 
                                        ucfirst(str_replace('_', ' ', $fieldKey)) : 
                                        ($fieldConfig['label'] ?? ucfirst(str_replace('_', ' ', $fieldKey)));
                                    $fieldValue = data_get($contract, $fieldKey);
                                    $fieldType = is_array($fieldConfig) ? ($fieldConfig['type'] ?? 'text') : 'text';
                                    $colSize = is_array($fieldConfig) ? ($fieldConfig['col_size'] ?? 'col-md-6') : 'col-md-6';
                                    
                                    if (($section['hide_empty'] ?? false) && ($fieldValue === null || $fieldValue === '')) {
                                        continue;
                                    }
                                @endphp
                                
                                <div class="{{ $colSize }}">
                                    <div class="field-group">
                                        <label class="field-label text-muted small">{{ $fieldLabel }}</label>
                                        <div class="field-value">
                                            {!! formatFieldValue($fieldValue, is_array($fieldConfig) ? $fieldConfig : ['type' => $fieldType], $contract) !!}
                                        </div>
                                        
                                        @if(is_array($fieldConfig) && ($fieldConfig['help_text'] ?? false))
                                            <small class="form-text text-muted">{{ $fieldConfig['help_text'] }}</small>
                                        @endif
                                        
                                        @if(is_array($fieldConfig) && ($fieldConfig['actions'] ?? false))
                                            <div class="field-actions mt-1">
                                                @foreach($fieldConfig['actions'] as $action)
                                                    <a href="{{ $action['url'] ?? '#' }}" 
                                                       class="btn btn-sm btn-outline-{{ $action['color'] ?? 'primary' }} me-1"
                                                       title="{{ $action['label'] }}">
                                                        <i class="{{ $action['icon'] ?? 'fas fa-link' }}"></i>
                                                        {{ $action['label'] ?? '' }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    
                    {{-- Custom content --}}
                    @if($section['custom_content'] ?? false)
                        <div class="custom-content mt-3">
                            @if($section['custom_component'] ?? false)
                                @include($section['custom_component'], ['contract' => $contract, 'section' => $section])
                            @else
                                {!! $section['custom_content'] !!}
                            @endif
                        </div>
                    @endif
                    
                    {{-- Section actions --}}
                    @if($section['actions'] ?? false)
                        <div class="section-actions mt-3 pt-3 border-top">
                            @foreach($section['actions'] as $action)
                                @if($action['type'] ?? 'link' === 'button')
                                    <button class="btn btn-{{ $action['color'] ?? 'primary' }} me-2"
                                            onclick="{{ $action['onclick'] ?? '' }}"
                                            @if($action['confirm'] ?? false) onclick="return confirm('{{ $action['confirm_message'] ?? 'Are you sure?' }}')" @endif>
                                        <i class="{{ $action['icon'] ?? 'fas fa-link' }}"></i>
                                        {{ $action['label'] }}
                                    </button>
                                @else
                                    <a href="{{ $action['url'] ?? '#' }}" 
                                       class="btn btn-{{ $action['color'] ?? 'primary' }} me-2"
                                       @if($action['target'] ?? false) target="{{ $action['target'] }}" @endif
                                       @if($action['confirm'] ?? false) onclick="return confirm('{{ $action['confirm_message'] ?? 'Are you sure?' }}')" @endif>
                                        <i class="{{ $action['icon'] ?? 'fas fa-link' }}"></i>
                                        {{ $action['label'] }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endforeach

@push('styles')
<style>
    .field-group {
        margin-bottom: 1rem;
    }
    
    .field-label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.25rem;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
    }
    
    .field-value {
        font-size: 0.95rem;
        line-height: 1.4;
    }
    
    .preserved-whitespace {
        white-space: pre-wrap;
    }
    
    .contract-section .card-header.cursor-pointer:hover {
        background-color: #f8f9fa;
    }
    
    .contract-section .card-header .fas {
        transition: transform 0.2s ease;
    }
    
    .contract-section .collapsed .fas {
        transform: rotate(-90deg);
    }
    
    .field-actions {
        margin-top: 0.5rem;
    }
    
    .field-actions .btn {
        margin-right: 0.25rem;
    }
    
    .section-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .custom-content {
        padding: 1rem;
        background-color: #f8f9fa;
        border-radius: 0.375rem;
        border: 1px solid #dee2e6;
    }
    
    /* Progress bar styling */
    .progress {
        background-color: #e9ecef;
    }
    
    /* Badge styling */
    .badge {
        font-size: 0.75em;
    }
    
    /* Table responsive improvements */
    .table-responsive .table td {
        vertical-align: top;
        padding: 0.75rem 0.5rem;
    }
    
    .table-responsive .table td:first-child {
        padding-left: 0;
    }
    
    .table-responsive .table td:last-child {
        padding-right: 0;
    }
    
    /* List group styling */
    .list-group-flush .list-group-item {
        border-left: 0;
        border-right: 0;
    }
    
    .list-group-flush .list-group-item:first-child {
        border-top: 0;
    }
    
    .list-group-flush .list-group-item:last-child {
        border-bottom: 0;
    }
</style>
@endpush