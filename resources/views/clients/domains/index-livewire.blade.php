@extends('layouts.app')

@php
$pageTitle = 'Domains';
$pageSubtitle = 'Manage domain registrations, renewals, and DNS settings';
$pageActions = [
    ['label' => 'Add Domain', 'href' => route('clients.domains.create'), 'icon' => 'plus', 'variant' => 'primary']
];
@endphp

@section('content')
    <div class="container-fluid">
        @livewire('clients.client-domain-index')
    </div>
@endsection
