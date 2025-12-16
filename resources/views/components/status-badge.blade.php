@props([
    'status',
    'type' => null,
    'model' => null,
    'size' => 'sm',
])

@php
    // Determine color
    if ($model && method_exists($model, 'getStatusColor')) {
        $color = $model->getStatusColor();
    } elseif ($type) {
        $color = \App\Helpers\StatusColorHelper::get($type, $status);
    } else {
        $color = 'zinc';
    }
    
    // Format label
    $label = ucfirst(str_replace('_', ' ', $status));
@endphp

@if($color === 'yellow')
    <flux:badge color="yellow" size="{{ $size }}" {{ $attributes }}>{{ $slot->isEmpty() ? $label : $slot }}</flux:badge>
@elseif($color === 'blue')
    <flux:badge color="blue" size="{{ $size }}" {{ $attributes }}>{{ $slot->isEmpty() ? $label : $slot }}</flux:badge>
@elseif($color === 'green')
    <flux:badge color="green" size="{{ $size }}" {{ $attributes }}>{{ $slot->isEmpty() ? $label : $slot }}</flux:badge>
@elseif($color === 'red')
    <flux:badge color="red" size="{{ $size }}" {{ $attributes }}>{{ $slot->isEmpty() ? $label : $slot }}</flux:badge>
@elseif($color === 'orange')
    <flux:badge color="orange" size="{{ $size }}" {{ $attributes }}>{{ $slot->isEmpty() ? $label : $slot }}</flux:badge>
@elseif($color === 'purple')
    <flux:badge color="purple" size="{{ $size }}" {{ $attributes }}>{{ $slot->isEmpty() ? $label : $slot }}</flux:badge>
@elseif($color === 'pink')
    <flux:badge color="pink" size="{{ $size }}" {{ $attributes }}>{{ $slot->isEmpty() ? $label : $slot }}</flux:badge>
@elseif($color === 'cyan')
    <flux:badge color="cyan" size="{{ $size }}" {{ $attributes }}>{{ $slot->isEmpty() ? $label : $slot }}</flux:badge>
@else
    <flux:badge color="zinc" size="{{ $size }}" {{ $attributes }}>{{ $slot->isEmpty() ? $label : $slot }}</flux:badge>
@endif
