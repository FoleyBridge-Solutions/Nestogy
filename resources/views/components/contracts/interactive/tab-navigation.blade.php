@props([
    'tabs' => [],
    'activeTab' => 'overview',
    'contract' => null
])

@php
$defaultTabs = [
    'overview' => [
        'label' => 'Overview',
        'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'badge' => null,
        'description' => 'Contract details and summary'
    ],
    'schedules' => [
        'label' => 'Schedules',
        'icon' => 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h2a2 2 0 002-2z',
        'badge' => $contract ? $contract->schedules->count() : null,
        'description' => 'Infrastructure, pricing, and SLA schedules'
    ],
    'approvals' => [
        'label' => 'Approvals',
        'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'badge' => $contract ? $contract->approvals->where('status', 'pending')->count() : null,
        'description' => 'Approval workflow and status'
    ],
    'signatures' => [
        'label' => 'Signatures',
        'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
        'badge' => null,
        'description' => 'Digital signature tracking'
    ],
    'milestones' => [
        'label' => 'Milestones',
        'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        'badge' => $contract ? $contract->contractMilestones->where('status', 'pending')->count() : null,
        'description' => 'Project milestones and deliverables'
    ],
    'billing' => [
        'label' => 'Billing',
        'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1',
        'badge' => null,
        'description' => 'Billing history and usage metrics'
    ],
    'history' => [
        'label' => 'History',
        'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'badge' => null,
        'description' => 'Audit trail and activity log'
    ]
];

// Merge default tabs with custom tabs
$tabConfig = array_merge($defaultTabs, $tabs);

// Filter tabs based on contract data availability
$availableTabs = [];
foreach($tabConfig as $key => $tab) {
    $shouldShow = true;
    
    // Hide tabs based on contract data
    if ($contract) {
        switch($key) {
            case 'approvals':
                $shouldShow = $contract->approvals && $contract->approvals->count() > 0;
                break;
            case 'signatures':
                $shouldShow = $contract->signatures && $contract->signatures->count() > 0;
                break;
            case 'milestones':
                $shouldShow = $contract->contractMilestones && $contract->contractMilestones->count() > 0;
                break;
        }
    }
    
    if ($shouldShow) {
        $availableTabs[$key] = $tab;
    }
}
@endphp

<div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
    <!-- Desktop Navigation -->
    <div class="hidden lg:block">
        <div class="max-w-7xl mx-auto px-6 sm:px-6 lg:px-8">
            <nav class="flex space-x-8" aria-label="Tabs">
                @foreach($availableTabs as $key => $tab)
                    <button @click="activeTab = '{{ $key }}'"
                            :class="activeTab === '{{ $key }}' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                            class="group inline-flex items-center py-6 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                        <!-- Tab Icon -->
                        <svg class="w-5 h-5 mr-2 transition-colors duration-200"
                             :class="activeTab === '{{ $key }}' ? 'text-blue-500 dark:text-blue-400' : 'text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300'"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $tab['icon'] }}"/>
                        </svg>
                        
                        <!-- Tab Label -->
                        <span>{{ $tab['label'] }}</span>
                        
                        <!-- Badge -->
                        @if(isset($tab['badge']) && $tab['badge'] > 0)
                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                  :class="activeTab === '{{ $key }}' ? 'bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200'">
                                {{ $tab['badge'] }}
                            </span>
                        @endif
                    </button>
                @endforeach
            </nav>
        </div>
    </div>
    
    <!-- Mobile Navigation -->
    <div class="lg:hidden">
        <div class="max-w-7xl mx-auto px-6 sm:px-6">
            <!-- Mobile Tab Selector -->
            <div class="relative">
                <button @click="mobileMenuOpen = !mobileMenuOpen" 
                        type="button" 
                        class="w-full flex items-center justify-between py-6 text-sm font-medium text-gray-900 dark:text-gray-100">
                    <div class="flex items-center">
                        @foreach($availableTabs as $key => $tab)
                            <div x-show="activeTab === '{{ $key }}'" class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $tab['icon'] }}"/>
                                </svg>
                                <span>{{ $tab['label'] }}</span>
                                @if(isset($tab['badge']) && $tab['badge'] > 0)
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200">
                                        {{ $tab['badge'] }}
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" 
                         :class="mobileMenuOpen ? 'rotate-180' : ''" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                
                <!-- Mobile Dropdown -->
                <div x-show="mobileMenuOpen" 
                     @click.away="mobileMenuOpen = false"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 shadow-lg rounded-md border border-gray-200 dark:border-gray-700">
                    
                    <div class="py-1">
                        @foreach($availableTabs as $key => $tab)
                            <button @click="activeTab = '{{ $key }}'; mobileMenuOpen = false"
                                    :class="activeTab === '{{ $key }}' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : 'text-gray-900 dark:text-gray-100'"
                                    class="group flex items-center px-6 py-6 text-sm font-medium w-full text-left hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-5 h-5 mr-3" 
                                     :class="activeTab === '{{ $key }}' ? 'text-blue-500 dark:text-blue-400' : 'text-gray-400'"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $tab['icon'] }}"/>
                                </svg>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <span>{{ $tab['label'] }}</span>
                                        @if(isset($tab['badge']) && $tab['badge'] > 0)
                                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                                {{ $tab['badge'] }}
                                            </span>
                                        @endif
                                    </div>
                                    @if(isset($tab['description']))
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $tab['description'] }}</div>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
