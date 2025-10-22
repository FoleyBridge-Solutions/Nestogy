@extends('layouts.app')

@section('title', 'Leads')

@php
$pageTitle = 'Leads';
$pageSubtitle = 'Manage your sales leads and track conversion';
$pageActions = [
    [
        'label' => 'Export CSV',
        'href' => route('leads.export.csv', request()->query()),
        'icon' => 'arrow-down-tray',
        'variant' => 'ghost',
    ],
    [
        'label' => 'Import CSV',
        'href' => route('leads.import.form'),
        'icon' => 'arrow-up-tray',
        'variant' => 'ghost',
    ],
    [
        'label' => 'New Lead',
        'href' => route('leads.create'),
        'icon' => 'plus',
        'variant' => 'primary',
    ],
];
@endphp

@section('content')
    @livewire('leads.lead-index')
@endsection
