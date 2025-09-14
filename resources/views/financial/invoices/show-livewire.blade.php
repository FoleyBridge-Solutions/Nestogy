@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        @livewire('financial.invoice-show', ['invoice' => $invoice])
    </div>
@endsection