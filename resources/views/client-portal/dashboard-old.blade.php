@extends('client-portal.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="portal-container mx-auto mx-auto">
    <!-- Welcome Header -->
    <div class="portal-flex flex-wrap -mx-4 portal-mb-6">
        <div class="portal-flex-1 px-6-12">
            <div class="portal-flex portal-justify-between portal-items-center">
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
    <div class="portal-flex flex-wrap -mx-4 portal-mb-6">
        <div class="portal-flex-1 px-6-12">
            <div class="portal-px-6 py-6 rounded mb-6 portal-bg-yellow-100 border border-yellow-400 text-yellow-700 portal-px-6 py-6 rounded mb-6-dismissible portal-fade show" role="alert">
                <h5 class="portal-px-6 py-6 rounded mb-6-heading"><i class="fas fa-exclamation-triangle"></i> Action Required</h5>
                @foreach($pendingActions as $action)
                <p class="portal-mb-2">
                    <strong>{{ $action['message'] }}</strong>
                    <a href="{{ $action['action_url'] }}" class="portal-px-4 py-2 font-medium rounded-md transition-colors portal-px-6 py-1 text-sm portal-px-6 py-2 font-medium rounded-md transition-colors-outline-warning" style="margin-left: 0.5rem;">
                        Take Action
                    </a>
                </p>
                @endforeach
                <button type="button" class="portal-px-6 py-6 rounded mb-6-close" onclick="this.parentElement.style.display='none'">
                    <span>&times;</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Statistics Cards -->
    <div class="portal-flex flex-wrap -mx-4 portal-mb-6">
        <!-- Total Contracts Card -->
        <div class="portal-flex-1 px-6-xl-3 portal-flex-1 px-6-6 portal-mb-6">
            <div class="portal-bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden portal-bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-border-primary portal-shadow portal-h-100 portal-py-2">
                <div class="portal-bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                    <div class="portal-flex portal-items-center">
                        <div class="portal-flex-1 px-6 portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-blue-600 dark:text-blue-400 portal-uppercase portal-mb-1">
                                Total Contracts
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800">
                                {{ $contractStats['total_contracts'] ?? 0 }}
                            </div>
                        </div>
                        <div class="portal-flex-1 px-6-auto">
                            <i class="fas fa-file-contract fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Contracts Card -->
        <div class="portal-flex-1 px-6-xl-3 portal-flex-1 px-6-6 portal-mb-6">
            <div class="portal-bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden portal-bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-border-success portal-shadow portal-h-100 portal-py-2">
                <div class="portal-bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                    <div class="portal-flex portal-items-center">
                        <div class="portal-flex-1 px-6 portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-green-600 dark:text-green-400 portal-uppercase portal-mb-1">
                                Active Contracts
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800">
                                {{ $contractStats['active_contracts'] ?? 0 }}
                            </div>
                        </div>
                        <div class="portal-flex-1 px-6-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Signatures Card -->
        <div class="portal-flex-1 px-6-xl-3 portal-flex-1 px-6-6 portal-mb-6">
            <div class="portal-bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden portal-bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-border-warning portal-shadow portal-h-100 portal-py-2">
                <div class="portal-bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                    <div class="portal-flex portal-items-center">
                        <div class="portal-flex-1 px-6 portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-yellow-600 dark:text-yellow-400 portal-uppercase portal-mb-1">
                                Pending Signatures
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800">
                                {{ $contractStats['pending_signatures'] ?? 0 }}
                            </div>
                        </div>
                        <div class="portal-flex-1 px-6-auto">
                            <i class="fas fa-signature fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Value Card -->
        <div class="portal-flex-1 px-6-xl-3 portal-flex-1 px-6-6 portal-mb-6">
            <div class="portal-bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden portal-bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-border-info portal-shadow portal-h-100 portal-py-2">
                <div class="portal-bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                    <div class="portal-flex portal-items-center">
                        <div class="portal-flex-1 px-6 portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-cyan-600 dark:text-cyan-400 portal-uppercase portal-mb-1">
                                Total Value
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800">
                                ${{ number_format($contractStats['total_contract_value'] ?? 0, 2) }}
                            </div>
                        </div>
                        <div class="portal-flex-1 px-6-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="portal-flex flex-wrap -mx-4">
        <!-- Recent Contracts -->
        <div class="portal-w-full px-6 portal-flex-1 px-6-lg-8">
            <div class="portal-bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden portal-shadow portal-mb-6">
                <div class="px-6 py-6 portal-border-b portal-bg-gray-50 py-6 portal-flex portal-justify-between portal-items-center">
                    <h6 class="portal-mb-0 portal-font-bold portal-text-blue-600 dark:text-blue-400">Recent Contracts</h6>
                    <a href="{{ route('client.contracts') }}" class="portal-px-4 py-2 font-medium rounded-md transition-colors portal-px-6 py-1 text-sm portal-px-6 py-2 font-medium rounded-md transition-colors-primary">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="portal-bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                    <div class="portal-min-w-full divide-y divide-gray-200 dark:divide-gray-700-responsive">
                        <table class="portal-min-w-full divide-y divide-gray-200 dark:divide-gray-700 portal-min-w-full portal-divide-y portal-divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-6 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase tracking-wider">
                                        Contract
                                    </th>
                                    <th class="px-6 py-6 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th class="px-6 py-6 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-6 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase tracking-wider">
                                        Value
                                    </th>
                                    <th class="px-6 py-6 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white portal-divide-y portal-divide-gray-200">
                                @if(isset($contracts) && count($contracts) > 0)
                                    @foreach($contracts->take(5) as $contract)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-6 whitespace-nowrap">
                                            <div class="portal-font-bold">{{ $contract->title }}</div>
                                            <div class="text-gray-600 dark:text-gray-400 portal-text-sm">{{ $contract->contract_number }}</div>
                                        </td>
                                        <td class="px-6 py-6 whitespace-nowrap">
                                            <span class="text-gray-900 portal-text-sm">
                                                {{ ucwords(str_replace('_', ' ', $contract->contract_type)) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-6 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 portal-text-xs portal-font-medium rounded-full 
                                                @if($contract->status === 'active') bg-green-100 text-green-800
                                                @elseif($contract->status === 'pending') bg-yellow-100 text-yellow-800
                                               @else bg-gray-100 text-gray-800 @endif">
                                                {{ ucfirst($contract->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-6 whitespace-nowrap portal-text-sm text-gray-900">
                                            ${{ number_format($contract->contract_value, 2) }}
                                        </td>
                                        <td class="px-6 py-6 whitespace-nowrap portal-text-sm portal-font-medium">
                                            <a href="{{ route('client.contracts.show', $contract) }}" class="text-blue-600 hover:text-blue-900">
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5" class="px-6 py-6 whitespace-nowrap portal-text-sm text-gray-500 text-center">
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
        <div class="portal-w-full px-6 portal-flex-1 px-6-lg-4">
            <div class="portal-bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden portal-shadow portal-mb-6">
                <div class="px-6 py-6 portal-border-b portal-bg-gray-50 py-6">
                    <h6 class="portal-mb-0 portal-font-bold portal-text-blue-600 dark:text-blue-400">
                        <i class="fas fa-tasks portal-mr-2"></i>Upcoming Milestones
                    </h6>
                </div>
                <div class="portal-bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                    @if(isset($upcomingMilestones) && count($upcomingMilestones) > 0)
                        <div class="space-y-3">
                            @foreach($upcomingMilestones as $milestone)
                            <div class="portal-flex portal-items-center p-6 bg-gray-50 rounded-lg">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full portal-flex portal-items-center justify-center">
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
                        <p class="text-gray-500 portal-text-sm text-center py-6">
                            No upcoming milestones.
                        </p>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="portal-bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden portal-shadow">
                <div class="px-6 py-6 portal-border-b portal-bg-gray-50 py-6">
                    <h6 class="portal-mb-0 portal-font-bold portal-text-blue-600 dark:text-blue-400">
                        <i class="fas fa-bolt portal-mr-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="portal-bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                    <div class="space-y-2">
                        <a href="{{ route('client.contracts') }}" class="portal-px-6 py-2 font-medium rounded-md transition-colors portal-w-100 portal-px-6 py-2 font-medium rounded-md transition-colors-outline-primary portal-text-sm">
                            <i class="fas fa-file-contract portal-mr-2"></i>View All Contracts
                        </a>
                        <a href="{{ route('client.profile') }}" class="portal-px-6 py-2 font-medium rounded-md transition-colors portal-w-100 portal-px-6 py-2 font-medium rounded-md transition-colors-outline-primary portal-text-sm">
                            <i class="fas fa-user-cog portal-mr-2"></i>Update Profile
                        </a>
                        <a href="mailto:support@{{ parse_url(config('app.url'), PHP_URL_HOST) }}" class="portal-px-6 py-2 font-medium rounded-md transition-colors portal-w-100 portal-px-6 py-2 font-medium rounded-md transition-colors-outline-primary portal-text-sm">
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
    .portal-lg:w-1/3 {
        flex: 0 0 33.333333%;
        max-width: 33.333333%;
    }
    
    .portal-lg:w-2/3 {
        flex: 0 0 66.666667%;
        max-width: 66.666667%;
    }
}
</style>
@endpush
