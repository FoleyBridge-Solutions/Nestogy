@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-6">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="mb-6">
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('settings.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            Settings
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-1 text-gray-700 dark:text-gray-200 font-medium">
                                {{ $domainInfo['name'] ?? ucfirst($domain) }}
                            </span>
                        </div>
                    </li>
                </ol>
            </nav>
            
            <div class="mt-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $domainInfo['name'] ?? ucfirst($domain) }} Settings
                </h1>
                @if($domainInfo['description'] ?? null)
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        {{ $domainInfo['description'] }}
                    </p>
                @endif
            </div>
        </div>

        {{-- Category Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($categories as $categoryKey => $categoryData)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                    <a href="{{ route('settings.category.show', ['domain' => $domain, 'category' => $categoryKey]) }}" 
                       class="block hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center">
                                @if($categoryData['metadata']['icon'] ?? null)
                                    <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                    </div>
                                @endif
                                <div class="ml-5 w-0 flex-1">
                                    <dt class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                        {{ $categoryData['metadata']['name'] ?? ucfirst(str_replace('_', ' ', $categoryKey)) }}
                                    </dt>
                                    @if($categoryData['metadata']['description'] ?? null)
                                        <dd class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $categoryData['metadata']['description'] }}
                                        </dd>
                                    @endif
                                </div>
                                <div class="ml-5 flex-shrink-0">
                                    <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            
                            {{-- Show some key settings as preview --}}
                            @if(is_array($categoryData['settings']) && count($categoryData['settings']) > 0)
                                <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                                    @php
                                        $previewSettings = array_slice($categoryData['settings'], 0, 3);
                                    @endphp
                                    <ul class="space-y-1">
                                        @foreach($previewSettings as $key => $value)
                                            <li class="truncate">
                                                <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                <span>{{ is_bool($value) ? ($value ? 'Yes' : 'No') : (is_array($value) ? json_encode($value) : $value) }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                    @if(count($categoryData['settings']) > 3)
                                        <p class="mt-2 text-indigo-600 dark:text-indigo-400">
                                            +{{ count($categoryData['settings']) - 3 }} more settings
                                        </p>
                                    @endif
                                </div>
                            @else
                                <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                                    No settings configured yet
                                </div>
                            @endif
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        {{-- Empty State --}}
        @if(empty($categories))
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No settings categories</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    This domain doesn't have any settings categories yet.
                </p>
                <div class="mt-6">
                    <a href="{{ route('settings.index') }}" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                        Back to Settings
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection