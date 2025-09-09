@props([
    'title' => '',
    'subtitle' => null,
    'compact' => false,
    'actions' => null,
    'backRoute' => null,
    'backLabel' => 'Back'
])

<div class="{{ $compact ? '' : 'mb-6' }}">
    <div class="bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm border-b border-gray-200 dark:border-gray-700">
        <div class="{{ $compact ? 'px-4 py-2 sm:px-6' : 'px-6 py-6 sm:px-6' }}">
            <div class="flex items-center justify-between">
                <div class="min-w-0 flex-1">
                    <h1 class="{{ $compact ? 'text-lg font-semibold' : 'text-xl font-bold' }} text-gray-900 dark:text-white truncate">
                        {{ $title }}
                    </h1>
                    @if($subtitle)
                        <p class="{{ $compact ? 'text-xs' : 'text-sm' }} text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $subtitle }}
                        </p>
                    @endif
                </div>
                
                @if($actions || $backRoute)
                    <div class="ml-4 flex items-center space-x-2">
                        @if($backRoute)
                            <a href="{{ $backRoute }}" 
                               class="inline-flex items-center {{ $compact ? 'px-2.5 py-1 text-xs' : 'px-6 py-1.5 text-sm' }} border border-gray-300 dark:border-gray-600 rounded-md font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 transition-colors">
                                <svg class="{{ $compact ? 'w-3 h-3 mr-1' : 'w-3.5 h-3.5 mr-1.5' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                {{ $backLabel }}
                            </a>
                        @endif
                        
                        {{ $actions }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
