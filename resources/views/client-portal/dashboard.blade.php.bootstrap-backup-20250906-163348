@extends('client-portal.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="portal-container">
    <!-- Welcome Header -->
    <div class="portal-row portal-mb-4">
        <div class="portal-col-12">
            <div class="portal-d-flex portal-justify-content-between portal-align-items-center">
                <div>
                    <h1 class="portal-text-3xl portal-mb-0 text-gray-800 dark:text-gray-200">Welcome back, {{ $contact->name }}!</h1>
                    <p class="text-gray-600 dark:text-gray-400">{{ $client->name }} - Here's your account overview</p>
                </div>
                <div style="text-align: right;">
                    <small class="text-gray-600 dark:text-gray-400">{{ now()->format('M j, Y g:i A') }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Critical Alerts -->
    @php
        $criticalAlerts = [];
        if (isset($ticketStats) && ($ticketStats['open_tickets'] ?? 0) > 0) {
            $criticalAlerts[] = ['type' => 'danger', 'message' => ($ticketStats['open_tickets'] ?? 0) . ' open support tickets need attention', 'action' => route('client.tickets') ?? '#'];
        }
        if (isset($invoiceStats) && ($invoiceStats['outstanding_amount'] ?? 0) > 0) {
            $criticalAlerts[] = ['type' => 'warning', 'message' => '$' . number_format($invoiceStats['outstanding_amount'] ?? 0, 2) . ' in outstanding invoices', 'action' => route('client.invoices')];
        }
        if (isset($assetStats) && ($assetStats['maintenance_due'] ?? 0) > 0) {
            $criticalAlerts[] = ['type' => 'info', 'message' => ($assetStats['maintenance_due'] ?? 0) . ' assets require maintenance', 'action' => route('client.assets') ?? '#'];
        }
    @endphp

    @if(count($criticalAlerts) > 0)
    <div class="portal-row portal-mb-4">
        @foreach($criticalAlerts as $alert)
        <div class="portal-col-12 portal-mb-2">
            <div class="portal-alert portal-alert-{{ $alert['type'] }} portal-alert-dismissible portal-fade show" role="alert">
                <strong>{{ $alert['message'] }}</strong>
                <a href="{{ $alert['action'] }}" class="portal-btn portal-btn-sm portal-btn-outline-primary" style="margin-left: 0.5rem;">
                    View Details
                </a>
                <button type="button" class="portal-alert-close" onclick="this.parentElement.style.display='none'">
                    <span>&times;</span>
                </button>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Key Statistics Cards -->
    <div class="portal-row portal-mb-4">
        @if(isset($contractStats))
        <div class="portal-col-6 portal-col-xl-3 portal-mb-4">
            <div class="portal-card portal-card-border-primary portal-shadow portal-h-100">
                <div class="portal-card-body portal-py-4">
                    <div class="portal-d-flex portal-align-items-center">
                        <div class="portal-col portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-primary portal-uppercase portal-mb-1">
                                Active Contracts
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800 dark:text-gray-200">
                                {{ $contractStats['active_contracts'] ?? 0 }}
                            </div>
                        </div>
                        <div class="portal-col-auto">
                            <i class="fas fa-file-contract fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(isset($invoiceStats))
        <div class="portal-col-6 portal-col-xl-3 portal-mb-4">
            <div class="portal-card portal-card-border-warning portal-shadow portal-h-100">
                <div class="portal-card-body portal-py-4">
                    <div class="portal-d-flex portal-align-items-center">
                        <div class="portal-col portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-warning portal-uppercase portal-mb-1">
                                Outstanding
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800 dark:text-gray-200">
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
        @endif

        @if(isset($ticketStats))
        <div class="portal-col-6 portal-col-xl-3 portal-mb-4">
            <div class="portal-card portal-card-border-danger portal-shadow portal-h-100">
                <div class="portal-card-body portal-py-4">
                    <div class="portal-d-flex portal-align-items-center">
                        <div class="portal-col portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-danger portal-uppercase portal-mb-1">
                                Open Tickets
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800 dark:text-gray-200">
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
        @endif

        @if(isset($assetStats))
        <div class="portal-col-6 portal-col-xl-3 portal-mb-4">
            <div class="portal-card portal-card-border-info portal-shadow portal-h-100">
                <div class="portal-card-body portal-py-4">
                    <div class="portal-d-flex portal-align-items-center">
                        <div class="portal-col portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-info portal-uppercase portal-mb-1">
                                Total Assets
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800 dark:text-gray-200">
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
        @endif
    </div>

    <!-- Quick Overview & Actions -->
    <div class="portal-row">
        <!-- System Health Overview -->
        <div class="portal-col-12 portal-col-lg-8 portal-mb-4">
            <div class="portal-card portal-shadow">
                <div class="px-6 py-4 portal-border-b portal-bg-gray-50 dark:bg-gray-900 py-3">
                    <h6 class="portal-mb-0 portal-font-bold portal-text-primary">
                        <i class="fas fa-heartbeat portal-mr-2"></i>System Health
                    </h6>
                </div>
                <div class="portal-card-body">
                    <div class="portal-row">
                        @if(isset($assetStats))
                        <div class="portal-col-6 portal-col-xl-4 portal-mb-4">
                            <div class="portal-d-flex portal-align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-yellow-100 rounded-full portal-d-flex portal-align-items-center justify-center">
                                        <i class="fas fa-tools portal-text-sm text-yellow-600"></i>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p class="portal-text-sm portal-font-medium text-gray-900 dark:text-white">
                                        {{ $assetStats['maintenance_due'] ?? 0 }} Assets
                                    </p>
                                    <p class="portal-text-xs text-gray-500">Need Maintenance</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="portal-col-6 portal-col-xl-4 portal-mb-4">
                            <div class="portal-d-flex portal-align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-orange-100 rounded-full portal-d-flex portal-align-items-center justify-center">
                                        <i class="fas fa-shield-alt portal-text-sm text-orange-600"></i>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p class="portal-text-sm portal-font-medium text-gray-900 dark:text-white">
                                        {{ $assetStats['warranty_expiring'] ?? 0 }} Assets
                                    </p>
                                    <p class="portal-text-xs text-gray-500">Warranty Expiring</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if(isset($ticketStats))
                        <div class="portal-col-6 portal-col-xl-4 portal-mb-4">
                            <div class="portal-d-flex portal-align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 {{ ($ticketStats['open_tickets'] ?? 0) > 0 ? 'bg-red-100' : 'bg-green-100' }} rounded-full portal-d-flex portal-align-items-center justify-center">
                                        <i class="fas fa-ticket-alt portal-text-sm {{ ($ticketStats['open_tickets'] ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}"></i>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p class="portal-text-sm portal-font-medium text-gray-900 dark:text-white">
                                        {{ $ticketStats['open_tickets'] ?? 0 }} Open
                                    </p>
                                    <p class="portal-text-xs text-gray-500">Support Tickets</p>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="portal-col-12 portal-col-lg-4 portal-mb-4">
            <div class="portal-card portal-shadow">
                <div class="px-6 py-4 portal-border-b portal-bg-gray-50 dark:bg-gray-900 py-3">
                    <h6 class="portal-mb-0 portal-font-bold portal-text-primary">
                        <i class="fas fa-bolt portal-mr-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="portal-card-body">
                    <div class="space-y-2">
                        @if(isset($contractStats))
                        <a href="{{ route('client.contracts') }}" class="portal-btn portal-w-100 portal-btn-outline-primary portal-text-sm">
                            <i class="fas fa-file-contract portal-mr-2"></i>View Contracts
                        </a>
                        @endif
                        
                        @if(isset($invoiceStats))
                        <a href="{{ route('client.invoices') }}" class="portal-btn portal-w-100 portal-btn-outline-primary portal-text-sm">
                            <i class="fas fa-file-invoice portal-mr-2"></i>View Invoices
                        </a>
                        @endif
                        
                        @if(isset($ticketStats))
                        <a href="{{ route('client.tickets') ?? '#' }}" class="portal-btn portal-w-100 portal-btn-outline-primary portal-text-sm">
                            <i class="fas fa-ticket-alt portal-mr-2"></i>Support Tickets
                        </a>
                        @endif
                        
                        @if(isset($assetStats))
                        <a href="{{ route('client.assets') ?? '#' }}" class="portal-btn portal-w-100 portal-btn-outline-primary portal-text-sm">
                            <i class="fas fa-server portal-mr-2"></i>View Assets
                        </a>
                        @endif
                        
                        <a href="{{ route('client.profile') }}" class="portal-btn portal-w-100 portal-btn-outline-primary portal-text-sm">
                            <i class="fas fa-user-cog portal-mr-2"></i>Account Settings
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
.space-y-2 > * + * { margin-top: 0.5rem; }
.flex-shrink-0 { flex-shrink: 0; }
.w-10 { width: 2.5rem; }
.h-10 { height: 2.5rem; }
.ml-3 { margin-left: 0.75rem; }
.rounded-full { border-radius: 9999px; }

/* Badge colors */
.bg-green-100 { background-color: #dcfce7; } .text-green-600 { color: #16a34a; }
.bg-yellow-100 { background-color: #fef3c7; } .text-yellow-600 { color: #ca8a04; }
.bg-red-100 { background-color: #fee2e2; } .text-red-600 { color: #dc2626; }
.bg-orange-100 { background-color: #fed7aa; } .text-orange-600 { color: #ea580c; }

/* Responsive */
@media (min-width: 1024px) {
    .portal-col-lg-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
    .portal-col-lg-8 { flex: 0 0 66.666667%; max-width: 66.666667%; }
}
</style>
@endpush