@extends('layouts.app')
@section('title', 'Bundles')
@php
$pageTitle = 'Bundles';
$pageActions = [['label' => 'New Bundle', 'href' => route('bundles.create'), 'icon' => 'plus']];
@endphp
@section('content')
    @livewire('product.bundle-index')
@endsection
