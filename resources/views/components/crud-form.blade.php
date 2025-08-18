@props([
    'action' => '',
    'method' => 'POST',
    'item' => null,
    'title' => '',
    'cancelRoute' => '',
    'fields' => [],
    'submitText' => 'Save',
    'cancelText' => 'Cancel'
])

<div class="bg-white shadow-sm rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">{{ $title }}</h3>
    </div>
    
    <form action="{{ $action }}" method="POST" class="p-6" enctype="multipart/form-data">
        @csrf
        
        @if($method !== 'POST')
            @method($method)
        @endif
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($fields as $field)
                <div class="{{ $field['width'] ?? 'col-span-1' }}">
                    <label for="{{ $field['name'] }}" class="block text-sm font-medium text-gray-700 mb-2">
                        {{ $field['label'] }}
                        @if($field['required'] ?? false)
                            <span class="text-red-500">*</span>
                        @endif
                    </label>
                    
                    @switch($field['type'] ?? 'text')
                        @case('text')
                        @case('email')
                        @case('url')
                        @case('number')
                            <input type="{{ $field['type'] }}" 
                                   id="{{ $field['name'] }}" 
                                   name="{{ $field['name'] }}"
                                   value="{{ old($field['name'], $item->{$field['name']} ?? $field['default'] ?? '') }}"
                                   @if($field['required'] ?? false) required @endif
                                   @if(isset($field['min'])) min="{{ $field['min'] }}" @endif
                                   @if(isset($field['max'])) max="{{ $field['max'] }}" @endif
                                   @if(isset($field['step'])) step="{{ $field['step'] }}" @endif
                                   @if(isset($field['placeholder'])) placeholder="{{ $field['placeholder'] }}" @endif
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @break
                            
                        @case('textarea')
                            <textarea id="{{ $field['name'] }}" 
                                      name="{{ $field['name'] }}"
                                      rows="{{ $field['rows'] ?? 4 }}"
                                      @if($field['required'] ?? false) required @endif
                                      @if(isset($field['placeholder'])) placeholder="{{ $field['placeholder'] }}" @endif
                                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old($field['name'], $item->{$field['name']} ?? $field['default'] ?? '') }}</textarea>
                            @break
                            
                        @case('select')
                            <select id="{{ $field['name'] }}" 
                                    name="{{ $field['name'] }}"
                                    @if($field['required'] ?? false) required @endif
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @if(!($field['required'] ?? false))
                                    <option value="">{{ $field['placeholder'] ?? 'Select an option...' }}</option>
                                @endif
                                @foreach($field['options'] ?? [] as $value => $label)
                                    <option value="{{ $value }}" 
                                            @if(old($field['name'], $item->{$field['name']} ?? $field['default'] ?? '') == $value) selected @endif>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @break
                            
                        @case('checkbox')
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       id="{{ $field['name'] }}" 
                                       name="{{ $field['name'] }}"
                                       value="{{ $field['value'] ?? '1' }}"
                                       @if(old($field['name'], $item->{$field['name']} ?? $field['default'] ?? false)) checked @endif
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <label for="{{ $field['name'] }}" class="ml-2 text-sm text-gray-600">
                                    {{ $field['description'] ?? '' }}
                                </label>
                            </div>
                            @break
                            
                        @case('file')
                            <input type="file" 
                                   id="{{ $field['name'] }}" 
                                   name="{{ $field['name'] }}"
                                   @if($field['required'] ?? false) required @endif
                                   @if(isset($field['accept'])) accept="{{ $field['accept'] }}" @endif
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            @break
                            
                        @case('date')
                        @case('datetime-local')
                            <input type="{{ $field['type'] }}" 
                                   id="{{ $field['name'] }}" 
                                   name="{{ $field['name'] }}"
                                   value="{{ old($field['name'], isset($item->{$field['name']}) ? $item->{$field['name']}?->format($field['type'] === 'date' ? 'Y-m-d' : 'Y-m-d\TH:i') : ($field['default'] ?? '')) }}"
                                   @if($field['required'] ?? false) required @endif
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @break
                            
                        @default
                            <input type="text" 
                                   id="{{ $field['name'] }}" 
                                   name="{{ $field['name'] }}"
                                   value="{{ old($field['name'], $item->{$field['name']} ?? $field['default'] ?? '') }}"
                                   @if($field['required'] ?? false) required @endif
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @endswitch
                    
                    @if(isset($field['help']))
                        <p class="mt-1 text-sm text-gray-500">{{ $field['help'] }}</p>
                    @endif
                    
                    @error($field['name'])
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
        </div>
        
        <div class="flex items-center justify-end mt-6 pt-6 border-t border-gray-200 space-x-3">
            @if($cancelRoute)
                <a href="{{ $cancelRoute }}" 
                   class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-150">
                    {{ $cancelText }}
                </a>
            @endif
            
            <button type="submit" 
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-150">
                {{ $submitText }}
            </button>
        </div>
        
        {{ $slot }}
    </form>
</div>