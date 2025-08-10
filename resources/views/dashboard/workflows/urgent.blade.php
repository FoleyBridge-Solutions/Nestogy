<!-- Urgent Workflow Dashboard -->
<div class="mb-8">
    <!-- Urgent Alerts -->
    @if(isset($alerts) && count($alerts) > 0)
    <div class="mb-6">
        @foreach($alerts as $alert)
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4 rounded-lg">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">{{ $alert['title'] ?? 'Alert' }}</h3>
                    <p class="text-sm text-red-700 mt-1">{{ $alert['message'] ?? '' }}</p>
                    @if(isset($alert['action_url']))
                    <a href="{{ $alert['action_url'] }}" class="text-sm text-red-600 hover:text-red-500 font-medium mt-1 inline-block">
                        {{ $alert['action_text'] ?? 'Take Action' }} →
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Urgent Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Urgent Tickets -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Critical Tickets</h3>
                <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                    {{ $data['counts']['urgent_tickets'] ?? 0 }}
                </span>
            </div>
            @if(isset($data['urgent_tickets']) && count($data['urgent_tickets']) > 0)
            <div class="space-y-3">
                @foreach($data['urgent_tickets']->take(5) as $ticket)
                <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-700">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            #{{ $ticket->id }} - {{ Str::limit($ticket->subject ?? 'No Subject', 30) }}
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ $ticket->client->name ?? 'No Client' }}
                        </p>
                    </div>
                    <span class="px-2 py-1 text-xs font-medium rounded-full
                        @if($ticket->priority === 'Critical') bg-red-100 text-red-800
                        @else bg-orange-100 text-orange-800
                        @endif">
                        {{ $ticket->priority }}
                    </span>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-slate-500 dark:text-slate-400">No urgent tickets</p>
            @endif
        </div>

        <!-- Overdue Invoices -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Overdue Invoices</h3>
                <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                    {{ $data['counts']['overdue_invoices'] ?? 0 }}
                </span>
            </div>
            @if(isset($data['overdue_invoices']) && count($data['overdue_invoices']) > 0)
            <div class="space-y-3">
                @foreach($data['overdue_invoices']->take(5) as $invoice)
                <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-700">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            #{{ $invoice->number ?? $invoice->id }}
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ $invoice->client->name ?? 'No Client' }} - Due {{ $invoice->due_date ? $invoice->due_date->diffForHumans() : 'N/A' }}
                        </p>
                    </div>
                    <span class="text-sm font-bold text-red-600">
                        ${{ number_format($invoice->amount ?? 0, 2) }}
                    </span>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-slate-500 dark:text-slate-400">No overdue invoices</p>
            @endif
        </div>

        <!-- SLA Breaches -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white">SLA Breaches</h3>
                <span class="bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                    {{ $data['counts']['sla_breaches'] ?? 0 }}
                </span>
            </div>
            @if(isset($data['sla_breaches']) && count($data['sla_breaches']) > 0)
            <div class="space-y-3">
                @foreach($data['sla_breaches']->take(5) as $ticket)
                <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-700">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            #{{ $ticket->id }} - {{ Str::limit($ticket->subject ?? 'No Subject', 30) }}
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Open for {{ $ticket->created_at ? $ticket->created_at->diffForHumans(null, true) : 'N/A' }}
                        </p>
                    </div>
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                        {{ $ticket->status }}
                    </span>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-slate-500 dark:text-slate-400">No SLA breaches</p>
            @endif
        </div>
    </div>

    <!-- Quick Actions for Urgent Items -->
    @if(isset($quickActions) && count($quickActions) > 0)
    <div class="flex flex-wrap gap-4 mb-6">
        @foreach($quickActions as $action)
        <a href="{{ route($action['route'], $action['params'] ?? []) }}" 
           class="inline-flex items-center px-4 py-2 bg-{{ $action['color'] ?? 'blue' }}-600 hover:bg-{{ $action['color'] ?? 'blue' }}-700 text-white font-medium rounded-lg transition-colors">
            @if(isset($action['icon']))
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                @switch($action['icon'])
                    @case('exclamation-triangle')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        @break
                    @case('clock')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        @break
                    @case('mail')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        @break
                    @default
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                @endswitch
            </svg>
            @endif
            {{ $action['label'] }}
        </a>
        @endforeach
    </div>
    @endif
</div>