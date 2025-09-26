<div>
    <flux:card class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="lg">Client Access Restrictions</flux:heading>
                <flux:text>Manage technician access to {{ $client->name }}</flux:text>
            </div>
            @if(!$showAddForm)
                <flux:button wire:click="toggleAddForm" icon="plus" variant="primary">
                    Add Restriction
                </flux:button>
            @endif
        </div>
        
        <!-- Info Banner -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">How Client Restrictions Work</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <ul class="list-disc list-inside space-y-1">
                            <li><strong>No restrictions:</strong> Technicians can access ALL clients in the company</li>
                            <li><strong>With restrictions:</strong> Technicians can ONLY access clients they're assigned to</li>
                            <li>Admins always have access to all clients regardless of restrictions</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        @if($showAddForm)
            <flux:separator />
            
            <div class="space-y-4">
                <flux:heading size="md">Add Access Restriction</flux:heading>
                
                <flux:select wire:model="selectedTechnicianId" label="Select Technician" placeholder="Choose a technician to restrict...">
                    @foreach($availableTechnicians as $tech)
                        <option value="{{ $tech['id'] }}">
                            {{ $tech['name'] }} ({{ $tech['email'] }})
                        </option>
                    @endforeach
                </flux:select>
                
                <flux:select wire:model="accessLevel" label="Access Level">
                    <option value="view">View Only</option>
                    <option value="manage">Manage</option>
                    <option value="admin">Admin</option>
                </flux:select>
                
                <flux:checkbox wire:model="isPrimary" label="Set as primary technician" />
                
                <flux:input 
                    wire:model="expiresAt" 
                    label="Expires At (Optional)" 
                    type="date" 
                    placeholder="Leave empty for permanent assignment"
                />
                
                <flux:textarea 
                    wire:model="notes" 
                    label="Notes (Optional)" 
                    placeholder="Any special notes about this assignment..."
                    rows="3"
                />
                
                <div class="flex gap-2">
                    <flux:button wire:click="assignTechnician" variant="primary">
                        Add Restriction
                    </flux:button>
                    <flux:button wire:click="toggleAddForm" variant="ghost">
                        Cancel
                    </flux:button>
                </div>
            </div>
        @endif

        @if(count($technicians) > 0)
            <flux:separator />
            
            <div class="space-y-4">
                <flux:heading size="md">Current Access Restrictions</flux:heading>
                
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Technician</flux:table.column>
                        <flux:table.column>Access Level</flux:table.column>
                        <flux:table.column>Primary</flux:table.column>
                        <flux:table.column>Assigned</flux:table.column>
                        <flux:table.column>Expires</flux:table.column>
                        <flux:table.column>Actions</flux:table.column>
                    </flux:table.columns>
                    
                    <flux:table.rows>
                        @foreach($technicians as $tech)
                            <div class="flex gap-4">
                                <flux:table.cell>
                                    <div>
                                        <div class="font-medium">{{ $tech['name'] }}</div>
                                        <div class="text-sm text-zinc-600">{{ $tech['email'] }}</div>
                                    </div>
                                </flux:table.cell>
                                
                                <flux:table.cell>
                                    <flux:select 
                                        wire:change="updateAccessLevel({{ $tech['id'] }}, $event.target.value)"
                                        value="{{ $tech['access_level'] }}"
                                        size="sm"
                                    >
                                        <option value="view">View</option>
                                        <option value="manage">Manage</option>
                                        <option value="admin">Admin</option>
                                    </flux:select>
                                </flux:table.cell>
                                
                                <flux:table.cell>
                                    @if($tech['is_primary'])
                                        <flux:badge variant="success" size="sm">Primary</flux:badge>
                                    @else
                                        <flux:button 
                                            wire:click="setPrimary({{ $tech['id'] }})"
                                            variant="ghost"
                                            size="xs"
                                        >
                                            Set Primary
                                        </flux:button>
                                    @endif
                                </flux:table.cell>
                                
                                <flux:table.cell>
                                    {{ \Carbon\Carbon::parse($tech['assigned_at'])->format('M d, Y') }}
                                </flux:table.cell>
                                
                                <flux:table.cell>
                                    @if($tech['expires_at'])
                                        <flux:badge variant="warning" size="sm">
                                            {{ \Carbon\Carbon::parse($tech['expires_at'])->format('M d, Y') }}
                                        </flux:badge>
                                    @else
                                        <span class="text-sm text-zinc-600">Permanent</span>
                                    @endif
                                </flux:table.cell>
                                
                                <flux:table.cell>
                                    <flux:button 
                                        wire:click="removeTechnician({{ $tech['id'] }})"
                                        wire:confirm="Are you sure you want to remove this technician?"
                                        variant="ghost"
                                        size="sm"
                                        icon="trash"
                                    />
                                </flux:table.cell>
                            </div>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @else
            <flux:separator />
            
            <div class="text-center py-8">
                <flux:icon name="users" class="mx-auto text-zinc-400 mb-4" size="lg" />
                <flux:text>No technicians assigned to this client yet.</flux:text>
            </div>
        @endif
    </flux:card>
    
    @script
    <script>
        $wire.on('technician-assigned', (event) => {
            Flux.toast(event.detail.message, 'success');
        });
        
        $wire.on('assignment-failed', (event) => {
            Flux.toast(event.detail.message, 'error');
        });
        
        $wire.on('access-updated', (event) => {
            Flux.toast(event.detail.message, 'success');
        });
        
        $wire.on('primary-updated', (event) => {
            Flux.toast(event.detail.message, 'success');
        });
        
        $wire.on('technician-removed', (event) => {
            Flux.toast(event.detail.message, 'success');
        });
        
        $wire.on('update-failed', (event) => {
            Flux.toast(event.detail.message, 'error');
        });
        
        $wire.on('removal-failed', (event) => {
            Flux.toast(event.detail.message, 'error');
        });
    </script>
    @endscript
</div>
