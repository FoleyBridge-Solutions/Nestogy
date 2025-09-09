{{-- Pricing Calculator Component --}}
{{-- Simplified pricing display that integrates with the quote store --}}

<div x-data="pricingDisplay()" 
     x-init="init()"
     @pricing-updated.window="updateFromStore($event.detail)"
     class="pricing-calculator">
    
    <!-- Pricing Summary Card -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-6 border-b border-gray-200 bg-gray-50 bg-gray-100">
            <div class="flex justify-between items-center">
                <h6 class="mb-0">
                    <i class="fas fa-calculator mr-2"></i>Pricing Summary
                </h6>
                <button @click="showDetails = !showDetails" 
                        class="btn px-4 py-1 text-sm px-6 py-2 font-medium rounded-md transition-colors-link text-decoration-none">
                    <i class="fas" :class="showDetails ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
            </div>
        </div>
        
        <div class="p-6">
            <!-- Basic Pricing -->
            <div class="pricing-lines">
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Subtotal:</span>
                    <span class="fw-semibold" x-text="formatCurrency(pricing.subtotal)"></span>
                </div>
                
                <div x-show="pricing.discount > 0" class="flex justify-between mb-2">
                    <span class="text-gray-600">Discount:</span>
                    <span class="text-green-600">
                        -<span x-text="formatCurrency(pricing.discount)"></span>
                    </span>
                </div>
                
                <div x-show="pricing.savings > 0" class="flex justify-between mb-2">
                    <span class="text-gray-600 dark:text-gray-400">Volume Savings:</span>
                    <span class="text-green-600">
                        -<span x-text="formatCurrency(pricing.savings)"></span>
                    </span>
                </div>
                
                <div x-show="pricing.tax > 0" class="flex justify-between mb-2">
                    <span class="text-gray-600 dark:text-gray-400">Tax:</span>
                    <span x-text="formatCurrency(pricing.tax)"></span>
                </div>
                
                <hr class="my-2">
                
                <div class="flex justify-between">
                    <strong>Total:</strong>
                    <strong class="text-blue-600 fs-5" x-text="formatCurrency(pricing.total)"></strong>
                </div>
            </div>
            
            <!-- Recurring Revenue (if applicable) -->
            <div x-show="pricing.recurring && (pricing.recurring.monthly > 0 || pricing.recurring.annual > 0)" 
                 class="mt-6 pt-3 border-t">
                <h6 class="small text-gray-600 dark:text-gray-400 mb-2">
                    <i class="fas fa-sync-alt mr-1"></i>Recurring Revenue
                </h6>
                
                <div x-show="pricing.recurring?.monthly > 0" 
                     class="flex justify-between small">
                    <span class="text-gray-600 dark:text-gray-400">Monthly (MRR):</span>
                    <span class="text-cyan-600 dark:text-cyan-400" x-text="formatCurrency(pricing.recurring.monthly)"></span>
                </div>
                
                <div x-show="pricing.recurring?.annual > 0" 
                     class="flex justify-between small">
                    <span class="text-gray-600 dark:text-gray-400">Annual (ARR):</span>
                    <span class="text-cyan-600 dark:text-cyan-400" x-text="formatCurrency(pricing.recurring.annual)"></span>
                </div>
            </div>
            
            <!-- Pricing Details (collapsible) -->
            <div x-show="showDetails && pricing.appliedRules?.length > 0" 
                 x-transition
                 class="mt-6 pt-3 border-t">
                <h6 class="small text-gray-600 dark:text-gray-400 mb-2">Applied Pricing Rules</h6>
                <ul class="list-unstyled small">
                    <template x-for="rule in pricing.appliedRules" :key="rule.id">
                        <li class="mb-1">
                            <i class="fas fa-check-circle text-green-600 dark:text-green-400 mr-1"></i>
                            <span x-text="rule.name"></span>
                            <span class="text-gray-600 dark:text-gray-400" x-text="`(${rule.discount}% off)`"></span>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden-footer bg-gray-100">
            <div class="flex justify-between items-center">
                <small class="text-gray-600 dark:text-gray-400">
                    <span x-text="itemCount"></span> items selected
                </small>
                
                <div class=" px-6 py-2 font-medium rounded-md transition-colors-group-sm">
                    <button @click="applyDiscount()" 
                            class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-secondary"
                            title="Apply Discount">
                        <i class="fas fa-percentage"></i>
                    </button>
                    
                    <button @click="recalculate()" 
                            class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-secondary"
                            title="Recalculate">
                        <i class="fas fa-sync"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Total Savings Badge (if applicable) -->
    <div x-show="totalSavings > 0" 
         class="px-6 py-6 rounded bg-green-100 border border-green-400 text-green-700 mt-6">
        <div class="flex items-center">
            <i class="fas fa-piggy-bank mr-2"></i>
            <div>
                <strong>Total Savings: </strong>
                <span x-text="formatCurrency(totalSavings)"></span>
                <span class="text-gray-600 dark:text-gray-400 ml-2" x-show="pricing.subtotal > 0">
                    (<span x-text="Math.round((totalSavings / pricing.subtotal) * 100)"></span>% off)
                </span>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function pricingDisplay() {
    return {
        pricing: {
            subtotal: 0,
            discount: 0,
            savings: 0,
            tax: 0,
            total: 0,
            recurring: {
                monthly: 0,
                annual: 0
            },
            appliedRules: []
        },
        showDetails: false,
        itemCount: 0,
        
        init() {
            // Watch store for pricing changes
            if (this.$store && this.$store.quote) {
                this.$watch('$store.quote.pricing', (newPricing) => {
                    if (newPricing) {
                        this.pricing = { ...newPricing };
                    }
                }, { deep: true });
                
                this.$watch('$store.quote.selectedItemsCount', (count) => {
                    this.itemCount = count || 0;
                });
                
                // Initial load
                this.pricing = { ...this.$store.quote.pricing };
                this.itemCount = this.$store.quote.selectedItemsCount || 0;
            }
        },
        
        get totalSavings() {
            return (this.pricing.discount || 0) + (this.pricing.savings || 0);
        },
        
        updateFromStore(pricingData) {
            if (pricingData) {
                this.pricing = { ...pricingData };
            }
        },
        
        formatCurrency(amount) {
            const currency = (this.$store && this.$store.quote) ? 
                this.$store.quote.document.currency_code : 'USD';
                
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency || 'USD'
            }).format(amount || 0);
        },
        
        applyDiscount() {
            // Dispatch event to parent for discount application
            this.$dispatch('apply-discount', {
                current: this.pricing
            });
        },
        
        recalculate() {
            // Force recalculation via store
            if (this.$store && this.$store.quote) {
                this.$store.quote.updatePricing();
            }
            
            // Dispatch event to parent
            this.$dispatch('recalculate-pricing', {
                pricing: this.pricing
            });
        }
    };
}
</script>
@endpush
