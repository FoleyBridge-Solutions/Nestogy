@extends('layouts.app')
@php
$pageTitle = ($client->name ?? 'Client') . ' - Contacts';
$pageSubtitle = 'Manage client contacts';
$pageActions = [
    ['label' => 'Add Contact', 'href' => route('clients.contacts.create'), 'icon' => 'plus', 'variant' => 'primary']
];
$sidebarContext = 'clients';
$activeDomain = 'clients';
@endphp
@section('content')
    <div class="container-fluid">
        @livewire('clients.contact-index')
    </div>
@endsection
