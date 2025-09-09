@props([
    'model' => null,
    'label' => '',
    'description' => '',
    'disabled' => false
])

<div class="flex items-center justify-between {{ $attributes->get('class') }}">
    <div class="flex-1">
        @if($label)
            <label class="text-sm font-medium text-gray-700">{{ $label }}</label>
        @endif
        @if($description)
            <p class="text-sm text-gray-500">{{ $description }}</p>
        @endif
    </div>
    <button type="button" 
            @if($model)
                @click="{{ $model }} = !{{ $model }}"
                :class="{{ $model }} ? 'bg-blue-600' : 'bg-gray-200'"
            @endif
            @if($disabled) disabled @endif
            class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}">
        <span @if($model) :class="{{ $model }} ? 'translate-x-5' : 'translate-x-0'" @endif
              class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"></span>
    </button>
</div>
