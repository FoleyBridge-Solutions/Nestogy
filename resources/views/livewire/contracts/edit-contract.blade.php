<div>
    <!-- Header with Status Warning -->
    <div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-6 py-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <flux:heading size="xl">Edit Contract: {{ $contract->contract_number }}</flux:heading>
                    <flux:text class="mt-2">Modify contract details and billing configuration</flux:text>
                </div>
                <div class="flex items-center gap-3">
                    <flux:button href="{{ route('financial.contracts.show', $contract) }}" variant="ghost">
                        Cancel
                    </flux:button>
                </div>
            </div>
            
            <!-- Status & Edit Restrictions Alert -->
            @if(!$canEdit)
                <flux:callout variant="danger" icon="exclamation-triangle">
                    <flux:callout.heading>Contract Status Restriction</flux:callout.heading>
                    <flux:callout.text>
                        This contract is currently <strong>{{ ucwords(str_replace('_', ' ', $contract->status)) }}</strong> 
                        and cannot be edited. Only draft and pending review contracts can be modified.
                    </flux:callout.text>
                </flux:callout>
            @elseif($contract->billingCalculations()->count() > 0)
                <flux:callout variant="warning" icon="exclamation-triangle">
                    <flux:callout.heading>Billing History Warning</flux:callout.heading>
                    <flux:callout.text>
                        This contract has existing billing records. Changes to pricing structures may affect future billing calculations.
                    </flux:callout.text>
                </flux:callout>
            @endif
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
        <form wire:submit.prevent="save">
            <flux:card>
                <flux:tab.group>
                    <flux:tabs wire:model="activeTab">
                        <flux:tab name="basic" icon="information-circle">
                            Basic Information
                        </flux:tab>
                        <flux:tab name="billing" icon="currency-dollar">
                            Billing Model
                        </flux:tab>
                        <flux:tab name="schedules" icon="document-text">
                            Schedules
                            @if($contract->schedules()->count() > 0)
                                <flux:badge size="sm" color="blue" inset="top bottom" class="ml-2">
                                    {{ $contract->schedules()->count() }}
                                </flux:badge>
                            @endif
                        </flux:tab>
                        <flux:tab name="assignments" icon="users">
                            Assignments
                            @if(($contract->assetAssignments()->count() + $contract->contactAssignments()->count()) > 0)
                                <flux:badge size="sm" color="green" inset="top bottom" class="ml-2">
                                    {{ $contract->assetAssignments()->count() + $contract->contactAssignments()->count() }}
                                </flux:badge>
                            @endif
                        </flux:tab>
                        <flux:tab name="content" icon="document-text">
                            Contract Language
                        </flux:tab>
                    </flux:tabs>

                    <!-- Tab Content -->
                    <!-- Basic Information Tab -->
                    <flux:tab.panel name="basic">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Left Column -->
                            <div class="space-y-6">
                                <!-- Contract Title -->
                                <flux:input 
                                    label="Contract Title" 
                                    wire:model.defer="title" 
                                    :disabled="!$canEdit"
                                    required />

                                <!-- Contract Type -->
                                <flux:select 
                                    label="Contract Type" 
                                    wire:model.defer="contract_type" 
                                    :disabled="!$canEdit"
                                    placeholder="Select contract type...">
                                    <flux:select.option value="one_time_service">One-Time Service</flux:select.option>
                                    <flux:select.option value="recurring_service">Recurring Service</flux:select.option>
                                    <flux:select.option value="maintenance">Maintenance</flux:select.option>
                                    <flux:select.option value="support">Support</flux:select.option>
                                    <flux:select.option value="managed_services">Managed Services</flux:select.option>
                                </flux:select>

                                <!-- Client Selection -->
                                <flux:select 
                                    label="Client" 
                                    wire:model.defer="client_id" 
                                    :disabled="!$canEdit || $contract->status !== 'draft'"
                                    placeholder="Select client...">
                                    @foreach($this->clients as $client)
                                        <flux:select.option value="{{ $client->id }}">{{ $client->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>

                                <!-- Description -->
                                <flux:textarea 
                                    label="Description" 
                                    wire:model.defer="description" 
                                    rows="4"
                                    :disabled="!$canEdit"
                                    placeholder="Brief description of the contract scope..." />
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-6">
                                <!-- Start Date -->
                                <flux:input 
                                    type="date" 
                                    label="Start Date" 
                                    wire:model.defer="start_date" 
                                    :disabled="!$canEdit"
                                    max="2999-12-31"
                                    required />

                                <!-- End Date -->
                                <flux:input 
                                    type="date" 
                                    label="End Date" 
                                    wire:model.defer="end_date" 
                                    :disabled="!$canEdit"
                                    max="2999-12-31" />

                                <!-- Contract Value -->
                                <flux:field>
                                    <flux:label>
                                        Contract Value (Annual)
                                        @if($billing_model !== 'fixed')
                                            <flux:text size="xs" class="font-normal">(Auto-calculated)</flux:text>
                                        @endif
                                    </flux:label>
                                    <flux:input.group>
                                        <flux:input.group.prefix>$</flux:input.group.prefix>
                                        <flux:input 
                                            type="number" 
                                            wire:model.defer="contract_value" 
                                            step="0.01"
                                            :disabled="!$canEdit || $billing_model !== 'fixed'"
                                            :readonly="$billing_model !== 'fixed'" />
                                    </flux:input.group>
                                    @if($billing_model !== 'fixed')
                                        <flux:description>
                                            Based on {{ $billing_model === 'per_asset' ? 'asset' : ($billing_model === 'per_contact' ? 'contact' : 'tiered/hybrid') }} assignments
                                        </flux:description>
                                    @endif
                                    <flux:error name="contract_value" />
                                </flux:field>

                                <!-- Status -->
                                <flux:select 
                                    label="Status" 
                                    wire:model.defer="status" 
                                    :disabled="!$canEdit">
                                    <flux:select.option value="draft">Draft</flux:select.option>
                                    <flux:select.option value="pending_review">Pending Review</flux:select.option>
                                    <flux:select.option value="active">Active</flux:select.option>
                                    <flux:select.option value="expired">Expired</flux:select.option>
                                    <flux:select.option value="terminated">Terminated</flux:select.option>
                                </flux:select>

                                <!-- Financial Configuration -->
                                <div class="grid grid-cols-2 gap-4">
                                    <flux:select 
                                        label="Currency" 
                                        wire:model.defer="currency_code" 
                                        :disabled="!$canEdit">
                                        <flux:select.option value="USD">USD</flux:select.option>
                                        <flux:select.option value="EUR">EUR</flux:select.option>
                                        <flux:select.option value="GBP">GBP</flux:select.option>
                                        <flux:select.option value="CAD">CAD</flux:select.option>
                                        <flux:select.option value="AUD">AUD</flux:select.option>
                                    </flux:select>

                                    <flux:input 
                                        label="Payment Terms" 
                                        wire:model.defer="payment_terms" 
                                        :disabled="!$canEdit"
                                        placeholder="Net 30" />
                                </div>
                            </div>
                        </div>
                    </flux:tab.panel>

                    <!-- Billing Model Tab -->
                    <flux:tab.panel name="billing">
                        <div class="space-y-6">
                            <flux:radio.group 
                                wire:model.live="billing_model" 
                                label="Select Billing Model" 
                                variant="cards" 
                                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                
                                <flux:radio 
                                    value="fixed" 
                                    label="Fixed Price" 
                                    description="Single fixed monthly or project fee"
                                    icon="banknotes"
                                    :disabled="!$canEdit"
                                    wire:click="selectBillingModel('fixed')" />
                                
                                <flux:radio 
                                    value="per_asset" 
                                    label="Per Asset" 
                                    description="Charge per device or asset managed"
                                    icon="computer-desktop"
                                    :disabled="!$canEdit"
                                    wire:click="selectBillingModel('per_asset')" />
                                
                                <flux:radio 
                                    value="per_contact" 
                                    label="Per Contact" 
                                    description="Charge per user or seat"
                                    icon="user"
                                    :disabled="!$canEdit"
                                    wire:click="selectBillingModel('per_contact')" />
                                
                                <flux:radio 
                                    value="tiered" 
                                    label="Tiered Pricing" 
                                    description="Different rates based on volume or service level"
                                    icon="chart-bar"
                                    :disabled="!$canEdit"
                                    wire:click="selectBillingModel('tiered')" />
                                
                                <flux:radio 
                                    value="hybrid" 
                                    label="Hybrid Model" 
                                    description="Combination of multiple billing methods"
                                    icon="puzzle-piece"
                                    :disabled="!$canEdit"
                                    wire:click="selectBillingModel('hybrid')" />
                            </flux:radio.group>

                        <!-- Current Billing Summary -->
                        @if($billing_model && $billing_model !== 'fixed')
                            <flux:card>
                                <flux:heading size="lg">Billing Calculation Summary</flux:heading>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                                    <div>
                                        <flux:text size="sm">Assigned Assets</flux:text>
                                        <flux:heading size="xl" class="text-blue-600 dark:text-blue-400 mt-1">{{ $contract->assetAssignments()->count() }}</flux:heading>
                                        @if($billing_model === 'per_asset')
                                            <flux:text size="xs" class="mt-1">
                                                @php
                                                    $monthlyAssetTotal = 0;
                                                    foreach ($contract->assetAssignments as $assignment) {
                                                        $monthlyAssetTotal += $assignment->calculateMonthlyCharges();
                                                    }
                                                @endphp
                                                ${{ number_format($monthlyAssetTotal, 2) }}/month
                                            </flux:text>
                                        @endif
                                    </div>
                                    <div>
                                        <flux:text size="sm">Assigned Contacts</flux:text>
                                        <flux:heading size="xl" class="text-green-600 dark:text-green-400 mt-1">{{ $contract->contactAssignments()->count() }}</flux:heading>
                                        @if($billing_model === 'per_contact')
                                            <flux:text size="xs" class="mt-1">
                                                @php
                                                    $monthlyContactTotal = 0;
                                                    foreach ($contract->contactAssignments as $assignment) {
                                                        $monthlyContactTotal += $assignment->monthly_rate ?? 0;
                                                    }
                                                @endphp
                                                ${{ number_format($monthlyContactTotal, 2) }}/month
                                            </flux:text>
                                        @endif
                                    </div>
                                    <div>
                                        <flux:text size="sm">Total Value</flux:text>
                                        <flux:text size="xs" class="mb-1">Monthly: ${{ number_format($contract->getMonthlyRecurringRevenue(), 2) }}</flux:text>
                                        <flux:heading size="xl" class="text-purple-600 dark:text-purple-400">${{ number_format($contract->getAnnualValue(), 2) }}</flux:heading>
                                        <flux:text size="xs" class="mt-1">Annual</flux:text>
                                    </div>
                                </div>
                                
                                @if($billing_model === 'hybrid' && $contract->pricing_structure)
                                    <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                                        <flux:heading size="sm">Breakdown:</flux:heading>
                                        <div class="space-y-1 mt-2">
                                            @if(isset($contract->pricing_structure['recurring_monthly']))
                                                <div class="flex justify-between">
                                                    <flux:text size="xs">Base Monthly Fee:</flux:text>
                                                    <flux:text size="xs">${{ number_format($contract->pricing_structure['recurring_monthly'], 2) }}</flux:text>
                                                </div>
                                            @endif
                                            @if($contract->assetAssignments()->count() > 0)
                                                <div class="flex justify-between">
                                                    <flux:text size="xs">Per-Asset Charges:</flux:text>
                                                    <flux:text size="xs">${{ number_format($monthlyAssetTotal ?? 0, 2) }}</flux:text>
                                                </div>
                                            @endif
                                            @if($contract->contactAssignments()->count() > 0)
                                                <div class="flex justify-between">
                                                    <flux:text size="xs">Per-Contact Charges:</flux:text>
                                                    <flux:text size="xs">${{ number_format($monthlyContactTotal ?? 0, 2) }}</flux:text>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                
                                <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                                    <flux:button 
                                        type="button" 
                                        wire:click="calculateContractValue" 
                                        variant="ghost" 
                                        size="sm"
                                        icon="arrow-path">
                                        Recalculate
                                    </flux:button>
                                </div>
                            </flux:card>
                        @endif
                        
                        @if($billing_model === 'fixed')
                            <flux:callout icon="information-circle" color="blue">
                                <flux:callout.heading>Fixed Price Billing</flux:callout.heading>
                                <flux:callout.text>
                                    Enter the total contract value manually in the Basic Information tab. This value will not be automatically calculated.
                                </flux:callout.text>
                            </flux:callout>
                        @endif
                        </div>
                    </flux:tab.panel>

                    <!-- Schedules Tab -->
                    <flux:tab.panel name="schedules">
                        <livewire:contracts.schedule-manager :contract="$contract" :key="'schedules-'.$contract->id" />
                    </flux:tab.panel>

                    <!-- Assignments Tab -->
                    <flux:tab.panel name="assignments">
                        <div class="space-y-6">
                            {{-- Billing Model Notice --}}
                            @if(in_array($billing_model, ['per_asset', 'per_contact', 'hybrid']))
                                <flux:callout icon="information-circle" color="blue">
                                    <flux:callout.heading>Assignment-Based Billing</flux:callout.heading>
                                    <flux:callout.text>
                                        This contract uses {{ $billing_model }} billing. Assignments directly affect the contract value calculation.
                                    </flux:callout.text>
                                </flux:callout>
                            @else
                                <flux:callout icon="information-circle" color="yellow" variant="warning">
                                    <flux:callout.heading>Fixed Price Billing</flux:callout.heading>
                                    <flux:callout.text>
                                        This contract uses <strong>{{ $billing_model }}</strong> billing. You can still assign assets and contacts for tracking purposes, but they won't affect the contract value.
                                        To enable automatic billing calculations, change the billing model in the "Billing Model" tab.
                                    </flux:callout.text>
                                </flux:callout>
                            @endif

                            <div class="grid grid-cols-1 gap-6">
                                {{-- Asset Assignments Component - Always Show --}}
                                <livewire:contracts.asset-assignment-manager :contract="$contract" :key="'asset-assignments-'.$contract->id" />
                                
                                {{-- Contact Assignments Placeholder --}}
                                <flux:card>
                                    <div class="flex items-center justify-between mb-4">
                                        <div>
                                            <flux:heading size="lg">Contact Assignments</flux:heading>
                                            <flux:text class="mt-1">
                                                Manage which contacts are covered under this contract
                                            </flux:text>
                                        </div>
                                    </div>
                                    
                                    <flux:callout icon="information-circle" variant="secondary">
                                        Contact assignment management coming soon. For now, contacts can be assigned from the contract detail page after saving.
                                    </flux:callout>
                                </flux:card>
                            </div>
                        </div>
                    </flux:tab.panel>

                    <!-- Contract Language Tab -->
                    <flux:tab.panel name="content">
                        <div class="h-[800px]">
                            <livewire:contracts.contract-language-editor :contract="$contract" :key="$contract->id" />
                        </div>
                    </flux:tab.panel>
                </flux:tab.group>

                <!-- Form Actions -->
                <flux:separator />
                <div class="flex justify-between items-center p-6">
                    <flux:button 
                        href="{{ route('financial.contracts.show', $contract) }}" 
                        variant="ghost">
                        Cancel
                    </flux:button>
                    <flux:button 
                        type="submit" 
                        variant="primary"
                        :disabled="!$canSave">
                        Save Changes
                    </flux:button>
                </div>
            </flux:card>
        </form>
    </div>
</div>
