@extends('layouts.app')

@php
$pageTitle = 'Invoices';
@endphp

@section('content')
    <div class="container-fluid">
        <div class="mb-6">
            <p class="text-gray-500">Manage billing and invoices</p>
        </div>
        @livewire('financial.invoice-index')
    </div>
@endsection