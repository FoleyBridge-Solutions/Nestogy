@extends('layouts.app')

@section('title', isset($client) && $client ? $client->name . ' - Invoices' : 'Invoices')

@section('content')
<div class="container mx-auto px-6">
    @if(isset($client) && $client)
        <!-- Header with client context -->
        <div class="mb-6">
            <nav class="flex items-center mb-4">
                <a href="{{ route('clients.show', $client) }}" class="text-blue-600 dark:text-blue-400 hover:underline flex items-center">
                    <flux:icon name="arrow-left" class="w-4 h-4 mr-2" />
                    Back to {{ $client->name }}
                </a>
            </nav>
            
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">{{ $client->name }}</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Invoice Management</p>
                </div>
                
                <flux:button href="{{ route('financial.invoices.create', ['client_id' => $client->id]) }}" variant="primary">
                    Create Invoice
                </flux:button>
            </div>
        </div>
    @endif

    <!-- Livewire Component handles everything else -->
    <livewire:financial.invoice-index :client="$client ?? null" />
</div>
@endsection
