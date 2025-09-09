@props(['schedule', 'contract'])

@php
$pricingStructure = $schedule->pricing_structure ?? [];
$tieredPricing = $schedule->tiered_pricing ?? [];
$usagePricing = $schedule->usage_pricing ?? [];
$additionalFees = $schedule->additional_fees ?? [];
$billingTerms = $schedule->billing_terms ?? [];
@endphp

<div class="space-y-6">
    <!-- Base Pricing Structure -->
    @if(!empty($pricingStructure))
        <div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Base Pricing Structure</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($pricingStructure as $type => $amount)
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                ${{ number_format($amount, 2) }}
                            </div>
                            <div class="text-sm font-medium text-green-800 dark:text-green-200 mt-1">
                                {{ ucwords(str_replace('_', ' ', $type)) }}
                            </div>
                            @php
                            $descriptions = [
                                'monthly_base' => 'Fixed monthly fee',
                                'setup_fee' => 'One-time setup charge',
                                'per_asset' => 'Per managed device',
                                'per_user' => 'Per user/contact',
                                'hourly_rate' => 'Professional services'
                            ];
                            @endphp
                            @if(isset($descriptions[$type]))
                                <div class="text-xs text-green-600 dark:text-green-300 mt-1">
                                    {{ $descriptions[$type] }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Tiered Pricing -->
    @if(!empty($tieredPricing))
        <div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Volume-Based Pricing Tiers</h4>
            <div class="space-y-3">
                @foreach($tieredPricing as $tier)
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-4">
                                    <!-- Tier Range -->
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200">
                                            @if(isset($tier['min_quantity']) && isset($tier['max_quantity']))
                                                {{ $tier['min_quantity'] }} - {{ $tier['max_quantity'] }} assets
                                            @elseif(isset($tier['min_quantity']))
                                                {{ $tier['min_quantity'] }}+ assets
                                            @else
                                                Base tier
                                            @endif
                                        </span>
                                    </div>

                                    <!-- Pricing -->
                                    <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        ${{ number_format($tier['price'] ?? 0, 2) }}
                                        @if(isset($tier['unit']))
                                            <span class="text-sm font-normal text-gray-600 dark:text-gray-400">/ {{ $tier['unit'] }}</span>
                                        @endif
                                    </div>

                                    <!-- Discount -->
                                    @if(isset($tier['discount_percentage']) && $tier['discount_percentage'] > 0)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200">
                                            {{ $tier['discount_percentage'] }}% off
                                        </span>
                                    @endif
                                </div>

                                <!-- Description -->
                                @if(isset($tier['description']))
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">{{ $tier['description'] }}</p>
                                @endif
                            </div>

                            <!-- Current Usage Indicator -->
                            @if(isset($contract) && $contract)
                                @php
                                $currentAssetCount = $contract->supportedAssets()->count();
                                $isCurrentTier = false;
                                if (isset($tier['min_quantity']) && isset($tier['max_quantity'])) {
                                    $isCurrentTier = $currentAssetCount >= $tier['min_quantity'] && $currentAssetCount <= $tier['max_quantity'];
                                } elseif (isset($tier['min_quantity'])) {
                                    $isCurrentTier = $currentAssetCount >= $tier['min_quantity'];
                                }
                                @endphp
                                @if($isCurrentTier)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Current Tier
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Usage-Based Pricing -->
    @if(!empty($usagePricing))
        <div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Usage-Based Pricing</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($usagePricing as $metric => $pricing)
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <h5 class="text-sm font-medium text-blue-900 dark:text-blue-100">
                                    {{ ucwords(str_replace('_', ' ', $metric)) }}
                                </h5>
                                @if(isset($pricing['description']))
                                    <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">{{ $pricing['description'] }}</p>
                                @endif
                                
                                <!-- Pricing Details -->
                                <div class="mt-3 space-y-1">
                                    @if(isset($pricing['base_rate']))
                                        <div class="text-sm text-blue-800 dark:text-blue-200">
                                            <span class="font-medium">Base Rate:</span> ${{ number_format($pricing['base_rate'], 2) }}
                                            @if(isset($pricing['unit']))
                                                / {{ $pricing['unit'] }}
                                            @endif
                                        </div>
                                    @endif
                                    
                                    @if(isset($pricing['overage_rate']))
                                        <div class="text-sm text-blue-800 dark:text-blue-200">
                                            <span class="font-medium">Overage Rate:</span> ${{ number_format($pricing['overage_rate'], 2) }}
                                            @if(isset($pricing['unit']))
                                                / {{ $pricing['unit'] }}
                                            @endif
                                        </div>
                                    @endif
                                    
                                    @if(isset($pricing['included_quantity']))
                                        <div class="text-sm text-blue-800 dark:text-blue-200">
                                            <span class="font-medium">Included:</span> {{ $pricing['included_quantity'] }}
                                            @if(isset($pricing['unit']))
                                                {{ Str::plural($pricing['unit'], $pricing['included_quantity']) }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Additional Fees -->
    @if(!empty($additionalFees))
        <div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Additional Fees</h4>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
                <div class="space-y-3">
                    @foreach($additionalFees as $fee)
                        <div class="flex items-center justify-between py-2 border-b border-yellow-200 dark:border-yellow-700 last:border-0">
                            <div class="flex-1">
                                <div class="font-medium text-yellow-900 dark:text-yellow-100">{{ $fee['name'] ?? 'Fee' }}</div>
                                @if(isset($fee['description']))
                                    <div class="text-sm text-yellow-700 dark:text-yellow-300">{{ $fee['description'] }}</div>
                                @endif
                            </div>
                            <div class="text-right">
                                <div class="font-semibold text-yellow-900 dark:text-yellow-100">
                                    @if(isset($fee['percentage']) && $fee['percentage'])
                                        {{ $fee['amount'] }}%
                                    @else
                                        ${{ number_format($fee['amount'] ?? 0, 2) }}
                                    @endif
                                </div>
                                @if(isset($fee['frequency']))
                                    <div class="text-xs text-yellow-600 dark:text-yellow-400">{{ $fee['frequency'] }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Billing Terms -->
    @if(!empty($billingTerms))
        <div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Billing Terms & Conditions</h4>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($billingTerms as $term => $value)
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ ucwords(str_replace('_', ' ', $term)) }}:
                            </span>
                            <span class="text-sm text-gray-900 dark:text-gray-100">
                                @if(is_bool($value))
                                    {{ $value ? 'Yes' : 'No' }}
                                @else
                                    {{ $value }}
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Current Billing Summary -->
    @if(isset($contract) && $contract)
        <div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Current Billing Summary</h4>
            <div class="bg-gradient-to-r from-green-50 to-blue-50 dark:from-green-900/20 dark:to-blue-900/20 border border-green-200 dark:border-green-700 rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Monthly Amount -->
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                            ${{ number_format($contract->getMonthlyRecurringRevenue(), 2) }}
                        </div>
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Monthly Recurring</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Based on current assignments</div>
                    </div>

                    <!-- Annual Value -->
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                            ${{ number_format($contract->getAnnualValue(), 2) }}
                        </div>
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Annual Contract Value</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Including one-time fees</div>
                    </div>

                    <!-- Next Billing -->
                    <div class="text-center">
                        <div class="text-lg font-bold text-purple-600 dark:text-purple-400">
                            @if($contract->invoices()->latest()->first())
                                {{ $contract->invoices()->latest()->first()->created_at->addMonth()->format('M d, Y') }}
                            @else
                                TBD
                            @endif
                        </div>
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Next Billing Date</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Estimated based on schedule</div>
                    </div>
                </div>
                
                @if($contract->canBeEdited())
                    <div class="mt-6 text-center">
                        <button type="button" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                            </svg>
                            Recalculate Billing
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @else
        <!-- Preview for contract creation -->
        <div class="space-y-6">
            <div>
                <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Pricing Schedule Preview</h4>
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-700 rounded-lg p-6">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h5 class="text-lg font-medium text-green-900 dark:text-green-100">Schedule B - Pricing & Fee Structure</h5>
                            <p class="mt-2 text-green-700 dark:text-green-300">
                                This schedule will define the comprehensive pricing model, billing rates, fee structures, 
                                and payment terms based on your template selection and configuration.
                            </p>
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-green-200 dark:border-green-600">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Base Pricing</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Monthly fees, Setup costs</div>
                                </div>
                                <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-green-200 dark:border-green-600">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Billing Model</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Per-asset, Per-user, Tiered</div>
                                </div>
                                <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-green-200 dark:border-green-600">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Payment Terms</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Billing cycles, Terms</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sample Pricing Structure -->
            <div>
                <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Expected Pricing Components</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Monthly Recurring -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="text-sm font-medium text-blue-900 dark:text-blue-100">Monthly Recurring Revenue</h5>
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </div>
                        <div class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                            <div class="flex justify-between">
                                <span>Base service fee</span>
                                <span class="font-medium">Variable</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Per-asset/user charges</span>
                                <span class="font-medium">Based on model</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Add-on services</span>
                                <span class="font-medium">As configured</span>
                            </div>
                        </div>
                    </div>

                    <!-- One-Time Fees -->
                    <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="text-sm font-medium text-purple-900 dark:text-purple-100">One-Time Charges</h5>
                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div class="space-y-2 text-sm text-purple-800 dark:text-purple-200">
                            <div class="flex justify-between">
                                <span>Setup & onboarding</span>
                                <span class="font-medium">Per template</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Implementation fees</span>
                                <span class="font-medium">If applicable</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Custom configuration</span>
                                <span class="font-medium">As needed</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Billing Automation Notice -->
            <div>
                <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Automated Billing Configuration</h4>
                <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700 rounded-lg p-4">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h5 class="text-sm font-medium text-indigo-800 dark:text-indigo-200">Smart Pricing Engine</h5>
                            <p class="mt-1 text-sm text-indigo-700 dark:text-indigo-300">
                                Contract pricing will be automatically calculated based on your template selection, asset assignments, 
                                and billing model. Real-time calculations will be available immediately after contract creation.
                            </p>
                            <div class="mt-3 flex items-center space-x-4 text-xs text-indigo-600 dark:text-indigo-400">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Dynamic pricing calculations
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Automated billing setup
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Invoice generation
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>