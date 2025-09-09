<div class="max-w-4xl mx-auto py-8">
    <!-- Simple Wizard Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Contract Creation Wizard</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $this->getStepTitle() }}</p>
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Step {{ $currentStep }} of {{ $totalSteps }}
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                 style="width: {{ ($currentStep / $totalSteps) * 100 }}%"></div>
        </div>
        
        <!-- Step Labels -->
        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
            <span class="{{ $currentStep >= 1 ? 'text-blue-600 font-semibold' : '' }}">Client</span>
            <span class="{{ $currentStep >= 2 ? 'text-blue-600 font-semibold' : '' }}">Type</span>
            <span class="{{ $currentStep >= 3 ? 'text-blue-600 font-semibold' : '' }}">Services</span>
            <span class="{{ $currentStep >= 4 ? 'text-blue-600 font-semibold' : '' }}">Terms</span>
            <span class="{{ $currentStep >= 5 ? 'text-blue-600 font-semibold' : '' }}">Review</span>
        </div>
    </div>

    <!-- Wizard Content -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8">
        <!-- Loading State -->
        <div wire:loading class="absolute inset-0 bg-white/75 dark:bg-gray-800/75 flex items-center justify-center z-10 rounded-lg">
            <div class="flex items-center space-x-2">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="text-gray-600 dark:text-gray-400">Loading...</span>
            </div>
        </div>

        <!-- Step Content -->
        <div class="min-h-96">
            @switch($currentStep)
                @case(1)
                    <!-- Step 1: Client Selection -->
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Select Client</h2>
                            <p class="text-gray-600 dark:text-gray-400 mb-6">Choose the client for this contract.</p>
                        </div>
                        
                        <div class="max-w-md">
                            <label for="client_select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Client
                            </label>
                            <select wire:model.live="data.client_id" id="client_select" 
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">Select a client...</option>
                                <option value="1">Acme Corporation</option>
                                <option value="2">TechStart LLC</option>
                                <option value="3">Global Industries</option>
                            </select>
                        </div>
                        
                        @if(!empty($data['client_id']))
                            <div class="mt-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <p class="text-green-700 dark:text-green-300">✓ Client selected successfully</p>
                            </div>
                        @endif
                    </div>
                    @break

                @case(2)
                    <!-- Step 2: Contract Type -->
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Choose Contract Type</h2>
                            <p class="text-gray-600 dark:text-gray-400 mb-6">Select the type of contract to create.</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <label class="relative">
                                <input type="radio" wire:model.live="data.contract_type" value="managed_services" 
                                       class="sr-only peer">
                                <div class="p-4 border-2 rounded-lg cursor-pointer peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <h3 class="font-medium text-gray-900 dark:text-white">Managed Services</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Ongoing IT support and maintenance</p>
                                </div>
                            </label>
                            
                            <label class="relative">
                                <input type="radio" wire:model.live="data.contract_type" value="project_based" 
                                       class="sr-only peer">
                                <div class="p-4 border-2 rounded-lg cursor-pointer peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <h3 class="font-medium text-gray-900 dark:text-white">Project-Based</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Fixed-scope project delivery</p>
                                </div>
                            </label>
                            
                            <label class="relative">
                                <input type="radio" wire:model.live="data.contract_type" value="maintenance" 
                                       class="sr-only peer">
                                <div class="p-4 border-2 rounded-lg cursor-pointer peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <h3 class="font-medium text-gray-900 dark:text-white">Maintenance</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Equipment and software maintenance</p>
                                </div>
                            </label>
                        </div>
                    </div>
                    @break

                @case(3)
                    <!-- Step 3: Services Configuration -->
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Configure Services</h2>
                            <p class="text-gray-600 dark:text-gray-400 mb-6">Select the services included in this contract.</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach(['monitoring' => '24/7 Monitoring', 'backup' => 'Backup Management', 'security' => 'Security Management', 'support' => 'Help Desk Support'] as $value => $label)
                                <label class="flex items-start space-x-3 p-4 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <input type="checkbox" wire:model.live="data.services" value="{{ $value }}" 
                                           class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <div>
                                        <h3 class="font-medium text-gray-900 dark:text-white">{{ $label }}</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            @if($value === 'monitoring') Continuous system monitoring and alerts
                                            @elseif($value === 'backup') Automated backup and recovery services
                                            @elseif($value === 'security') Endpoint protection and security updates  
                                            @else User support and troubleshooting
                                            @endif
                                        </p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    @break

                @case(4)
                    <!-- Step 4: Terms & Conditions -->
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Set Terms & Conditions</h2>
                            <p class="text-gray-600 dark:text-gray-400 mb-6">Define the contract terms and conditions.</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="duration" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Contract Duration
                                </label>
                                <select wire:model.live="data.terms.duration" id="duration"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="">Select duration...</option>
                                    <option value="12">12 Months</option>
                                    <option value="24">24 Months</option>
                                    <option value="36">36 Months</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="payment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Payment Terms
                                </label>
                                <select wire:model.live="data.terms.payment" id="payment"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="">Select payment terms...</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="annually">Annually</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label for="special_terms" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Special Terms
                            </label>
                            <textarea wire:model.live="data.terms.special_terms" id="special_terms" rows="4"
                                      placeholder="Enter any special terms or conditions..."
                                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>
                    </div>
                    @break

                @case(5)
                    <!-- Step 5: Review & Create -->
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Review & Create</h2>
                            <p class="text-gray-600 dark:text-gray-400 mb-6">Review your contract configuration before creating.</p>
                        </div>
                        
                        <!-- Contract Summary -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Contract Summary</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Client:</span>
                                    <span class="text-gray-900 dark:text-white">
                                        @if($data['client_id'] == 1) Acme Corporation
                                        @elseif($data['client_id'] == 2) TechStart LLC  
                                        @elseif($data['client_id'] == 3) Global Industries
                                        @else Not selected @endif
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Type:</span>
                                    <span class="text-gray-900 dark:text-white capitalize">{{ str_replace('_', ' ', $data['contract_type'] ?? 'Not selected') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Services:</span>
                                    <span class="text-gray-900 dark:text-white">{{ count($data['services'] ?? []) }} selected</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Success Message -->
                        @if($this->validateAllSteps())
                            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <p class="text-green-700 dark:text-green-300">✓ All required information has been provided. Ready to create contract!</p>
                            </div>
                        @endif
                    </div>
                    @break
            @endswitch
        </div>

        <!-- Navigation Buttons -->
        <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="flex space-x-3">
                <button wire:click="previousStep" 
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                        {{ $currentStep == 1 ? 'disabled' : '' }}>
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Previous
                </button>
                
                <button wire:click="saveDraft" wire:loading.attr="disabled"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <span wire:loading.remove>Save Draft</span>
                    <span wire:loading>Saving...</span>
                </button>
            </div>
            
            <div>
                @if ($currentStep == $totalSteps)
                    <button wire:click="createContract" wire:loading.attr="disabled"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                            {{ !$this->validateAllSteps() ? 'disabled' : '' }}>
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span wire:loading.remove>Create Contract</span>
                        <span wire:loading>Creating...</span>
                    </button>
                @else
                    <button wire:click="nextStep"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                            {{ !$this->validateCurrentStep() ? 'disabled' : '' }}>
                        Next
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
