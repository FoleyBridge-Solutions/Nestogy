@extends('layouts.app')

@section('title', 'Permission Matrix')

@section('content')
<div class="max-w-full mx-auto space-y-6 px-4">
    <!-- Page Header -->
    <flux:card>
        <div class="border-b pb-4 mb-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <flux:button href="{{ route('settings.permissions.index') }}" variant="ghost" icon="arrow-left" class="mr-4">
                        Back
                    </flux:button>
                    <div>
                        <flux:heading>Permission Matrix</flux:heading>
                        <flux:subheading>View and manage role permissions in a matrix format</flux:subheading>
                    </div>
                </div>
                <div class="flex gap-3">
                    <flux:button onclick="toggleEditMode()" variant="ghost" icon="pencil">
                        <span id="edit-mode-text">Enable Edit Mode</span>
                    </flux:button>
                    <flux:button onclick="saveAllChanges()" variant="primary" icon="check" class="hidden" id="save-button">
                        Save All Changes
                    </flux:button>
                </div>
            </div>
        </div>
    </flux:card>

    <!-- Matrix Legend -->
    <flux:card>
        
            <div class="flex items-center gap-6">
                <flux:text size="sm" weight="medium">Legend:</flux:text>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-green-500 rounded"></div>
                    <flux:text size="sm">Has Permission</flux:text>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-gray-300 dark:bg-gray-600 rounded"></div>
                    <flux:text size="sm">No Permission</flux:text>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-red-500 rounded"></div>
                    <flux:text size="sm">System Role (Read-only)</flux:text>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-blue-500 rounded"></div>
                    <flux:text size="sm">Modified (Unsaved)</flux:text>
                </div>
            </div>
        
    </flux:card>

    <!-- Permission Matrix -->
    <flux:card class="overflow-x-auto">
        
            <div class="min-w-full overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0 z-10">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider sticky left-0 bg-gray-50 dark:bg-gray-800 z-20">
                                Permission
                            </th>
                            @foreach($roles as $role)
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider min-w-[120px]">
                                @php
                                    $roleColors = [
                                        'super-admin' => 'red',
                                        'admin' => 'purple',
                                        'tech' => 'blue',
                                        'accountant' => 'green',
                                    ];
                                    $color = $roleColors[$role->name] ?? 'gray';
                                @endphp
                                <div class="flex flex-col items-center">
                                    <flux:badge color="{{ $color }}" size="sm">{{ $role->title }}</flux:badge>
                                    <flux:text size="xs" variant="muted" class="mt-1">
                                        {{ $role->users->count() }} users
                                    </flux:text>
                                </div>
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($matrix as $category => $permissions)
                        <!-- Category Header -->
                        <tr class="bg-gray-100 dark:bg-gray-800">
                            <td colspan="{{ count($roles) + 1 }}" class="px-6 py-2">
                                <flux:text weight="bold" size="sm">{{ $category }}</flux:text>
                            </td>
                        </tr>
                        
                        <!-- Permissions in Category -->
                        @foreach($permissions as $permissionName => $permission)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-6 py-3 text-sm text-gray-900 dark:text-gray-100 sticky left-0 bg-white dark:bg-gray-900 z-10">
                                <div>
                                    <flux:text size="sm" weight="medium">{{ $permission['title'] }}</flux:text>
                                    <flux:text size="xs" variant="muted">{{ $permissionName }}</flux:text>
                                </div>
                            </td>
                            @foreach($roles as $role)
                            <td class="px-3 py-3 text-center">
                                @php
                                    $hasPermission = $permission['roles'][$role->name] ?? false;
                                    $isSystemRole = in_array($role->name, ['super-admin']);
                                    $isProtectedRole = in_array($role->name, ['admin']);
                                @endphp
                                
                                <div class="flex justify-center">
                                    @if($isSystemRole)
                                        <!-- Super Admin - Always has all permissions -->
                                        <div class="w-6 h-6 bg-red-500 rounded flex items-center justify-center" title="System role - cannot be modified">
                                            <flux:icon name="check" class="w-4 h-4 text-white" />
                                        </div>
                                    @else
                                        <!-- Editable permission checkbox -->
                                        <label class="relative inline-flex items-center cursor-pointer permission-toggle" data-role="{{ $role->name }}" data-ability="{{ $permissionName }}">
                                            <input type="checkbox" 
                                                   class="sr-only permission-checkbox" 
                                                   {{ $hasPermission ? 'checked' : '' }}
                                                   {{ $isProtectedRole ? 'data-protected="true"' : '' }}
                                                   onchange="togglePermission('{{ $role->name }}', '{{ $permissionName }}', this.checked)"
                                                   disabled>
                                            <div class="permission-indicator w-6 h-6 rounded transition-colors {{ $hasPermission ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}">
                                                @if($hasPermission)
                                                <flux:icon name="check" class="w-4 h-4 text-white m-auto" />
                                                @endif
                                            </div>
                                        </label>
                                    @endif
                                </div>
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        
    </flux:card>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        @foreach($roles as $role)
        @if(!in_array($role->name, ['super-admin']))
        <flux:card>
            
                <flux:text size="sm" variant="muted">{{ $role->title }}</flux:text>
                <flux:heading size="lg">{{ $role->abilities->count() }}</flux:heading>
                <flux:text size="xs" variant="muted">Total Permissions</flux:text>
            
        </flux:card>
        @endif
        @endforeach
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <flux:card>
        
            <div class="flex items-center gap-3">
                <flux:spinner />
                <flux:text>Updating permissions...</flux:text>
            </div>
        
    </flux:card>
</div>
@endsection

@push('scripts')
<script>
let editMode = false;
let pendingChanges = {};

function toggleEditMode() {
    editMode = !editMode;
    const editModeText = document.getElementById('edit-mode-text');
    const saveButton = document.getElementById('save-button');
    const checkboxes = document.querySelectorAll('.permission-checkbox');
    
    if (editMode) {
        editModeText.textContent = 'Disable Edit Mode';
        saveButton.classList.remove('hidden');
        checkboxes.forEach(checkbox => {
            if (!checkbox.dataset.protected) {
                checkbox.disabled = false;
            }
        });
    } else {
        editModeText.textContent = 'Enable Edit Mode';
        saveButton.classList.add('hidden');
        checkboxes.forEach(checkbox => {
            checkbox.disabled = true;
        });
        // Reset pending changes
        pendingChanges = {};
        resetModifiedIndicators();
    }
}

function togglePermission(role, ability, granted) {
    if (!editMode) return;
    
    // Track the change
    const key = `${role}:${ability}`;
    pendingChanges[key] = { role, ability, granted };
    
    // Update visual indicator
    const toggle = document.querySelector(`[data-role="${role}"][data-ability="${ability}"]`);
    const indicator = toggle.querySelector('.permission-indicator');
    
    if (granted) {
        indicator.classList.remove('bg-gray-300', 'dark:bg-gray-600');
        indicator.classList.add('bg-blue-500'); // Blue for unsaved changes
        indicator.innerHTML = '<svg class="w-4 h-4 text-white m-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
    } else {
        indicator.classList.remove('bg-green-500', 'bg-blue-500');
        indicator.classList.add('bg-gray-300', 'dark:bg-gray-600');
        indicator.innerHTML = '';
    }
}

async function saveAllChanges() {
    if (Object.keys(pendingChanges).length === 0) {
        alert('No changes to save');
        return;
    }
    
    const loadingOverlay = document.getElementById('loading-overlay');
    loadingOverlay.classList.remove('hidden');
    
    try {
        // Save each change
        for (const [key, change] of Object.entries(pendingChanges)) {
            await savePermission(change.role, change.ability, change.granted);
        }
        
        // Clear pending changes
        pendingChanges = {};
        
        // Update indicators to show saved state
        updateSavedIndicators();
        
        // Show success message
        alert('All changes saved successfully');
        
        // Reload to get fresh data
        window.location.reload();
    } catch (error) {
        console.error('Error saving changes:', error);
        alert('Failed to save some changes. Please try again.');
    } finally {
        loadingOverlay.classList.add('hidden');
    }
}

async function savePermission(role, ability, granted) {
    const response = await fetch('{{ route("settings.permissions.matrix.update") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ role, ability, granted })
    });
    
    if (!response.ok) {
        throw new Error(`Failed to update permission: ${role} - ${ability}`);
    }
    
    return response.json();
}

function resetModifiedIndicators() {
    document.querySelectorAll('.permission-indicator.bg-blue-500').forEach(indicator => {
        const checkbox = indicator.closest('label').querySelector('input');
        if (checkbox.checked) {
            indicator.classList.remove('bg-blue-500');
            indicator.classList.add('bg-green-500');
        } else {
            indicator.classList.remove('bg-blue-500');
            indicator.classList.add('bg-gray-300', 'dark:bg-gray-600');
        }
    });
}

function updateSavedIndicators() {
    document.querySelectorAll('.permission-indicator.bg-blue-500').forEach(indicator => {
        indicator.classList.remove('bg-blue-500');
        indicator.classList.add('bg-green-500');
    });
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+E to toggle edit mode
    if (e.ctrlKey && e.key === 'e') {
        e.preventDefault();
        toggleEditMode();
    }
    
    // Ctrl+S to save changes
    if (e.ctrlKey && e.key === 's' && editMode) {
        e.preventDefault();
        saveAllChanges();
    }
});
</script>
@endpush