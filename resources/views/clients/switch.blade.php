@extends('layouts.app')

@section('title', 'Switch Client')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-6 py-8 sm:px-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Switch Client</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        @if($currentClient)
                            Currently working with <strong>{{ $currentClient->name }}</strong>. Select a different client below.
                        @else
                            Select a client to work with.
                        @endif
                    </p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('clients.index') }}" class="inline-flex items-center px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Client List
                    </a>
                    @if($currentClient)
                        <a href="{{ route('clients.clear-selection') }}" class="inline-flex items-center px-6 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white dark:bg-gray-800 hover:bg-red-50">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Clear Selection
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Current Client (if selected) -->
    @if($currentClient)
        <div class="bg-blue-50 border border-blue-200 rounded-lg">
            <div class="px-6 py-8 sm:px-6">
                <h3 class="text-lg font-medium text-blue-900 mb-6">Current Client</h3>
                <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-blue-100">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-12 w-12">
                            <div class="h-12 w-12 rounded-full bg-blue-500 flex items-center justify-center">
                                <span class="text-lg font-medium text-white">{{ substr($currentClient->name, 0, 2) }}</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-lg font-medium text-gray-900 dark:text-white">{{ $currentClient->name }}</div>
                            @if($currentClient->company_name)
                                <div class="text-sm text-gray-500">{{ $currentClient->company_name }}</div>
                            @endif
                            <div class="text-sm text-gray-500">{{ $currentClient->type ?? 'Individual' }}</div>
                        </div>
                    </div>
                    <div class="flex space-x-3">
                        <a href="{{ route('clients.index') }}" class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200">
                            View Dashboard
                        </a>
                        <span class="inline-flex items-center px-6 py-2 text-sm font-medium text-green-700 bg-green-100 rounded-md">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Current
                        </span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Available Clients -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-6 py-8 sm:px-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                {{ $currentClient ? 'Other Available Clients' : 'Available Clients' }}
            </h3>
            <p class="mt-1 text-sm text-gray-500">Click on a client to select them</p>
        </div>
        
        <div class="divide-y divide-gray-200">
            @forelse($clients->where('id', '!=', $currentClient?->id ?? 0) as $client)
                <div class="px-6 py-6 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 cursor-pointer client-flex flex-wrap -mx-4" 
                     onclick="selectClient({{ $client->id }}, '{{ addslashes($client->name) }}')">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ substr($client->name, 0, 2) }}</span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $client->name }}</div>
                                @if($client->company_name)
                                    <div class="text-sm text-gray-500">{{ $client->company_name }}</div>
                                @endif
                                <div class="flex items-center mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200">
                                        {{ $client->type ?? 'Individual' }}
                                    </span>
                                    @if($client->status === 'active')
                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            @if($client->accessed_at)
                                <div class="text-sm text-gray-500">
                                    Last accessed: {{ $client->accessed_at->diffForHumans() }}
                                </div>
                            @endif
                            <button type="button" 
                                    onclick="event.stopPropagation(); selectClient({{ $client->id }}, '{{ addslashes($client->name) }}')"
                                    class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Select Client
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-6 py-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No other clients available</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        @if($currentClient)
                            You are currently working with your only active client.
                        @else
                            You don't have any active clients yet.
                        @endif
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('clients.create') }}" class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add New Client
                        </a>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>

<script>
// Select client function
function selectClient(clientId, clientName) {
    if (confirm(`Switch to "${clientName}"?`)) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/clients/select/${clientId}`;
        
        const tokenField = document.createElement('input');
        tokenField.type = 'hidden';
        tokenField.name = '_token';
        tokenField.value = '{{ csrf_token() }}';
        
        form.appendChild(tokenField);
        document.body.appendChild(form);
        form.submit();
    }
}

// Add hover effects
document.addEventListener('DOMContentLoaded', function() {
    const clientRows = document.querySelectorAll('.client-flex flex-wrap');
    clientRows.forEach(flex flex-wrap => {
        flex flex-wrap.addEventListener('mouseenter', function() {
            this.classList.add('bg-gray-50 dark:bg-gray-900');
        });
        flex flex-wrap.addEventListener('mouseleave', function() {
            this.classList.remove('bg-gray-50 dark:bg-gray-900');
        });
    });
});
</script>
@endsection
