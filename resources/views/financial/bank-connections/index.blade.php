@extends('layouts.app')

@section('title', 'Bank Connections')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @livewire('financial.bank-connection-manager')
</div>
@endsection

@push('scripts')
<script src="https://cdn.plaid.com/link/v2/stable/link-initialize.js"></script>
@endpush
