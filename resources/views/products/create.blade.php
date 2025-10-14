@extends('layouts.app')

@section('title', $type === 'service' ? 'Create Service' : 'Create Product')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $type === 'service' ? 'Create Service' : 'Create Product' }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ $type === 'service' ? 'Set up a new service offering for your clients' : 'Add a new product to your catalog' }}
                </p>
            </div>
            <a href="{{ $type === 'service' ? route('services.index') : route('products.index') }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to {{ $type === 'service' ? 'Services' : 'Products' }}
            </a>
        </div>
    </div>

    @livewire('products.create-product')
</div>
@endsection
