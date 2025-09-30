@props([
    'model' => null,
    'label' => '',
    'description' => '',
    'disabled' => false
])

<flux:switch 
    @if($model) wire:model="{{ $model }}" @endif
    :disabled="$disabled"
    {{ $attributes }}>
    <flux:label>{{ $label }}</flux:label>
    @if($description)
        <flux:description>{{ $description }}</flux:description>
    @endif
</flux:switch>