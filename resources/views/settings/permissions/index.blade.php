@extends('layouts.app')

@section('title', 'Permissions Management')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <!-- Page Header -->
    <flux:card>
        <flux:card.header>
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading>Permissions Management</flux:heading>
                    <flux:subheading>Manage user roles, permissions, and access control across your organization</flux:subheading>
                </div>
                <div class="flex gap-3">
                    <flux:dropdown align="end">
                        <flux:button variant="ghost" icon="download">Export</flux:button>
                        <flux:menu>
                            <flux:menu.item href="{{ route('settings.permissions.export') }}" icon="download">
                                Export Configuration
                            </flux:menu.item>
                            <flux:menu.item onclick="document.getElementById('import-file').click()" icon="upload">
                                Import Configuration
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                    
                    <form id="import-form" action="{{ route('settings.permissions.import') }}" method="POST" enctype="multipart/form-data" class="hidden">
                        @csrf
                        <input type="file" id="import-file" name="file" accept=".json" onchange="document.getElementById('import-form').submit()">
                    </form>
                    
                    <flux:button href="{{ route('settings.permissions.matrix') }}" variant="ghost" icon="table-cells">
                        Permission Matrix
                    </flux:button>
                    
                    <flux:button href="{{ route('settings.roles.create') }}" variant="primary" icon="plus">
                        Create Role
                    </flux:button>
                </div>
            </div>
        </flux:card.header>
    </flux:card>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <flux:card>
            
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <flux:badge color="blue" size="lg">{{ $stats['total_users'] }}</flux:badge>
                    </div>
                    <div class="ml-5">
                        <flux:text variant="muted" size="sm">Total Users</flux:text>
                        <flux:heading size="lg">{{ $stats['total_users'] }}</flux:heading>
                    </div>
                </div>
            
        </flux:card>
        
        <flux:card>
            
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <flux:badge color="green" size="lg">{{ $stats['total_roles'] }}</flux:badge>
                    </div>
                    <div class="ml-5">
                        <flux:text variant="muted" size="sm">Total Roles</flux:text>
                        <flux:heading size="lg">{{ $stats['total_roles'] }}</flux:heading>
                    </div>
                </div>
            
        </flux:card>
        
        <flux:card>
            
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <flux:badge color="purple" size="lg">{{ $stats['total_abilities'] }}</flux:badge>
                    </div>
                    <div class="ml-5">
                        <flux:text variant="muted" size="sm">Total Permissions</flux:text>
                        <flux:heading size="lg">{{ $stats['total_abilities'] }}</flux:heading>
                    </div>
                </div>
            
        </flux:card>
        
        <flux:card>
            
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <flux:badge color="amber" size="lg">{{ $stats['users_without_roles'] }}</flux:badge>
                    </div>
                    <div class="ml-5">
                        <flux:text variant="muted" size="sm">Users Without Roles</flux:text>
                        <flux:heading size="lg">{{ $stats['users_without_roles'] }}</flux:heading>
                    </div>
                </div>
            
        </flux:card>
    </div>

    <!-- Tabs for Users and Roles -->
    <flux:tabs>
        <flux:tabs.list>
            <flux:tabs.tab name="users">Users & Permissions</flux:tabs.tab>
            <flux:tabs.tab name="roles">Roles Management</flux:tabs.tab>
            <flux:tabs.tab name="quick-assign">Quick Assign</flux:tabs.tab>
        </flux:tabs.list>

        <!-- Users Tab -->
        <flux:tabs.panel name="users">
            <flux:card>
                <flux:card.header>
                    <div class="flex items-center justify-between">
                        <flux:heading size="lg">User Permissions</flux:heading>
                        <flux:input type="search" placeholder="Search users..." wire:model.live="searchUsers" />
                    </div>
                </flux:card.header>
                
                <flux:table>
                    <flux:table.head>
                        <flux:table.row>
                            <flux:table.heading>
                                <flux:checkbox wire:model="selectAll" />
                            </flux:table.heading>
                            <flux:table.heading>User</flux:table.heading>
                            <flux:table.heading>Roles</flux:table.heading>
                            <flux:table.heading>Direct Permissions</flux:table.heading>
                            <flux:table.heading>Last Active</flux:table.heading>
                            <flux:table.heading>Actions</flux:table.heading>
                        </flux:table.row>
                    </flux:table.head>
                    <flux:table.body>
                        @foreach($users as $user)
                        <flux:table.row>
                            <flux:table.cell>
                                <flux:checkbox value="{{ $user->id }}" wire:model="selectedUsers" />
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center">
                                    <flux:avatar size="sm" src="{{ $user->avatar_url }}" />
                                    <div class="ml-3">
                                        <flux:text weight="medium">{{ $user->name }}</flux:text>
                                        <flux:text size="sm" variant="muted">{{ $user->email }}</flux:text>
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->roles as $role)
                                        @php
                                            $roleColors = [
                                                'super-admin' => 'red',
                                                'admin' => 'purple',
                                                'tech' => 'blue',
                                                'accountant' => 'green',
                                            ];
                                            $color = $roleColors[$role->name] ?? 'gray';
                                        @endphp
                                        <flux:badge color="{{ $color }}" size="sm">{{ $role->title }}</flux:badge>
                                    @empty
                                        <flux:text variant="muted" size="sm">No roles</flux:text>
                                    @endforelse
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($user->abilities->count() > 0)
                                    <flux:badge color="zinc" size="sm">{{ $user->abilities->count() }} permissions</flux:badge>
                                @else
                                    <flux:text variant="muted" size="sm">No direct permissions</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text size="sm" variant="muted">
                                    {{ $user->last_active_at ? $user->last_active_at->diffForHumans() : 'Never' }}
                                </flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:dropdown align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item href="{{ route('settings.permissions.user', $user) }}" icon="shield-check">
                                            Manage Permissions
                                        </flux:menu.item>
                                        <flux:menu.item href="{{ route('users.show', $user) }}" icon="user">
                                            View Profile
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item onclick="revokeAllPermissions({{ $user->id }})" icon="shield-exclamation" variant="danger">
                                            Revoke All Permissions
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                        @endforeach
                    </flux:table.body>
                </flux:table>
                
                <flux:card.footer>
                    {{ $users->links() }}
                </flux:card.footer>
            </flux:card>
        </flux:tabs.panel>

        <!-- Roles Tab -->
        <flux:tabs.panel name="roles">
            <flux:card>
                <flux:card.header>
                    <flux:heading size="lg">Role Management</flux:heading>
                </flux:card.header>
                
                <flux:table>
                    <flux:table.head>
                        <flux:table.row>
                            <flux:table.heading>Role</flux:table.heading>
                            <flux:table.heading>Description</flux:table.heading>
                            <flux:table.heading>Permissions</flux:table.heading>
                            <flux:table.heading>Users</flux:table.heading>
                            <flux:table.heading>Actions</flux:table.heading>
                        </flux:table.row>
                    </flux:table.head>
                    <flux:table.body>
                        @foreach($roles as $role)
                        <flux:table.row>
                            <flux:table.cell>
                                @php
                                    $roleColors = [
                                        'super-admin' => 'red',
                                        'admin' => 'purple',
                                        'tech' => 'blue',
                                        'accountant' => 'green',
                                    ];
                                    $color = $roleColors[$role->name] ?? 'gray';
                                @endphp
                                <flux:badge color="{{ $color }}">{{ $role->title }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text size="sm">{{ $role->description ?? 'No description' }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="zinc" size="sm">{{ $role->abilities->count() }} permissions</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text size="sm">{{ $role->users->count() }} users</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:dropdown align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item href="{{ route('settings.roles.show', $role->name) }}" icon="eye">
                                            View Details
                                        </flux:menu.item>
                                        @if(!in_array($role->name, ['super-admin', 'admin']))
                                        <flux:menu.item href="{{ route('settings.roles.edit', $role->name) }}" icon="pencil">
                                            Edit Role
                                        </flux:menu.item>
                                        <flux:menu.item onclick="duplicateRole('{{ $role->name }}')" icon="document-duplicate">
                                            Duplicate Role
                                        </flux:menu.item>
                                        @endif
                                        @if(!in_array($role->name, ['super-admin', 'admin', 'tech', 'accountant']))
                                        <flux:menu.separator />
                                        <flux:menu.item onclick="deleteRole('{{ $role->name }}')" icon="trash" variant="danger">
                                            Delete Role
                                        </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                        @endforeach
                    </flux:table.body>
                </flux:table>
            </flux:card>
        </flux:tabs.panel>

        <!-- Quick Assign Tab -->
        <flux:tabs.panel name="quick-assign">
            <flux:card>
                <flux:card.header>
                    <flux:heading size="lg">Quick Permission Assignment</flux:heading>
                    <flux:subheading>Bulk assign roles or permissions to multiple users at once</flux:subheading>
                </flux:card.header>
                
                
                    <form action="{{ route('settings.permissions.bulk-assign') }}" method="POST" class="space-y-6">
                        @csrf
                        
                        <!-- User Selection -->
                        <div>
                            <flux:label>Select Users</flux:label>
                            <flux:select multiple name="user_ids[]" size="lg">
                                @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </flux:select>
                        </div>
                        
                        <!-- Action Selection -->
                        <div>
                            <flux:label>Action</flux:label>
                            <flux:radio.group name="action" value="add_role">
                                <flux:radio value="add_role">Add Role</flux:radio>
                                <flux:radio value="remove_role">Remove Role</flux:radio>
                                <flux:radio value="add_ability">Add Permission</flux:radio>
                                <flux:radio value="remove_ability">Remove Permission</flux:radio>
                            </flux:radio.group>
                        </div>
                        
                        <!-- Role Selection (shown when role action selected) -->
                        <div id="role-selection" class="hidden">
                            <flux:label>Select Role</flux:label>
                            <flux:select name="role">
                                <option value="">Choose a role...</option>
                                @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->title }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                        
                        <!-- Permission Selection (shown when permission action selected) -->
                        <div id="ability-selection" class="hidden">
                            <flux:label>Select Permission</flux:label>
                            <flux:select name="ability">
                                <option value="">Choose a permission...</option>
                                @foreach($abilitiesByCategory as $category => $abilities)
                                <optgroup label="{{ $category }}">
                                    @foreach($abilities as $ability)
                                    <option value="{{ $ability['name'] }}">{{ $ability['title'] }}</option>
                                    @endforeach
                                </optgroup>
                                @endforeach
                            </flux:select>
                        </div>
                        
                        <div class="flex justify-end">
                            <flux:button type="submit" variant="primary">Apply Changes</flux:button>
                        </div>
                    </form>
                
            </flux:card>
        </flux:tabs.panel>
    </flux:tabs>

    <!-- Recent Activity -->
    @if(count($recentChanges) > 0)
    <flux:card>
        <flux:card.header>
            <flux:heading size="lg">Recent Permission Changes</flux:heading>
        </flux:card.header>
        
        <flux:table>
            <flux:table.head>
                <flux:table.row>
                    <flux:table.heading>Date</flux:table.heading>
                    <flux:table.heading>User</flux:table.heading>
                    <flux:table.heading>Action</flux:table.heading>
                    <flux:table.heading>Changed By</flux:table.heading>
                </flux:table.row>
            </flux:table.head>
            <flux:table.body>
                @foreach($recentChanges as $change)
                <flux:table.row>
                    <flux:table.cell>{{ $change['created_at'] }}</flux:table.cell>
                    <flux:table.cell>{{ $change['user'] }}</flux:table.cell>
                    <flux:table.cell>{{ $change['action'] }}</flux:table.cell>
                    <flux:table.cell>{{ $change['changed_by'] }}</flux:table.cell>
                </flux:table.row>
                @endforeach
            </flux:table.body>
        </flux:table>
    </flux:card>
    @endif
</div>
@endsection

@push('scripts')
<script>
// Handle action radio button changes
document.querySelectorAll('input[name="action"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const roleSelection = document.getElementById('role-selection');
        const abilitySelection = document.getElementById('ability-selection');
        
        if (this.value === 'add_role' || this.value === 'remove_role') {
            roleSelection.classList.remove('hidden');
            abilitySelection.classList.add('hidden');
        } else if (this.value === 'add_ability' || this.value === 'remove_ability') {
            roleSelection.classList.add('hidden');
            abilitySelection.classList.remove('hidden');
        }
    });
});

function duplicateRole(roleName) {
    // Implementation for role duplication
    if (confirm('Do you want to duplicate this role?')) {
        window.location.href = `/settings/roles/${roleName}/edit`;
    }
}

function deleteRole(roleName) {
    if (confirm('Are you sure you want to delete this role? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/settings/roles/${roleName}`;
        form.innerHTML = `
            @csrf
            @method('DELETE')
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function revokeAllPermissions(userId) {
    if (confirm('Are you sure you want to revoke all permissions for this user?')) {
        // Implementation for revoking all permissions
        console.log('Revoking permissions for user:', userId);
    }
}
</script>
@endpush