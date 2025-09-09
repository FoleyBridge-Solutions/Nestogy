@extends('layouts.app')

@section('content')
<div class="py-6" x-data="taxReportDashboard()" x-init="init()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="md:flex md:items-center md:justify-between mb-8">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    <i class="fas fa-chart-bar text-blue-600 mr-3"></i>
                    Tax Reporting Dashboard
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Comprehensive tax calculation reports and analytics for {{ $dateRange['label'] }}
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
                <div class="relative">
                    <select x-model="selectedPeriod" @change="updateDateRange()" class="btn 
                        <option value="7_days">Last 7 days</option>
                        <option value="30_days" selected>Last 30 days</option>
                        <option value="90_days">Last 90 days</option>
                        <option value="current_month">Current month</option>
                        <option value="last_month">Last month</option>
                        <option value="custom">Custom range</option>
                    </select>
                </div>
                <flux:button variant="ghost" @click="exportReport('summary')">
                    <i class="fas fa-download mr-1"></i>
                    Export
                </flux:button>
                <button @click="refreshData()" :disabled="loading" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <i class="fas fa-sync" :class="{ 'fa-spin': loading }"></i>
                    <span class="ml-1" x-text="loading ? 'Loading...' : 'Refresh'"></span>
                </button>
            </div>
        </div>

        <!-- Summary Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Calculations -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-calculator text-blue-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Calculations</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($stats['total_calculations']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <span class="text-green-600 font-medium">{{ number_format($stats['success_rate'], 1) }}%</span>
                        <span class="text-gray-500">success rate</span>
                    </div>
                </div>
            </div>

            <!-- Total Tax Calculated -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-dollar-sign text-green-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Tax Calculated</dt>
                                <dd class="text-lg font-medium text-gray-900">${{ number_format($stats['total_tax_calculated'], 2) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <a href="{{ route('reports.tax.summary') }}" class="font-medium text-green-600 hover:text-green-500">
                            View breakdown
                        </a>
                    </div>
                </div>
            </div>

            <!-- Average Calculation Time -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-stopwatch text-purple-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Avg. Calculation Time</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($stats['avg_calculation_time'], 1) }}ms</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <a href="{{ route('reports.tax.performance') }}" class="font-medium text-purple-600 hover:text-purple-500">
                            View performance
                        </a>
                    </div>
                </div>
            </div>

            <!-- Failed Calculations -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Failed Calculations</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($stats['failed_calculations']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        @if($stats['failed_calculations'] > 0)
                            <span class="font-medium text-red-600">Needs attention</span>
                        @else
                            <span class="font-medium text-green-600">All good</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Data Visualization -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Tax Calculation Trend -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Tax Calculation Trend</h3>
                </div>
                <div class="p-6">
                    <div class="h-64" id="tax-trend-chart">
                        <div class="flex items-center justify-center h-full text-gray-500">
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            Loading chart data...
                        </div>
                    </div>
                </div>
            </div>

            <!-- Engine Performance -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Engine Performance</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @foreach($engineMetrics as $engine)
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $engine['engine_used'] ?? 'General')) }}</div>
                                <div class="text-sm text-gray-500">{{ number_format($engine['usage_count']) }} calculations</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-semibold text-gray-900">{{ number_format($engine['avg_time'], 1) }}ms avg</div>
                                <div class="text-xs text-gray-500">{{ number_format($engine['success_rate'], 1) }}% success</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Jurisdiction Breakdown -->
        @if(count($jurisdictionBreakdown) > 0)
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Top Tax Jurisdictions</h3>
                    <a href="{{ route('reports.tax.jurisdictions') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                        View all jurisdictions
                    </a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jurisdiction</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">State</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Calculations</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Tax</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($jurisdictionBreakdown as $jurisdiction)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $jurisdiction['name'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $jurisdiction['state'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($jurisdiction['calculation_count']) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${{ number_format($jurisdiction['total_tax_amount'], 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Recent Calculations -->
        @if($recentCalculations->count() > 0)
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Recent Tax Calculations</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Engine</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($recentCalculations as $calc)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                                {{ substr($calc->calculation_id, -8) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ ucfirst($calc->calculation_type) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $calc->engine_used ?? 'General' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${{ number_format($calc->total_amount ?? 0, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($calc->calculation_time_ms ?? 0, 1) }}ms
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                   {{ $calc->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ ucfirst($calc->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $calc->created_at->format('M j, H:i') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Quick Actions -->
        <div class="mt-8">
            <div class="bg-gray-50 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <flux:button variant="ghost" href="{{ route('reports.tax.summary') }}" class="w-full" >
                        <i class="fas fa-chart-pie mr-2"></i>
                        Tax Summary
                    </flux:button>
                    <flux:button variant="ghost" href="{{ route('reports.tax.jurisdictions') }}" class="w-full" >
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        Jurisdictions
                    </flux:button>
                    <flux:button variant="ghost" href="{{ route('reports.tax.compliance') }}" class="w-full" >
                        <i class="fas fa-shield-alt mr-2"></i>
                        Compliance
                    </flux:button>
                    <flux:button variant="ghost" href="{{ route('reports.tax.performance') }}" class="w-full" >
                        <i class="fas fa-tachometer-alt mr-2"></i>
                        Performance
                    </flux:button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function taxReportDashboard() {
    return {
        selectedPeriod: '30_days',
        loading: false,
        chartData: null,
        
        init() {
            this.loadChartData();
        },
        
        async updateDateRange() {
            this.loading = true;
            
            try {
                const params = new URLSearchParams({
                    period: this.selectedPeriod
                });
                
                window.location.href = `{{ route('reports.tax.index') }}?${params.toString()}`;
            } catch (error) {
                console.error('Error updating date range:', error);
            } finally {
                this.loading = false;
            }
        },
        
        async refreshData() {
            this.loading = true;
            
            try {
                await this.loadChartData();
                // Reload the page to refresh all data
                window.location.reload();
            } catch (error) {
                console.error('Error refreshing data:', error);
            } finally {
                this.loading = false;
            }
        },
        
        async loadChartData() {
            try {
                const response = await fetch(`{{ route('reports.tax.api-data') }}?type=tax_trend&period=${this.selectedPeriod}`);
                const data = await response.json();
                
                this.renderTaxTrendChart(data);
            } catch (error) {
                console.error('Error loading chart data:', error);
            }
        },
        
        renderTaxTrendChart(data) {
            // Using Chart.js or similar library
            const ctx = document.getElementById('tax-trend-chart');
            if (ctx && typeof Chart !== 'undefined') {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.map(item => item.period),
                        datasets: [{
                            label: 'Tax Calculations',
                            data: data.map(item => item.calculation_count),
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        },
        
        async exportReport(type) {
            try {
                const params = new URLSearchParams({
                    type: type,
                    format: 'csv',
                    period: this.selectedPeriod
                });
                
                window.open(`{{ route('reports.tax.export') }}?${params.toString()}`, '_blank');
            } catch (error) {
                console.error('Error exporting report:', error);
            }
        }
    };
}
</script>
@endsection
