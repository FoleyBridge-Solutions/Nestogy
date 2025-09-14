@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="mb-6">
            <h1 class="text-2xl font-bold">Invoices</h1>
            <p class="text-gray-500">Manage billing and invoices</p>
        </div>
        @livewire('financial.invoice-index')
    </div>
@endsection