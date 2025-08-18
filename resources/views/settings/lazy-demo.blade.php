@extends('layouts.settings-lazy')

@section('title', 'Settings Demo - Nestogy')

@section('settings-title', 'Settings Demo')
@section('settings-description', 'Demonstration of lazy loading settings functionality')

@section('settings-actions')
<button @click="clearCache()" 
        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
    </svg>
    Clear Cache
</button>
<button @click="window.settingsLazyEnabled = !window.settingsLazyEnabled; $dispatch('notify', { type: 'info', message: 'Lazy loading ' + (window.settingsLazyEnabled ? 'enabled' : 'disabled') })"
        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
    </svg>
    Toggle Lazy Loading
</button>
@endsection

@section('settings-content')
<div class="p-6 text-center">
    <div class="max-w-lg mx-auto">
        <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Lazy Loading Demo</h3>
        <p class="text-gray-600 mb-6">
            Click on any navigation item in the sidebar to see lazy loading in action. 
            Content will be loaded dynamically via AJAX with skeleton loading states.
        </p>
        
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="text-sm">
                    <p class="text-blue-800 font-medium">Features Implemented:</p>
                    <ul class="text-blue-700 mt-1 space-y-1">
                        <li>• Dynamic content loading via AJAX</li>
                        <li>• Skeleton loading states</li>
                        <li>• Content caching</li>
                        <li>• Error handling & retry</li>
                        <li>• Mobile responsive</li>
                        <li>• History API integration</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 mb-2">Performance Benefits</h4>
                <ul class="text-gray-600 space-y-1">
                    <li>• Faster initial page load</li>
                    <li>• Reduced bandwidth usage</li>
                    <li>• Better user experience</li>
                    <li>• Smart caching</li>
                </ul>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 mb-2">Implementation Details</h4>
                <ul class="text-gray-600 space-y-1">
                    <li>• Alpine.js components</li>
                    <li>• AJAX API endpoints</li>
                    <li>• Blade partials</li>
                    <li>• Skeleton components</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Enable lazy loading for this demo
window.settingsLazyEnabled = true;
</script>
@endsection