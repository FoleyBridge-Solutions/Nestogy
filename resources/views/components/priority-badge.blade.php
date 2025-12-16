@props([
    'priority',
    'model' => null,
    'size' => 'sm',
])

@php
    if ($model && method_exists($model, 'getPriorityColor')) {
        $color = $model->getPriorityColor();
    } else {
        $color = \App\Helpers\StatusColorHelper::priority($priority);
    }
    
    $label = ucfirst($priority);
@endphp

@if($color === 'red')
    <flux:badge color="red" size="{{ $size }}" {{ $attributes }}>{{ $slot->isEmpty() ? $label : $slot }}</flux:badge>
@elseif($color === 'orange')
    <flux:badge color="orange" size="{{ $size }}" {{ $attributes }}>{{ $slot->isEmpty() ? $label : $slot }}</flux:badge>
@elseif($color === 'yellow')
    <flux:badge color="yellow" size="{{ $size }}" {{ $attributes }}>{{ $slot->isEmpty() ? $label : $slot }}</flux:badge>
@elseif($color === 'blue')
    <flux:badge color="blue" size="{{ $size }}" {{ $attributes }}>{{ $slot->isEmpty() ? $label : $slot }}</flux:badge>
@elseif($color === 'green')
    <flux:badge color="green" size="{{ $size }}" {{ $attributes }}>{{ $slot->isEmpty() ? $label : $slot }}</flux:badge>
@else
    <flux:badge color="zinc" size="{{ $size }}" {{ $attributes }}>{{ $slot->isEmpty() ? $label : $slot }}</flux:badge>
@endif
