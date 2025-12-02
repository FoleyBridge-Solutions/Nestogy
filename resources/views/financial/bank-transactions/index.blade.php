@extends('layouts.app')

@section('title', 'Bank Transactions')

@section('content')
<div class="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @livewire('financial.bank-transaction-index')
</div>
@endsection
