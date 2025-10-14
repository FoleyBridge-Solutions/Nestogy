@extends('layouts.app')

@section('title', 'Products')

@php
$pageTitle = 'Products';
$pageSubtitle = 'Manage your product catalog';
$pageActions = [
    [
        'label' => 'Import',
        'href' => route('products.import'),
        'icon' => 'arrow-up-tray',
    ],
    [
        'label' => 'Export',
        'href' => route('products.export'),
        'icon' => 'arrow-down-tray',
    ],
    [
        'label' => 'Add Product',
        'href' => route('products.create'),
        'icon' => 'plus',
        'variant' => 'primary',
    ],
];
@endphp

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8">
    
    </div>

    @livewire('products.product-index')
</div>
@endsection
