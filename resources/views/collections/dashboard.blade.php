@extends('layouts.app')

@section('title', 'Collection Dashboard')

@section('content')
<div class="collection-dashboard">
    <!-- Header with date range selector -->
    <div class="dashboard-header bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Collection Dashboard</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Real-time collection performance and analytics</p>
            </div>
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Date Range:</label>
                    <input type="date" id="start_date" value="{{ $dateRange['start'] }}" 
                           class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm">
                    <span class="text-gray-500">to</span>
                    <input type="date" id="end_date" value="{{ $dateRange['end'] }}" 
                           class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm">
                    <button onclick="updateDashboard()" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        Update
                    </button>
                </div>
                <button onclick="exportData()" 
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                    Export Data
                </button>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Total Outstanding -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Outstanding</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        ${{ number_format($dashboard['summary']['total_outstanding'], 2) }}
                    </p>
                </div>
                <div class="bg-red-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Amount Collected -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Amount Collected</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        ${{ number_format($dashboard['summary']['amount_collected'], 2) }}
                    </p>
                    <p class="text-sm text-green-600 font-medium">
                        {{ number_format($dashboard['summary']['period_over_period_change'], 1) }}% vs previous period
                    </p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Collection Rate -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Collection Rate</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($dashboard['summary']['collection_rate'], 1) }}%
                    </p>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div class="bg-blue-600 h-2 rounded-full" 
                             style="width: {{ min($dashboard['summary']['collection_rate'], 100) }}%"></div>
                    </div>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Clients -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Clients</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($dashboard['summary']['active_clients']) }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        of {{ number_format($dashboard['summary']['total_clients']) }} total
                    </p>
                </div>
                <div class="bg-yellow-100 p-3 rounded-full">
                    <svg class="w-6 h-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Key Performance Indicators -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Key Performance Indicators</h3>
            <div class="space-y-4">
                @foreach($dashboard['kpi_metrics'] as $kpi => $data)
                <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-900">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ ucfirst(str_replace('_', ' ', $kpi)) }}
                        </p>
                        <div class="flex items-center mt-1">
                            <span class="text-lg font-bold text-gray-900 dark:text-white">
                                @if(in_array($kpi, ['collection_rate', 'payment_plan_success_rate', 'first_call_resolution']))
                                    {{ number_format($data['value'], 1) }}%
                                @elseif($kpi === 'days_sales_outstanding')
                                    {{ number_format($data['value'], 0) }} days
                                @else
                                    ${{ number_format($data['value'], 2) }}
                                @endif
                            </span>
                            <span class="text-sm text-gray-500 ml-2">
                                / Target: 
                                @if(in_array($kpi, ['collection_rate', 'payment_plan_success_rate', 'first_call_resolution']))
                                    {{ number_format($data['target'], 1) }}%
                                @elseif($kpi === 'days_sales_outstanding')
                                    {{ number_format($data['target'], 0) }} days
                                @else
                                    ${{ number_format($data['target'], 2) }}
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @if($data['status'] === 'excellent') bg-green-100 text-green-800
                            @elseif($data['status'] === 'good') bg-blue-100 text-blue-800
                            @elseif($data['status'] === 'warning') bg-yellow-100 text-yellow-800
                            @else bg-red-100 text-red-800 @endif">
                            {{ ucfirst($data['status']) }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Collection Trends Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Collection Trends</h3>
            <div class="h-64 flex items-center justify-center bg-gray-50 dark:bg-gray-900 rounded-lg">
                <canvas id="trendsChart" class="w-full h-full"></canvas>
            </div>
        </div>
    </div>

    <!-- Channel Effectiveness & Aging Analysis -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Channel Effectiveness -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Channel Effectiveness</h3>
            <div class="space-y-3">
                @foreach($dashboard['channel_effectiveness'] as $channel => $data)
                <div class="flex items-center justify-between p-3 border rounded-lg">
                    <div class="flex-1">
                        <p class="font-medium text-gray-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $channel)) }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $data['total_actions'] }} actions • {{ number_format($data['conversion_rate'], 1) }}% conversion
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ number_format($data['success_rate'], 1) }}%
                        </p>
                        <p class="text-xs text-gray-500">Success Rate</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Aging Analysis -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Aging Analysis</h3>
            <div class="space-y-3">
                @foreach($dashboard['aging_analysis']['buckets'] as $bucket => $data)
                <div class="flex items-center justify-between p-3 border rounded-lg">
                    <div class="flex-1">
                        <p class="font-medium text-gray-900 dark:text-white">
                            @if($bucket === 'current') Current
                            @elseif($bucket === 'over_120') 120+ Days
                            @else {{ str_replace('_', '-', $bucket) }} Days
                            @endif
                        </p>
                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                            <div class="bg-gradient-to-r from-green-500 to-red-500 h-2 rounded-full" 
                                 style="width: {{ $data['percentage'] }}%"></div>
                        </div>
                    </div>
                    <div class="text-right ml-4">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            ${{ number_format($data['amount'], 0) }}
                        </p>
                        <p class="text-xs text-gray-500">{{ number_format($data['percentage'], 1) }}%</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Alerts & Recommendations -->
    @if(!empty($dashboard['alerts']) || !empty($dashboard['recommendations']))
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Performance Alerts -->
        @if(!empty($dashboard['alerts']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Performance Alerts</h3>
            <div class="space-y-3">
                @foreach($dashboard['alerts'] as $alert)
                <div class="flex items-start p-3 rounded-lg border-l-4 
                    @if($alert['severity'] === 'high') border-red-500 bg-red-50
                    @elseif($alert['severity'] === 'medium') border-yellow-500 bg-yellow-50
                    @else border-blue-500 bg-blue-50 @endif">
                    <div class="flex-shrink-0">
                        @if($alert['severity'] === 'high')
                            <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        @else
                            <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        @endif
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $alert['message'] }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ ucfirst($alert['severity']) }} Priority</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Optimization Recommendations -->
        @if(!empty($dashboard['recommendations']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Optimization Recommendations</h3>
            <div class="space-y-3">
                @foreach($dashboard['recommendations'] as $recommendation)
                <div class="p-3 border rounded-lg border-l-4 border-blue-500">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $recommendation['recommendation'] }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                {{ ucfirst($recommendation['priority']) }} Priority • 
                                {{ ucfirst(str_replace('_', ' ', $recommendation['type'])) }}
                            </p>
                            @if(isset($recommendation['potential_improvement']))
                            <p class="text-xs text-green-600 mt-1">
                                Potential Improvement: {{ $recommendation['potential_improvement'] }}
                            </p>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js" 
        integrity="sha512-CQBWl4fJHWbryGE+Pc7UAxWMUMNMWzWxF4SQo9CgkJIN1kx6djDQZjh3Y8SZ1d+6I+1zze6Z7kHXO7q3UyZAWw==" 
        crossorigin="anonymous"></script>
<script>
// Dashboard JavaScript functionality
function updateDashboard() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    
    if (startDate && endDate) {
        window.location.href = `{{ route('collections.dashboard') }}?start_date=${startDate}&end_date=${endDate}`;
    }
}

function exportData() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const format = 'csv'; // Could be made selectable
    
    window.open(`{{ route('collections.export') }}?start_date=${startDate}&end_date=${endDate}&format=${format}`);
}

// Initialize trends chart
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('trendsChart').getContext('2d');
    const trendsData = @json($dashboard['collection_trends']['daily_data']);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: trendsData.map(d => d.date),
            datasets: [{
                label: 'Collections',
                data: trendsData.map(d => d.collections),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.1
            }, {
                label: 'Actions',
                data: trendsData.map(d => d.actions),
                borderColor: 'rgb(16, 185, 129)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.1,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
});

// Auto-refresh dashboard every 5 minutes
setInterval(() => {
    fetch('{{ route("collections.dashboard.data") }}?' + new URLSearchParams({
        start_date: document.getElementById('start_date').value,
        end_date: document.getElementById('end_date').value
    }))
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update summary cards with new data
            updateSummaryCards(data.data.summary);
        }
    })
    .catch(error => console.error('Auto-refresh failed:', error));
}, 300000); // 5 minutes

function updateSummaryCards(summaryData) {
    // Update the summary cards with fresh data
    // This could be implemented to update specific elements without full page reload
}
</script>
@endpush
@endsection
