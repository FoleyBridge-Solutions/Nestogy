<div class="grid grid-cols-12 gap-6">
    {{-- Left Sidebar - Role List --}}
    <div class="col-span-12 md:col-span-4">
        <flux:card>
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="lg">Roles</flux:heading>
                <flux:button variant="primary" size="sm" icon="plus" wire:click="createRole">
                    New Role
                </flux:button>
            </div>

            <div class="space-y-2">
                @forelse($roles as $role)
                    <button 
                        wire:click="selectRole('{{ $role['name'] }}')"
                        class="w-full text-left px-4 py-3 rounded-lg border transition-colors {{ $selectedRole && $selectedRole->name === $role['name'] ? 'bg-blue-50 border-blue-300 dark:bg-blue-900/20 dark:border-blue-700' : 'border-zinc-200 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800' }}"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <flux:text class="font-medium">{{ $role['title'] ?? ucfirst($role['name']) }}</flux:text>
                                <div class="flex gap-2 mt-1">
                                    <flux:badge color="blue" size="sm">{{ $role['users_count'] }} users</flux:badge>
                                    <flux:badge color="purple" size="sm">{{ count($role['abilities']) }} perms</flux:badge>
                                </div>
                            </div>
                            @if($selectedRole && $selectedRole->name === $role['name'])
                                <flux:icon name="chevron-right" class="text-blue-600" />
                            @endif
                        </div>
                    </button>
                @empty
                    <flux:text variant="muted" class="text-center py-4">No roles found</flux:text>
                @endforelse
            </div>
        </flux:card>
    </div>

    {{-- Right Panel - Role Details --}}
    <div class="col-span-12 md:col-span-8">
        @if($isCreating || $isEditing || $selectedRole)
            <flux:card>
                @if($isCreating)
                    <flux:heading size="lg">Create New Role</flux:heading>
                    <flux:text class="mt-2 mb-6">Define a new role with specific permissions</flux:text>
                @elseif($isEditing)
                    <flux:heading size="lg">Edit Role: {{ $form['title'] }}</flux:heading>
                    <flux:text class="mt-2 mb-6">Modify role details and permissions</flux:text>
                @else
                    <div class="flex items-start justify-between mb-6">
                        <div>
                            <flux:heading size="lg">{{ $selectedRole->title ?? ucfirst($selectedRole->name) }}</flux:heading>
                            <flux:text class="mt-2">{{ $selectedRole->description ?? 'No description provided' }}</flux:text>
                            <div class="flex gap-2 mt-3">
                                <flux:badge color="blue">{{ $selectedRole->users_count }} users assigned</flux:badge>
                                <flux:badge color="purple">{{ count($selectedRole->abilities) }} permissions</flux:badge>
                            </div>
                        </div>
                        @if(!in_array($selectedRole->name, ['super-admin']))
                            <flux:button variant="primary" icon="pencil" wire:click="editRole">
                                Edit Role
                            </flux:button>
                        @endif
                    </div>
                @endif

                @if($isCreating || $isEditing)
                    {{-- Role Form --}}
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:input 
                                label="Role Name" 
                                wire:model="form.title" 
                                placeholder="e.g., Senior Technician"
                                required
                            />
                            
                            @if($isCreating)
                                <flux:input 
                                    label="Role Slug" 
                                    wire:model="form.name" 
                                    placeholder="e.g., senior-technician"
                                    description="Lowercase, hyphens only"
                                    required
                                />
                            @else
                                <flux:input 
                                    label="Role Slug" 
                                    value="{{ $form['name'] }}" 
                                    disabled
                                    description="Cannot be changed"
                                />
                            @endif
                        </div>

                        <flux:textarea 
                            label="Description" 
                            wire:model="form.description" 
                            placeholder="Brief description of this role's purpose..."
                            rows="2"
                        />

                        <flux:separator />

                        <div>
                            <flux:heading size="lg" class="mb-4">Permissions ({{ count($form['abilities']) }} selected)</flux:heading>
                            
                            <flux:accordion>
                                @foreach($abilitiesByCategory as $category => $abilities)
                                    @php
                                        $categoryAbilities = collect($abilities)->pluck('name')->toArray();
                                        $selectedInCategory = array_intersect($categoryAbilities, $form['abilities']);
                                        $allSelected = count($selectedInCategory) === count($categoryAbilities);
                                        $someSelected = !$allSelected && count($selectedInCategory) > 0;
                                    @endphp
                                    
                                    <flux:accordion.item>
                                        <flux:accordion.heading>
                                            <div class="flex items-center justify-between w-full">
                                                <div class="flex items-center gap-3">
                                                    <flux:checkbox 
                                                        wire:click.stop="toggleCategory('{{ $category }}')"
                                                        :checked="$allSelected"
                                                    />
                                                    <span class="font-medium">{{ $category }}</span>
                                                </div>
                                                <flux:badge color="{{ $allSelected ? 'green' : ($someSelected ? 'amber' : 'zinc') }}" size="sm">
                                                    {{ count($selectedInCategory) }}/{{ count($abilities) }}
                                                </flux:badge>
                                            </div>
                                        </flux:accordion.heading>
                                        
                                        <flux:accordion.content>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pl-8">
                                                @foreach($abilities as $ability)
                                                    @php
                                                        $isChecked = in_array($ability['name'], $form['abilities']);
                                                    @endphp
                                                    <flux:field variant="inline">
                                                        <flux:checkbox 
                                                            wire:click="toggleAbility('{{ $ability['name'] }}')"
                                                            @if($isChecked) checked @endif
                                                        />
                                                        <flux:label>{{ $ability['title'] }}</flux:label>
                                                    </flux:field>
                                                @endforeach
                                            </div>
                                        </flux:accordion.content>
                                    </flux:accordion.item>
                                @endforeach
                            </flux:accordion>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex gap-3 justify-end pt-4 border-t">
                            <flux:button variant="ghost" wire:click="cancelEdit">
                                Cancel
                            </flux:button>
                            <flux:button variant="primary" wire:click="saveRole">
                                {{ $isCreating ? 'Create Role' : 'Save Changes' }}
                            </flux:button>
                        </div>
                    </div>
                @else
                    {{-- View Mode - Permission List --}}
                    <div>
                        <flux:heading class="mb-4">Assigned Permissions</flux:heading>
                        
                        <flux:accordion>
                            @foreach($abilitiesByCategory as $category => $abilities)
                                @php
                                    $roleAbilities = collect($selectedRole->abilities)->pluck('name')->toArray();
                                    $categoryAbilities = collect($abilities)->pluck('name')->toArray();
                                    $hasAbilities = !empty(array_intersect($categoryAbilities, $roleAbilities));
                                    $selectedInCategory = array_intersect($categoryAbilities, $roleAbilities);
                                @endphp
                                
                                @if($hasAbilities)
                                    <flux:accordion.item>
                                        <flux:accordion.heading>
                                            <div class="flex items-center justify-between w-full">
                                                <span class="font-medium">{{ $category }}</span>
                                                <flux:badge color="green" size="sm">
                                                    {{ count($selectedInCategory) }} permissions
                                                </flux:badge>
                                            </div>
                                        </flux:accordion.heading>
                                        
                                        <flux:accordion.content>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                @foreach($abilities as $ability)
                                                    @if(in_array($ability['name'], $roleAbilities))
                                                        <div class="flex items-center gap-2 px-3 py-2 rounded bg-green-50 dark:bg-green-900/20">
                                                            <flux:icon name="check-circle" variant="micro" class="text-green-600" />
                                                            <flux:text size="sm">{{ $ability['title'] }}</flux:text>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </flux:accordion.content>
                                    </flux:accordion.item>
                                @endif
                            @endforeach
                        </flux:accordion>
                    </div>
                @endif
            </flux:card>
        @else
            <flux:card>
                <div class="text-center py-12">
                    <flux:icon name="shield-check" class="text-zinc-400 mx-auto size-16" />
                    <flux:heading size="lg" class="mt-4">Select a Role</flux:heading>
                    <flux:text class="mt-2">Choose a role from the list to view or edit its permissions</flux:text>
                    <flux:button variant="primary" icon="plus" class="mt-4" wire:click="createRole">
                        Create New Role
                    </flux:button>
                </div>
            </flux:card>
        @endif
    </div>
</div>
