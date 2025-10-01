<div class="grid grid-cols-12 gap-6">
    {{-- Left Sidebar - User List --}}
    <div class="col-span-12 md:col-span-4">
        <flux:card>
            <flux:heading size="lg" class="mb-4">Users</flux:heading>

            {{-- Filters --}}
            <div class="space-y-3 mb-4">
                <flux:input 
                    icon="magnifying-glass" 
                    placeholder="Search users..." 
                    wire:model.live.debounce.300ms="search"
                />
                
                <flux:select placeholder="Filter by role" wire:model.live="roleFilter">
                    <flux:select.option value="">All Roles</flux:select.option>
                    @foreach($roles as $role)
                        <flux:select.option value="{{ $role['name'] }}">{{ $role['title'] ?? ucfirst($role['name']) }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:field variant="inline">
                    <flux:checkbox wire:model.live="showOnlyWithoutRoles" />
                    <flux:label>Show users without roles only</flux:label>
                </flux:field>
            </div>

            {{-- User List --}}
            <div class="space-y-2">
                @forelse($this->users as $user)
                    <button 
                        wire:click="selectUser({{ $user->id }})"
                        class="w-full text-left px-4 py-3 rounded-lg border transition-colors {{ $selectedUser && $selectedUser->id === $user->id ? 'bg-blue-50 border-blue-300 dark:bg-blue-900/20 dark:border-blue-700' : 'border-zinc-200 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800' }}"
                    >
                        <div class="flex items-start gap-3">
                            <flux:avatar size="sm" :src="$user->avatar ?? null" />
                            <div class="flex-1 min-w-0">
                                <flux:text class="font-medium block truncate">{{ $user->name }}</flux:text>
                                <flux:text size="sm" variant="muted" class="block truncate">{{ $user->email }}</flux:text>
                                @if($user->roles->isEmpty())
                                    <flux:badge color="amber" size="sm" class="mt-1">No role</flux:badge>
                                @else
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @foreach($user->roles as $role)
                                            <flux:badge color="blue" size="sm">{{ $role->title ?? $role->name }}</flux:badge>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            @if($selectedUser && $selectedUser->id === $user->id)
                                <flux:icon name="chevron-right" class="text-blue-600 flex-shrink-0" />
                            @endif
                        </div>
                    </button>
                @empty
                    <flux:text variant="muted" class="text-center py-4">No users found</flux:text>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if($this->users->hasPages())
                <div class="mt-4">
                    <flux:pagination :paginator="$this->users" />
                </div>
            @endif
        </flux:card>
    </div>

    {{-- Right Panel - User Details --}}
    <div class="col-span-12 md:col-span-8">
        @if($selectedUser)
            <flux:card>
                {{-- User Header --}}
                <div class="flex items-start justify-between mb-6">
                    <div class="flex items-start gap-4">
                        <flux:avatar size="lg" :src="$selectedUser->avatar ?? null" />
                        <div>
                            <flux:heading size="lg">{{ $selectedUser->name }}</flux:heading>
                            <flux:text variant="muted">{{ $selectedUser->email }}</flux:text>
                            <div class="flex gap-2 mt-2">
                                @if(count($selectedUser->roles) > 0)
                                    @foreach($selectedUser->roles as $role)
                                        <flux:badge color="blue">{{ $role->title ?? $role->name }}</flux:badge>
                                    @endforeach
                                @else
                                    <flux:badge color="amber">No role assigned</flux:badge>
                                @endif
                            </div>
                        </div>
                    </div>
                    @if(!$editMode)
                        <flux:button variant="primary" icon="pencil" wire:click="editUser">
                            Edit Permissions
                        </flux:button>
                    @endif
                </div>

                @if($editMode)
                    {{-- Edit Mode --}}
                    <div class="space-y-6">
                        {{-- Assigned Roles --}}
                        <div>
                            <flux:heading size="lg" class="mb-3">Assigned Roles</flux:heading>
                            <flux:text variant="muted" class="mb-4">Select the roles for this user. Users inherit all permissions from their assigned roles.</flux:text>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($roles as $role)
                                    @php
                                        $roleChecked = in_array($role['name'], $form['assigned_roles']);
                                    @endphp
                                    <flux:field variant="inline">
                                        <flux:checkbox 
                                            wire:click="toggleRole('{{ $role['name'] }}')"
                                            @if($roleChecked) checked @endif
                                        />
                                        <div class="flex-1">
                                            <flux:label>{{ $role['title'] ?? ucfirst($role['name']) }}</flux:label>
                                            @if(isset($role['description']))
                                                <flux:text size="xs" variant="muted" class="block mt-0.5">{{ $role['description'] }}</flux:text>
                                            @endif
                                        </div>
                                    </flux:field>
                                @endforeach
                            </div>
                        </div>

                        <flux:separator />

                        {{-- Direct Permissions --}}
                        <div>
                            <flux:heading size="lg" class="mb-3">Direct Permissions (Override)</flux:heading>
                            <flux:text variant="muted" class="mb-4">Grant additional permissions not provided by roles, or revoke specific permissions.</flux:text>
                            
                            <flux:accordion>
                                @foreach($abilitiesByCategory as $category => $abilities)
                                    <flux:accordion.item>
                                        <flux:accordion.heading>
                                            <div class="flex items-center justify-between w-full">
                                                <span class="font-medium">{{ $category }}</span>
                                                <flux:badge color="zinc" size="sm">{{ count($abilities) }} permissions</flux:badge>
                                            </div>
                                        </flux:accordion.heading>
                                        
                                        <flux:accordion.content>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                @foreach($abilities as $ability)
                                                    @php
                                                        $abilityChecked = in_array($ability['name'], $form['direct_abilities']);
                                                    @endphp
                                                    <flux:field variant="inline">
                                                        <flux:checkbox 
                                                            wire:click="toggleDirectAbility('{{ $ability['name'] }}')"
                                                            @if($abilityChecked) checked @endif
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

                        {{-- Effective Permissions Summary --}}
                        @if(count($effectivePermissions) > 0)
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                                <div class="flex items-start gap-2">
                                    <flux:icon name="information-circle" class="text-blue-600 flex-shrink-0 mt-0.5" />
                                    <div>
                                        <flux:text class="font-medium">Effective Permissions</flux:text>
                                        <flux:text size="sm" class="mt-1">This user will have <strong>{{ count($effectivePermissions) }} total permissions</strong> from roles and direct assignments.</flux:text>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Action Buttons --}}
                        <div class="flex gap-3 justify-end pt-4 border-t">
                            <flux:button variant="ghost" wire:click="cancelEdit">
                                Cancel
                            </flux:button>
                            <flux:button variant="primary" wire:click="saveUserPermissions">
                                Save Changes
                            </flux:button>
                        </div>
                    </div>
                @else
                    {{-- View Mode --}}
                    <div class="space-y-6">
                        {{-- Assigned Roles (View) --}}
                        @if(count($selectedUser->roles) > 0)
                            <div>
                                <flux:heading size="lg" class="mb-3">Assigned Roles</flux:heading>
                                <div class="space-y-2">
                                    @foreach($selectedUser->roles as $role)
                                        <div class="flex items-center gap-3 px-4 py-3 rounded-lg bg-blue-50 dark:bg-blue-900/20">
                                            <flux:icon name="shield-check" class="text-blue-600" />
                                            <div>
                                                <flux:text class="font-medium">{{ $role->title ?? ucfirst($role->name) }}</flux:text>
                                                @if($role->description)
                                                    <flux:text size="sm" variant="muted" class="block mt-0.5">{{ $role->description }}</flux:text>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Direct Permissions (View) --}}
                        @if(count($selectedUser->abilities) > 0)
                            <div>
                                <flux:heading size="lg" class="mb-3">Direct Permissions</flux:heading>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    @foreach($selectedUser->abilities as $ability)
                                        <div class="flex items-center gap-2 px-3 py-2 rounded bg-purple-50 dark:bg-purple-900/20">
                                            <flux:icon name="key" variant="micro" class="text-purple-600" />
                                            <flux:text size="sm">{{ $ability->title ?? $ability->name }}</flux:text>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Effective Permissions Count --}}
                        <div class="bg-zinc-100 dark:bg-zinc-800 rounded-lg p-4">
                            <div class="flex items-center gap-3">
                                <flux:icon name="shield-check" class="text-green-600 size-8" />
                                <div>
                                    <flux:text class="font-medium">Total Effective Permissions</flux:text>
                                    <flux:text size="sm" variant="muted" class="mt-0.5">
                                        This user has <strong>{{ count($effectivePermissions) }} permissions</strong> 
                                        ({{ count($selectedUser->roles) }} from roles, {{ count($selectedUser->abilities) }} direct)
                                    </flux:text>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </flux:card>
        @else
            <flux:card>
                <div class="text-center py-12">
                    <flux:icon name="user-circle" class="text-zinc-400 mx-auto size-16" />
                    <flux:heading size="lg" class="mt-4">Select a User</flux:heading>
                    <flux:text class="mt-2">Choose a user from the list to view or manage their permissions</flux:text>
                </div>
            </flux:card>
        @endif
    </div>
</div>
