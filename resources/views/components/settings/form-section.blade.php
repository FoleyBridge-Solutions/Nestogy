@props([
    'title' => '',
    'description' => '',
    'icon' => null
])

<div class="bg-white rounded-lg shadow-sm p-6">
    @if($title)
        <div class="flex items-center mb-6">
            @if($icon)
                <div class="mr-2">
                    {{ $icon }}
                </div>
            @endif
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ $title }}</h2>
                @if($description)
                    <p class="text-sm text-gray-600 mt-1">{{ $description }}</p>
                @endif
            </div>
        </div>
    @endif
    
    <div {{ $attributes->merge(['class' => '']) }}>
        {{ $slot }}
    </div>
</div>
