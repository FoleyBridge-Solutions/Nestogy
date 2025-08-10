@extends('layouts.app')

@section('content')
<div 
    x-data="modernDashboard()" 
    x-init="init()"
    class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 transition-all duration-500"
>
    <!-- Dashboard Header -->
    <header class="sticky top-0 z-40 backdrop-blur-xl bg-white/80 dark:bg-slate-900/80 border-b border-slate-200/60 dark:border-slate-700/60">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <!-- Title & Time -->
                <div class="flex items-center space-x-6">
                    <div>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-slate-900 to-slate-600 dark:from-white dark:to-slate-300 bg-clip-text text-transparent">
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
                            class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-2 z-50"
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
        <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
            <!-- Total Revenue Card -->
            <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-[1.02]">
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-white/10"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="rounded-xl bg-white/20 p-3">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="text-right">
                            <div class="text-xs opacity-80">Total Revenue</div>
                            <div class="text-2xl font-bold" x-text="formatCurrency(kpis.totalRevenue?.value || 0)"></div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs bg-white/20 px-2 py-1 rounded-full" 
                              :class="(kpis.totalRevenue?.growth || 0) >= 0 ? 'text-emerald-100' : 'text-red-200'">
                            <span x-text="(kpis.totalRevenue?.growth || 0) >= 0 ? '↗' : '↘'"></span>
                            <span x-text="Math.abs(kpis.totalRevenue?.growth || 0) + '%'"></span>
                        </span>
                        <span class="text-xs opacity-80">vs last month</span>
                    </div>
                </div>
            </div>

            <!-- MRR Card -->
            <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-[1.02]">
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-white/10"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="rounded-xl bg-white/20 p-3">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="text-right">
                            <div class="text-xs opacity-80">Monthly Recurring</div>
                            <div class="text-2xl font-bold" x-text="formatCurrency(kpis.mrr?.value || 0)"></div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs bg-white/20 px-2 py-1 rounded-full" 
                              :class="(kpis.mrr?.growth || 0) >= 0 ? 'text-blue-100' : 'text-red-200'">
                            <span x-text="(kpis.mrr?.growth || 0) >= 0 ? '↗' : '↘'"></span>
                            <span x-text="Math.abs(kpis.mrr?.growth || 0) + '%'"></span>
                        </span>
                        <span class="text-xs opacity-80">vs last month</span>
                    </div>
                </div>
            </div>

            <!-- Active Clients Card -->
            <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 to-pink-600 p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-[1.02]">
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-white/10"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="rounded-xl bg-white/20 p-3">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div class="text-right">
                            <div class="text-xs opacity-80">Active Clients</div>
                            <div class="text-2xl font-bold" x-text="kpis.activeClients?.value || 0"></div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs bg-white/20 px-2 py-1 rounded-full" 
                              :class="(kpis.activeClients?.growth || 0) >= 0 ? 'text-purple-100' : 'text-red-200'">
                            <span x-text="(kpis.activeClients?.growth || 0) >= 0 ? '↗' : '↘'"></span>
                            <span x-text="Math.abs(kpis.activeClients?.growth || 0) + '%'"></span>
                        </span>
                        <span class="text-xs opacity-80">vs last month</span>
                    </div>
                </div>
            </div>

            <!-- Open Tickets Card -->
            <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-orange-500 to-red-600 p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-[1.02]">
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-white/10"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="rounded-xl bg-white/20 p-3">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <div class="text-right">
                            <div class="text-xs opacity-80">Open Tickets</div>
                            <div class="text-2xl font-bold" x-text="kpis.openTickets?.value || 0"></div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs bg-white/20 px-2 py-1 rounded-full" 
                              :class="(kpis.openTickets?.growth || 0) <= 0 ? 'text-green-200' : 'text-red-200'">
                            <span x-text="(kpis.openTickets?.growth || 0) <= 0 ? '↘' : '↗'"></span>
                            <span x-text="Math.abs(kpis.openTickets?.growth || 0) + '%'"></span>
                        </span>
                        <span class="text-xs opacity-80">vs last month</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Charts Grid -->
        <section class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-8">
            <!-- Revenue Chart -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-6 hover:shadow-xl transition-all duration-300">
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
                    <canvas id="revenueChart" class="w-full h-full"></canvas>
                </div>
            </div>

            <!-- Ticket Distribution Chart -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 p-6 hover:shadow-xl transition-all duration-300">
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
                    <canvas id="ticketsChart" class="w-full h-full"></canvas>
                </div>
            </div>
        </section>

        <!-- Quick Actions -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="{{ route('clients.create') }}" 
               class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-8 text-white shadow-lg hover:shadow-2xl transition-all duration-300 hover:scale-[1.02]">
                <div class="absolute -right-6 -top-6 h-32 w-32 rounded-full bg-white/10 group-hover:bg-white/20 transition-all duration-300"></div>
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
                <div class="absolute -right-6 -top-6 h-32 w-32 rounded-full bg-white/10 group-hover:bg-white/20 transition-all duration-300"></div>
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
                <div class="absolute -right-6 -top-6 h-32 w-32 rounded-full bg-white/10 group-hover:bg-white/20 transition-all duration-300"></div>
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
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 flex items-center space-x-4 shadow-2xl">
            <div class="animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
            <span class="text-slate-900 dark:text-white font-medium">Loading dashboard data...</span>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function modernDashboard() {
    return {
        // State
        loading: false,
        darkMode: localStorage.getItem('darkMode') === 'true' || false,
        currentTime: '',
        autoRefresh: true,
        refreshInterval: null,
        
        // Data
        kpis: @json($stats ?? []),
        charts: {
            revenue: null,
            tickets: null
        },
        
        // Initialize
        init() {
            this.updateCurrentTime();
            this.applyDarkMode();
            this.setupAutoRefresh();
            
            // Initialize charts
            this.$nextTick(() => {
                this.initCharts();
                this.loadRealtimeData();
            });
            
            setInterval(() => this.updateCurrentTime(), 1000);
        },
        
        // Time Management
        updateCurrentTime() {
            const now = new Date();
            this.currentTime = now.toLocaleDateString('en-US', { 
                weekday: 'long',
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        
        // Auto-refresh
        setupAutoRefresh() {
            if (this.autoRefresh) {
                this.refreshInterval = setInterval(() => {
                    this.loadRealtimeData(false);
                }, 30000);
            }
        },
        
        toggleAutoRefresh() {
            this.autoRefresh = !this.autoRefresh;
            if (this.autoRefresh) {
                this.setupAutoRefresh();
            } else if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
                this.refreshInterval = null;
            }
        },
        
        // Dark Mode
        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            this.applyDarkMode();
            localStorage.setItem('darkMode', this.darkMode);
        },
        
        applyDarkMode() {
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },
        
        // Data Loading
        async loadRealtimeData(showLoading = true) {
            if (showLoading) this.loading = true;
            
            try {
                const response = await fetch(`{{ route('dashboard.realtime') }}?type=all`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (!response.ok) throw new Error('Failed to fetch data');
                const data = await response.json();
                
                // Update KPIs
                this.kpis = data.stats || {};
                
                console.log('Dashboard data refreshed at:', new Date().toLocaleTimeString());
                
            } catch (error) {
                console.error('Error loading dashboard data:', error);
            } finally {
                if (showLoading) this.loading = false;
            }
        },
        
        // Charts
        initCharts() {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not available');
                return;
            }
            
            this.initRevenueChart();
            this.initTicketsChart();
        },
        
        initRevenueChart() {
            const ctx = document.getElementById('revenueChart');
            if (!ctx) return;
            
            const isDark = this.darkMode;
            
            this.charts.revenue = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($revenueChartData['labels'] ?? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']) !!},
                    datasets: [{
                        label: 'Revenue',
                        data: {!! json_encode($revenueChartData['data'] ?? [0, 0, 0, 0, 0, 0]) !!},
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 3,
                        pointBackgroundColor: 'rgb(34, 197, 94)',
                        pointBorderColor: 'white',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            borderColor: 'rgb(34, 197, 94)',
                            borderWidth: 1,
                            cornerRadius: 8,
                            displayColors: false,
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false,
                            },
                            ticks: {
                                color: isDark ? 'rgba(255, 255, 255, 0.6)' : 'rgba(0, 0, 0, 0.6)',
                                font: { size: 12 }
                            }
                        },
                        y: {
                            grid: {
                                color: isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false,
                            },
                            ticks: {
                                color: isDark ? 'rgba(255, 255, 255, 0.6)' : 'rgba(0, 0, 0, 0.6)',
                                font: { size: 12 },
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        },
        
        initTicketsChart() {
            const ctx = document.getElementById('ticketsChart');
            if (!ctx) return;
            
            this.charts.tickets = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Open', 'In Progress', 'Resolved', 'Closed'],
                    datasets: [{
                        data: {!! json_encode($ticketChartData['data'] ?? [0, 0, 0, 0]) !!},
                        backgroundColor: [
                            'rgb(239, 68, 68)',
                            'rgb(245, 158, 11)', 
                            'rgb(34, 197, 94)',
                            'rgb(107, 114, 128)'
                        ],
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: { size: 12 },
                                color: this.darkMode ? 'rgba(255, 255, 255, 0.8)' : 'rgba(0, 0, 0, 0.8)'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            cornerRadius: 8,
                        }
                    }
                }
            });
        },
        
        // Export Functionality
        async exportData(format) {
            this.loading = true;
            
            try {
                const response = await fetch(`{{ route('dashboard.export') }}?format=${format}&type=executive`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (!response.ok) throw new Error('Export failed');
                const data = await response.json();
                
                // Create download
                const blob = new Blob([JSON.stringify(data.data, null, 2)], { type: 'application/json' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = data.filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                this.showNotification(`Data exported as ${format.toUpperCase()}`, 'success');
                
            } catch (error) {
                console.error('Export error:', error);
                this.showNotification('Failed to export data', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        // Utility Functions
        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount || 0);
        },
        
        showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-xl shadow-2xl z-50 max-w-sm transform transition-all duration-300 ${
                type === 'success' ? 'bg-emerald-500 text-white' :
                type === 'error' ? 'bg-red-500 text-white' :
                'bg-blue-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        ${type === 'success' ? 
                            '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' :
                            type === 'error' ? 
                            '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>' :
                            '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
                        }
                    </div>
                    <p class="text-sm font-medium">${message}</p>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => notification.style.transform = 'translateX(0)', 10);
            
            // Remove after delay
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                notification.style.opacity = '0';
                setTimeout(() => document.body.removeChild(notification), 300);
            }, 3000);
        }
    };
}
</script>
@endpush