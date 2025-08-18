@props([
    'type' => 'product',
    'categories' => []
])

<x-forms.section 
    title="Basic Information" 
    description="Core details about your {{ $type }}"
    :icon="'<svg class=\'w-5 h-5 text-blue-600\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z\'></path></svg>'">
    
    <!-- Name and SKU -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2">
            <x-forms.input 
                name="name" 
                label="Product Name" 
                :required="true"
                placeholder="Enter {{ $type }} name"
                x-model="name" />
        </div>
        
        <x-forms.input 
            name="sku" 
            label="SKU"
            placeholder="Auto-generated"
            x-model="sku"
            help="Leave empty to auto-generate" />
    </div>
    
    <!-- Description -->
    <x-forms.textarea 
        name="description" 
        label="Description"
        :rows="4"
        placeholder="Detailed description of the {{ $type }}..."
        x-model="description" />
    
    <!-- Type, Category, Unit Type -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @if(!isset($type) || !$type)
            <x-forms.select 
                name="type" 
                label="Type" 
                :required="true"
                x-model="type">
                <option value="product">Product</option>
                <option value="service">Service</option>
            </x-forms.select>
        @else
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="flex items-center">
                    @if($type === 'service')
                        <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Service</span>
                    @else
                        <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Product</span>
                    @endif
                </div>
            </div>
        @endif
        
        <x-forms.select 
            name="category_id" 
            label="Category" 
            :required="true"
            x-model="categoryId">
            @foreach($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </x-forms.select>
        
        <x-forms.select 
            name="unit_type" 
            label="Unit Type" 
            :required="true"
            x-model="unitType">
            <option value="units" selected>Units</option>
            <option value="hours">Hours</option>
            <option value="days">Days</option>
            <option value="weeks">Weeks</option>
            <option value="months">Months</option>
            <option value="years">Years</option>
            <option value="fixed">Fixed</option>
            <option value="subscription">Subscription</option>
        </x-forms.select>
    </div>
    
    <!-- Short Description -->
    <x-forms.textarea 
        name="short_description" 
        label="Short Description"
        :rows="2"
        placeholder="Brief summary for previews and listings (max 500 characters)"
        help="Used in summaries and previews (max 500 characters)"
        maxlength="500"
        x-model="shortDescription" />
        
</x-forms.section>