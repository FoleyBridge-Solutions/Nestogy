@extends('layouts.app')

@section('title', 'Create Payment')

@php
$pageTitle = 'Create Payment';
$pageSubtitle = 'Record a new payment transaction';
$pageActions = [
    [
        'label' => 'Back to Payments',
        'href' => route('financial.payments.index'),
        'icon' => 'arrow-left',
        'variant' => 'ghost',
    ],
];
@endphp

@section('content')
<div class="w-full px-6 py-6">
    <livewire:financial.payment-create 
        :client-id="request()->get('client_id')" 
        :invoice-id="request()->get('invoice_id')"
    />
</div>
@endsection
