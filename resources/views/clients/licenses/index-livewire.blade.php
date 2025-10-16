@extends('layouts.app')

@php
$pageTitle = 'Licenses';
$pageSubtitle = 'Track software licenses, renewals, and compliance';
$pageActions = [
    ['label' => 'Add License', 'href' => route('clients.licenses.create'), 'icon' => 'plus', 'variant' => 'primary']
];
@endphp

@section('content')
    <div class="container-fluid">
        @livewire('client.client-license-index')
    </div>
@endsection
