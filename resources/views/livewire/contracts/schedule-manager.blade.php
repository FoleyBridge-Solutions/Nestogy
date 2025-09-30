<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg">Contract Schedules</flux:heading>
            <flux:text class="text-gray-600">
                Manage infrastructure, pricing, and additional terms through contract schedules
            </flux:text>
        </div>
        
        <flux:dropdown position="bottom end">
            <flux:button icon="plus" variant="primary">
                Add Schedule
            </flux:button>
            
            <flux:menu>
                <flux:menu.item wire:click="openCreateModal('A')" icon="server">
                    Schedule A - Infrastructure & SLA
                </flux:menu.item>
                <flux:menu.item wire:click="openCreateModal('B')" icon="currency-dollar">
                    Schedule B - Pricing & Fees
                </flux:menu.item>
                <flux:menu.item wire:click="openCreateModal('C')" icon="document-text">
                    Schedule C - Additional Terms
                </flux:menu.item>
                <flux:menu.item wire:click="openCreateModal('D')" icon="shield-check">
                    Schedule D - Compliance
                </flux:menu.item>
                <flux:menu.item wire:click="openCreateModal('E')" icon="cog">
                    Schedule E - Custom
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </div>
    
    {{-- Schedules Grid --}}
    @if(empty($schedules))
        <flux:callout icon="information-circle" variant="info">
            <div class="space-y-2">
                <flux:heading size="base">No Schedules Yet</flux:heading>
                <flux:text>
                    Add schedules to define infrastructure coverage, pricing structures, and compliance requirements.
                </flux:text>
            </div>
        </flux:callout>
    @else
        <div class="grid grid-cols-1 gap-4">
            @foreach($schedules as $schedule)
                @php
                    $typeInfo = match($schedule['schedule_type']) {
                        'A' => ['label' => 'Infrastructure & SLA', 'icon' => 'server', 'color' => 'blue'],
                        'B' => ['label' => 'Pricing & Fees', 'icon' => 'currency-dollar', 'color' => 'green'],
                        'C' => ['label' => 'Additional Terms', 'icon' => 'document-text', 'color' => 'purple'],
                        'D' => ['label' => 'Compliance', 'icon' => 'shield-check', 'color' => 'red'],
                        'E' => ['label' => 'Custom', 'icon' => 'cog', 'color' => 'gray'],
                        default => ['label' => 'Unknown', 'icon' => 'question-mark', 'color' => 'gray'],
                    };
                    
                    $statusColor = match($schedule['status']) {
                        'active' => 'green',
                        'draft' => 'yellow',
                        'suspended' => 'orange',
                        'archived' => 'gray',
                        default => 'zinc',
                    };
                @endphp
                
                <flux:card>
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-4 flex-1">
                            {{-- Schedule Type Badge --}}
                            <div class="flex-shrink-0">
                                <div class="w-16 h-16 rounded-lg bg-{{ $typeInfo['color'] }}-100 dark:bg-{{ $typeInfo['color'] }}-900/20 
                                            flex items-center justify-center border border-{{ $typeInfo['color'] }}-200 dark:border-{{ $typeInfo['color'] }}-700">
                                    <flux:heading size="xl" class="text-{{ $typeInfo['color'] }}-600 dark:text-{{ $typeInfo['color'] }}-400">
                                        {{ $schedule['schedule_type'] }}
                                    </flux:heading>
                                </div>
                            </div>
                            
                            {{-- Schedule Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <flux:heading size="base">{{ $schedule['title'] }}</flux:heading>
                                    
                                    <flux:badge size="sm" :color="$statusColor">
                                        {{ ucfirst($schedule['status']) }}
                                    </flux:badge>
                                    
                                    @if($schedule['approval_status'] === 'approved')
                                        <flux:badge size="sm" color="green" icon="check-circle">
                                            Approved
                                        </flux:badge>
                                    @elseif($schedule['approval_status'] === 'pending')
                                        <flux:badge size="sm" color="yellow" icon="clock">
                                            Pending Approval
                                        </flux:badge>
                                    @endif
                                    
                                    @if($schedule['auto_assign_assets'])
                                        <flux:badge size="sm" color="blue" icon="bolt">
                                            Auto-Assign
                                        </flux:badge>
                                    @endif
                                </div>
                                
                                <flux:text size="sm" class="text-gray-600 mb-2">
                                    {{ $typeInfo['label'] }}
                                </flux:text>
                                
                                @if($schedule['description'])
                                    <flux:text size="sm" class="text-gray-500">
                                        {{ Str::limit($schedule['description'], 120) }}
                                    </flux:text>
                                @endif
                                
                                {{-- Meta Information --}}
                                <div class="flex items-center gap-4 mt-3 text-xs text-gray-500">
                                    @if($schedule['effective_date'])
                                        <div class="flex items-center gap-1">
                                            <flux:icon.calendar variant="micro" />
                                            <span>Effective: {{ \Carbon\Carbon::parse($schedule['effective_date'])->format('M d, Y') }}</span>
                                        </div>
                                    @endif
                                    
                                    @if($schedule['expiration_date'])
                                        <div class="flex items-center gap-1">
                                            <flux:icon.clock variant="micro" />
                                            <span>Expires: {{ \Carbon\Carbon::parse($schedule['expiration_date'])->format('M d, Y') }}</span>
                                        </div>
                                    @endif
                                    
                                    @if(!empty($schedule['supported_asset_types']))
                                        <div class="flex items-center gap-1">
                                            <flux:icon.server variant="micro" />
                                            <span>{{ count($schedule['supported_asset_types']) }} asset types</span>
                                        </div>
                                    @endif
                                    
                                    <div class="flex items-center gap-1">
                                        <flux:icon.document-text variant="micro" />
                                        <span>v{{ $schedule['version'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Actions --}}
                        <div class="flex items-center gap-2 ml-4">
                            @if($schedule['status'] !== 'active')
                                <flux:button 
                                    wire:click="activateSchedule({{ $schedule['id'] }})"
                                    size="sm"
                                    variant="ghost"
                                    icon="play"
                                    tooltip="Activate"
                                />
                            @endif
                            
                            @if($schedule['approval_status'] === 'pending')
                                <flux:button 
                                    wire:click="approveSchedule({{ $schedule['id'] }})"
                                    size="sm"
                                    variant="ghost"
                                    icon="check-circle"
                                    tooltip="Approve"
                                />
                            @endif
                            
                            <flux:button 
                                wire:click="openEditModal({{ $schedule['id'] }})"
                                size="sm"
                                variant="ghost"
                                icon="pencil"
                                tooltip="Edit"
                            />
                            
                            <flux:button 
                                wire:click="deleteSchedule({{ $schedule['id'] }})"
                                wire:confirm="Are you sure you want to delete this schedule?"
                                size="sm"
                                variant="ghost"
                                icon="trash"
                                tooltip="Delete"
                            />
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>
    @endif
    
    {{-- Create Schedule Modal --}}
    <flux:modal wire:model="showCreateModal" class="md:w-[600px]">
        <form wire:submit.prevent="createSchedule" class="space-y-6">
            <div>
                <flux:heading size="lg">Create Schedule {{ $schedule_type }}</flux:heading>
                <flux:text class="mt-2">Add a new schedule to define contract terms.</flux:text>
            </div>
            
            <div class="space-y-4">
                <flux:field>
                    <flux:label>Title *</flux:label>
                    <flux:input wire:model="title" placeholder="e.g., Infrastructure Coverage - Standard Tier" />
                    <flux:error name="title" />
                </flux:field>
                
                <flux:field>
                    <flux:label>Description</flux:label>
                    <flux:textarea wire:model="description" rows="3" placeholder="Brief description of this schedule..." />
                    <flux:error name="description" />
                </flux:field>
                
                <flux:field>
                    <flux:label>Content</flux:label>
                    <flux:textarea wire:model="content" rows="6" placeholder="Schedule content and terms..." />
                    <flux:error name="content" />
                </flux:field>
                
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Status *</flux:label>
                        <flux:select wire:model="status">
                            <option value="draft">Draft</option>
                            <option value="pending_approval">Pending Approval</option>
                            <option value="active">Active</option>
                        </flux:select>
                        <flux:error name="status" />
                    </flux:field>
                    
                    @if($schedule_type === 'A')
                        <flux:field>
                            <flux:checkbox wire:model="auto_assign_assets">
                                Auto-assign assets
                            </flux:checkbox>
                            <flux:description>
                                Automatically assign matching assets to this schedule
                            </flux:description>
                        </flux:field>
                    @endif
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Effective Date</flux:label>
                        <flux:input type="date" wire:model="effective_date" />
                        <flux:error name="effective_date" />
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>Expiration Date</flux:label>
                        <flux:input type="date" wire:model="expiration_date" />
                        <flux:error name="expiration_date" />
                    </flux:field>
                </div>
            </div>
            
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">
                        Cancel
                    </flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    Create Schedule
                </flux:button>
            </div>
        </form>
    </flux:modal>
    
    {{-- Edit Schedule Modal --}}
    <flux:modal wire:model="showEditModal" class="md:w-[600px]">
        <form wire:submit.prevent="updateSchedule" class="space-y-6">
            <div>
                <flux:heading size="lg">Edit Schedule {{ $schedule_type }}</flux:heading>
                <flux:text class="mt-2">Update schedule details and terms.</flux:text>
            </div>
            
            <div class="space-y-4">
                <flux:field>
                    <flux:label>Title *</flux:label>
                    <flux:input wire:model="title" />
                    <flux:error name="title" />
                </flux:field>
                
                <flux:field>
                    <flux:label>Description</flux:label>
                    <flux:textarea wire:model="description" rows="3" />
                    <flux:error name="description" />
                </flux:field>
                
                <flux:field>
                    <flux:label>Content</flux:label>
                    <flux:textarea wire:model="content" rows="6" />
                    <flux:error name="content" />
                </flux:field>
                
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Status *</flux:label>
                        <flux:select wire:model="status">
                            <option value="draft">Draft</option>
                            <option value="pending_approval">Pending Approval</option>
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                        </flux:select>
                        <flux:error name="status" />
                    </flux:field>
                    
                    @if($schedule_type === 'A')
                        <flux:field>
                            <flux:checkbox wire:model="auto_assign_assets">
                                Auto-assign assets
                            </flux:checkbox>
                        </flux:field>
                    @endif
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Effective Date</flux:label>
                        <flux:input type="date" wire:model="effective_date" />
                        <flux:error name="effective_date" />
                    </flux:field>
                    
                    <flux:field>
                        <flux:label>Expiration Date</flux:label>
                        <flux:input type="date" wire:model="expiration_date" />
                        <flux:error name="expiration_date" />
                    </flux:field>
                </div>
            </div>
            
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">
                        Cancel
                    </flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    Update Schedule
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
