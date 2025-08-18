@extends('layouts.app')

@section('title', 'Assignments & Watchers')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Assignments & Watchers</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">Manage ticket assignments and watcher notification preferences.</p>
                    </div>
                    <div class="flex space-x-3">
                        <button type="button" 
                                onclick="bulkNotifyWatchers()"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 9.172a4 4 0 015.656 0L12 10.828l1.516-1.656a4 4 0 115.656 5.656l-6.928 7.071a1 1 0 01-1.414 0l-6.928-7.071a4 4 0 010-5.656z" />
                            </svg>
                            Notify All Watchers
                        </button>
                        <a href="{{ route('tickets.assignments.create') }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add Assignment
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignment Overview -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg border">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Active Assignments</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ $assignmentStats['active_assignments'] }}</dd>
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Watchers</dt>
                                        <dd class="text-lg font-medium text-blue-600">{{ $assignmentStats['total_watchers'] }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm rounded-lg border">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Resolved This Week</dt>
                                        <dd class="text-lg font-medium text-green-600">{{ $assignmentStats['resolved_this_week'] }}</dd>
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Avg Response Time</dt>
                                        <dd class="text-lg font-medium text-orange-600">{{ $assignmentStats['avg_response_time'] }}h</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Selector & Filters -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex space-x-4">
                        <button type="button" 
                                onclick="switchView('assignments')"
                                class="view-button px-4 py-2 text-sm font-medium rounded-md {{ $view === 'assignments' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700' }}">
                            Assignments
                        </button>
                        <button type="button" 
                                onclick="switchView('watchers')"
                                class="view-button px-4 py-2 text-sm font-medium rounded-md {{ $view === 'watchers' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700' }}">
                            Watchers
                        </button>
                        <button type="button" 
                                onclick="switchView('analytics')"
                                class="view-button px-4 py-2 text-sm font-medium rounded-md {{ $view === 'analytics' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700' }}">
                            Analytics
                        </button>
                    </div>

                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <label class="text-sm text-gray-700">Filter by:</label>
                            <select name="user_filter" onchange="filterByUser(this.value)" class="rounded-md border-gray-300 text-sm">
                                <option value="all" {{ request('user') === 'all' ? 'selected' : '' }}>All Users</option>
                                @foreach($availableUsers as $user)
                                    <option value="{{ $user->id }}" {{ request('user') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" 
                                   name="search" 
                                   placeholder="Search tickets..."
                                   value="{{ request('search') }}"
                                   onkeyup="searchTickets(this.value)"
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignments View -->
        @if($view === 'assignments')
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Ticket Assignments 
                            <span class="text-sm text-gray-500">({{ $assignedTickets->total() }} tickets)</span>
                        </h3>
                        <div class="flex space-x-3">
                            <button type="button" 
                                    onclick="bulkReassign()"
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Reassign Selected
                            </button>
                            <button type="button" 
                                    onclick="exportAssignments()"
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Export
                            </button>
                        </div>
                    </div>
                </div>
                
                @if($assignedTickets->count() > 0)
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
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assignee</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Watchers</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Activity</th>
                                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($assignedTickets as $ticket)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" 
                                                   name="selected_tickets[]" 
                                                   value="{{ $ticket->id }}"
                                                   class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div class="flex items-center">
                                                    <span class="text-sm font-medium text-gray-900">
                                                        #{{ $ticket->ticket_number }}
                                                    </span>
                                                    @if($ticket->due_at && $ticket->due_at->isPast())
                                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                            Overdue
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-sm text-gray-900 mt-1">{{ $ticket->subject }}</div>
                                                <div class="text-sm text-gray-500">{{ $ticket->client->name }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($ticket->assignee)
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-8 w-8">
                                                        <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                                            <span class="text-xs font-medium text-indigo-800">
                                                                {{ strtoupper(substr($ticket->assignee->name, 0, 2)) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="ml-2">
                                                        <div class="text-sm text-gray-900">{{ $ticket->assignee->name }}</div>
                                                        <div class="text-xs text-gray-500">
                                                            Assigned {{ $ticket->assigned_at ? $ticket->assigned_at->diffForHumans() : 'recently' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-gray-500 italic">Unassigned</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium @if($ticket->status === 'open') bg-green-100 text-green-800 @elseif($ticket->status === 'in_progress') bg-blue-100 text-blue-800 @elseif($ticket->status === 'pending') bg-yellow-100 text-yellow-800 @elseif($ticket->status === 'resolved') bg-purple-100 text-purple-800 @elseif($ticket->status === 'closed') bg-gray-100 text-gray-800 @else bg-gray-100 text-gray-800 @endif">
                                                {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium @if($ticket->priority === 'critical') bg-red-100 text-red-800 @elseif($ticket->priority === 'high') bg-orange-100 text-orange-800 @elseif($ticket->priority === 'medium') bg-yellow-100 text-yellow-800 @elseif($ticket->priority === 'low') bg-green-100 text-green-800 @else bg-gray-100 text-gray-800 @endif">
                                                {{ ucfirst($ticket->priority) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($ticket->watchers->count() > 0)
                                                <div class="flex items-center">
                                                    <div class="flex -space-x-1 overflow-hidden">
                                                        @foreach($ticket->watchers->take(3) as $watcher)
                                                            <div class="inline-block h-6 w-6 rounded-full bg-gray-100 ring-2 ring-white">
                                                                <span class="text-xs font-medium text-gray-600 flex items-center justify-center h-full">
                                                                    {{ strtoupper(substr($watcher->user->name, 0, 1)) }}
                                                                </span>
                                                            </div>
                                                        @endforeach
                                                        @if($ticket->watchers->count() > 3)
                                                            <div class="inline-block h-6 w-6 rounded-full bg-gray-200 ring-2 ring-white">
                                                                <span class="text-xs font-medium text-gray-500 flex items-center justify-center h-full">
                                                                    +{{ $ticket->watchers->count() - 3 }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <span class="ml-2 text-xs text-gray-500">
                                                        {{ $ticket->watchers->count() }} watching
                                                    </span>
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-500">No watchers</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div>
                                                {{ $ticket->updated_at->format('M j, Y') }}
                                                <div class="text-xs text-gray-500">
                                                    {{ $ticket->updated_at->format('g:i A') }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end space-x-2">
                                                <button type="button" 
                                                        onclick="manageWatchers({{ $ticket->id }})"
                                                        class="text-blue-600 hover:text-blue-900 text-xs">Watchers</button>
                                                <button type="button" 
                                                        onclick="reassignTicket({{ $ticket->id }})"
                                                        class="text-green-600 hover:text-green-900 text-xs">Reassign</button>
                                                <a href="{{ route('tickets.show', $ticket) }}" 
                                                   class="text-indigo-600 hover:text-indigo-900 text-xs">View</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        {{ $assignedTickets->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No assigned tickets</h3>
                        <p class="mt-1 text-sm text-gray-500">Assign tickets to team members to distribute workload effectively.</p>
                        <div class="mt-6">
                            <a href="{{ route('tickets.assignments.create') }}" 
                               class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Create Assignment
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

<!-- Watcher Management Modal -->
<div id="watcherModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeWatcherModal()"></div>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="watcherModalTitle">Manage Watchers</h3>
                        <div class="mt-4" id="watcherModalContent">
                            <!-- Watcher management content will be populated here -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-flex flex-wrap -mx-4-reverse">
                <button type="button" 
                        onclick="saveWatchers()"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                    Save Changes
                </button>
                <button type="button" 
                        onclick="closeWatcherModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function switchView(view) {
    window.location.href = `{{ route('tickets.assignments.index') }}?view=${view}`;
}

function filterByUser(userId) {
    const url = new URL(window.location);
    url.searchParams.set('user', userId);
    window.location.href = url.toString();
}

function searchTickets(query) {
    const url = new URL(window.location);
    if (query) {
        url.searchParams.set('search', query);
    } else {
        url.searchParams.delete('search');
    }
    
    // Debounce the search
    clearTimeout(this.searchTimeout);
    this.searchTimeout = setTimeout(() => {
        window.location.href = url.toString();
    }, 500);
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('input[name="selected_tickets[]"]');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function manageWatchers(ticketId) {
    fetch(`{{ route('tickets.assignments.index') }}/${ticketId}/watchers`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('watcherModalTitle').textContent = `Manage Watchers - Ticket #${data.ticket.ticket_number}`;
        
        let content = '<div class="space-y-4">';
        content += '<div class="text-sm text-gray-600 mb-4">' + data.ticket.subject + '</div>';
        
        // Current watchers
        if (data.watchers.length > 0) {
            content += '<div><h4 class="text-sm font-medium text-gray-900 mb-2">Current Watchers:</h4>';
            content += '<div class="space-y-2">';
            data.watchers.forEach(watcher => {
                content += `
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                        <div class="flex items-center">
                            <div class="h-8 w-8 rounded-full bg-indigo-100 