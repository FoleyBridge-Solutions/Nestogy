@props([
    'id' => null,
    'title' => '',
    'subtitle' => '',
    'icon' => null,
    'iconColor' => 'gray',
    'expanded' => false
])

<div class="border border-gray-200 rounded-lg" 
     x-data="{ isExpanded: @json($expanded) }">
    <button type="button"
            @click="isExpanded = !isExpanded"
            class="w-full px-6 py-6 flex items-center justify-between hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 rounded-t-lg"
            :class="{ 'rounded-b-lg': !isExpanded }">
        <div class="flex items-center">
            @if($icon)
                <div class="w-10 h-10 bg-{{ $iconColor }}-100 rounded-lg flex items-center justify-center mr-3">
                    {{ $icon }}
                </div>
            @endif
            <div class="text-left">
                <h3 class="font-medium text-gray-900">{{ $title }}</h3>
                @if($subtitle)
                    <p class="text-sm text-gray-500">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200"
             :class="{'rotate-180': isExpanded}"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>
    
    <div x-show="isExpanded" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-2"
         class="px-6 pb-4 border-t border-gray-200">
        {{ $slot }}
    </div>
</div>
