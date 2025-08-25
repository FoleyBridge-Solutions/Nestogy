@props(['contract', 'contractType', 'sections' => [], 'actions' => [], 'tabs' => []])

@php
    // Get contract type configuration
    $typeConfig = $contractType ?? [];
    $detailConfig = $typeConfig['detail_config'] ?? [];
    
    // Extract detail view configuration
    $showHeader = $detailConfig['show_header'] ?? true;
    $showSidebar = $detailConfig['show_sidebar'] ?? true;
    $showTabs = $detailConfig['show_tabs'] ?? true;
    $showTimeline = $detailConfig['show_timeline'] ?? true;
    $showComments = $detailConfig['show_comments'] ?? true;
    $showAttachments = $detailConfig['show_attachments'] ?? true;
    $layout = $detailConfig['layout'] ?? 'standard'; // standard, compact, wide
    $sidebarPosition = $detailConfig['sidebar_position'] ?? 'right'; // left, right
    
    // Default sections if none provided
    $defaultSections = [
        'overview' => [
            'title' => 'Overview',
            'icon' => 'fas fa-info-circle',
            'fields' => ['name', 'description', 'status', 'created_at'],
            'collapsible' => false
        ],
        'details' => [
            'title' => 'Contract Details',
            'icon' => 'fas fa-file-contract',
            'fields' => ['start_date', 'end_date', 'value', 'client_id'],
            'collapsible' => true
        ]
    ];
    
    $displaySections = !empty($sections) ? $sections : $defaultSections;
    
    // Default actions
    $defaultActions = [
        'edit' => ['label' => 'Edit', 'icon' => 'fas fa-edit', 'color' => 'primary', 'url' => route('contracts.edit', $contract->id ?? 1)],
        'duplicate' => ['label' => 'Duplicate', 'icon' => 'fas fa-copy', 'color' => 'secondary'],
        'delete' => ['label' => 'Delete', 'icon' => 'fas fa-trash', 'color' => 'danger', 'confirm' => true],
        'export' => ['label' => 'Export', 'icon' => 'fas fa-download', 'color' => 'success']
    ];
    
    $availableActions = array_merge($defaultActions, $actions);
    
    // Default tabs
    $defaultTabs = [
        'overview' => ['label' => 'Overview', 'icon' => 'fas fa-info-circle', 'active' => true],
        'timeline' => ['label' => 'Timeline', 'icon' => 'fas fa-history'],
        'attachments' => ['label' => 'Attachments', 'icon' => 'fas fa-paperclip'],
        'comments' => ['label' => 'Comments', 'icon' => 'fas fa-comments']
    ];
    
    $displayTabs = !empty($tabs) ? $tabs : $defaultTabs;
    
    // Get status configuration
    $statusConfig = $typeConfig['statuses'][$contract->status ?? 'draft'] ?? [];
    $statusColor = $statusConfig['color'] ?? 'secondary';
    $statusLabel = $statusConfig['label'] ?? ($contract->status ?? 'Draft');
    
    // Layout classes
    $containerClass = 'contract-detail-container';
    if ($layout === 'compact') $containerClass .= ' compact-layout';
    if ($layout === 'wide') $containerClass .= ' wide-layout';
    if ($sidebarPosition === 'left') $containerClass .= ' sidebar-left';
@endphp

<div class="{{ $containerClass }}" data-contract-id="{{ $contract->id ?? '' }}">
    {{-- Contract Header --}}
    @if($showHeader)
        <div class="contract-header">
            <div class="row align-items-start">
                <div class="col-md-8">
                    <div class="d-flex align-items-center mb-2">
                        <h2 class="mb-0 me-3">{{ $contract->name ?? 'Contract Details' }}</h2>
                        <span class="badge bg-{{ $statusColor }} fs-6">{{ $statusLabel }}</span>
                    </div>
                    
                    @if($contract->description ?? false)
                        <p class="text-muted mb-3">{{ $contract->description }}</p>
                    @endif
                    
                    <div class="contract-meta">
                        <div class="row">
                            <div class="col-sm-6">
                                <small class="text-muted d-block">Contract ID</small>
                                <span class="fw-medium">{{ $contract->contract_number ?? $contract->id ?? 'N/A' }}</span>
                            </div>
                            <div class="col-sm-6">
                                <small class="text-muted d-block">Type</small>
                                <span class="fw-medium">{{ $typeConfig['label'] ?? 'Contract' }}</span>
                            </div>
                            @if($contract->client ?? false)
                                <div class="col-sm-6 mt-2">
                                    <small class="text-muted d-block">Client</small>
                                    <a href="{{ route('clients.show', $contract->client->id) }}" class="fw-medium">
                                        {{ $contract->client->name }}
                                    </a>
                                </div>
                            @endif
                            @if($contract->value ?? false)
                                <div class="col-sm-6 mt-2">
                                    <small class="text-muted d-block">Value</small>
                                    <span class="fw-medium">${{ number_format($contract->value, 2) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 text-md-end">
                    {{-- Action Buttons --}}
                    <div class="action-buttons">
                        <div class="btn-group">
                            @foreach($availableActions as $actionKey => $action)
                                @if($actionKey === 'delete')
                                    <form method="POST" action="{{ route('contracts.destroy', $contract->id ?? 1) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="btn btn-outline-{{ $action['color'] }}"
                                                onclick="return confirm('Are you sure you want to delete this contract?')"
                                                title="{{ $action['label'] }}">
                                            <i class="{{ $action['icon'] }}"></i>
                                            @if(!($detailConfig['compact_actions'] ?? false))
                                                {{ $action['label'] }}
                                            @endif
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ $action['url'] ?? '#' }}" 
                                       class="btn btn-outline-{{ $action['color'] }}"
                                       @if($action['confirm'] ?? false) onclick="return confirm('Are you sure?')" @endif
                                       title="{{ $action['label'] }}">
                                        <i class="{{ $action['icon'] }}"></i>
                                        @if(!($detailConfig['compact_actions'] ?? false))
                                            {{ $action['label'] }}
                                        @endif
                                    </a>
                                @endif
                            @endforeach
                        </div>
                        
                        {{-- Status Change Dropdown --}}
                        @if(!empty($typeConfig['statuses']))
                            <div class="btn-group ms-2">
                                <button class="btn btn-outline-secondary dropdown-toggle" 
                                        data-bs-toggle="dropdown" id="status-dropdown">
                                    <i class="fas fa-exchange-alt"></i> Change Status
                                </button>
                                <ul class="dropdown-menu">
                                    @foreach($typeConfig['statuses'] as $statusValue => $statusConf)
                                        @if($statusValue !== ($contract->status ?? 'draft'))
                                            <li>
                                                <a class="dropdown-item status-change" 
                                                   href="#" 
                                                   data-status="{{ $statusValue }}">
                                                    <span class="badge bg-{{ $statusConf['color'] ?? 'secondary' }} me-2"></span>
                                                    {{ $statusConf['label'] ?? $statusValue }}
                                                </a>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                    
                    {{-- Progress Indicator --}}
                    @if($contract->progress ?? false)
                        <div class="contract-progress mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">Progress</small>
                                <small class="fw-medium">{{ $contract->progress }}%</small>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-{{ $contract->progress >= 75 ? 'success' : ($contract->progress >= 50 ? 'warning' : 'danger') }}" 
                                     style="width: {{ $contract->progress }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <hr>
    @endif
    
    {{-- Main Content Area --}}
    <div class="contract-content">
        <div class="row">
            {{-- Main Content --}}
            <div class="col-lg-{{ $showSidebar ? '8' : '12' }} {{ $sidebarPosition === 'left' ? 'order-2' : '' }}">
                @if($showTabs)
                    {{-- Tabbed Content --}}
                    <ul class="nav nav-tabs mb-4" id="contract-tabs">
                        @foreach($displayTabs as $tabKey => $tab)
                            <li class="nav-item">
                                <a class="nav-link {{ $tab['active'] ?? false ? 'active' : '' }}" 
                                   data-bs-toggle="tab" 
                                   href="#tab-{{ $tabKey }}"
                                   id="{{ $tabKey }}-tab">
                                    <i class="{{ $tab['icon'] ?? 'fas fa-circle' }}"></i>
                                    {{ $tab['label'] }}
                                    @if(isset($tab['count']))
                                        <span class="badge bg-secondary ms-1">{{ $tab['count'] }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    
                    <div class="tab-content" id="contract-tab-content">
                        {{-- Overview Tab --}}
                        <div class="tab-pane fade {{ $displayTabs['overview']['active'] ?? true ? 'show active' : '' }}" 
                             id="tab-overview">
                            @include('components.contracts.detail-sections', ['sections' => $displaySections, 'contract' => $contract, 'typeConfig' => $typeConfig])
                        </div>
                        
                        {{-- Timeline Tab --}}
                        @if($showTimeline)
                            <div class="tab-pane fade" id="tab-timeline">
                                @include('components.contracts.timeline', ['contract' => $contract])
                            </div>
                        @endif
                        
                        {{-- Attachments Tab --}}
                        @if($showAttachments)
                            <div class="tab-pane fade" id="tab-attachments">
                                @include('components.contracts.attachments', ['contract' => $contract])
                            </div>
                        @endif
                        
                        {{-- Comments Tab --}}
                        @if($showComments)
                            <div class="tab-pane fade" id="tab-comments">
                                @include('components.contracts.comments', ['contract' => $contract])
                            </div>
                        @endif
                        
                        {{-- Custom tabs from configuration --}}
                        @foreach($displayTabs as $tabKey => $tab)
                            @if(!in_array($tabKey, ['overview', 'timeline', 'attachments', 'comments']) && isset($tab['component']))
                                <div class="tab-pane fade" id="tab-{{ $tabKey }}">
                                    @include($tab['component'], ['contract' => $contract, 'config' => $tab])
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    {{-- Non-tabbed content --}}
                    @include('components.contracts.detail-sections', ['sections' => $displaySections, 'contract' => $contract, 'typeConfig' => $typeConfig])
                @endif
            </div>
            
            {{-- Sidebar --}}
            @if($showSidebar)
                <div class="col-lg-4 {{ $sidebarPosition === 'left' ? 'order-1' : '' }}">
                    <div class="contract-sidebar">
                        {{-- Quick Stats --}}
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-chart-bar"></i> Quick Stats
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="stat-item text-center">
                                            <div class="stat-value text-primary">{{ $contract->days_remaining ?? 'N/A' }}</div>
                                            <div class="stat-label text-muted small">Days Left</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="stat-item text-center">
                                            <div class="stat-value text-success">${{ number_format($contract->value ?? 0, 0) }}</div>
                                            <div class="stat-label text-muted small">Value</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="stat-item text-center">
                                            <div class="stat-value text-warning">{{ $contract->milestones_count ?? 0 }}</div>
                                            <div class="stat-label text-muted small">Milestones</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="stat-item text-center">
                                            <div class="stat-value text-info">{{ $contract->amendments_count ?? 0 }}</div>
                                            <div class="stat-label text-muted small">Amendments</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Key Dates --}}
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-calendar"></i> Key Dates
                                </h6>
                            </div>
                            <div class="card-body">
                                @if($contract->start_date ?? false)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Start Date:</span>
                                        <span class="fw-medium">{{ \Carbon\Carbon::parse($contract->start_date)->format('M j, Y') }}</span>
                                    </div>
                                @endif
                                @if($contract->end_date ?? false)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">End Date:</span>
                                        <span class="fw-medium">{{ \Carbon\Carbon::parse($contract->end_date)->format('M j, Y') }}</span>
                                    </div>
                                @endif
                                @if($contract->renewal_date ?? false)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Renewal:</span>
                                        <span class="fw-medium">{{ \Carbon\Carbon::parse($contract->renewal_date)->format('M j, Y') }}</span>
                                    </div>
                                @endif
                                @if($contract->created_at ?? false)
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Created:</span>
                                        <span class="fw-medium">{{ \Carbon\Carbon::parse($contract->created_at)->format('M j, Y') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        {{-- Related Items --}}
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-link"></i> Related Items
                                </h6>
                            </div>
                            <div class="card-body">
                                @if($contract->client ?? false)
                                    <div class="related-item mb-2">
                                        <i class="fas fa-building text-muted me-2"></i>
                                        <a href="{{ route('clients.show', $contract->client->id) }}">{{ $contract->client->name }}</a>
                                    </div>
                                @endif
                                
                                @if($contract->project ?? false)
                                    <div class="related-item mb-2">
                                        <i class="fas fa-project-diagram text-muted me-2"></i>
                                        <a href="{{ route('projects.show', $contract->project->id) }}">{{ $contract->project->name }}</a>
                                    </div>
                                @endif
                                
                                @if($contract->parent_contract ?? false)
                                    <div class="related-item mb-2">
                                        <i class="fas fa-file-contract text-muted me-2"></i>
                                        <a href="{{ route('contracts.show', $contract->parent_contract->id) }}">{{ $contract->parent_contract->name }}</a>
                                    </div>
                                @endif
                                
                                @if(($contract->assets_count ?? 0) > 0)
                                    <div class="related-item mb-2">
                                        <i class="fas fa-server text-muted me-2"></i>
                                        <a href="{{ route('contracts.assets', $contract->id) }}">{{ $contract->assets_count }} Assets</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        {{-- Recent Activity --}}
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-history"></i> Recent Activity
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="activity-feed" id="recent-activity">
                                    <div class="text-center py-3">
                                        <i class="fas fa-spinner fa-spin text-muted"></i>
                                        <div class="text-muted small mt-1">Loading activity...</div>
                                    </div>
                                </div>
                                <div class="text-center mt-2">
                                    <a href="#tab-timeline" data-bs-toggle="tab" class="btn btn-sm btn-outline-primary">
                                        View All Activity
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Status Change Modal --}}
<div class="modal fade" id="statusChangeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Contract Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="status-change-form">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">New Status</label>
                        <select name="status" class="form-select" id="new-status" required>
                            <option value="">Select status...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason for Change</label>
                        <textarea name="reason" class="form-control" rows="3" 
                                  placeholder="Optional: Enter reason for status change"></textarea>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="notify-client" name="notify_client">
                        <label class="form-check-label" for="notify-client">
                            Notify client of status change
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const contractId = document.querySelector('.contract-detail-container').dataset.contractId;
        const statusChangeModal = new bootstrap.Modal(document.getElementById('statusChangeModal'));
        
        // Initialize detail view
        initializeDetailView();
        
        function initializeDetailView() {
            loadRecentActivity();
            setupStatusChange();
            setupAutoRefresh();
        }
        
        // Load recent activity
        function loadRecentActivity() {
            const activityContainer = document.getElementById('recent-activity');
            if (!activityContainer) return;
            
            fetch(`{{ route('contracts.activity') }}/${contractId}?limit=5`)
                .then(response => response.json())
                .then(data => {
                    if (data.activities && data.activities.length > 0) {
                        const activityHtml = data.activities.map(activity => `
                            <div class="activity-item mb-2">
                                <div class="d-flex">
                                    <div class="activity-icon me-2">
                                        <i class="${activity.icon} text-${activity.color || 'muted'}"></i>
                                    </div>
                                    <div class="activity-content flex-grow-1">
                                        <div class="activity-text">${activity.description}</div>
                                        <small class="text-muted">${activity.time_ago}</small>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                        
                        activityContainer.innerHTML = activityHtml;
                    } else {
                        activityContainer.innerHTML = '<div class="text-muted text-center py-2">No recent activity</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading activity:', error);
                    activityContainer.innerHTML = '<div class="text-danger text-center py-2">Error loading activity</div>';
                });
        }
        
        // Setup status change functionality
        function setupStatusChange() {
            const statusChangeLinks = document.querySelectorAll('.status-change');
            const statusForm = document.getElementById('status-change-form');
            const newStatusSelect = document.getElementById('new-status');
            
            // Status change links
            statusChangeLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const newStatus = this.dataset.status;
                    
                    // Populate status options
                    const statuses = @json($typeConfig['statuses'] ?? []);
                    newStatusSelect.innerHTML = '<option value="">Select status...</option>';
                    
                    Object.entries(statuses).forEach(([value, config]) => {
                        const option = document.createElement('option');
                        option.value = value;
                        option.textContent = config.label || value;
                        option.selected = value === newStatus;
                        newStatusSelect.appendChild(option);
                    });
                    
                    statusChangeModal.show();
                });
            });
            
            // Status change form submission
            statusForm?.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                formData.append('_method', 'PATCH');
                
                fetch(`{{ route('contracts.update-status') }}/${contractId}`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update status badge
                        const statusBadge = document.querySelector('.badge.bg-{{ $statusColor }}');
                        if (statusBadge) {
                            statusBadge.className = `badge bg-${data.status_color} fs-6`;
                            statusBadge.textContent = data.status_label;
                        }
                        
                        // Reload activity
                        loadRecentActivity();
                        
                        // Show success message
                        showMessage('success', data.message || 'Status updated successfully');
                        
                        statusChangeModal.hide();
                        statusForm.reset();
                    } else {
                        showMessage('error', data.message || 'Failed to update status');
                    }
                })
                .catch(error => {
                    console.error('Status update error:', error);
                    showMessage('error', 'Failed to update status');
                });
            });
        }
        
        // Auto-refresh data periodically
        function setupAutoRefresh() {
            setInterval(() => {
                loadRecentActivity();
                refreshStats();
            }, 30000); // Refresh every 30 seconds
        }
        
        // Refresh statistics
        function refreshStats() {
            fetch(`{{ route('contracts.stats-single') }}/${contractId}`)
                .then(response => response.json())
                .then(data => {
                    // Update stat values
                    Object.entries(data).forEach(([key, value]) => {
                        const statElement = document.querySelector(`[data-stat="${key}"]`);
                        if (statElement) {
                            statElement.textContent = value;
                        }
                    });
                })
                .catch(error => {
                    console.error('Error refreshing stats:', error);
                });
        }
        
        // Tab change handling
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', function(e) {
                const tabId = e.target.getAttribute('href').substring(1);
                
                // Load tab content if needed
                if (tabId === 'tab-timeline' && !document.querySelector('#tab-timeline .timeline-loaded')) {
                    loadTimeline();
                } else if (tabId === 'tab-attachments' && !document.querySelector('#tab-attachments .attachments-loaded')) {
                    loadAttachments();
                } else if (tabId === 'tab-comments' && !document.querySelector('#tab-comments .comments-loaded')) {
                    loadComments();
                }
            });
        });
        
        // Load timeline content
        function loadTimeline() {
            const timelineContainer = document.getElementById('tab-timeline');
            if (!timelineContainer) return;
            
            timelineContainer.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading timeline...</div>';
            
            fetch(`{{ route('contracts.timeline') }}/${contractId}`)
                .then(response => response.text())
                .then(html => {
                    timelineContainer.innerHTML = html;
                    timelineContainer.classList.add('timeline-loaded');
                })
                .catch(error => {
                    console.error('Error loading timeline:', error);
                    timelineContainer.innerHTML = '<div class="text-danger text-center py-4">Error loading timeline</div>';
                });
        }
        
        // Load attachments content
        function loadAttachments() {
            const attachmentsContainer = document.getElementById('tab-attachments');
            if (!attachmentsContainer) return;
            
            attachmentsContainer.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading attachments...</div>';
            
            fetch(`{{ route('contracts.attachments') }}/${contractId}`)
                .then(response => response.text())
                .then(html => {
                    attachmentsContainer.innerHTML = html;
                    attachmentsContainer.classList.add('attachments-loaded');
                })
                .catch(error => {
                    console.error('Error loading attachments:', error);
                    attachmentsContainer.innerHTML = '<div class="text-danger text-center py-4">Error loading attachments</div>';
                });
        }
        
        // Load comments content
        function loadComments() {
            const commentsContainer = document.getElementById('tab-comments');
            if (!commentsContainer) return;
            
            commentsContainer.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading comments...</div>';
            
            fetch(`{{ route('contracts.comments') }}/${contractId}`)
                .then(response => response.text())
                .then(html => {
                    commentsContainer.innerHTML = html;
                    commentsContainer.classList.add('comments-loaded');
                })
                .catch(error => {
                    console.error('Error loading comments:', error);
                    commentsContainer.innerHTML = '<div class="text-danger text-center py-4">Error loading comments</div>';
                });
        }
        
        // Utility function for showing messages
        function showMessage(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            document.querySelector('.contract-detail-container').insertAdjacentHTML('afterbegin', alertHtml);
            
            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) alert.remove();
            }, 5000);
        }
    });
</script>
@endpush