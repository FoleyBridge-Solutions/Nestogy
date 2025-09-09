@extends('layouts.app')

@section('title', 'Create Role')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Page Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create New Role</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Create a custom role with specific permissions for your organization.
                    </p>
                </div>
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

    <!-- Form -->
    <form method="POST" action="{{ route('settings.roles.store') }}" class="space-y-6">
        @csrf

        <!-- Role Information -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Role Information</h3>
                <p class="mt-1 text-sm text-gray-500">Basic details about this role.</p>
            </div>
            <div class="px-4 py-5 sm:px-6 space-y-6">
                <!-- Role Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Role Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('name') border-red-300 @enderror"
                           placeholder="e.g., field-technician">
                    <p class="mt-1 text-xs text-gray-500">Lowercase letters, numbers, and hyphens only. This cannot be changed later.</p>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Display Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Display Title <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('title') border-red-300 @enderror"
                           placeholder="e.g., Field Technician">
                    <p class="mt-1 text-xs text-gray-500">Human-readable name shown in the interface.</p>
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
                              placeholder="Describe the purpose and responsibilities of this role...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Permissions -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Permissions</h3>
                <p class="mt-1 text-sm text-gray-500">Select the permissions this role should have access to.</p>
            </div>
            <div class="px-4 py-5 sm:px-6">
                <div class="space-y-6">
                    @foreach($abilitiesByCategory as $category => $abilities)
                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg">
                            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ $category }}</h4>
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
                                                   {{ in_array($ability['name'], old('abilities', [])) ? 'checked' : '' }}>
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

        <!-- Templates (if available) -->
        @if(!empty($roleTemplates))
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Quick Start with Templates</h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                        <p>You can also use pre-configured templates from the <a href="{{ route('settings.roles.index') }}" class="font-medium underline">main roles page</a> to quickly create roles for common MSP workflows.</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Form Actions -->
        <div class="flex justify-between">
            <div>
                <!-- Permission count display -->
                <div class="text-sm text-gray-500">
                    <span id="selectedCount">0</span> permissions selected
                </div>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('settings.roles.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                    Cancel
                </a>
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Create Role
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
// Auto-generate role name from title
document.getElementById('title').addEventListener('input', function(e) {
    const title = e.target.value;
    const nameField = document.getElementById('name');
    
    // Only auto-generate if name field is empty
    if (!nameField.value) {
        const name = title.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
        nameField.value = name;
    }
});

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

// Update selected count
function updateSelectedCount() {
    const checkedBoxes = document.querySelectorAll('input[name="abilities[]"]:checked');
    document.getElementById('selectedCount').textContent = checkedBoxes.length;
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
