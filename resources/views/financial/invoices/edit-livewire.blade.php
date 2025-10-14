@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        @livewire('financial.invoice-edit', ['invoice' => $invoice])
    </div>
@endsection