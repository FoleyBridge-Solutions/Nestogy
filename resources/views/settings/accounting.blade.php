@extends('layouts.settings')

@section('title', 'Accounting Integration Settings - Nestogy')

@section('settings-title', 'Accounting Integration Settings')
@section('settings-description', 'Configure QuickBooks, Xero, and other accounting software integrations')

@section('settings-content')
<div x-data="{ activeTab: 'platforms' }">
    <form method="POST" action="{{ route('settings.accounting.update') }}">
        @csrf
        @method('PUT')

        <!-- Tab Navigation -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6 pt-4">
                <button type="button" 
                        @click="activeTab = 'platforms'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'platforms', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'platforms'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Integration Platforms
                </button>
                <button type="button" 
                        @click="activeTab = 'sync'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'sync', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'sync'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Synchronization Settings
                </button>
                <button type="button" 
                        @click="activeTab = 'mapping'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'mapping', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'mapping'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Data Mapping & Configuration
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Integration Platforms Tab -->
            <div x-show="activeTab === 'platforms'" x-transition>
                <div class="space-y-6">
                    <!-- QuickBooks Integration -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                QuickBooks Integration
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="quickbooks_integration_enabled" 
                                           name="quickbooks_integration_enabled" 
                                           value="1"
                                           {{ old('quickbooks_integration_enabled', $setting->quickbooks_integration_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable QuickBooks Integration</span>
                                </label>
                                <p class="text-sm text-gray-500 ml-6">
                                    Sync invoices, payments, and customer data with QuickBooks
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Xero Integration -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                Xero Integration
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="xero_integration_enabled" 
                                           name="xero_integration_enabled" 
                                           value="1"
                                           {{ old('xero_integration_enabled', $setting->xero_integration_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Xero Integration</span>
                                </label>
                                <p class="text-sm text-gray-500 ml-6">
                                    Connect with Xero for accounting automation
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Sage Integration -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0h3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                Sage Integration
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="sage_integration_enabled" 
                                           name="sage_integration_enabled" 
                                           value="1"
                                           {{ old('sage_integration_enabled', $setting->sage_integration_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Sage Integration</span>
                                </label>
                                <p class="text-sm text-gray-500 ml-6">
                                    Connect with Sage accounting software
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Synchronization Settings Tab -->
            <div x-show="activeTab === 'sync'" x-transition>
                <div class="space-y-6">
                    <!-- Synchronization Settings -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Synchronization Settings
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="accounting_sync_enabled" 
                                           name="accounting_sync_enabled" 
                                           value="1"
                                           {{ old('accounting_sync_enabled', $setting->accounting_sync_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Automatic Sync</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="chart_of_accounts_sync" 
                                           name="chart_of_accounts_sync" 
                                           value="1"
                                           {{ old('chart_of_accounts_sync', $setting->chart_of_accounts_sync ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Sync Chart of Accounts</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="auto_invoice_sync" 
                                           name="auto_invoice_sync" 
                                           value="1"
                                           {{ old('auto_invoice_sync', $setting->auto_invoice_sync ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Auto-sync Invoices</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="payment_sync_enabled" 
                                           name="payment_sync_enabled" 
                                           value="1"
                                           {{ old('payment_sync_enabled', $setting->payment_sync_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Sync Payment Records</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Sync Frequency Settings -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Sync Frequency
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div>
                                    <label for="sync_frequency" class="block text-sm font-medium text-gray-700 mb-1">
                                        Sync Frequency
                                    </label>
                                    <select id="sync_frequency" 
                                            name="sync_frequency"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="realtime" {{ old('sync_frequency', $setting->sync_frequency ?? 'hourly') == 'realtime' ? 'selected' : '' }}>Real-time</option>
                                        <option value="15min" {{ old('sync_frequency', $setting->sync_frequency ?? 'hourly') == '15min' ? 'selected' : '' }}>Every 15 minutes</option>
                                        <option value="hourly" {{ old('sync_frequency', $setting->sync_frequency ?? 'hourly') == 'hourly' ? 'selected' : '' }}>Hourly</option>
                                        <option value="daily" {{ old('sync_frequency', $setting->sync_frequency ?? 'hourly') == 'daily' ? 'selected' : '' }}>Daily</option>
                                        <option value="manual" {{ old('sync_frequency', $setting->sync_frequency ?? 'hourly') == 'manual' ? 'selected' : '' }}>Manual only</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Mapping & Configuration Tab -->
            <div x-show="activeTab === 'mapping'" x-transition>
                <div class="space-y-6">
                    <!-- Account Mapping -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Account Mapping
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div>
                                    <label for="default_revenue_account" class="block text-sm font-medium text-gray-700 mb-1">
                                        Default Revenue Account
                                    </label>
                                    <input type="text" 
                                           id="default_revenue_account" 
                                           name="default_revenue_account" 
                                           value="{{ old('default_revenue_account', $setting->default_revenue_account ?? '') }}"
                                           placeholder="e.g., 4000 - Service Revenue"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                
                                <div>
                                    <label for="default_expense_account" class="block text-sm font-medium text-gray-700 mb-1">
                                        Default Expense Account
                                    </label>
                                    <input type="text" 
                                           id="default_expense_account" 
                                           name="default_expense_account" 
                                           value="{{ old('default_expense_account', $setting->default_expense_account ?? '') }}"
                                           placeholder="e.g., 5000 - Operating Expenses"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                
                                <div>
                                    <label for="default_tax_account" class="block text-sm font-medium text-gray-700 mb-1">
                                        Default Tax Account
                                    </label>
                                    <input type="text" 
                                           id="default_tax_account" 
                                           name="default_tax_account" 
                                           value="{{ old('default_tax_account', $setting->default_tax_account ?? '') }}"
                                           placeholder="e.g., 2200 - Sales Tax Payable"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Validation -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Data Validation & Error Handling
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="validate_before_sync" 
                                           name="validate_before_sync" 
                                           value="1"
                                           {{ old('validate_before_sync', $setting->validate_before_sync ?? true) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Validate data before synchronization</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="log_sync_errors" 
                                           name="log_sync_errors" 
                                           value="1"
                                           {{ old('log_sync_errors', $setting->log_sync_errors ?? true) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Log synchronization errors</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="send_error_notifications" 
                                           name="send_error_notifications" 
                                           value="1"
                                           {{ old('send_error_notifications', $setting->send_error_notifications ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Send error notifications via email</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 px-6 py-4 border-t border-gray-200">
            <a href="{{ route('settings.index') }}" 
               class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                Cancel
            </a>
            <button type="submit" 
                    class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Save Settings
            </button>
        </div>
    </form>
</div>
@endsection