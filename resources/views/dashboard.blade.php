@extends('layouts.app')

@section('content')
<div 
    x-data="modernDashboard()" 
    x-init="init()"
    class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-all duration-500"
>
    <!-- Dashboard Header -->
    <header class="sticky top-0 z-40 bg-white dark:bg-gray-800/80 dark:bg-slate-900/80 backdrop-blur-sm shadow-sm border-b border-gray-200 dark:border-gray-700/60 dark:border-slate-700/60">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <!-- Title & Time -->
                <div class="flex items-center space-x-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white">
                            @php
                                $workflowTitles = [
                                    'urgent' => 'Urgent Items Dashboard',
                                    'today' => "Today's Work Dashboard",
                                    'scheduled' => 'Scheduled Work Dashboard',
                                    'financial' => 'Financial Dashboard',
                                    'reports' => 'Reports Dashboard',
                                    'default' => 'Executive Dashboard'
                                ];
                                $currentWorkflow = $workflow ?? $workflowView ?? 'default';
                            @endphp
                            {{ $workflowTitles[$currentWorkflow] ?? 'Executive Dashboard' }}
                        </h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            @if($selectedClient ?? null)
                                <span class="font-medium">{{ $selectedClient->name }}</span> •
                            @endif
                            <span x-text="currentTime"></span>
                        </p>
                    </div>
                </div>

                <!-- Header Actions -->
                <div class="flex items-center space-x-4">
                    <!-- Auto Refresh Toggle -->
                    <button 
                        @click="toggleAutoRefresh()"
                        :class="autoRefresh ? 'bg-emerald-500 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'"
                        class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 hover:scale-105 focus:ring-4 focus:ring-emerald-500/20"
                    >
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4" :class="autoRefresh && 'animate-spin'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span x-text="autoRefresh ? 'Auto Refresh On' : 'Auto Refresh Off'"></span>
                        </div>
                    </button>

                    <!-- Dark Mode Toggle -->
                    <button 
                        @click="toggleDarkMode()"
                        class="p-3 rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-600 transition-all duration-200 hover:scale-105 focus:ring-4 focus:ring-slate-500/20"
                    >
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                    </button>

                    <!-- Export Menu -->
                    <div class="relative" x-data="{ open: false }">
                        <button 
                            @click="open = !open"
                            class="p-3 rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 text-white hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 hover:scale-105 focus:ring-4 focus:ring-blue-500/20 shadow-lg"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </button>

                        <div 
                            x-show="open" 
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            @click.outside="open = false"
                            class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-2 z-50"
                        >
                            <button @click="exportData('json'); open = false" class="w-full px-4 py-2 text-left text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">Export JSON</button>
                            <button @click="exportData('csv'); open = false" class="w-full px-4 py-2 text-left text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">Export CSV</button>
                            <button @click="exportData('excel'); open = false" class="w-full px-4 py-2 text-left text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">Export Excel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Dashboard Content -->
    <main class="px-6 py-8">
        @php
            $currentWorkflow = $workflow ?? $workflowView ?? 'default';
        @endphp
        
        @if($currentWorkflow === 'urgent' && isset($workflowData))
            <!-- Urgent Workflow Content -->
            @include('dashboard.workflows.urgent', ['data' => $workflowData, 'kpis' => $kpis ?? [], 'alerts' => $alerts ?? []])
        @elseif($currentWorkflow === 'today' && isset($workflowData))
            <!-- Today's Work Content -->
            @include('dashboard.workflows.today', ['data' => $workflowData, 'kpis' => $kpis ?? []])
        @elseif($currentWorkflow === 'scheduled' && isset($workflowData))
            <!-- Scheduled Work Content -->
            @include('dashboard.workflows.scheduled', ['data' => $workflowData, 'kpis' => $kpis ?? []])
        @elseif($currentWorkflow === 'financial' && isset($workflowData))
            <!-- Financial Workflow Content -->
            @include('dashboard.workflows.financial', ['data' => $workflowData, 'kpis' => $kpis ?? []])
        @elseif($currentWorkflow === 'reports' && isset($workflowData))
            <!-- Reports Workflow Content -->
            @include('dashboard.workflows.reports', ['data' => $workflowData, 'kpis' => $kpis ?? []])
        @else
            <!-- Default Executive Dashboard -->
        @endif
        
        <!-- KPI Cards Grid -->
        <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8" role="region" aria-label="Key Performance Indicators">
            <!-- Total Revenue Card -->
            <article class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-[1.02] focus-within:ring-4 focus-within:ring-emerald-500/20" tabindex="0" aria-label="Total Revenue Statistics">
                <!-- Decorative Pattern -->
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-emerald-400/20 backdrop-blur-sm transition-all duration-300 group-hover:scale-110 group-hover:bg-emerald-300/30"></div>
                <div class="absolute -right-8 -top-8 h-16 w-16 rounded-full bg-emerald-300/10"></div>
                
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <!-- Icon Container -->
                        <div class="rounded-xl bg-emerald-400/20 backdrop-blur-sm p-3 transition-all duration-300 group-hover:bg-emerald-300/30 group-hover:scale-110">
                            <svg class="h-6 w-6 text-emerald-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <!-- Value Display -->
                        <div class="text-right">
                            <div class="text-xs font-medium text-emerald-100/80 mb-1">Total Revenue</div>
                            <div class="text-2xl font-bold tracking-tight" x-text="formatCurrency(kpis.monthly_revenue || 0)">$0</div>
                        </div>
                    </div>
                    <!-- Status Indicator -->
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center text-xs bg-emerald-400/20 backdrop-blur-sm px-3 py-1.5 rounded-full text-emerald-100 font-medium">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                            Monthly
                        </span>
                        <span class="text-xs text-emerald-100/70">Current month</span>
                    </div>
                </div>
            </article>

            <!-- Pending Invoices Card -->
            <article class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-[1.02] focus-within:ring-4 focus-within:ring-blue-500/20" tabindex="0" aria-label="Pending Invoices Statistics">
                <!-- Decorative Pattern -->
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-blue-400/20 backdrop-blur-sm transition-all duration-300 group-hover:scale-110 group-hover:bg-blue-300/30"></div>
                <div class="absolute -right-8 -top-8 h-16 w-16 rounded-full bg-blue-300/10"></div>
                
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <!-- Icon Container -->
                        <div class="rounded-xl bg-blue-400/20 backdrop-blur-sm p-3 transition-all duration-300 group-hover:bg-blue-300/30 group-hover:scale-110">
                            <svg class="h-6 w-6 text-blue-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <!-- Value Display -->
                        <div class="text-right">
                            <div class="text-xs font-medium text-blue-100/80 mb-1">Pending Invoices</div>
                            <div class="text-2xl font-bold tracking-tight" x-text="formatCurrency(kpis.pending_invoices_amount || 0)">$0</div>
                        </div>
                    </div>
                    <!-- Status Indicator -->
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center text-xs bg-blue-400/20 backdrop-blur-sm px-3 py-1.5 rounded-full text-blue-100 font-medium">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span x-text="(kpis.pending_invoices_amount || 0).toLocaleString()">0</span>
                        </span>
                        <span class="text-xs text-blue-100/70">outstanding</span>
                    </div>
                </div>
            </article>

            <!-- Active Clients Card -->
            <article class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 to-pink-600 p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-[1.02] focus-within:ring-4 focus-within:ring-purple-500/20" tabindex="0" aria-label="Active Clients Statistics">
                <!-- Decorative Pattern -->
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-purple-400/20 backdrop-blur-sm transition-all duration-300 group-hover:scale-110 group-hover:bg-purple-300/30"></div>
                <div class="absolute -right-8 -top-8 h-16 w-16 rounded-full bg-purple-300/10"></div>
                
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <!-- Icon Container -->
                        <div class="rounded-xl bg-purple-400/20 backdrop-blur-sm p-3 transition-all duration-300 group-hover:bg-purple-300/30 group-hover:scale-110">
                            <svg class="h-6 w-6 text-purple-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <!-- Value Display -->
                        <div class="text-right">
                            <div class="text-xs font-medium text-purple-100/80 mb-1">Active Clients</div>
                            <div class="text-2xl font-bold tracking-tight" x-text="kpis.total_clients || 0">0</div>
                        </div>
                    </div>
                    <!-- Status Indicator -->
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center text-xs bg-purple-400/20 backdrop-blur-sm px-3 py-1.5 rounded-full text-purple-100 font-medium">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Stable
                        </span>
                        <span class="text-xs text-purple-100/70">vs last month</span>
                    </div>
                </div>
            </article>

            <!-- Open Tickets Card -->
            <article class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-orange-500 to-red-600 p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-[1.02] focus-within:ring-4 focus-within:ring-orange-500/20" tabindex="0" aria-label="Open Tickets Statistics">
                <!-- Decorative Pattern -->
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-orange-400/20 backdrop-blur-sm transition-all duration-300 group-hover:scale-110 group-hover:bg-orange-300/30"></div>
                <div class="absolute -right-8 -top-8 h-16 w-16 rounded-full bg-orange-300/10"></div>
                
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <!-- Icon Container -->
                        <div class="rounded-xl bg-orange-400/20 backdrop-blur-sm p-3 transition-all duration-300 group-hover:bg-orange-300/30 group-hover:scale-110">
                            <svg class="h-6 w-6 text-orange-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <!-- Value Display -->
                        <div class="text-right">
                            <div class="text-xs font-medium text-orange-100/80 mb-1">Open Tickets</div>
                            <div class="text-2xl font-bold tracking-tight" x-text="kpis.open_tickets || 0">0</div>
                        </div>
                    </div>
                    <!-- Status Indicator -->
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center text-xs bg-orange-400/20 backdrop-blur-sm px-3 py-1.5 rounded-full text-orange-100 font-medium">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span x-text="kpis.overdue_invoices || 0">0</span> overdue
                        </span>
                        <span class="text-xs text-orange-100/70">invoices pending</span>
                    </div>
                </div>
            </article>
        </section>

        <!-- Charts Grid -->
        <section class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-8">
            <!-- Revenue Chart -->
            <div class="bg-white dark:bg-gray-800 dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-6 hover:shadow-xl transition-all duration-300">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Revenue Trends</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Monthly performance overview</p>
                    </div>
                    <div class="flex space-x-2">
                        <span class="px-3 py-1 text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-full">
                            Live Data
                        </span>
                    </div>
                </div>
                <div class="relative h-80">
                    @if(empty($revenueChartData['data']) || array_sum($revenueChartData['data'] ?? []) == 0)
                        <div class="flex items-center justify-center h-full">
                            <div class="text-center">
                                <svg class="w-16 h-16 text-slate-300 dark:text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                <p class="text-slate-500 dark:text-slate-400">No revenue data yet</p>
                                <p class="text-sm text-slate-400 dark:text-slate-500 mt-1">Start creating invoices to see trends</p>
                            </div>
                        </div>
                    @else
                        <canvas id="revenueChart" class="w-full h-full"></canvas>
                    @endif
                </div>
            </div>

            <!-- Ticket Distribution Chart -->
            <div class="bg-white dark:bg-gray-800 dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-6 hover:shadow-xl transition-all duration-300">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Support Overview</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Ticket status distribution</p>
                    </div>
                    <div class="flex space-x-2">
                        <span class="px-3 py-1 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full">
                            Real-time
                        </span>
                    </div>
                </div>
                <div class="relative h-80">
                    @if(empty($ticketChartData['data']) || array_sum($ticketChartData['data'] ?? []) == 0)
                        <div class="flex items-center justify-center h-full">
                            <div class="text-center">
                                <svg class="w-16 h-16 text-slate-300 dark:text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                </svg>
                                <p class="text-slate-500 dark:text-slate-400">No ticket data yet</p>
                                <p class="text-sm text-slate-400 dark:text-slate-500 mt-1">Tickets will appear here once created</p>
                            </div>
                        </div>
                    @else
                        <canvas id="ticketsChart" class="w-full h-full"></canvas>
                    @endif
                </div>
            </div>
        </section>

        <!-- Quick Actions -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="{{ route('clients.create') }}" 
               class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-8 text-white shadow-lg hover:shadow-2xl transition-all duration-300 hover:scale-[1.02]">
                <div class="absolute -right-6 -top-6 h-32 w-32 rounded-full bg-white dark:bg-gray-800/10 group-hover:bg-white dark:bg-gray-800/20 transition-all duration-300"></div>
                <div class="relative">
                    <div class="mb-4">
                        <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold mb-2">Add New Client</h4>
                    <p class="text-emerald-100 text-sm">Create a new client profile and start managing their services</p>
                    <div class="mt-4 flex items-center">
                        <span class="text-sm font-medium">Get Started</span>
                        <svg class="ml-2 h-4 w-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </div>
            </a>

            <a href="{{ route('financial.invoices.create') }}" 
               class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 p-8 text-white shadow-lg hover:shadow-2xl transition-all duration-300 hover:scale-[1.02]">
                <div class="absolute -right-6 -top-6 h-32 w-32 rounded-full bg-white dark:bg-gray-800/10 group-hover:bg-white dark:bg-gray-800/20 transition-all duration-300"></div>
                <div class="relative">
                    <div class="mb-4">
                        <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold mb-2">Create Invoice</h4>
                    <p class="text-blue-100 text-sm">Generate professional invoices and track payments</p>
                    <div class="mt-4 flex items-center">
                        <span class="text-sm font-medium">Create Now</span>
                        <svg class="ml-2 h-4 w-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </div>
            </a>

            <a href="{{ route('reports.index') }}" 
               class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 to-pink-600 p-8 text-white shadow-lg hover:shadow-2xl transition-all duration-300 hover:scale-[1.02]">
                <div class="absolute -right-6 -top-6 h-32 w-32 rounded-full bg-white dark:bg-gray-800/10 group-hover:bg-white dark:bg-gray-800/20 transition-all duration-300"></div>
                <div class="relative">
                    <div class="mb-4">
                        <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold mb-2">View Reports</h4>
                    <p class="text-purple-100 text-sm">Access detailed analytics and business intelligence reports</p>
                    <div class="mt-4 flex items-center">
                        <span class="text-sm font-medium">Explore</span>
                        <svg class="ml-2 h-4 w-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </div>
            </a>
        </section>
    </main>

    <!-- Loading Overlay -->
    <div 
        x-show="loading" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50"
    >
        <div class="bg-white dark:bg-gray-800 dark:bg-slate-800 rounded-2xl p-8 flex items-center space-x-4 shadow-2xl">
            <div class="animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
            <span class="text-slate-900 dark:text-white font-medium">Loading dashboard data...</span>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Pass server-side data to the dashboard component
window.dashboardData = {
    stats: @json($stats ?? []),
    revenueChartData: @json($revenueChartData ?? []),
    ticketChartData: @json($ticketChartData ?? []),
    routes: {
        realtime: '{{ route('dashboard.realtime') }}',
        export: '{{ route('dashboard.export') }}'
    }
};
</script>
@endpush