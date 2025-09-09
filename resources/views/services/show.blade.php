@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <x-page-header 
        :title="$service->name"
        :subtitle="'Service details and billing configuration' . ($service->sku ? ' • SKU: ' . $service->sku : '')"
        :back-route="route('services.index')"
        back-label="Back to Services"
    >
        <x-slot name="actions">
            <div class="flex gap-3">
                @can('update', $service)
                <a href="{{ route('services.edit', $service) }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit Service
                </a>
                @endcan
            </div>
        </x-slot>
    </x-page-header>

    <!-- Main Content Grid -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Column (Main Content - 2/3 width) -->
            <div class="lg:col-span-12-span-2 space-y-6">
                <!-- Service Details -->
                <x-content-card>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $service->name }}</h3>
                                @if($service->sku)
                                    <p class="text-sm text-gray-500 mt-1">SKU: {{ $service->sku }}</p>
                                @endif
                                <div class="flex space-x-2 mt-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        Service
                                    </span>
                                    @if($service->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200">
                                            Inactive
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-green-600">${{ number_format($service->price, 2) }}</div>
                                <div class="text-sm text-gray-500">{{ $service->getFormattedUnitType() }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4">
                        @if($service->description)
                            <div class="mb-6">
                                <h4 class="text-base font-medium text-gray-900 dark:text-white mb-2">Description</h4>
                                <p class="text-gray-600 dark:text-gray-400">{{ $service->description }}</p>
                            </div>
                        @endif

                        <!-- Service Configuration -->
                        <div class="mb-6">
                            <h4 class="text-base font-medium text-gray-900 dark:text-white mb-4">Service Configuration</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Unit Type</dt>
                                    <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($service->unit_type) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Billing Model</dt>
                                    <dd class="mt-1">
                                        @if($service->billing_model === 'subscription')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Subscription</span>
                                        @elseif($service->billing_model === 'usage_based')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Usage-based</span>
                                        @elseif($service->billing_model === 'hybrid')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">Hybrid</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">One-time</span>
                                        @endif
                                    </dd>
                                </div>
                            </div>
                        </div>

                        @if($service->isSubscription())
                        <div class="mb-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Billing Cycle</dt>
                                    <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $service->getBillingCycleDescription() }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Billing Interval</dt>
                                    <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $service->billing_interval ?: 1 }}</dd>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Pricing Information -->
                        <div class="mb-6">
                            <h4 class="text-base font-medium text-gray-900 dark:text-white mb-4">Pricing Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Base Price</dt>
                                    <dd class="mt-1 text-lg font-semibold text-green-600">${{ number_format($service->price, 2) }}</dd>
                                </div>
                                @if($service->cost)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Cost</dt>
                                    <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">${{ number_format($service->cost, 2) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Margin</dt>
                                    <dd class="mt-1 text-sm font-medium text-blue-600">{{ $service->getFormattedProfitMargin() }}</dd>
                                </div>
                                @endif
                            </div>
                        </div>

                        @if($service->tax)
                        <div class="mb-6">
                            <h4 class="text-base font-medium text-gray-900 dark:text-white mb-4">Tax Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Tax Rate</dt>
                                    <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $service->tax->name }} ({{ $service->tax->percent }}%)</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Price with Tax</dt>
                                    <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">${{ number_format($service->getPriceWithTax(), 2) }}</dd>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Category & Organization -->
                        <div class="mb-6">
                            <h4 class="text-base font-medium text-gray-900 dark:text-white mb-4">Organization</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Category</dt>
                                    <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                        @if($service->category)
                                            {{ $service->category->name }}
                                        @else
                                            <span class="text-gray-400">No category assigned</span>
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Currency</dt>
                                    <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $service->currency_code }}</dd>
                                </div>
                            </div>
                        </div>

                        @if($service->notes)
                        <div class="mb-6">
                            <h4 class="text-base font-medium text-gray-900 dark:text-white mb-2">Internal Notes</h4>
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $service->notes }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </x-content-card>
            </div>

            <!-- Right Column (Sidebar - 1/3 width) -->
            <div class="lg:col-span-12-span-1 space-y-6">
                
                <!-- Service Summary -->
                <x-content-card>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Service Summary</h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    @if($service->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200">
                                            Inactive
                                        </span>
                                    @endif
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Service Type</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ ucfirst($service->billing_model) }} Service</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Unit Pricing</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">${{ number_format($service->price, 2) }} {{ $service->getFormattedUnitType() }}</dd>
                            </div>

                            @if($service->isSubscription())
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Billing Frequency</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $service->getBillingCycleDescription() }}</dd>
                            </div>
                            @endif

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Usage Count</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $service->getUsageCount() }} times</dd>
                            </div>

                            @if($service->getTotalRevenue() > 0)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Total Revenue</dt>
                                <dd class="mt-1 text-sm font-medium text-green-600">{{ $service->getFormattedTotalRevenue() }}</dd>
                            </div>
                            @endif

                            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                                <div class="space-y-3">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $service->created_at->format('M j, Y') }}</dd>
                                    </div>
                                    @if($service->updated_at != $service->created_at)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $service->updated_at->format('M j, Y') }}</dd>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="space-y-3">
                            @can('update', $service)
                            <a href="{{ route('services.edit', $service) }}" 
                               class="inline-flex items-center justify-center w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                <svg class="-ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit Service
                            </a>
                            @endcan
                            
                            @can('create', App\Models\Product::class)
                            <form method="POST" action="{{ route('services.duplicate', $service) }}" class="w-full">
                                @csrf
                                <button type="submit" class="inline-flex items-center justify-center w-full px-4 py-2 border border-blue-300 rounded-md shadow-sm text-sm font-medium text-blue-700 bg-white dark:bg-gray-800 hover:bg-blue-50">
                                    <svg class="-ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    Duplicate Service
                                </button>
                            </form>
                            @endcan
                            
                            @can('delete', $service)
                            <button type="button" 
                                    onclick="confirmDelete('{{ $service->name }}', '{{ route('services.destroy', $service) }}')"
                                    class="inline-flex items-center justify-center w-full px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white dark:bg-gray-800 hover:bg-red-50">
                                <svg class="-ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Delete Service
                            </button>
                            @endcan
                        </div>
                    </div>
                </x-content-card>

                <!-- Price Calculator -->
                <x-content-card>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Price Calculator</h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="space-y-4">
                            <div>
                                <label for="calc_quantity" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Quantity</label>
                                <input type="number" id="calc_quantity" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" value="1" min="1">
                            </div>

                            @if($service->isSubscription())
                            <div>
                                <label for="calc_billing_periods" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Billing Periods</label>
                                <input type="number" id="calc_billing_periods" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" value="1" min="1">
                                <p class="mt-1 text-xs text-gray-500">Number of {{ $service->billing_cycle }}s</p>
                            </div>
                            @endif

                            @if($service->isUsageBased())
                            <div>
                                <label for="calc_usage" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Usage Amount</label>
                                <input type="number" id="calc_usage" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" value="0" min="0" step="0.01">
                                <p class="mt-1 text-xs text-gray-500">Usage units consumed</p>
                            </div>
                            @endif

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Total Price</label>
                                <div class="mt-1 text-xl font-bold text-green-600" id="calculated_price">
                                    ${{ number_format($service->price, 2) }}
                                </div>
                            </div>

                            <button type="button" onclick="calculatePrice()" class="w-full inline-flex items-center justify-center px-4 py-2 border border-blue-300 rounded-md shadow-sm text-sm font-medium text-blue-700 bg-white dark:bg-gray-800 hover:bg-blue-50">
                                <svg class="-ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                Calculate
                            </button>
                        </div>
                    </div>
                </x-content-card>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(name, url) {
    if (confirm(`Are you sure you want to delete the service "${name}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        form.innerHTML = `
            @csrf
            @method('DELETE')
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function calculatePrice() {
    const quantity = parseInt(document.getElementById('calc_quantity').value) || 1;
    const billingPeriodsElement = document.getElementById('calc_billing_periods');
    const usageElement = document.getElementById('calc_usage');
    
    const billingPeriods = billingPeriodsElement ? parseInt(billingPeriodsElement.value) || 1 : 1;
    const usage = usageElement ? parseFloat(usageElement.value) || 0 : 0;
    
    const basePrice = {{ $service->price }};
    let totalPrice = basePrice * quantity;
    
    @if($service->isSubscription())
        totalPrice *= billingPeriods;
    @endif
    
    @if($service->isUsageBased())
        totalPrice += (usage * basePrice);
    @endif
    
    @if($service->tax && !$service->tax_inclusive)
        const taxAmount = totalPrice * ({{ $service->tax->percent ?? 0 }} / 100);
        totalPrice += taxAmount;
    @endif
    
    document.getElementById('calculated_price').textContent = 
        `$${totalPrice.toFixed(2)}`;
}

// Auto-calculate when inputs change
document.addEventListener('DOMContentLoaded', function() {
    const inputs = ['calc_quantity', 'calc_billing_periods', 'calc_usage'];
    inputs.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('input', calculatePrice);
        }
    });
});
</script>
@endsection
