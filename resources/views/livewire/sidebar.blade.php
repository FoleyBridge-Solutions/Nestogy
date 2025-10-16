{{-- Pure Livewire Sidebar Component --}}

<div class="{{ $this->mobile ? 'h-screen' : 'h-full' }} relative z-50">
@if(!empty($sidebarConfig))
    <div class="{{ $this->mobile ? 'w-full h-screen' : 'w-64 h-full' }} flex flex-col bg-white dark:bg-zinc-900 {{ !$this->mobile ? 'border-r border-zinc-200 dark:border-zinc-700' : '' }}">
        
        @if(isset($sidebarConfig['title']) && !$this->mobile)
        <!-- Sidebar Header -->
        <div class="flex-shrink-0 px-3 py-3 border-b border-zinc-200 dark:border-zinc-700 bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-zinc-800 dark:to-zinc-900">
            <div class="flex items-center space-x-3">
                @if(isset($sidebarConfig['icon']))
                <div class="flex-shrink-0">
                    <flux:icon name="{{ $sidebarConfig['icon'] }}" class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                </div>
                @endif
                <div class="flex-1 min-w-0">
                    <flux:heading size="sm" class="text-zinc-900 dark:text-zinc-100 truncate">
                        {{ $sidebarConfig['title'] }}
                    </flux:heading>
                    @if(isset($sidebarConfig['subtitle']))
                        <flux:text size="xs" class="text-zinc-600 dark:text-zinc-400 truncate">
                            {{ $sidebarConfig['subtitle'] }}
                        </flux:text>
                    @elseif($selectedClient)
                        <flux:text size="xs" class="text-zinc-600 dark:text-zinc-400 truncate">
                            {{ $selectedClient->display_name ?? $selectedClient->name }}
                        </flux:text>
                    @endif
                </div>
                <div class="flex-shrink-0">
                    <div class="w-2 h-2 bg-gradient-to-r from-indigo-500 to-blue-500 rounded-full animate-pulse"></div>
                </div>
            </div>
        </div>
        @endif

        <!-- Expand/Collapse All Controls -->
        <div class="flex-shrink-0 px-4 py-2 border-b border-zinc-100 dark:border-zinc-800">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Navigation</span>
                <div class="flex items-center space-x-1">
                    <button
                        type="button"
                        wire:click="expandAll"
                        class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors cursor-pointer"
                        title="Expand all sections"
                    >
                        <flux:icon name="chevron-double-down" class="w-4 h-4 pointer-events-none" />
                    </button>
                    <button
                        type="button"
                        wire:click="collapseAll"
                        class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors cursor-pointer"
                        title="Collapse all sections"
                    >
                        <flux:icon name="chevron-double-up" class="w-4 h-4 pointer-events-none" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Navigation Content -->
        <div class="flex-1 overflow-y-auto {{ $this->mobile ? 'pb-4' : '' }} min-h-0">
            <nav class="p-2 space-y-1 {{ $this->mobile ? 'space-y-2' : '' }}">
                @foreach($sidebarConfig['sections'] ?? [] as $sectionIndex => $section)
                    
                    @if($section['type'] === 'primary')
                        <!-- Primary navigation items (no grouping) -->
                        @foreach($section['items'] as $item)
                            @php
                                $isActive = $this->currentRoute === $item['route'] || str_starts_with($this->currentRoute, $item['route'] . '.');
                                $routeParams = $this->resolveContextualParams($item['params'] ?? [], $selectedClient);
                                $badgeData = config('sidebar.features.badges', true) ? $this->calculateBadgeData($item, $selectedClient) : ['count' => 0];
                                $shouldDisplay = $this->shouldDisplayItem($item, $selectedClient);
                            @endphp
                            
                            @if($shouldDisplay)
                                @php
                                    $iconAttr = (config('sidebar.features.icons', true) && isset($item['icon'])) ? $item['icon'] : null;
                                    $badgeAttr = ($badgeData['count'] > 0) ? ($badgeData['count'] > 99 ? '99+' : $badgeData['count']) : null;
                                    $itemHref = isset($item['url']) ? $item['url'] : (count($routeParams) > 0 ? route($item['route'], $routeParams) : route($item['route']));
                                @endphp
                                <a
                                    href="{{ $itemHref }}"
                                    class="group flex items-center px-3 py-3 text-sm font-bold rounded-lg {{ $isActive ? 'bg-indigo-50 border-r-4 border-indigo-500 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-200' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100' }} {{ $this->mobile ? 'py-6 text-base' : '' }}"
                                >
                                    @if($iconAttr)
                                        <flux:icon name="{{ $iconAttr }}" class="mr-3 w-6 h-6 {{ $isActive ? 'text-indigo-500 dark:text-indigo-300' : 'text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-400' }}" />
                                    @endif
                                    <span class="flex-1">{{ $item['name'] }}</span>
                                    @if($badgeAttr)
                                        <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                            {{ $badgeAttr }}
                                        </span>
                                    @endif
                                </a>
                            @endif
                        @endforeach
                        
                    @elseif($section['type'] === 'section')
                        <!-- Section groups -->
                        @php
                            $sectionId = 'section_' . $sectionIndex;
                            $isExpanded = $this->expandedSections[$sectionId] ?? false;
                        @endphp
                        
                        <div class="sidebar-section" data-section-id="{{ $sectionId }}">
                            <!-- Section Header -->
                            <button 
                                type="button"
                                wire:click="toggleSection('{{ $sectionId }}')"
                                class="w-full flex items-center justify-between px-3 py-2 text-left text-sm font-medium text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg cursor-pointer {{ $isExpanded ? 'bg-gray-50 dark:bg-gray-800' : '' }}"
                            >
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 pointer-events-none">
                                    {{ $section['title'] }}
                                </span>
                                <flux:icon 
                                    name="chevron-right" 
                                    class="w-4 h-4 transition-transform duration-200 pointer-events-none {{ $isExpanded ? 'rotate-90' : '' }}"
                                />
                            </button>
                            
                            <!-- Section Content -->
                            @if($isExpanded)
                            <div class="mt-1 space-y-1">
                            @foreach($section['items'] as $item)
                                @php
                                    $isActive = $this->currentRoute === $item['route'] || str_starts_with($this->currentRoute, $item['route'] . '.');
                                    $routeParams = $this->resolveContextualParams($item['params'] ?? [], $selectedClient);
                                    $badgeData = config('sidebar.features.badges', true) ? $this->calculateBadgeData($item, $selectedClient) : ['count' => 0];
                                    $shouldDisplay = $this->shouldDisplayItem($item, $selectedClient);
                                @endphp
                                
                                @if($shouldDisplay)
                                    @php
                                        $iconAttr = (config('sidebar.features.icons', true) && isset($item['icon'])) ? $item['icon'] : null;
                                        $badgeAttr = ($badgeData['count'] > 0) ? ($badgeData['count'] > 99 ? '99+' : $badgeData['count']) : null;
                                    @endphp
                                    <a
                                        href="{{ isset($item['url']) ? $item['url'] : route($item['route'], $routeParams) }}"
                                        class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ $isActive ? 'bg-indigo-50 border-r-4 border-indigo-500 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-200' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100' }} {{ $this->mobile ? 'py-2 text-base min-h-[44px]' : '' }}"
                                    >
                                        @if($iconAttr)
                                            <flux:icon name="{{ $iconAttr }}" class="mr-3 w-5 h-5 {{ $isActive ? 'text-indigo-500 dark:text-indigo-300' : 'text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-400' }}" />
                                        @endif
                                        <span class="flex-1">{{ $item['name'] }}</span>
                                        @if($badgeAttr)
                                            <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                                {{ $badgeAttr }}
                                            </span>
                                        @endif
                                    </a>
                                @endif
                            @endforeach
                            </div>
                            @endif
                        </div>
                    
                    @elseif($section['type'] === 'divider')
                        <!-- Simple divider -->
                        <div class="border-t border-gray-200 dark:border-gray-700 my-3"></div>
                    @endif
                    
                    @if(!$loop->last && $section['type'] === 'primary')
                        <div class="border-t border-gray-200 dark:border-gray-700 my-3"></div>
                    @endif
                @endforeach
            </nav>
        </div>

        @if(config('sidebar.features.footer', true) && (isset($sidebarConfig['footer']) || $sidebarContext))
        <!-- Footer -->
        <div class="mt-auto flex-shrink-0 border-t border-zinc-200 dark:border-zinc-700 p-6 bg-white dark:bg-zinc-900">
            @if(isset($sidebarConfig['footer']))
                {!! $sidebarConfig['footer'] !!}
            @else
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-gradient-to-r from-green-400 to-blue-500 rounded-full"></div>
                        <flux:text size="xs" class="text-zinc-600 dark:text-zinc-400 font-medium">
                            {{ ucfirst($sidebarContext) }} Module
                        </flux:text>
                    </div>
                    <flux:text size="xs" class="text-zinc-500 dark:text-zinc-500">
                        {{ now()->format('H:i') }}
                    </flux:text>
                </div>
            @endif
        </div>
        @endif
    </div>

@endif

@push('styles')
<style>
    .sidebar-section {
        margin-bottom: 0.5rem;
    }

    .sidebar-section:last-child {
        margin-bottom: 0;
    }

    .sidebar-section button:focus {
        outline: 2px solid rgb(99 102 241);
        outline-offset: -2px;
    }
</style>
@endpush
</div>
