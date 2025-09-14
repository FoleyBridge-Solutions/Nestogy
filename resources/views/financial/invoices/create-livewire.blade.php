@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="mb-6">
            <h1 class="text-2xl font-bold">Create Invoice</h1>
            <p class="text-gray-500">Create a new invoice for billing</p>
        </div>
        @livewire('financial.invoice-create')
    </div>
@endsection