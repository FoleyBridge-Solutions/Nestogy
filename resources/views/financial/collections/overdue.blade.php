@extends('layouts.app')

@section('title', 'Overdue Invoices')

@section('content')
<flux:main class="space-y-6">
    <flux:header>
        <flux:heading>Overdue Invoices</flux:heading>
        <flux:actions>
            <flux:button href="{{ route('financial.collections.reminders') }}" variant="outline">
                <flux:icon name="mail" size="sm" />
                Manage Reminders
            </flux:button>
            <flux:button href="{{ route('financial.invoices.create') }}" variant="primary">
                <flux:icon name="plus" size="sm" />
                New Invoice
            </flux:button>
        </flux:actions>
    </flux:header>

    <flux:card>
        <flux:card.header>
            <flux:card.title>Collection Summary</flux:card.title>
        </flux:card.header>
        <flux:card.body>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <flux:stat>
                    <flux:stat.label>Total Overdue</flux:stat.label>
                    <flux:stat.value>${{ number_format($totalOverdue, 2) }}</flux:stat.value>
                    <flux:stat.description>{{ $overdueInvoices->total() }} invoices</flux:stat.description>
                </flux:stat>
                
                <flux:stat>
                    <flux:stat.label>Average Days Overdue</flux:stat.label>
                    <flux:stat.value>{{ number_format($avgDaysOverdue, 0) }}</flux:stat.value>
                    <flux:stat.description>days past due</flux:stat.description>
                </flux:stat>
                
                <flux:stat>
                    <flux:stat.label>Collection Rate</flux:stat.label>
                    <flux:stat.value>68%</flux:stat.value>
                    <flux:stat.description>Last 30 days</flux:stat.description>
                </flux:stat>
            </div>
        </flux:card.body>
    </flux:card>

    <flux:card>
        <flux:card.header>
            <flux:card.title>Overdue Invoices</flux:card.title>
            <flux:card.actions>
                <flux:input type="search" name="search" placeholder="Search invoices..." />
                <flux:select name="age">
                    <flux:select.option value="">All Ages</flux:select.option>
                    <flux:select.option value="30">0-30 days</flux:select.option>
                    <flux:select.option value="60">31-60 days</flux:select.option>
                    <flux:select.option value="90">61-90 days</flux:select.option>
                    <flux:select.option value="over90">Over 90 days</flux:select.option>
                </flux:select>
            </flux:card.actions>
        </flux:card.header>
        <flux:card.body>
            <flux:table>
                <flux:table.header>
                    <flux:table.row>
                        <flux:table.head>Invoice #</flux:table.head>
                        <flux:table.head>Client</flux:table.head>
                        <flux:table.head>Amount Due</flux:table.head>
                        <flux:table.head>Due Date</flux:table.head>
                        <flux:table.head>Days Overdue</flux:table.head>
                        <flux:table.head>Status</flux:table.head>
                        <flux:table.head>Actions</flux:table.head>
                    </flux:table.row>
                </flux:table.header>
                <flux:table.body>
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
                </flux:table.body>
            </flux:table>
            
            <div class="mt-4">
                {{ $overdueInvoices->links() }}
            </div>
        </flux:card.body>
    </flux:card>
</flux:main>
@endsection