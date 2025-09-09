@props([
    'taxCalculation' => null,
    'fallbackTaxAmount' => null,
    'collapsible' => true,
    'realTimeBreakdown' => null,
])

@php
    // Use real-time breakdown if available
    if ($realTimeBreakdown && isset($realTimeBreakdown['has_breakdown']) && $realTimeBreakdown['has_breakdown']) {
        $hasDetailedData = true;
        $jurisdictions = $realTimeBreakdown['jurisdictions'] ?? [];
        $taxBreakdown = [];
        $totalTax = $realTimeBreakdown['total_tax'] ?? $fallbackTaxAmount ?? 0;
        $effectiveRate = $realTimeBreakdown['total_rate'] ?? 0;
        $source = $realTimeBreakdown['source'] ?? 'real_time';
    } else {
        // Fallback to stored tax calculation
        $hasDetailedData = $taxCalculation && ($taxCalculation->tax_breakdown || $taxCalculation->jurisdictions);
        $jurisdictions = $hasDetailedData ? $taxCalculation->getJurisdictionBreakdown() : [];
        $taxBreakdown = $hasDetailedData ? $taxCalculation->getTaxBreakdownSummary() : [];
        $totalTax = $hasDetailedData ? $taxCalculation->total_tax_amount : ($fallbackTaxAmount ?? 0);
        $effectiveRate = $hasDetailedData ? $taxCalculation->effective_tax_rate : 0;
        $source = 'stored';
    }
    
    // Determine if we have any breakdown to show
    $hasBreakdown = count($jurisdictions) > 0 || count($taxBreakdown) > 0;
@endphp

@if($totalTax > 0)
    @if($collapsible && $hasBreakdown)
        <!-- Collapsible version with detailed breakdown -->
        <div x-data="{ showDetails: false }">
            <!-- Summary row -->
            <div class="flex justify-between items-center py-2">
                <button 
                    @click="showDetails = !showDetails"
                    class="flex items-center text-sm text-gray-600 hover:text-gray-800 focus:outline-none"
                >
                    <span>Tax:</span>
                    <svg 
                        :class="showDetails ? 'rotate-180' : ''"
                        class="ml-1 h-4 w-4 transform transition-transform duration-200" 
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <span class="text-sm text-gray-900 font-medium">${{ number_format($totalTax, 2) }}</span>
            </div>

            <!-- Detailed breakdown (shown when expanded) -->
            <div x-show="showDetails" x-transition:enter="transition ease-out duration-200" x-transition:leave="transition ease-in duration-200">
                <div class="ml-4 space-y-2 py-2 border-l-2 border-gray-200 pl-4">
                    @if($effectiveRate > 0)
                        <div class="text-xs text-gray-500 mb-2">
                            {{ number_format($effectiveRate, 2) }}% effective rate
                            @if($source === 'real_time_local')
                                <span class="text-green-600 font-medium">(Real-time calculation)</span>
                            @endif
                        </div>
                    @endif
                    
                    @if(count($jurisdictions) > 0)
                        <!-- Jurisdiction breakdown -->
                        @foreach($jurisdictions as $jurisdiction)
                            @if(isset($jurisdiction['tax_amount']) && $jurisdiction['tax_amount'] > 0)
                                <div class="flex justify-between items-center">
                                    <div class="flex-1">
                                        <div class="text-sm text-gray-900">{{ $jurisdiction['name'] ?? 'Unknown Authority' }}</div>
                                        @if(isset($jurisdiction['type']))
                                            <div class="text-xs text-gray-500 capitalize">{{ $jurisdiction['type'] }}</div>
                                        @endif
                                    </div>
                                    <div class="text-right ml-4">
                                        <div class="text-sm font-medium text-gray-900">${{ number_format($jurisdiction['tax_amount'], 2) }}</div>
                                        @if(isset($jurisdiction['tax_rate']) && $jurisdiction['tax_rate'] > 0)
                                            <div class="text-xs text-gray-500">{{ number_format($jurisdiction['tax_rate'], 2) }}%</div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @elseif(count($taxBreakdown) > 0)
                        <!-- Tax type breakdown -->
                        @foreach($taxBreakdown as $tax)
                            @if($tax['amount'] > 0)
                                <div class="flex justify-between items-center">
                                    <div class="flex-1">
                                        <div class="text-sm text-gray-900">{{ $tax['description'] }}</div>
                                        @if(isset($tax['source']) && $tax['source'] !== 'internal')
                                            <div class="text-xs text-gray-500 capitalize">{{ $tax['source'] }}</div>
                                        @endif
                                    </div>
                                    <div class="text-right ml-4">
                                        <div class="text-sm font-medium text-gray-900">${{ number_format($tax['amount'], 2) }}</div>
                                        @if(isset($tax['rate']) && $tax['rate'] > 0)
                                            <div class="text-xs text-gray-500">{{ number_format($tax['rate'], 2) }}%</div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @else
                        <!-- No detailed breakdown available -->
                        <div class="text-sm text-gray-500">Detailed breakdown not available</div>
                    @endif
                </div>
            </div>
        </div>
    @else
        <!-- Simple version (no breakdown or not collapsible) -->
        <div class="flex justify-between py-2">
            <span class="text-sm text-gray-600">Tax:</span>
            <span class="text-sm text-gray-900">${{ number_format($totalTax, 2) }}</span>
        </div>
    @endif
@endif