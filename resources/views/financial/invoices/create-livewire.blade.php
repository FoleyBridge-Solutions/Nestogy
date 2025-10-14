@extends('layouts.app')

@php
$pageTitle = 'Create Invoice';
$pageSubtitle = 'Create a new invoice for your client';
@endphp

@section('content')
    <div class="container-fluid">
        @livewire('financial.invoice-create')
    </div>
@endsection