<!-- FINANCIAL REVENUE COMMAND CENTER -->
<div class="financial-dashboard">
    
    <!-- HEADER -->
    <div class="bg-gradient-to-r from-green-600 to-emerald-600 rounded-xl p-6 mb-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold">Revenue Command Center</h2>
                <p class="text-green-100 mt-1">
                    Month-to-Date Revenue: ${{ number_format($data['metrics']['total_revenue'] ?? 0, 2) }}
                </p>
            </div>
            <div class="text-right">
                <p class="text-sm text-green-100">Outstanding</p>
                <p class="text-2xl font-bold">${{ number_format($data['metrics']['total_outstanding'] ?? 0, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- KEY METRICS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 uppercase">Monthly Revenue</p>
                    <p class="text-2xl font-bold text-slate-800 dark:text-white">
                        ${{ number_format($data['metrics']['total_revenue'] ?? 0, 2) }}
                    </p>
                </div>
                <div class="bg-green-100 p-2 rounded-lg">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 uppercase">Outstanding</p>
                    <p class="text-2xl font-bold text-slate-800 dark:text-white">
                        ${{ number_format($data['metrics']['total_outstanding'] ?? 0, 2) }}
                    </p>
                </div>
                <div class="bg-yellow-100 p-2 rounded-lg">
                    <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 uppercase">Overdue</p>
                    <p class="text-2xl font-bold text-red-600">
                        ${{ number_format($data['metrics']['overdue_amount'] ?? 0, 2) }}
                    </p>
                </div>
                <div class="bg-red-100 p-2 rounded-lg">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 uppercase">MRR</p>
                    <p class="text-2xl font-bold text-slate-800 dark:text-white">
                        ${{ number_format($data['metrics']['monthly_recurring'] ?? 0, 2) }}
                    </p>
                </div>
                <div class="bg-blue-100 p-2 rounded-lg">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        
        <!-- REVENUE TREND CHART -->
        <div class="lg:col-span-12-span-2">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">30-Day Revenue Trend</h3>
                <div style="position: relative; height: 300px;">
                    <canvas id="revenueTrendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- TOP CLIENTS -->
        <div class="lg:col-span-12-span-1">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Top Clients (This Month)</h3>
                <div class="space-y-3">
                    @forelse($data['top_clients'] ?? [] as $client)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-700 dark:text-slate-300">{{ Str::limit($client->name, 20) }}</span>
                        <span class="text-sm font-bold text-green-600">
                            ${{ number_format($client->payments_sum_amount ?? 0, 2) }}
                        </span>
                    </div>
                    @empty
                    <p class="text-sm text-slate-500">No client data available</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- SECOND ROW -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        
        <!-- OVERDUE INVOICES -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Overdue Invoices</h3>
                <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-medium rounded-full">
                    {{ $data['counts']['overdue_invoices'] ?? 0 }}
                </span>
            </div>
            <div class="space-y-2 max-h-64 overflow-y-auto">
                @forelse($data['overdue_invoices'] ?? [] as $invoice)
                <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-800 dark:text-white">
                                #{{ $invoice->number ?? $invoice->id }}
                            </p>
                            <p class="text-xs text-slate-600 dark:text-slate-400">
                                {{ $invoice->client->name ?? 'Unknown' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-red-600">${{ number_format($invoice->amount, 2) }}</p>
                            <p class="text-xs text-slate-500">{{ $invoice->due_date->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-sm text-slate-500">No overdue invoices</p>
                @endforelse
            </div>
        </div>

        <!-- RECENT PAYMENTS -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Recent Payments</h3>
                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                    {{ $data['counts']['recent_payments'] ?? 0 }}
                </span>
            </div>
            <div class="space-y-2 max-h-64 overflow-y-auto">
                @forelse($data['recent_payments'] ?? [] as $payment)
                <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-800 dark:text-white">
                                {{ $payment->client->name ?? 'Unknown' }}
                            </p>
                            <p class="text-xs text-slate-600 dark:text-slate-400">
                                {{ $payment->created_at->format('M d, Y') }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-green-600">${{ number_format($payment->amount, 2) }}</p>
                            <p class="text-xs text-slate-500">{{ $payment->payment_method ?? 'Unknown' }}</p>
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-sm text-slate-500">No recent payments</p>
                @endforelse
            </div>
        </div>

        <!-- PAYMENT METHODS -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Payment Methods</h3>
            <div class="space-y-3">
                @forelse($data['payment_methods'] ?? [] as $method)
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                        <span class="text-sm text-slate-700 dark:text-slate-300">{{ $method->payment_method ?? 'Unknown' }}</span>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-slate-800 dark:text-white">${{ number_format($method->total, 2) }}</p>
                        <p class="text-xs text-slate-500">{{ $method->count }} payments</p>
                    </div>
                </div>
                @empty
                <p class="text-sm text-slate-500">No payment data</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- UPCOMING INVOICES -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Upcoming Invoices (Next 7 Days)</h3>
            <span class="text-sm text-slate-500">{{ $data['counts']['upcoming_invoices'] ?? 0 }} invoices</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700">
                        <th class="text-left py-2 text-xs font-medium text-slate-500 uppercase">Invoice</th>
                        <th class="text-left py-2 text-xs font-medium text-slate-500 uppercase">Client</th>
                        <th class="text-left py-2 text-xs font-medium text-slate-500 uppercase">Due Date</th>
                        <th class="text-right py-2 text-xs font-medium text-slate-500 uppercase">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data['upcoming_invoices'] ?? [] as $invoice)
                    <tr class="border-b border-slate-100 dark:border-slate-700">
                        <td class="py-2 text-sm text-slate-800 dark:text-white">#{{ $invoice->number ?? $invoice->id }}</td>
                        <td class="py-2 text-sm text-slate-600 dark:text-slate-400">{{ $invoice->client->name ?? 'Unknown' }}</td>
                        <td class="py-2 text-sm text-slate-600 dark:text-slate-400">{{ $invoice->due_date->format('M d, Y') }}</td>
                        <td class="py-2 text-sm font-medium text-right text-slate-800 dark:text-white">${{ number_format($invoice->amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-4 text-center text-sm text-slate-500">No upcoming invoices</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Trend Chart
    const ctx = document.getElementById('revenueTrendChart');
    if (ctx) {
        const trendData = @json($data['revenue_trend'] ?? []);
        const labels = trendData.map(d => d.date);
        const data = trendData.map(d => d.total);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Daily Revenue',
                    data: data,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '$' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
