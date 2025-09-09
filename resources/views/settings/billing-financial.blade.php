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
                @if(auth()->user()->company_id === config('saas.platform_company_id', 1))
                <button type="button" 
                        @click="activeTab = 'subscription-plans'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'subscription-plans', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'subscription-plans'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Subscription Plans
                </button>
                @endif
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
                                        <input type="hidden" name="paypal_enabled" value="0">
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
                                        <input type="hidden" name="stripe_enabled" value="0">
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
                            
                            <!-- ACH/Bank Transfer -->
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-md font-medium text-gray-900 dark:text-white">ACH/Bank Transfer</h4>
                                    <label class="flex items-center">
                                        <input type="hidden" name="ach_enabled" value="0">
                                        <input type="checkbox" name="ach_enabled" value="1" {{ old('ach_enabled', $setting->ach_enabled ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Enable</span>
                                    </label>
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bank Account Name</label>
                                        <input type="text" name="ach_bank_name" value="{{ old('ach_bank_name', $setting->ach_bank_name ?? '') }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Routing Number</label>
                                        <input type="text" name="ach_routing_number" value="{{ old('ach_routing_number', $setting->ach_routing_number ?? '') }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="9 digits">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Account Number</label>
                                        <input type="text" name="ach_account_number" value="{{ old('ach_account_number', $setting->ach_account_number ?? '') }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>

                            <!-- Wire Transfer -->
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-md font-medium text-gray-900 dark:text-white">Wire Transfer</h4>
                                    <label class="flex items-center">
                                        <input type="hidden" name="wire_enabled" value="0">
                                        <input type="checkbox" name="wire_enabled" value="1" {{ old('wire_enabled', $setting->wire_enabled ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Enable</span>
                                    </label>
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bank Name</label>
                                        <input type="text" name="wire_bank_name" value="{{ old('wire_bank_name', $setting->wire_bank_name ?? '') }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">SWIFT Code</label>
                                        <input type="text" name="wire_swift_code" value="{{ old('wire_swift_code', $setting->wire_swift_code ?? '') }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Account Number</label>
                                        <input type="text" name="wire_account_number" value="{{ old('wire_account_number', $setting->wire_account_number ?? '') }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>

                            <!-- Check Payments -->
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-md font-medium text-gray-900 dark:text-white">Check Payments</h4>
                                    <label class="flex items-center">
                                        <input type="hidden" name="check_enabled" value="0">
                                        <input type="checkbox" name="check_enabled" value="1" {{ old('check_enabled', $setting->check_enabled ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Enable</span>
                                    </label>
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pay To (Company Name)</label>
                                        <input type="text" name="check_payto_name" value="{{ old('check_payto_name', $setting->check_payto_name ?? '') }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mailing Address</label>
                                        <textarea name="check_mailing_address" rows="3" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">{{ old('check_mailing_address', $setting->check_mailing_address ?? '') }}</textarea>
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

            @if(auth()->user()->company_id === config('saas.platform_company_id', 1))
            <!-- Subscription Plans Tab -->
            <div x-show="activeTab === 'subscription-plans'" x-transition x-data="subscriptionPlansManager()">
                <div class="space-y-6">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Subscription Plans Management</h3>
                        <button @click="startCreate()" 
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md">
                            Create New Plan
                        </button>
                    </div>

                    <!-- Plans List -->
                    <div x-show="!showCreateForm && !editPlan" class="space-y-4">
                        <template x-for="plan in plans" :key="plan.id">
                            <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2">
                                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="plan.name"></h4>
                                            <span x-show="!plan.is_active" class="bg-red-100 text-red-800 text-xs font-medium px-2 py-1 rounded">Inactive</span>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1" x-text="plan.description"></p>
                                        <div class="mt-2 flex items-center space-x-4">
                                            <span class="text-2xl font-bold text-gray-900 dark:text-white" x-text="plan.formatted_price"></span>
                                            <span class="text-sm text-gray-500 dark:text-gray-400" x-text="plan.user_limit_text"></span>
                                        </div>
                                        <div class="mt-2">
                                            <span class="text-xs text-gray-500 dark:text-gray-400">Stripe Price ID: </span>
                                            <code class="text-xs bg-gray-100 dark:bg-gray-600 px-1 rounded" x-text="plan.stripe_price_id"></code>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button @click="startEdit(plan)" 
                                                class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 rounded">
                                            Edit
                                        </button>
                                        <button @click="deletePlan(plan.id)" 
                                                class="px-3 py-1 text-sm bg-red-100 hover:bg-red-200 dark:bg-red-900 dark:hover:bg-red-800 text-red-700 dark:text-red-300 rounded">
                                            Deactivate
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div x-show="plans.length === 0" class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400">No subscription plans found. Create your first plan to get started.</p>
                        </div>
                    </div>

                    <!-- Create/Edit Form -->
                    <div x-show="showCreateForm || editPlan" class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-6">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4" x-text="editPlan ? 'Edit Subscription Plan' : 'Create New Subscription Plan'"></h4>
                        
                        <form @submit.prevent="savePlan" class="space-y-4" @keydown.enter.prevent>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Plan Name</label>
                                    <input type="text" x-model="formData.name" required 
                                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Monthly Price</label>
                                    <input type="number" x-model="formData.price_monthly" step="0.01" min="0" required 
                                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">User Limit</label>
                                    <input type="number" x-model="formData.user_limit" min="1" 
                                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                           placeholder="Leave empty for unlimited">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Stripe Price ID</label>
                                    <input type="text" x-model="formData.stripe_price_id" required 
                                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                           placeholder="price_xxxxxxxxxxxxx">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sort Order</label>
                                    <input type="number" x-model="formData.sort_order" min="0" 
                                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" x-model="formData.is_active" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                <textarea x-model="formData.description" rows="3" 
                                          class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                          placeholder="Brief description of this plan"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Features</label>
                                <textarea x-model="formData.features_text" rows="4" 
                                          class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                          placeholder="Enter features, one per line"></textarea>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Enter one feature per line</p>
                            </div>
                            <div class="flex justify-end space-x-3">
                                <button type="button" @click="cancelForm" 
                                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="saving" 
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md">
                                    <span x-show="!saving" x-text="editPlan ? 'Update Plan' : 'Create Plan'"></span>
                                    <span x-show="saving">Saving...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
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

@push('scripts')
<script>
function subscriptionPlansManager() {
    return {
        plans: [],
        showCreateForm: false,
        editPlan: null,
        saving: false,
        formReady: false,
        formData: {
            name: '',
            price_monthly: '',
            user_limit: '',
            features_text: '',
            description: '',
            stripe_price_id: '',
            is_active: true,
            sort_order: 0
        },

        async init() {
            await this.loadPlans();
        },

        async loadPlans() {
            try {
                const response = await fetch('{{ route('settings.platform.subscription-plans.index') }}');
                const data = await response.json();
                this.plans = data.plans;
            } catch (error) {
                console.error('Failed to load plans:', error);
                alert('Failed to load subscription plans');
            }
        },

        async savePlan() {
            if (this.saving || !this.formReady) return;
            this.saving = true;

            try {
                // Convert features text to array
                const features = this.formData.features_text
                    .split('\n')
                    .map(f => f.trim())
                    .filter(f => f.length > 0);

                const requestData = {
                    ...this.formData,
                    features: features,
                    user_limit: this.formData.user_limit || null
                };

                const url = this.editPlan 
                    ? `{{ route('settings.platform.subscription-plans.update', '_ID_') }}`.replace('_ID_', this.editPlan.id)
                    : '{{ route('settings.platform.subscription-plans.store') }}';

                const method = this.editPlan ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify(requestData)
                });

                const result = await response.json();

                if (result.success) {
                    await this.loadPlans();
                    this.cancelForm();
                    alert(result.message);
                } else {
                    alert(result.message || 'Failed to save plan');
                }
            } catch (error) {
                console.error('Save error:', error);
                alert('Failed to save plan');
            } finally {
                this.saving = false;
            }
        },

        async deletePlan(planId) {
            if (!confirm('Are you sure you want to deactivate this plan?')) return;

            try {
                const response = await fetch(`{{ route('settings.platform.subscription-plans.destroy', '_ID_') }}`.replace('_ID_', planId), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    }
                });

                const result = await response.json();

                if (result.success) {
                    await this.loadPlans();
                    alert(result.message);
                } else {
                    alert(result.message || 'Failed to delete plan');
                }
            } catch (error) {
                console.error('Delete error:', error);
                alert('Failed to delete plan');
            }
        },

        startEdit(plan) {
            this.editPlan = plan;
            this.showCreateForm = false;
            this.formReady = false;
            
            // Use setTimeout to ensure the form is rendered before setting values
            setTimeout(() => {
                this.formData = {
                    name: plan.name || '',
                    price_monthly: plan.price_monthly || '',
                    user_limit: plan.user_limit || '',
                    features_text: Array.isArray(plan.features) ? plan.features.join('\n') : '',
                    description: plan.description || '',
                    stripe_price_id: plan.stripe_price_id || '',
                    is_active: plan.is_active !== undefined ? plan.is_active : true,
                    sort_order: plan.sort_order || 0
                };
                this.formReady = true;
            }, 100);
        },

        cancelForm() {
            this.showCreateForm = false;
            this.editPlan = null;
            this.formReady = false;
            this.formData = {
                name: '',
                price_monthly: '',
                user_limit: '',
                features_text: '',
                description: '',
                stripe_price_id: '',
                is_active: true,
                sort_order: 0
            };
        },

        startCreate() {
            this.showCreateForm = true;
            this.editPlan = null;
            this.formReady = true;
            this.formData = {
                name: '',
                price_monthly: '',
                user_limit: '',
                features_text: '',
                description: '',
                stripe_price_id: '',
                is_active: true,
                sort_order: 0
            };
        }
    }
}
</script>
@endpush

@endsection
