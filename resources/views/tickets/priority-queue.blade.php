@extends('layouts.app')

@section('title', 'Priority Queue')

@section('content')
<div class="container-fluid px-4">
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="h3 mb-0">Priority Queue</h1>
                <p class="text-muted mb-0">Manage high-priority tickets requiring immediate attention</p>
            </div>
            <div class="col-auto">
                <button class="btn btn-outline-primary" onclick="window.location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <button class="btn btn-primary" id="auto-prioritize">
                    <i class="fas fa-sort-amount-down"></i> Auto-Prioritize
                </button>
            </div>
        </div>
    </div>

    <!-- Priority Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-danger mb-1">Critical</h6>
                            <h3 class="mb-0">{{ $stats['critical'] ?? 0 }}</h3>
                        </div>
                        <i class="fas fa-exclamation-circle text-danger fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-warning mb-1">High Priority</h6>
                            <h3 class="mb-0">{{ $stats['high'] ?? 0 }}</h3>
                        </div>
                        <i class="fas fa-exclamation-triangle text-warning fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-info mb-1">SLA at Risk</h6>
                            <h3 class="mb-0">{{ $stats['sla_risk'] ?? 0 }}</h3>
                        </div>
                        <i class="fas fa-clock text-info fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-success mb-1">On Track</h6>
                            <h3 class="mb-0">{{ $stats['on_track'] ?? 0 }}</h3>
                        </div>
                        <i class="fas fa-check-circle text-success fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('tickets.priority-queue') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="priority" class="form-label">Priority Level</label>
                    <select name="priority" id="priority" class="form-select">
                        <option value="">All Priorities</option>
                        <option value="critical" {{ request('priority') == 'critical' ? 'selected' : '' }}>Critical</option>
                        <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>High</option>
                        <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Low</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="sla_status" class="form-label">SLA Status</label>
                    <select name="sla_status" id="sla_status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="breached" {{ request('sla_status') == 'breached' ? 'selected' : '' }}>Breached</option>
                        <option value="at_risk" {{ request('sla_status') == 'at_risk' ? 'selected' : '' }}>At Risk</option>
                        <option value="on_track" {{ request('sla_status') == 'on_track' ? 'selected' : '' }}>On Track</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="assigned_to" class="form-label">Assigned To</label>
                    <select name="assigned_to" id="assigned_to" class="form-select">
                        <option value="">All Agents</option>
                        <option value="unassigned" {{ request('assigned_to') == 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                        @foreach($agents ?? [] as $agent)
                        <option value="{{ $agent->id }}" {{ request('assigned_to') == $agent->id ? 'selected' : '' }}>
                            {{ $agent->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="escalated" class="form-label">Escalation</label>
                    <select name="escalated" id="escalated" class="form-select">
                        <option value="">All Tickets</option>
                        <option value="yes" {{ request('escalated') == 'yes' ? 'selected' : '' }}>Escalated Only</option>
                        <option value="no" {{ request('escalated') == 'no' ? 'selected' : '' }}>Not Escalated</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="{{ route('tickets.priority-queue') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                    <button type="button" class="btn btn-outline-info" onclick="exportQueue()">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Priority Queue Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="30">
                                <input type="checkbox" class="form-check-input" id="select-all">
                            </th>
                            <th>Priority</th>
                            <th>Ticket #</th>
                            <th>Subject</th>
                            <th>Client</th>
                            <th>Status</th>
                            <th>SLA Deadline</th>
                            <th>Time Remaining</th>
                            <th>Assigned To</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets ?? [] as $item)
                        <tr class="{{ $item->sla_status == 'breached' ? 'table-danger' : ($item->sla_status == 'at_risk' ? 'table-warning' : '') }}">
                            <td>
                                <input type="checkbox" class="form-check-input ticket-select" value="{{ $item->id }}">
                            </td>
                            <td>
                                @php
                                    $priorityClass = match($item->ticket->priority) {
                                        'critical' => 'badge bg-danger',
                                        'high' => 'badge bg-warning',
                                        'medium' => 'badge bg-info',
                                        'low' => 'badge bg-secondary',
                                        default => 'badge bg-secondary'
                                    };
                                @endphp
                                <span class="{{ $priorityClass }}">
                                    {{ ucfirst($item->ticket->priority) }}
                                </span>
                                @if($item->escalated_at)
                                    <span class="badge bg-danger ms-1" title="Escalated">
                                        <i class="fas fa-arrow-up"></i>
                                    </span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('tickets.show', $item->ticket_id) }}" class="text-decoration-none">
                                    #{{ $item->ticket_id }}
                                </a>
                            </td>
                            <td>
                                <strong>{{ Str::limit($item->ticket->subject, 50) }}</strong>
                                @if($item->ticket->tags)
                                <div class="small text-muted">
                                    @foreach(explode(',', $item->ticket->tags) as $tag)
                                    <span class="badge bg-light text-dark me-1">{{ $tag }}</span>
                                    @endforeach
                                </div>
                                @endif
                            </td>
                            <td>
                                {{ $item->ticket->client->name ?? 'N/A' }}
                                @if($item->ticket->client->vip_status ?? false)
                                    <span class="badge bg-gold ms-1" title="VIP Client">VIP</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $item->ticket->status == 'open' ? 'success' : ($item->ticket->status == 'pending' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($item->ticket->status) }}
                                </span>
                            </td>
                            <td>
                                @if($item->sla_deadline)
                                    {{ $item->sla_deadline->format('M d, Y H:i') }}
                                @else
                                    <span class="text-muted">No SLA</span>
                                @endif
                            </td>
                            <td>
                                @if($item->sla_deadline)
                                    @php
                                        $remaining = now()->diff($item->sla_deadline);
                                        $isPast = now() > $item->sla_deadline;
                                    @endphp
                                    @if($isPast)
                                        <span class="text-danger">-{{ $remaining->format('%h:%I') }}</span>
                                    @else
                                        <span class="{{ $remaining->h < 2 ? 'text-warning' : 'text-success' }}">
                                            {{ $remaining->format('%h:%I') }}
                                        </span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($item->ticket->assigned_to)
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm me-2">
                                            {{ substr($item->ticket->assignedTo->name ?? '', 0, 2) }}
                                        </div>
                                        {{ $item->ticket->assignedTo->name ?? 'Unknown' }}
                                    </div>
                                @else
                                    <span class="text-warning">Unassigned</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('tickets.show', $item->ticket_id) }}" class="btn btn-outline-primary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if(!$item->ticket->assigned_to)
                                    <button class="btn btn-outline-success" onclick="assignTicket({{ $item->ticket_id }})" title="Assign">
                                        <i class="fas fa-user-plus"></i>
                                    </button>
                                    @endif
                                    @if(!$item->escalated_at)
                                    <button class="btn btn-outline-danger" onclick="escalateTicket({{ $item->id }})" title="Escalate">
                                        <i class="fas fa-arrow-up"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No tickets in priority queue</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($tickets) && $tickets->hasPages())
            <div class="mt-3">
                {{ $tickets->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
// Select all checkbox
document.getElementById('select-all')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.ticket-select');
    checkboxes.forEach(cb => cb.checked = this.checked);
});

// Auto-prioritize function
document.getElementById('auto-prioritize')?.addEventListener('click', function() {
    if (confirm('This will automatically re-prioritize all tickets based on SLA deadlines and client importance. Continue?')) {
        fetch('{{ route("tickets.priority-queue.auto-prioritize") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message || 'Queue prioritized successfully');
            window.location.reload();
        });
    }
});

// Assign ticket function
function assignTicket(ticketId) {
    // This would open a modal or prompt to select an agent
    const agentId = prompt('Enter agent ID to assign:');
    if (agentId) {
        fetch(`/api/tickets/${ticketId}/assign`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ assigned_to: agentId })
        })
        .then(response => response.json())
        .then(data => {
            alert('Ticket assigned successfully');
            window.location.reload();
        });
    }
}

// Escalate ticket function
function escalateTicket(queueItemId) {
    if (confirm('Escalate this ticket to management?')) {
        fetch(`{{ route("tickets.priority-queue.escalate", "") }}/${queueItemId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            alert('Ticket escalated successfully');
            window.location.reload();
        });
    }
}

// Export queue function
function exportQueue() {
    const params = new URLSearchParams(window.location.search);
    window.location.href = `{{ route("tickets.priority-queue.export") }}?${params.toString()}`;
}

// Auto-refresh every 60 seconds
setInterval(() => {
    window.location.reload();
}, 60000);
</script>
@endpush
@endsection