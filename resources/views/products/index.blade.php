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
    @livewire('products.product-index')
@endsection
