@extends('layouts.app')

@section('title', $client->name . ' - Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Client Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-2">{{ $client->name }}</h1>
                    <div class="text-muted">
                        <span class="badge badge-{{ $dashboardData['overview']['status'] === 'active' ? 'success' : 'secondary' }}">
                            {{ ucfirst($dashboardData['overview']['status']) }}
                        </span>
                        <span class="ms-3">Client since {{ $client->created_at->format('M Y') }}</span>
                    </div>
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <div class="dropdown d-inline-block ms-2">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-download"></i> Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('clients.dashboard.export', ['client' => $client, 'format' => 'pdf']) }}">PDF</a></li>
                            <li><a class="dropdown-item" href="{{ route('clients.dashboard.export', ['client' => $client, 'format' => 'excel']) }}">Excel</a></li>
                            <li><a class="dropdown-item" href="{{ route('clients.dashboard.export', ['client' => $client, 'format' => 'csv']) }}">CSV</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics Row -->
    <div class="row mb-4">
        <!-- Financial Card -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-subtitle text-muted">Total Revenue</h6>
                        <i class="fas fa-dollar-sign text-success"></i>
                    </div>
                    <h3 class="card-title mb-2">${{ number_format($dashboardData['financial']['total_revenue'], 2) }}</h3>
                    <div class="small">
                        <span class="text-success">MRR: ${{ number_format($dashboardData['financial']['mrr'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tickets Card -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-subtitle text-muted">Open Tickets</h6>
                        <i class="fas fa-ticket-alt text-warning"></i>
                    </div>
                    <h3 class="card-title mb-2">{{ $dashboardData['tickets']['open'] }}</h3>
                    <div class="small">
                        <span class="text-muted">Total: {{ $dashboardData['tickets']['total'] }}</span>
                        <span class="ms-2">SLA: {{ $dashboardData['tickets']['sla_compliance'] }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assets Card -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-subtitle text-muted">Active Assets</h6>
                        <i class="fas fa-server text-info"></i>
                    </div>
                    <h3 class="card-title mb-2">{{ $dashboardData['assets']['active_assets'] }}</h3>
                    <div class="small">
                        <span class="text-muted">Total: {{ $dashboardData['assets']['total_assets'] }}</span>
                        @if($dashboardData['assets']['maintenance_due'] > 0)
                            <span class="ms-2 text-warning">{{ $dashboardData['assets']['maintenance_due'] }} due</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects Card -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-subtitle text-muted">Active Projects</h6>
                        <i class="fas fa-project-diagram text-primary"></i>
                    </div>
                    <h3 class="card-title mb-2">{{ $dashboardData['projects']['active_projects'] }}</h3>
                    <div class="small">
                        <span class="text-muted">Completion: {{ $dashboardData['projects']['completion_rate'] }}%</span>
                        @if($dashboardData['projects']['overdue_projects'] > 0)
                            <span class="ms-2 text-danger">{{ $dashboardData['projects']['overdue_projects'] }} overdue</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Recent Activity -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @foreach($dashboardData['recent_activity'] as $activity)
                        <div class="timeline-item">
                            <div class="timeline-marker 
                                @if($activity['type'] === 'ticket') bg-warning
                                @elseif($activity['type'] === 'invoice') bg-info
                                @elseif($activity['type'] === 'payment') bg-success
                                @else bg-secondary
                                @endif">
                            </div>
                            <div class="timeline-content">
                                <h6 class="mb-1">{{ $activity['description'] }}</h6>
                                <small class="text-muted">
                                    {{ \Carbon\Carbon::parse($activity['date'])->diffForHumans() }}
                                    @if(isset($activity['amount']))
                                        <span class="ms-2">${{ number_format($activity['amount'], 2) }}</span>
                                    @endif
                                    @if(isset($activity['status']))
                                        <span class="badge badge-sm badge-{{ $activity['status'] === 'completed' ? 'success' : 'warning' }} ms-2">
                                            {{ $activity['status'] }}
                                        </span>
                                    @endif
                                </small>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Financial Overview Chart -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Financial Overview</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="80"></canvas>
                </div>
            </div>

            <!-- Recent Tickets -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Tickets</h5>
                    <a href="{{ route('tickets.index', ['client_id' => $client->id]) }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dashboardData['tickets']['recent_tickets'] as $ticket)
                                <tr>
                                    <td>#{{ $ticket->id }}</td>
                                    <td>
                                        <a href="{{ route('tickets.show', $ticket) }}">
                                            {{ Str::limit($ticket->subject, 40) }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $ticket->status === 'open' ? 'warning' : ($ticket->status === 'closed' ? 'success' : 'info') }}">
                                            {{ $ticket->status }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $ticket->priority === 'high' ? 'danger' : ($ticket->priority === 'medium' ? 'warning' : 'secondary') }}">
                                            {{ $ticket->priority }}
                                        </span>
                                    </td>
                                    <td>{{ $ticket->created_at->format('M d, Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Client Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Client Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Primary Contact:</dt>
                        <dd class="col-sm-7">
                            @if($dashboardData['overview']['primary_contact'])
                                {{ $dashboardData['overview']['primary_contact']->name }}
                                <br><small class="text-muted">{{ $dashboardData['overview']['primary_contact']->email }}</small>
                            @else
                                <span class="text-muted">Not set</span>
                            @endif
                        </dd>

                        <dt class="col-sm-5">Total Contacts:</dt>
                        <dd class="col-sm-7">{{ $dashboardData['overview']['total_contacts'] }}</dd>

                        <dt class="col-sm-5">Locations:</dt>
                        <dd class="col-sm-7">{{ $dashboardData['overview']['total_locations'] }}</dd>

                        <dt class="col-sm-5">Outstanding:</dt>
                        <dd class="col-sm-7">
                            <span class="{{ $dashboardData['financial']['outstanding_balance'] > 0 ? 'text-danger' : 'text-success' }}">
                                ${{ number_format($dashboardData['financial']['outstanding_balance'], 2) }}
                            </span>
                        </dd>

                        <dt class="col-sm-5">Credit Status:</dt>
                        <dd class="col-sm-7">
                            <span class="badge badge-success">Good Standing</span>
                        </dd>
                    </dl>
                </div>
            </div>

            <!-- Expiring Items -->
            @if(count($dashboardData['expiring_items']) > 0)
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle"></i> Expiring Soon
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($dashboardData['expiring_items'] as $type => $items)
                            @foreach($items as $item)
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">{{ $item->name ?? $item->domain_name ?? 'Item' }}</h6>
                                        <small class="text-muted">{{ ucfirst($type) }}</small>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-danger">
                                            {{ \Carbon\Carbon::parse($item->expiry_date ?? $item->warranty_expiry)->format('M d, Y') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Upcoming Events -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Upcoming Events</h5>
                </div>
                <div class="card-body">
                    @if($dashboardData['upcoming_events']->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($dashboardData['upcoming_events'] as $event)
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">{{ $event->title ?? $event->name }}</h6>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($event->start_date ?? $event->due_date)->format('M d, Y') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">No upcoming events</p>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('tickets.create', ['client_id' => $client->id]) }}" class="btn btn-outline-primary">
                            <i class="fas fa-ticket-alt"></i> Create Ticket
                        </a>
                        <a href="{{ route('invoices.create', ['client_id' => $client->id]) }}" class="btn btn-outline-success">
                            <i class="fas fa-file-invoice"></i> Create Invoice
                        </a>
                        <a href="{{ route('projects.create', ['client_id' => $client->id]) }}" class="btn btn-outline-info">
                            <i class="fas fa-project-diagram"></i> New Project
                        </a>
                        <a href="{{ route('assets.create', ['client_id' => $client->id]) }}" class="btn btn-outline-secondary">
                            <i class="fas fa-server"></i> Add Asset
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}
.timeline-item {
    position: relative;
    padding-bottom: 20px;
}
.timeline-item:not(:last-child):before {
    content: '';
    position: absolute;
    left: -21px;
    top: 20px;
    height: calc(100% - 20px);
    width: 2px;
    background: #e9ecef;
}
.timeline-marker {
    position: absolute;
    left: -25px;
    top: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue Chart
const ctx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
            label: 'Revenue',
            data: [12000, 19000, 15000, 25000, 22000, 30000, 28000, 35000, 32000, 38000, 35000, 40000],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Refresh Dashboard Function
function refreshDashboard() {
    fetch('{{ route("clients.dashboard.refresh", $client) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        // Show success message
        alert(data.message);
        // Reload page to show fresh data
        window.location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to refresh dashboard');
    });
}
</script>
@endpush

@endsection