@extends('layouts.app')

@section('title', 'Payment Statements')

@php
$pageTitle = 'Payment Statements';
$pageSubtitle = 'View payment applications to invoices';
$pageActions = [];
@endphp

@section('content')
@livewire('financial.statement-index')
@endsection
