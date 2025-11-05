<div>
    <!-- Header -->
    <div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-6 py-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <flux:heading size="xl">
                        {{ $contract ? 'Edit Contract: ' . $contract->contract_number : 'Create Contract' }}
                    </flux:heading>
                    <flux:text class="mt-2">
                        Step {{ $currentStep }} of {{ $totalSteps }}
                    </flux:text>
                </div>
                <flux:button href="{{ route('financial.contracts.index') }}" variant="ghost">
                    Cancel
                </flux:button>
            </div>
            
            <!-- Progress Bar -->
            <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                     style="width: {{ ($currentStep / $totalSteps) * 100 }}%"></div>
            </div>
        </div>
    </div>

    <!-- Error Messages -->
    @if (session()->has('error'))
        <div class="max-w-7xl mx-auto px-6 mt-6">
            <flux:callout variant="danger" icon="x-circle">
                <flux:callout.heading>Error</flux:callout.heading>
                <flux:callout.text>{{ session('error') }}</flux:callout.text>
            </flux:callout>
        </div>
    @endif

    @if ($errors->any())
        <div class="max-w-7xl mx-auto px-6 mt-6">
            <flux:callout variant="danger" icon="exclamation-circle">
                <flux:callout.heading>Please correct the following errors:</flux:callout.heading>
                <flux:callout.text>
                    <ul class="mt-2 list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </flux:callout.text>
            </flux:callout>
        </div>
    @endif

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-6 py-6">
        <flux:card>
            <form wire:submit="save">
                <!-- Step 1: Template Selection -->
                @if($currentStep === 1)
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <flux:heading size="lg">Select Template</flux:heading>
                            <div class="flex items-center gap-2">
                                <flux:select wire:model.live="templateFilter.category" placeholder="All Categories" size="sm">
                                    <flux:select.option value="">All Categories</flux:select.option>
                                    <flux:select.option value="msp">MSP</flux:select.option>
                                    <flux:select.option value="voip">VoIP</flux:select.option>
                                    <flux:select.option value="var">VAR</flux:select.option>
                                    <flux:select.option value="compliance">Compliance</flux:select.option>
                                </flux:select>
                                
                                <flux:select wire:model.live="templateFilter.billingModel" placeholder="All Models" size="sm">
                                    <flux:select.option value="">All Models</flux:select.option>
                                    <flux:select.option value="fixed">Fixed</flux:select.option>
                                    <flux:select.option value="per_asset">Per Asset</flux:select.option>
                                    <flux:select.option value="per_contact">Per User</flux:select.option>
                                    <flux:select.option value="tiered">Tiered</flux:select.option>
                                    <flux:select.option value="hybrid">Hybrid</flux:select.option>
                                </flux:select>
                            </div>
                        </div>

                        <!-- Custom Contract Option -->
                        <flux:card class="cursor-pointer hover:border-blue-500 transition-colors" 
                                   :class="!$selectedTemplate ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : ''"
                                   wire:click="selectTemplate(null)">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <flux:icon.pencil-square class="w-6 h-6" />
                                    <div>
                                        <flux:heading size="base">Custom Contract</flux:heading>
                                        <flux:text size="sm">Start from scratch with complete customization</flux:text>
                                    </div>
                                </div>
                                <flux:badge color="zinc">Manual</flux:badge>
                            </div>
                        </flux:card>

                        <!-- Template List -->
                        <div class="space-y-2">
                            @foreach($templates as $template)
                                <flux:card class="cursor-pointer hover:border-blue-500 transition-colors"
                                           :class="$selectedTemplate?->id === $template->id ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : ''"
                                           wire:click="selectTemplate({{ $template->id }})"
                                           wire:key="template-{{ $template->id }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-4">
                                            <flux:icon.document-text class="w-6 h-6" />
                                            <div>
                                                <flux:heading size="base">{{ $template->name }}</flux:heading>
                                                <flux:text size="sm">{{ $template->description }}</flux:text>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <flux:badge color="blue" size="sm">{{ ucfirst($template->billing_model) }}</flux:badge>
                                            <flux:badge color="zinc" size="sm">{{ ucfirst($template->category) }}</flux:badge>
                                            @if($template->usage_count > 0)
                                                <flux:text size="sm">{{ $template->usage_count }} uses</flux:text>
                                            @endif>
                                        </div>
                                    </div>
                                </flux:card>
                            @endforeach
                        </div>

                        @if($selectedTemplate)
                            <flux:callout icon="information-circle" color="blue">
                                <flux:callout.heading>Template Details</flux:callout.heading>
                                <flux:callout.text>
                                    <div class="grid grid-cols-3 gap-4 mt-2">
                                        <div>
                                            <strong>Billing Model:</strong> {{ ucfirst($selectedTemplate->billing_model) }}
                                        </div>
                                        <div>
                                            <strong>Variables:</strong> {{ $selectedTemplate->variable_count }} fields
                                        </div>
                                        <div>
                                            <strong>Usage:</strong> {{ $selectedTemplate->usage_count }} times
                                        </div>
                                    </div>
                                </flux:callout.text>
                            </flux:callout>
                        @endif
                    </div>
                @endif

                <!-- Step 2: Basic Information -->
                @if($currentStep === 2)
                    <div class="space-y-6">
                        <flux:heading size="lg">Contract Details</flux:heading>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Left Column -->
                            <div class="space-y-6">
                                <flux:input 
                                    label="Contract Title" 
                                    wire:model="title" 
                                    required 
                                    placeholder="e.g., Comprehensive IT Support Agreement - Acme Corp" />

                                <flux:select 
                                    label="Contract Type" 
                                    wire:model="contract_type" 
                                    required 
                                    placeholder="Select contract type...">
                                    <flux:select.option value="one_time_service">One-Time Service</flux:select.option>
                                    <flux:select.option value="recurring_service">Recurring Service</flux:select.option>
                                    <flux:select.option value="maintenance">Maintenance</flux:select.option>
                                    <flux:select.option value="support">Support</flux:select.option>
                                    <flux:select.option value="managed_services">Managed Services</flux:select.option>
                                </flux:select>

                                <flux:select 
                                    label="Client" 
                                    wire:model="client_id" 
                                    required 
                                    placeholder="Select client...">
                                    @foreach($clients as $client)
                                        <flux:select.option value="{{ $client->id }}">{{ $client->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:textarea 
                                    label="Description" 
                                    wire:model="description" 
                                    rows="4"
                                    placeholder="Brief description of the contract scope..." />
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-6">
                                <flux:input 
                                    type="date" 
                                    label="Start Date" 
                                    wire:model="start_date" 
                                    max="2999-12-31"
                                    required />

                                <div class="grid grid-cols-2 gap-4">
                                    <flux:input 
                                        type="date" 
                                        label="End Date" 
                                        wire:model="end_date"
                                        max="2999-12-31" />

                                    <flux:input 
                                        type="number" 
                                        label="OR Term (Months)" 
                                        wire:model="term_months"
                                        min="1"
                                        max="120"
                                        placeholder="12" />
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <flux:select 
                                        label="Currency" 
                                        wire:model="currency_code">
                                        <flux:select.option value="USD">USD</flux:select.option>
                                        <flux:select.option value="EUR">EUR</flux:select.option>
                                        <flux:select.option value="GBP">GBP</flux:select.option>
                                        <flux:select.option value="CAD">CAD</flux:select.option>
                                        <flux:select.option value="AUD">AUD</flux:select.option>
                                    </flux:select>

                                    <flux:select 
                                        label="Payment Terms" 
                                        wire:model="payment_terms"
                                        placeholder="Select terms...">
                                        <flux:select.option value="net_15">Net 15 days</flux:select.option>
                                        <flux:select.option value="net_30">Net 30 days</flux:select.option>
                                        <flux:select.option value="net_45">Net 45 days</flux:select.option>
                                        <flux:select.option value="net_60">Net 60 days</flux:select.option>
                                        <flux:select.option value="due_on_receipt">Due on receipt</flux:select.option>
                                    </flux:select>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Step 3: Billing Model -->
                @if($currentStep === 3)
                    <div class="space-y-6">
                        <flux:heading size="lg">Select Billing Model</flux:heading>
                        
                        <flux:radio.group 
                            wire:model="billing_model" 
                            variant="cards" 
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            
                            <flux:radio 
                                value="fixed" 
                                label="Fixed Price" 
                                description="Single fixed monthly or project fee"
                                icon="banknotes"
                                wire:click="selectBillingModel('fixed')" />
                            
                            <flux:radio 
                                value="per_asset" 
                                label="Per Asset" 
                                description="Charge per device or asset managed"
                                icon="computer-desktop"
                                wire:click="selectBillingModel('per_asset')" />
                            
                            <flux:radio 
                                value="per_contact" 
                                label="Per Contact" 
                                description="Charge per user or seat"
                                icon="user"
                                wire:click="selectBillingModel('per_contact')" />
                            
                            <flux:radio 
                                value="tiered" 
                                label="Tiered Pricing" 
                                description="Different rates based on volume or service level"
                                icon="chart-bar"
                                wire:click="selectBillingModel('tiered')" />
                            
                            <flux:radio 
                                value="hybrid" 
                                label="Hybrid Model" 
                                description="Combination of multiple billing methods"
                                icon="puzzle-piece"
                                wire:click="selectBillingModel('hybrid')" />
                        </flux:radio.group>
                    </div>
                @endif

                <!-- Step 4: Schedules -->
                @if($currentStep === 4)
                    <div class="space-y-6">
                        <flux:heading size="lg">Schedule Configuration</flux:heading>
                        <flux:callout icon="information-circle" variant="secondary">
                            Configure contract schedules, asset types, and service levels
                        </flux:callout>
                        
                        <!-- TODO: Add schedule configuration component -->
                    </div>
                @endif

                <!-- Step 5: Review -->
                @if($currentStep === 5)
                    <div class="space-y-6">
                        <flux:heading size="lg">Review & Submit</flux:heading>
                        
                        <flux:card>
                            <flux:heading size="base">Contract Summary</flux:heading>
                            <div class="mt-4 space-y-2">
                                <div class="flex justify-between">
                                    <flux:text>Title:</flux:text>
                                    <flux:text class="font-semibold">{{ $title }}</flux:text>
                                </div>
                                <div class="flex justify-between">
                                    <flux:text>Type:</flux:text>
                                    <flux:text class="font-semibold">{{ ucwords(str_replace('_', ' ', $contract_type)) }}</flux:text>
                                </div>
                                <div class="flex justify-between">
                                    <flux:text>Client:</flux:text>
                                    <flux:text class="font-semibold">{{ $clients->firstWhere('id', $client_id)?->name }}</flux:text>
                                </div>
                                <div class="flex justify-between">
                                    <flux:text>Billing Model:</flux:text>
                                    <flux:text class="font-semibold">{{ ucfirst($billing_model) }}</flux:text>
                                </div>
                            </div>
                        </flux:card>
                    </div>
                @endif

                <!-- Navigation Footer -->
                <flux:separator class="my-6" />
                <div class="flex justify-between items-center">
                    <flux:button 
                        type="button" 
                        wire:click="previousStep" 
                        variant="ghost"
                        icon="arrow-left"
                        :disabled="$currentStep === 1">
                        Previous
                    </flux:button>

                    <div class="flex gap-2">
                        @if($currentStep < $totalSteps)
                            <flux:button 
                                type="button" 
                                wire:click="nextStep" 
                                variant="primary"
                                icon-trailing="arrow-right"
                                :disabled="!$this->canProceedToNext()">
                                Next Step
                            </flux:button>
                        @else
                            <flux:button 
                                type="submit" 
                                variant="primary"
                                icon="check">
                                {{ $contract ? 'Update Contract' : 'Create Contract' }}
                            </flux:button>
                        @endif
                    </div>
                </div>
            </form>
        </flux:card>
    </div>
</div>
