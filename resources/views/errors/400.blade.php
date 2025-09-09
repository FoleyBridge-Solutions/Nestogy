@extends('layouts.app')

@section('title', 'Bad Request - 400')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-24 w-24 text-red-400">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-bold text-gray-900 dark:text-white">
                Bad Request
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                Error 400
            </p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <p class="text-gray-700 dark:text-gray-300 text-center mb-6">
                {{ $message ?? 'The request could not be processed due to invalid data or malformed syntax.' }}
            </p>
            
            <div class="flex space-x-4 justify-center">
                <button onclick="history.back()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Go Back
                </button>
                
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>
            </div>
            
            @if(config('app.debug') && isset($context))
                <div class="mt-8 p-4 bg-gray-100 dark:bg-gray-700 rounded-md">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Debug Information</h4>
                    <pre class="text-xs text-gray-600 dark:text-gray-400 overflow-auto">{{ json_encode($context, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
