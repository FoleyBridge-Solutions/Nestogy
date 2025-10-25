<div class="space-y-8">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="space-y-2">
            <flux:heading size="2xl">Welcome back, {{ $contact->name }}!</flux:heading>
            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-300">
                {{ $client->name }} • {{ now()->format('M j, Y g:i A') }}
            </flux:text>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(isset($contractStats))
                <flux:badge icon="shield-check" color="indigo">
                    {{ $contractStats['active_contracts'] ?? 0 }} active contracts
                </flux:badge>
            @endif

            @if(isset($ticketStats))
                <flux:badge icon="lifebuoy" color="{{ ($ticketStats['open_tickets'] ?? 0) > 0 ? 'amber' : 'emerald' }}">
                    {{ $ticketStats['open_tickets'] ?? 0 }} open tickets
                </flux:badge>
            @endif
        </div>
    </div>

    @php
        $criticalAlerts = [];
        if (isset($ticketStats) && ($ticketStats['open_tickets'] ?? 0) > 0) {
            $criticalAlerts[] = [
                'icon' => 'lifebuoy',
                'color' => 'rose',
                'title' => 'Open Support Tickets',
                'message' => ($ticketStats['open_tickets'] ?? 0) . ' support tickets are waiting for updates',
                'action' => route('client.tickets') ?? '#',
            ];
        }
        if (isset($invoiceStats) && ($invoiceStats['outstanding_amount'] ?? 0) > 0) {
            $criticalAlerts[] = [
                'icon' => 'banknotes',
                'color' => 'amber',
                'title' => 'Outstanding Balance',
                'message' => '$' . number_format($invoiceStats['outstanding_amount'] ?? 0, 2) . ' is currently outstanding',
                'action' => route('client.invoices'),
            ];
        }
        if (isset($assetStats) && ($assetStats['maintenance_due'] ?? 0) > 0) {
            $criticalAlerts[] = [
                'icon' => 'wrench-screwdriver',
                'color' => 'sky',
                'title' => 'Maintenance Needed',
                'message' => ($assetStats['maintenance_due'] ?? 0) . ' assets require maintenance soon',
                'action' => route('client.assets') ?? '#',
            ];
        }
    @endphp

    @if(count($criticalAlerts) > 0)
        <div class="grid gap-4 md:grid-cols-{{ min(3, count($criticalAlerts)) }}">
            @foreach($criticalAlerts as $alert)
                <flux:callout icon="{{ $alert['icon'] }}" color="{{ $alert['color'] }}" variant="secondary">
                    <flux:callout.heading>{{ $alert['title'] }}</flux:callout.heading>
                    <flux:callout.text>{{ $alert['message'] }}</flux:callout.text>
                    <x-slot name="actions">
                        <flux:button href="{{ $alert['action'] }}" size="sm">View details</flux:button>
                    </x-slot>
                </flux:callout>
            @endforeach
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @if(isset($contractStats))
            <flux:card class="border-indigo-200 dark:border-indigo-800">
                <div class="flex items-start justify-between">
                    <div class="space-y-1">
                        <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Active Contracts</flux:text>
                        <flux:heading size="xl">{{ $contractStats['active_contracts'] ?? 0 }}</flux:heading>
                        <flux:text size="xs" class="text-zinc-400 dark:text-zinc-500">
                            {{ $contractStats['total_contracts'] ?? 0 }} total • ${{ number_format($contractStats['total_contract_value'] ?? 0, 2) }} value
                        </flux:text>
                    </div>
                    <flux:badge color="indigo" icon="document-text">Contracts</flux:badge>
                </div>
            </flux:card>
        @endif

        @if(isset($invoiceStats))
            <flux:card class="border-amber-200 dark:border-amber-800">
                <div class="flex items-start justify-between">
                    <div class="space-y-1">
                        <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Outstanding Balance</flux:text>
                        <flux:heading size="xl">${{ number_format($invoiceStats['outstanding_amount'] ?? 0, 2) }}</flux:heading>
                        <flux:text size="xs" class="text-zinc-400 dark:text-zinc-500">
                            {{ $invoiceStats['total_invoices'] ?? 0 }} invoices • {{ $invoiceStats['overdue_count'] ?? 0 }} overdue
                        </flux:text>
                    </div>
                    <flux:badge color="amber" icon="banknotes">Billing</flux:badge>
                </div>
            </flux:card>
        @endif

        @if(isset($ticketStats))
            <flux:card class="border-sky-200 dark:border-sky-800">
                <div class="flex items-start justify-between">
                    <div class="space-y-1">
                        <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Support Tickets</flux:text>
                        <flux:heading size="xl">{{ $ticketStats['open_tickets'] ?? 0 }}</flux:heading>
                        <flux:text size="xs" class="text-zinc-400 dark:text-zinc-500">
                            {{ $ticketStats['total_tickets'] ?? 0 }} total • Avg response {{ $ticketStats['avg_response_time'] ?? '—' }}
                        </flux:text>
                    </div>
                    <flux:badge color="sky" icon="lifebuoy">Support</flux:badge>
                </div>
            </flux:card>
        @endif

        @if(isset($assetStats))
            <flux:card class="border-emerald-200 dark:border-emerald-800">
                <div class="flex items-start justify-between">
                    <div class="space-y-1">
                        <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Managed Assets</flux:text>
                        <flux:heading size="xl">{{ $assetStats['total_assets'] ?? 0 }}</flux:heading>
                        <flux:text size="xs" class="text-zinc-400 dark:text-zinc-500">
                            {{ $assetStats['maintenance_due'] ?? 0 }} needs maintenance • {{ $assetStats['warranty_expiring'] ?? 0 }} warranty expiring
                        </flux:text>
                    </div>
                    <flux:badge color="emerald" icon="server-stack">Assets</flux:badge>
                </div>
            </flux:card>
        @endif
    </div>

    @if(isset($recentTickets) && $recentTickets->count() > 0)
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="lg">Recent Support Tickets</flux:heading>
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400 mt-1">Your latest support requests and their status</flux:text>
                </div>
                <flux:button variant="primary" size="sm" icon="plus" href="{{ route('client.tickets.create') ?? '#' }}">
                    New Ticket
                </flux:button>
            </div>
            <div class="mt-6 space-y-3">
                @foreach($recentTickets as $ticket)
                    <div class="flex items-center justify-between p-4 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <flux:badge size="sm" color="{{ $ticket->status === 'Open' ? 'rose' : ($ticket->status === 'Closed' ? 'emerald' : 'amber') }}">
                                    {{ $ticket->status }}
                                </flux:badge>
                                <flux:heading size="sm">#{{ $ticket->id }} - {{ $ticket->subject }}</flux:heading>
                            </div>
                            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400 mt-1">
                                Created {{ $ticket->created_at->diffForHumans() }}
                                @if($ticket->assignedTo)
                                    • Assigned to {{ $ticket->assignedTo->name }}
                                @endif
                            </flux:text>
                        </div>
                        <flux:button variant="ghost" size="xs" href="{{ route('client.tickets.show', $ticket->id) ?? '#' }}" icon="arrow-right">
                            View
                        </flux:button>
                    </div>
                @endforeach
            </div>
        </flux:card>
    @endif

    @if(isset($paymentHistory) && $paymentHistory->count() > 0)
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="lg">Recent Payments</flux:heading>
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400 mt-1">Your payment history and transactions</flux:text>
                </div>
                <flux:button variant="ghost" size="sm" href="{{ route('client.invoices') }}">View All</flux:button>
            </div>
            <div class="mt-6 overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="text-left py-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Date</th>
                            <th class="text-left py-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Invoice</th>
                            <th class="text-left py-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Method</th>
                            <th class="text-right py-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($paymentHistory as $payment)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="py-3">
                                    <flux:text size="sm">{{ $payment['date']->format('M j, Y') }}</flux:text>
                                </td>
                                <td class="py-3">
                                    <flux:text size="sm">{{ $payment['invoice_number'] }}</flux:text>
                                </td>
                                <td class="py-3">
                                    <flux:badge size="sm" color="zinc">{{ $payment['method'] }}</flux:badge>
                                </td>
                                <td class="py-3 text-right">
                                    <flux:text size="sm" class="font-semibold">${{ number_format($payment['amount'], 2) }}</flux:text>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>
    @endif

    @if(isset($serviceStatus) && count($serviceStatus) > 0)
        <flux:card>
            <flux:heading size="lg" class="flex items-center gap-2">
                <flux:icon name="signal" class="text-emerald-500" />
                Service Status
            </flux:heading>
            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400 mt-1">Real-time status of your services</flux:text>
            <div class="mt-6 space-y-3">
                @foreach($serviceStatus as $service)
                    <div class="flex items-center justify-between p-4 rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center gap-3">
                            <div class="h-3 w-3 rounded-full {{ $service['status'] === 'operational' ? 'bg-emerald-500' : 'bg-rose-500' }} animate-pulse"></div>
                            <div>
                                <flux:heading size="sm">{{ $service['name'] }}</flux:heading>
                                <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">
                                    Last checked {{ $service['lastChecked']->diffForHumans() }}
                                </flux:text>
                            </div>
                        </div>
                        <flux:badge color="{{ $service['status'] === 'operational' ? 'emerald' : 'rose' }}">
                            {{ ucfirst($service['status']) }}
                        </flux:badge>
                    </div>
                @endforeach
            </div>
        </flux:card>
    @endif

    <div class="space-y-6">
            <flux:card>
                <flux:heading size="lg" class="flex items-center gap-2">
                    <flux:icon name="heart" class="text-rose-500" />
                    System Health
                </flux:heading>
                <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400 mt-1">
                    Health indicators from the last 30 days
                </flux:text>
                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    @if(isset($assetStats))
                        <flux:card class="border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-950/20">
                            <flux:heading size="sm">Maintenance Alerts</flux:heading>
                            <flux:text size="sm" class="mt-2 text-amber-800 dark:text-amber-200">
                                {{ $assetStats['maintenance_due'] ?? 0 }} assets need attention in the next 30 days.
                            </flux:text>
                        </flux:card>

                        <flux:card class="border-sky-200 dark:border-sky-800 bg-sky-50/50 dark:bg-sky-950/20">
                            <flux:heading size="sm">Warranty Coverage</flux:heading>
                            <flux:text size="sm" class="mt-2 text-sky-800 dark:text-sky-200">
                                {{ $assetStats['warranty_expiring'] ?? 0 }} assets are within 60 days of warranty expiration.
                            </flux:text>
                        </flux:card>
                    @endif

                    @if(isset($ticketStats))
                        <flux:card class="border-rose-200 dark:border-rose-800 bg-rose-50/50 dark:bg-rose-950/20">
                            <flux:heading size="sm">Support Load</flux:heading>
                            <flux:text size="sm" class="mt-2 text-rose-800 dark:text-rose-200">
                                {{ $ticketStats['open_tickets'] ?? 0 }} tickets open • {{ $ticketStats['resolved_this_month'] ?? 0 }} resolved this month.
                            </flux:text>
                        </flux:card>
                    @endif

                    @if(isset($projectStats))
                        <flux:card class="border-emerald-200 dark:border-emerald-800 bg-emerald-50/50 dark:bg-emerald-950/20">
                            <flux:heading size="sm">Project Momentum</flux:heading>
                            <flux:text size="sm" class="mt-2 text-emerald-800 dark:text-emerald-200">
                                {{ $projectStats['active_projects'] ?? 0 }} active projects • {{ $projectStats['projects_on_time'] ?? 0 }} tracking on schedule.
                            </flux:text>
                        </flux:card>
                    @endif
                </div>
            </flux:card>

            <div class="grid gap-6 lg:grid-cols-2">
                @if(isset($ticketTrends) && !empty($ticketTrends['labels']))
                    <flux:card>
                    <flux:heading size="lg" class="flex items-center gap-2">
                        <flux:icon name="chart-bar" class="text-indigo-500" />
                        Ticket Trends
                    </flux:heading>
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400 mt-1">Support ticket volume over the last 6 months</flux:text>
                    <div class="mt-6" x-data="{
                        chart: null,
                        initChart() {
                            this.chart = new Chart(this.$refs.canvas, {
                                type: 'line',
                                data: {
                                    labels: {{ json_encode($ticketTrends['labels']) }},
                                    datasets: [{
                                        label: 'Open',
                                        data: {{ json_encode($ticketTrends['open']) }},
                                        borderColor: 'rgb(239, 68, 68)',
                                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                        tension: 0.4
                                    }, {
                                        label: 'Closed',
                                        data: {{ json_encode($ticketTrends['closed']) }},
                                        borderColor: 'rgb(34, 197, 94)',
                                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                        tension: 0.4
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: true,
                                            position: 'bottom'
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true
                                        }
                                    }
                                }
                            });
                        }
                    }" x-init="setTimeout(() => initChart(), 100)">
                        <canvas x-ref="canvas" class="w-full" height="250"></canvas>
                    </div>
                </flux:card>
            @endif

            @if(isset($spendingTrends) && !empty($spendingTrends['labels']))
                <flux:card>
                    <flux:heading size="lg" class="flex items-center gap-2">
                        <flux:icon name="currency-dollar" class="text-emerald-500" />
                        Spending Trends
                    </flux:heading>
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400 mt-1">Your spending over the last 6 months</flux:text>
                    <div class="mt-6" x-data="{
                        chart: null,
                        initChart() {
                            this.chart = new Chart(this.$refs.canvas, {
                                type: 'bar',
                                data: {
                                    labels: {{ json_encode($spendingTrends['labels']) }},
                                    datasets: [{
                                        label: 'Amount ($)',
                                        data: {{ json_encode($spendingTrends['amounts']) }},
                                        backgroundColor: 'rgba(99, 102, 241, 0.8)',
                                        borderColor: 'rgb(99, 102, 241)',
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: false
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
                        }
                    }" x-init="setTimeout(() => initChart(), 100)">
                        <canvas x-ref="canvas" class="w-full" height="250"></canvas>
                    </div>
                    </flux:card>
                @endif
            </div>

            <flux:card>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <flux:heading size="lg">Quick Actions</flux:heading>
                        <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Jump back into your most common workflows.</flux:text>
                    </div>
                    <div class="flex gap-2">
                        <flux:button size="xs" variant="ghost" icon="bolt">Shortcuts</flux:button>
                        <flux:button size="xs" variant="ghost" icon="sparkles">New Request</flux:button>
                    </div>
                </div>
                <div class="mt-6 grid gap-3 md:grid-cols-2">
                    @if(isset($contractStats))
                        <flux:button variant="ghost" icon="document-text" href="{{ route('client.contracts') }}" class="justify-start">
                            View contracts
                        </flux:button>
                    @endif

                    @if(isset($invoiceStats))
                        <flux:button variant="ghost" icon="banknotes" href="{{ route('client.invoices') }}" class="justify-start">
                            View invoices
                        </flux:button>
                    @endif

                    @if(isset($ticketStats))
                        <flux:button variant="ghost" icon="lifebuoy" href="{{ route('client.tickets') ?? '#' }}" class="justify-start">
                            Support tickets
                        </flux:button>
                    @endif

                    @if(isset($assetStats))
                        <flux:button variant="ghost" icon="server-stack" href="{{ route('client.assets') ?? '#' }}" class="justify-start">
                            View assets
                        </flux:button>
                    @endif

                    <flux:button variant="ghost" icon="user" href="{{ route('client.profile') }}" class="justify-start">
                        Account settings
                    </flux:button>
                </div>
            </flux:card>

            <div class="grid gap-6 lg:grid-cols-4">
                @if(isset($recentDocuments) && $recentDocuments->count() > 0)
                    <flux:card>
                    <flux:heading size="lg" class="flex items-center gap-2">
                        <flux:icon name="folder" class="text-blue-500" />
                        Recent Documents
                    </flux:heading>
                    <div class="mt-4 space-y-2">
                        @foreach($recentDocuments as $doc)
                            <a href="{{ $doc['url'] }}" class="flex items-center justify-between p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors group">
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <flux:icon name="{{ $doc['icon'] }}" class="text-zinc-400 shrink-0" />
                                    <div class="min-w-0">
                                        <flux:heading size="sm" class="truncate">{{ $doc['name'] }}</flux:heading>
                                        <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">
                                            {{ $doc['type'] }} • {{ $doc['date']->format('M j, Y') }}
                                        </flux:text>
                                    </div>
                                </div>
                                <flux:icon name="arrow-down-tray" class="text-zinc-400 group-hover:text-zinc-600 dark:group-hover:text-zinc-300 shrink-0" />
                            </a>
                        @endforeach
                    </div>
                    </flux:card>
                @endif

                @if(isset($activeProjects) && $activeProjects->count() > 0)
                    <flux:card>
                    <flux:heading size="lg" class="flex items-center gap-2">
                        <flux:icon name="briefcase" class="text-violet-500" />
                        Active Projects
                    </flux:heading>
                    <div class="mt-4 space-y-4">
                        @foreach($activeProjects as $project)
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <flux:heading size="sm">{{ $project['name'] }}</flux:heading>
                                    <flux:badge size="sm" color="indigo">{{ $project['progress'] }}%</flux:badge>
                                </div>
                                <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
                                    <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $project['progress'] }}%"></div>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">
                                        {{ $project['tasks_remaining'] }} tasks remaining
                                    </flux:text>
                                    @if($project['due_date'])
                                        <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">
                                            Due {{ \Carbon\Carbon::parse($project['due_date'])->format('M j') }}
                                        </flux:text>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    </flux:card>
                @endif

                @if(isset($knowledgeBaseArticles) && $knowledgeBaseArticles->count() > 0)
                    <flux:card>
                    <flux:heading size="lg" class="flex items-center gap-2">
                        <flux:icon name="book-open" class="text-amber-500" />
                        Helpful Articles
                    </flux:heading>
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400 mt-1">Quick guides to help you</flux:text>
                    <div class="mt-4 space-y-2">
                        @foreach($knowledgeBaseArticles as $article)
                            <a href="#" class="flex items-start gap-3 p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors group">
                                <flux:icon name="document-text" class="text-zinc-400 mt-0.5 shrink-0" />
                                <div class="flex-1 min-w-0">
                                    <flux:heading size="sm" class="group-hover:text-indigo-600 dark:group-hover:text-indigo-400">{{ $article['title'] }}</flux:heading>
                                    <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">
                                        {{ $article['category'] }} • {{ $article['views'] }} views
                                    </flux:text>
                                </div>
                            </a>
                        @endforeach>
                    </div>
                    </flux:card>
                @endif

                @if(isset($assetHealth))
                    <flux:card>
                    <flux:heading size="lg" class="flex items-center gap-2">
                        <flux:icon name="server-stack" class="text-emerald-500" />
                        Asset Health Overview
                    </flux:heading>
                    <div class="mt-4">
                        <div class="flex items-center justify-center mb-4">
                            <div class="relative w-32 h-32">
                                <svg class="transform -rotate-90 w-32 h-32">
                                    <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="8" fill="transparent" class="text-zinc-200 dark:text-zinc-700" />
                                    <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="8" fill="transparent" 
                                            class="{{ $assetHealth['overall'] >= 80 ? 'text-emerald-500' : ($assetHealth['overall'] >= 50 ? 'text-amber-500' : 'text-rose-500') }}"
                                            stroke-dasharray="351.86" 
                                            stroke-dashoffset="{{ 351.86 - ($assetHealth['overall'] / 100 * 351.86) }}"
                                            stroke-linecap="round" />
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <flux:heading size="xl">{{ $assetHealth['overall'] }}%</flux:heading>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <flux:heading size="lg" class="text-emerald-500">{{ $assetHealth['healthy'] }}</flux:heading>
                                <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">Healthy</flux:text>
                            </div>
                            <div>
                                <flux:heading size="lg" class="text-amber-500">{{ $assetHealth['warning'] }}</flux:heading>
                                <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">Warning</flux:text>
                            </div>
                            <div>
                                <flux:heading size="lg" class="text-rose-500">{{ $assetHealth['critical'] }}</flux:heading>
                                <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">Critical</flux:text>
                            </div>
                        </div>
                    </div>
                    </flux:card>
                @endif
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                @if(isset($pendingActions) && count($pendingActions) > 0)
                    <flux:card>
                    <flux:heading size="lg" class="flex items-center gap-2">
                        <flux:icon name="inbox" class="text-indigo-500" />
                        Pending Actions
                    </flux:heading>
                    <div class="mt-4 space-y-3">
                        @foreach($pendingActions as $action)
                            <flux:callout icon="{{ $action['type'] === 'invoice' ? 'banknotes' : 'document-text' }}" variant="secondary" inline>
                                <flux:callout.heading>{{ $action['message'] }}</flux:callout.heading>
                                <flux:callout.text>{{ $action['count'] }} item(s) awaiting your review.</flux:callout.text>
                                <x-slot name="actions">
                                    <flux:button size="sm" href="{{ $action['action_url'] }}">Review</flux:button>
                                </x-slot>
                            </flux:callout>
                        @endforeach
                    </div>
                    </flux:card>
                @endif

                @if(isset($recentActivity) && count($recentActivity) > 0)
                    <flux:card>
                    <flux:heading size="lg" class="flex items-center gap-2">
                        <flux:icon name="sparkles" class="text-purple-500" />
                        Recent Activity
                    </flux:heading>
                    <div class="mt-4 space-y-4">
                        @foreach(array_slice($recentActivity, 0, 6) as $activity)
                            <div class="flex gap-3 border-l-2 border-zinc-200 dark:border-zinc-700 pl-4">
                                <div class="flex-1">
                                    <flux:heading size="sm">{{ $activity['title'] ?? 'Update' }}</flux:heading>
                                    @if(isset($activity['description']))
                                        <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400 mt-1">{{ $activity['description'] }}</flux:text>
                                    @endif
                                    @if(isset($activity['date']))
                                        <flux:text size="xs" class="text-zinc-400 mt-1">{{ \Carbon\Carbon::parse($activity['date'])->diffForHumans() }}</flux:text>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    </flux:card>
                @endif

                @if(isset($upcomingMilestones) && count($upcomingMilestones) > 0)
                    <flux:card>
                    <flux:heading size="lg" class="flex items-center gap-2">
                        <flux:icon name="flag" class="text-emerald-500" />
                        Upcoming Milestones
                    </flux:heading>
                    <div class="mt-4 space-y-3">
                        @foreach($upcomingMilestones as $milestone)
                            <flux:card>
                                <flux:heading size="sm">{{ $milestone['name'] ?? 'Milestone' }}</flux:heading>
                                <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400 mt-1">
                                    Due {{ isset($milestone['due_date']) ? \Carbon\Carbon::parse($milestone['due_date'])->format('M j, Y') : 'TBD' }}
                                </flux:text>
                                <div class="mt-2 flex items-center gap-2">
                                    <flux:badge size="sm" color="{{ ($milestone['status'] ?? 'open') === 'completed' ? 'emerald' : 'indigo' }}">
                                        {{ ucfirst($milestone['status'] ?? 'open') }}
                                    </flux:badge>
                                    @if(isset($milestone['contract_id']))
                                        <flux:button size="xs" variant="ghost" href="{{ route('client.contracts.show', $milestone['contract_id']) }}">
                                            View contract
                                        </flux:button>
                                    @endif
                                </div>
                            </flux:card>
                        @endforeach
                    </div>
                    </flux:card>
                @endif
            </div>
    </div>
</div>
