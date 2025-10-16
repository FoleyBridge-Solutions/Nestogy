@props(['item'])

<flux:card 
    class="relative group hover:shadow-xl hover:-translate-y-1 transition-all duration-300 ease-out cursor-pointer overflow-hidden"
>
    {{-- Bulk Select Checkbox (if applicable) --}}
    @if(method_exists($this, 'bulkDelete') || method_exists($this, 'getBulkActions'))
        <div class="absolute top-3 left-3 z-10" @click.stop>
            <flux:checkbox 
                wire:model.live="selected" 
                :value="$item->id"
            />
        </div>
    @endif

    <div class="p-5">
        {{-- Header with Title and Actions --}}
        <div class="flex items-start justify-between mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex-1">
                <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100">
                    @if(isset($item->prefix) && isset($item->number))
                        {{ $item->prefix }}{{ $item->number }}
                    @elseif(isset($item->name))
                        {{ $item->name }}
                    @elseif(isset($item->title))
                        {{ $item->title }}
                    @else
                        #{{ $item->id }}
                    @endif
                </h3>
            </div>
            
            {{-- Row Actions --}}
            @if(method_exists($this, 'getRowActions'))
                <div role="presentation" @click.stop>
                    <flux:dropdown>
                        <flux:button variant="ghost" size="sm" class="opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            <flux:icon.ellipsis-vertical class="size-4" />
                        </flux:button>
                        <flux:menu>
                            @foreach($this->getRowActions($item) as $action)
                                @if(isset($action['href']))
                                    <flux:menu.item href="{{ $action['href'] }}">
                                        @if(isset($action['icon']))
                                            <flux:icon.{{ $action['icon'] }} class="size-4" />
                                        @endif
                                        {{ $action['label'] ?? $action['title'] ?? 'Action' }}
                                    </flux:menu.item>
                                 @elseif(isset($action['wire:click']))
                                     @php
                                         $confirm = $action['wire:confirm'] ?? null;
                                     @endphp
                                     @if($confirm)
                                         <flux:menu.item 
                                             wire:click="{{ $action['wire:click'] }}"
                                             wire:confirm="{{ $confirm }}"
                                             variant="{{ $action['variant'] ?? 'ghost' }}"
                                         >
                                             @if(isset($action['icon']))
                                                 <flux:icon.{{ $action['icon'] }} class="size-4" />
                                             @endif
                                             {{ $action['label'] ?? $action['title'] ?? 'Action' }}
                                         </flux:menu.item>
                                     @else
                                         <flux:menu.item 
                                             wire:click="{{ $action['wire:click'] }}"
                                             variant="{{ $action['variant'] ?? 'ghost' }}"
                                         >
                                             @if(isset($action['icon']))
                                                 <flux:icon.{{ $action['icon'] }} class="size-4" />
                                             @endif
                                             {{ $action['label'] ?? $action['title'] ?? 'Action' }}
                                         </flux:menu.item>
                                      @endif
                             @endif
                             @endforeach
                        </flux:menu>
                    </flux:dropdown>
                </div>
            @endif
        </div>

        {{-- Dynamic Content from Columns --}}
        <div class="grid grid-cols-2 gap-4 text-sm">
            @foreach($columns as $key => $column)
                @if(!($column['hidden_in_card'] ?? false))
                    @php
                        $value = data_get($item, $key);
                        $colType = $column['type'] ?? 'text';

                        if (is_array($value)) {
                            $value = implode(', ', array_filter($value, 'is_scalar')) ?: null;
                        }
                    @endphp

                    @if(!empty($value))
                        <div class="flex flex-col space-y-1">
                            <span class="text-gray-500 dark:text-gray-400 text-xs font-semibold uppercase tracking-wide">
                                {{ $column['label'] ?? $key }}
                            </span>
                            <div>
                                @if($colType === 'currency')
                                    <span class="text-gray-900 dark:text-gray-100 font-semibold text-base">${{ number_format($value ?? 0, 2) }}</span>
                                @elseif($colType === 'date')
                                    <span class="text-gray-700 dark:text-gray-300 font-medium">{{ \Carbon\Carbon::parse($value)->format('M d, Y') }}</span>
                                @elseif($colType === 'badge')
                                    @php
                                        $badgeColor = isset($column['badgeColor']) ? $column['badgeColor']($item) : 'zinc';
                                    @endphp
                                    <flux:badge
                                        size="sm"
                                        :color="$badgeColor"
                                        inset="top bottom"
                                    >
                                        {{ $value ?? '-' }}
                                    </flux:badge>
                                @elseif(method_exists($this, 'render' . \Illuminate\Support\Str::studly($key)))
                                    {!! $this->{'render' . \Illuminate\Support\Str::studly($key)}($item) !!}
                                @elseif(isset($column['component']))
                                    @include($column['component'], ['item' => $item])
                                @else
                                    <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $value ?? '-' }}</span>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif
            @endforeach
        </div>
    </div>
</flux:card>
