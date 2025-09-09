@props([
    'currentCompany' => null,
    'availableCompanies' => [],
    'showBreadcrumb' => true
])

@php
    $currentCompanyId = $currentCompany ? $currentCompany->id : (auth()->user()->company_id ?? null);
    $userCompany = auth()->user()->company ?? null;
@endphp

<div {{ $attributes->merge(['class' => 'company-switcher']) }} x-data="companySwitcher()">
    <div class="relative">
        <!-- Current Company Display -->
        <button @click="open = !open" 
                class="w-full flex items-center justify-between px-6 py-2 text-left bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <div class="flex items-center min-w-0 flex-1">
                <!-- Company Icon -->
                <div class="flex-shrink-0 mr-3">
                    @if($currentCompany)
                        @php
                            $bgColor = match($currentCompany->company_type) {
                                'root' => 'from-blue-500 to-blue-600',
                                'subsidiary' => 'from-green-500 to-green-600',
                                'branch' => 'from-purple-500 to-purple-600',
                                default => 'from-gray-500 to-gray-600'
                            };
                        @endphp
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br {{ $bgColor }} flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                    @else
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-gray-500 to-gray-600 flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                    @endif
                </div>

                <!-- Company Information -->
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-medium text-gray-900 truncate">
                        {{ $currentCompany ? $currentCompany->name : 'Select Company' }}
                    </div>
                    @if($currentCompany)
                        <div class="flex items-center text-xs text-gray-500 mt-0.5">
                            <span class="capitalize">{{ $currentCompany->company_type ?? 'company' }}</span>
                            @if($currentCompany->id !== $userCompany?->id)
                                <span class="mx-1">•</span>
                                <span class="text-blue-600">Cross-Company Access</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Dropdown Arrow -->
            <svg class="flex-shrink-0 ml-2 h-5 w-5 text-gray-400 transition-transform duration-200"
                 :class="{ 'rotate-180': open }"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <div x-show="open" 
             @click.outside="open = false"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="absolute z-50 mt-1 w-full bg-white shadow-lg max-h-80 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
            
            @if(count($availableCompanies) > 0)
                <!-- Search -->
                <div class="px-6 py-2 border-b border-gray-200">
                    <input x-model="searchTerm" 
                           type="text" 
                           placeholder="Search companies..."
                           class="w-full px-6 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Company List -->
                <div class="py-1">
                    <template x-for="company in filteredCompanies" :key="company.id">
                        <button @click="switchCompany(company)"
                                class="group w-full text-left px-6 py-2 text-sm text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 flex items-center"
                                :class="{ 'bg-blue-50 text-blue-600': company.id == {{ $currentCompanyId }} }">
                            
                            <!-- Company Icon -->
                            <div class="flex-shrink-0 mr-3">
                                <div class="w-6 h-6 rounded bg-gradient-to-br flex items-center justify-center text-white text-xs"
                                     :class="{
                                        'from-blue-500 to-blue-600': company.type === 'root',
                                        'from-green-500 to-green-600': company.type === 'subsidiary',
                                        'from-purple-500 to-purple-600': company.type === 'branch',
                                        'from-gray-500 to-gray-600': !company.type || company.type === 'unknown'
                                     }">
                                    <template x-if="company.type === 'root'">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm0 2h12v8H4V6z" clip-rule="evenodd" />
                                        </svg>
                                    </template>
                                    <template x-if="company.type !== 'root'">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z" />
                                            <path fill-rule="evenodd" d="M3 8a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </template>
                                </div>
                            </div>

                            <!-- Company Details -->
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900 truncate" x-text="company.name"></div>
                                <div class="flex items-center text-xs text-gray-500 mt-0.5">
                                    <span class="capitalize" x-text="company.type"></span>
                                    <template x-if="!company.is_primary">
                                        <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Access
                                        </span>
                                    </template>
                                </div>
                            </div>

                            <!-- Current Indicator -->
                            <template x-if="company.id == {{ $currentCompanyId }}">
                                <div class="flex-shrink-0 ml-2">
                                    <svg class="h-4 w-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </template>
                        </button>
                    </template>
                </div>

                <!-- No Results -->
                <div x-show="filteredCompanies.length === 0" class="px-6 py-6 text-center text-sm text-gray-500">
                    No companies found matching your search.
                </div>
            @else
                <div class="px-6 py-6 text-center text-sm text-gray-500">
                    No additional companies available.
                </div>
            @endif
        </div>
    </div>

    <!-- Breadcrumb Path (Optional) -->
    @if($showBreadcrumb && $currentCompany)
        <div class="mt-2 text-xs text-gray-500">
            <div class="flex items-center space-x-1">
                @if($currentCompany->parentCompany)
                    <!-- Show hierarchy path -->
                    @php
                        $path = [];
                        $company = $currentCompany;
                        while($company->parentCompany) {
                            array_unshift($path, $company->parentCompany);
                            $company = $company->parentCompany;
                        }
                    @endphp
                    
                    @foreach($path as $ancestor)
                        <span>{{ $ancestor->name }}</span>
                        <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    @endforeach
                    
                    <span class="font-medium text-gray-700">{{ $currentCompany->name }}</span>
                @else
                    <span class="font-medium text-gray-700">{{ $currentCompany->name }} (Root)</span>
                @endif
            </div>
        </div>
    @endif
</div>

<script>
    function companySwitcher() {
        return {
            open: false,
            searchTerm: '',
            companies: @json($availableCompanies),
            
            get filteredCompanies() {
                if (!this.searchTerm) {
                    return this.companies;
                }
                
                return this.companies.filter(company => 
                    company.name.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
                    company.type.toLowerCase().includes(this.searchTerm.toLowerCase())
                );
            },
            
            async switchCompany(company) {
                try {
                    const response = await fetch('/switch-company', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ company_id: company.id })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Reload the page to refresh with new company context
                        window.location.reload();
                    } else {
                        alert('Failed to switch company: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error switching company:', error);
                    alert('An error occurred while switching companies.');
                }
                
                this.open = false;
            }
        };
    }
</script>
