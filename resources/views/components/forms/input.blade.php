@props([
    'name',
    'label' => null,
    'type' => 'text',
    'required' => false,
    'placeholder' => '',
    'value' => '',
    'help' => null,
    'class' => '',
    'containerClass' => '',
    'id' => null
])

@php
$id = $id ?? $name;
$inputClasses = 'block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm transition-colors duration-200';

if ($errors->has($name)) {
    $inputClasses .= ' border-red-500 focus:ring-red-500 focus:border-red-500';
}

if ($class) {
    $inputClasses .= ' ' . $class;
}
@endphp

<div class="space-y-2 {{ $containerClass }}">
    @if($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    
    <input 
        type="{{ $type }}" 
        id="{{ $id }}" 
        name="{{ $name }}" 
        value="{{ old($name, $value) }}"
        class="{{ $inputClasses }}"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->except(['name', 'label', 'type', 'required', 'placeholder', 'value', 'help', 'class', 'containerClass', 'id']) }}
    >
    
    @error($name)
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
    
    @if($help)
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $help }}</p>
    @endif
</div>
