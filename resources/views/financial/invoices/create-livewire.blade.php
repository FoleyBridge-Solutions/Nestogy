@extends('layouts.app')

@php
$pageTitle = 'Create Invoice';
@endphp

@section('content')
    <div class="container-fluid">
        @livewire('financial.invoice-create')
    </div>
@endsection