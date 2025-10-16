@extends('layouts.app')

@php
$pageTitle = 'IT Documentation';
$pageSubtitle = 'Manage and organize IT documentation for your systems';
$pageActions = [
    ['label' => 'Create Documentation', 'href' => route('clients.it-documentation.create'), 'icon' => 'plus', 'variant' => 'primary']
];
@endphp

@section('content')
    <div class="container-fluid">
        @livewire('client.client-i-t-documentation-index')
    </div>
@endsection
