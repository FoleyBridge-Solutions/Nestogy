@props(['activeDomain' => null])

@php
// Get navigation context
$selectedClient = \App\Domains\Core\Services\NavigationService::getSelectedClient();
$currentWorkflow = \App\Domains\Core\Services\NavigationService::getWorkflowContext(); 
$workflowHighlights = \App\Domains\Core\Services\NavigationService::getWorkflowNavigationHighlights($currentWorkflow);

// Prepare badge counts
$badges = [
    'urgent' => $workflowHighlights['badges']['urgent'] ?? 0,
    'today' => $workflowHighlights['badges']['today'] ?? 0,
    'scheduled' => $workflowHighlights['badges']['scheduled'] ?? 0,
    'financial' => $workflowHighlights['badges']['financial'] ?? 0,
];

// Workflow items configuration
$workflowItems = [
    [
        'key' => 'urgent',
        'label' => 'Urgent',
        'icon' => 'exclamation-triangle',
        'color' => 'red',
        'count' => $badges['urgent'],
        'route' => route('dashboard', ['view' => 'urgent']),
        'description' => 'Critical items requiring immediate attention'
    ],
    [
        'key' => 'today',
        'label' => "Today",
        'icon' => 'calendar-days',
        'color' => 'blue',
        'count' => $badges['today'],
        'route' => route('dashboard', ['view' => 'today']),
        'description' => 'Scheduled tasks for today'
    ],
    [
        'key' => 'scheduled',
        'label' => 'Scheduled',
        'icon' => 'clock',
        'color' => 'indigo',
        'count' => $badges['scheduled'],
        'route' => route('dashboard', ['view' => 'scheduled']),
        'description' => 'Upcoming work and appointments'
    ],
    [
        'key' => 'financial',
        'label' => 'Financial',
        'icon' => 'banknotes',
        'color' => 'green',
        'count' => $badges['financial'],
        'route' => route('dashboard', ['view' => 'financial']),
        'description' => 'Invoices and payments'
    ],
];
@endphp

<!-- Desktop Workflow Navigation -->
<div class="hidden lg:flex items-center gap-1">
    @foreach($workflowItems as $item)
        <flux:button 
            variant="ghost" 
            size="sm"
            href="{{ $item['route'] }}" 
            icon="{{ $item['icon'] }}">
            <span class="hidden xl:inline">{{ $item['label'] }}</span>
            @if($item['count'] > 0)
                <flux:badge size="sm" variant="solid" color="{{ $item['color'] }}">
                    {{ $item['count'] > 99 ? '99+' : $item['count'] }}
                </flux:badge>
            @endif
        </flux:button>
    @endforeach

    <flux:separator vertical variant="subtle" class="mx-2 h-6"/>

    <!-- Client Switcher Component -->
    <livewire:client-switcher />
</div>

<!-- Mobile Workflow Navigation -->
<div class="lg:hidden">
    <flux:dropdown position="bottom" align="start">
        <flux:button variant="ghost">
            <flux:navbar.item icon:trailing="chevron-down">
                Menu
            </flux:navbar.item>
        </flux:button>
        
        <flux:navmenu class="w-64">
            <div class="p-6 border-b">
                <flux:heading size="sm">Workflow</flux:heading>
            </div>
            
            @foreach($workflowItems as $item)
                <flux:navmenu.item 
                    icon="{{ $item['icon'] }}" 
                    href="{{ $item['route'] }}"
                    class="{{ $currentWorkflow === $item['key'] ? 'bg-zinc-100 dark:bg-zinc-800' : '' }}">
                    <div class="flex justify-between items-center w-full">
                        <span>{{ $item['label'] }}</span>
                        @if($item['count'] > 0)
                            <flux:badge variant="outline" size="sm">{{ $item['count'] > 99 ? '99+' : $item['count'] }}</flux:badge>
                        @endif
                    </div>
                </flux:navmenu.item>
            @endforeach
            
            <flux:navmenu.separator />
            
            @if($selectedClient)
                <flux:navmenu.item icon="user" href="{{ route('clients.index') }}">
                    {{ Str::limit($selectedClient->name, 25) }}
                </flux:navmenu.item>
                <flux:navmenu.item icon="arrow-path" href="{{ route('clients.clear-selection') }}">
                    Clear Client
                </flux:navmenu.item>
            @else
                <flux:navmenu.item icon="user-group" href="{{ route('clients.index') }}">
                    Select Client
                </flux:navmenu.item>
            @endif
        </flux:navmenu>
    </flux:dropdown>
</div>
