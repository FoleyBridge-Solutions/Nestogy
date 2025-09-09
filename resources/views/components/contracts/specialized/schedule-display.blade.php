@props([
    'schedule',
    'contract',
    'type' => 'infrastructure',
    'title' => null,
    'editable' => false,
    'expanded' => false
])

@php
$scheduleTypeConfig = [
    'infrastructure' => [
        'title' => 'Schedule A - Supported Infrastructure',
        'icon' => 'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z',
        'color' => 'blue',
        'bgClass' => 'bg-blue-50 dark:bg-blue-900/20',
        'borderClass' => 'border-blue-200 dark:border-blue-700',
        'textClass' => 'text-blue-900 dark:text-blue-100',
        'iconClass' => 'text-blue-600 dark:text-blue-400'
    ],
    'pricing' => [
        'title' => 'Schedule B - Pricing and Fees',
        'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1',
        'color' => 'green',
        'bgClass' => 'bg-green-50 dark:bg-green-900/20',
        'borderClass' => 'border-green-200 dark:border-green-700',
        'textClass' => 'text-green-900 dark:text-green-100',
        'iconClass' => 'text-green-600 dark:text-green-400'
    ],
    'sla' => [
        'title' => 'Schedule C - Service Level Agreement',
        'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'color' => 'purple',
        'bgClass' => 'bg-purple-50 dark:bg-purple-900/20',
        'borderClass' => 'border-purple-200 dark:border-purple-700',
        'textClass' => 'text-purple-900 dark:text-purple-100',
        'iconClass' => 'text-purple-600 dark:text-purple-400'
    ],
    'procedures' => [
        'title' => 'Schedule D - Procedures and Protocols',
        'icon' => 'M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
        'color' => 'orange',
        'bgClass' => 'bg-orange-50 dark:bg-orange-900/20',
        'borderClass' => 'border-orange-200 dark:border-orange-700',
        'textClass' => 'text-orange-900 dark:text-orange-100',
        'iconClass' => 'text-orange-600 dark:text-orange-400'
    ]
];

$config = $scheduleTypeConfig[$type] ?? $scheduleTypeConfig['infrastructure'];
$displayTitle = $title ?? $config['title'];
@endphp

<div class="bg-white dark:bg-gray-800 rounded-lg border {{ $config['borderClass'] }} shadow-sm" 
     x-data="{ expanded: {{ $expanded ? 'true' : 'false' }} }">
    
    <!-- Schedule Header -->
    <div class="{{ $config['bgClass'] }} px-6 py-6 border-b {{ $config['borderClass'] }}">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <!-- Schedule Icon -->
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 {{ $config['iconClass'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $config['icon'] }}"/>
                    </svg>
                </div>
                
                <!-- Title and Meta -->
                <div>
                    <h3 class="text-lg font-semibold {{ $config['textClass'] }}">{{ $displayTitle }}</h3>
                    @if($schedule)
                        <div class="flex items-center space-x-4 mt-1">
                            <span class="text-sm {{ $config['textClass'] }} opacity-75">
                                Version {{ $schedule->version ?? '1.0' }}
                            </span>
                            @if($schedule->effective_date)
                                <span class="text-sm {{ $config['textClass'] }} opacity-75">
                                    Effective: {{ $schedule->effective_date->format('M d, Y') }}
                                </span>
                            @endif
                            @if($schedule->isEffective())
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Active
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center space-x-2">
                @if($editable && $schedule)
                    <button type="button" 
                            class="inline-flex items-center px-6 py-1.5 border border-transparent text-sm font-medium rounded {{ $config['textClass'] }} hover:bg-white hover:bg-opacity-20 transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                    </button>
                @endif
                
                <button @click="expanded = !expanded" 
                        type="button" 
                        class="inline-flex items-center px-2 py-1.5 {{ $config['textClass'] }} hover:bg-white hover:bg-opacity-20 rounded transition-colors">
                    <svg class="w-4 h-4 transform transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Schedule Content -->
    <div x-show="expanded" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95"
         class="p-6">
        
        @if($schedule)
            @if($type === 'infrastructure')
                <x-contracts.specialized.schedule-infrastructure :schedule="$schedule" :contract="$contract" />
            @elseif($type === 'pricing')
                <x-contracts.specialized.schedule-pricing :schedule="$schedule" :contract="$contract" />
            @elseif($type === 'sla')
                <x-contracts.specialized.schedule-sla :schedule="$schedule" :contract="$contract" />
            @elseif($type === 'procedures')
                <x-contracts.specialized.schedule-procedures :schedule="$schedule" :contract="$contract" />
            @else
                <!-- Generic schedule content -->
                <div class="prose prose-sm max-w-none">
                    <div class="text-gray-700 dark:text-gray-300">
                        {!! nl2br(e($schedule->content ?? 'No content available')) !!}
                    </div>
                </div>
            @endif
        @else
            <!-- No schedule content -->
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No Schedule Content</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">This schedule has not been configured yet.</p>
                @if($editable)
                    <div class="mt-6">
                        <button type="button" 
                                class="inline-flex items-center px-6 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Create {{ $displayTitle }}
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
