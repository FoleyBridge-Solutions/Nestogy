@extends('layouts.app')

@section('title', 'Contract Management')

@push('styles')
<style>
.contract-card {
    transition: all 0.3s ease;
    border-left: 4px solid #e5e7eb;
}
.contract-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}
.contract-card.status-active { border-left-color: #10b981; }
.contract-card.status-pending_signature { border-left-color: #f59e0b; }
.contract-card.status-draft { border-left-color: #6b7280; }
.contract-card.status-under_negotiation { border-left-color: #3b82f6; }
.contract-card.status-expired { border-left-color: #ef4444; }
.contract-card.status-terminated { border-left-color: #1f2937; }

.progress-ring {
    transform: rotate(-90deg);
}

.quick-filter-btn {
    transition: all 0.2s ease;
}
.quick-filter-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    transform: scale(1.05);
}

.bulk-actions {
    transform: translateY(-100%);
    transition: transform 0.3s ease;
}
.bulk-actions.show {
    transform: translateY(0);
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in-up {
    animation: fadeInUp 0.6s ease forwards;
}
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100" x-data="contractDashboard()">
    <!-- Modern Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Contract Management</h1>
                    <p class="mt-1 text-lg text-gray-600">Intelligent contract lifecycle management</p>
                </div>
                <div class="flex items-center space-x-3">
                    <!-- AI Assistant Button -->
                    <button class="inline-flex items-center px-4 py-2 border border-purple-300 rounded-lg text-sm font-medium text-purple-700 bg-purple-50 hover:bg-purple-100 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        AI Assistant
                    </button>
                    
                    <!-- Template Library -->
                    <a href="/contracts/templates" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        Template Library
                    </a>
                    
                    <!-- Create Contract Button -->
                    <a href="/financial/contracts/create" 
                       class="inline-flex items-center px-6 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 transform hover:scale-105 transition-all duration-200 shadow-lg">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        New Contract
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Enhanced Stats Dashboard -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow cursor-pointer" @click="filterByStatus('')">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-gray-900">{{ $statistics['total_contracts'] ?? 0 }}</div>
                        <div class="text-sm text-gray-600">Total Contracts</div>
                    </div>
                    <div class="p-2 bg-gray-100 rounded-lg">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow cursor-pointer" @click="filterByStatus('active')">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-emerald-600">{{ $statistics['active_contracts'] ?? 0 }}</div>
                        <div class="text-sm text-gray-600">Active</div>
                    </div>
                    <div class="p-2 bg-emerald-100 rounded-lg">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow cursor-pointer" @click="filterByStatus('pending_signature')">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-amber-600">{{ $statistics['pending_signature'] ?? 0 }}</div>
                        <div class="text-sm text-gray-600">Pending Signature</div>
                    </div>
                    <div class="p-2 bg-amber-100 rounded-lg">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow cursor-pointer" @click="filterByStatus('draft')">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-gray-600">{{ $statistics['draft_contracts'] ?? 0 }}</div>
                        <div class="text-sm text-gray-600">Drafts</div>
                    </div>
                    <div class="p-2 bg-gray-100 rounded-lg">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-green-600">${{ number_format(($statistics['total_value'] ?? 0) / 1000, 0) }}k</div>
                        <div class="text-sm text-gray-600">Total Value</div>
                    </div>
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-blue-600">${{ number_format(($statistics['monthly_revenue'] ?? 0) / 1000, 0) }}k</div>
                        <div class="text-sm text-gray-600">Monthly Revenue</div>
                    </div>
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow" @click="showExpiringContracts()">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-red-600">{{ $statistics['expiring_soon'] ?? 0 }}</div>
                        <div class="text-sm text-gray-600">Expiring Soon</div>
                    </div>
                    <div class="p-2 bg-red-100 rounded-lg">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-purple-600">{{ number_format($statistics['renewal_rate'] ?? 0, 0) }}%</div>
                        <div class="text-sm text-gray-600">Renewal Rate</div>
                    </div>
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Smart Control Bar -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8" x-data="{ showAdvancedFilters: false, selectedContracts: [] }">
            <!-- Main Controls -->
            <div class="p-6">
                <!-- Top Row: Search and Quick Filters -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0 mb-6">
                    <!-- AI-Powered Search -->
                    <div class="flex-1 max-w-2xl">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input type="text" 
                                   x-model="searchQuery"
                                   @input.debounce.300ms="search()"
                                   class="block w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" 
                                   placeholder="Search by contract name, client, or ask AI: 'Show me contracts expiring this month'...">
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center">
                                <div class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-md">⌘K</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- View Toggle -->
                    <div class="flex items-center space-x-2">
                        <div class="bg-gray-100 rounded-lg p-1 flex">
                            <button @click="viewMode = 'cards'" 
                                    :class="viewMode === 'cards' ? 'bg-white shadow-sm' : 'hover:bg-gray-200'"
                                    class="px-3 py-2 rounded-md text-sm font-medium transition-all duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                </svg>
                            </button>
                            <button @click="viewMode = 'list'" 
                                    :class="viewMode === 'list' ? 'bg-white shadow-sm' : 'hover:bg-gray-200'"
                                    class="px-3 py-2 rounded-md text-sm font-medium transition-all duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                </svg>
                            </button>
                            <button @click="viewMode = 'timeline'" 
                                    :class="viewMode === 'timeline' ? 'bg-white shadow-sm' : 'hover:bg-gray-200'"
                                    class="px-3 py-2 rounded-md text-sm font-medium transition-all duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Action Filters -->
                <div class="flex flex-wrap gap-2 mb-4">
                    <button @click="setQuickFilter('needs_attention')" 
                            :class="activeFilter === 'needs_attention' ? 'quick-filter-btn active' : 'quick-filter-btn'"
                            class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 hover:border-gray-400 bg-white">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.966-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        Needs My Attention
                        <span class="ml-2 px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">3</span>
                    </button>
                    
                    <button @click="setQuickFilter('expiring_soon')" 
                            :class="activeFilter === 'expiring_soon' ? 'quick-filter-btn active' : 'quick-filter-btn'"
                            class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 hover:border-gray-400 bg-white">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Expiring Soon
                        <span class="ml-2 px-2 py-1 bg-amber-100 text-amber-800 rounded-full text-xs">{{ $statistics['expiring_soon'] ?? 0 }}</span>
                    </button>
                    
                    <button @click="setQuickFilter('pending_signature')" 
                            :class="activeFilter === 'pending_signature' ? 'quick-filter-btn active' : 'quick-filter-btn'"
                            class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 hover:border-gray-400 bg-white">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Pending Signature
                        <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">{{ $statistics['pending_signature'] ?? 0 }}</span>
                    </button>
                    
                    <button @click="setQuickFilter('draft')" 
                            :class="activeFilter === 'draft' ? 'quick-filter-btn active' : 'quick-filter-btn'"
                            class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 hover:border-gray-400 bg-white">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                        My Drafts
                        <span class="ml-2 px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">{{ $statistics['draft_contracts'] ?? 0 }}</span>
                    </button>
                    
                    <button @click="setQuickFilter('high_value')" 
                            :class="activeFilter === 'high_value' ? 'quick-filter-btn active' : 'quick-filter-btn'"
                            class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 hover:border-gray-400 bg-white">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                        </svg>
                        High Value (>$50k)
                    </button>
                    
                    <button @click="clearFilters()" 
                            x-show="activeFilter !== ''"
                            class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 hover:border-red-400 bg-white text-red-600 hover:text-red-700">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Clear Filters
                    </button>
                </div>
                
                <!-- Advanced Filters Toggle -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button @click="showAdvancedFilters = !showAdvancedFilters" 
                                class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 focus:outline-none"
                                :class="{ 'text-blue-600': showAdvancedFilters }">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"/>
                            </svg>
                            Advanced Filters
                        </button>
                        
                        <!-- Sort Options -->
                        <select x-model="sortBy" @change="applySorting()"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                            <option value="updated_at-desc">Recently Updated</option>
                            <option value="created_at-desc">Newest First</option>
                            <option value="contract_value-desc">Highest Value</option>
                            <option value="end_date-asc">Expiring Soon</option>
                            <option value="client_name-asc">Client A-Z</option>
                            <option value="status-asc">Status</option>
                        </select>
                    </div>
                    
                    <!-- Bulk Actions -->
                    <div x-show="selectedContracts.length > 0" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600" x-text="selectedContracts.length + ' contract(s) selected'"></span>
                        <button class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Bulk Actions
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Advanced Filters Panel -->
            <div x-show="showAdvancedFilters" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform -translate-y-2"
                 class="border-t border-gray-200 p-6 bg-gray-50 rounded-b-xl">
                <form id="contractFilters" method="GET">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Status Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                <option value="">All Statuses</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="pending_signature" {{ request('status') == 'pending_signature' ? 'selected' : '' }}>Pending Signature</option>
                                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="under_negotiation" {{ request('status') == 'under_negotiation' ? 'selected' : '' }}>Under Negotiation</option>
                                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                            </select>
                        </div>
                        
                        <!-- Contract Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Contract Type</label>
                            <select name="contract_type" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                <option value="">All Types</option>
                                <option value="service_agreement" {{ request('contract_type') == 'service_agreement' ? 'selected' : '' }}>Service Agreement</option>
                                <option value="maintenance_agreement" {{ request('contract_type') == 'maintenance_agreement' ? 'selected' : '' }}>Maintenance Agreement</option>
                                <option value="support_contract" {{ request('contract_type') == 'support_contract' ? 'selected' : '' }}>Support Contract</option>
                                <option value="professional_services" {{ request('contract_type') == 'professional_services' ? 'selected' : '' }}>Professional Services</option>
                            </select>
                        </div>
                        
                        <!-- Client Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Client</label>
                            <select name="client_id" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                <option value="">All Clients</option>
                                @foreach($clients ?? [] as $client)
                                    <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Value Range -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Value Range</label>
                            <select name="value_range" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                <option value="">All Values</option>
                                <option value="0-5000" {{ request('value_range') == '0-5000' ? 'selected' : '' }}>$0 - $5,000</option>
                                <option value="5000-25000" {{ request('value_range') == '5000-25000' ? 'selected' : '' }}>$5,000 - $25,000</option>
                                <option value="25000-100000" {{ request('value_range') == '25000-100000' ? 'selected' : '' }}>$25,000 - $100,000</option>
                                <option value="100000+" {{ request('value_range') == '100000+' ? 'selected' : '' }}>$100,000+</option>
                            </select>
                        </div>
                        
                        <!-- Date Range -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Start Date From</label>
                            <input type="date" name="date_from" 
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white" 
                                   value="{{ request('date_from') }}">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Start Date To</label>
                            <input type="date" name="date_to" 
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white" 
                                   value="{{ request('date_to') }}">
                        </div>
                        
                        <!-- Template Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Template</label>
                            <select name="template_id" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                <option value="">All Templates</option>
                                <option value="custom">Custom Contracts</option>
                                <!-- Template options would be populated from backend -->
                            </select>
                        </div>
                        
                        <!-- Billing Model Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Billing Model</label>
                            <select name="billing_model" class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                <option value="">All Models</option>
                                <option value="fixed">Fixed Price</option>
                                <option value="per_asset">Per Asset</option>
                                <option value="per_contact">Per Contact</option>
                                <option value="hybrid">Hybrid</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" @click="resetAdvancedFilters()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Reset
                        </button>
                        <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transform hover:scale-105 transition-all duration-200">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Visual Contract Cards -->
        <div class="contract-workspace">
            @if($contracts->count() > 0)
                <!-- Contract Cards Grid -->
                <div x-show="viewMode === 'cards'" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
                    @foreach($contracts as $contract)
                        <div class="contract-card bg-white rounded-xl shadow-sm border border-gray-200 status-{{ $contract->status }} fade-in-up hover:shadow-lg" 
                             style="animation-delay: {{ $loop->index * 0.1 }}s"
                             x-data="{ selected: false }"
                             @click="window.location.href='/financial/contracts/{{ $contract->id }}'">
                            
                            <!-- Card Header -->
                            <div class="p-6 pb-4">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center mb-2">
                                            <!-- Progress Ring -->
                                            <div class="relative w-12 h-12 mr-4">
                                                <svg class="progress-ring w-12 h-12" viewBox="0 0 42 42">
                                                    <circle class="stroke-current text-gray-200" stroke-width="4" fill="transparent" r="15.915" cx="21" cy="21"/>
                                                    <circle class="progress-ring stroke-current 
                                                        @if($contract->status === 'active') text-emerald-500
                                                        @elseif($contract->status === 'pending_signature') text-amber-500
                                                        @elseif($contract->status === 'draft') text-gray-400
                                                        @elseif($contract->status === 'under_negotiation') text-blue-500
                                                        @elseif($contract->status === 'expired') text-red-500
                                                        @else text-gray-400 @endif" 
                                                        stroke-width="4" fill="transparent" r="15.915" cx="21" cy="21"
                                                        stroke-dasharray="{{ $contract->completion_percentage ?? 25 }} 75"
                                                        stroke-linecap="round"/>
                                                </svg>
                                                <div class="absolute inset-0 flex items-center justify-center">
                                                    <span class="text-xs font-bold text-gray-700">{{ $contract->completion_percentage ?? 25 }}%</span>
                                                </div>
                                            </div>
                                            
                                            <!-- Contract Info -->
                                            <div class="flex-1 min-w-0">
                                                <h3 class="text-lg font-semibold text-gray-900 truncate mb-1">
                                                    {{ $contract->title }}
                                                </h3>
                                                <p class="text-sm text-gray-600 truncate">
                                                    {{ $contract->client->name ?? 'No Client Assigned' }}
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <!-- Status Badge -->
                                        <div class="flex items-center justify-between">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium 
                                                @if($contract->status === 'active') bg-emerald-100 text-emerald-800
                                                @elseif($contract->status === 'pending_signature') bg-amber-100 text-amber-800
                                                @elseif($contract->status === 'under_negotiation') bg-blue-100 text-blue-800
                                                @elseif($contract->status === 'draft') bg-gray-100 text-gray-800
                                                @elseif($contract->status === 'expired') bg-red-100 text-red-800
                                                @elseif($contract->status === 'terminated') bg-gray-100 text-gray-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                @if($contract->status === 'active') ✅ 
                                                @elseif($contract->status === 'pending_signature') ✍️
                                                @elseif($contract->status === 'under_negotiation') 💬
                                                @elseif($contract->status === 'draft') 📝
                                                @elseif($contract->status === 'expired') ⏰
                                                @else 📄 @endif
                                                {{ ucwords(str_replace('_', ' ', $contract->status)) }}
                                            </span>
                                            
                                            <!-- Checkbox for bulk actions -->
                                            <label class="inline-flex items-center" @click.stop>
                                                <input type="checkbox" 
                                                       x-model="selectedContracts" 
                                                       value="{{ $contract->id }}"
                                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Contract Details Grid -->
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Contract Value</div>
                                        <div class="text-lg font-bold text-gray-900">
                                            @if($contract->billing_model === 'fixed')
                                                ${{ number_format($contract->contract_value, 0) }}
                                            @else
                                                <span class="text-purple-600">${{ number_format($contract->current_monthly_amount ?? 0, 0) }}/mo</span>
                                                <div class="text-xs text-gray-500">{{ ucwords(str_replace('_', ' ', $contract->billing_model)) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Duration</div>
                                        <div class="text-sm font-medium text-gray-700">
                                            {{ $contract->start_date ? $contract->start_date->format('M j') : 'TBD' }} - 
                                            {{ $contract->end_date ? $contract->end_date->format('M j, Y') : 'Ongoing' }}
                                            @if($contract->end_date && $contract->end_date->diffInDays(now()) <= 30)
                                                <span class="text-red-600 text-xs">⚠️ Expires soon</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Contract Type & Template Info -->
                                <div class="flex items-center justify-between text-xs mb-4">
                                    <div class="flex items-center space-x-2">
                                        <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded-md">
                                            {{ ucwords(str_replace('_', ' ', $contract->contract_type)) }}
                                        </span>
                                        @if($contract->template)
                                            <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded-md flex items-center">
                                                🤖 {{ $contract->template->name }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-gray-500">
                                        Updated {{ $contract->updated_at->diffForHumans() }}
                                    </div>
                                </div>
                                
                                <!-- Progress Indicators -->
                                @if($contract->milestones_count > 0 || $contract->approvals_count > 0 || $contract->signatures_count > 0)
                                    <div class="flex items-center space-x-4 text-xs text-gray-600 mb-4">
                                        @if($contract->milestones_count > 0)
                                            <div class="flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                {{ $contract->completed_milestones_count ?? 0 }}/{{ $contract->milestones_count }} milestones
                                            </div>
                                        @endif
                                        @if($contract->approvals_count > 0)
                                            <div class="flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                {{ $contract->approved_count ?? 0 }}/{{ $contract->approvals_count }} approvals
                                            </div>
                                        @endif
                                        @if($contract->signatures_count > 0)
                                            <div class="flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                {{ $contract->signed_count ?? 0 }}/{{ $contract->signatures_count }} signatures
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Card Footer with Actions -->
                            <div class="px-6 py-4 bg-gray-50 rounded-b-xl border-t border-gray-100">
                                <div class="flex items-center justify-between">
                                    <!-- Left side - Key info -->
                                    <div class="flex items-center space-x-3 text-sm text-gray-600">
                                        @if($contract->billing_model !== 'fixed')
                                            <div class="flex items-center">
                                                <svg class="w-4 h-4 mr-1 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                                </svg>
                                                {{ $contract->asset_assignments_count + $contract->contact_assignments_count }} assignments
                                            </div>
                                        @endif
                                        @if($contract->renewal_date)
                                            <div class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                                Renews {{ $contract->renewal_date->format('M j') }}
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <!-- Right side - Quick Actions -->
                                    <div class="flex items-center space-x-2" @click.stop>
                                        @if($contract->status === 'draft')
                                            <button @click="quickEdit({{ $contract->id }})" 
                                                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700 transition-colors">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                Edit
                                            </button>
                                        @elseif($contract->status === 'pending_signature')
                                            <button @click="sendForSignature({{ $contract->id }})" 
                                                    class="inline-flex items-center px-3 py-1.5 bg-amber-600 text-white text-xs font-medium rounded-md hover:bg-amber-700 transition-colors">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                Send
                                            </button>
                                        @elseif($contract->status === 'under_negotiation')
                                            <button @click="openNegotiation({{ $contract->id }})" 
                                                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700 transition-colors">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                                </svg>
                                                Negotiate
                                            </button>
                                        @elseif($contract->status === 'active')
                                            <button @click="viewDetails({{ $contract->id }})" 
                                                    class="inline-flex items-center px-3 py-1.5 bg-emerald-600 text-white text-xs font-medium rounded-md hover:bg-emerald-700 transition-colors">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                View
                                            </button>
                                        @endif
                                        
                                        <!-- More Actions Dropdown -->
                                        <div class="relative" x-data="{ open: false }">
                                            <button @click="open = !open" 
                                                    class="inline-flex items-center p-2 text-gray-400 hover:text-gray-600 rounded-md hover:bg-gray-200 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                                </svg>
                                            </button>
                                            
                                            <div x-show="open" @click.away="open = false"
                                                 x-transition:enter="transition ease-out duration-100"
                                                 x-transition:enter-start="transform opacity-0 scale-95"
                                                 x-transition:enter-end="transform opacity-100 scale-100"
                                                 x-transition:leave="transition ease-in duration-75"
                                                 x-transition:leave-start="transform opacity-100 scale-100"
                                                 x-transition:leave-end="transform opacity-0 scale-95"
                                                 class="absolute right-0 bottom-full mb-2 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-20">
                                                <div class="py-1">
                                                    <a href="/financial/contracts/{{ $contract->id }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">View Details</a>
                                                    @if($contract->canBeEdited())
                                                        <a href="/financial/contracts/{{ $contract->id }}/edit" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Edit Contract</a>
                                                    @endif
                                                    <a href="/financial/contracts/{{ $contract->id }}/pdf" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Download PDF</a>
                                                    <a href="/financial/contracts/{{ $contract->id }}/duplicate" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Duplicate</a>
                                                    @if($contract->billing_model !== 'fixed')
                                                        <a href="/financial/contracts/{{ $contract->id }}/usage-dashboard" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Usage Dashboard</a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- List View -->
                <div x-show="viewMode === 'list'" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform translate-y-4"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="min-w-full divide-y divide-gray-200">
                        <!-- Table Header -->
                        <div class="bg-gray-50 px-6 py-3">
                            <div class="grid grid-cols-12 gap-4 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="col-span-4">Contract</div>
                                <div class="col-span-2">Client</div>
                                <div class="col-span-2">Value</div>
                                <div class="col-span-2">Status</div>
                                <div class="col-span-1">Progress</div>
                                <div class="col-span-1">Actions</div>
                            </div>
                        </div>
                        
                        <!-- Table Body -->
                        <div class="bg-white divide-y divide-gray-200">
                            @foreach($contracts as $contract)
                                <div class="px-6 py-4 hover:bg-gray-50 cursor-pointer" @click="window.location.href='/financial/contracts/{{ $contract->id }}'">
                                    <div class="grid grid-cols-12 gap-4 items-center">
                                        <!-- Contract Info -->
                                        <div class="col-span-4">
                                            <div class="flex items-center">
                                                <input type="checkbox" x-model="selectedContracts" value="{{ $contract->id }}" @click.stop class="mr-3 rounded border-gray-300">
                                                <div>
                                                    <h4 class="text-sm font-medium text-gray-900 truncate">{{ $contract->title }}</h4>
                                                    <p class="text-xs text-gray-500">{{ ucwords(str_replace('_', ' ', $contract->contract_type)) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Client -->
                                        <div class="col-span-2">
                                            <div class="text-sm text-gray-900">{{ $contract->client->name ?? 'No Client' }}</div>
                                            <div class="text-xs text-gray-500">{{ $contract->start_date ? $contract->start_date->format('M j, Y') : 'No date' }}</div>
                                        </div>
                                        
                                        <!-- Value -->
                                        <div class="col-span-2">
                                            <div class="text-sm font-medium text-gray-900">
                                                @if($contract->billing_model === 'fixed')
                                                    ${{ number_format($contract->contract_value, 0) }}
                                                @else
                                                    <span class="text-purple-600">${{ number_format($contract->current_monthly_amount ?? 0, 0) }}/mo</span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500">{{ $contract->currency }}</div>
                                        </div>
                                        
                                        <!-- Status -->
                                        <div class="col-span-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                @if($contract->status === 'active') bg-emerald-100 text-emerald-800
                                                @elseif($contract->status === 'pending_signature') bg-amber-100 text-amber-800
                                                @elseif($contract->status === 'under_negotiation') bg-blue-100 text-blue-800
                                                @elseif($contract->status === 'draft') bg-gray-100 text-gray-800
                                                @elseif($contract->status === 'expired') bg-red-100 text-red-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ ucwords(str_replace('_', ' ', $contract->status)) }}
                                            </span>
                                        </div>
                                        
                                        <!-- Progress -->
                                        <div class="col-span-1">
                                            <div class="flex items-center">
                                                <div class="text-xs text-gray-900 font-medium mr-2">{{ $contract->completion_percentage ?? 25 }}%</div>
                                                <div class="w-8 h-2 bg-gray-200 rounded-full">
                                                    <div class="h-2 rounded-full 
                                                        @if($contract->status === 'active') bg-emerald-500
                                                        @elseif($contract->status === 'pending_signature') bg-amber-500
                                                        @elseif($contract->status === 'under_negotiation') bg-blue-500
                                                        @else bg-gray-400 @endif" 
                                                        style="width: {{ $contract->completion_percentage ?? 25 }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Actions -->
                                        <div class="col-span-1 text-right" @click.stop>
                                            <div class="relative" x-data="{ open: false }">
                                                <button @click="open = !open" class="text-gray-400 hover:text-gray-600">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                                    </svg>
                                                </button>
                                                
                                                <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-20">
                                                    <div class="py-1">
                                                        <a href="/financial/contracts/{{ $contract->id }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">View Details</a>
                                                        @if($contract->canBeEdited())
                                                            <a href="/financial/contracts/{{ $contract->id }}/edit" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Edit</a>
                                                        @endif
                                                        <a href="/financial/contracts/{{ $contract->id }}/pdf" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Download PDF</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                
                <!-- Timeline View -->
                <div x-show="viewMode === 'timeline'" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform translate-y-4"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Contract Timeline</h3>
                    <div class="space-y-4">
                        @foreach($contracts->sortBy('start_date') as $contract)
                            <div class="flex items-center space-x-4">
                                <div class="w-4 h-4 rounded-full 
                                    @if($contract->status === 'active') bg-emerald-500
                                    @elseif($contract->status === 'pending_signature') bg-amber-500
                                    @elseif($contract->status === 'under_negotiation') bg-blue-500
                                    @else bg-gray-400 @endif"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $contract->title }}</p>
                                    <p class="text-xs text-gray-500">{{ $contract->client->name ?? 'No Client' }} • {{ $contract->start_date ? $contract->start_date->format('M j, Y') : 'No date' }}</p>
                                </div>
                                <div class="text-sm font-medium text-gray-900">${{ number_format($contract->contract_value, 0) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Enhanced Pagination -->
                <div class="mt-8 flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing {{ $contracts->firstItem() ?? 0 }} to {{ $contracts->lastItem() ?? 0 }} of {{ $contracts->total() }} contracts
                    </div>
                    <div>
                        {{ $contracts->withQueryString()->links() }}
                    </div>
                </div>
            @else
                <!-- Enhanced Empty State -->
                <div class="text-center py-16 bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="mx-auto w-24 h-24 bg-gradient-to-br from-blue-100 to-purple-100 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-semibold text-gray-900 mb-4">No contracts found</h3>
                    <p class="text-lg text-gray-600 max-w-md mx-auto mb-8">
                        @if(request()->hasAny(['status', 'contract_type', 'value_range', 'search', 'client_id']))
                            No contracts match your current filters. Try adjusting your search criteria or clearing filters.
                        @else
                            Start building your contract portfolio with intelligent templates and automated workflows.
                        @endif
                    </p>
                    <div class="flex items-center justify-center space-x-4">
                        @if(request()->hasAny(['status', 'contract_type', 'value_range', 'search', 'client_id']))
                            <button @click="clearFilters()" class="inline-flex items-center px-6 py-3 border border-gray-300 rounded-lg text-base font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Clear Filters
                            </button>
                        @endif
                        <a href="/financial/contracts/create" 
                           class="inline-flex items-center px-8 py-3 border border-transparent rounded-lg text-base font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 transform hover:scale-105 transition-all duration-200 shadow-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Create Your First Contract
                        </a>
                    </div>
                </div>
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
        
        // Search functionality
        search() {
            // Implement AI-powered search
            console.log('Searching for:', this.searchQuery);
            // In real implementation, this would make an API call
        },
        
        // Filter functions
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
            // Build query parameters based on active filters
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
            
            // Apply filters
            window.location.href = '/financial/contracts?' + params.toString();
        },
        
        applySorting() {
            this.applyFilters();
        },
        
        resetAdvancedFilters() {
            // Reset all form fields
            const form = document.getElementById('contractFilters');
            if (form) {
                form.reset();
            }
        },
        
        // Quick actions
        quickEdit(contractId) {
            window.location.href = `/financial/contracts/${contractId}/edit`;
        },
        
        openNegotiation(contractId) {
            window.location.href = `/financial/contracts/${contractId}/negotiation`;
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
                        // Show success notification
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
        
        showNotification(message, type = 'info') {
            // Create and show a notification
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300 ${
                type === 'success' ? 'bg-green-500 text-white' : 
                type === 'error' ? 'bg-red-500 text-white' : 
                'bg-blue-500 text-white'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Remove after 5 seconds
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 5000);
        }
    };
}

// Legacy function for backward compatibility
function submitForApproval(contractId) {
    if (confirm('Are you sure you want to submit this contract for approval?')) {
        fetch(`/api/contracts/${contractId}/submit-approval`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error submitting for approval: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error submitting for approval');
        });
    }
}

// Keyboard shortcuts
document.addEventListener('DOMContentLoaded', function() {
    // Cmd/Ctrl + K for search focus
    document.addEventListener('keydown', function(e) {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('input[x-model="searchQuery"]');
            if (searchInput) {
                searchInput.focus();
            }
        }
        
        // Escape to clear filters
        if (e.key === 'Escape') {
            const dashboardData = Alpine.$data(document.querySelector('[x-data="contractDashboard()"]'));
            if (dashboardData) {
                dashboardData.clearFilters();
            }
        }
    });
    
    // Auto-submit form when filters change
    const selects = document.querySelectorAll('#contractFilters select');
    selects.forEach(select => {
        select.addEventListener('change', function() {
            document.getElementById('contractFilters').submit();
        });
    });
});
</script>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endpush
@endsection