@extends('layouts.app')

@section('title', $reportInfo['name'])

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Report Header -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $reportInfo['name'] }}</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Generated on {{ now()->format('F j, Y \a\t g:i A') }}
                        @if(isset($params['start_date']) && isset($params['end_date']))
                            | Period: {{ \Carbon\Carbon::parse($params['start_date'])->format('M j, Y') }} - {{ \Carbon\Carbon::parse($params['end_date'])->format('M j, Y') }}
                        @endif
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <button onclick="window.print()" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Print
                    </button>
                    <div class="relative">
                        <button onclick="toggleExportMenu()" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Export
                            <svg class="ml-2 -mr-0.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="exportMenu" class="hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5">
                            <div class="py-1">
                                <a href="{{ route('reports.generate', array_merge(['reportId' => $reportId, 'format' => 'pdf'], $params)) }}" 
                                   class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:bg-gray-800">
                                    Export as PDF
                                </a>
                                <a href="{{ route('reports.generate', array_merge(['reportId' => $reportId, 'format' => 'excel'], $params)) }}" 
                                   class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:bg-gray-800">
                                    Export as Excel
                                </a>
                                <a href="{{ route('reports.generate', array_merge(['reportId' => $reportId, 'format' => 'csv'], $params)) }}" 
                                   class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:bg-gray-800">
                                    Export as CSV
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Content -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <div class="p-6">
            <!-- Summary Metrics -->
            @if(isset($data['metrics']) && !empty($data['metrics']))
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Summary Metrics</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach($data['metrics'] as $key => $value)
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                        <dt class="text-sm font-medium text-gray-500 truncate">
                            {{ ucwords(str_replace('_', ' ', $key)) }}
                        </dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">
                            @if(is_numeric($value))
                                @if(strpos($key, 'revenue') !== false || strpos($key, 'amount') !== false || strpos($key, 'value') !== false)
                                    ${{ number_format($value, 2) }}
                                @elseif(strpos($key, 'rate') !== false || strpos($key, 'percent') !== false)
                                    {{ number_format($value, 1) }}%
                                @else
                                    {{ number_format($value) }}
                                @endif
                            @else
                                {{ $value }}
                            @endif
                        </dd>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Charts Section -->
            @if(isset($data['monthly_trend']) && !empty($data['monthly_trend']))
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Monthly Trend</h2>
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
            </div>
            @endif

            <!-- Top Items Lists -->
            @if(isset($data['top_clients']) && !empty($data['top_clients']))
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top Clients</h2>
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoices</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200">
                            @foreach($data['top_clients'] as $client)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $client->client_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $client->invoice_count }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    ${{ number_format($client->revenue, 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Detailed Data Table -->
            @if(isset($data['details']) && !empty($data['details']))
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Detailed Data</h2>
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    @if(count($data['details']) > 0)
                                        @foreach(array_keys((array)$data['details']->first()) as $header)
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ ucwords(str_replace('_', ' ', $header)) }}
                                        </th>
                                        @endforeach
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200">
                                @foreach($data['details'] as $row)
                                <tr>
                                    @foreach((array)$row as $key => $value)
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        @if(is_numeric($value) && (strpos($key, 'amount') !== false || strpos($key, 'total') !== false || strpos($key, 'revenue') !== false))
                                            ${{ number_format($value, 2) }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    </td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Summary Section -->
            @if(isset($data['summary']) && !empty($data['summary']))
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Summary</h2>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">Report Summary</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <ul class="list-disc list-inside space-y-1">
                                    @foreach($data['summary'] as $key => $value)
                                    <li>
                                        <strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong>
                                        @if(is_numeric($value))
                                            {{ number_format($value) }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Toggle export menu
function toggleExportMenu() {
    const menu = document.getElementById('exportMenu');
    menu.classList.toggle('hidden');
}

// Close export menu when clicking outside
document.addEventListener('click', function(event) {
    const menu = document.getElementById('exportMenu');
    const button = event.target.closest('button');
    
    if (!button || !button.onclick || button.onclick.toString().indexOf('toggleExportMenu') === -1) {
        if (!menu.contains(event.target)) {
            menu.classList.add('hidden');
        }
    }
});

// Create monthly trend chart if data exists
@if(isset($data['monthly_trend']) && !empty($data['monthly_trend']))
const ctx = document.getElementById('monthlyTrendChart').getContext('2d');
const monthlyData = @json($data['monthly_trend']);

new Chart(ctx, {
    type: 'line',
    data: {
        labels: monthlyData.map(item => item.month),
        datasets: [{
            label: 'Revenue',
            data: monthlyData.map(item => item.revenue),
            borderColor: 'rgb(79, 70, 229)',
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return '$' + context.parsed.y.toLocaleString();
                    }
                }
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
@endif

// Print styles
@media print {
    .no-print {
        display: none !important;
    }
}
</script>
@endpush
@endsection