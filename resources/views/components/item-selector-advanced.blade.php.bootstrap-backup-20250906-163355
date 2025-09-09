{{-- Advanced Item Selector Component --}}
{{-- This is an enhanced version of the product selector that integrates with the quote store --}}

<div x-data="productSelectorAdvanced()" 
     x-init="init()"
     class="item-selector-container mx-auto px-4 mx-auto px-4"
     @client-selected.window="clientId = $event.detail.clientId">
    
    {{-- Header with Search and Filters --}}
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-4">
        <div class="p-6">
            <div class="flex flex-wrap -mx-4 items-center">
                <div class="md:w-1/2 px-4">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text"
                               x-ref="searchInput"
                               x-model="searchQuery"
                               @input.debounce.300ms="quickSearch()"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="Search products, services, or bundles... (Press / to focus)">
                    </div>
                </div>
                
                <div class="md:w-1/2 px-4">
                    <div class="btn-group float-end" role="group">
                        <button type="button"
                                @click="showFilters = !showFilters"
                                class="btn btn-outline-secondary"
                                :class="{ 'active': showFilters }">
                            <i class="fas fa-filter"></i>
                            Filters
                            <span x-show="hasFiltersApplied" class="badge bg-blue-600 ml-1" x-text="Object.values(filters).filter(f => f).length"></span>
                        </button>
                        
                        <button type="button"
                                @click="viewMode = 'grid'"
                                class="btn btn-outline-secondary"
                                :class="{ 'active': viewMode === 'grid' }">
                            <i class="fas fa-th"></i>
                        </button>
                        
                        <button type="button"
                                @click="viewMode = 'list'"
                                class="btn btn-outline-secondary"
                                :class="{ 'active': viewMode === 'list' }">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            {{-- Filter Panel --}}
            <div x-show="showFilters" x-transition class="mt-3">
                <div class="flex flex-wrap -mx-4">
                    <div class="col-md-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="category-filter">Category</label>
                        <select x-model="filters.category" id="category-filter" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-sm">
                            <option value="">All Categories</option>
                            <template x-for="category in categories" :key="category">
                                <option :value="category" x-text="category"></option>
                            </template>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="billing-model-filter">Billing Model</label>
                        <select x-model="filters.billingModel" id="billing-model-filter" class="form-select form-select-sm">
                            <option value="">All Models</option>
                            <option value="one_time">One-time</option>
                            <option value="subscription">Subscription</option>
                            <option value="usage_based">Usage-based</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label" for="price-range-min">Price Range</label>
                        <div class="input-group input-group-sm">
                            <input type="number" 
                                   x-model="filters.priceRange.min" 
                                   id="price-range-min"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                   placeholder="Min">
                            <span class="input-group-text">-</span>
                            <input type="number" 
                                   x-model="filters.priceRange.max" 
                                   id="price-range-max"
                                   class="form-control" 
                                   placeholder="Max">
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button @click="clearFilters()" 
                                class="btn btn-sm btn-outline-danger w-100">
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Tabs for Products, Services, Bundles --}}
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item">
            <button class="nav-link"
                    :class="{ 'active': activeTab === 'products' }"
                    @click="switchTab('products')">
                <i class="fas fa-box"></i> Products
                <span class="badge bg-gray-600 ml-1" x-text="products.length"></span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link"
                    :class="{ 'active': activeTab === 'services' }"
                    @click="switchTab('services')">
                <i class="fas fa-concierge-bell"></i> Services
                <span class="badge bg-gray-600 ms-1" x-text="services.length"></span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link"
                    :class="{ 'active': activeTab === 'bundles' }"
                    @click="switchTab('bundles')">
                <i class="fas fa-layer-group"></i> Bundles
                <span class="badge bg-secondary ms-1" x-text="bundles.length"></span>
            </button>
        </li>
    </ul>
    
    {{-- Loading State --}}
    <div x-show="loading" class="text-center py-5">
        <div class="spinner-border text-blue-600" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
    
    {{-- Products Grid/List View --}}
    <div x-show="!loading && activeTab === 'products'">
        {{-- Sort Options --}}
        <div class="flex justify-between items-center mb-3">
            <div>
                <span class="text-gray-600">Showing <span x-text="products.length"></span> products</span>
            </div>
            <div class="btn-group btn-group-sm">
                <button @click="changeSort('name')" 
                        class="btn btn-outline-secondary"
                        :class="{ 'active': sortBy === 'name' }">
                    Name
                    <i x-show="sortBy === 'name'" 
                       class="fas" 
                       :class="sortOrder === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                </button>
                <button @click="changeSort('price')" 
                        class="btn btn-outline-secondary"
                        :class="{ 'active': sortBy === 'price' }">
                    Price
                    <i x-show="sortBy === 'price'" 
                       class="fas" 
                       :class="sortOrder === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                </button>
                <button @click="changeSort('newest')" 
                        class="btn btn-outline-secondary"
                        :class="{ 'active': sortBy === 'newest' }">
                    Newest
                </button>
            </div>
        </div>
        
        {{-- Grid View --}}
        <div x-show="viewMode === 'grid'" class="row">
            <template x-for="product in products" :key="product.id">
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden h-100" 
                         :class="{ 'border-primary': isSelected(product.id, 'product') }">
                        <div class="p-6">
                            <h6 class="card-title" x-text="product.name"></h6>
                            <p class="text-gray-600 small" x-text="product.sku"></p>
                            
                            <div class="mb-2">
                                <span class="badge bg-secondary" x-text="product.category"></span>
                                <span class="badge bg-info" x-text="formatBillingCycle(product.billing_cycle)"></span>
                            </div>
                            
                            <div class="price-display mb-3">
                                <div class="h5 mb-0" x-text="formatPrice(product.base_price)"></div>
                            </div>
                            
                            <div class="flex justify-between">
                                <button @click="showProductDetails(product)"
                                        class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-info-circle"></i> Details
                                </button>
                                
                                <button @click="toggleItem(product, 'product')"
                                        class="btn btn-sm"
                                        :class="isSelected(product.id, 'product') ? 'btn-primary' : 'btn-outline-primary'">
                                    <i class="fas" :class="isSelected(product.id, 'product') ? 'fa-check' : 'fa-plus'"></i>
                                    <span x-text="isSelected(product.id, 'product') ? 'Selected' : 'Select'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        
        {{-- List View --}}
        <div x-show="viewMode === 'list'" class="min-w-full divide-y divide-gray-200-responsive">
            <table class="min-w-full divide-y divide-gray-200 [&>tbody>tr:hover]:bg-gray-100">
                <thead>
                    <tr>
                        <th width="30" scope="col"></th>
                        <th scope="col">Product</th>
                        <th scope="col">SKU</th>
                        <th scope="col">Category</th>
                        <th scope="col">Billing</th>
                        <th scope="col">Price</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="product in products" :key="product.id">
                        <tr :class="{ 'table-primary': isSelected(product.id, 'product') }">
                            <td>
                                <input type="checkbox"
                                       :checked="isSelected(product.id, 'product')"
                                       @change="toggleItem(product, 'product')"
                                       class="form-check-input">
                            </td>
                            <td>
                                <strong x-text="product.name"></strong>
                            </td>
                            <td x-text="product.sku"></td>
                            <td>
                                <span class="badge bg-secondary" x-text="product.category"></span>
                            </td>
                            <td>
                                <span class="badge bg-info" x-text="formatBillingCycle(product.billing_cycle)"></span>
                            </td>
                            <td>
                                <div x-text="formatPrice(product.base_price)"></div>
                            </td>
                            <td>
                                <button @click="showProductDetails(product)"
                                        class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
    
    {{-- Services View --}}
    <div x-show="!loading && activeTab === 'services'">
        <div class="row">
            <template x-for="service in services" :key="service.id">
                <div class="col-md-6 mb-3">
                    <div class="card h-100"
                         :class="{ 'border-primary': isSelected(service.id, 'service') }">
                        <div class="card-body">
                            <h6 class="card-title" x-text="service.name"></h6>
                            <p class="text-muted small" x-text="service.service_type"></p>
                            
                            <div class="price-display mb-3">
                                <div class="h5 mb-0" x-text="formatPrice(service.base_price)"></div>
                            </div>
                            
                            <button @click="toggleItem(service, 'service')"
                                    class="btn w-100"
                                    :class="isSelected(service.id, 'service') ? 'btn-primary' : 'btn-outline-primary'">
                                <i class="fas" :class="isSelected(service.id, 'service') ? 'fa-check' : 'fa-plus'"></i>
                                <span x-text="isSelected(service.id, 'service') ? 'Selected' : 'Select'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
    
    {{-- Bundles View --}}
    <div x-show="!loading && activeTab === 'bundles'">
        <div class="row">
            <template x-for="bundle in bundles" :key="bundle.id">
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100 border-success">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 bg-success text-white">
                            <h6 class="mb-0" x-text="bundle.name"></h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small" x-text="bundle.description"></p>
                            
                            <div class="price-display mb-3">
                                <div x-show="bundle.fixed_price" class="h5 mb-0">
                                    <span x-text="formatPrice(bundle.fixed_price)"></span>
                                </div>
                            </div>
                            
                            <button @click="addBundle(bundle)"
                                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 w-100">
                                <i class="fas fa-layer-group"></i> Configure Bundle
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
    
    {{-- Pagination --}}
    <div x-show="totalPages > 1" class="d-flex justify-center mt-4">
        <nav>
            <ul class="pagination">
                <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
                    <button class="page-link" @click="previousPage()">Previous</button>
                </li>
                
                <template x-for="page in paginationRange" :key="page">
                    <li class="page-item" :class="{ 'active': currentPage === page }">
                        <button class="page-link" @click="goToPage(page)" x-text="page"></button>
                    </li>
                </template>
                
                <li class="page-item" :class="{ 'disabled': currentPage === totalPages }">
                    <button class="page-link" @click="nextPage()">Next</button>
                </li>
            </ul>
        </nav>
    </div>
    
    
    {{-- Product Details Modal --}}
    <div x-show="showProductModal" 
         class="modal fade show block" 
         tabindex="-1"
         style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" x-show="selectedProduct">
                <div class="modal-header">
                    <h5 class="modal-title" x-text="selectedProduct?.name"></h5>
                    <button type="button" 
                            class="btn-close" 
                            @click="closeProductModal()"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Product Details</h6>
                            <dl class="row">
                                <dt class="col-sm-4">SKU:</dt>
                                <dd class="col-sm-8" x-text="selectedProduct?.sku"></dd>
                                
                                <dt class="col-sm-4">Category:</dt>
                                <dd class="col-sm-8" x-text="selectedProduct?.category"></dd>
                                
                                <dt class="col-sm-4">Billing:</dt>
                                <dd class="col-sm-8" x-text="formatBillingCycle(selectedProduct?.billing_cycle)"></dd>
                                
                                <dt class="col-sm-4">Base Price:</dt>
                                <dd class="col-sm-8" x-text="formatPrice(selectedProduct?.base_price)"></dd>
                            </dl>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>Description</h6>
                            <p x-text="selectedProduct?.description"></p>
                            
                            <div x-show="selectedProduct?.features">
                                <h6>Features</h6>
                                <ul>
                                    <template x-for="feature in selectedProduct?.features" :key="feature">
                                        <li x-text="feature"></li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div x-show="selectedProduct?.pricing_tiers?.length > 0" class="mt-3">
                        <h6>Volume Pricing</h6>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th scope="col">Quantity</th>
                                    <th scope="col">Price</th>
                                    <th scope="col">Discount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="tier in selectedProduct?.pricing_tiers" :key="tier.min_quantity">
                                    <tr>
                                        <td>
                                            <span x-text="tier.min_quantity"></span>
                                            <span x-show="tier.max_quantity">- <span x-text="tier.max_quantity"></span></span>
                                            <span x-show="!tier.max_quantity">+</span>
                                        </td>
                                        <td x-text="formatPrice(tier.price)"></td>
                                        <td>
                                            <span class="badge bg-success" x-text="`${tier.discount_percentage.toFixed(0)}%`"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" 
                            class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" 
                            @click="closeProductModal()">Close</button>
                    <button type="button" 
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            @click="toggleItem(selectedProduct, 'product'); closeProductModal();">
                        <i class="fas fa-plus"></i> Add to Selection
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- JavaScript function provided by quote-integration-simple.js --}}