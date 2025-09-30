<div class="space-y-4">
    {{-- Header with Add Button --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="base">Assigned Assets</flux:heading>
            <flux:text size="sm" class="text-gray-600">
                Manage which assets are covered under this contract
            </flux:text>
        </div>
        
        <flux:button wire:click="openAddModal" icon="plus" size="sm">
            Assign Assets
        </flux:button>
    </div>
    
    {{-- Assignments List --}}
    @if(empty($assignments))
        <flux:callout icon="information-circle" variant="info">
            No assets assigned yet. Click "Assign Assets" to get started.
        </flux:callout>
    @else
        <div class="space-y-2">
            @foreach($assignments as $assignment)
                @php
                    $asset = \App\Models\Asset::find($assignment['asset_id']);
                @endphp
                
                <flux:card class="hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-3 flex-1">
                            {{-- Asset Icon --}}
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
                                <flux:icon.server class="size-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            
                            {{-- Asset Details --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <flux:heading size="sm">{{ $asset->name ?? 'Asset #' . $assignment['asset_id'] }}</flux:heading>
                                    <flux:badge size="xs" :color="$assignment['status'] === 'active' ? 'green' : 'gray'">
                                        {{ ucfirst($assignment['status']) }}
                                    </flux:badge>
                                </div>
                                
                                @if($asset)
                                    <div class="flex items-center gap-3 text-xs text-gray-500 mb-2">
                                        @if($asset->type)
                                            <span>{{ ucfirst($asset->type) }}</span>
                                        @endif
                                        @if($asset->serial)
                                            <span>S/N: {{ $asset->serial }}</span>
                                        @endif
                                        @if($asset->ip)
                                            <span>{{ $asset->ip }}</span>
                                        @endif
                                    </div>
                                @endif
                                
                                {{-- Services --}}
                                @if(!empty($assignment['assigned_services']))
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($assignment['assigned_services'] as $service)
                                            <span class="px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded">
                                                {{ $availableServices[$service] ?? ucfirst($service) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                
                                {{-- Billing Info --}}
                                <div class="mt-2 flex items-center gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500">Rate:</span>
                                        <span class="font-semibold text-gray-900 dark:text-white">
                                            ${{ number_format($assignment['base_monthly_rate'], 2) }}
                                        </span>
                                        <span class="text-gray-500">/{{ $assignment['billing_frequency'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Start:</span>
                                        <span class="text-gray-900 dark:text-white">
                                            {{ \Carbon\Carbon::parse($assignment['start_date'])->format('M d, Y') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Actions --}}
                        <div class="flex items-center gap-2 ml-4">
                            <flux:button 
                                wire:click="openEditModal({{ $assignment['id'] }})"
                                size="sm"
                                variant="ghost"
                                icon="pencil"
                                tooltip="Edit"
                            />
                            
                            <flux:button 
                                wire:click="removeAssignment({{ $assignment['id'] }})"
                                wire:confirm="Remove this asset from the contract?"
                                size="sm"
                                variant="ghost"
                                icon="trash"
                                tooltip="Remove"
                            />
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>
        
        {{-- Summary --}}
        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center">
                <flux:text size="sm" class="text-gray-600">
                    Total: {{ count($assignments) }} asset(s)
                </flux:text>
                
                @php
                    $totalMonthly = 0;
                    foreach ($assignments as $assignment) {
                        $totalMonthly += $assignment['base_monthly_rate'];
                    }
                @endphp
                
                <flux:text class="font-bold text-blue-600 dark:text-blue-400">
                    ${{ number_format($totalMonthly, 2) }}/month
                </flux:text>
            </div>
        </div>
    @endif
    
    {{-- Add Assets Modal --}}
    <flux:modal wire:model="showAddModal" class="md:w-[700px]">
        <form wire:submit.prevent="assignAssets" class="space-y-6">
            <div>
                <flux:heading size="lg">Assign Assets to Contract</flux:heading>
                <flux:text class="mt-2">Select assets and configure their services and billing.</flux:text>
            </div>
            
            {{-- Asset Search --}}
            <flux:field>
                <flux:label>Search Assets</flux:label>
                <flux:input 
                    wire:model.live.debounce.300ms="searchAssets" 
                    placeholder="Search by name, serial, or IP..."
                    icon="magnifying-glass"
                />
            </flux:field>
            
            {{-- Available Assets List --}}
            <div class="max-h-[300px] overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                @if(empty($availableAssets))
                    <div class="p-4 text-center text-gray-500">
                        @if($searchAssets)
                            No assets found matching "{{ $searchAssets }}"
                        @else
                            No available assets. Start typing to search.
                        @endif
                    </div>
                @else
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($availableAssets as $asset)
                            <label class="flex items-center gap-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                                <flux:checkbox 
                                    wire:model.live="selectedAssetIds" 
                                    value="{{ $asset['id'] }}"
                                />
                                
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ $asset['name'] }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ ucfirst($asset['type'] ?? 'Unknown') }}
                                        @if($asset['serial'])
                                            • S/N: {{ $asset['serial'] }}
                                        @endif
                                        @if($asset['ip'])
                                            • {{ $asset['ip'] }}
                                        @endif
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>
            
            @if(count($selectedAssetIds) > 0)
                <flux:callout icon="information-circle" variant="info">
                    {{ count($selectedAssetIds) }} asset(s) selected. Configure services and billing below.
                </flux:callout>
                
                {{-- Services Selection --}}
                <flux:field>
                    <flux:label>Assigned Services *</flux:label>
                    <flux:description>Select the services provided for these assets</flux:description>
                    
                    <div class="grid grid-cols-2 gap-2 mt-2">
                        @foreach($availableServices as $key => $label)
                            <label class="flex items-center gap-2 p-2 border border-gray-200 dark:border-gray-700 rounded hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                                <flux:checkbox 
                                    wire:click="toggleService('{{ $key }}')"
                                    :checked="in_array('{{ $key }}', $assigned_services)"
                                />
                                <span class="text-sm">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <flux:error name="assigned_services" />
                </flux:field>
                
                {{-- Billing Configuration --}}
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Monthly Rate *</flux:label>
                        <flux:input 
                            type="number" 
                            step="0.01"
                            wire:model="base_monthly_rate" 
                            placeholder="0.00"
                        />
                        <flux:error name="base_monthly_rate" />
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>Billing Frequency *</flux:label>
                        <flux:select wire:model="billing_frequency">
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="annually">Annually</option>
                        </flux:select>
                        <flux:error name="billing_frequency" />
                    </flux:field>
                </div>
                
                <flux:field>
                    <flux:label>Start Date *</flux:label>
                    <flux:input type="date" wire:model="start_date" />
                    <flux:error name="start_date" />
                </flux:field>
            @endif
            
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">
                        Cancel
                    </flux:button>
                </flux:modal.close>
                <flux:button 
                    type="submit" 
                    variant="primary"
                    :disabled="count($selectedAssetIds) === 0 || empty($assigned_services)"
                >
                    Assign {{ count($selectedAssetIds) }} Asset(s)
                </flux:button>
            </div>
        </form>
    </flux:modal>
    
    {{-- Edit Assignment Modal --}}
    <flux:modal wire:model="showEditModal" class="md:w-[600px]">
        <form wire:submit.prevent="updateAssignment" class="space-y-6">
            <div>
                <flux:heading size="lg">Edit Asset Assignment</flux:heading>
                <flux:text class="mt-2">Update services and billing configuration.</flux:text>
            </div>
            
            {{-- Services Selection --}}
            <flux:field>
                <flux:label>Assigned Services *</flux:label>
                
                <div class="grid grid-cols-2 gap-2 mt-2">
                    @foreach($availableServices as $key => $label)
                        <label class="flex items-center gap-2 p-2 border border-gray-200 dark:border-gray-700 rounded hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                            <flux:checkbox 
                                wire:click="toggleService('{{ $key }}')"
                                :checked="in_array('{{ $key }}', $assigned_services)"
                            />
                            <span class="text-sm">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <flux:error name="assigned_services" />
            </flux:field>
            
            {{-- Billing Configuration --}}
            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Monthly Rate *</flux:label>
                    <flux:input 
                        type="number" 
                        step="0.01"
                        wire:model="base_monthly_rate" 
                    />
                    <flux:error name="base_monthly_rate" />
                </flux:field>
                
                <flux:field>
                    <flux:label>Billing Frequency *</flux:label>
                    <flux:select wire:model="billing_frequency">
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="annually">Annually</option>
                    </flux:select>
                    <flux:error name="billing_frequency" />
                </flux:field>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Start Date *</flux:label>
                    <flux:input type="date" wire:model="start_date" />
                    <flux:error name="start_date" />
                </flux:field>
                
                <flux:field>
                    <flux:label>Status *</flux:label>
                    <flux:select wire:model="status">
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="terminated">Terminated</option>
                    </flux:select>
                    <flux:error name="status" />
                </flux:field>
            </div>
            
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">
                        Cancel
                    </flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    Update Assignment
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
