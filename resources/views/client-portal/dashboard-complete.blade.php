@extends('client-portal.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="portal-container">
    <!-- Welcome Header -->
    <div class="portal-row portal-mb-4">
        <div class="portal-col-12">
            <div class="portal-d-flex portal-justify-content-between portal-align-items-center">
                <div>
                    <h1 class="portal-text-3xl portal-mb-0 text-gray-800">Welcome back, {{ $client->name }}!</h1>
                    <p class="text-gray-600">Here's an overview of your account and recent activity</p>
                </div>
                <div style="text-align: right;">
                    <small class="text-gray-600">Last login: {{ now()->format('M j, Y g:i A') }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Actions Alert -->
    @if(isset($pendingActions) && count($pendingActions) > 0)
    <div class="portal-row portal-mb-4">
        <div class="portal-col-12">
            <div class="portal-alert portal-alert-warning portal-alert-dismissible portal-fade show" role="alert">
                <h5 class="portal-alert-heading"><i class="fas fa-exclamation-triangle"></i> Action Required</h5>
                @foreach($pendingActions as $action)
                <p class="portal-mb-2">
                    <strong>{{ $action['message'] }}</strong>
                    <a href="{{ $action['action_url'] }}" class="portal-btn portal-btn-sm portal-btn-outline-warning" style="margin-left: 0.5rem;">
                        Take Action
                    </a>
                </p>
                @endforeach
                <button type="button" class="portal-alert-close" onclick="this.parentElement.style.display='none'">
                    <span>&times;</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Main Statistics Cards Row 1: Contracts & Invoices -->
    <div class="portal-row portal-mb-4">
        <!-- Total Contracts -->
        <div class="portal-col-xl-3 portal-col-6 portal-mb-4">
            <div class="portal-card portal-card-border-primary portal-shadow portal-h-100 portal-py-2">
                <div class="portal-card-body">
                    <div class="portal-d-flex portal-align-items-center">
                        <div class="portal-col portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-primary portal-uppercase portal-mb-1">
                                Total Contracts
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800">
                                {{ $contractStats['total_contracts'] ?? 0 }}
                            </div>
                        </div>
                        <div class="portal-col-auto">
                            <i class="fas fa-file-contract fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Outstanding Amount -->
        <div class="portal-col-xl-3 portal-col-6 portal-mb-4">
            <div class="portal-card portal-card-border-warning portal-shadow portal-h-100 portal-py-2">
                <div class="portal-card-body">
                    <div class="portal-d-flex portal-align-items-center">
                        <div class="portal-col portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-warning portal-uppercase portal-mb-1">
                                Outstanding Amount
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800">
                                ${{ number_format($invoiceStats['outstanding_amount'] ?? 0, 2) }}
                            </div>
                        </div>
                        <div class="portal-col-auto">
                            <i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Open Tickets -->
        <div class="portal-col-xl-3 portal-col-6 portal-mb-4">
            <div class="portal-card portal-card-border-danger portal-shadow portal-h-100 portal-py-2">
                <div class="portal-card-body">
                    <div class="portal-d-flex portal-align-items-center">
                        <div class="portal-col portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-danger portal-uppercase portal-mb-1">
                                Open Tickets
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800">
                                {{ $ticketStats['open_tickets'] ?? 0 }}
                            </div>
                        </div>
                        <div class="portal-col-auto">
                            <i class="fas fa-ticket-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Assets -->
        <div class="portal-col-xl-3 portal-col-6 portal-mb-4">
            <div class="portal-card portal-card-border-info portal-shadow portal-h-100 portal-py-2">
                <div class="portal-card-body">
                    <div class="portal-d-flex portal-align-items-center">
                        <div class="portal-col portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-info portal-uppercase portal-mb-1">
                                Total Assets
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800">
                                {{ $assetStats['total_assets'] ?? 0 }}
                            </div>
                        </div>
                        <div class="portal-col-auto">
                            <i class="fas fa-server fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary Statistics Row 2: Additional Stats -->
    <div class="portal-row portal-mb-4">
        <!-- Active Contracts -->
        <div class="portal-col-xl-3 portal-col-6 portal-mb-4">
            <div class="portal-card portal-card-border-success portal-shadow portal-h-100 portal-py-2">
                <div class="portal-card-body">
                    <div class="portal-d-flex portal-align-items-center">
                        <div class="portal-col portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-success portal-uppercase portal-mb-1">
                                Active Contracts
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800">
                                {{ $contractStats['active_contracts'] ?? 0 }}
                            </div>
                        </div>
                        <div class="portal-col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Invoices -->
        <div class="portal-col-xl-3 portal-col-6 portal-mb-4">
            <div class="portal-card portal-card-border-primary portal-shadow portal-h-100 portal-py-2">
                <div class="portal-card-body">
                    <div class="portal-d-flex portal-align-items-center">
                        <div class="portal-col portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-primary portal-uppercase portal-mb-1">
                                Total Invoices
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800">
                                {{ $invoiceStats['total_invoices'] ?? 0 }}
                            </div>
                        </div>
                        <div class="portal-col-auto">
                            <i class="fas fa-file-invoice fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Tickets -->
        <div class="portal-col-xl-3 portal-col-6 portal-mb-4">
            <div class="portal-card portal-card-border-secondary portal-shadow portal-h-100 portal-py-2">
                <div class="portal-card-body">
                    <div class="portal-d-flex portal-align-items-center">
                        <div class="portal-col portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-secondary portal-uppercase portal-mb-1">
                                Total Tickets
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800">
                                {{ $ticketStats['total_tickets'] ?? 0 }}
                            </div>
                        </div>
                        <div class="portal-col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Assets -->
        <div class="portal-col-xl-3 portal-col-6 portal-mb-4">
            <div class="portal-card portal-card-border-success portal-shadow portal-h-100 portal-py-2">
                <div class="portal-card-body">
                    <div class="portal-d-flex portal-align-items-center">
                        <div class="portal-col portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-success portal-uppercase portal-mb-1">
                                Active Assets
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800">
                                {{ $assetStats['active_assets'] ?? 0 }}
                            </div>
                        </div>
                        <div class="portal-col-auto">
                            <i class="fas fa-power-off fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Sections -->
    <div class="portal-row">
        <!-- Left Column: Recent Items -->
        <div class="portal-col-12 portal-col-lg-8">
            
            <!-- Recent Contracts -->
            <div class="portal-card portal-shadow portal-mb-4">
                <div class="px-6 py-4 portal-border-b portal-bg-gray-50 py-3 portal-d-flex portal-justify-content-between portal-align-items-center">
                    <h6 class="portal-mb-0 portal-font-bold portal-text-primary">
                        <i class="fas fa-file-contract portal-mr-2"></i>Recent Contracts
                    </h6>
                    <a href="{{ route('client.contracts') }}" class="portal-btn portal-btn-sm portal-btn-primary">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="portal-card-body">
                    <div class="portal-table-responsive">
                        <table class="portal-table portal-min-w-full">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">Contract</th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">Type</th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">Status</th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($contracts) && count($contracts) > 0)
                                    @foreach($contracts->take(3) as $contract)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="portal-font-bold portal-text-sm">{{ $contract->title }}</div>
                                            <div class="text-muted portal-text-xs">{{ $contract->contract_number }}</div>
                                        </td>
                                        <td class="px-4 py-3 portal-text-sm">{{ ucwords(str_replace('_', ' ', $contract->contract_type)) }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex px-2 py-1 portal-text-xs portal-font-medium rounded-full 
                                                @if($contract->status === 'active') bg-green-100 text-green-800
                                                @elseif($contract->status === 'pending') bg-yellow-100 text-yellow-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ ucfirst($contract->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 portal-text-sm">${{ number_format($contract->contract_value, 2) }}</td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr><td colspan="4" class="px-4 py-3 text-center text-gray-500 portal-text-sm">No contracts available.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Tickets -->
            <div class="portal-card portal-shadow portal-mb-4">
                <div class="px-6 py-4 portal-border-b portal-bg-gray-50 py-3 portal-d-flex portal-justify-content-between portal-align-items-center">
                    <h6 class="portal-mb-0 portal-font-bold portal-text-primary">
                        <i class="fas fa-ticket-alt portal-mr-2"></i>Recent Tickets
                    </h6>
                    <a href="{{ route('client.tickets') ?? '#' }}" class="portal-btn portal-btn-sm portal-btn-primary">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="portal-card-body">
                    <div class="portal-table-responsive">
                        <table class="portal-table portal-min-w-full">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">Ticket</th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">Subject</th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">Priority</th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($tickets) && count($tickets) > 0)
                                    @foreach($tickets->take(3) as $ticket)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="portal-font-bold portal-text-sm">#{{ $ticket->number }}</div>
                                            <div class="text-muted portal-text-xs">{{ $ticket->created_at->format('M j, Y') }}</div>
                                        </td>
                                        <td class="px-4 py-3 portal-text-sm">{{ Str::limit($ticket->subject, 40) }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex px-2 py-1 portal-text-xs portal-font-medium rounded-full
                                                @if($ticket->priority === 'critical') bg-red-100 text-red-800
                                                @elseif($ticket->priority === 'high') bg-orange-100 text-orange-800
                                                @elseif($ticket->priority === 'normal') bg-blue-100 text-blue-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ ucfirst($ticket->priority ?? 'normal') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex px-2 py-1 portal-text-xs portal-font-medium rounded-full
                                                @if($ticket->status === 'open') bg-red-100 text-red-800
                                                @elseif($ticket->status === 'in_progress') bg-yellow-100 text-yellow-800
                                                @elseif($ticket->status === 'resolved') bg-green-100 text-green-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ ucwords(str_replace('_', ' ', $ticket->status)) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr><td colspan="4" class="px-4 py-3 text-center text-gray-500 portal-text-sm">No recent tickets.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Assets -->
            <div class="portal-card portal-shadow portal-mb-4">
                <div class="px-6 py-4 portal-border-b portal-bg-gray-50 py-3 portal-d-flex portal-justify-content-between portal-align-items-center">
                    <h6 class="portal-mb-0 portal-font-bold portal-text-primary">
                        <i class="fas fa-server portal-mr-2"></i>Recent Assets
                    </h6>
                    <a href="{{ route('client.assets') ?? '#' }}" class="portal-btn portal-btn-sm portal-btn-primary">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="portal-card-body">
                    <div class="portal-table-responsive">
                        <table class="portal-table portal-min-w-full">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">Asset</th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">Type</th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">Status</th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">Location</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($assets) && count($assets) > 0)
                                    @foreach($assets->take(3) as $asset)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="portal-font-bold portal-text-sm">{{ $asset->name }}</div>
                                            <div class="text-muted portal-text-xs">{{ $asset->make }} {{ $asset->model }}</div>
                                        </td>
                                        <td class="px-4 py-3 portal-text-sm">{{ $asset->type }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex px-2 py-1 portal-text-xs portal-font-medium rounded-full
                                                @if($asset->status === 'active') bg-green-100 text-green-800
                                                @elseif($asset->status === 'maintenance') bg-yellow-100 text-yellow-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ ucfirst($asset->status ?? 'unknown') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 portal-text-sm">{{ $asset->location->name ?? 'Not assigned' }}</td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr><td colspan="4" class="px-4 py-3 text-center text-gray-500 portal-text-sm">No assets available.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Sidebar -->
        <div class="portal-col-12 portal-col-lg-4">
            
            <!-- Upcoming Milestones -->
            <div class="portal-card portal-shadow portal-mb-4">
                <div class="px-6 py-4 portal-border-b portal-bg-gray-50 py-3">
                    <h6 class="portal-mb-0 portal-font-bold portal-text-primary">
                        <i class="fas fa-tasks portal-mr-2"></i>Upcoming Milestones
                    </h6>
                </div>
                <div class="portal-card-body">
                    @if(isset($upcomingMilestones) && count($upcomingMilestones) > 0)
                        <div class="space-y-3">
                            @foreach($upcomingMilestones as $milestone)
                            <div class="portal-d-flex portal-align-items-center p-3 bg-gray-50 rounded-lg">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full portal-d-flex portal-align-items-center justify-center">
                                        <i class="fas fa-flag portal-text-xs text-blue-600"></i>
                                    </div>
                                </div>
                                <div class="ml-3 flex-1 min-w-0">
                                    <p class="portal-text-sm portal-font-medium text-gray-900 truncate">
                                        {{ $milestone->title }}
                                    </p>
                                    <p class="portal-text-xs text-gray-500">
                                        Due: {{ $milestone->due_date->format('M j, Y') }}
                                    </p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 portal-text-sm text-center py-4">
                            No upcoming milestones.
                        </p>
                    @endif
                </div>
            </div>

            <!-- System Status -->
            <div class="portal-card portal-shadow portal-mb-4">
                <div class="px-6 py-4 portal-border-b portal-bg-gray-50 py-3">
                    <h6 class="portal-mb-0 portal-font-bold portal-text-primary">
                        <i class="fas fa-heartbeat portal-mr-2"></i>System Status
                    </h6>
                </div>
                <div class="portal-card-body">
                    <div class="space-y-3">
                        <div class="portal-d-flex portal-justify-content-between portal-align-items-center">
                            <span class="portal-text-sm text-gray-600">Maintenance Due</span>
                            <span class="inline-flex px-2 py-1 portal-text-xs portal-font-medium rounded-full bg-yellow-100 text-yellow-800">
                                {{ $assetStats['maintenance_due'] ?? 0 }} Assets
                            </span>
                        </div>
                        <div class="portal-d-flex portal-justify-content-between portal-align-items-center">
                            <span class="portal-text-sm text-gray-600">Warranty Expiring</span>
                            <span class="inline-flex px-2 py-1 portal-text-xs portal-font-medium rounded-full bg-orange-100 text-orange-800">
                                {{ $assetStats['warranty_expiring'] ?? 0 }} Assets
                            </span>
                        </div>
                        <div class="portal-d-flex portal-justify-content-between portal-align-items-center">
                            <span class="portal-text-sm text-gray-600">Open Tickets</span>
                            <span class="inline-flex px-2 py-1 portal-text-xs portal-font-medium rounded-full 
                                @if(($ticketStats['open_tickets'] ?? 0) > 5) bg-red-100 text-red-800 
                                @elseif(($ticketStats['open_tickets'] ?? 0) > 0) bg-yellow-100 text-yellow-800 
                                @else bg-green-100 text-green-800 @endif">
                                {{ $ticketStats['open_tickets'] ?? 0 }} Tickets
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="portal-card portal-shadow">
                <div class="px-6 py-4 portal-border-b portal-bg-gray-50 py-3">
                    <h6 class="portal-mb-0 portal-font-bold portal-text-primary">
                        <i class="fas fa-bolt portal-mr-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="portal-card-body">
                    <div class="space-y-2">
                        <a href="{{ route('client.contracts') }}" class="portal-btn portal-w-100 portal-btn-outline-primary portal-text-sm">
                            <i class="fas fa-file-contract portal-mr-2"></i>View Contracts
                        </a>
                        <a href="{{ route('client.tickets') ?? '#' }}" class="portal-btn portal-w-100 portal-btn-outline-primary portal-text-sm">
                            <i class="fas fa-ticket-alt portal-mr-2"></i>Submit Ticket
                        </a>
                        <a href="{{ route('client.profile') }}" class="portal-btn portal-w-100 portal-btn-outline-primary portal-text-sm">
                            <i class="fas fa-user-cog portal-mr-2"></i>Update Profile
                        </a>
                        <a href="mailto:support@{{ parse_url(config('app.url'), PHP_URL_HOST) }}" class="portal-btn portal-w-100 portal-btn-outline-primary portal-text-sm">
                            <i class="fas fa-life-ring portal-mr-2"></i>Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Additional styles for enhanced look */
.space-y-2 > * + * {
    margin-top: 0.5rem;
}

.space-y-3 > * + * {
    margin-top: 0.75rem;
}

.truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.tracking-wider {
    letter-spacing: 0.05em;
}

.flex-shrink-0 {
    flex-shrink: 0;
}

.flex-1 {
    flex: 1 1 0%;
}

.min-w-0 {
    min-width: 0px;
}

/* Badge colors for status indicators */
.bg-green-100 { background-color: #dcfce7; }
.text-green-800 { color: #166534; }
.bg-yellow-100 { background-color: #fef3c7; }
.text-yellow-800 { color: #92400e; }
.bg-red-100 { background-color: #fee2e2; }
.text-red-800 { color: #991b1b; }
.bg-blue-100 { background-color: #dbeafe; }
.text-blue-800 { color: #1e40af; }
.bg-orange-100 { background-color: #fed7aa; }
.text-orange-800 { color: #9a3412; }
.bg-gray-100 { background-color: #f3f4f6; }
.text-gray-800 { color: #1f2937; }

/* Hover effects for table rows */
.portal-table tbody tr:hover {
    background-color: #f9fafb;
}

/* Responsive layout adjustments */
@media (min-width: 1024px) {
    .portal-col-lg-4 {
        flex: 0 0 33.333333%;
        max-width: 33.333333%;
    }
    
    .portal-col-lg-8 {
        flex: 0 0 66.666667%;
        max-width: 66.666667%;
    }
}
</style>
@endpush