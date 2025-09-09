@extends('layouts.app')

@section('title', 'Edit Role')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Page Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Role: {{ $role->title }}</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Modify permissions and details for this role.
                    </p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('settings.roles.show', $role->name) }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        View Details
                    </a>
                    <a href="{{ route('settings.roles.index') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Roles
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- System Role Warning -->
    @if(in_array($role->name, ['super-admin', 'admin']))
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">System Role</h3>
                <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                    <p>This is a system-critical role. Changes to permissions should be made carefully as they may affect system functionality.</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Form -->
    <form method="POST" action="{{ route('settings.roles.update', $role->name) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Role Information -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Role Information</h3>
                <p class="mt-1 text-sm text-gray-500">Basic details about this role.</p>
            </div>
            <div class="px-4 py-5 sm:px-6 space-y-6">
                <!-- Role Name (Read Only) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Role Name
                    </label>
                    <input type="text" value="{{ $role->name }}" disabled
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">Role names cannot be changed after creation.</p>
                </div>

                <!-- Display Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Display Title <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title" id="title" value="{{ old('title', $role->title) }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('title') border-red-300 @enderror">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Description
                    </label>
                    <textarea name="description" id="description" rows="3"
                              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('description') border-red-300 @enderror"
                              placeholder="Describe the purpose and responsibilities of this role...">{{ old('description', $role->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Current Permissions Summary -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Current Status</h3>
                        <p class="mt-1 text-sm text-gray-500">Overview of current permissions assigned to this role.</p>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-blue-600">{{ $role->abilities->count() }}</div>
                        <div class="text-sm text-gray-500">permissions assigned</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Permissions -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Permissions</h3>
                        <p class="mt-1 text-sm text-gray-500">Select the permissions this role should have access to.</p>
                    </div>
                    <div class="flex space-x-3">
                        <button type="button" onclick="selectAllPermissions()"
                                class="text-sm text-blue-600 hover:text-blue-800">Select All</button>
                        <button type="button" onclick="clearAllPermissions()"
                                class="text-sm text-gray-500 hover:text-gray-700">Clear All</button>
                        <span class="text-sm text-gray-500">|</span>
                        <span id="selectedCount" class="text-sm font-medium text-gray-900 dark:text-white">0 selected</span>
                    </div>
                </div>
            </div>
            <div class="px-4 py-5 sm:px-6">
                <div class="space-y-6">
                    @foreach($abilitiesByCategory as $category => $abilities)
                        @php
                            $categoryAbilities = collect($abilities)->pluck('name');
                            $currentAbilities = collect($roleAbilities);
                            $categoryHasSelected = $categoryAbilities->intersect($currentAbilities)->isNotEmpty();
                            $categoryAllSelected = $categoryAbilities->diff($currentAbilities)->isEmpty();
                        @endphp
                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg">
                            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ $category }}</h4>
                                        @if($categoryHasSelected)
                                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $categoryAllSelected ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                                                {{ $categoryAbilities->intersect($currentAbilities)->count() }}/{{ $categoryAbilities->count() }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex space-x-2">
                                        <button type="button" onclick="selectAllInCategory('{{ strtolower($category) }}')"
                                                class="text-xs text-blue-600 hover:text-blue-800">Select All</button>
                                        <button type="button" onclick="deselectAllInCategory('{{ strtolower($category) }}')"
                                                class="text-xs text-gray-500 hover:text-gray-700">Clear All</button>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach($abilities as $ability)
                                        <label class="inline-flex items-start">
                                            <input type="checkbox" name="abilities[]" value="{{ $ability['name'] }}"
                                                   class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 mt-0.5 category-{{ strtolower($category) }}"
                                                   {{ in_array($ability['name'], old('abilities', $roleAbilities)) ? 'checked' : '' }}>
                                            <div class="ml-2">
                                                <span class="text-sm text-gray-900 dark:text-white">{{ $ability['title'] }}</span>
                                                <p class="text-xs text-gray-500">{{ $ability['name'] }}</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                @error('abilities')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Danger Zone -->
        @if(!in_array($role->name, ['super-admin', 'admin', 'tech', 'accountant']))
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg border border-red-200 dark:border-red-800">
            <div class="px-4 py-5 sm:px-6 border-b border-red-200 dark:border-red-800">
                <h3 class="text-lg font-medium text-red-900 dark:text-red-200">Danger Zone</h3>
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">Actions that cannot be undone.</p>
            </div>
            <div class="px-4 py-5 sm:px-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Delete this role</h4>
                        <p class="text-sm text-gray-500">Once you delete a role, there is no going back. Please be certain.</p>
                    </div>
                    <button type="button" onclick="deleteRole('{{ $role->name }}', '{{ $role->title }}')"
                            class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50">
                        Delete Role
                    </button>
                </div>
            </div>
        </div>
        @endif

        <!-- Form Actions -->
        <div class="flex justify-between">
            <div class="text-sm text-gray-500">
                <span id="changesSummary">Click a checkbox to see changes</span>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('settings.roles.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                    Cancel
                </a>
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Update Role
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
// Store original permissions for comparison
const originalAbilities = @json($roleAbilities);

// Select/deselect all permissions
function selectAllPermissions() {
    const checkboxes = document.querySelectorAll('input[name="abilities[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    updateSelectedCount();
}

function clearAllPermissions() {
    const checkboxes = document.querySelectorAll('input[name="abilities[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    updateSelectedCount();
}

// Select/deselect all in category
function selectAllInCategory(category) {
    const checkboxes = document.querySelectorAll('.category-' + category);
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    updateSelectedCount();
}

function deselectAllInCategory(category) {
    const checkboxes = document.querySelectorAll('.category-' + category);
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    updateSelectedCount();
}

// Update selected count and changes summary
function updateSelectedCount() {
    const checkedBoxes = document.querySelectorAll('input[name="abilities[]"]:checked');
    const currentAbilities = Array.from(checkedBoxes).map(cb => cb.value);
    
    // Update count
    document.getElementById('selectedCount').textContent = currentAbilities.length + ' selected';
    
    // Calculate changes
    const added = currentAbilities.filter(ability => !originalAbilities.includes(ability));
    const removed = originalAbilities.filter(ability => !currentAbilities.includes(ability));
    
    let summary = '';
    if (added.length === 0 && removed.length === 0) {
        summary = 'No changes made';
    } else {
        const parts = [];
        if (added.length > 0) {
            parts.push(`+${added.length} added`);
        }
        if (removed.length > 0) {
            parts.push(`-${removed.length} removed`);
        }
        summary = parts.join(', ');
    }
    
    document.getElementById('changesSummary').textContent = summary;
}

// Delete role function
function deleteRole(roleName, roleTitle) {
    if (confirm(`Are you sure you want to delete the role "${roleTitle}"? This action cannot be undone.`)) {
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

// Initialize count and listen for changes
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
    
    // Listen for checkbox changes
    document.querySelectorAll('input[name="abilities[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });
});
</script>
@endpush
