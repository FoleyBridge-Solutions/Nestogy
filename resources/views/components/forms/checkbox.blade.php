@props([
    'name',
    'label' => null,
    'value' => '1',
    'checked' => false,
    'help' => null,
    'class' => '',
    'containerClass' => '',
    'id' => null
])

@php
$id = $id ?? $name;
$isChecked = old($name) !== null ? (bool)old($name) : $checked;
@endphp

<div class="flex items-start space-x-3 {{ $containerClass }}">
    <div class="flex items-center h-5">
        <input 
            type="checkbox" 
            id="{{ $id }}" 
            name="{{ $name }}" 
            value="{{ $value }}"
            {{ $isChecked ? 'checked' : '' }}
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded transition-colors duration-200 {{ $class }}"
            {{ $attributes->except(['name', 'label', 'value', 'checked', 'help', 'class', 'containerClass', 'id']) }}
        >
    </div>
    
    @if($label || $help)
        <div class="flex-1">
            @if($label)
                <label for="{{ $id }}" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                    {{ $label }}
                </label>
            @endif
            
            @if($help)
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $help }}</p>
            @endif
        </div>
    @endif
    
    @error($name)
        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
    @enderror
</div>
