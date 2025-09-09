<div class="max-w-6xl mx-auto py-8">
    <!-- Wizard Header -->
    <div class="mb-8">
        <flux:card class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Contract Creation Wizard</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $stepNames[$currentStep] ?? 'Contract Wizard' }}</p>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Step {{ $currentStep }} of {{ $totalSteps }}
                </div>
            </div>
            
            <!-- Progress Indicator -->
            <div class="flex items-center space-x-4">
                @for ($step = 1; $step <= $totalSteps; $step++)
                    <div class="flex items-center @if(!$loop->last) flex-1 @endif">
                        <!-- Step Circle -->
                        <div class="relative">
                            <flux:button 
                                wire:click="goToStep({{ $step }})"
                                :disabled="!$this->canGoToStep($step)"
                                variant="{{ $step <= $currentStep ? 'primary' : 'ghost' }}"
                                size="sm"
                                class="w-10 h-10 rounded-full flex items-center justify-center {{ $step == $currentStep ? 'ring-2 ring-primary-300' : '' }}"
                            >
                                @if ($step < $currentStep)
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    {{ $step }}
                                @endif
                            </flux:button>
                        </div>
                        
                        <!-- Step Label -->
                        <div class="ml-3 min-w-0 @if(!$loop->last) flex-1 @endif">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $stepNames[$step] ?? 'Step '.$step }}
                            </p>
                        </div>
                        
                        <!-- Progress Line -->
                        @if (!$loop->last)
                            <div class="flex-1 mx-4">
                                <div class="h-0.5 {{ $step < $currentStep ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                            </div>
                        @endif
                    </div>
                @endfor
            </div>
        </flux:card>
    </div>

    <!-- Wizard Content -->
    <flux:card class="p-8">
        <!-- Loading State -->
        <div wire:loading class="absolute inset-0 bg-white/75 dark:bg-gray-800/75 flex items-center justify-center z-10">
            <div class="flex items-center space-x-2">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
                <span class="text-gray-600 dark:text-gray-400">Loading...</span>
            </div>
        </div>

        <!-- Step Content -->
        <div class="min-h-96">
            @switch($currentStep)
                @case(1)
                    <!-- Step 1: Template Selection -->
                    @livewire('contract-wizard.template-selection', ['selectedTemplate' => $selectedTemplate])
                    @break

                @case(2)
                    <!-- Step 2: Contract Details -->
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Contract Details</h2>
                            <p class="text-gray-600 dark:text-gray-400 mb-6">Configure the contract details and client selection.</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <flux:field>
                                    <flux:label>Client</flux:label>
                                    <flux:select 
                                        wire:model.live="data.contract_details.client_id"
                                        placeholder="Select a client..."
                                        searchable
                                    >
                                        <flux:select.option value="1">Acme Corporation</flux:select.option>
                                        <flux:select.option value="2">TechStart LLC</flux:select.option>
                                        <flux:select.option value="3">Global Industries</flux:select.option>
                                    </flux:select>
                                </flux:field>
                            </div>
                        </div>
                        
                        @if(!empty($data['contract_details']['client_id']))
                            <div class="mt-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <p class="text-green-700 dark:text-green-300">✓ Client selected successfully</p>
                            </div>
                        @endif
                    </div>
                    @break

                @case(3)
                    <!-- Step 3: Asset Assignment -->
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Asset Assignment</h2>
                            <p class="text-gray-600 dark:text-gray-400 mb-6">Select and assign assets to this contract.</p>
                        </div>
                        
                        <div class="space-y-4">
                            <flux:checkbox.group wire:model.live="data.assets">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <flux:card class="p-4">
                                        <flux:checkbox value="server_1" />
                                        <div class="ml-3">
                                            <h3 class="font-medium text-gray-900 dark:text-white">Primary Server</h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Dell PowerEdge R740 - Main application server</p>
                                        </div>
                                    </flux:card>
                                    
                                    <flux:card class="p-4">
                                        <flux:checkbox value="firewall_1" />
                                        <div class="ml-3">
                                            <h3 class="font-medium text-gray-900 dark:text-white">Network Firewall</h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">SonicWall NSa 3700 - Network protection</p>
                                        </div>
                                    </flux:card>
                                    
                                    <flux:card class="p-4">
                                        <flux:checkbox value="workstations" />
                                        <div class="ml-3">
                                            <h3 class="font-medium text-gray-900 dark:text-white">Workstations (15)</h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Dell OptiPlex desktops and laptops</p>
                                        </div>
                                    </flux:card>
                                    
                                    <flux:card class="p-4">
                                        <flux:checkbox value="backup_nas" />
                                        <div class="ml-3">
                                            <h3 class="font-medium text-gray-900 dark:text-white">Backup Storage</h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Synology DS1819+ NAS - Data backup</p>
                                        </div>
                                    </flux:card>
                                </div>
                            </flux:checkbox.group>
                        </div>
                    </div>
                    @break

                @case(4)
                    <!-- Step 4: Infrastructure Schedule -->
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Infrastructure Schedule</h2>
                            <p class="text-gray-600 dark:text-gray-400 mb-6">Configure infrastructure pricing and service schedules.</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <flux:field>
                                <flux:label>Service Level</flux:label>
                                <flux:select wire:model.live="data.infrastructure.service_level">
                                    <flux:select.option value="basic">Basic (Business Hours)</flux:select.option>
                                    <flux:select.option value="standard">Standard (Extended Hours)</flux:select.option>
                                    <flux:select.option value="premium">Premium (24/7)</flux:select.option>
                                </flux:select>
                            </flux:field>
                            
                            <flux:field>
                                <flux:label>Billing Frequency</flux:label>
                                <flux:select wire:model.live="data.infrastructure.billing_frequency">
                                    <flux:select.option value="monthly">Monthly</flux:select.option>
                                    <flux:select.option value="quarterly">Quarterly</flux:select.option>
                                    <flux:select.option value="annually">Annually</flux:select.option>
                                </flux:select>
                            </flux:field>
                        </div>
                        
                        <flux:field>
                            <flux:label>Monthly Rate</flux:label>
                            <flux:input 
                                wire:model.live="data.infrastructure.monthly_rate"
                                placeholder="Enter monthly rate..."
                                type="number"
                                step="0.01"
                            />
                        </flux:field>
                        
                        <flux:field>
                            <flux:label>Additional Notes</flux:label>
                            <flux:textarea 
                                wire:model.live="data.infrastructure.notes"
                                placeholder="Enter any additional notes about the infrastructure schedule..."
                                rows="4"
                            />
                        </flux:field>
                    </div>
                    @break

                @case(5)
                    <!-- Step 5: Review & Generate -->
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Review & Generate</h2>
                            <p class="text-gray-600 dark:text-gray-400 mb-6">Review your contract configuration before generating.</p>
                        </div>
                        
                        <div class="space-y-6">
                            <!-- Contract Summary -->
                            <flux:card class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Contract Summary</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Client:</span>
                                        <span class="text-gray-900 dark:text-white">
                                            @if($data['contract_details']['client_id'] == 1) Acme Corporation
                                            @elseif($data['contract_details']['client_id'] == 2) TechStart LLC  
                                            @elseif($data['contract_details']['client_id'] == 3) Global Industries
                                            @else Not selected @endif
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Template:</span>
                                        <span class="text-gray-900 dark:text-white">{{ $selectedTemplate['name'] ?? 'Not selected' }}</span>
                                    </div>
                                    @if($selectedTemplate && $selectedTemplate['category'])
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Category:</span>
                                        <span class="text-gray-900 dark:text-white">{{ $selectedTemplate['category'] }}</span>
                                    </div>
                                    @endif
                                    @if($selectedTemplate && $selectedTemplate['billing_model'])
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Billing Model:</span>
                                        <span class="text-gray-900 dark:text-white">{{ $selectedTemplate['billing_model'] }}</span>
                                    </div>
                                    @endif
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Assets:</span>
                                        <span class="text-gray-900 dark:text-white">{{ count($data['assets'] ?? []) }} selected</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Monthly Rate:</span>
                                        <span class="text-gray-900 dark:text-white">${{ number_format($data['infrastructure']['monthly_rate'] ?? 0, 2) }}</span>
                                    </div>
                                </div>
                            </flux:card>
                            
                            <!-- Ready Indicator -->
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <p class="text-blue-700 dark:text-blue-300">ℹ️ Contract wizard shell is ready for testing. Step components will be added in subsequent phases.</p>
                            </div>
                        </div>
                    </div>
                    @break
            @endswitch
        </div>

        <!-- Navigation Buttons -->
        <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="flex space-x-3">
                <flux:button 
                    wire:click="previousStep" 
                    variant="ghost"
                    :disabled="$currentStep == 1"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Previous
                </flux:button>
                
                <flux:button 
                    wire:click="saveDraft"
                    variant="ghost"
                    wire:loading.attr="disabled"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <span wire:loading.remove>Save Draft</span>
                    <span wire:loading>Saving...</span>
                </flux:button>
            </div>
            
            <div>
                @if ($currentStep == $totalSteps)
                    <flux:button 
                        wire:click="saveDraft"
                        variant="primary"
                        wire:loading.attr="disabled"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span wire:loading.remove>Generate Contract</span>
                        <span wire:loading>Generating...</span>
                    </flux:button>
                @else
                    <flux:button 
                        wire:click="nextStep"
                        variant="primary"
                        :disabled="!$this->canGoToStep($currentStep + 1)"
                    >
                        Next
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </flux:button>
                @endif
            </div>
        </div>
    </flux:card>
</div>
