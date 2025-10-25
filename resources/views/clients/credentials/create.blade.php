@extends('layouts.app')

@section('title', 'Add Credential')

@php
$sidebarContext = 'clients';
$activeDomain = 'clients';
$pageTitle = 'Add Credential';
$pageSubtitle = 'Create a new credential';
$pageActions = [];
@endphp

@section('content')
    <div class="container-fluid">
        @livewire('clients.create-credential')
    </div>
@endsection
