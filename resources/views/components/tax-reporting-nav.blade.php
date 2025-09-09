@props([
    'active' => false,
    'collapsed' => false,
])

@php
    $currentRoute = request()->route()->getName();
    $isTaxReportActive = str_starts_with($currentRoute, 'reports.tax.');
@endphp

<div class="relative" x-data="{ open: {{ $isTaxReportActive ? 'true' : 'false' }} }">
    <!-- Main Tax Reports Menu Item -->
    <div class="flex items-center justify-between w-full">
        <a href="{{ route('reports.tax.index') }}" 
           class="flex items-center px-6 py-2 text-sm font-medium rounded-md transition-colors duration-200 {{ $isTaxReportActive ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <i class="fas fa-calculator {{ $collapsed ? '' : 'mr-3' }} flex-shrink-0"></i>
            @if(!$collapsed)
                <span>Tax Reports</span>
            @endif
        </a>
        
        @if(!$collapsed)
            <button @click="open = !open" class="p-1 rounded hover:bg-gray-100 transition-colors duration-200">
                <i class="fas fa-chevron-down transform transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
            </button>
        @endif
    </div>
    
    <!-- Submenu Items -->
    @if(!$collapsed)
        <div x-show="open" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95"
             class="ml-6 mt-2 space-y-1">
            
            <a href="{{ route('reports.tax.index') }}" 
               class="flex items-center px-6 py-2 text-sm rounded-md transition-colors duration-200 {{ $currentRoute === 'reports.tax.index' ? 'bg-blue-50 text-blue-600 border-l-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <i class="fas fa-chart-bar mr-3 text-xs"></i>
                <span>Tax Overview</span>
            </a>
            
            <a href="{{ route('reports.tax.summary') }}" 
               class="flex items-center px-6 py-2 text-sm rounded-md transition-colors duration-200 {{ $currentRoute === 'reports.tax.summary' ? 'bg-blue-50 text-blue-600 border-l-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <i class="fas fa-chart-pie mr-3 text-xs"></i>
                <span>Tax Summary</span>
            </a>
            
            <a href="{{ route('reports.tax.jurisdictions') }}" 
               class="flex items-center px-6 py-2 text-sm rounded-md transition-colors duration-200 {{ $currentRoute === 'reports.tax.jurisdictions' ? 'bg-blue-50 text-blue-600 border-l-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <i class="fas fa-map-marker-alt mr-3 text-xs"></i>
                <span>Jurisdictions</span>
            </a>
            
            <a href="{{ route('reports.tax.compliance') }}" 
               class="flex items-center px-6 py-2 text-sm rounded-md transition-colors duration-200 {{ $currentRoute === 'reports.tax.compliance' ? 'bg-blue-50 text-blue-600 border-l-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <i class="fas fa-shield-alt mr-3 text-xs"></i>
                <span>Compliance</span>
            </a>
            
            <a href="{{ route('reports.tax.performance') }}" 
               class="flex items-center px-6 py-2 text-sm rounded-md transition-colors duration-200 {{ $currentRoute === 'reports.tax.performance' ? 'bg-blue-50 text-blue-600 border-l-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <i class="fas fa-tachometer-alt mr-3 text-xs"></i>
                <span>Performance</span>
            </a>
        </div>
    @endif
</div>

@if($collapsed)
    <!-- Tooltip for collapsed state -->
    <div class="absolute left-12 top-0 z-50 hidden group-hover:block bg-gray-900 text-white text-xs rounded py-1 px-2 whitespace-nowrap">
        Tax Reports
        <div class="absolute left-0 top-1/2 transform -translate-y-1/2 -translate-x-1 border-4 border-transparent border-r-gray-900"></div>
    </div>
@endif
