@extends('layouts.app')

@section('title', 'Create Invoice')

@section('content')
<div class="w-full px-6 py-6">
    {{-- Page Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <flux:heading size="xl" level="1">Create Invoice</flux:heading>
            <flux:text class="mt-1">Create a new invoice for billing</flux:text>
        </div>
        <flux:button 
            variant="ghost" 
            icon="arrow-left"
            href="{{ route('financial.invoices.index') }}"
        >
            Back to Invoices
        </flux:button>
    </div>

    {{-- Livewire Component --}}
    <livewire:financial.invoice-create 
        :client-id="request()->get('client_id')" 
        :ticket-id="request()->get('ticket_id')"
    />
</div>
@endsection
