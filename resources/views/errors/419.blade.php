@extends('layouts.app')

@section('title', 'Page Expired - 419')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-24 w-24 text-gray-400">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-bold text-gray-900 dark:text-white">
                Page Expired
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                Error 419
            </p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <p class="text-gray-700 dark:text-gray-300 text-center mb-6">
                {{ $message ?? 'Your session has expired. Please refresh the page and try again.' }}
            </p>
            
            @if(Route::has('dashboard'))
            <div class="mb-6">
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-4">Quick Links:</p>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-center p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700">Dashboard</a>
                    @if(Route::has('clients.index'))
                    <a href="{{ route('clients.index') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-center p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700">Clients</a>
                    @endif
                    @if(Route::has('tickets.index'))
                    <a href="{{ route('tickets.index') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-center p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700">Tickets</a>
                    @endif
                    @if(Route::has('financial.invoices.index'))
                    <a href="{{ route('financial.invoices.index') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-center p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700">Invoices</a>
                    @endif
                </div>
            </div>
            @endif
            
            <div class="flex space-x-4 justify-center">
                <button onclick="window.location.reload()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh Page
                </button>
                
                @if(Route::has('dashboard'))
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
