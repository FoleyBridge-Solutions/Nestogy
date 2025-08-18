@extends('layouts.settings')

@section('title', 'Billing & Financial Settings - Nestogy')

@section('settings-title', 'Billing & Financial Settings')
@section('settings-description', 'Configure payment gateways, tax settings, and billing preferences')

@section('settings-content')
<div x-data="{ activeTab: 'payment' }">
    <form method="POST" action="{{ route('settings.billing-financial.update') }}">
        @csrf
        @method('PUT')
        
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8 px-6 pt-4">
                <button type="button" 
                        @click="activeTab = 'payment'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'payment', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'payment'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Payment Gateways
                </button>
                <button type="button" 
                        @click="activeTab = 'tax'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'tax', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'tax'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Tax Settings
                </button>
                <button type="button" 
                        @click="activeTab = 'billing'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'billing', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'billing'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Billing Configuration
                </button>
                <button type="button" 
                        @click="activeTab = 'invoicing'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'invoicing', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'invoicing'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Invoicing
                </button>
                <button type="button" 
                        @click="activeTab = 'reporting'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'reporting', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'reporting'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Financial Reporting
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">

            <!-- Payment Gateways Tab -->
            <div x-show="activeTab === 'payment'" x-transition>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Payment Gateway Configuration</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- PayPal -->
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-md font-medium text-gray-900 dark:text-white">PayPal</h4>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="paypal_enabled" value="1" {{ old('paypal_enabled', $setting->paypal_enabled ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Enable</span>
                                    </label>
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client ID</label>
                                        <input type="text" name="paypal_client_id" value="{{ old('paypal_client_id', $setting->paypal_client_id ?? '') }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client Secret</label>
                                        <input type="password" name="paypal_client_secret" placeholder="Leave blank to keep current" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Stripe -->
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-md font-medium text-gray-900 dark:text-white">Stripe</h4>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="stripe_enabled" value="1" {{ old('stripe_enabled', $setting->stripe_enabled ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Enable</span>
                                    </label>
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Publishable Key</label>
                                        <input type="text" name="stripe_publishable_key" value="{{ old('stripe_publishable_key', $setting->stripe_publishable_key ?? '') }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Secret Key</label>
                                        <input type="password" name="stripe_secret_key" placeholder="Leave blank to keep current" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tax Settings Tab -->
            <div x-show="activeTab === 'tax'" x-transition>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Tax Configuration</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="default_tax_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Default Tax Rate (%)</label>
                                <input type="number" id="default_tax_rate" name="default_tax_rate" value="{{ old('default_tax_rate', $setting->default_tax_rate ?? 0) }}" min="0" max="100" step="0.01" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="tax_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tax Name</label>
                                <input type="text" id="tax_name" name="tax_name" value="{{ old('tax_name', $setting->tax_name ?? 'Tax') }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                        </div>
                        <div class="mt-4 space-y-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="tax_inclusive" value="1" {{ old('tax_inclusive', $setting->tax_inclusive ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Tax Inclusive Pricing</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="compound_tax" value="1" {{ old('compound_tax', $setting->compound_tax ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Compound Tax Calculation</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Billing Configuration Tab -->
            <div x-show="activeTab === 'billing'" x-transition>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Billing Settings</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="billing_cycle" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Default Billing Cycle</label>
                                <select id="billing_cycle" name="billing_cycle" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="monthly" {{ old('billing_cycle', $setting->billing_cycle ?? 'monthly') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    <option value="quarterly" {{ old('billing_cycle', $setting->billing_cycle ?? 'monthly') == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                    <option value="annually" {{ old('billing_cycle', $setting->billing_cycle ?? 'monthly') == 'annually' ? 'selected' : '' }}>Annually</option>
                                </select>
                            </div>
                            <div>
                                <label for="payment_terms" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Default Payment Terms (days)</label>
                                <input type="number" id="payment_terms" name="payment_terms" value="{{ old('payment_terms', $setting->payment_terms ?? 30) }}" min="1" max="365" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                        </div>
                        <div class="mt-4 space-y-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="auto_charge" value="1" {{ old('auto_charge', $setting->auto_charge ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Auto-charge Saved Payment Methods</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="late_fee_enabled" value="1" {{ old('late_fee_enabled', $setting->late_fee_enabled ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Apply Late Fees</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoicing Tab -->
            <div x-show="activeTab === 'invoicing'" x-transition>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Invoice Configuration</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="invoice_prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Invoice Prefix</label>
                                <input type="text" id="invoice_prefix" name="invoice_prefix" value="{{ old('invoice_prefix', $setting->invoice_prefix ?? 'INV') }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="invoice_starting_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Starting Invoice Number</label>
                                <input type="number" id="invoice_starting_number" name="invoice_starting_number" value="{{ old('invoice_starting_number', $setting->invoice_starting_number ?? 1000) }}" min="1" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                        </div>
                        <div class="mt-4 space-y-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="auto_send_invoices" value="1" {{ old('auto_send_invoices', $setting->auto_send_invoices ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Auto-send Invoices to Clients</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="invoice_reminders" value="1" {{ old('invoice_reminders', $setting->invoice_reminders ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Send Payment Reminders</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Reporting Tab -->
            <div x-show="activeTab === 'reporting'" x-transition>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Reporting Configuration</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="fiscal_year_start" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fiscal Year Start</label>
                                <select id="fiscal_year_start" name="fiscal_year_start" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="01-01" {{ old('fiscal_year_start', $setting->fiscal_year_start ?? '01-01') == '01-01' ? 'selected' : '' }}>January 1</option>
                                    <option value="04-01" {{ old('fiscal_year_start', $setting->fiscal_year_start ?? '01-01') == '04-01' ? 'selected' : '' }}>April 1</option>
                                    <option value="07-01" {{ old('fiscal_year_start', $setting->fiscal_year_start ?? '01-01') == '07-01' ? 'selected' : '' }}>July 1</option>
                                    <option value="10-01" {{ old('fiscal_year_start', $setting->fiscal_year_start ?? '01-01') == '10-01' ? 'selected' : '' }}>October 1</option>
                                </select>
                            </div>
                            <div>
                                <label for="default_currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Default Currency</label>
                                <select id="default_currency" name="default_currency" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="USD" {{ old('default_currency', $setting->default_currency ?? 'USD') == 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                    <option value="EUR" {{ old('default_currency', $setting->default_currency ?? 'USD') == 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                    <option value="GBP" {{ old('default_currency', $setting->default_currency ?? 'USD') == 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                                    <option value="CAD" {{ old('default_currency', $setting->default_currency ?? 'USD') == 'CAD' ? 'selected' : '' }}>CAD - Canadian Dollar</option>
                                </select>
                            </div>
                            <div>
                                <label for="goal_margin" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Goal Margin (%)</label>
                                <input type="number" id="goal_margin" name="profitability_tracking_settings[goal_margin]" value="{{ old('profitability_tracking_settings.goal_margin', $setting->profitability_tracking_settings['goal_margin'] ?? 25) }}" min="0" max="100" step="0.1" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="25.0">
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Target profit margin for products. Used for color-coded profitability indicators.</p>
                            </div>
                        </div>
                        <div class="mt-4 space-y-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="auto_generate_reports" value="1" {{ old('auto_generate_reports', $setting->auto_generate_reports ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Auto-generate Monthly Reports</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="email_reports" value="1" {{ old('email_reports', $setting->email_reports ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Email Reports to Management</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('settings.index') }}" 
               class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                Cancel
            </a>
            <button type="submit" 
                    class="px-4 py-2 bg-blue-600 dark:bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 dark:hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Save Settings
            </button>
        </div>
    </form>
</div>
@endsection