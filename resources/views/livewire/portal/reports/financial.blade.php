{{-- Financial Reports --}}
<div class="space-y-6">
    {{-- Metrics Cards --}}
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <flux:card class="border-emerald-200 dark:border-emerald-800">
            <div class="space-y-1">
                <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Total Invoiced</flux:text>
                <flux:heading size="xl">${{ number_format($this->invoiceStats['total_invoiced'] ?? 0, 2) }}</flux:heading>
                <flux:text size="xs" class="text-zinc-400">This period</flux:text>
            </div>
        </flux:card>

        <flux:card class="border-amber-200 dark:border-amber-800">
            <div class="space-y-1">
                <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Outstanding Balance</flux:text>
                <flux:heading size="xl">${{ number_format($this->invoiceStats['outstanding_balance'] ?? 0, 2) }}</flux:heading>
                <flux:text size="xs" class="text-zinc-400">Unpaid</flux:text>
            </div>
        </flux:card>

        <flux:card class="border-blue-200 dark:border-blue-800">
            <div class="space-y-1">
                <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Payments Made</flux:text>
                <flux:heading size="xl">${{ number_format($this->invoiceStats['payments_made'] ?? 0, 2) }}</flux:heading>
                <flux:text size="xs" class="text-zinc-400">Received</flux:text>
            </div>
        </flux:card>

        <flux:card class="border-red-200 dark:border-red-800">
            <div class="space-y-1">
                <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Overdue Amount</flux:text>
                <flux:heading size="xl">${{ number_format($this->invoiceStats['overdue_amount'] ?? 0, 2) }}</flux:heading>
                <flux:text size="xs" class="text-zinc-400">Past due</flux:text>
            </div>
        </flux:card>
    </div>

    {{-- Charts Row 1 --}}
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Spending Trend --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Spending Trend</flux:heading>
            <div x-data="{
                chart: null,
                initChart() {
                    this.chart = new Chart(this.$refs.canvas, {
                        type: 'bar',
                        data: {
                            labels: {{ json_encode($this->spendingTrends['labels'] ?? []) }},
                            datasets: [{
                                label: 'Amount ($)',
                                data: {{ json_encode($this->spendingTrends['amounts'] ?? []) }},
                                backgroundColor: 'rgba(99, 102, 241, 0.8)',
                                borderColor: 'rgb(99, 102, 241)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: { 
                                y: { 
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) { return '$' + value.toLocaleString(); }
                                    }
                                } 
                            }
                        }
                    });
                }
            }" x-init="setTimeout(() => initChart(), 100)">
                <canvas x-ref="canvas" class="w-full" height="250"></canvas>
            </div>
        </flux:card>

        {{-- Invoice Aging --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Invoice Aging</flux:heading>
            <div x-data="{
                chart: null,
                initChart() {
                    this.chart = new Chart(this.$refs.canvas, {
                        type: 'bar',
                        data: {
                            labels: {{ json_encode($this->invoiceAging['labels'] ?? []) }},
                            datasets: [{
                                label: 'Amount ($)',
                                data: {{ json_encode($this->invoiceAging['data'] ?? []) }},
                                backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#f97316', '#ef4444']
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: { 
                                y: { 
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) { return '$' + value.toLocaleString(); }
                                    }
                                }
                            }
                        }
                    });
                }
            }" x-init="setTimeout(() => initChart(), 100)">
                <canvas x-ref="canvas" class="w-full" height="250"></canvas>
            </div>
        </flux:card>
    </div>

    {{-- Payment Methods --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Payment Methods</flux:heading>
        <div x-data="{
            chart: null,
            initChart() {
                this.chart = new Chart(this.$refs.canvas, {
                    type: 'doughnut',
                    data: {
                        labels: {{ json_encode($this->paymentMethods['labels'] ?? []) }},
                        datasets: [{
                            data: {{ json_encode($this->paymentMethods['data'] ?? []) }},
                            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: true, position: 'bottom' } }
                    }
                });
            }
        }" x-init="setTimeout(() => initChart(), 100)">
            <canvas x-ref="canvas" class="w-full" height="200"></canvas>
        </div>
    </flux:card>

    {{-- Recent Invoices Table --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Recent Invoices</flux:heading>
        @if($this->recentInvoices->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="text-left py-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Invoice #</th>
                            <th class="text-left py-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Date</th>
                            <th class="text-left py-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Due Date</th>
                            <th class="text-right py-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Amount</th>
                            <th class="text-left py-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($this->recentInvoices as $invoice)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="py-3">
                                    <flux:text size="sm" class="font-mono">{{ $invoice->getFullNumber() }}</flux:text>
                                </td>
                                <td class="py-3">
                                    <flux:text size="sm">{{ $invoice->date->format('M j, Y') }}</flux:text>
                                </td>
                                <td class="py-3">
                                    <flux:text size="sm">{{ $invoice->due_date->format('M j, Y') }}</flux:text>
                                </td>
                                <td class="py-3 text-right">
                                    <flux:text size="sm" class="font-semibold">${{ number_format($invoice->amount, 2) }}</flux:text>
                                </td>
                                <td class="py-3">
                                    <flux:badge size="sm" :color="$invoice->status === 'paid' ? 'green' : ($invoice->status === 'sent' ? 'blue' : 'gray')">
                                        {{ ucfirst($invoice->status) }}
                                    </flux:badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <flux:text class="text-center py-8 text-zinc-500">No invoices found for this period.</flux:text>
        @endif
    </flux:card>
</div>
