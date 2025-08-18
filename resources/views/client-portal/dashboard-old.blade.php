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
                    <p class="text-gray-600">Here's an overview of your contracts and recent activity</p>
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

    <!-- Statistics Cards -->
    <div class="portal-row portal-mb-4">
        <!-- Total Contracts Card -->
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

        <!-- Active Contracts Card -->
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

        <!-- Pending Signatures Card -->
        <div class="portal-col-xl-3 portal-col-6 portal-mb-4">
            <div class="portal-card portal-card-border-warning portal-shadow portal-h-100 portal-py-2">
                <div class="portal-card-body">
                    <div class="portal-d-flex portal-align-items-center">
                        <div class="portal-col portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-warning portal-uppercase portal-mb-1">
                                Pending Signatures
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800">
                                {{ $contractStats['pending_signatures'] ?? 0 }}
                            </div>
                        </div>
                        <div class="portal-col-auto">
                            <i class="fas fa-signature fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Value Card -->
        <div class="portal-col-xl-3 portal-col-6 portal-mb-4">
            <div class="portal-card portal-card-border-info portal-shadow portal-h-100 portal-py-2">
                <div class="portal-card-body">
                    <div class="portal-d-flex portal-align-items-center">
                        <div class="portal-col portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-info portal-uppercase portal-mb-1">
                                Total Value
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800">
                                ${{ number_format($contractStats['total_contract_value'] ?? 0, 2) }}
                            </div>
                        </div>
                        <div class="portal-col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="portal-row">
        <!-- Recent Contracts -->
        <div class="portal-col-12 portal-col-lg-8">
            <div class="portal-card portal-shadow portal-mb-4">
                <div class="px-6 py-4 portal-border-b portal-bg-gray-50 py-3 portal-d-flex portal-justify-content-between portal-align-items-center">
                    <h6 class="portal-mb-0 portal-font-bold portal-text-primary">Recent Contracts</h6>
                    <a href="{{ route('client.contracts') }}" class="portal-btn portal-btn-sm portal-btn-primary">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="portal-card-body">
                    <div class="portal-table-responsive">
                        <table class="portal-table portal-min-w-full portal-divide-y portal-divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase tracking-wider">
                                        Contract
                                    </th>
                                    <th class="px-6 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th class="px-6 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase tracking-wider">
                                        Value
                                    </th>
                                    <th class="px-6 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white portal-divide-y portal-divide-gray-200">
                                @if(isset($contracts) && count($contracts) > 0)
                                    @foreach($contracts->take(5) as $contract)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="portal-font-bold">{{ $contract->title }}</div>
                                            <div class="text-muted portal-text-sm">{{ $contract->contract_number }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-gray-900 portal-text-sm">
                                                {{ ucwords(str_replace('_', ' ', $contract->contract_type)) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 portal-text-xs portal-font-medium rounded-full 
                                                @if($contract->status === 'active') bg-green-100 text-green-800
                                                @elseif($contract->status === 'pending') bg-yellow-100 text-yellow-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ ucfirst($contract->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap portal-text-sm text-gray-900">
                                            ${{ number_format($contract->contract_value, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap portal-text-sm portal-font-medium">
                                            <a href="{{ route('client.contracts.show', $contract) }}" class="text-blue-600 hover:text-blue-900">
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 whitespace-nowrap portal-text-sm text-gray-500 text-center">
                                            No contracts available.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Milestones -->
        <div class="portal-col-12 portal-col-lg-4">
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
                            <i class="fas fa-file-contract portal-mr-2"></i>View All Contracts
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