@extends('layouts.app')

@section('title', $product->name)

@php
$pageTitle = $product->name;
$pageSubtitle = 'SKU: ' . ($product->sku ?? 'N/A') . ($product->category ? ' • Category: ' . $product->category->name : '');
$pageActions = [];

if (auth()->user()->can('update', $product)) {
    $pageActions[] = ['label' => 'Edit', 'href' => route('products.edit', $product), 'icon' => 'pencil', 'variant' => 'ghost'];
}

$pageActions[] = ['label' => 'Back to Products', 'href' => route('products.index'), 'icon' => 'arrow-left', 'variant' => 'ghost'];
@endphp

@section('content')
@livewire('products.show-product', ['product' => $product])
@endsection
