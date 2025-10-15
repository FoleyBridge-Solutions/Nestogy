@extends('layouts.app')

@section('title', 'Payment #' . $payment->payment_reference)

@php
$pageTitle = 'Payment Details';
$pageSubtitle = 'Reference: ' . ($payment->payment_reference ?? 'No Reference') . ' • ' . $payment->client->name;
$pageActions = [
    [
        'label' => 'Back to Payments',
        'href' => route('financial.payments.index'),
        'icon' => 'arrow-left',
        'variant' => 'ghost'
    ],
];

if(in_array($payment->status, ['pending', 'failed'])) {
    $pageActions[] = [
        'label' => 'Edit Payment',
        'href' => route('financial.payments.edit', $payment),
        'icon' => 'pencil',
        'variant' => 'primary'
    ];
}
@endphp

@section('content')
@livewire('financial.payment-show', ['payment' => $payment])
@endsection
