@props(['selected' => [], 'itemLabel' => 'item'])

@if(count($selected) > 0)
    <div {{ $attributes->merge(['class' => 'mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg']) }}>
        <div class="flex items-center justify-between">
            <flux:text>
                <span class="font-semibold">{{ count($selected) }}</span> {{ Str::plural($itemLabel, count($selected)) }} selected
            </flux:text>
            
            <div class="flex items-center gap-2">
                {{ $slot }}
            </div>
        </div>
    </div>
@endif
