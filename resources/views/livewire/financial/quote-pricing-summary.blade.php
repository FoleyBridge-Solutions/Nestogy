<div class="space-y-4">
    <!-- Main Pricing Card -->
    <flux:card class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Pricing Summary</h3>
            @if($this->hasTax)
                <flux:button 
                    wire:click="toggleBreakdown"
                    variant="ghost"
                    size="sm"
                    class="text-sm"
                >
                    {{ $showBreakdown ? 'Hide' : 'Show' }} Breakdown
                </flux:button>
            @endif
        </div>

        <div class="space-y-3">
            <!-- Subtotal -->
            <div class="flex justify-between items-center">
                <span class="text-gray-600 dark:text-gray-400">Subtotal:</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $this->formattedSubtotal }}</span>
            </div>

            <!-- Discount -->
            @if($this->hasDiscount)
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">Discount:</span>
                    <span class="text-red-600 dark:text-red-400 font-medium">
                        -{{ $this->formattedDiscount }}
                    </span>
                </div>
            @endif

            <!-- Savings -->
            @if($this->hasSavings)
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">Additional Savings:</span>
                    <span class="text-green-600 dark:text-green-400 font-medium">
                        -{{ $this->formattedSavings }}
                    </span>
                </div>
            @endif

            <!-- Tax -->
            @if($this->hasTax)
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">
                        Tax ({{ $this->taxRate }}):
                    </span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $this->formattedTax }}</span>
                </div>

                <!-- Tax Breakdown -->
                @if($showBreakdown && !empty($taxBreakdown))
                    <div class="ml-4 space-y-2 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        @foreach($taxBreakdown['jurisdictions'] as $jurisdiction)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{ $jurisdiction['name'] }} ({{ $jurisdiction['rate'] }}%):
                                </span>
                                <span class="text-gray-700 dark:text-gray-300">
                                    {{ $this->formatCurrency($jurisdiction['amount']) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif

            <!-- Total -->
            <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center">
                    <span class="text-lg font-medium text-gray-900 dark:text-white">Total:</span>
                    <span class="text-lg font-bold text-blue-600 dark:text-blue-400-600 dark:text-blue-600 dark:text-blue-400-400">
                        {{ $this->formattedTotal }}
                    </span>
                </div>
            </div>
        </div>
    </flux:card>

    <!-- Recurring Revenue Card -->
    @if($this->hasRecurring)
        <flux:card class="p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Recurring Revenue</h3>
            
            <div class="space-y-3">
                @if(($pricing['recurring']['monthly'] ?? 0) > 0)
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">Monthly:</span>
                        <span class="font-medium text-green-600 dark:text-green-400">
                            {{ $this->formattedRecurringMonthly }}
                        </span>
                    </div>
                @endif

                @if(($pricing['recurring']['annual'] ?? 0) > 0)
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">Annual:</span>
                        <span class="font-medium text-green-600 dark:text-green-400">
                            {{ $this->formattedRecurringAnnual }}
                        </span>
                    </div>
                @endif
            </div>

            <!-- Recurring Revenue Projection -->
            @if(($pricing['recurring']['monthly'] ?? 0) > 0)
                <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <div class="text-sm text-green-700 dark:text-green-300">
                        <div class="flex justify-between">
                            <span>3 Months:</span>
                            <span>{{ $this->formatCurrency(($pricing['recurring']['monthly'] ?? 0) * 3) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>6 Months:</span>
                            <span>{{ $this->formatCurrency(($pricing['recurring']['monthly'] ?? 0) * 6) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>12 Months:</span>
                            <span>{{ $this->formatCurrency(($pricing['recurring']['monthly'] ?? 0) * 12) }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </flux:card>
    @endif

    <!-- Quick Stats Card -->
    <flux:card class="p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Quick Stats</h3>
        
        <div class="space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Base Amount:</span>
                <span class="text-gray-900 dark:text-white">{{ $this->formattedSubtotal }}</span>
            </div>
            
            @if($this->hasDiscount)
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Discount %:</span>
                    <span class="text-red-600 dark:text-red-400">
                        {{ number_format((($pricing['discount'] ?? 0) / max(($pricing['subtotal'] ?? 0), 1)) * 100, 1) }}%
                    </span>
                </div>
            @endif

            @if($this->hasTax)
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Effective Tax Rate:</span>
                    <span class="text-gray-900 dark:text-white">{{ $this->taxRate }}</span>
                </div>
            @endif

            @if($this->hasRecurring)
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Monthly Value:</span>
                    <span class="text-green-600 dark:text-green-400">{{ $this->formattedRecurringMonthly }}</span>
                </div>
            @endif
        </div>
    </flux:card>

    <!-- Price Validity Notice -->
    <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
        <div class="flex items-start space-x-2">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="text-sm text-blue-700 dark:text-blue-300">
                <p class="font-medium">Pricing Information</p>
                <p class="mt-1">Prices are calculated in real-time and may be subject to change based on final configurations and service requirements.</p>
            </div>
        </div>
    </div>

    <!-- Additional Actions -->
    <div class="space-y-2">
        <flux:button 
            variant="ghost" 
            size="sm" 
            class="w-full justify-center"
            disabled
        >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"/>
            </svg>
            Price Adjustments (Coming Soon)
        </flux:button>

        <flux:button 
            variant="ghost" 
            size="sm" 
            class="w-full justify-center"
            disabled
        >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            Tax Calculator (Coming Soon)
        </flux:button>
    </div>
</div>
