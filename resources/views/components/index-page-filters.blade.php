@props(['searchModel' => 'search', 'searchPlaceholder' => 'Search...'])

<flux:card {{ $attributes->merge(['class' => 'mb-6']) }}>
    <div class="p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <flux:input 
                    wire:model.live.debounce.300ms="{{ $searchModel }}" 
                    placeholder="{{ $searchPlaceholder }}"
                    icon="magnifying-glass"
                />
            </div>
            
            {{ $slot }}
        </div>
        
        @if(isset($additionalRow))
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{ $additionalRow }}
            </div>
        @endif
    </div>
</flux:card>
