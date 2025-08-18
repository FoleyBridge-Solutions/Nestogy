@props([
    'name' => 'assigned_to',
    'label' => 'Assign To',
    'placeholder' => 'Search for user...',
    'required' => false,
    'selected' => null
])

@php
$id = $name;
$inputClasses = 'block w-full pl-10 pr-10 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm transition-colors duration-200';

if ($errors->has($name)) {
    $inputClasses .= ' border-red-500 focus:ring-red-500 focus:border-red-500';
}
@endphp

<div class="space-y-2 relative" 
     x-data="userSearchField({
         name: '{{ $name }}',
         selectedUser: {{ $selected ? json_encode($selected->only(['id', 'name', 'email', 'role'])) : 'null' }}
     })"
     x-init="init()">
     
    @if($label)
        <label for="{{ $id }}_search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
        
    <!-- Hidden input for form submission -->
    <input type="hidden" 
           name="{{ $name }}" 
           x-model="selectedUserId"
           @if($required) required @endif>
        
    <!-- Search Input -->
    <div class="relative">
        <!-- Search Icon -->
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
        
        <!-- Input Field -->
        <input type="text"
               id="{{ $id }}_search"
               x-model="searchQuery"
               @focus="openDropdown()"
               @input="search()"
               @keydown="onKeyDown($event)"
               placeholder="{{ $placeholder }}"
               autocomplete="off"
               class="{{ $inputClasses }}"
               :disabled="loadingUsers">
        
        <!-- Loading Spinner -->
        <div x-show="loadingUsers" class="absolute inset-y-0 right-0 pr-3 flex items-center">
            <svg class="animate-spin h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
        
        <!-- Clear Button -->
        <button type="button" 
                x-show="selectedUser && selectedUser.id"
                @click="clearSelection()"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    
    <!-- Dropdown -->
    <div x-show="open && filteredUsers.length > 0" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-1"
         class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 shadow-lg max-h-60 rounded-lg py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
        
        <template x-for="(user, index) in filteredUsers" :key="user.id">
            <div @click="selectUser(user)"
                 :class="{ 'bg-blue-600 text-white': index === selectedIndex, 'text-gray-900 dark:text-gray-100': index !== selectedIndex }"
                 class="cursor-pointer select-none relative py-3 pl-3 pr-9 hover:bg-gray-100 dark:hover:bg-gray-700">
                
                <div class="flex items-center justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center">
                            <span class="font-medium block truncate" x-text="user.name"></span>
                            <span x-show="user.role" 
                                  class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                                  x-text="user.role"></span>
                        </div>
                        
                        <div x-show="user.email" class="text-sm text-gray-500 dark:text-gray-400 truncate" x-text="user.email"></div>
                    </div>
                </div>
            </div>
        </template>
        
        <!-- No results message -->
        <div x-show="filteredUsers.length === 0 && searchQuery.length > 0" 
             class="py-3 px-3 text-gray-500 dark:text-gray-400 text-sm">
            No users found matching "<span x-text="searchQuery"></span>"
        </div>
        
        <!-- Unassigned option -->
        <div @click="clearSelection()"
             :class="{ 'bg-blue-600 text-white': selectedIndex === -2, 'text-gray-900 dark:text-gray-100': selectedIndex !== -2 }"
             class="cursor-pointer select-none relative py-3 pl-3 pr-9 hover:bg-gray-100 dark:hover:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
            
            <div class="flex items-center">
                <span class="font-medium block truncate text-gray-500 dark:text-gray-400">Unassigned</span>
            </div>
        </div>
    </div>
    
    @error($name)
        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>