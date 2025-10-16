@extends('layouts.app')

@section('title', 'Contracts')

@php
$pageTitle = 'Contracts';
$pageSubtitle = 'Manage service agreements and contracts';
$pageActions = [
    [
        'label' => 'New Contract',
        'href' => route('financial.contracts.create'),
        'icon' => 'plus',
        'variant' => 'primary',
    ],
];
@endphp

@section('content')
    @livewire('contracts.contract-index')
@endsection
