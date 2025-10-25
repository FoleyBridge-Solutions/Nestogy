@extends('layouts.app')
@php
$pageTitle = ($client->name ?? 'Client') . ' - Locations';
$pageSubtitle = 'Manage client locations';
$pageActions = [
    ['label' => 'Add Location', 'href' => route('clients.locations.create', $client), 'icon' => 'plus', 'variant' => 'primary']
];
$sidebarContext = 'clients';
$activeDomain = 'clients';
@endphp
@section('content')
    <div class="container-fluid">
        @livewire('clients.location-index')
    </div>
@endsection
