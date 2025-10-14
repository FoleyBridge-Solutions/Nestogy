@extends('layouts.app')

@php
    // Determine which model variable we're working with
    $item = $type === 'service' ? $service : $product;
@endphp

@section('title', $type === 'service' ? 'Edit Service' : 'Edit Product')

@section('content')
@livewire('products.edit-product', ['product' => $item])
@endsection
