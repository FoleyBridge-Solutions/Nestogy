@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="mb-6">
            <h1 class="text-2xl font-bold">Edit Invoice</h1>
            <p class="text-gray-500">Edit invoice #{{ $invoice->number }}</p>
        </div>
        @livewire('financial.invoice-edit', ['invoice' => $invoice])
    </div>
@endsection