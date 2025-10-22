@extends('layouts.app')
@section('title', 'Expenses')
@php
$pageTitle = 'Expenses';
$pageActions = [['label' => 'Add Expense', 'href' => route('financial.expenses.create'), 'icon' => 'plus']];
@endphp
@section('content')
    @livewire('financial.expense-index')
@endsection
