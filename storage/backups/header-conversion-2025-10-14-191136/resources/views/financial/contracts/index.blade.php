@extends('layouts.app')

@section('title', 'Contract Management')

@section('content')
<div class="min-h-screen" x-data="contractDashboard()">
    <!-- Header Section -->
    <div class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 mb-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="xl">Contract Management</flux:heading>
                    <flux:text class="mt-1">Intelligent contract lifecycle management</flux:text>
                </div>
                <div class="flex items-center space-x-3">
                    <!-- AI Assistant Button -->
                    <flux:button variant="ghost" icon="sparkles">
                        AI Assistant
                    </flux:button>
                    
                    <!-- Template Library -->
                    <flux:button href="/contracts/templates" variant="ghost" icon="rectangle-stack">
                        Template Library
                    </flux:button>
                    
                    <!-- Create Contract Button -->
                    <flux:button href="/financial/contracts/create" variant="primary" icon="plus">
                        New Contract
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Statistics Dashboard -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
            <!-- Total Contracts -->
            <flux:card class="hover:shadow-md transition-shadow cursor-pointer" @click="filterByStatus('')">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="2xl">{{ $statistics['total_contracts'] ?? 0 }}</flux:heading>
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Total Contracts</flux:text>
                    </div>
                    <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                        <flux:icon name="document-text" class="w-6 h-6 text-gray-600 dark:text-gray-400" />
                    </div>
                </div>
            </flux:card>
            
            <!-- Active Contracts -->
            <flux:card class="hover:shadow-md transition-shadow cursor-pointer" @click="filterByStatus('active')">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="2xl" class="text-emerald-600 dark:text-emerald-400">{{ $statistics['active_contracts'] ?? 0 }}</flux:heading>
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Active</flux:text>
                    </div>
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                        <flux:icon name="check-circle" class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                </div>
            </flux:card>
            
            <!-- Pending Signature -->
            <flux:card class="hover:shadow-md transition-shadow cursor-pointer" @click="filterByStatus('pending_signature')">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="2xl" class="text-amber-600 dark:text-amber-400">{{ $statistics['pending_signature'] ?? 0 }}</flux:heading>
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Pending Signature</flux:text>
                    </div>
                    <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                        <flux:icon name="pencil-square" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>
            </flux:card>
            
            <!-- Draft Contracts -->
            <flux:card class="hover:shadow-md transition-shadow cursor-pointer" @click="filterByStatus('{{ \App\Domains\Contract\Models\Contract::STATUS_DRAFT }}')">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="2xl" class="text-gray-600 dark:text-gray-400">{{ $statistics['draft_contracts'] ?? 0 }}</flux:heading>
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Drafts</flux:text>
                    </div>
                    <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                        <flux:icon name="pencil" class="w-6 h-6 text-gray-600 dark:text-gray-400" />
                    </div>
                </div>
            </flux:card>
            
            <!-- Total Value -->
            <flux:card class="hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="2xl" class="text-green-600 dark:text-green-400">${{ number_format(($statistics['total_value'] ?? 0) / 1000, 0) }}k</flux:heading>
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Total Value</flux:text>
                    </div>
                    <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <flux:icon name="currency-dollar" class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                </div>
            </flux:card>
            
            <!-- Monthly Revenue -->
            <flux:card class="hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="2xl" class="text-blue-600 dark:text-blue-400">${{ number_format(($statistics['monthly_revenue'] ?? 0) / 1000, 0) }}k</flux:heading>
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Monthly Revenue</flux:text>
                    </div>
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <flux:icon name="arrow-trending-up" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </flux:card>
            
            <!-- Expiring Soon -->
            <flux:card class="hover:shadow-md transition-shadow cursor-pointer" @click="showExpiringContracts()">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="2xl" class="text-red-600 dark:text-red-400">{{ $statistics['expiring_soon'] ?? 0 }}</flux:heading>
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Expiring Soon</flux:text>
                    </div>
                    <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                        <flux:icon name="clock" class="w-6 h-6 text-red-600 dark:text-red-400" />
                    </div>
                </div>
            </flux:card>
            
            <!-- Renewal Rate -->
            <flux:card class="hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="2xl" class="text-purple-600 dark:text-purple-400">{{ number_format($statistics['renewal_rate'] ?? 0, 0) }}%</flux:heading>
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Renewal Rate</flux:text>
                    </div>
                    <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <flux:icon name="arrow-path" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                </div>
            </flux:card>
        </div>

        <!-- Control Bar -->
        <flux:card x-data="{ showAdvancedFilters: false, selectedContracts: [] }">
            <!-- Main Controls -->
            <div class="space-y-4">
                <!-- Search and View Toggle -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <!-- Search Bar -->
                    <div class="flex-1 max-w-2xl">
                        <flux:input 
                            type="search"
                            x-model="searchQuery"
                            @input.debounce.300ms="search()"
                            placeholder="Search by contract name, client, or ask AI..."
                            icon="magnifying-glass"
                        />
                    </div>
                    
                    <!-- View Toggle -->
                    <flux:tabs variant="segmented" x-model="viewMode">
                        <flux:tab name="cards" icon="squares-2x2">Cards</flux:tab>
                        <flux:tab name="list" icon="list-bullet">List</flux:tab>
                        <flux:tab name="timeline" icon="calendar-days">Timeline</flux:tab>
                    </flux:tabs>
                </div>
                
                <!-- Quick Filters -->
                <div class="flex flex-wrap gap-2">
                    <flux:button 
                        @click="setQuickFilter('needs_attention')"
                        x-bind:class="activeFilter === 'needs_attention' ? '' : 'opacity-75'"
                        variant="ghost"
                        size="sm"
                        icon="exclamation-triangle"
                    >
                        Needs My Attention
                        <flux:badge color="red" size="sm" inset="top bottom">3</flux:badge>
                    </flux:button>
                    
                    <flux:button 
                        @click="setQuickFilter('expiring_soon')"
                        x-bind:class="activeFilter === 'expiring_soon' ? '' : 'opacity-75'"
                        variant="ghost"
                        size="sm"
                        icon="clock"
                    >
                        Expiring Soon
                        <flux:badge color="amber" size="sm" inset="top bottom">{{ $statistics['expiring_soon'] ?? 0 }}</flux:badge>
                    </flux:button>
                    
                    <flux:button 
                        @click="setQuickFilter('pending_signature')"
                        x-bind:class="activeFilter === 'pending_signature' ? '' : 'opacity-75'"
                        variant="ghost"
                        size="sm"
                        icon="pencil-square"
                    >
                        Pending Signature
                        <flux:badge color="blue" size="sm" inset="top bottom">{{ $statistics['pending_signature'] ?? 0 }}</flux:badge>
                    </flux:button>
                    
                    <flux:button 
                        @click="setQuickFilter('{{ \App\Domains\Contract\Models\Contract::STATUS_DRAFT }}')"
                        x-bind:class="activeFilter === '{{ \App\Domains\Contract\Models\Contract::STATUS_DRAFT }}' ? '' : 'opacity-75'"
                        variant="ghost"
                        size="sm"
                        icon="pencil"
                    >
                        My Drafts
                        <flux:badge color="zinc" size="sm" inset="top bottom">{{ $statistics['draft_contracts'] ?? 0 }}</flux:badge>
                    </flux:button>
                    
                    <flux:button 
                        @click="setQuickFilter('high_value')"
                        x-bind:class="activeFilter === 'high_value' ? '' : 'opacity-75'"
                        variant="ghost"
                        size="sm"
                        icon="currency-dollar"
                    >
                        High Value (>$50k)
                    </flux:button>
                    
                    <flux:button 
                        @click="clearFilters()"
                        x-show="activeFilter !== ''"
                        variant="ghost"
                        size="sm"
                        icon="x-mark"
                        class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300"
                    >
                        Clear Filters
                    </flux:button>
                </div>
                
                <!-- Advanced Filters Toggle and Sort -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <flux:button 
                            @click="showAdvancedFilters = !showAdvancedFilters"
                            variant="ghost"
                            size="sm"
                            icon="adjustments-horizontal"
                            x-bind:class="showAdvancedFilters ? 'text-blue-600' : ''"
                        >
                            Advanced Filters
                        </flux:button>
                        
                        <!-- Sort Options -->
                        <flux:select 
                            x-model="sortBy" 
                            @change="applySorting()"
                            size="sm"
                            class="w-48"
                        >
                            <flux:select.option value="updated_at-desc">Recently Updated</flux:select.option>
                            <flux:select.option value="created_at-desc">Newest First</flux:select.option>
                            <flux:select.option value="contract_value-desc">Highest Value</flux:select.option>
                            <flux:select.option value="end_date-asc">Expiring Soon</flux:select.option>
                            <flux:select.option value="client_name-asc">Client A-Z</flux:select.option>
                            <flux:select.option value="status-asc">Status</flux:select.option>
                        </flux:select>
                    </div>
                    
                    <!-- Bulk Actions -->
                    <div x-show="selectedContracts.length > 0" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         class="flex items-center space-x-2">
                        <flux:text size="sm" x-text="selectedContracts.length + ' contract(s) selected'"></flux:text>
                        <flux:button variant="ghost" size="sm">Bulk Actions</flux:button>
                    </div>
                </div>
            </div>
            
            <!-- Advanced Filters Panel -->
            <div x-show="showAdvancedFilters" x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <form id="contractFilters" method="GET">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Status Filter -->
                        <flux:select name="status" label="Status" placeholder="All Statuses">
                            <flux:select.option value="active">Active</flux:select.option>
                            <flux:select.option value="pending_signature">Pending Signature</flux:select.option>
                            <flux:select.option value="{{ \App\Domains\Contract\Models\Contract::STATUS_DRAFT }}">Draft</flux:select.option>
                            <flux:select.option value="under_negotiation">Under Negotiation</flux:select.option>
                            <flux:select.option value="expired">Expired</flux:select.option>
                        </flux:select>
                        
                        <!-- Contract Type -->
                        <flux:select name="contract_type" label="Contract Type" placeholder="All Types">
                            <flux:select.option value="service_agreement">Service Agreement</flux:select.option>
                            <flux:select.option value="maintenance_agreement">Maintenance Agreement</flux:select.option>
                            <flux:select.option value="support_contract">Support Contract</flux:select.option>
                            <flux:select.option value="professional_services">Professional Services</flux:select.option>
                        </flux:select>
                        
                        <!-- Client Filter -->
                        <flux:select name="client_id" label="Client" placeholder="All Clients">
                            @foreach($clients ?? [] as $client)
                                <flux:select.option value="{{ $client->id }}">{{ $client->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        
                        <!-- Value Range -->
                        <flux:select name="value_range" label="Value Range" placeholder="All Values">
                            <flux:select.option value="0-5000">$0 - $5,000</flux:select.option>
                            <flux:select.option value="5000-25000">$5,000 - $25,000</flux:select.option>
                            <flux:select.option value="25000-100000">$25,000 - $100,000</flux:select.option>
                            <flux:select.option value="100000+">$100,000+</flux:select.option>
                        </flux:select>
                        
                        <!-- Date Range -->
                        <flux:input type="date" name="date_from" label="Start Date From" value="{{ request('date_from') }}" />
                        <flux:input type="date" name="date_to" label="Start Date To" value="{{ request('date_to') }}" />
                        
                        <!-- Template Filter -->
                        <flux:select name="template_id" label="Template" placeholder="All Templates">
                            <flux:select.option value="custom">Custom Contracts</flux:select.option>
                            <!-- Template options would be populated from backend -->
                        </flux:select>
                        
                        <!-- Billing Model Filter -->
                        <flux:select name="billing_model" label="Billing Model" placeholder="All Models">
                            <flux:select.option value="fixed">Fixed Price</flux:select.option>
                            <flux:select.option value="per_asset">Per Asset</flux:select.option>
                            <flux:select.option value="per_contact">Per Contact</flux:select.option>
                            <flux:select.option value="hybrid">Hybrid</flux:select.option>
                        </flux:select>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        <flux:button type="button" @click="resetAdvancedFilters()" variant="ghost">Reset</flux:button>
                        <flux:button type="submit" variant="primary">Apply Filters</flux:button>
                    </div>
                </form>
            </div>
        </flux:card>

        <!-- Contract Views -->
        <div class="contract-workspace">
            @if($contracts->count() > 0)
                <!-- Contract Cards Grid -->
                <div x-show="viewMode === 'cards'" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                    @foreach($contracts as $contract)
                        <flux:card 
                            class="hover:shadow-lg transition-all duration-200 border-l-4 cursor-pointer
                                {{ $contract->status === 'active' ? 'border-l-emerald-500' : '' }}
                                {{ $contract->status === 'pending_signature' ? 'border-l-amber-500' : '' }}
                                {{ $contract->status === \App\Domains\Contract\Models\Contract::STATUS_DRAFT ? 'border-l-gray-400' : '' }}
                                {{ $contract->status === 'under_negotiation' ? 'border-l-blue-500' : '' }}
                                {{ $contract->status === 'expired' ? 'border-l-red-500' : '' }}
                                {{ $contract->status === 'terminated' ? 'border-l-gray-900' : '' }}"
                            @click="window.location.href='/financial/contracts/{{ $contract->id }}'"
                        >
                            <!-- Card Header -->
                            <div class="space-y-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <flux:heading size="lg" class="truncate">{{ $contract->title }}</flux:heading>
                                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400 truncate">
                                            {{ $contract->client->name ?? 'No Client Assigned' }}
                                        </flux:text>
                                    </div>
                                    
                                    <flux:badge 
                                        color="{{ $contract->status === 'active' ? 'emerald' : 
                                               ($contract->status === 'pending_signature' ? 'amber' : 
                                               ($contract->status === 'under_negotiation' ? 'blue' : 
                                               ($contract->status === 'expired' ? 'red' : 'zinc'))) }}"
                                        variant="solid"
                                        size="sm"
                                    >
                                        {{ ucwords(str_replace('_', ' ', $contract->status)) }}
                                    </flux:badge>
                                </div>
                                
                                <!-- Contract Details Grid -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <flux:text size="xs" class="uppercase tracking-wide text-gray-500 dark:text-gray-400">Contract Value</flux:text>
                                        <flux:heading size="lg">
                                            @if($contract->billing_model === 'fixed')
                                                ${{ number_format($contract->contract_value, 0) }}
                                            @else
                                                <span class="text-purple-600 dark:text-purple-400">${{ number_format($contract->current_monthly_amount ?? 0, 0) }}/mo</span>
                                            @endif
                                        </flux:heading>
                                        @if($contract->billing_model !== 'fixed')
                                            <flux:text size="xs" class="text-gray-500 dark:text-gray-400">
                                                {{ ucwords(str_replace('_', ' ', $contract->billing_model)) }}
                                            </flux:text>
                                        @endif
                                    </div>
                                    <div>
                                        <flux:text size="xs" class="uppercase tracking-wide text-gray-500 dark:text-gray-400">Duration</flux:text>
                                        <flux:text size="sm">
                                            {{ $contract->start_date ? $contract->start_date->format('M j') : 'TBD' }} - 
                                            {{ $contract->end_date ? $contract->end_date->format('M j, Y') : 'Ongoing' }}
                                        </flux:text>
                                        @if($contract->end_date && $contract->end_date->diffInDays(now()) <= 30)
                                            <flux:text size="xs" class="text-red-600 dark:text-red-400">⚠️ Expires soon</flux:text>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Contract Type & Progress -->
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <flux:badge size="sm" color="zinc">
                                            {{ ucwords(str_replace('_', ' ', $contract->contract_type)) }}
                                        </flux:badge>
                                        @if($contract->template)
                                            <flux:badge size="sm" color="purple" icon="cpu-chip">
                                                {{ $contract->template->name }}
                                            </flux:badge>
                                        @endif
                                    </div>
                                    
                                    <!-- Progress Bar -->
                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <flux:text size="xs" class="text-gray-500 dark:text-gray-400">Progress</flux:text>
                                            <flux:text size="xs" class="font-medium">{{ $contract->completion_percentage ?? 25 }}%</flux:text>
                                        </div>
                                        <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full">
                                            <div class="h-2 rounded-full transition-all duration-300
                                                {{ $contract->status === 'active' ? 'bg-emerald-500' : '' }}
                                                {{ $contract->status === 'pending_signature' ? 'bg-amber-500' : '' }}
                                                {{ $contract->status === 'under_negotiation' ? 'bg-blue-500' : '' }}
                                                {{ $contract->status === \App\Domains\Contract\Models\Contract::STATUS_DRAFT ? 'bg-gray-400' : '' }}
                                                {{ $contract->status === 'expired' ? 'bg-red-500' : '' }}"
                                                style="width: {{ $contract->completion_percentage ?? 25 }}%">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Milestones & Signatures -->
                                    @if($contract->milestones_count > 0 || $contract->signatures_count > 0)
                                        <div class="flex items-center space-x-4 text-xs text-gray-600 dark:text-gray-400">
                                            @if($contract->milestones_count > 0)
                                                <div class="flex items-center">
                                                    <flux:icon name="check-circle" class="w-3 h-3 mr-1" />
                                                    {{ $contract->completed_milestones_count ?? 0 }}/{{ $contract->milestones_count }} milestones
                                                </div>
                                            @endif
                                            @if($contract->signatures_count > 0)
                                                <div class="flex items-center">
                                                    <flux:icon name="pencil-square" class="w-3 h-3 mr-1" />
                                                    {{ $contract->signed_count ?? 0 }}/{{ $contract->signatures_count }} signatures
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                
                                <flux:separator />
                                
                                <!-- Card Footer Actions -->
                                <div class="flex items-center justify-between" @click.stop>
                                    <div class="flex items-center space-x-2">
                                        @if($contract->status === \App\Domains\Contract\Models\Contract::STATUS_DRAFT)
                                            <flux:button size="sm" variant="primary" icon="pencil" @click="quickEdit({{ $contract->id }})">
                                                Edit
                                            </flux:button>
                                        @elseif($contract->status === 'pending_signature')
                                            <flux:button size="sm" variant="primary" icon="paper-airplane" @click="sendForSignature({{ $contract->id }})">
                                                Send
                                            </flux:button>
                                        @elseif($contract->status === 'active')
                                            <flux:button size="sm" variant="ghost" icon="eye" @click="viewDetails({{ $contract->id }})">
                                                View
                                            </flux:button>
                                        @endif
                                    </div>
                                    
                                    <!-- More Actions Dropdown -->
                                    <flux:dropdown>
                                        <flux:button slot="trigger" variant="ghost" icon="ellipsis-vertical" size="sm" />
                                        <flux:menu>
                                            <flux:menu.item icon="eye" href="/financial/contracts/{{ $contract->id }}">View Details</flux:menu.item>
                                            @if($contract->canBeEdited())
                                                <flux:menu.item icon="pencil" href="/financial/contracts/{{ $contract->id }}/edit">Edit Contract</flux:menu.item>
                                            @endif
                                            <flux:menu.item icon="arrow-down-tray" href="/financial/contracts/{{ $contract->id }}/pdf" target="_blank">Download PDF</flux:menu.item>
                                            <flux:menu.item icon="document-duplicate" href="/financial/contracts/{{ $contract->id }}/duplicate">Duplicate</flux:menu.item>
                                            @if($contract->billing_model !== 'fixed')
                                                <flux:menu.item icon="chart-bar" href="/financial/contracts/{{ $contract->id }}/usage-dashboard">Usage Dashboard</flux:menu.item>
                                            @endif
                                            @can('delete', $contract)
                                                @if($contract->status === \App\Domains\Contract\Models\Contract::STATUS_DRAFT)
                                                    <flux:separator />
                                                    <flux:menu.item icon="trash" variant="danger" @click="deleteContract({{ $contract->id }}, @js($contract->title))">Delete Contract</flux:menu.item>
                                                @endif
                                            @endcan
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </div>
                        </flux:card>
                    @endforeach
                </div>
                
                <!-- List View -->
                <div x-show="viewMode === 'list'" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform translate-y-4"
                     x-transition:enter-end="opacity-100 transform translate-y-0">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Contract</flux:table.column>
                            <flux:table.column>Client</flux:table.column>
                            <flux:table.column>Value</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            <flux:table.column>Progress</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>
                        
                        <flux:table.rows>
                            @foreach($contracts as $contract)
                                <flux:table.row class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800" @click="window.location.href='/financial/contracts/{{ $contract->id }}'">
                                    <flux:table.cell>
                                        <div class="flex items-center" @click.stop>
                                            <flux:checkbox x-model="selectedContracts" value="{{ $contract->id }}" class="mr-3" />
                                            <div>
                                                <flux:text class="font-medium">{{ $contract->title }}</flux:text>
                                                <flux:text size="xs" class="text-gray-500 dark:text-gray-400">
                                                    {{ ucwords(str_replace('_', ' ', $contract->contract_type)) }}
                                                </flux:text>
                                            </div>
                                        </div>
                                    </flux:table.cell>
                                    
                                    <flux:table.cell>
                                        <flux:text>{{ $contract->client->name ?? 'No Client' }}</flux:text>
                                        <flux:text size="xs" class="text-gray-500 dark:text-gray-400">
                                            {{ $contract->start_date ? $contract->start_date->format('M j, Y') : 'No date' }}
                                        </flux:text>
                                    </flux:table.cell>
                                    
                                    <flux:table.cell>
                                        <flux:text class="font-medium">
                                            @if($contract->billing_model === 'fixed')
                                                ${{ number_format($contract->contract_value, 0) }}
                                            @else
                                                <span class="text-purple-600 dark:text-purple-400">${{ number_format($contract->current_monthly_amount ?? 0, 0) }}/mo</span>
                                            @endif
                                        </flux:text>
                                        <flux:text size="xs" class="text-gray-500 dark:text-gray-400">{{ $contract->currency }}</flux:text>
                                    </flux:table.cell>
                                    
                                    <flux:table.cell>
                                        <flux:badge 
                                            color="{{ $contract->status === 'active' ? 'emerald' : 
                                                   ($contract->status === 'pending_signature' ? 'amber' : 
                                                   ($contract->status === 'under_negotiation' ? 'blue' : 
                                                   ($contract->status === 'expired' ? 'red' : 'zinc'))) }}"
                                            size="sm"
                                        >
                                            {{ ucwords(str_replace('_', ' ', $contract->status)) }}
                                        </flux:badge>
                                    </flux:table.cell>
                                    
                                    <flux:table.cell>
                                        <div class="flex items-center">
                                            <flux:text size="xs" class="mr-2">{{ $contract->completion_percentage ?? 25 }}%</flux:text>
                                            <div class="w-16 h-2 bg-gray-200 dark:bg-gray-700 rounded-full">
                                                <div class="h-2 rounded-full 
                                                    {{ $contract->status === 'active' ? 'bg-emerald-500' : '' }}
                                                    {{ $contract->status === 'pending_signature' ? 'bg-amber-500' : '' }}
                                                    {{ $contract->status === 'under_negotiation' ? 'bg-blue-500' : '' }}
                                                    {{ $contract->status === \App\Domains\Contract\Models\Contract::STATUS_DRAFT ? 'bg-gray-400' : '' }}"
                                                    style="width: {{ $contract->completion_percentage ?? 25 }}%">
                                                </div>
                                            </div>
                                        </div>
                                    </flux:table.cell>
                                    
                                    <flux:table.cell @click.stop>
                                        <flux:dropdown>
                                            <flux:button slot="trigger" variant="ghost" icon="ellipsis-vertical" size="sm" />
                                            <flux:menu>
                                                <flux:menu.item icon="eye" href="/financial/contracts/{{ $contract->id }}">View Details</flux:menu.item>
                                                @if($contract->canBeEdited())
                                                    <flux:menu.item icon="pencil" href="/financial/contracts/{{ $contract->id }}/edit">Edit</flux:menu.item>
                                                @endif
                                                <flux:menu.item icon="arrow-down-tray" href="/financial/contracts/{{ $contract->id }}/pdf" target="_blank">Download PDF</flux:menu.item>
                                                @can('delete', $contract)
                                                    @if($contract->status === \App\Domains\Contract\Models\Contract::STATUS_DRAFT)
                                                        <flux:separator />
                                                        <flux:menu.item icon="trash" variant="danger" @click="deleteContract({{ $contract->id }}, @js($contract->title))">Delete</flux:menu.item>
                                                    @endif
                                                @endcan
                                            </flux:menu>
                                        </flux:dropdown>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
                
                <!-- Timeline View -->
                <div x-show="viewMode === 'timeline'" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform translate-y-4"
                     x-transition:enter-end="opacity-100 transform translate-y-0">
                    <flux:card>
                        <flux:heading size="lg" class="mb-6">Contract Timeline</flux:heading>
                        <div class="space-y-4">
                            @foreach($contracts->sortBy('start_date') as $contract)
                                <div class="flex items-center space-x-4 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer" 
                                     @click="window.location.href='/financial/contracts/{{ $contract->id }}'">
                                    <div class="w-4 h-4 rounded-full flex-shrink-0
                                        {{ $contract->status === 'active' ? 'bg-emerald-500' : '' }}
                                        {{ $contract->status === 'pending_signature' ? 'bg-amber-500' : '' }}
                                        {{ $contract->status === 'under_negotiation' ? 'bg-blue-500' : '' }}
                                        {{ $contract->status === \App\Domains\Contract\Models\Contract::STATUS_DRAFT ? 'bg-gray-400' : '' }}
                                        {{ $contract->status === 'expired' ? 'bg-red-500' : '' }}">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <flux:text class="font-medium truncate">{{ $contract->title }}</flux:text>
                                        <flux:text size="xs" class="text-gray-500 dark:text-gray-400">
                                            {{ $contract->client->name ?? 'No Client' }} • {{ $contract->start_date ? $contract->start_date->format('M j, Y') : 'No date' }}
                                        </flux:text>
                                    </div>
                                    <flux:text class="font-medium">${{ number_format($contract->contract_value, 0) }}</flux:text>
                                </div>
                            @endforeach
                        </div>
                    </flux:card>
                </div>
                
                <!-- Pagination -->
                <div class="mt-6 flex items-center justify-between">
                    <flux:text size="sm" class="text-gray-700 dark:text-gray-300">
                        Showing {{ $contracts->firstItem() ?? 0 }} to {{ $contracts->lastItem() ?? 0 }} of {{ $contracts->total() }} contracts
                    </flux:text>
                    
                    {{ $contracts->withQueryString()->links() }}
                </div>
            @else
                <!-- Empty State -->
                <flux:card class="text-center py-16">
                    <div class="mx-auto w-24 h-24 bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-900/30 dark:to-purple-900/30 rounded-full flex items-center justify-center mb-6">
                        <flux:icon name="document-text" class="w-12 h-12 text-blue-600 dark:text-blue-400" />
                    </div>
                    <flux:heading size="xl" class="mb-2">No contracts found</flux:heading>
                    <flux:text class="max-w-md mx-auto mb-8">
                        @if(request()->hasAny(['status', 'contract_type', 'value_range', 'search', 'client_id']))
                            No contracts match your current filters. Try adjusting your search criteria or clearing filters.
                        @else
                            Start building your contract portfolio with intelligent templates and automated workflows.
                        @endif
                    </flux:text>
                    <div class="flex items-center justify-center space-x-4">
                        @if(request()->hasAny(['status', 'contract_type', 'value_range', 'search', 'client_id']))
                            <flux:button @click="clearFilters()" variant="ghost" icon="x-mark">
                                Clear Filters
                            </flux:button>
                        @endif
                        <flux:button href="/financial/contracts/create" variant="primary" icon="plus">
                            Create Your First Contract
                        </flux:button>
                    </div>
                </flux:card>
            @endif
        </div>
    </div>
</div>

<script>
function contractDashboard() {
    return {
        viewMode: 'cards',
        searchQuery: '',
        activeFilter: '',
        selectedContracts: [],
        sortBy: 'updated_at-desc',
        
        search() {
            console.log('Searching for:', this.searchQuery);
            // In real implementation, this would make an API call
        },
        
        setQuickFilter(filter) {
            this.activeFilter = this.activeFilter === filter ? '' : filter;
            this.applyFilters();
        },
        
        filterByStatus(status) {
            this.activeFilter = 'status:' + status;
            this.applyFilters();
        },
        
        clearFilters() {
            this.activeFilter = '';
            this.searchQuery = '';
            window.location.href = '/financial/contracts';
        },
        
        showExpiringContracts() {
            this.activeFilter = 'expiring_soon';
            this.applyFilters();
        },
        
        applyFilters() {
            let params = new URLSearchParams();
            
            if (this.searchQuery) {
                params.set('search', this.searchQuery);
            }
            
            if (this.activeFilter) {
                if (this.activeFilter.startsWith('status:')) {
                    params.set('status', this.activeFilter.replace('status:', ''));
                } else {
                    params.set('filter', this.activeFilter);
                }
            }
            
            if (this.sortBy) {
                params.set('sort', this.sortBy);
            }
            
            window.location.href = '/financial/contracts?' + params.toString();
        },
        
        applySorting() {
            this.applyFilters();
        },
        
        resetAdvancedFilters() {
            const form = document.getElementById('contractFilters');
            if (form) {
                form.reset();
            }
        },
        
        quickEdit(contractId) {
            window.location.href = `/financial/contracts/${contractId}/edit`;
        },
        
        viewDetails(contractId) {
            window.location.href = `/financial/contracts/${contractId}`;
        },
        
        sendForSignature(contractId) {
            if (confirm('Send this contract for signature?')) {
                fetch(`/api/contracts/${contractId}/send-signature`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.showNotification('Contract sent for signature successfully!', 'success');
                        location.reload();
                    } else {
                        this.showNotification('Error: ' + (data.message || 'Unknown error'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.showNotification('Error sending for signature', 'error');
                });
            }
        },
        
        deleteContract(contractId, contractTitle) {
            if (confirm(`Are you sure you want to delete "${contractTitle}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/financial/contracts/${contractId}`;
                
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                form.appendChild(csrfToken);
                
                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';
                form.appendChild(methodInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        },
        
        showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300 ${
                type === 'success' ? 'bg-green-500 text-white' : 
                type === 'error' ? 'bg-red-500 text-white' : 
                'bg-blue-500 text-white'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 5000);
        }
    };
}

// Keyboard shortcuts
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('keydown', function(e) {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('input[x-model="searchQuery"]');
            if (searchInput) {
                searchInput.focus();
            }
        }
        
        if (e.key === 'Escape') {
            const dashboardData = Alpine.$data(document.querySelector('[x-data="contractDashboard()"]'));
            if (dashboardData) {
                dashboardData.clearFilters();
            }
        }
    });
    
    const selects = document.querySelectorAll('#contractFilters select');
    selects.forEach(select => {
        select.addEventListener('change', function() {
            document.getElementById('contractFilters').submit();
        });
    });
});
</script>

@endsection