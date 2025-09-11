@props(['sidebarContext' => null, 'activeSection' => null, 'mobile' => false])

@php
$mobile = $mobile ?? false;
// The component will have these variables available from the FluxSidebar component class:
// $sidebarConfig - The configuration loaded by SidebarConfigProvider
// $selectedClient - The currently selected client (if any)
// $resolveContextualParams - Helper method from component
// $calculateBadgeData - Helper method from component
// $shouldDisplayItem - Helper method from component
@endphp

@if(!empty($sidebarConfig))
    <div class="{{ $mobile ? 'w-full' : 'w-64' }} h-full flex flex-col bg-white dark:bg-zinc-900 {{ !$mobile ? 'border-r border-zinc-200 dark:border-zinc-700' : '' }}">
        
        @if(isset($sidebarConfig['title']))
        <!-- Enhanced Sidebar Header -->
        <div class="flex-shrink-0 px-6 py-6 border-b border-zinc-200 dark:border-zinc-700 bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-zinc-800 dark:to-zinc-900">
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
                    @if($selectedClient)
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

        @if(isset($sidebarConfig['search']) && $sidebarConfig['search'] && config('sidebar.features.search', true))
        <!-- Quick Search -->
        <div class="flex-shrink-0 p-6 border-b border-zinc-100 dark:border-zinc-800">
            <div class="relative">
                <flux:input 
                    type="search" 
                    placeholder="Search navigation..."
                    class="w-full text-sm"
                    x-data="{ searchTerm: '' }"
                    x-model="searchTerm"
                    x-on:input="filterNavItems($event.target.value)"
                />
                <flux:icon name="magnifying-glass" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400" />
            </div>
        </div>
        @endif

        <!-- Navigation Content -->
        <div class="flex-1 overflow-y-auto {{ $mobile ? 'pb-4' : '' }}" x-data="sidebarNavigation()">
            <flux:navlist class="p-2 space-y-1 {{ $mobile ? 'space-y-2' : '' }}">
                @foreach($sidebarConfig['sections'] ?? [] as $sectionIndex => $section)
                    
                    @if($section['type'] === 'primary')
                        <!-- Primary navigation items (no grouping) -->
                        @foreach($section['items'] as $item)
                            @php
                                $isActive = $activeSection === $item['key'];
                                $routeParams = $resolveContextualParams($item['params'] ?? [], $selectedClient);
                                $badgeData = config('sidebar.features.badges', true) ? $calculateBadgeData($item, $selectedClient) : ['count' => 0];
                                $shouldDisplay = $shouldDisplayItem($item, $selectedClient);
                            @endphp
                            
                            @if($shouldDisplay)
                                @php
                                    $iconAttr = (config('sidebar.features.icons', true) && isset($item['icon'])) ? $item['icon'] : null;
                                    $badgeAttr = ($badgeData['count'] > 0) ? ($badgeData['count'] > 99 ? '99+' : $badgeData['count']) : null;
                                @endphp
                                <flux:navlist.item 
                                    href="{{ route($item['route'], $routeParams) }}" 
                                    :current="$isActive"
                                    :icon="$iconAttr"
                                    :badge="$badgeAttr"
                                    class="font-medium {{ $mobile ? 'py-6 text-base' : '' }}"
                                >
                                    {{ $item['name'] }}
                                    @if(config('sidebar.features.descriptions', false) && isset($item['description']))
                                        <span class="block text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                            {{ $item['description'] }}
                                        </span>
                                    @endif
                                </flux:navlist.item>
                            @endif
                        @endforeach
                        
                    @elseif($section['type'] === 'section')
                        <!-- Section groups -->
                        @php
                            $isExpandable = $section['expandable'] ?? true;
                            $isDefaultExpanded = $section['default_expanded'] ?? false;
                            $isPriority = $section['priority'] ?? false;
                            
                            // Check if any item in this section is active
                            $sectionHasActiveItem = false;
                            foreach($section['items'] as $item) {
                                if($activeSection === $item['key']) {
                                    $sectionHasActiveItem = true;
                                    break;
                                }
                            }
                            
                            // Expand if section has active item, otherwise use default
                            $shouldExpand = $sectionHasActiveItem || $isDefaultExpanded;
                        @endphp
                        
                        <flux:navlist.group 
                            :heading="$section['title']" 
                            :expandable="$isExpandable"
                            :expanded="$shouldExpand"
                            class="{{ $isPriority ? 'priority-section' : '' }}"
                        >
                            @foreach($section['items'] as $item)
                                @php
                                    $isActive = $activeSection === $item['key'];
                                    $routeParams = $resolveContextualParams($item['params'] ?? [], $selectedClient);
                                    $badgeData = config('sidebar.features.badges', true) ? $calculateBadgeData($item, $selectedClient) : ['count' => 0];
                                    $shouldDisplay = $shouldDisplayItem($item, $selectedClient);
                                @endphp
                                
                                @if($shouldDisplay)
                                    @php
                                        $iconAttr = (config('sidebar.features.icons', true) && isset($item['icon'])) ? $item['icon'] : null;
                                        $badgeAttr = ($badgeData['count'] > 0) ? ($badgeData['count'] > 99 ? '99+' : $badgeData['count']) : null;
                                    @endphp
                                    <flux:navlist.item 
                                        href="{{ route($item['route'], $routeParams) }}" 
                                        :current="$isActive"
                                        :icon="$iconAttr"
                                        :badge="$badgeAttr"
                                        class="{{ $mobile ? 'py-2 text-base min-h-[44px]' : '' }}"
                                    >
                                        {{ $item['name'] }}
                                        @if(config('sidebar.features.descriptions', false) && isset($item['description']))
                                            <span class="block text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                                {{ $item['description'] }}
                                            </span>
                                        @endif
                                    </flux:navlist.item>
                                @endif
                            @endforeach
                        </flux:navlist.group>
                    
                    @elseif($section['type'] === 'divider')
                        <!-- Simple divider -->
                        <flux:separator variant="subtle" class="my-3" />
                        
                    @elseif($section['type'] === 'custom')
                        <!-- Custom section content -->
                        <div class="px-3 py-2">
                            {!! $section['content'] ?? '' !!}
                        </div>
                    @endif
                    
                    @if(!$loop->last && $section['type'] === 'primary')
                        <flux:separator variant="subtle" class="my-3" />
                    @endif
                @endforeach
            </flux:navlist>
        </div>

        @if(config('sidebar.features.footer', true) && (isset($sidebarConfig['footer']) || $sidebarContext))
        <!-- Enhanced Footer -->
        <div class="flex-shrink-0 border-t border-zinc-200 dark:border-zinc-700 p-6">
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

    @push('styles')
    <style>
        .priority-section {
            position: sticky;
            top: 0;
            z-index: 10;
            background: linear-gradient(to right, rgb(254 242 242), rgb(255 247 247));
            border-left: 4px solid rgb(239 68 68);
            border-radius: 0 0.375rem 0.375rem 0;
            margin: 0.25rem 0;
            padding: 0.5rem;
        }
        
        .dark .priority-section {
            background: linear-gradient(to right, rgb(127 29 29 / 0.2), rgb(127 29 29 / 0.1));
            border-left-color: rgb(239 68 68);
        }

        .nav-item-hidden {
            display: none !important;
        }

        .nav-group-hidden {
            display: none !important;
        }

        .search-highlight {
            background: rgb(254 240 138);
            color: rgb(146 64 14);
            padding: 0.125rem 0.25rem;
            border-radius: 0.25rem;
            font-weight: 600;
        }

        .dark .search-highlight {
            background: rgb(146 64 14);
            color: rgb(254 240 138);
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        function sidebarNavigation() {
            return {
                searchTerm: '',
                
                init() {
                    // Initialize search functionality
                    this.setupGlobalSearch();
                    
                    // Restore collapsed/expanded state if enabled
                    @if(config('sidebar.features.remember_state', true))
                    this.restoreExpandedState();
                    @endif
                },

                setupGlobalSearch() {
                    // Global search function accessible from search input
                    window.filterNavItems = (searchTerm) => {
                        this.searchTerm = searchTerm.toLowerCase();
                        this.filterItems();
                    };
                },

                filterItems() {
                    const navItems = this.$el.querySelectorAll('[flux\\:navlist\\.item]');
                    const navGroups = this.$el.querySelectorAll('[flux\\:navlist\\.group]');
                    
                    if (!this.searchTerm) {
                        // Show all items when search is empty
                        navItems.forEach(item => item.classList.remove('nav-item-hidden'));
                        navGroups.forEach(group => group.classList.remove('nav-group-hidden'));
                        this.clearHighlights();
                        return;
                    }

                    navItems.forEach(item => {
                        const text = item.textContent.toLowerCase();
                        if (text.includes(this.searchTerm)) {
                            item.classList.remove('nav-item-hidden');
                            // Highlight matching text
                            this.highlightText(item, this.searchTerm);
                            // Ensure parent group is visible
                            const parentGroup = item.closest('[flux\\:navlist\\.group]');
                            if (parentGroup) {
                                parentGroup.classList.remove('nav-group-hidden');
                            }
                        } else {
                            item.classList.add('nav-item-hidden');
                        }
                    });

                    // Hide empty groups
                    navGroups.forEach(group => {
                        const visibleItems = group.querySelectorAll('[flux\\:navlist\\.item]:not(.nav-item-hidden)');
                        if (visibleItems.length === 0) {
                            group.classList.add('nav-group-hidden');
                        }
                    });
                },

                highlightText(element, searchTerm) {
                    const textElement = element.querySelector('span:last-child') || element;
                    if (!textElement) return;
                    
                    const originalText = textElement.textContent;
                    const regex = new RegExp(`(${searchTerm})`, 'gi');
                    const highlightedText = originalText.replace(regex, '<span class="search-highlight">$1</span>');
                    
                    if (highlightedText !== originalText) {
                        textElement.innerHTML = highlightedText;
                    }
                },
                
                clearHighlights() {
                    document.querySelectorAll('.search-highlight').forEach(el => {
                        const parent = el.parentNode;
                        parent.textContent = parent.textContent;
                    });
                },
                
                restoreExpandedState() {
                    // Restore expanded/collapsed state from localStorage
                    const state = localStorage.getItem('sidebar-expanded-state');
                    if (state) {
                        try {
                            const expandedSections = JSON.parse(state);
                            // Apply saved state to sections
                        } catch (e) {
                            console.error('Failed to restore sidebar state:', e);
                        }
                    }
                }
            };
        }
    </script>
    @endpush
@endif