@extends('layouts.app')

@section('title', $type === 'service' ? 'Create Service' : 'Create Product')

@section('content')
@livewire('products.create-product')
@endsection
