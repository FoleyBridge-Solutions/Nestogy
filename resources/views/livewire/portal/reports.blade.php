<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <flux:heading size="2xl">Reports & Analytics</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400 mt-1">
                Insights about your services and support
            </flux:text>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            {{-- Date Range Selector --}}
            <flux:select wire:model.live="dateRange" class="w-48">
                <option value="7days">Last 7 Days</option>
                <option value="30days">Last 30 Days</option>
                <option value="90days">Last 90 Days</option>
                <option value="6months" selected>Last 6 Months</option>
                <option value="12months">Last 12 Months</option>
            </flux:select>

            {{-- Export Button (Future) --}}
            <flux:button variant="ghost" icon="arrow-down-tray" disabled>
                Export
            </flux:button>
        </div>
    </div>

    @php
        $availableTabs = $this->getAvailableTabs();
    @endphp

    @if(empty($availableTabs))
        {{-- Empty State: No Permissions --}}
        @include('livewire.portal.reports.empty-state')
    @else
        {{-- Tab Navigation --}}
        <div class="border-b border-zinc-200 dark:border-zinc-700">
            <nav class="-mb-px flex space-x-8 overflow-x-auto" aria-label="Tabs">
                @foreach($availableTabs as $tab)
                    <button
                        wire:click="setTab('{{ $tab['id'] }}')"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors
                            {{ $activeTab === $tab['id'] 
                                ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' 
                                : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' 
                            }}"
                    >
                        <div class="flex items-center gap-2">
                            <flux:icon name="{{ $tab['icon'] }}" class="w-5 h-5" />
                            {{ $tab['name'] }}
                        </div>
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Tab Content --}}
        <div class="mt-6">
            @if($activeTab === 'support')
                @include('livewire.portal.reports.support')
            @endif

            @if($activeTab === 'financial')
                @include('livewire.portal.reports.financial')
            @endif

            @if($activeTab === 'assets')
                @include('livewire.portal.reports.assets')
            @endif

            @if($activeTab === 'projects')
                @include('livewire.portal.reports.projects')
            @endif

            @if($activeTab === 'contracts')
                @include('livewire.portal.reports.contracts')
            @endif

            @if($activeTab === 'quotes')
                @include('livewire.portal.reports.quotes')
            @endif
        </div>
    @endif
</div>
