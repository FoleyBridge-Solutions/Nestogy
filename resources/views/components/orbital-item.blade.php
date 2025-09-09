@props([
    'icon' => null,
    'label' => '',
    'value' => '',
    'subvalue' => null,
    'color' => 'zinc',
    'hasAlert' => false,
    'alertCount' => 0,
    'size' => 'md', // sm, md, lg
    'href' => null,
    'clickable' => true,
])

@php
    $sizeClasses = [
        'sm' => 'w-20 h-20 p-2',
        'md' => 'w-24 h-24 p-3',
        'lg' => 'w-28 h-28 p-4',
    ][$size];
    
    $colorClasses = [
        'zinc' => 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700',
        'blue' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/30',
        'green' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 hover:bg-green-100 dark:hover:bg-green-900/30',
        'yellow' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800 hover:bg-yellow-100 dark:hover:bg-yellow-900/30',
        'red' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 hover:bg-red-100 dark:hover:bg-red-900/30',
        'purple' => 'bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-800 hover:bg-purple-100 dark:hover:bg-purple-900/30',
    ][$color];
    
    $iconColorClasses = [
        'zinc' => 'text-gray-500 dark:text-gray-400',
        'blue' => 'text-blue-600 dark:text-blue-400',
        'green' => 'text-green-600 dark:text-green-400',
        'yellow' => 'text-yellow-600 dark:text-yellow-400',
        'red' => 'text-red-600 dark:text-red-400',
        'purple' => 'text-purple-600 dark:text-purple-400',
    ][$color];
@endphp

<div 
    {{ $attributes->merge([
        'class' => "orbital-item-content {$sizeClasses} {$colorClasses} rounded-xl border-2 flex flex-col items-center justify-center transition-all duration-200 shadow-sm hover:shadow-md relative cursor-pointer",
        'data-has-alert' => $hasAlert ? 'true' : 'false',
    ]) }}
    @if($href)
        onclick="window.location.href='{{ $href }}'"
    @endif
>
    @if($hasAlert && $alertCount > 0)
        <div class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center text-xs font-bold animate-pulse">
            {{ $alertCount > 9 ? '9+' : $alertCount }}
        </div>
    @endif
    
    @if($icon)
        <flux:icon 
            :name="$icon" 
            class="w-6 h-6 {{ $iconColorClasses }} mb-1"
        />
    @endif
    
    <div class="text-center">
        @if($label)
            <div class="text-xs text-gray-600 dark:text-gray-400 font-medium truncate max-w-full">
                {{ $label }}
            </div>
        @endif
        
        @if($value)
            <div class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate max-w-full">
                {{ $value }}
            </div>
        @endif
        
        @if($subvalue)
            <div class="text-xs text-gray-500 dark:text-gray-500 truncate max-w-full">
                {{ $subvalue }}
            </div>
        @endif
    </div>
</div>