@extends('layouts.app')

@php
$pageTitle = 'Invoices';
@endphp

@section('content')
    <div class="container-fluid">
        @livewire('financial.invoice-index')
    </div>
@endsection