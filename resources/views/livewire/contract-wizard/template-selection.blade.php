<flux:card>
    <!-- Header Section -->
    <div class="mb-6">
        <flux:heading size="lg" class="mb-2">Choose a Contract Template</flux:heading>
        <flux:text size="sm" variant="muted">
            Select a pre-built template to get started quickly, or choose to create a custom contract from scratch.
        </flux:text>
    </div>

    <!-- Filter Section -->
    <div class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search Input -->
            <div>
                <flux:input 
                    wire:model.live.debounce.300ms="searchQuery"
                    type="search"
                    placeholder="Search templates..."
                    icon="magnifying-glass"
                />
            </div>

            <!-- Category Filter -->
            <div>
                <flux:select wire:model.debounce.150ms="categoryFilter" placeholder="All Categories">
                    <flux:select.option value="">All Categories</flux:select.option>
                    @foreach($this->availableCategories as $category)
                        <flux:select.option value="{{ $category }}">
                            {{ $this->getCategoryLabel($category) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <!-- Billing Model Filter -->
            <div>
                <flux:select wire:model.debounce.150ms="billingModelFilter" placeholder="All Billing Models">
                    <flux:select.option value="">All Billing Models</flux:select.option>
                    @foreach($this->availableBillingModels as $billingModel)
                        <flux:select.option value="{{ $billingModel }}">
                            {{ $this->getBillingModelLabel($billingModel) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>
    </div>

    <!-- Hidden Template Alert -->
    @if($selectedTemplate && $this->isSelectedTemplateHidden())
        <div class="mb-4">
            <flux:card variant="outline" class="border-amber-200 bg-amber-50">
                <div class="p-4">
                    <div class="flex items-center space-x-2 text-amber-800">
                        <flux:icon name="exclamation-triangle" class="h-5 w-5" />
                        <flux:text size="sm" class="font-medium">
                            Selected template "{{ $selectedTemplate['name'] }}" is hidden by current filters.
                        </flux:text>
                    </div>
                    <div class="mt-2">
                        <flux:button 
                            size="sm" 
                            variant="outline" 
                            wire:click="clearFilters"
                            class="text-amber-800 border-amber-300 hover:bg-amber-100"
                        >
                            Clear filters to show
                        </flux:button>
                    </div>
                </div>
            </flux:card>
        </div>
    @endif

    <!-- Templates Grid -->
    <div wire:loading.remove wire:target="searchQuery,categoryFilter,billingModelFilter">
        <!-- Custom Contract Option - Always Available -->
        <div class="mb-6">
            <flux:card 
                wire:key="custom-template-card"
                class="cursor-pointer border-2 hover:shadow-md transition-all duration-200 {{ $selectedTemplate && $selectedTemplate['id'] === null ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}"
                wire:click="selectCustomContract"
            >
                <div class="p-4">
                    <div class="flex items-center justify-between mb-2">
                        <flux:heading size="sm">Custom Contract</flux:heading>
                        <flux:badge size="sm" class="border border-gray-300 bg-gray-100 text-gray-700">Custom</flux:badge>
                    </div>
                    
                    <flux:text size="sm" variant="muted" class="mb-3">
                        Create a contract from scratch without using a pre-built template.
                    </flux:text>
                    
                    <div class="flex items-center justify-between">
                        <flux:text size="xs" variant="muted">Start fresh</flux:text>
                        @if($selectedTemplate && $selectedTemplate['id'] === null)
                            <flux:badge size="sm" class="bg-blue-500 text-white">Selected</flux:badge>
                        @endif
                    </div>
                </div>
            </flux:card>
        </div>

        @if(count($filteredTemplates) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Template Cards -->
                @foreach($filteredTemplates as $template)
                    <flux:card 
                        wire:key="template-card-{{ $template['id'] }}"
                        class="cursor-pointer border-2 hover:shadow-md transition-all duration-200 {{ $selectedTemplate && $selectedTemplate['id'] === $template['id'] ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}"
                        wire:click="selectTemplateById({{ $template['id'] }})"
                    >
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-2">
                                <flux:heading size="sm">{{ $template['name'] }}</flux:heading>
                                @if($template['category'])
                                    <flux:badge size="sm" class="border border-gray-300 bg-gray-100 text-gray-700">{{ $this->getCategoryLabel($template['category']) }}</flux:badge>
                                @endif
                            </div>
                            
                            @if($template['description'])
                                <flux:text size="sm" variant="muted" class="mb-3 line-clamp-2">
                                    {{ $template['description'] }}
                                </flux:text>
                            @endif
                            
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    @if($template['billing_model'])
                                        <flux:badge size="xs" class="border border-green-300 bg-green-100 text-green-700">{{ $this->getBillingModelLabel($template['billing_model']) }}</flux:badge>
                                    @endif
                                    <flux:text size="xs" variant="muted">Used {{ $template['usage_count'] ?? 0 }} times</flux:text>
                                </div>
                                
                                @if($selectedTemplate && $selectedTemplate['id'] === $template['id'])
                                    <flux:badge size="sm" class="bg-blue-500 text-white">Selected</flux:badge>
                                @endif
                            </div>
                        </div>
                    </flux:card>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="text-center py-12">
                <div class="mx-auto h-12 w-12 text-gray-400 mb-4">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <flux:heading size="sm" class="mb-2">No templates found</flux:heading>
                <flux:text size="sm" variant="muted" class="mb-4">
                    No templates match your current filters. Try adjusting your search criteria.
                </flux:text>
                <flux:button 
                    variant="outline" 
                    size="sm"
                    wire:click="clearFilters"
                >
                    Clear filters
                </flux:button>
            </div>
        @endif
    </div>

    <!-- Loading State -->
    <div wire:loading wire:target="searchQuery,categoryFilter,billingModelFilter" class="text-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
        <flux:text size="sm" variant="muted">Filtering templates...</flux:text>
    </div>

    <!-- Selected Template Details -->
    @if($selectedTemplate)
        <div class="mt-6 pt-6 border-t border-gray-200">
            <flux:heading size="sm" class="mb-2">Selected Template Details</flux:heading>
            <flux:card variant="outline">
                <div class="p-4">
                    <div class="flex items-center justify-between mb-2">
                        <flux:heading size="sm">{{ $selectedTemplate['name'] }}</flux:heading>
                        <div class="flex items-center space-x-2">
                            @if($selectedTemplate['category'])
                                <flux:badge size="sm" class="border border-gray-300 bg-gray-100 text-gray-700">{{ $this->getCategoryLabel($selectedTemplate['category']) }}</flux:badge>
                            @endif
                            @if($selectedTemplate['billing_model'])
                                <flux:badge size="sm" class="border border-green-300 bg-green-100 text-green-700">{{ $this->getBillingModelLabel($selectedTemplate['billing_model']) }}</flux:badge>
                            @endif
                        </div>
                    </div>
                    
                    @if($selectedTemplate['description'])
                        <flux:text size="sm" variant="muted">
                            {{ $selectedTemplate['description'] }}
                        </flux:text>
                    @endif
                    
                    @if($selectedTemplate['id'] && isset($selectedTemplate['usage_count']))
                        <div class="mt-3">
                            <flux:text size="xs" variant="muted">
                                This template has been used {{ $selectedTemplate['usage_count'] }} times
                            </flux:text>
                        </div>
                    @endif
                </div>
            </flux:card>
        </div>
    @endif
</flux:card>
