@props([
    'currentStep' => 1,
    'totalSteps' => 5,
    'title' => 'Create Contract'
])

<!-- Compact Header -->
<div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-6 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <h1 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h1>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    Step <span x-text="currentStep"></span> of <span x-text="totalSteps"></span>
                </span>
            </div>
            <a href="{{ route('financial.contracts.index') }}" 
               class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                Cancel
            </a>
        </div>
    </div>
</div>

<!-- Progress Bar -->
<div class="bg-gray-200 dark:bg-gray-700 h-1">
    <div class="bg-blue-600 h-1 transition-all duration-300" 
         :style="`width: ${(currentStep / totalSteps) * 100}%`"></div>
</div>