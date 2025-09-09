@extends('layouts.app')

@php
$activeDomain = 'financial';
$activeItem = 'contracts';
@endphp

@section('title', 'Usage Dashboard - ' . $contract->title)

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="usageDashboard(@json($contract), @json($billingData))">
    <!-- Header -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Usage Tracking Dashboard</h1>
                <p class="text-gray-600 mt-1">Monitor billing calculations and usage patterns for: <strong>{{ $contract->title }}</strong></p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="refreshData" 
                        :disabled="refreshing"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <svg x-show="!refreshing" class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <svg x-show="refreshing" class="animate-spin w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="m100 50c0 28-22 50-50 50s-50-22-50-50 22-50 50-50"></path>
                    </svg>
                    <span x-text="refreshing ? 'Refreshing...' : 'Refresh Data'"></span>
                </button>
                <a href="{{ route('financial.contracts.show', $contract) }}" 
                   class="px-6 py-2 text-gray-600 hover:text-gray-800 transition-colors">
                    Back to Contract
                </a>
            </div>
        </div>
        
        <!-- Contract Summary -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-blue-700">Billing Model</p>
                        <p class="font-medium text-blue-900">{{ ucwords(str_replace('_', ' ', $contract->billing_model)) }}</p>
                    </div>
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-green-700">Current MRR</p>
                        <p class="font-medium text-green-900" x-text="'$' + (billingData.current_mrr || 0).toFixed(2)"></p>
                    </div>
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
            </div>
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-purple-700">Assigned Assets</p>
                        <p class="font-medium text-purple-900" x-text="billingData.assigned_assets_count || 0"></p>
                    </div>
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-orange-700">Assigned Contacts</p>
                        <p class="font-medium text-orange-900" x-text="billingData.assigned_contacts_count || 0"></p>
                    </div>
                    <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM9 3a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Time Period Selector -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-medium text-gray-900">Billing Period Analysis</h2>
            <div class="flex items-center gap-3">
                <select x-model="selectedPeriod" @change="loadPeriodData" 
                        class="px-6 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="current_month">Current Month</option>
                    <option value="last_month">Last Month</option>
                    <option value="last_3_months">Last 3 Months</option>
                    <option value="last_6_months">Last 6 Months</option>
                    <option value="year_to_date">Year to Date</option>
                </select>
                <button @click="generateInvoice" 
                        :disabled="!canGenerateInvoice"
                        :class="canGenerateInvoice ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-400 cursor-not-allowed'"
                        class="px-6 py-2 text-white rounded-lg transition-colors">
                    Generate Invoice
                </button>
            </div>
        </div>

        <!-- Period Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600" x-text="'$' + (periodData.total_amount || 0).toFixed(2)"></div>
                    <div class="text-sm text-gray-600">Total Amount</div>
                </div>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600" x-text="'$' + (periodData.asset_billing || 0).toFixed(2)"></div>
                    <div class="text-sm text-gray-600">Asset Billing</div>
                </div>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600" x-text="'$' + (periodData.contact_billing || 0).toFixed(2)"></div>
                    <div class="text-sm text-gray-600">Contact Billing</div>
                </div>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600" x-text="periodData.calculation_count || 0"></div>
                    <div class="text-sm text-gray-600">Calculations</div>
                </div>
            </div>
        </div>

        <!-- Usage Trend Chart -->
        <div class="bg-gray-50 rounded-lg p-6">
            <h3 class="font-medium text-gray-900 mb-6">Billing Trend</h3>
            <div class="h-64 flex items-center justify-center">
                <canvas x-ref="usageChart" class="w-full h-full"></canvas>
            </div>
        </div>
    </div>

    <!-- Asset Usage Breakdown -->
    <div x-show="['per_asset', 'hybrid'].includes(contract.billing_model)" class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-medium text-gray-900">Asset Usage Breakdown</h2>
            <a href="{{ route('financial.contracts.asset-assignments', $contract) }}" 
               class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                Manage Assets
            </a>
        </div>

        <!-- Asset Type Summary -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <template x-for="(data, assetType) in assetBreakdown" :key="assetType">
                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="text-center">
                        <div class="text-xl font-bold text-gray-900" x-text="data.count"></div>
                        <div class="text-sm text-gray-600 capitalize" x-text="assetType.replace('_', ' ')"></div>
                        <div class="text-sm text-green-600 mt-1" x-text="'$' + data.monthly_amount.toFixed(2) + '/mo'"></div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Asset Details Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset</th>
                        <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Date</th>
                        <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monthly Rate</th>
                        <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="asset in assetDetails" :key="asset.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-6 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900" x-text="asset.hostname || asset.name"></div>
                                <div class="text-sm text-gray-500" x-text="asset.ip_address"></div>
                            </td>
                            <td class="px-6 py-6 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                      :class="getAssetTypeClass(asset.asset_type)"
                                      x-text="asset.asset_type.replace('_', ' ')"></span>
                            </td>
                            <td class="px-6 py-6 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                      :class="asset.is_online ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                      x-text="asset.is_online ? 'Online' : 'Offline'"></span>
                            </td>
                            <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-900" x-text="formatDate(asset.assigned_date)"></td>
                            <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-900" x-text="'$' + asset.monthly_rate.toFixed(2)"></td>
                            <td class="px-6 py-6 whitespace-nowrap text-sm font-medium text-gray-900" x-text="'$' + asset.period_total.toFixed(2)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Contact Usage Breakdown -->
    <div x-show="['per_contact', 'hybrid'].includes(contract.billing_model)" class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-medium text-gray-900">Contact Usage Breakdown</h2>
            <a href="{{ route('financial.contracts.contact-assignments', $contract) }}" 
               class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                Manage Contacts
            </a>
        </div>

        <!-- Access Tier Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <template x-for="tier in contactTierBreakdown" :key="tier.id">
                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="text-center">
                        <div class="text-xl font-bold text-gray-900" x-text="tier.contact_count"></div>
                        <div class="text-sm text-gray-600" x-text="tier.name"></div>
                        <div class="text-sm text-purple-600 mt-1" x-text="'$' + tier.rate.toFixed(2) + '/contact'"></div>
                        <div class="text-sm text-green-600" x-text="'$' + tier.total_amount.toFixed(2) + '/mo'"></div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Contact Details Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Access Tier</th>
                        <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Portal Usage</th>
                        <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Date</th>
                        <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monthly Rate</th>
                        <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="contact in contactDetails" :key="contact.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-6 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900" x-text="contact.name"></div>
                                <div class="text-sm text-gray-500" x-text="contact.email"></div>
                            </td>
                            <td class="px-6 py-6 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800"
                                      x-text="contact.access_tier_name"></span>
                            </td>
                            <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-900">
                                <div x-text="contact.login_count + ' logins'"></div>
                                <div class="text-xs text-gray-500" x-text="'Last: ' + formatDate(contact.last_login)"></div>
                            </td>
                            <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-900" x-text="formatDate(contact.assigned_date)"></td>
                            <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-900" x-text="'$' + contact.monthly_rate.toFixed(2)"></td>
                            <td class="px-6 py-6 whitespace-nowrap text-sm font-medium text-gray-900" x-text="'$' + contact.period_total.toFixed(2)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Billing Calculations History -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-medium text-gray-900">Billing Calculations History</h2>
            <div class="flex items-center gap-3">
                <select x-model="calculationFilters.status" @change="filterCalculations" 
                        class="px-6 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All Status</option>
                    <option value="calculated">Calculated</option>
                    <option value="invoiced">Invoiced</option>
                    <option value="paid">Paid</option>
                    <option value="disputed">Disputed</option>
                </select>
                <button @click="recalculateBilling" 
                        class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                    Recalculate Current Period
                </button>
            </div>
        </div>

        <!-- Calculations Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                        <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Calculation Date</th>
                        <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Billing</th>
                        <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Billing</th>
                        <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                        <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="calculation in filteredCalculations" :key="calculation.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-900" x-text="calculation.billing_period"></td>
                            <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-900" x-text="formatDate(calculation.calculated_at)"></td>
                            <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-900" x-text="'$' + calculation.asset_billing_amount.toFixed(2)"></td>
                            <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-900" x-text="'$' + calculation.contact_billing_amount.toFixed(2)"></td>
                            <td class="px-6 py-6 whitespace-nowrap text-sm font-medium text-gray-900" x-text="'$' + calculation.total_amount.toFixed(2)"></td>
                            <td class="px-6 py-6 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                      :class="getCalculationStatusClass(calculation.status)"
                                      x-text="calculation.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())"></span>
                            </td>
                            <td class="px-6 py-6 whitespace-nowrap text-sm font-medium">
                                <button @click="viewCalculationDetails(calculation)" 
                                        class="text-blue-600 hover:text-blue-900 mr-3">View Details</button>
                                <button x-show="calculation.status === 'calculated'" 
                                        @click="markAsInvoiced(calculation)" 
                                        class="text-green-600 hover:text-green-900">Mark Invoiced</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Empty State -->
        <div x-show="filteredCalculations.length === 0" class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No billing calculations found</h3>
            <p class="mt-1 text-sm text-gray-500">Start tracking usage by running the first calculation.</p>
            <button @click="recalculateBilling" 
                    class="mt-6 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Run First Calculation
            </button>
        </div>
    </div>

    <!-- Calculation Details Modal -->
    <div x-show="showDetailsModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div @click.away="showDetailsModal = false"
             class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-6 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Billing Calculation Details</h3>
            </div>
            <div class="p-6">
                <div x-show="selectedCalculation">
                    <!-- Calculation details content would go here -->
                    <div class="text-center py-8 text-gray-500">
                        <p>Detailed breakdown of billing calculation</p>
                        <p class="text-sm mt-2">Asset assignments, contact assignments, rates, and totals</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-6 border-t border-gray-200 flex justify-end">
                <button @click="showDetailsModal = false" 
                        class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function usageDashboard(contract, billingData) {
    return {
        contract: contract,
        billingData: billingData || {},
        selectedPeriod: 'current_month',
        periodData: {},
        refreshing: false,
        showDetailsModal: false,
        selectedCalculation: null,
        
        calculationFilters: {
            status: ''
        },
        
        assetBreakdown: {},
        assetDetails: [],
        contactTierBreakdown: [],
        contactDetails: [],
        filteredCalculations: [],
        
        get canGenerateInvoice() {
            return this.periodData.total_amount > 0 && this.periodData.calculation_count > 0;
        },
        
        init() {
            this.loadInitialData();
            this.loadPeriodData();
            this.initializeChart();
        },
        
        loadInitialData() {
            // Load asset breakdown
            this.assetBreakdown = this.billingData.asset_breakdown || {};
            this.assetDetails = this.billingData.asset_details || [];
            
            // Load contact breakdown
            this.contactTierBreakdown = this.billingData.contact_tier_breakdown || [];
            this.contactDetails = this.billingData.contact_details || [];
            
            // Load calculations
            this.filteredCalculations = this.billingData.calculations || [];
            this.filterCalculations();
        },
        
        async loadPeriodData() {
            try {
                const response = await fetch(`/api/contracts/${this.contract.id}/billing-period/${this.selectedPeriod}`);
                const data = await response.json();
                
                if (data.success) {
                    this.periodData = data.data;
                    this.updateChart();
                }
            } catch (error) {
                console.error('Error loading period data:', error);
            }
        },
        
        async refreshData() {
            this.refreshing = true;
            
            try {
                const response = await fetch(`/api/contracts/${this.contract.id}/usage-dashboard`);
                const data = await response.json();
                
                if (data.success) {
                    this.billingData = data.data;
                    this.loadInitialData();
                    this.loadPeriodData();
                }
            } catch (error) {
                console.error('Error refreshing data:', error);
            } finally {
                this.refreshing = false;
            }
        },
        
        filterCalculations() {
            let calculations = this.billingData.calculations || [];
            
            if (this.calculationFilters.status) {
                calculations = calculations.filter(calc => calc.status === this.calculationFilters.status);
            }
            
            this.filteredCalculations = calculations;
        },
        
        getAssetTypeClass(assetType) {
            const classes = {
                'workstation': 'bg-blue-100 text-blue-800',
                'server': 'bg-green-100 text-green-800',
                'network_device': 'bg-purple-100 text-purple-800',
                'mobile_device': 'bg-yellow-100 text-yellow-800'
            };
            return classes[assetType] || 'bg-gray-100 text-gray-800';
        },
        
        getCalculationStatusClass(status) {
            const classes = {
                'calculated': 'bg-blue-100 text-blue-800',
                'invoiced': 'bg-green-100 text-green-800',
                'paid': 'bg-emerald-100 text-emerald-800',
                'disputed': 'bg-red-100 text-red-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },
        
        formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString();
        },
        
        viewCalculationDetails(calculation) {
            this.selectedCalculation = calculation;
            this.showDetailsModal = true;
        },
        
        async markAsInvoiced(calculation) {
            try {
                const response = await fetch(`/api/contracts/${this.contract.id}/billing-calculations/${calculation.id}/mark-invoiced`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    calculation.status = 'invoiced';
                    this.filterCalculations();
                } else {
                    alert('Error marking as invoiced: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error marking as invoiced:', error);
                alert('Error marking as invoiced');
            }
        },
        
        async recalculateBilling() {
            try {
                const response = await fetch(`/api/contracts/${this.contract.id}/recalculate-billing`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.refreshData();
                } else {
                    alert('Error recalculating billing: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error recalculating billing:', error);
                alert('Error recalculating billing');
            }
        },
        
        async generateInvoice() {
            if (!this.canGenerateInvoice) return;
            
            try {
                const response = await fetch(`/api/contracts/${this.contract.id}/generate-invoice`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        period: this.selectedPeriod
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Redirect to invoice or show success message
                    window.open(result.invoice_url, '_blank');
                } else {
                    alert('Error generating invoice: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error generating invoice:', error);
                alert('Error generating invoice');
            }
        },
        
        initializeChart() {
            // Chart initialization would go here
            // This would use Chart.js or similar library
            console.log('Chart initialized');
        },
        
        updateChart() {
            // Chart update would go here
            console.log('Chart updated with period data');
        }
    }
}
</script>
@endsection
