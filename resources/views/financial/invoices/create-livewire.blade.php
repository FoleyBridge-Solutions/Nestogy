@extends('layouts.app')

@php
$pageTitle = 'Create Invoice';
@endphp

@section('content')
    <div class="container-fluid">
        <div class="mb-6">
            <p class="text-gray-500">Create a new invoice for billing</p>
        </div>
        @livewire('financial.invoice-create')
    </div>
@endsection