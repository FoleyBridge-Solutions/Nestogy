@extends('layouts.app')

@section('content')
<div class="py-6" x-data="taxSummaryReport()" x-init="init()">
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
                                <span class="text-sm font-medium text-gray-500">Tax Summary</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h2 class="mt-2 text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Tax Summary Report
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Detailed breakdown of tax calculations for {{ $dateRange['label'] }}
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
                <div class="relative">
                    <select x-model="groupBy" @change="updateGrouping()" class="btn btn-outline-secondary">
                        <option value="day">Daily</option>
                        <option value="week">Weekly</option>
                        <option value="month" selected>Monthly</option>
                    </select>
                </div>
                <button @click="exportReport()" class="btn btn-outline-primary">
                    <i class="fas fa-download mr-1"></i>
                    Export Summary
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Tax Collected -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-money-bill-wave text-green-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Tax Calculated</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    ${{ number_format(collect($taxData)->sum('total_tax'), 2) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <span class="text-gray-500">From</span>
                        <span class="font-medium text-gray-900">{{ number_format(collect($taxData)->sum('calculation_count')) }} calculations</span>
                    </div>
                </div>
            </div>

            <!-- Average Tax Rate -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-percentage text-blue-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Average Tax Rate</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    @php
                                        $totalBase = collect($taxData)->sum('total_base');
                                        $totalTax = collect($taxData)->sum('total_tax');
                                        $avgRate = $totalBase > 0 ? ($totalTax / $totalBase) * 100 : 0;
                                    @endphp
                                    {{ number_format($avgRate, 2) }}%
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <span class="text-gray-500">On base amount of</span>
                        <span class="font-medium text-gray-900">${{ number_format($totalBase, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Peak Calculation Period -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-chart-line text-purple-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Peak Period</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    @php
                                        $peakPeriod = collect($taxData)->sortByDesc('calculation_count')->first();
                                    @endphp
                                    {{ $peakPeriod['period'] ?? 'N/A' }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <span class="font-medium text-gray-900">{{ number_format($peakPeriod['calculation_count'] ?? 0) }}</span>
                        <span class="text-gray-500">calculations</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tax Trend Chart -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Tax Calculation Trend</h3>
                <p class="text-sm text-gray-500">Tax amounts calculated over time (grouped by {{ $groupBy }})</p>
            </div>
            <div class="p-6">
                <div class="h-80" id="tax-trend-chart">
                    <canvas></canvas>
                </div>
            </div>
        </div>

        <!-- Tax Type Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Tax by Engine Type</h3>
                </div>
                <div class="p-6">
                    @if(count($taxTypeBreakdown) > 0)
                        <div class="space-y-4">
                            @foreach($taxTypeBreakdown as $type)
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ ucfirst(str_replace('_', ' ', $type['tax_type'])) }}
                                        </div>
                                        <div class="ml-2 text-xs text-gray-500">
                                            ({{ number_format($type['calculation_count']) }} calculations)
                                        </div>
                                    </div>
                                    <div class="mt-1 w-full bg-gray-200 rounded-full h-2">
                                        @php
                                            $totalTypeAmount = collect($taxTypeBreakdown)->sum('total_amount');
                                            $percentage = $totalTypeAmount > 0 ? ($type['total_amount'] / $totalTypeAmount) * 100 : 0;
                                        @endphp
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                                <div class="ml-4 text-right">
                                    <div class="text-sm font-semibold text-gray-900">
                                        ${{ number_format($type['total_amount'], 2) }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ number_format($percentage, 1) }}%
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-gray-500 py-8">
                            <i class="fas fa-chart-pie fa-2x mb-4"></i>
                            <p>No tax type data available for this period</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Invoice vs Quote Comparison -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Invoice vs Quote Taxes</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-6">
                        <!-- Invoices -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Invoice Taxes</span>
                                <span class="text-sm font-semibold text-gray-900">
                                    ${{ number_format($invoiceQuoteComparison['invoices']['total_tax'], 2) }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                @php
                                    $totalTax = $invoiceQuoteComparison['invoices']['total_tax'] + $invoiceQuoteComparison['quotes']['total_tax'];
                                    $invoicePercentage = $totalTax > 0 ? ($invoiceQuoteComparison['invoices']['total_tax'] / $totalTax) * 100 : 0;
                                @endphp
                                <div class="bg-green-600 h-3 rounded-full" style="width: {{ $invoicePercentage }}%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500 mt-1">
                                <span>{{ number_format($invoiceQuoteComparison['invoices']['count']) }} calculations</span>
                                <span>{{ number_format($invoicePercentage, 1) }}%</span>
                            </div>
                        </div>

                        <!-- Quotes -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Quote Taxes</span>
                                <span class="text-sm font-semibold text-gray-900">
                                    ${{ number_format($invoiceQuoteComparison['quotes']['total_tax'], 2) }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                @php
                                    $quotePercentage = 100 - $invoicePercentage;
                                @endphp
                                <div class="bg-blue-600 h-3 rounded-full" style="width: {{ $quotePercentage }}%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500 mt-1">
                                <span>{{ number_format($invoiceQuoteComparison['quotes']['count']) }} calculations</span>
                                <span>{{ number_format($quotePercentage, 1) }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Data Table -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Detailed Tax Data</h3>
                <p class="text-sm text-gray-500">Tax calculations grouped by {{ $groupBy }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Calculations</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Base Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Tax</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Tax</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tax Rate</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($taxData as $period)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $period['period'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($period['calculation_count']) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${{ number_format($period['total_base'], 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${{ number_format($period['total_tax'], 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${{ number_format($period['avg_tax'], 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @php
                                    $taxRate = $period['total_base'] > 0 ? ($period['total_tax'] / $period['total_base']) * 100 : 0;
                                @endphp
                                {{ number_format($taxRate, 2) }}%
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                No tax data available for this period
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function taxSummaryReport() {
    return {
        groupBy: '{{ $groupBy }}',
        
        init() {
            this.renderTaxTrendChart();
        },
        
        updateGrouping() {
            const params = new URLSearchParams(window.location.search);
            params.set('group_by', this.groupBy);
            window.location.href = `{{ route('reports.tax.summary') }}?${params.toString()}`;
        },
        
        renderTaxTrendChart() {
            const ctx = document.querySelector('#tax-trend-chart canvas');
            if (!ctx || typeof Chart === 'undefined') return;
            
            const data = @json($taxData);
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(item => item.period),
                    datasets: [{
                        label: 'Tax Amount ($)',
                        data: data.map(item => item.total_tax),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Calculation Count',
                        data: data.map(item => item.calculation_count),
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
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
                            title: {
                                display: true,
                                text: 'Tax Amount ($)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Calculation Count'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Tax Calculations Over Time'
                        }
                    }
                }
            });
        },
        
        exportReport() {
            const params = new URLSearchParams({
                type: 'summary',
                format: 'csv',
                group_by: this.groupBy
            });
            
            window.open(`{{ route('reports.tax.export') }}?${params.toString()}`, '_blank');
        }
    };
}
</script>
@endsection