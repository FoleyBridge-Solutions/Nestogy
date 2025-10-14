@extends('layouts.app')

@php
$pageTitle = 'Invoices';
$pageSubtitle = 'Manage billing and invoices';
$pageActions = [
    ['label' => 'Create Invoice', 'href' => route('financial.invoices.create'), 'icon' => 'plus', 'variant' => 'primary']
];
@endphp

@section('content')
    <div class="container-fluid">
        @livewire('financial.invoice-index')
    </div>
@endsection