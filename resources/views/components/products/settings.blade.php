@props([])

<div class="space-y-8">
    <!-- Status & Settings -->
    <x-forms.section 
        title="Status & Settings" 
        description="Product visibility and ordering"
        :icon="'<svg class=\'w-5 h-5 text-purple-600\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4\'></path></svg>'"
        class="mb-8">
        
        <div class="space-y-4">
            <x-forms.checkbox 
                name="is_active" 
                label="Active"
                :checked="true"
                help="Product is available for selection"
                x-model="isActive" />
                
            <x-forms.checkbox 
                name="is_featured" 
                label="Featured Product"
                help="Highlight in product lists"
                x-model="isFeatured" />
        </div>
        
        <x-forms.input 
            name="sort_order" 
            label="Sort Order"
            type="number"
            min="0"
            :value="old('sort_order', 0)"
            help="Lower numbers appear first"
            x-model="sortOrder" />
            
    </x-forms.section>
    
    <!-- Inventory Management -->
    <x-forms.section 
        title="Inventory Management" 
        description="Stock tracking and levels"
        :icon="'<svg class=\'w-5 h-5 text-orange-600\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4\'></path></svg>'"
        class="mb-8">
        
        <x-forms.checkbox 
            name="track_inventory" 
            label="Track Inventory"
            help="Monitor stock levels for this product"
            x-model="trackInventory" />
            
        <!-- Inventory Fields (shown when tracking is enabled) -->
        <div x-show="trackInventory" x-transition class="space-y-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-forms.input 
                    name="current_stock" 
                    label="Current Stock"
                    type="number"
                    min="0"
                    :value="old('current_stock', 0)"
                    x-model="currentStock" />
                    
                <x-forms.input 
                    name="min_stock_level" 
                    label="Minimum Stock Level"
                    type="number"
                    min="0"
                    :value="old('min_stock_level', 0)"
                    help="Alert when stock falls below this level"
                    x-model="minStockLevel" />
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-forms.input 
                    name="reorder_level" 
                    label="Reorder Level"
                    type="number"
                    min="0"
                    :value="old('reorder_level', '')"
                    help="Trigger reorder when stock reaches this level"
                    x-model="reorderLevel" />
                    
                <x-forms.input 
                    name="max_quantity_per_order" 
                    label="Max Quantity Per Order"
                    type="number"
                    min="1"
                    :value="old('max_quantity_per_order', '')"
                    help="Maximum quantity allowed per order"
                    x-model="maxQuantityPerOrder" />
            </div>
        </div>
        
    </x-forms.section>
    
    <!-- Actions -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-900/5 overflow-hidden">
        <div class="p-6">
            <div class="flex flex-flex-1 px-6 space-y-3 sm:flex-flex flex-wrap -mx-4 sm:space-y-0 sm:space-x-3">
                <button type="submit" 
                        x-bind:disabled="!isFormValid || submitting"
                        x-bind:class="isFormValid && !submitting ? 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500' : 'bg-gray-400 cursor-not-allowed'"
                        class="flex-1 sm:flex-initial inline-flex items-center justify-center px-6 py-6 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200">
                    <svg x-show="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg x-show="!submitting" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span x-show="!submitting">Create Product</span>
                    <span x-show="submitting">Creating...</span>
                </button>
                
                <a href="{{ url()->previous() }}"
                   class="inline-flex items-center justify-center px-6 py-6 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Cancel
                </a>
            </div>
            
            <!-- Form Status -->
            <div class="mt-6 flex items-center text-sm text-gray-500 dark:text-gray-400">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Fields marked with <span class="text-red-500">*</span> are required</span>
            </div>
        </div>
    </div>
    
</div>
