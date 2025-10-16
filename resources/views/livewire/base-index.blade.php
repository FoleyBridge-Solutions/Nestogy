<div x-data="{
    init() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'v' && !e.metaKey && !e.ctrlKey && !e.altKey && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
                @this.toggleView(@this.viewMode === 'cards' ? 'table' : 'cards');
            }
        });
    }
}">
    {{-- Stats Cards --}}
    @if(!empty($stats))
        <x-index-page-stats :stats="$stats" />
    @endif

    {{-- Header with View Toggle --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex-1"></div>
        <div class="flex items-center gap-3">
            <div class="flex bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
                <button
                    wire:click="toggleView('table')"
                    class="px-3 py-1.5 rounded {{ $viewMode === 'table' ? 'bg-white dark:bg-gray-700 shadow text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400' }} transition-all duration-200"
                    title="Table view (Press V)"
                >
                    <flux:icon.bars-3 class="size-4" />
                </button>
                <button
                    wire:click="toggleView('cards')"
                    class="px-3 py-1.5 rounded {{ $viewMode === 'cards' ? 'bg-white dark:bg-gray-700 shadow text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400' }} transition-all duration-200"
                    title="Card view (Press V)"
                >
                    <flux:icon.squares-2x2 class="size-4" />
                </button>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <x-index-page-filters search-placeholder="Search...">
        @foreach($columns as $key => $column)
            @if($column['filterable'] ?? false)
                @php
                    $filterKey = str_replace('.', '_', $key);
                    $type = $column['type'] ?? 'text';
                @endphp
                
                @if($type === 'select' && isset($column['options']))
                    <flux:pillbox 
                        wire:model.live="columnFilters.{{ $filterKey }}" 
                        placeholder="{{ $column['label'] }}"
                        size="sm"
                        searchable
                        multiple
                    >
                        @foreach($column['options'] as $value => $label)
                            <flux:pillbox.option value="{{ $value }}">{{ $label }}</flux:pillbox.option>
                        @endforeach
                    </flux:pillbox>
                @elseif($type === 'date')
                    <flux:date-picker 
                        wire:model.live="columnFilters.{{ $filterKey }}"
                        mode="range"
                        with-presets
                        presets="today yesterday thisWeek lastWeek last7Days thisMonth lastMonth thisYear yearToDate"
                        placeholder="{{ $column['label'] }}"
                        clearable
                    >
                        <x-slot name="trigger">
                            <flux:date-picker.button 
                                placeholder="{{ $column['label'] }}"
                                size="sm"
                                clearable
                            />
                        </x-slot>
                    </flux:date-picker>
                @elseif(($column['filter_type'] ?? null) === 'numeric_range')
                    @php
                        $stats = $this->getColumnStats($key);
                    @endphp
                    <x-numeric-range-filter 
                        wire-model="columnFilters.{{ $filterKey }}"
                        :label="$column['label']"
                        :step="$column['step'] ?? '0.01'"
                        :data-min="$stats['min']"
                        :data-max="$stats['max']"
                        :prefix="$column['prefix'] ?? null"
                    />
                @endif
            @endif
        @endforeach
    </x-index-page-filters>

    {{-- Bulk Actions --}}
    @if(method_exists($this, 'bulkDelete') || method_exists($this, 'getBulkActions'))
        <x-index-page-bulk-actions :selected="$selected" item-label="item">
            @if(method_exists($this, 'getBulkActions'))
                @foreach($this->getBulkActions() as $action)
                    @if($action['confirm'] ?? false)
                        <flux:button 
                            wire:click="{{ $action['method'] }}" 
                            wire:confirm="{{ $action['confirm'] }}"
                            size="sm" 
                            variant="{{ $action['variant'] ?? 'ghost' }}"
                        >
                            {{ $action['label'] }}
                        </flux:button>
                    @else
                        <flux:button 
                            wire:click="{{ $action['method'] }}" 
                            size="sm" 
                            variant="{{ $action['variant'] ?? 'ghost' }}"
                        >
                            {{ $action['label'] }}
                        </flux:button>
                    @endif
                @endforeach
            @else
                <flux:button wire:click="bulkDelete" size="sm" variant="danger">
                    Delete
                </flux:button>
            @endif
        </x-index-page-bulk-actions>
    @endif

    {{-- Content View --}}
    @if($viewMode === 'cards')
        {{-- Cards View --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($items as $item)
                @include('livewire.base-index-card', ['item' => $item])
            @empty
                <div class="col-span-full">
                    <flux:card>
                        <div class="text-center py-12">
                            <flux:icon.{{ $emptyState['icon'] ?? 'document-text' }} class="size-12 mx-auto mb-4 text-gray-300" />
                            <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ $emptyState['title'] }}</h3>
                            <p class="text-gray-500 mb-4">{{ $emptyState['message'] }}</p>
                            @if($emptyState['action'])
                                <flux:button href="{{ is_array($emptyState['action']) ? $emptyState['action']['href'] : $emptyState['action'] }}" variant="primary">
                                    {{ $emptyState['actionLabel'] ?? 'Create New' }}
                                </flux:button>
                            @endif
                        </div>
                    </flux:card>
                </div>
            @endforelse
        </div>
    @else
        {{-- Table View --}}
        <x-index-page-table
            :items="$items"
            :empty-icon="$emptyState['icon']"
            :empty-title="$emptyState['title']"
            :empty-message="$emptyState['message']"
            :empty-action="is_array($emptyState['action'] ?? null) ? $emptyState['action']['href'] : $emptyState['action']"
            :empty-action-label="$emptyState['actionLabel']"
            :has-filters="$this->hasActiveFilters()"
            filter-clear-action="clearAllFilters"
        >
        <flux:table.columns>
            {{-- Bulk Select Column --}}
            @if(method_exists($this, 'bulkDelete') || method_exists($this, 'getBulkActions'))
                <flux:table.column class="w-12">
                    <flux:checkbox wire:model.live="selectAll" />
                </flux:table.column>
            @endif

            {{-- Dynamic Columns --}}
            @foreach($columns as $key => $column)
                @php
                    $isSortable = $column['sortable'] ?? false;
                    $hasClass = isset($column['class']);
                    $colClass = $column['class'] ?? '';
                @endphp
                
                @if($isSortable && $hasClass)
                    <flux:table.column 
                        sortable 
                        :sorted="$sortField === '{{ $key }}'" 
                        :direction="$sortDirection" 
                        wire:click="sort('{{ $key }}')"
                        class="{{ $colClass }}"
                    >
                        {{ $column['label'] }}
                    </flux:table.column>
                @elseif($isSortable)
                    <flux:table.column 
                        sortable 
                        :sorted="$sortField === '{{ $key }}'" 
                        :direction="$sortDirection" 
                        wire:click="sort('{{ $key }}')"
                    >
                        {{ $column['label'] }}
                    </flux:table.column>
                @elseif($hasClass)
                    <flux:table.column class="{{ $colClass }}">
                        {{ $column['label'] }}
                    </flux:table.column>
                @else
                    <flux:table.column>
                        {{ $column['label'] }}
                    </flux:table.column>
                @endif
            @endforeach

            {{-- Actions Column --}}
            @if(method_exists($this, 'getRowActions'))
                <flux:table.column>Actions</flux:table.column>
            @endif
        </flux:table.columns>

        <flux:table.rows>
            @foreach($items as $item)
                <flux:table.row :key="$item->id">
                    {{-- Bulk Select Cell --}}
                    @if(method_exists($this, 'bulkDelete') || method_exists($this, 'getBulkActions'))
                        <flux:table.cell>
                            <flux:checkbox wire:model.live="selected" :value="$item->id" />
                        </flux:table.cell>
                    @endif

                    {{-- Dynamic Cells --}}
                    @foreach($columns as $key => $column)
                        <flux:table.cell>
                            @if(isset($column['component']))
                                @include($column['component'], ['item' => $item])
                            @elseif(method_exists($this, 'render' . \Illuminate\Support\Str::studly($key)))
                                {!! $this->{'render' . \Illuminate\Support\Str::studly($key)}($item) !!}
                            @else
                                @php
                                    $value = data_get($item, $key);
                                    $colType = $column['type'] ?? 'text';

                                    // Convert arrays to readable string
                                    if (is_array($value)) {
                                        $value = implode(', ', array_filter($value, 'is_scalar')) ?: null;
                                    }
                                @endphp

                                @if($colType === 'currency')
                                    <flux:text variant="strong">${{ number_format($value ?? 0, 2) }}</flux:text>
                                @elseif($colType === 'date')
                                    {{ $value ? \Carbon\Carbon::parse($value)->format('M d, Y') : '-' }}
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
                                @else
                                    {{ $value ?? '-' }}
                                @endif
                            @endif
                        </flux:table.cell>
                    @endforeach

                    {{-- Actions Cell --}}
                    @if(method_exists($this, 'getRowActions'))
                        <flux:table.cell>
                            <div class="flex items-center gap-1">
                                @foreach($this->getRowActions($item) as $action)
                                    {{-- Link Button --}}
                                    @if(isset($action['href']))
                                        <flux:button 
                                            href="{{ $action['href'] }}"
                                            size="sm"
                                            variant="{{ $action['variant'] ?? 'ghost' }}"
                                            icon="{{ $action['icon'] }}"
                                            tooltip="{{ $action['label'] ?? $action['title'] ?? '' }}"
                                            inset="top bottom"
                                        />
                                    
                                    {{-- Livewire Action Button (with confirmation) --}}
                                    @elseif(isset($action['wire:click']) && isset($action['wire:confirm']))
                                        <flux:button 
                                            wire:click="{{ $action['wire:click'] }}"
                                            wire:confirm="{{ $action['wire:confirm'] }}"
                                            size="sm"
                                            variant="{{ $action['variant'] ?? 'ghost' }}"
                                            icon="{{ $action['icon'] }}"
                                            tooltip="{{ $action['label'] ?? $action['title'] ?? '' }}"
                                            inset="top bottom"
                                        />
                                    
                                    {{-- Livewire Action Button (no confirmation) --}}
                                    @elseif(isset($action['wire:click']))
                                        <flux:button 
                                            wire:click="{{ $action['wire:click'] }}"
                                            size="sm"
                                            variant="{{ $action['variant'] ?? 'ghost' }}"
                                            icon="{{ $action['icon'] }}"
                                            tooltip="{{ $action['label'] ?? $action['title'] ?? '' }}"
                                            inset="top bottom"
                                        />
                                    @endif
                                @endforeach
                            </div>
                        </flux:table.cell>
                    @endif
                </flux:table.row>
            @endforeach
         </flux:table.rows>
     </x-index-page-table>
    @endif
</div>
