@props([
    'contract',
    'title' => null,
    'subtitle' => null,
    'showActions' => true,
    'actions' => null
])

<div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-6 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-flex-1 px-6 lg:flex-flex flex-wrap -mx-4 lg:items-center lg:justify-between space-y-4 lg:space-y-0">
            <!-- Contract Info -->
            <div class="flex-1 min-w-0">
                <div class="flex items-start space-x-4">
                    <!-- Contract Icon -->
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Title and Details -->
                    <div class="flex-1 min-w-0">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white truncate">
                            {{ $title ?? $contract->title }}
                        </h1>
                        
                        @if($subtitle)
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $subtitle }}</p>
                        @endif

                        <!-- Contract Meta Info -->
                        <div class="flex flex-wrap items-center gap-4 mt-6">
                            <!-- Status Badge -->
                            <x-contracts.display.status-badge :status="$contract->status" />
                            
                            <!-- Contract Number -->
                            <span class="inline-flex items-center px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium rounded">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                </svg>
                                {{ $contract->contract_number }}
                            </span>
                            
                            <!-- Client -->
                            <span class="inline-flex items-center px-2 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 text-xs font-medium rounded">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-2m-2 0H7m5 0v-9a2 2 0 00-2-2H6a2 2 0 00-2 2v9"/>
                                </svg>
                                {{ $contract->client->name ?? 'No Client' }}
                            </span>

                            <!-- Contract Value -->
                            <span class="inline-flex items-center px-2 py-1 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 text-xs font-medium rounded">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                </svg>
                                {{ $contract->getFormattedValue() }}
                            </span>

                            <!-- Schedule Count -->
                            @if($contract->schedules && $contract->schedules->count() > 0)
                                <span class="inline-flex items-center px-2 py-1 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 text-xs font-medium rounded">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h2a2 2 0 002-2z"/>
                                    </svg>
                                    {{ $contract->schedules->count() }} {{ Str::plural('Schedule', $contract->schedules->count()) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            @if($showActions)
                <div class="flex-shrink-0">
                    <div class="flex items-center space-x-3">
                        @if($actions)
                            {{ $actions }}
                        @else
                            <x-contracts.interactive.actions-dropdown :contract="$contract" />
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
