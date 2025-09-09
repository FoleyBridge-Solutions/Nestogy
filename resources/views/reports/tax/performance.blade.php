@extends('layouts.app')

@section('content')
<div class="py-6" x-data="taxPerformanceReport()" x-init="init()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="md:flex md:items-center md:justify-between mb-8">
            <div class="min-w-0 flex-1">
                <nav class="flex" aria-label="Breadcrumb">
                    <ol class="flex items-center space-x-4">
                        <li>
                            <a href="{{ route('reports.tax.index') }}" class="text-gray-400 hover:text-gray-500">
                                <i class="fas fa-chart-bar"></i>
                                <span class="sr-only">Tax Reports</span>
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                                <span class="text-sm font-medium text-gray-500">Performance</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h2 class="mt-2 text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Tax System Performance
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Performance metrics and optimization insights for {{ $dateRange['label'] }}
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
                <flux:button variant="ghost" color="zinc" @click="refreshMetrics()" :disabled="loading">
                    <i class="fas fa-sync" :class="{ 'fa-spin': loading }"></i>
                    <span class="ml-1" x-text="loading ? 'Loading...' : 'Refresh'"></span>
                </flux:button>
                <flux:button variant="ghost" @click="exportReport()">
                    <i class="fas fa-download mr-1"></i>
                    Export Performance Report
                </flux:button>
            </div>
        </div>

        <!-- Performance Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Average Response Time -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-stopwatch text-blue-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Avg Response Time</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    {{ number_format($performanceMetrics['avg_time'] ?? 0, 1) }}ms
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <span class="text-gray-500">Range: {{ number_format($performanceMetrics['min_time'] ?? 0, 1) }}-{{ number_format($performanceMetrics['max_time'] ?? 0, 1) }}ms</span>
                    </div>
                </div>
            </div>

            <!-- P95 Response Time -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-chart-line text-purple-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">P95 Response Time</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    {{ number_format($performanceMetrics['p95_time'] ?? 0, 1) }}ms
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        @php
                            $p95Time = $performanceMetrics['p95_time'] ?? 0;
                            $isGood = $p95Time < 1000;
                        @endphp
                        <span class="font-medium {{ $isGood ? 'text-green-600' : 'text-yellow-600' }}">
                            {{ $isGood ? 'Excellent' : 'Needs attention' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Cache Hit Rate -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-memory text-green-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Cache Hit Rate</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    {{ number_format($cacheMetrics['cache_hit_rate'] ?? 0, 1) }}%
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <span class="text-gray-500">{{ number_format($cacheMetrics['profiles_cached'] ?? 0) }} profiles cached</span>
                    </div>
                </div>
            </div>

            <!-- Error Rate -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Error Rate</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    @php
                                        $totalCalculations = $performanceMetrics['total_calculations'] ?? 0;
                                        $errorCount = collect($errorAnalysis)->sum('occurrence_count');
                                        $errorRate = $totalCalculations > 0 ? ($errorCount / $totalCalculations) * 100 : 0;
                                    @endphp
                                    {{ number_format($errorRate, 2) }}%
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        @if($errorRate < 1)
                            <span class="font-medium text-green-600">Excellent</span>
                        @elseif($errorRate < 5)
                            <span class="font-medium text-yellow-600">Good</span>
                        @else
                            <span class="font-medium text-red-600">Needs attention</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Engine Comparison -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Engine Performance Chart -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Engine Performance Comparison</h3>
                </div>
                <div class="p-6">
                    <div class="h-64" id="engine-performance-chart">
                        <canvas></canvas>
                    </div>
                </div>
            </div>

            <!-- Error Analysis -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Error Analysis</h3>
                </div>
                <div class="p-6">
                    @if(count($errorAnalysis) > 0)
                        <div class="space-y-4">
                            @foreach($errorAnalysis as $error)
                            <div class="flex items-start justify-between p-4 bg-red-50 rounded-lg border border-red-200">
                                <div class="flex-1">
                                    <div class="font-medium text-red-900">
                                        {{ $error['error_message'] ?? 'Unknown Error' }}
                                    </div>
                                    <div class="text-sm text-red-700 mt-1">
                                        First: {{ $error['first_occurrence'] ?? 'N/A' }} | 
                                        Last: {{ $error['last_occurrence'] ?? 'N/A' }}
                                    </div>
                                </div>
                                <div class="ml-4 text-right">
                                    <div class="text-lg font-bold text-red-900">
                                        {{ number_format($error['occurrence_count'] ?? 0) }}
                                    </div>
                                    <div class="text-xs text-red-700">occurrences</div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="text-green-600">
                                <i class="fas fa-check-circle fa-3x mb-4"></i>
                                <p class="text-lg font-medium">No errors detected!</p>
                                <p class="text-sm text-gray-500 mt-1">Your tax system is running smoothly</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Detailed Performance Metrics -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Detailed Performance Metrics</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Response Time Distribution -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Response Time Distribution</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Minimum:</span>
                                <span class="font-medium">{{ number_format($performanceMetrics['min_time'] ?? 0, 1) }}ms</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Average:</span>
                                <span class="font-medium">{{ number_format($performanceMetrics['avg_time'] ?? 0, 1) }}ms</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Median:</span>
                                <span class="font-medium">{{ number_format($performanceMetrics['median_time'] ?? 0, 1) }}ms</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">95th Percentile:</span>
                                <span class="font-medium">{{ number_format($performanceMetrics['p95_time'] ?? 0, 1) }}ms</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Maximum:</span>
                                <span class="font-medium">{{ number_format($performanceMetrics['max_time'] ?? 0, 1) }}ms</span>
                            </div>
                        </div>
                    </div>

                    <!-- Cache Performance -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Cache Performance</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Hit Rate:</span>
                                <span class="font-medium">{{ number_format($cacheMetrics['cache_hit_rate'] ?? 0, 1) }}%</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Profiles Cached:</span>
                                <span class="font-medium">{{ number_format($cacheMetrics['profiles_cached'] ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Rates Cached:</span>
                                <span class="font-medium">{{ number_format($cacheMetrics['rates_cached'] ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Avg Lookup Time:</span>
                                <span class="font-medium">{{ number_format($cacheMetrics['avg_cache_lookup_time'] ?? 0, 1) }}ms</span>
                            </div>
                        </div>
                    </div>

                    <!-- System Health -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">System Health</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Total Calculations:</span>
                                <span class="font-medium">{{ number_format($performanceMetrics['total_calculations'] ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Standard Deviation:</span>
                                <span class="font-medium">{{ number_format($performanceMetrics['stddev_time'] ?? 0, 1) }}ms</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Error Count:</span>
                                <span class="font-medium">{{ number_format(collect($errorAnalysis)->sum('occurrence_count')) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Unique Errors:</span>
                                <span class="font-medium">{{ count($errorAnalysis) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Engine Comparison Table -->
        @if(count($engineComparison) > 0)
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Engine Performance Comparison</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Engine</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usage Count</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Min Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Max Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Success Rate</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($engineComparison as $engine)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ ucfirst(str_replace('_', ' ', $engine['engine_used'])) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($engine['usage_count']) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($engine['avg_time'], 1) }}ms
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($engine['min_time'], 1) }}ms
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($engine['max_time'], 1) }}ms
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $engine['success_rate'] >= 95 ? 'bg-green-100 text-green-800' : ($engine['success_rate'] >= 90 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ number_format($engine['success_rate'], 1) }}%
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
function taxPerformanceReport() {
    return {
        loading: false,
        
        init() {
            this.renderEnginePerformanceChart();
        },
        
        async refreshMetrics() {
            this.loading = true;
            
            try {
                // Reload the page to refresh all metrics
                window.location.reload();
            } catch (error) {
                console.error('Error refreshing metrics:', error);
            } finally {
                this.loading = false;
            }
        },
        
        renderEnginePerformanceChart() {
            const ctx = document.querySelector('#engine-performance-chart canvas');
            if (!ctx || typeof Chart === 'undefined') return;
            
            const engineData = @json($engineComparison);
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: engineData.map(engine => engine.engine_used.replace('_', ' ')),
                    datasets: [{
                        label: 'Average Response Time (ms)',
                        data: engineData.map(engine => engine.avg_time),
                        backgroundColor: 'rgba(59, 130, 246, 0.8)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1
                    }, {
                        label: 'Usage Count',
                        data: engineData.map(engine => engine.usage_count),
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderColor: 'rgb(16, 185, 129)',
                        borderWidth: 1,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Response Time (ms)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Usage Count'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });
        },
        
        exportReport() {
            const params = new URLSearchParams({
                type: 'performance',
                format: 'csv'
            });
            
            window.open(`{{ route('reports.tax.export') }}?${params.toString()}`, '_blank');
        }
    };
}
</script>
@endsection
