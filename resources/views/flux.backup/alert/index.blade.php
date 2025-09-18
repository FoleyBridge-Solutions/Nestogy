@props([
    'variant' => 'default',
    'icon' => null,
])

@php
    $variantClasses = [
        'default' => 'bg-gray-50 border-gray-200 text-gray-800 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200',
        'success' => 'bg-green-50 border-green-200 text-green-800 dark:bg-green-800 dark:border-green-700 dark:text-green-200',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-800 dark:border-yellow-700 dark:text-yellow-200',
        'danger' => 'bg-red-50 border-red-200 text-red-800 dark:bg-red-800 dark:border-red-700 dark:text-red-200',
        'info' => 'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-800 dark:border-blue-700 dark:text-blue-200',
    ];

    $iconClasses = [
        'default' => 'text-gray-400',
        'success' => 'text-green-400',
        'warning' => 'text-yellow-400',
        'danger' => 'text-red-400',
        'info' => 'text-blue-400',
    ];
@endphp

<div {{ $attributes->class(['border rounded-lg p-4', $variantClasses[$variant] ?? $variantClasses['default']]) }}>
    @if($icon)
        <div class="flex">
            <div class="flex-shrink-0">
                <flux:icon.{{ $icon }} class="h-5 w-5 {{ $iconClasses[$variant] ?? $iconClasses['default'] }}" />
            </div>
            <div class="ml-3">
                {{ $slot }}
            </div>
        </div>
    @else
        {{ $slot }}
    @endif
</div>