@extends('layouts.app')

@section('title', 'Priority Queue')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Priority Queue</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">Manage high-priority tickets with SLA tracking and escalation rules.</p>
                    </div>
                    <div class="flex space-x-3">
                        <button type="button" 
                                onclick="autoPrioritize()"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Auto-Prioritize
                        </button>
                        <a href="{{ route('tickets.priority-queue.create') }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add to Queue
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Selector & Stats -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex space-x-4">
                        <button type="button" 
                                onclick="switchView('queue')"
                                class="view-button px-4 py-2 text-sm font-medium rounded-md {{ $view === 'queue' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700' }}">
                            Queue View
                        </button>
                        <button type="button" 
                                onclick="switchView('matrix')"
                                class="view-button px-4 py-2 text-sm font-medium rounded-md {{ $view === 'matrix' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700' }}">
                            Priority Matrix
                        </button>
                        <button type="button" 
                                onclick="switchView('analytics')"
                                class="view-button px-4 py-2 text-sm font-medium rounded-md {{ $view === 'analytics' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700' }}">
                            Analytics
                        </button>
                    </div>
                </div>

                <!-- Queue Statistics -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg border">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Items</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ $queueStats['total_items'] }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm rounded-lg border">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">SLA Breached</dt>
                                        <dd class="text-lg font-medium text-red-600">{{ $queueStats['sla_breached'] }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm rounded-lg border">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Escalated</dt>
                                        <dd class="text-lg font-medium text-orange-600">{{ $queueStats['escalated_items'] }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm rounded-lg border">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Avg Wait Time</dt>
                                        <dd class="text-lg font-medium text-blue-600">{{ $queueStats['avg_wait_days'] }} days</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm rounded-lg border">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Critical Items</dt>
                                        <dd class="text-lg font-medium text-purple-600">{{ $queueStats['priority_breakdown']['Critical'] ?? 0 }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Priority Queue List -->
        @if($view === 'queue')
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Priority Queue 
                            <span class="text-sm text-gray-500">({{ $queueItems->total() }} items)</span>
                        </h3>
                        <div class="flex space-x-3">
                            <button type="button" 
                                    onclick="bulkEscalate()"
                                    class="inline-flex items-center px-3 py-2 border border-orange-300 shadow-sm text-sm leading-4 font-medium rounded-md text-orange-700 bg-white hover:bg-orange-50">
                                Escalate Selected
                            </button>
                            <button type="button" 
                                    onclick="bulkRemove()"
                                    class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50">
                                Remove Selected
                            </button>
                        </div>
                    </div>
                </div>
                
                @if($queueItems->count() > 0)
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left">
                                        <input type="checkbox" 
                                               id="selectAll"
                                               onchange="toggleSelectAll()"
                                               class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SLA Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assignee</th>
                                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="sortableQueue">
                                @foreach($queueItems as $item)
                                    <tr class="hover:bg-gray-50 queue-item" data-id="{{ $item->id }}">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" 
                                                   name="selected_items[]" 
                                                   value="{{ $item->id }}"
                                                   class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <div class="flex items-center">
                                                <svg class="drag-handle h-4 w-4 text-gray-400 cursor-move mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                                </svg>
                                                #{{ $item->queue_position }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div class="flex items-center">
                                                    <span class="text-sm font-medium text-gray-900">
                                                        #{{ $item->ticket->ticket_number }}
                                                    </span>
                                                    @if($item->is_escalated)
                                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                            Escalated
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-sm text-gray-900 mt-1">{{ $item->ticket->subject }}</div>
                                                <div class="text-sm text-gray-500">{{ $item->ticket->client->name }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                @if($item->priority_level === 'Critical') bg-red-100 text-red-800
                                                @elseif($item->priority_level === 'High') bg-orange-100 text-orange-800
                                                @elseif($item->priority_level === 'Medium') bg-yellow-100 text-yellow-800
                                                @elseif($item->priority_level === 'Low') bg-green-100 text-green-800
                                                @else bg-gray-100 text-gray-800
                                                @endif">
                                                {{ $item->priority_level }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 w-16 bg-gray-200 rounded-full h-2">
                                                    <div class="bg-indigo-600 h-2 rounded-full" 
                                                         style="width: {{ min(100, ($item->priority_score / 100) * 100) }}%"></div>
                                                </div>
                                                <span class="ml-2 text-sm text-gray-900">{{ $item->priority_score }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($item->sla_due_at)
                                                @php
                                                    $timeLeft = now()->diffInHours($item->sla_due_at, false);
                                                    $isBreached = $timeLeft < 0;
                                                    $isWarning = $timeLeft < 2 && $timeLeft >= 0;
                                                @endphp
                                                <div class="flex items-center">
                                                    @if($isBreached)
                                                        <svg class="h-4 w-4 text-red-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                        </svg>
                                                        <span class="text-sm text-red-600">Breached</span>
                                                    @elseif($isWarning)
                                                        <svg class="h-4 w-4 text-orange-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                        </svg>
                                                        <span class="text-sm text-orange-600">{{ abs($timeLeft) }}h left</span>
                                                    @else
                                                        <svg class="h-4 w-4 text-green-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                        </svg>
                                                        <span class="text-sm text-green-600">{{ $timeLeft }}h left</span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-sm text-gray-500">No SLA</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            @if($item->ticket->assignee)
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-8 w-8">
                                                        <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                                            <span class="text-xs font-medium text-indigo-800">
                                                                {{ strtoupper(substr($item->ticket->assignee->name, 0, 2)) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="ml-2">
                                                        <div class="text-sm text-gray-900">{{ $item->ticket->assignee->name }}</div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-gray-500 italic">Unassigned</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end space-x-2">
                                                <a href="{{ route('tickets.show', $item->ticket) }}" 
                                                   class="text-indigo-600 hover:text-indigo-900">View</a>
                                                <a href="{{ route('tickets.priority-queue.show', $item) }}" 
                                                   class="text-indigo-600 hover:text-indigo-900">Details</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        {{ $queueItems->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Priority queue is empty</h3>
                        <p class="mt-1 text-sm text-gray-500">Add high-priority tickets to the queue for better management.</p>
                        <div class="mt-6">
                            <a href="{{ route('tickets.priority-queue.create') }}" 
                               class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Add to Queue
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

<script>
function switchView(view) {
    window.location.href = `{{ route('tickets.priority-queue.index') }}?view=${view}`;
}

function autoPrioritize() {
    if (confirm('This will automatically reorder the queue based on priority scores. Continue?')) {
        fetch('{{ route('tickets.priority-queue.auto-prioritize') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ method: 'score' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to auto-prioritize queue');
            }
        });
    }
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('input[name="selected_items[]"]');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function bulkEscalate() {
    const selected = document.querySelectorAll('input[name="selected_items[]"]:checked');
    
    if (selected.length === 0) {
        alert('Please select items to escalate');
        return;
    }

    const reason = prompt('Enter escalation reason:');
    if (!reason) return;

    const ids = Array.from(selected).map(cb => cb.value);
    
    fetch('{{ route('tickets.priority-queue.escalate') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            queue_item_ids: ids,
            escalation_reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to escalate selected items');
        }
    });
}

function bulkRemove() {
    const selected = document.querySelectorAll('input[name="selected_items[]"]:checked');
    
    if (selected.length === 0) {
        alert('Please select items to remove');
        return;
    }

    if (!confirm('Are you sure you want to remove selected items from the priority queue?')) {
        return;
    }

    const ids = Array.from(selected).map(cb => cb.value);
    
    fetch('{{ route('tickets.priority-queue.bulk-update') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            queue_item_ids: ids,
            action: 'remove'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to remove selected items');
        }
    });
}

// Make queue sortable (would require SortableJS or similar library)
// This is a placeholder for drag-and-drop functionality
document.querySelectorAll('.drag-handle').forEach(handle => {
    handle.addEventListener('mousedown', function() {
        // Implement drag and drop reordering
        console.log('Drag and drop would be implemented here');
    });
});
</script>
@endsection