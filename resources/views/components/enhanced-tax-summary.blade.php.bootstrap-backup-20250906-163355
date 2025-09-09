@props([
    'taxData' => null,
    'showDetailedBreakdown' => true,
    'showPerformanceInfo' => false,
    'currency' => 'USD',
    'precision' => 2,
])

@php
    $isCalculating = false;
    $hasError = isset($taxData['error']) && !empty($taxData['error']);
    $hasResults = $taxData && !$hasError;
@endphp

<div 
    class="bg-white rounded-lg shadow-md overflow-hidden border-l-4 border-blue-500"
    x-data="{ 
        showDetails: {{ $showDetailedBreakdown ? 'true' : 'false' }},
        showPerformance: {{ $showPerformanceInfo ? 'true' : 'false' }},
        isAnimating: false 
    }"
    x-init="
        $watch('$store.taxEngine.isCalculating', value => {
            if (value) {
                isAnimating = true;
            } else {
                setTimeout(() => isAnimating = false, 500);
            }
        })
    "
>
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-calculator text-blue-600 mr-2"></i>
                Tax Calculation Summary
                
                <!-- Loading indicator -->
                <div 
                    x-show="$store.taxEngine.isCalculating || isAnimating" 
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:leave="transition ease-in duration-300"
                    class="ml-3"
                >
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                </div>
            </h3>
            
            <div class="flex items-center space-x-2">
                @if($showPerformanceInfo)
                    <button 
                        @click="showPerformance = !showPerformance"
                        class="text-sm text-gray-500 hover:text-gray-700 transition-colors duration-200"
                        title="Toggle performance info"
                    >
                        <i class="fas fa-chart-line"></i>
                    </button>
                @endif
                
                @if($showDetailedBreakdown)
                    <button 
                        @click="showDetails = !showDetails"
                        class="text-sm text-gray-500 hover:text-gray-700 transition-colors duration-200"
                        title="Toggle detailed breakdown"
                    >
                        <i class="fas fa-list-ul"></i>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Error State -->
    @if($hasError)
        <div class="p-6">
            <div class="bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">
                            Tax Calculation Error
                        </h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>{{ $taxData['error'] ?? 'An error occurred while calculating taxes.' }}</p>
                        </div>
                        <div class="mt-4">
                            <button 
                                type="button" 
                                @click="$store.taxEngine.calculateTaxFromAPI($store.quoteItems || [], $store.selectedClient)"
                                class="text-sm bg-red-100 text-red-800 rounded-md px-3 py-2 hover:bg-red-200 transition-colors duration-200"
                            >
                                <i class="fas fa-redo mr-1"></i>
                                Retry Calculation
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Tax Summary -->
    @if($hasResults)
        <div class="p-6">
            <!-- Primary Totals -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-sm font-medium text-gray-500">Subtotal</div>
                    <div class="text-2xl font-bold text-gray-900" x-text="$store.taxEngine.getFormattedTaxBreakdown().subtotal">
                        ${{ number_format($taxData['subtotal'] ?? 0, $precision) }}
                    </div>
                </div>
                
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="text-sm font-medium text-blue-600">Total Tax</div>
                    <div class="text-2xl font-bold text-blue-900" x-text="$store.taxEngine.getFormattedTaxBreakdown().totalTax">
                        ${{ number_format($taxData['totalTax'] ?? 0, $precision) }}
                    </div>
                    <div class="text-xs text-blue-600 mt-1" x-text="$store.taxEngine.getFormattedTaxBreakdown().effectiveTaxRate">
                        ({{ number_format(($taxData['effectiveTaxRate'] ?? 0), 2) }}%)
                    </div>
                </div>
                
                <div class="bg-green-50 rounded-lg p-4">
                    <div class="text-sm font-medium text-green-600">Grand Total</div>
                    <div class="text-2xl font-bold text-green-900" x-text="$store.taxEngine.getFormattedTaxBreakdown().grandTotal">
                        ${{ number_format($taxData['grandTotal'] ?? 0, $precision) }}
                    </div>
                </div>
            </div>

            <!-- Detailed Tax Breakdown -->
            <div x-show="showDetails" x-transition:enter="transition ease-out duration-300" x-transition:leave="transition ease-in duration-300">
                @if($showDetailedBreakdown && isset($taxData['taxes']) && count($taxData['taxes']) > 0)
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                            <h4 class="text-sm font-semibold text-gray-700">Tax Breakdown by Type</h4>
                        </div>
                        
                        <div class="divide-y divide-gray-200">
                            @foreach($taxData['taxes'] as $tax)
                                <div class="px-4 py-3 flex justify-between items-center">
                                    <div class="flex-1">
                                        <div class="text-sm font-medium text-gray-900">{{ $tax['name'] ?? 'Tax' }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ $tax['authority'] ?? 'Tax Authority' }}
                                            @if(isset($tax['type']) && $tax['type'] === 'regulatory')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 ml-2">
                                                    Regulatory
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-semibold text-gray-900">
                                            ${{ number_format($tax['amount'] ?? 0, $precision) }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ number_format($tax['rate'] ?? 0, 2) }}%
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Performance Information -->
            <div x-show="showPerformance" x-transition:enter="transition ease-out duration-300" x-transition:leave="transition ease-in duration-300">
                @if($showPerformanceInfo && isset($taxData['performance']))
                    <div class="mt-4 bg-gray-50 rounded-lg p-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Performance Metrics</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">Calculation Time:</span>
                                <span class="font-medium ml-1">{{ number_format($taxData['performance']['calculation_time_ms'] ?? 0, 1) }}ms</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Items/Second:</span>
                                <span class="font-medium ml-1">{{ number_format($taxData['performance']['items_per_second'] ?? 0, 1) }}</span>
                            </div>
                            @if(isset($taxData['performance']['engine_breakdown']))
                                <div>
                                    <span class="text-gray-500">Engine Used:</span>
                                    <span class="font-medium ml-1">{{ array_keys($taxData['performance']['engine_breakdown'])[0] ?? 'General' }}</span>
                                </div>
                            @endif
                            @if(isset($taxData['calculationId']))
                                <div>
                                    <span class="text-gray-500">Calc ID:</span>
                                    <span class="font-mono text-xs ml-1">{{ substr($taxData['calculationId'], -8) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Empty State -->
    @if(!$hasResults && !$hasError)
        <div class="p-6 text-center">
            <div class="text-gray-400">
                <i class="fas fa-calculator fa-2x mb-3"></i>
                <p class="text-sm">No tax calculation available yet.</p>
                <p class="text-xs text-gray-500 mt-1">Add items and select a customer to calculate taxes.</p>
            </div>
        </div>
    @endif
</div>

<!-- Alpine.js store integration -->
<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('taxEngine', {
        ...Alpine.store('taxEngine'),
        
        // Expose formatting method globally
        formatCurrency(amount, currency = 'USD') {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            }).format(amount || 0);
        }
    });
});
</script>