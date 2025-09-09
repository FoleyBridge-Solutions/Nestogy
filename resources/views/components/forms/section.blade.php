@props([
    'title',
    'description' => null,
    'icon' => null,
    'class' => '',
    'headerClass' => '',
    'bodyClass' => ''
])

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-900/5 overflow-hidden {{ $class }}">
    <!-- Header -->
    <div class="px-6 py-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 {{ $headerClass }}">
        <div class="flex items-center">
            @if($icon)
                <div class="flex-shrink-0 mr-3">
                    {!! $icon !!}
                </div>
            @endif
            
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $title }}
                </h3>
                
                @if($description)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ $description }}
                    </p>
                @endif
            </div>
            
            @isset($actions)
                <div class="flex-shrink-0">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    </div>
    
    <!-- Body -->
    <div class="p-6 space-y-6 {{ $bodyClass }}">
        {{ $slot }}
    </div>
</div>
