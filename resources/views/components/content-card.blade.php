@props([
    'noPadding' => false,
    'compact' => false
])

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    @if(!$noPadding)
        <div class="{{ $compact ? 'p-6' : 'p-6' }}">
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</div>
