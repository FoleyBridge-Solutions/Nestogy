@extends('layouts.app')

@section('title', 'User Permissions - ' . $user->name)

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <!-- Page Header -->
    <flux:card>
        <div class="border-b pb-4 mb-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <flux:button href="{{ route('settings.permissions.index') }}" variant="ghost" icon="arrow-left" class="mr-4">
                        Back
                    </flux:button>
                    <flux:avatar size="lg" src="{{ $user->avatar_url }}" />
                    <div class="ml-4">
                        <flux:heading>{{ $user->name }}</flux:heading>
                        <flux:subheading>{{ $user->email }} • {{ $user->company->name }}</flux:subheading>
                    </div>
                </div>
                <div class="flex gap-3">
                    <flux:button onclick="resetPermissions()" variant="ghost" icon="arrow-path">
                        Reset to Default
                    </flux:button>
                    <flux:button form="permissions-form" type="submit" variant="primary" icon="check">
                        Save Changes
                    </flux:button>
                </div>
            </div>
        </div>
    </flux:card>

    <!-- Permission Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <flux:card>
            
                <flux:text variant="muted" size="sm">Total Roles</flux:text>
                <flux:heading size="lg">{{ count($userRoles) }}</flux:heading>
                <div class="mt-2 flex flex-wrap gap-1">
                    @foreach($userRoles as $roleName)
                        @php
                            $role = $availableRoles->firstWhere('name', $roleName);
                            $roleColors = [
                                'super-admin' => 'red',
                                'admin' => 'purple',
                                'tech' => 'blue',
                                'accountant' => 'green',
                            ];
                            $color = $roleColors[$roleName] ?? 'gray';
                        @endphp
                        <flux:badge color="{{ $color }}" size="sm">{{ $role->title ?? $roleName }}</flux:badge>
                    @endforeach
                </div>
            
        </flux:card>
        
        <flux:card>
            
                <flux:text variant="muted" size="sm">Direct Permissions</flux:text>
                <flux:heading size="lg">{{ count($userAbilities) }}</flux:heading>
                <flux:text size="sm" variant="muted" class="mt-2">
                    Permissions assigned directly to this user
                </flux:text>
            
        </flux:card>
        
        <flux:card>
            
                <flux:text variant="muted" size="sm">Effective Permissions</flux:text>
                <flux:heading size="lg">{{ count($effectivePermissions) }}</flux:heading>
                <flux:text size="sm" variant="muted" class="mt-2">
                    Total permissions from roles and direct assignments
                </flux:text>
            
        </flux:card>
    </div>

    <form id="permissions-form" action="{{ route('settings.permissions.user.update', $user) }}" method="POST">
        @csrf
        @method('PUT')
        
        <!-- Roles Assignment -->
        <flux:card>
            <div class="border-b pb-4 mb-4">
                <flux:heading size="lg">Role Assignment</flux:heading>
                <flux:subheading>Assign roles to grant predefined sets of permissions</flux:subheading>
            </div>
            
            
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($availableRoles as $role)
                    <div class="border rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-800">
                        <div class="flex items-start">
                            <flux:checkbox 
                                name="roles[]" 
                                value="{{ $role->name }}"
                                :checked="in_array($role->name, $userRoles)"
                                :disabled="$role->name === 'super-admin' && !auth()->user()->hasRole('super-admin')"
                            />
                            <div class="ml-3 flex-1">
                                <div class="flex items-center gap-2">
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
                                    @if($role->name === 'super-admin')
                                    <flux:badge color="amber" size="sm">Restricted</flux:badge>
                                    @endif
                                </div>
                                <flux:text size="sm" variant="muted" class="mt-1">
                                    {{ $role->description ?? 'No description available' }}
                                </flux:text>
                                <flux:text size="xs" variant="muted" class="mt-2">
                                    {{ $role->abilities->count() }} permissions
                                </flux:text>
                                
                                <!-- Show role permissions on hover/click -->
                                <details class="mt-2">
                                    <summary class="cursor-pointer text-sm text-blue-600 hover:text-blue-700">
                                        View permissions
                                    </summary>
                                    <div class="mt-2 pl-4 border-l-2 border-gray-200">
                                        @foreach($role->abilities->take(5) as $ability)
                                        <flux:text size="xs" variant="muted">• {{ $ability->title ?? $ability->name }}</flux:text>
                                        @endforeach
                                        @if($role->abilities->count() > 5)
                                        <flux:text size="xs" variant="muted">... and {{ $role->abilities->count() - 5 }} more</flux:text>
                                        @endif
                                    </div>
                                </details>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            
        </flux:card>

        <!-- Direct Permissions Assignment -->
        <flux:card>
            <div class="border-b pb-4 mb-4">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="lg">Direct Permissions</flux:heading>
                        <flux:subheading>Grant specific permissions directly to this user</flux:subheading>
                    </div>
                    <div class="flex gap-2">
                        <flux:button type="button" onclick="selectAllPermissions()" variant="ghost" size="sm">
                            Select All
                        </flux:button>
                        <flux:button type="button" onclick="deselectAllPermissions()" variant="ghost" size="sm">
                            Deselect All
                        </flux:button>
                    </div>
                </div>
            </div>
            
            
                <flux:accordion>
                    @foreach($abilitiesByCategory as $category => $abilities)
                    <flux:accordion.item>
                        <flux:accordion.trigger>
                            <div class="flex items-center justify-between w-full">
                                <span>{{ $category }}</span>
                                <flux:badge color="zinc" size="sm">
                                    <span class="permission-count" data-category="{{ $category }}">0</span> / {{ count($abilities) }}
                                </flux:badge>
                            </div>
                        </flux:accordion.trigger>
                        <flux:accordion.content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 p-4">
                                @foreach($abilities as $ability)
                                <div class="flex items-start">
                                    <flux:checkbox 
                                        name="abilities[]" 
                                        value="{{ $ability['name'] }}"
                                        :checked="in_array($ability['name'], $userAbilities)"
                                        class="permission-checkbox"
                                        data-category="{{ $category }}"
                                        onchange="updatePermissionCount('{{ $category }}')"
                                    />
                                    <div class="ml-3">
                                        <flux:text size="sm" weight="medium">{{ $ability['title'] }}</flux:text>
                                        <flux:text size="xs" variant="muted">{{ $ability['description'] }}</flux:text>
                                        @if(isset($effectivePermissions[$ability['name']]))
                                            @if($effectivePermissions[$ability['name']]['source'] === 'role')
                                            <flux:badge color="blue" size="xs" class="mt-1">
                                                From role: {{ $effectivePermissions[$ability['name']]['role'] }}
                                            </flux:badge>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </flux:accordion.content>
                    </flux:accordion.item>
                    @endforeach
                </flux:accordion>
            
        </flux:card>

        <!-- Effective Permissions Summary -->
        <flux:card>
            <div class="border-b pb-4 mb-4">
                <flux:heading size="lg">Effective Permissions Summary</flux:heading>
                <flux:subheading>All permissions this user has from both roles and direct assignments</flux:subheading>
            </div>
            
            
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    @foreach($effectivePermissions as $permissionName => $permission)
                    <div class="flex items-center gap-2">
                        <flux:icon name="check-circle" class="w-4 h-4 text-green-500" />
                        <flux:text size="xs">{{ explode('.', $permissionName)[1] ?? $permissionName }}</flux:text>
                        @if($permission['source'] === 'role')
                        <flux:badge color="blue" size="xs">R</flux:badge>
                        @else
                        <flux:badge color="green" size="xs">D</flux:badge>
                        @endif
                    </div>
                    @endforeach
                </div>
            
        </flux:card>
    </form>
</div>
@endsection

@push('scripts')
<script>
// Initialize permission counts
document.addEventListener('DOMContentLoaded', function() {
    const categories = document.querySelectorAll('[data-category]');
    const uniqueCategories = [...new Set(Array.from(categories).map(el => el.dataset.category))];
    
    uniqueCategories.forEach(category => {
        updatePermissionCount(category);
    });
});

function updatePermissionCount(category) {
    const checkboxes = document.querySelectorAll(`.permission-checkbox[data-category="${category}"]`);
    const checked = document.querySelectorAll(`.permission-checkbox[data-category="${category}"]:checked`);
    const counter = document.querySelector(`.permission-count[data-category="${category}"]`);
    
    if (counter) {
        counter.textContent = checked.length;
    }
}

function selectAllPermissions() {
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
    updateAllCounts();
}

function deselectAllPermissions() {
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    updateAllCounts();
}

function updateAllCounts() {
    const categories = document.querySelectorAll('[data-category]');
    const uniqueCategories = [...new Set(Array.from(categories).map(el => el.dataset.category))];
    
    uniqueCategories.forEach(category => {
        updatePermissionCount(category);
    });
}

function resetPermissions() {
    if (confirm('Reset this user\'s permissions to default? This will remove all custom permissions and keep only default role assignments.')) {
        // Reset form to original state
        document.getElementById('permissions-form').reset();
        updateAllCounts();
    }
}
</script>
@endpush