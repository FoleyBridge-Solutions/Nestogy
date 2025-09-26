@extends('layouts.app')

@section('title', 'Overdue Invoices')

@section('content')
<flux:main class="space-y-6">
    <flux:header>
        <flux:heading>Overdue Invoices</flux:heading>
        
            <flux:button href="{{ route('financial.collections.reminders') }}" variant="outline">
                <flux:icon name="mail" size="sm" />
                Manage Reminders
            </flux:button>
            <flux:button href="{{ route('financial.invoices.create') }}" variant="primary">
                <flux:icon name="plus" size="sm" />
                New Invoice
            </flux:button>
        
    </flux:header>

    <flux:card>
        <div class="border-b pb-4 mb-4">
            <flux:heading size="lg">Collection Summary</flux:heading>
        </div>
        
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-lg border p-4">
                    <div class="text-sm font-medium text-gray-500">Total Overdue</div>
                    <div class="text-2xl font-bold text-gray-900">${{ number_format($totalOverdue, 2) }}</div>
                    <div class="text-xs text-gray-400">{{ $overdueInvoices->total() }} invoices</div>
                </div>
                
                <div class="bg-white rounded-lg border p-4">
                    <div class="text-sm font-medium text-gray-500">Average Days Overdue</div>
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($avgDaysOverdue, 0) }}</div>
                    <div class="text-xs text-gray-400">days past due</div>
                </div>
                
                <div class="bg-white rounded-lg border p-4">
                    <div class="text-sm font-medium text-gray-500">Collection Rate</div>
                    <div class="text-2xl font-bold text-gray-900">68%</div>
                    <div class="text-xs text-gray-400">Last 30 days</div>
                </div>
            </div>
        
    </flux:card>

    <flux:card>
        <div class="border-b pb-4 mb-4">
            <flux:heading size="lg">Overdue Invoices</flux:heading>
            <div class="flex gap-2 mt-4">
                <flux:input type="search" name="search" placeholder="Search invoices..." />
                <flux:select name="age">
                    <flux:select.option value="">All Ages</flux:select.option>
                    <flux:select.option value="30">0-30 days</flux:select.option>
                    <flux:select.option value="60">31-60 days</flux:select.option>
                    <flux:select.option value="90">61-90 days</flux:select.option>
                    <flux:select.option value="over90">Over 90 days</flux:select.option>
                </flux:select>
            </div>
        </div>
        
            <flux:table>
                <div class="mb-4">
                    <flux:table.row>
                        <flux:table.columns>Invoice #</flux:table.columns>
                        <flux:table.columns>Client</flux:table.columns>
                        <flux:table.columns>Amount Due</flux:table.columns>
                        <flux:table.columns>Due Date</flux:table.columns>
                        <flux:table.columns>Days Overdue</flux:table.columns>
                        <flux:table.columns>Status</flux:table.columns>
                        <flux:table.columns>Actions</flux:table.columns>
                    </flux:table.row>
                </div>
                <flux:table.rows>
                    @forelse($overdueInvoices as $invoice)
                    <flux:table.row>
                        <flux:table.cell>
                            <a href="{{ route('financial.invoices.show', $invoice) }}" class="text-blue-600 hover:underline">
                                {{ $invoice->invoice_number }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell>{{ $invoice->client->name ?? 'N/A' }}</flux:table.cell>
                        <flux:table.cell>${{ number_format($invoice->balance_due, 2) }}</flux:table.cell>
                        <flux:table.cell>{{ $invoice->due_date->format('M d, Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge variant="{{ $invoice->days_overdue > 60 ? 'danger' : ($invoice->days_overdue > 30 ? 'warning' : 'info') }}">
                                {{ $invoice->days_overdue }} days
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge variant="warning">Overdue</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button size="sm" variant="ghost">
                                    <flux:icon name="ellipsis-horizontal" />
                                </flux:button>
                                <flux:menu>
                                    <flux:menu.item 
                                        wire:click="sendReminder({{ $invoice->id }})"
                                        icon="mail">
                                        Send Reminder
                                    </flux:menu.item>
                                    <flux:menu.item 
                                        href="{{ route('financial.invoices.show', $invoice) }}"
                                        icon="eye">
                                        View Invoice
                                    </flux:menu.item>
                                    <flux:menu.item 
                                        wire:click="markDisputed({{ $invoice->id }})"
                                        icon="exclamation-triangle">
                                        Mark as Disputed
                                    </flux:menu.item>
                                    <flux:menu.item 
                                        wire:click="recordPayment({{ $invoice->id }})"
                                        icon="banknotes">
                                        Record Payment
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                    @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center text-gray-500">
                            No overdue invoices found
                        </flux:table.cell>
                    </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
            
            <div class="mt-4">
                {{ $overdueInvoices->links() }}
            </div>
        
    </flux:card>
</flux:main>
@endsection