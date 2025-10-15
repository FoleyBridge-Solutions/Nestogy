@extends('layouts.app')

@section('title', 'Client Credits')

@php
$pageTitle = 'Client Credits';
$pageSubtitle = 'Manage client credits from overpayments, refunds, and promotional credits';
$pageActions = [
    [
        'label' => 'Create Credit',
        'href' => route('financial.credits.create'),
        'icon' => 'plus',
        'variant' => 'primary'
    ],
];
@endphp

@section('content')
    @livewire('financial.client-credit-index')
@endsection
