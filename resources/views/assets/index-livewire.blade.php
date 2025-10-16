@extends('layouts.app')
@php
$pageTitle = 'Assets';
$pageSubtitle = 'Manage and track your IT assets';
$pageActions = [['label' => 'Create Asset', 'href' => route('assets.create'), 'icon' => 'plus', 'variant' => 'primary']];
@endphp
@section('content')
    <div class="container-fluid">
        @livewire('assets.asset-index')
    </div>
@endsection