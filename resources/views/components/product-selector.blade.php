{{-- Simplified Product Selector Component --}}
<div x-data="productSelectorAdvanced()" 
     class="product-selector-container mx-auto w-full"
     @client-selected.window="clientId = $event.detail.clientId">
    
    {{-- Search and Filter Header --}}
    <x-product-selector-header />
    
    {{-- Products Section --}}
    <div x-show="activeTab === 'products'" class="bg-white rounded-lg shadow-sm mb-6">
        <div class="px-6 py-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">
                    Products
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 ml-1" x-text="products.length"></span>
                </h3>
                
                <div class="flex gap-1">
                    <button type="button"
                            @click="sortProducts('name')"
                            class="inline-flex items-center px-6 py-1 border border-gray-300 text-gray-600 bg-white rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 text-sm"
                            :class="{ 'bg-blue-600 text-white border-blue-600': productSort.field === 'name' }">
                        Name
                    </button>
                    
                    <button type="button"
                            @click="sortProducts('price')"
                            class="inline-flex items-center px-6 py-1 border border-gray-300 text-gray-600 bg-white rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 text-sm"
                            :class="{ 'bg-blue-600 text-white border-blue-600': productSort.field === 'price' }">
                        Price
                    </button>
                    
                    <button type="button"
                            @click="sortProducts('category')"
                            class="inline-flex items-center px-6 py-1 border border-gray-300 text-gray-600 bg-white rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 text-sm"
                            :class="{ 'bg-blue-600 text-white border-blue-600': productSort.field === 'category' }">
                        Category
                    </button>
                </div>
            </div>
        </div>
        
        <div class="p-6">
            <div x-show="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="product in paginatedProducts" :key="product.id">
                    {{-- Product Grid Card --}}
                    <div class="bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow relative">
                        <div x-show="product.discontinued" class="absolute top-2 right-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Discontinued
                        </div>
                        
                        <div class="p-6">
                            <h6 class="font-medium text-gray-900 mb-2" x-text="product.name"></h6>
                            <div class="text-sm text-gray-600 mb-6" x-text="product.description"></div>
                            
                            <div class="flex flex-wrap gap-1 mb-6">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-500 text-white" x-text="product.category"></span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800" x-text="formatBillingCycle(product.billing_cycle)"></span>
                            </div>
                            
                            <div class="mb-6">
                                <div class="text-lg font-semibold text-gray-900" x-text="formatPrice(product.base_price)"></div>
                                <template x-if="isSelected(product.id, 'product') && getSelectedItem(product.id, 'product')?.tax_amount > 0">
                                    <div class="text-sm text-gray-600 mt-1">
                                        <span>Tax: </span>
                                        <span x-text="formatPrice(getSelectedItem(product.id, 'product')?.tax_amount)"></span>
                                        <span class="text-xs" x-text="'(' + (getSelectedItem(product.id, 'product')?.tax_rate || 0).toFixed(2) + '%)'"></span>
                                    </div>
                                </template>
                                <template x-if="isSelected(product.id, 'product') && getSelectedItem(product.id, 'product')?.total">
                                    <div class="text-sm font-medium text-blue-600">
                                        Total: <span x-text="formatPrice(getSelectedItem(product.id, 'product')?.total)"></span>
                                    </div>
                                </template>
                            </div>
                            
                            <div class="flex gap-2">
                                <button type="button" @click="showProductDetails(product)"
                                        class="flex-1 inline-flex justify-center items-center px-6 py-2 border border-gray-300 text-gray-600 bg-white rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 text-sm">
                                    <i class="fas fa-info-circle mr-1"></i>Details
                                </button>
                                
                                <button type="button" @click="toggleItem(product, 'product')"
                                        class="flex-1 inline-flex justify-center items-center px-6 py-2 rounded text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2"
                                        :class="isSelected(product.id, 'product') ? 'bg-blue-600 text-white focus:ring-blue-500' : 'border border-blue-300 text-blue-600 bg-white hover:bg-blue-50 focus:ring-blue-500'">
                                    <i class="fas fa-plus mr-1" x-show="!isSelected(product.id, 'product')"></i>
                                    <i class="fas fa-check mr-1" x-show="isSelected(product.id, 'product')"></i>
                                    <span x-show="!isSelected(product.id, 'product')">Add</span>
                                    <span x-show="isSelected(product.id, 'product')">Added</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            
            <div x-show="viewMode === 'list'" class="divide-y divide-gray-200">
                <template x-for="product in paginatedProducts" :key="product.id">
                    {{-- Product List Item --}}
                    <div class="bg-white border-b hover:bg-gray-50 px-6 py-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <h6 class="font-medium text-gray-900" x-text="product.name"></h6>
                                    <div x-show="product.discontinued" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 ml-2">
                                        Discontinued
                                    </div>
                                </div>
                                <div class="text-sm text-gray-600 mt-1" x-text="product.description"></div>
                                <div class="flex flex-wrap gap-1 mt-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-500 text-white" x-text="product.category"></span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800" x-text="formatBillingCycle(product.billing_cycle)"></span>
                                </div>
                            </div>
                            
                            <div class="text-right ml-4">
                                <div class="text-lg font-semibold text-gray-900" x-text="formatPrice(product.base_price)"></div>
                                <template x-if="isSelected(product.id, 'product') && getSelectedItem(product.id, 'product')?.tax_amount > 0">
                                    <div class="text-sm text-gray-600 mt-1">
                                        Tax: <span x-text="formatPrice(getSelectedItem(product.id, 'product')?.tax_amount)"></span>
                                        <span class="text-xs" x-text="'(' + (getSelectedItem(product.id, 'product')?.tax_rate || 0).toFixed(2) + '%)'"></span>
                                    </div>
                                </template>
                                <template x-if="isSelected(product.id, 'product') && getSelectedItem(product.id, 'product')?.total">
                                    <div class="text-sm font-medium text-blue-600">
                                        Total: <span x-text="formatPrice(getSelectedItem(product.id, 'product')?.total)"></span>
                                    </div>
                                </template>
                                <div class="flex gap-2 mt-2">
                                    <button type="button" @click="showProductDetails(product)"
                                            class="inline-flex items-center px-6 py-1 border border-gray-300 text-gray-600 bg-white rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 text-sm">
                                        <i class="fas fa-info-circle mr-1"></i>Details
                                    </button>
                                    
                                    <button type="button" @click="toggleItem(product, 'product')"
                                            class="inline-flex items-center px-6 py-1 rounded text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2"
                                            :class="isSelected(product.id, 'product') ? 'bg-blue-600 text-white focus:ring-blue-500' : 'border border-blue-300 text-blue-600 bg-white hover:bg-blue-50 focus:ring-blue-500'">
                                        <i class="fas fa-plus mr-1" x-show="!isSelected(product.id, 'product')"></i>
                                        <i class="fas fa-check mr-1" x-show="isSelected(product.id, 'product')"></i>
                                        <span x-show="!isSelected(product.id, 'product')">Add</span>
                                        <span x-show="isSelected(product.id, 'product')">Added</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
    
    {{-- Services Section --}}
    <div x-show="activeTab === 'services'" class="bg-white rounded-lg shadow-sm mb-6">
        <div class="px-6 py-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">
                    Services  
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 ml-1" x-text="services.length"></span>
                </h3>
                
                <div class="flex gap-1">
                    <button type="button"
                            @click="sortServices('name')"
                            class="inline-flex items-center px-6 py-1 border border-gray-300 text-gray-600 bg-white rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 text-sm"
                            :class="{ 'bg-blue-600 text-white border-blue-600': serviceSort.field === 'name' }">
                        Name
                    </button>
                    
                    <button type="button"
                            @click="sortServices('price')"
                            class="inline-flex items-center px-6 py-1 border border-gray-300 text-gray-600 bg-white rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 text-sm"
                            :class="{ 'bg-blue-600 text-white border-blue-600': serviceSort.field === 'price' }">
                        Price
                    </button>
                    
                    <button type="button"
                            @click="sortServices('type')"
                            class="inline-flex items-center px-6 py-1 border border-gray-300 text-gray-600 bg-white rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 text-sm"
                            :class="{ 'bg-blue-600 text-white border-blue-600': serviceSort.field === 'type' }">
                        Type
                    </button>
                </div>
            </div>
        </div>
        
        <div class="p-6">
            <div x-show="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <template x-for="service in paginatedServices" :key="service.id">
                    {{-- Service Grid Card --}}
                    <div class="bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow h-full">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-1">
                                    <h6 class="font-medium text-gray-900" x-text="service.product.name"></h6>
                                    <div class="text-sm text-gray-600 mt-1" x-text="service.product.description"></div>
                                </div>
                                <span x-show="service.sla_days" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <span x-text="service.sla_days"></span>d SLA
                                </span>
                            </div>
                            
                            <div class="mb-6">
                                <div class="text-lg font-semibold text-gray-900">
                                    <span x-text="formatPrice(service.product.base_price)"></span>
                                    <span class="text-sm text-gray-500 font-normal">
                                        / <span x-text="formatBillingCycle(service.product.billing_cycle)"></span>
                                    </span>
                                </div>
                                <template x-if="isSelected(service.id, 'service') && getSelectedItem(service.id, 'service')?.tax_amount > 0">
                                    <div class="text-sm text-gray-600 mt-1">
                                        <span>Tax: </span>
                                        <span x-text="formatPrice(getSelectedItem(service.id, 'service')?.tax_amount)"></span>
                                        <span class="text-xs" x-text="'(' + (getSelectedItem(service.id, 'service')?.tax_rate || 0).toFixed(2) + '%)'"></span>
                                    </div>
                                </template>
                                <template x-if="isSelected(service.id, 'service') && getSelectedItem(service.id, 'service')?.total">
                                    <div class="text-sm font-medium text-blue-600">
                                        Total: <span x-text="formatPrice(getSelectedItem(service.id, 'service')?.total)"></span>
                                    </div>
                                </template>
                            </div>
                            
                            <div class="flex flex-wrap gap-1 mb-6">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-500 text-white" x-text="service.service_type"></span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800" x-text="formatBillingCycle(service.product.billing_cycle)"></span>
                            </div>
                            
                            <button type="button" @click="toggleItem(service, 'service')"
                                    class="w-full inline-flex justify-center items-center px-6 py-2 rounded text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2"
                                    :class="isSelected(service.id, 'service') ? 'bg-blue-600 text-white focus:ring-blue-500' : 'border border-blue-300 text-blue-600 bg-white hover:bg-blue-50 focus:ring-blue-500'">
                                <i class="fas fa-plus mr-2" x-show="!isSelected(service.id, 'service')"></i>
                                <i class="fas fa-check mr-2" x-show="isSelected(service.id, 'service')"></i>
                                <span x-show="!isSelected(service.id, 'service')">Add Service</span>
                                <span x-show="isSelected(service.id, 'service')">Added</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
            
            <div x-show="viewMode === 'list'" class="divide-y divide-gray-200">
                <template x-for="service in paginatedServices" :key="service.id">
                    {{-- Service List Item --}}
                    <div class="bg-white border-b hover:bg-gray-50 px-6 py-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <h6 class="font-medium text-gray-900" x-text="service.product.name"></h6>
                                    <span x-show="service.sla_days" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 ml-2">
                                        <span x-text="service.sla_days"></span>d SLA
                                    </span>
                                </div>
                                <div class="text-sm text-gray-600 mt-1" x-text="service.product.description"></div>
                                <div class="flex flex-wrap gap-1 mt-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-500 text-white" x-text="service.service_type"></span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800" x-text="formatBillingCycle(service.product.billing_cycle)"></span>
                                </div>
                            </div>
                            
                            <div class="text-right ml-4">
                                <div class="text-lg font-semibold text-gray-900">
                                    <span x-text="formatPrice(service.product.base_price)"></span>
                                    <span class="text-sm text-gray-500 font-normal">
                                        / <span x-text="formatBillingCycle(service.product.billing_cycle)"></span>
                                    </span>
                                </div>
                                <template x-if="isSelected(service.id, 'service') && getSelectedItem(service.id, 'service')?.tax_amount > 0">
                                    <div class="text-sm text-gray-600 mt-1">
                                        Tax: <span x-text="formatPrice(getSelectedItem(service.id, 'service')?.tax_amount)"></span>
                                        <span class="text-xs" x-text="'(' + (getSelectedItem(service.id, 'service')?.tax_rate || 0).toFixed(2) + '%)'"></span>
                                    </div>
                                </template>
                                <template x-if="isSelected(service.id, 'service') && getSelectedItem(service.id, 'service')?.total">
                                    <div class="text-sm font-medium text-blue-600">
                                        Total: <span x-text="formatPrice(getSelectedItem(service.id, 'service')?.total)"></span>
                                    </div>
                                </template>
                                <button type="button" @click="toggleItem(service, 'service')"
                                        class="inline-flex items-center px-6 py-1 rounded text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 mt-2"
                                        :class="isSelected(service.id, 'service') ? 'bg-blue-600 text-white focus:ring-blue-500' : 'border border-blue-300 text-blue-600 bg-white hover:bg-blue-50 focus:ring-blue-500'">
                                    <i class="fas fa-plus mr-1" x-show="!isSelected(service.id, 'service')"></i>
                                    <i class="fas fa-check mr-1" x-show="isSelected(service.id, 'service')"></i>
                                    <span x-show="!isSelected(service.id, 'service')">Add</span>
                                    <span x-show="isSelected(service.id, 'service')">Added</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
    
    {{-- Pagination --}}
    <div x-show="totalPages > 1" class="flex justify-center mt-6">
        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
            <button @click="changePage(currentPage - 1)" 
                    :disabled="currentPage === 1"
                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                <i class="fas fa-chevron-left"></i>
            </button>
            
            <template x-for="page in visiblePages" :key="page">
                <button @click="changePage(page)"
                        class="relative inline-flex items-center px-6 py-2 border text-sm font-medium"
                        :class="page === currentPage ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'"
                        x-text="page">
                </button>
            </template>
            
            <button @click="changePage(currentPage + 1)" 
                    :disabled="currentPage === totalPages"
                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                <i class="fas fa-chevron-right"></i>
            </button>
        </nav>
    </div>
</div>
