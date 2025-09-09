@props([
    'name' => 'contact_id',
    'label' => 'Contact',
    'placeholder' => 'Search for contact...',
    'required' => false,
    'clientId' => null,
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
     x-data="contactSearchField({
         name: '{{ $name }}',
         clientId: {{ $clientId ? "'{$clientId}'" : 'null' }},
         selectedContact: {{ $selected ? json_encode($selected->only(['id', 'name', 'email', 'primary'])) : 'null' }}
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
           x-model="selectedContactId"
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
               :disabled="!clientId || loadingContacts">
        
        <!-- Loading Spinner -->
        <div x-show="loadingContacts" class="absolute inset-y-0 right-0 pr-3 flex items-center">
            <svg class="animate-spin h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
        
        <!-- Clear Button -->
        <button type="button" 
                x-show="selectedContact && selectedContact.id"
                @click="clearSelection()"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    
    <!-- Dropdown -->
    <div x-show="open && contacts.length > 0" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-1"
         class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 shadow-lg max-h-60 rounded-lg py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
        
        <template x-for="(contact, index) in contacts" :key="contact.id">
            <div @click="selectContact(contact)"
                 :class="{ 'bg-blue-600 text-white': index === selectedIndex, 'text-gray-900 dark:text-gray-100': index !== selectedIndex }"
                 class="cursor-pointer select-none relative py-3 pl-3 pr-9 hover:bg-gray-100 dark:hover:bg-gray-700">
                
                <div class="flex items-center">
                    <span class="font-medium block truncate" x-text="contact.name"></span>
                    <span x-show="contact.primary" 
                          class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        Primary
                    </span>
                </div>
                
                <span class="text-gray-500 dark:text-gray-400 text-sm block truncate" x-text="contact.email"></span>
            </div>
        </template>
    </div>
    
    @error($name)
        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>