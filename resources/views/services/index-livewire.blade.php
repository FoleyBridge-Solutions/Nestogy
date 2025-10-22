@extends('layouts.app')
@section('title', 'Services')
@php
$pageTitle = 'Services';
$pageActions = [['label' => 'New Service', 'href' => route('services.create'), 'icon' => 'plus']];
@endphp
@section('content')
    @livewire('product.service-index')
@endsection
