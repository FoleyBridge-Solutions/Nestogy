@extends('layouts.app')

@section('title', ($client ? $client->name . ' - ' : '') . 'Invoices')

@section('content')
<div class="container mx-auto px-6">
    @if($client)
        <!-- Header with client context -->
        <div class="mb-6">
            <nav class="flex items-center mb-4">
                <a href="{{ route('clients.show', $client) }}" class="text-blue-600 dark:text-blue-400 hover:underline flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to {{ $client->name }}
                </a>
            </nav>
            
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Invoices for {{ $client->name }}</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Manage invoices and billing</p>
                </div>
                
                <div class="flex gap-2">
                    <flux:button href="{{ route('financial.invoices.create') }}" variant="primary">
                        <i class="fas fa-plus mr-2"></i>
                        Create Invoice
                    </flux:button>
                </div>
            </div>
        </div>
    @else
        <!-- Header without client context -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">All Invoices</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Manage all invoices across clients</p>
                </div>
                
                <div class="flex gap-2">
                    <flux:button href="{{ route('financial.invoices.create') }}" variant="primary">
                        <i class="fas fa-plus mr-2"></i>
                        Create Invoice
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <flux:card>
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-green-600 dark:text-green-400"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <flux:text size="sm" class="text-gray-500 dark:text-gray-400">Total Revenue</flux:text>
                    <flux:heading size="lg">${{ number_format($stats['total_revenue'] ?? 0, 2) }}</flux:heading>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center">
                        <i class="fas fa-hourglass-half text-yellow-600 dark:text-yellow-400"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <flux:text size="sm" class="text-gray-500 dark:text-gray-400">Outstanding</flux:text>
                    <flux:heading size="lg">${{ number_format($stats['outstanding'] ?? 0, 2) }}</flux:heading>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <flux:text size="sm" class="text-gray-500 dark:text-gray-400">Overdue</flux:text>
                    <flux:heading size="lg">${{ number_format($stats['overdue'] ?? 0, 2) }}</flux:heading>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                        <i class="fas fa-file-invoice text-blue-600 dark:text-blue-400"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <flux:text size="sm" class="text-gray-500 dark:text-gray-400">Total Invoices</flux:text>
                    <flux:heading size="lg">{{ $stats['total_count'] ?? 0 }}</flux:heading>
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Livewire Component -->
    <livewire:financial.invoice-index />
</div>
@endsection