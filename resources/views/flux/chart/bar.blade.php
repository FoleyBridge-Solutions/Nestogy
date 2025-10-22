@pure

@props([
    'field' => 'value',
])

<template name="bar" field="{{ $field }}">
    <rect {{ $attributes->class('[:where(&)]:text-zinc-800')->merge([
        'fill' => 'currentColor',
        'stroke' => 'none',
    ]) }}></rect>
</template>
