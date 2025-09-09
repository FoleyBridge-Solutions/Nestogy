@extends('layouts.app')

@section('title', $client->name . ' - Client Overview')

@push('styles')
<style>
    .client-dashboard {
        display: grid;
        grid-template-areas:
            "header header header"
            "metrics metrics metrics"
            "activity notes sidebar"
            "tabs tabs tabs";
        grid-template-columns: 1fr 1fr 300px;
        gap: 0.75rem;
        padding: 0.75rem;
    }
    
    .header-section {
        grid-area: header;
    }
    
    .metrics-section {
        grid-area: metrics;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 0.75rem;
    }
    
    .activity-section {
        grid-area: activity;
        display: flex;
        flex-direction: column;
    }
    
    .activity-section > .flux-card {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .notes-section {
        grid-area: notes;
    }
    
    .sidebar-section {
        grid-area: sidebar;
    }
    
    .tabs-section {
        grid-area: tabs;
    }
    
    .metric-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
    }
    
    .metric-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .metric-card.has-alert::after {
        content: '';
        position: absolute;
        top: 0.25rem;
        right: 0.25rem;
        width: 8px;
        height: 8px;
        background: rgb(239, 68, 68);
        border-radius: 50%;
    }
    
    .metric-content {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .metric-value {
        font-size: 1.5rem;
        font-weight: 600;
        line-height: 1;
    }
    
    .metric-label {
        font-size: 0.75rem;
        color: rgb(107 114 128);
    }
    
    .metric-sublabel {
        font-size: 0.7rem;
        font-weight: 500;
    }
    
    .activity-timeline {
        flex: 1;
        overflow-y: auto;
    }
    
    .activity-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem;
        border-left: 2px solid transparent;
        transition: all 0.2s;
    }
    
    .activity-item:hover {
        background: rgba(59, 130, 246, 0.05);
        border-left-color: rgb(59, 130, 246);
    }
    
    .activity-time {
        font-size: 0.7rem;
        color: rgb(156 163 175);
        min-width: 50px;
    }
    
    .activity-icon {
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .activity-text {
        font-size: 0.875rem;
        flex: 1;
    }
    
    .info-card {
        padding: 0.75rem;
        margin-bottom: 0.75rem;
    }
    
    .info-card-title {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        color: rgb(107 114 128);
        margin-bottom: 0.5rem;
    }
    
    .info-card-content {
        font-size: 0.875rem;
    }
    
    /* Responsive */
    @media (max-width: 1024px) {
        .client-dashboard {
            grid-template-areas:
                "header"
                "metrics"
                "activity"
                "notes"
                "sidebar"
                "tabs";
            grid-template-columns: 1fr;
        }
        
        .metrics-section {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 640px) {
        .metrics-section {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="client-dashboard">
    <!-- Compact Header -->
    <div class="header-section">
        <flux:card class="p-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <!-- Avatar -->
                    @if($client->avatar)
                        <img class="h-10 w-10 rounded-full object-cover" src="{{ Storage::url($client->avatar) }}" alt="{{ $client->name }}">
                    @else
                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                            <span class="text-sm font-bold text-white">{{ strtoupper(substr($client->name, 0, 2)) }}</span>
                        </div>
                    @endif
                    
                    <!-- Client Info -->
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-lg font-semibold">{{ $client->name }}</h1>
                            <flux:badge color="{{ $client->is_active ? 'green' : 'red' }}" size="sm">
                                {{ $client->is_active ? 'Active' : 'Inactive' }}
                            </flux:badge>
                            @if($client->lead)
                                <flux:badge color="yellow" size="sm">Lead</flux:badge>
                            @else
                                <flux:badge color="blue" size="sm">Customer</flux:badge>
                            @endif
                        </div>
                        @if($client->company_name)
                            <p class="text-xs text-gray-500">{{ $client->company_name }}</p>
                        @endif
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="flex items-center gap-2">
                    <div class="hidden md:flex items-center gap-4 mr-4">
                        <div class="text-center">
                            <p class="text-xs text-gray-500">Revenue</p>
                            <p class="text-sm font-semibold">${{ number_format($client->invoices()->where('status', 'paid')->sum('amount') ?? 0, 0) }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-500">SLA</p>
                            <p class="text-sm font-semibold">{{ $metrics['sla_compliance'] ?? 0 }}%</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-500">Score</p>
                            <p class="text-sm font-semibold">{{ $metrics['satisfaction_score'] ?? 0 }}%</p>
                        </div>
                    </div>
                    
                    <flux:button variant="ghost" size="sm" icon="pencil" href="{{ route('clients.edit', $client) }}">
                        Edit
                    </flux:button>
                    <flux:dropdown>
                        <flux:button variant="primary" size="sm" icon="plus">Actions</flux:button>
                        <flux:menu>
                            <flux:menu.item icon="ticket" href="{{ route('tickets.create', ['client_id' => $client->id]) }}">
                                New Ticket
                            </flux:menu.item>
                            <flux:menu.item icon="document-text" href="{{ route('financial.invoices.create', ['client_id' => $client->id]) }}">
                                New Invoice
                            </flux:menu.item>
                            <flux:menu.item icon="clipboard-document-list" href="{{ route('projects.create', ['client_id' => $client->id]) }}">
                                New Project
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
        </flux:card>
    </div>
    
    <!-- Metrics Cards -->
    <div class="metrics-section">
        @php
            $openTickets = $client->tickets()->whereIn('status', ['Open', 'In Progress'])->count();
            $urgentTickets = $client->tickets()->where('priority', 'high')->whereIn('status', ['Open', 'In Progress'])->count();
            $overdueInvoices = $client->invoices()->where('status', 'overdue')->count();
            $pendingAmount = $client->invoices()->whereIn('status', ['pending', 'overdue'])->sum('amount');
            $activeProjects = $client->projects()->where('status', 'in_progress')->count();
            $totalAssets = $client->assets()->count();
            $offlineAssets = $client->assets()->where('status', 'offline')->count();
        @endphp
        
        <!-- Tickets Card -->
        <flux:card class="metric-card {{ $urgentTickets > 0 ? 'has-alert' : '' }}">
            <div class="metric-content">
                <div class="metric-value">{{ $openTickets }}</div>
                <div class="metric-label">Open Tickets</div>
                @if($urgentTickets > 0)
                    <div class="metric-sublabel text-red-600">{{ $urgentTickets }} urgent</div>
                @endif
            </div>
            <div class="text-3xl opacity-20">🎫</div>
        </flux:card>
        
        <!-- Invoices Card -->
        <flux:card class="metric-card {{ $overdueInvoices > 0 ? 'has-alert' : '' }}">
            <div class="metric-content">
                <div class="metric-value">${{ number_format($pendingAmount, 0) }}</div>
                <div class="metric-label">Outstanding</div>
                @if($overdueInvoices > 0)
                    <div class="metric-sublabel text-red-600">{{ $overdueInvoices }} overdue</div>
                @endif
            </div>
            <div class="text-3xl opacity-20">💰</div>
        </flux:card>
        
        <!-- Projects Card -->
        <flux:card class="metric-card">
            <div class="metric-content">
                <div class="metric-value">{{ $activeProjects }}</div>
                <div class="metric-label">Active Projects</div>
                @if($activeProjects > 0)
                    <div class="metric-sublabel text-blue-600">In progress</div>
                @endif
            </div>
            <div class="text-3xl opacity-20">📋</div>
        </flux:card>
        
        <!-- Assets Card -->
        <flux:card class="metric-card {{ $offlineAssets > 0 ? 'has-alert' : '' }}">
            <div class="metric-content">
                <div class="metric-value">{{ $totalAssets }}</div>
                <div class="metric-label">Total Assets</div>
                @if($offlineAssets > 0)
                    <div class="metric-sublabel text-orange-600">{{ $offlineAssets }} offline</div>
                @endif
            </div>
            <div class="text-3xl opacity-20">🖥️</div>
        </flux:card>
    </div>
    
    <!-- Activity Timeline -->
    <div class="activity-section">
        <flux:card class="p-3 h-full flex flex-col">
            <h3 class="text-sm font-semibold mb-3">Recent Activity</h3>
            <div class="flex-1 overflow-y-auto">
                @livewire('client-activity-timeline', ['client' => $client])
            </div>
        </flux:card>
    </div>
    
    <!-- Notes/Details Section -->
    <div class="notes-section">
        <flux:card class="p-3 h-full flex flex-col">
            <h3 class="text-sm font-semibold mb-3">Notes & Details</h3>
            <div class="flex-1 flex flex-col">
                @livewire('client-notes', ['client' => $client])
                
                    <div class="mt-3 space-y-3">
                        <div>
                            <h4 class="text-xs font-medium text-gray-600 mb-1">Service Summary</h4>
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-600">Avg Ticket Resolution</span>
                                    <span>{{ $metrics['avg_resolution_time'] ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-600">Monthly Revenue</span>
                                    <span>${{ number_format($metrics['monthly_revenue'] ?? 0, 0) }}</span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-600">Monthly Service Cost</span>
                                    <span>${{ number_format($metrics['total_monthly_cost'] ?? 0, 0) }}</span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-600">Last Contact</span>
                                    <span>{{ $client->tickets()->latest()->first()?->created_at?->diffForHumans() ?? 'Never' }}</span>
                                </div>
                            </div>
                        </div>
                    
                    @if($client->tags)
                        <div>
                            <h4 class="text-xs font-medium text-gray-600 mb-1">Tags</h4>
                            <div class="flex flex-wrap gap-1">
                                @foreach(explode(',', $client->tags) as $tag)
                                    <flux:badge color="zinc" size="sm">{{ trim($tag) }}</flux:badge>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </flux:card>
    </div>
    
    <!-- Sidebar Info Cards -->
    <div class="sidebar-section">
        <!-- Contact Info -->
        <flux:card class="info-card">
            <div class="info-card-title">Primary Contact</div>
            <div class="info-card-content">
                @php
                    $primaryContact = $client->contacts()->where('primary', true)->first();
                @endphp
                @if($primaryContact)
                    <p class="font-medium">{{ $primaryContact->name }}</p>
                    <p class="text-xs text-gray-600">{{ $primaryContact->email }}</p>
                    <p class="text-xs text-gray-600">{{ $primaryContact->phone }}</p>
                @else
                    <p class="text-xs text-gray-500">No primary contact set</p>
                @endif
            </div>
        </flux:card>
        
        <!-- Billing Info -->
        <flux:card class="info-card">
            <div class="info-card-title">Billing</div>
            <div class="info-card-content">
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-600">Net Terms</span>
                    <span>{{ $client->net_terms ?? 30 }} days</span>
                </div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-600">Currency</span>
                    <span>{{ $client->currency_code ?? 'USD' }}</span>
                </div>
                @if($client->rate)
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-600">Rate</span>
                        <span>${{ number_format($client->rate, 0) }}/hr</span>
                    </div>
                @endif
            </div>
        </flux:card>
        
        <!-- Location Info -->
        <flux:card class="info-card">
            <div class="info-card-title">Primary Location</div>
            <div class="info-card-content">
                @php
                    $primaryLocation = $client->locations()->where('primary', true)->first();
                @endphp
                @if($primaryLocation)
                    <p class="text-xs">{{ $primaryLocation->address }}</p>
                    <p class="text-xs">{{ $primaryLocation->city }}, {{ $primaryLocation->state }} {{ $primaryLocation->zip_code }}</p>
                @elseif($client->address)
                    <p class="text-xs">{{ $client->address }}</p>
                    <p class="text-xs">{{ $client->city }}, {{ $client->state }} {{ $client->zip_code }}</p>
                @else
                    <p class="text-xs text-gray-500">No location set</p>
                @endif
            </div>
        </flux:card>
        
        <!-- Active Services -->
        <flux:card class="info-card">
            <div class="info-card-title">Active Services</div>
            <div class="info-card-content">
                @if(!empty($metrics['active_services']))
                    <div class="space-y-1">
                        @foreach(array_slice($metrics['active_services'], 0, 3) as $service)
                            <div class="text-xs">
                                <div class="font-medium">{{ $service['name'] }}</div>
                                <div class="text-gray-600">
                                    {{ $service['quantity'] }}x @ ${{ number_format($service['price'], 0) }}/{{ $service['billing_cycle'] }}
                                </div>
                            </div>
                        @endforeach
                        @if(count($metrics['active_services']) > 3)
                            <div class="text-xs text-gray-500">
                                +{{ count($metrics['active_services']) - 3 }} more services
                            </div>
                        @endif
                    </div>
                @else
                    <p class="text-xs text-gray-500">No active services</p>
                @endif
            </div>
        </flux:card>

    </div>
    
    <!-- Tabbed Content Area -->
    <div class="tabs-section">
        <flux:card>
            <flux:tab.group>
                <flux:tabs>
                    <flux:tab name="tickets" icon="ticket">
                        Tickets
                        <flux:badge size="sm" variant="subtle" class="ml-2">{{ $client->tickets()->count() }}</flux:badge>
                    </flux:tab>
                    <flux:tab name="invoices" icon="document-text">
                        Invoices
                        <flux:badge size="sm" variant="subtle" class="ml-2">{{ $client->invoices()->count() }}</flux:badge>
                    </flux:tab>
                    <flux:tab name="projects" icon="clipboard-document-list">
                        Projects
                        <flux:badge size="sm" variant="subtle" class="ml-2">{{ $client->projects()->count() }}</flux:badge>
                    </flux:tab>
                    <flux:tab name="assets" icon="server-stack">
                        Assets
                        <flux:badge size="sm" variant="subtle" class="ml-2">{{ $client->assets()->count() }}</flux:badge>
                    </flux:tab>
                    <flux:tab name="contacts" icon="user-group">
                        Contacts
                        <flux:badge size="sm" variant="subtle" class="ml-2">{{ $client->contacts()->count() }}</flux:badge>
                    </flux:tab>
                    <flux:tab name="locations" icon="map-pin">
                        Locations
                        <flux:badge size="sm" variant="subtle" class="ml-2">{{ $client->locations()->count() }}</flux:badge>
                    </flux:tab>
                </flux:tabs>
                
                <flux:tab.panel name="tickets" class="p-4">
                    @include('clients.partials.tickets-table', ['tickets' => $client->tickets()->latest()->limit(20)->get()])
                </flux:tab.panel>
                
                <flux:tab.panel name="invoices" class="p-4">
                    @include('clients.partials.invoices-table', ['invoices' => $client->invoices()->latest()->limit(20)->get()])
                </flux:tab.panel>
                
                <flux:tab.panel name="projects" class="p-4">
                    @include('clients.partials.projects-table', ['projects' => $client->projects()->latest()->limit(20)->get()])
                </flux:tab.panel>
                
                <flux:tab.panel name="assets" class="p-4">
                    @include('clients.partials.assets-table', ['assets' => $client->assets()->latest()->limit(20)->get()])
                </flux:tab.panel>
                
                <flux:tab.panel name="contacts" class="p-4">
                    @include('clients.partials.contacts-table', ['contacts' => $client->contacts])
                </flux:tab.panel>
                
                <flux:tab.panel name="locations" class="p-4">
                    @include('clients.partials.locations-table', ['locations' => $client->locations])
                </flux:tab.panel>
            </flux:tab.group>
        </flux:card>
    </div>
</div>
@endsection