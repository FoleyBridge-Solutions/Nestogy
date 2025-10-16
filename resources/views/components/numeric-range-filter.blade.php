@props([
    'wireModel',
    'label',
    'step' => '0.01',
    'dataMin' => 0,
    'dataMax' => 0,
    'prefix' => null,
])

@php
    $filterKey = str_replace('.', '_', $wireModel);
@endphp

<flux:dropdown {{ $attributes }}>
    <button 
        type="button"
        class="appearance-none w-full ps-3 pe-10 h-8 py-1.5 text-sm leading-[1.125rem] rounded-md shadow-xs border bg-white dark:bg-white/10 text-zinc-700 dark:text-zinc-300 border border-zinc-200 border-b-zinc-300/80 dark:border-white/10 relative text-left"
        x-data
    >
        <span class="text-zinc-400 dark:text-zinc-400" x-show="!$wire.{{ $wireModel }}.min && !$wire.{{ $wireModel }}.max">
            {{ $label }}
        </span>
        <span x-show="$wire.{{ $wireModel }}.min || $wire.{{ $wireModel }}.max">
            <span x-text="
                '{{ $prefix }}' + 
                ($wire.{{ $wireModel }}.min ? Number($wire.{{ $wireModel }}.min).toLocaleString() : '{{ number_format($dataMin) }}') + 
                ' - ' + 
                '{{ $prefix }}' + 
                ($wire.{{ $wireModel }}.max ? Number($wire.{{ $wireModel }}.max).toLocaleString() : '{{ number_format($dataMax) }}')
            "></span>
        </span>
        <flux:icon.chevron-down variant="micro" class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
    </button>

    <flux:popover class="min-w-[22rem] flex flex-col gap-4">
        <div class="grid grid-cols-2 gap-3">
            <flux:input 
                wire:model.live.debounce.500ms="{{ $wireModel }}.min"
                label="Minimum"
                type="number"
                step="{{ $step }}"
                placeholder="No min"
            />
            
            <flux:input 
                wire:model.live.debounce.500ms="{{ $wireModel }}.max"
                label="Maximum"
                type="number"
                step="{{ $step }}"
                placeholder="No max"
            />
        </div>

        <div class="space-y-3" x-data>
            <div>
                <label class="text-sm text-zinc-600 dark:text-zinc-400 mb-1 block">Minimum</label>
                <input 
                    type="range" 
                    wire:model.live="{{ $wireModel }}.min"
                    min="{{ $dataMin }}"
                    max="{{ $dataMax }}"
                    step="{{ $step }}"
                    value="{{ $dataMin }}"
                    class="w-full h-2 bg-zinc-200 dark:bg-zinc-700 rounded-lg appearance-none cursor-pointer"
                />
                <div class="flex justify-between text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                    <span>{{ $prefix }}{{ number_format($dataMin, 2) }}</span>
                    <span class="font-medium" x-text="'{{ $prefix }}' + ($wire.{{ $wireModel }}.min || {{ $dataMin }})"></span>
                </div>
            </div>
            
            <div>
                <label class="text-sm text-zinc-600 dark:text-zinc-400 mb-1 block">Maximum</label>
                <input 
                    type="range" 
                    wire:model.live="{{ $wireModel }}.max"
                    min="{{ $dataMin }}"
                    max="{{ $dataMax }}"
                    step="{{ $step }}"
                    value="{{ $dataMax }}"
                    class="w-full h-2 bg-zinc-200 dark:bg-zinc-700 rounded-lg appearance-none cursor-pointer"
                />
                <div class="flex justify-between text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                    <span class="font-medium" x-text="'{{ $prefix }}' + ($wire.{{ $wireModel }}.max || {{ $dataMax }})"></span>
                    <span>{{ $prefix }}{{ number_format($dataMax, 2) }}</span>
                </div>
            </div>
        </div>

        <flux:separator variant="subtle" />

        <flux:button
            variant="subtle"
            size="sm"
            class="justify-start -m-2 !px-2"
            wire:click="$set('{{ $wireModel }}', {min: '', max: ''})"
        >
            Clear filter
        </flux:button>
    </flux:popover>
</flux:dropdown>
