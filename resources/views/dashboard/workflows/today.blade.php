<!-- Today's Work Dashboard -->
<div class="mb-8">
    <!-- Today's Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Today's Tickets -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Today's Tickets</p>
                    <p class="text-2xl font-bold text-slate-800 dark:text-white">
                        {{ $data['counts']['todays_tickets'] ?? 0 }}
                    </p>
                </div>
                <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Scheduled Tickets -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Scheduled</p>
                    <p class="text-2xl font-bold text-slate-800 dark:text-white">
                        {{ $data['counts']['scheduled_tickets'] ?? 0 }}
                    </p>
                </div>
                <div class="bg-green-100 dark:bg-green-900 p-3 rounded-lg">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- My Assigned -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">My Assigned</p>
                    <p class="text-2xl font-bold text-slate-800 dark:text-white">
                        {{ $data['counts']['my_assigned_tickets'] ?? 0 }}
                    </p>
                </div>
                <div class="bg-purple-100 dark:bg-purple-900 p-3 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Today's Invoices -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Today's Invoices</p>
                    <p class="text-2xl font-bold text-slate-800 dark:text-white">
                        {{ $data['counts']['todays_invoices'] ?? 0 }}
                    </p>
                </div>
                <div class="bg-emerald-100 dark:bg-emerald-900 p-3 rounded-lg">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Work Lists -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Today's Tickets List -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Today's Tickets</h3>
            @if(isset($data['todays_tickets']) && count($data['todays_tickets']) > 0)
            <div class="space-y-3">
                @foreach($data['todays_tickets']->take(10) as $ticket)
                <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-700">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            #{{ $ticket->id }} - {{ Str::limit($ticket->subject ?? 'No Subject', 40) }}
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ $ticket->client->name ?? 'No Client' }} 
                            @if($ticket->assignee)
                            • Assigned to {{ $ticket->assignee->name }}
                            @endif
                        </p>
                    </div>
                    <span class="px-2 py-1 text-xs font-medium rounded-full
                        @if($ticket->status === 'Open') bg-green-100 text-green-800
                        @elseif($ticket->status === 'In Progress') bg-blue-100 text-blue-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ $ticket->status }}
                    </span>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-slate-500 dark:text-slate-400">No tickets for today</p>
            @endif
        </div>

        <!-- Scheduled for Today -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Scheduled for Today</h3>
            @if(isset($data['scheduled_tickets']) && count($data['scheduled_tickets']) > 0)
            <div class="space-y-3">
                @foreach($data['scheduled_tickets']->take(10) as $ticket)
                <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-700">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            #{{ $ticket->id }} - {{ Str::limit($ticket->subject ?? 'No Subject', 40) }}
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ $ticket->client->name ?? 'No Client' }}
                            @if($ticket->scheduled_at)
                            • {{ $ticket->scheduled_at->format('g:i A') }}
                            @endif
                        </p>
                    </div>
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800">
                        Scheduled
                    </span>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-slate-500 dark:text-slate-400">No scheduled tickets for today</p>
            @endif
        </div>
    </div>
</div>