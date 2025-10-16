@extends('layouts.app')

@section('title', 'Payments')

@php
$pageTitle = 'Payments';
$pageSubtitle = 'Manage payment records and transactions';
$pageActions = [
    [
        'label' => 'Add Payment',
        'href' => route('financial.payments.create'),
        'icon' => 'plus',
        'variant' => 'primary',
    ],
];
@endphp

@section('content')
    @livewire('financial.payment-index')
@endsection
