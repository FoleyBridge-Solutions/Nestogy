@extends('layouts.app')

@section('content')
<div class="container-fluid px-6 py-4">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">SLA Violations</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Tickets that have breached their Service Level Agreement
        </p>
    </div>

    <!-- Alert Banner -->
    @if($tickets->count() > 0)
        <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <flux:icon name="exclamation-triangle" class="h-5 w-5 text-red-400"/>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">
                        <strong>{{ $tickets->total() }} tickets</strong> are currently in SLA violation. 
                        Immediate action is required to prevent further escalation.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- SLA Violations Table -->
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Ticket
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Client
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Priority
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Assigned To
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        SLA Breach
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($tickets as $ticket)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <a href="{{ route('tickets.show', $ticket) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                                    #{{ $ticket->ticket_number }}
                                </a>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ Str::limit($ticket->subject, 50) }}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($ticket->client)
                                <div class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ $ticket->client->name }}
                                </div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $priorityColors = [
                                    'critical' => 'red',
                                    'high' => 'orange',
                                    'medium' => 'yellow',
                                    'low' => 'green'
                                ];
                                $color = $priorityColors[$ticket->priority] ?? 'gray';
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 dark:bg-{{ $color }}-900 dark:text-{{ $color }}-200">
                                {{ ucfirst($ticket->priority) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($ticket->assignee)
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8">
                                        <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                            <span class="text-xs font-medium text-gray-600">
                                                {{ substr($ticket->assignee->name, 0, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $ticket->assignee->name }}
                                        </div>
                                    </div>
                                </div>
                            @else
                                <span class="text-red-600 dark:text-red-400 font-medium">Unassigned</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-red-600 dark:text-red-400 font-semibold">
                                @if($ticket->sla_deadline)
                                    {{ \Carbon\Carbon::parse($ticket->sla_deadline)->diffForHumans() }}
                                @else
                                    Breached
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                Since {{ $ticket->created_at->diffForHumans() }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusColors = [
                                    'open' => 'yellow',
                                    'in_progress' => 'blue',
                                    'waiting' => 'purple',
                                    'on_hold' => 'gray'
                                ];
                                $statusColor = $statusColors[$ticket->status] ?? 'gray';
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800 dark:bg-{{ $statusColor }}-900 dark:text-{{ $statusColor }}-200">
                                {{ str_replace('_', ' ', ucfirst($ticket->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('tickets.show', $ticket) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                View
                            </a>
                            @if(!$ticket->assignee)
                                <button onclick="quickAssign({{ $ticket->id }})" class="ml-3 text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                    Assign
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <flux:icon name="check-circle" class="h-12 w-12 text-green-400 mb-3"/>
                                <p class="text-lg font-medium text-gray-900 dark:text-gray-100">No SLA Violations</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    All tickets are within their SLA requirements
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($tickets->hasPages())
        <div class="mt-6">
            {{ $tickets->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
    function quickAssign(ticketId) {
        // This would open a modal or quick assign dialog
        // For now, redirect to ticket page
        window.location.href = `/tickets/${ticketId}`;
    }
</script>
@endpush
@endsection