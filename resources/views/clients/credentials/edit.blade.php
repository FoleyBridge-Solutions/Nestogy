@extends('layouts.app')

@section('title', 'Edit Credential')

@php
$sidebarContext = 'clients';
$activeDomain = 'clients';
$pageTitle = 'Edit Credential';
$pageSubtitle = 'Update credential details';
$pageActions = [];
@endphp

@section('content')
    <div class="container-fluid">
        @livewire('clients.edit-credential', ['credential' => $credential])
    </div>
@endsection
