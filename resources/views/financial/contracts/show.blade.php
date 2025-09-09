@extends('layouts.app')

@section('title', 'Contract Details - ' . $contract->title)



@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900" x-data="{ activeTab: 'overview', mobileMenuOpen: false, contract: @js($contract->only(['id', 'title', 'status', 'contract_number', 'contract_value'])) }">
    <!-- Modern Contract Header -->
    <x-contracts.layout.header 
        :contract="$contract" 
        :title="$contract->title"
        :subtitle="'Contract #' . $contract->contract_number"
    />

    <!-- Tab Navigation -->
    <x-contracts.interactive.tab-navigation 
        :contract="$contract"
        active-tab="overview"
    />

    <!-- Tab Content -->
        <!-- Overview Tab -->
        <div x-show="activeTab === 'overview'" class="max-w-7xl mx-auto px-6 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:flex-1 px-6-span-2 space-y-8">
                    <!-- Contract Overview Card -->
                    <x-content-card>
                        <div class="space-y-6">
                            <div class="flex items-center justify-between">
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Contract Overview</h2>
                                @if($contract->canBeEdited())
                                    <a href="{{ route('financial.contracts.edit', $contract) }}" 
                                       class="inline-flex items-center px-6 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Edit
                                    </a>
                                @endif
                            </div>

                            <!-- Contract Details Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Contract Type</label>
                                        <p class="text-base text-gray-900 dark:text-gray-100 mt-1">
                                            {{ ucwords(str_replace('_', ' ', $contract->contract_type)) }}
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Start Date</label>
                                        <p class="text-base text-gray-900 dark:text-gray-100 mt-1">
                                            {{ $contract->start_date ? $contract->start_date->format('M d, Y') : 'Not set' }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Contract Value</label>
                                        <p class="text-xl font-semibold text-green-600 dark:text-green-400 mt-1">
                                            {{ $contract->getFormattedValue() }}
                                        </p>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</label>
                                        <div class="mt-1">
                                            <x-contracts.display.status-badge :status="$contract->status" size="md" />
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">End Date</label>
                                        <p class="text-base text-gray-900 dark:text-gray-100 mt-1">
                                            {{ $contract->end_date ? $contract->end_date->format('M d, Y') : 'No end date' }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Duration</label>
                                        <p class="text-base text-gray-900 dark:text-gray-100 mt-1">
                                            @if($contract->getDurationMonths())
                                                {{ $contract->getDurationMonths() }} {{ Str::plural('month', $contract->getDurationMonths()) }}
                                            @else
                                                Ongoing
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Description -->
                            @if($contract->description)
                                <div>
                                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</label>
                                    <p class="text-base text-gray-900 dark:text-gray-100 mt-1">{{ $contract->description }}</p>
                                </div>
                            @endif
                        </div>
                    </x-content-card>

                    <!-- Progress and Metrics -->
                    @if($contract->isActive())
                        <x-content-card>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">Contract Progress</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Time Progress -->
                                <div class="text-center">
                                    @php
                                    $totalDays = $contract->start_date && $contract->end_date 
                                        ? $contract->start_date->diffInDays($contract->end_date) 
                                        : null;
                                    $elapsedDays = $contract->start_date 
                                        ? $contract->start_date->diffInDays(now()) 
                                        : 0;
                                    $progressPercent = $totalDays && $totalDays > 0 
                                        ? min(100, ($elapsedDays / $totalDays) * 100) 
                                        : 0;
                                    @endphp
                                    <div class="relative w-20 h-20 mx-auto mb-2">
                                        <svg class="w-20 h-20 transform -rotate-90">
                                            <circle cx="40" cy="40" r="36" stroke="currentColor" stroke-width="8" fill="none" class="text-gray-200 dark:text-gray-700"/>
                                            <circle cx="40" cy="40" r="36" stroke="currentColor" stroke-width="8" fill="none" 
                                                    stroke-dasharray="{{ 2 * pi() * 36 }}" 
                                                    stroke-dashoffset="{{ 2 * pi() * 36 * (1 - $progressPercent / 100) }}"
                                                    class="text-blue-500 dark:text-blue-400"/>
                                        </svg>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ round($progressPercent) }}%</span>
                                        </div>
                                    </div>
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Time Elapsed</p>
                                </div>

                                <!-- Performance Score -->
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                                        {{ number_format($contract->getPerformanceScore(), 1) }}%
                                    </div>
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Performance Score</p>
                                </div>

                                <!-- Monthly Revenue -->
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                                        ${{ number_format($contract->getMonthlyRecurringRevenue(), 0) }}
                                    </div>
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Monthly Revenue</p>
                                </div>
                            </div>
                        </x-content-card>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Client Information -->
                    <x-content-card>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">Client Information</h3>
                        <div class="space-y-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-2m-2 0H7m5 0v-9a2 2 0 00-2-2H6a2 2 0 00-2 2v9"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $contract->client->name ?? 'No Client' }}</p>
                                    @if($contract->client)
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $contract->client->email ?? '' }}</p>
                                    @endif
                                </div>
                            </div>
                            
                            @if($contract->client)
                                <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                                    <a href="{{ route('clients.show', $contract->client) }}" 
                                       class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">
                                        View Client Details →
                                    </a>
                                </div>
                            @endif
                        </div>
                    </x-content-card>

                    <!-- Quick Actions -->
                    <x-content-card>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">Quick Actions</h3>
                        <div class="space-y-2">
                            <a href="{{ route('financial.contracts.pdf', $contract) }}" 
                               target="_blank"
                               class="w-full inline-flex items-center justify-center px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Download PDF
                            </a>
                            
                            <button type="button" 
                                    onclick="window.print()"
                                    class="w-full inline-flex items-center justify-center px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                Print Contract
                            </button>
                            
                            <a href="{{ route('financial.contracts.duplicate', $contract) }}" 
                               class="w-full inline-flex items-center justify-center px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                Duplicate Contract
                            </a>
                        </div>
                    </x-content-card>

                    <!-- Contract Metrics -->
                    @if($contract->isActive())
                        <x-content-card>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">Contract Metrics</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Remaining Days</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ $contract->getRemainingDays() ?? '∞' }}
                                    </span>
                                </div>
                                
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Annual Value</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">
                                        ${{ number_format($contract->getAnnualValue(), 2) }}
                                    </span>
                                </div>
                                
                                @if($contract->supportedAssets)
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Supported Assets</span>
                                        <span class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $contract->supportedAssets->count() }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </x-content-card>
                    @endif
                </div>
            </div>
        </div>

        <!-- Schedules Tab -->
        <div x-show="activeTab === 'schedules'" class="max-w-7xl mx-auto px-6 sm:px-6 lg:px-8 py-8">
            <div class="space-y-8">
                @if($contract->schedules && $contract->schedules->count() > 0)
                    @foreach($contract->schedules as $schedule)
                        <x-contracts.specialized.schedule-display 
                            :schedule="$schedule"
                            :contract="$contract"
                            :type="$schedule->schedule_type"
                            :editable="$contract->canBeEdited()"
                            :expanded="$loop->first"
                        />
                    @endforeach
                @else
                    <!-- No Schedules -->
                    <x-content-card>
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h2a2 2 0 002-2z"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No Schedules</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">This contract doesn't have any schedules attached yet.</p>
                            @if($contract->canBeEdited())
                                <div class="mt-6">
                                    <button type="button" 
                                            class="inline-flex items-center px-6 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                        Add Schedule
                                    </button>
                                </div>
                            @endif
                        </div>
                    </x-content-card>
                @endif
            </div>
        </div>

        <!-- Billing Tab -->
        <div x-show="activeTab === 'billing'" class="max-w-7xl mx-auto px-6 sm:px-6 lg:px-8 py-8">
            <div class="space-y-8">
                <!-- Billing Overview -->
                <x-content-card>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">Billing Overview</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-6">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                ${{ number_format($contract->getMonthlyRecurringRevenue(), 2) }}
                            </div>
                            <div class="text-sm text-green-700 dark:text-green-300">Monthly Recurring Revenue</div>
                        </div>
                        
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                ${{ number_format($contract->getAnnualValue(), 2) }}
                            </div>
                            <div class="text-sm text-blue-700 dark:text-blue-300">Annual Contract Value</div>
                        </div>
                        
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-6">
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                {{ $contract->invoices->count() }}
                            </div>
                            <div class="text-sm text-purple-700 dark:text-purple-300">Total Invoices</div>
                        </div>
                    </div>
                </x-content-card>

                <!-- Asset Assignment Summary -->
                <x-content-card>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">Asset Assignment Summary</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ $contract->getAssignedAssetCount() }}
                            </div>
                            <div class="text-sm text-blue-700 dark:text-blue-300">Assets Assigned</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $contract->hasAutoAssignmentEnabled() ? 'Auto-assignment enabled' : 'Manual assignment only' }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                Asset Management
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                @if($contract->getSupportedAssetTypes())
                                    {{ count($contract->getSupportedAssetTypes()) }} asset types covered
                                @else
                                    No specific asset types configured
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                Coverage Scope
                            </div>
                        </div>
                    </div>
                    
                    @if($contract->getSupportedAssetTypes())
                        <div class="mt-6">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-6">Supported Asset Types</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($contract->getSupportedAssetTypes() as $assetType)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        {{ ucfirst(str_replace('_', ' ', $assetType)) }}
                                        <span class="ml-1 text-blue-600 dark:text-blue-300">
                                            ({{ $contract->supportedAssets()->where('type', $assetType)->count() }})
                                        </span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </x-content-card>

                <!-- Recent Invoices -->
                @if($contract->invoices && $contract->invoices->count() > 0)
                    <x-content-card>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">Recent Invoices</h3>
                        <div class="overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Invoice</th>
                                        <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($contract->invoices->take(5) as $invoice)
                                        <tr>
                                            <td class="px-6 py-6 whitespace-nowrap text-sm font-medium text-blue-600 dark:text-blue-400">
                                                <a href="{{ route('financial.invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a>
                                            </td>
                                            <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ $invoice->invoice_date->format('M d, Y') }}
                                            </td>
                                            <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                ${{ number_format($invoice->total_amount, 2) }}
                                            </td>
                                            <td class="px-6 py-6 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                    @if($invoice->status === 'paid') bg-green-100 text-green-800 
                                                    @elseif($invoice->status === 'pending') bg-yellow-100 text-yellow-800 
                                                    @else bg-red-100 text-red-800 @endif">
                                                    {{ ucfirst($invoice->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-content-card>
                @endif
            </div>
        </div>

        <!-- History Tab -->
        <div x-show="activeTab === 'history'" class="max-w-7xl mx-auto px-6 sm:px-6 lg:px-8 py-8">
            <x-content-card>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">Contract History</h3>
                
                @php $history = $contract->getAuditHistory(); @endphp
                
                @if(count($history) > 0)
                    <div class="flow-root">
                        <ul class="-mb-8">
                            @foreach($history as $event)
                                <li>
                                    <div class="relative pb-8 {{ !$loop->last ? '' : '' }}">
                                        @if(!$loop->last)
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-gray-900 
                                                    @if($event['type'] === 'creation') bg-blue-500 
                                                    @elseif($event['type'] === 'signature') bg-green-500 
                                                    @elseif($event['type'] === 'amendment') bg-yellow-500 
                                                    @elseif($event['type'] === 'termination') bg-red-500 
                                                    @else bg-gray-500 @endif">
                                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        @if($event['icon'] === 'plus')
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                        @elseif($event['icon'] === 'signature')
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                        @elseif($event['icon'] === 'check')
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                        @elseif($event['icon'] === 'times')
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                        @else
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        @endif
                                                    </svg>
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div>
                                                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ $event['description'] }}</p>
                                                    @if(isset($event['details']))
                                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $event['details'] }}</p>
                                                    @endif
                                                    @if(isset($event['reason']))
                                                        <p class="text-sm text-gray-500 dark:text-gray-400">Reason: {{ $event['reason'] }}</p>
                                                    @endif
                                                </div>
                                                <div class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                                    <div>{{ $event['date']->format('M d, Y') }}</div>
                                                    <div>{{ $event['date']->format('g:i A') }}</div>
                                                    <div class="text-xs">{{ $event['user'] }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No History</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No events have been recorded for this contract yet.</p>
                    </div>
                @endif
            </x-content-card>
        </div>
</div>

@endsection
