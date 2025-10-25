@extends('layouts.app')

@php
$pageTitle = 'Credentials';
$pageSubtitle = 'Securely store and manage credentials for all services and systems';
$pageActions = [
    ['label' => 'Add Credential', 'href' => route('clients.credentials.create'), 'icon' => 'plus', 'variant' => 'primary']
];
@endphp

@section('content')
    <div class="container-fluid">
        @livewire('clients.client-credential-index')
    </div>
@endsection
