@props([
    'title' => '',
    'description' => '',
    'icon' => null
])

<flux:card {{ $attributes }}>
    @if($title)
        <div class="mb-6">
            <div class="flex items-center gap-3">
                @if($icon)
                    <flux:icon name="{{ $icon }}" class="w-5 h-5" />
                @endif
                <div>
                    <flux:heading size="md">{{ $title }}</flux:heading>
                    @if($description)
                        <flux:text size="sm" class="mt-1">{{ $description }}</flux:text>
                    @endif
                </div>
            </div>
        </div>
    @endif
    
    {{ $slot }}
</flux:card>