@extends('layouts.app')

@section('title', 'Tickets')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Tickets</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage support tickets and customer requests</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('tickets.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        New Ticket
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Open Tickets</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['open'] ?? 0 }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">In Progress</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['in_progress'] ?? 0 }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Resolved Today</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['resolved_today'] ?? 0 }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Avg Response Time</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['avg_response_time'] ?? '0h' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <form method="GET" action="{{ route('tickets.index') }}" class="space-y-4 sm:space-y-0 sm:flex sm:items-center sm:space-x-4">
                <!-- Search -->
                <div class="flex-1">
                    <label for="search" class="sr-only">Search tickets</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" 
                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500" 
                               placeholder="Search tickets...">
                    </div>
                </div>

                <!-- Status Filter -->
                <div>
                    <select name="status" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md">
                        <option value="">All Status</option>
                        <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>

                <!-- Priority Filter -->
                <div>
                    <select name="priority" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md">
                        <option value="">All Priorities</option>
                        <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>High</option>
                        <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                    </select>
                </div>

                <!-- Client Filter -->
                <div>
                    <select name="client_id" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md">
                        <option value="">All Clients</option>
                        @foreach($clients ?? [] as $client)
                            <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                {{ $client->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Filter
                    </button>
                </div>

                <!-- Clear Filters -->
                @if(request()->hasAny(['search', 'status', 'priority', 'client_id']))
                <div>
                    <a href="{{ route('tickets.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Clear
                    </a>
                </div>
                @endif
            </form>
        </div>
    </div>

    <!-- Tickets Table -->
    <div class="bg-white shadow overflow-hidden rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    {{ $tickets->total() }} {{ Str::plural('Ticket', $tickets->total()) }}
                </h3>
                <div class="flex items-center space-x-2">
                    <!-- Bulk Actions -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Bulk Actions
                            <svg class="ml-2 -mr-0.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="open" @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                            <div class="py-1">
                                <button onclick="bulkAction('close')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">Close Selected</button>
                                <button onclick="bulkAction('assign')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">Assign Selected</button>
                                <button onclick="bulkAction('priority')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">Change Priority</button>
                                <button onclick="bulkAction('export')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left">Export Selected</button>
                            </div>
                        </div>
                    </div>

                    <!-- Export -->
                    <a href="{{ route('tickets.export.csv') }}" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export
                    </a>
                </div>
            </div>
        </div>

        @if($tickets->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'id', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}" class="group inline-flex">
                                Ticket
                                <span class="ml-2 flex-none rounded text-gray-400 group-hover:text-gray-500">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                </span>
                            </a>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Client
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Priority
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Assigned To
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}" class="group inline-flex">
                                Created
                                <span class="ml-2 flex-none rounded text-gray-400 group-hover:text-gray-500">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                </span>
                            </a>
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($tickets as $ticket)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" name="selected_tickets[]" value="{{ $ticket->id }}" class="ticket-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-2 w-2">
                                    <div class="h-2 w-2 rounded-full bg-{{ $ticket->priority_color ?? 'gray' }}-400"></div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <a href="{{ route('tickets.show', $ticket) }}" class="hover:text-blue-600">
                                            #{{ $ticket->id }} - {{ Str::limit($ticket->subject, 50) }}
                                        </a>
                                    </div>
                                    @if($ticket->category)
                                    <div class="text-sm text-gray-500">{{ $ticket->category->name }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8">
                                    @if($ticket->client->avatar)
                                        <img class="h-8 w-8 rounded-full" src="{{ Storage::url($ticket->client->avatar) }}" alt="{{ $ticket->client->name }}">
                                    @else
                                        <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                            <span class="text-xs font-medium text-gray-700">{{ substr($ticket->client->name, 0, 2) }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">
                                        <a href="{{ route('clients.show', $ticket->client) }}" class="hover:text-blue-600">{{ $ticket->client->name }}</a>
                                    </div>
                                    @if($ticket->client->company)
                                    <div class="text-sm text-gray-500">{{ $ticket->client->company }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $ticket->status_color ?? 'gray' }}-100 text-{{ $ticket->status_color ?? 'gray' }}-800">
                                {{ ucfirst(str_replace('_', ' ', $ticket->status ?? 'open')) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $ticket->priority_color ?? 'gray' }}-100 text-{{ $ticket->priority_color ?? 'gray' }}-800">
                                {{ ucfirst($ticket->priority ?? 'medium') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @if($ticket->assigned_to)
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-6 w-6">
                                        <div class="h-6 w-6 rounded-full bg-gray-300 flex items-center justify-center">
                                            <span class="text-xs font-medium text-gray-700">{{ substr($ticket->assignedUser->name, 0, 2) }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-2">{{ $ticket->assignedUser->name }}</div>
                                </div>
                            @else
                                <span class="text-gray-400">Unassigned</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div>{{ $ticket->created_at->format('M j, Y') }}</div>
                            <div class="text-xs text-gray-400">{{ $ticket->created_at->format('g:i A') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('tickets.show', $ticket) }}" class="text-blue-600 hover:text-blue-900">View</a>
                                <a href="{{ route('tickets.edit', $ticket) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                @if($ticket->status !== 'closed')
                                <button onclick="quickClose({{ $ticket->id }})" class="text-green-600 hover:text-green-900">Close</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $tickets->appends(request()->query())->links() }}
        </div>
        @else
        <!-- Empty State -->
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No tickets found</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating your first support ticket.</p>
            <div class="mt-6">
                <a href="{{ route('tickets.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Create Ticket
                </a>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Quick Close Modal -->
<div id="quickCloseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2">Close Ticket</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">Are you sure you want to close this ticket?</p>
                <textarea id="closeReason" placeholder="Optional: Add a closing note..." class="mt-3 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" rows="3"></textarea>
            </div>
            <div class="items-center px-4 py-3">
                <button id="confirmClose" class="px-4 py-2 bg-green-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-green-600">Close</button>
                <button onclick="closeQuickCloseModal()" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 hover:bg-gray-600">Cancel</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
let ticketToClose = null;

// Select All functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.ticket-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Quick close ticket
function quickClose(ticketId) {
    ticketToClose = ticketId;
    document.getElementById('quickCloseModal').classList.remove('hidden');
}

function closeQuickCloseModal() {
    document.getElementById('quickCloseModal').classList.add('hidden');
    ticketToClose = null;
    document.getElementById('closeReason').value = '';
}

document.getElementById('confirmClose').addEventListener('click', function() {
    if (ticketToClose) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/tickets/${ticketToClose}/close`;
        
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'PATCH';
        
        const tokenField = document.createElement('input');
        tokenField.type = 'hidden';
        tokenField.name = '_token';
        tokenField.value = '{{ csrf_token() }}';
        
        const reasonField = document.createElement('input');
        reasonField.type = 'hidden';
        reasonField.name = 'close_reason';
        reasonField.value = document.getElementById('closeReason').value;
        
        form.appendChild(methodField);
        form.appendChild(tokenField);
        form.appendChild(reasonField);
        document.body.appendChild(form);
        form// MIGRATED: .addEventListener('submit', );
    }
});

// Bulk actions
function bulkAction(action) {
    const selectedTickets = Array.from(document.querySelectorAll('.ticket-checkbox:checked')).map(cb => cb.value);
    
    if (selectedTickets.length === 0) {
        alert('Please select at least one ticket.');
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/tickets/bulk-${action}`;
    
    const tokenField = document.createElement('input');
    tokenField.type = 'hidden';
    tokenField.name = '_token';
    tokenField.value = '{{ csrf_token() }}';
.appendChild(tokenField);
    
    selectedTickets.forEach(ticketId => {
        const field = document.createElement('input');
        field.type = 'hidden';
        field.name = 'tickets[]';
        field.value = ticketId;
        form.appendChild(field);
    });
    
    document.body.appendChild(form);
    form// MIGRATED: .addEventListener('submit', );
}

// Close modal when clicking outside
document.getElementById('quickCloseModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeQuickCloseModal();
    }
});
</script>
@endpush
    form