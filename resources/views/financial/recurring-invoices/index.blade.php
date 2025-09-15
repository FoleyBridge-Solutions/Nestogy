@extends('layouts.app')

@section('content')
<div class="container mx-auto mx-auto px-6 py-8">
    <flux:heading size="xl" level="1">
        Recurring Invoices
    </flux:heading>

    <div class="mb-6">
        <flux:subheading>
            Manage recurring invoice templates and automated billing
        </flux:subheading>
    </div>

    <!-- Actions Bar -->
    <div class="flex justify-between items-center mb-6">
        <div class="flex space-x-2">
            <flux:button href="{{ route('financial.recurring-invoices.create') }}" color="primary">
                <flux:icon name="plus" />
                Create Recurring Invoice
            </flux:button>
        </div>

        <!-- Filters -->
        <div class="flex space-x-2">
            <flux:select placeholder="Filter by Status" wire:model.live="status">
                <flux:select.option value="">All Statuses</flux:select.option>
                <flux:select.option value="active">Active</flux:select.option>
                <flux:select.option value="paused">Paused</flux:select.option>
                <flux:select.option value="cancelled">Cancelled</flux:select.option>
            </flux:select>

            <flux:select placeholder="Filter by Frequency" wire:model.live="frequency">
                <flux:select.option value="">All Frequencies</flux:select.option>
                <flux:select.option value="monthly">Monthly</flux:select.option>
                <flux:select.option value="quarterly">Quarterly</flux:select.option>
                <flux:select.option value="yearly">Yearly</flux:select.option>
            </flux:select>
        </div>
    </div>

    <!-- Recurring Invoices Table -->
    <flux:table>
        <flux:table.columns>
            <flux:table.column sortable="template_name">Template Name</flux:table.column>
            <flux:table.column>Client</flux:table.column>
            <flux:table.column>Amount</flux:table.column>
            <flux:table.column>Frequency</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Next Invoice</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse($invoices as $invoice)
            <flux:table.row>
                <flux:table.cell>
                    <div class="font-medium">{{ $invoice->template_name }}</div>
                    @if($invoice->description)
                        <div class="text-sm text-gray-500">{{ Str::limit($invoice->description, 30) }}</div>
                    @endif
                </flux:table.cell>
                <flux:table.cell>{{ $invoice->client->name }}</flux:table.cell>
                <flux:table.cell>{{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}</flux:table.cell>
                <flux:table.cell>{{ ucfirst($invoice->frequency) }}</flux:table.cell>
                <flux:table.cell>
                    <flux:badge :color="$invoice->status === 'active' ? 'green' : ($invoice->status === 'paused' ? 'yellow' : 'gray')">
                        {{ ucfirst($invoice->status) }}
                    </flux:badge>
                </flux:table.cell>
                <flux:table.cell>
                    @if($invoice->next_invoice_date)
                        {{ $invoice->next_invoice_date->format('M j, Y') }}
                    @else
                        <span class="text-gray-400">N/A</span>
                    @endif
                </flux:table.cell>
                <flux:table.cell>
                    <div class="flex space-x-1">
                        <flux:button href="{{ route('financial.recurring-invoices.show', $invoice) }}" size="sm" variant="ghost">
                            <flux:icon name="eye" />
                        </flux:button>
                        <flux:button href="{{ route('financial.recurring-invoices.edit', $invoice) }}" size="sm" variant="ghost">
                            <flux:icon name="pencil" />
                        </flux:button>
                    </div>
                </flux:table.cell>
            </flux:table.row>
            @empty
            <flux:table.row>
                <flux:table.cell colspan="7" class="text-center py-8">
                    <div class="text-gray-500">
                        <flux:icon name="document-text" class="w-12 h-12 mx-auto mb-6 text-gray-300" />
                        <p class="text-lg font-medium">No recurring invoices found</p>
                        <p class="text-sm">Get started by creating your first recurring invoice template.</p>
                        <flux:button href="{{ route('financial.recurring-invoices.create') }}" color="primary" class="mt-6">
                            <flux:icon name="plus" />
                            Create First Recurring Invoice
                        </flux:button>
                    </div>
                </flux:table.cell>
            </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <!-- Pagination -->
    @if($invoices->hasPages())
        <div class="mt-6">
            {{ $invoices->links() }}
        </div>
    @endif

    <!-- Summary Stats -->
    @if($invoices->count() > 0)
    <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-4">
        <flux:card>
            <div class="px-6 py-6 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm">Total Templates</flux:heading>
            </div>
            <div class="p-6">
                <div class="text-2xl font-bold">{{ $invoices->total() }}</div>
            </div>
        </flux:card>

        <flux:card>
            <div class="px-6 py-6 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm">Active Templates</flux:heading>
            </div>
            <div class="p-6">
                <div class="text-2xl font-bold text-green-600">
                    {{ $invoices->where('status', 'active')->count() }}
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="px-6 py-6 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm">Monthly Revenue</flux:heading>
            </div>
            <div class="p-6">
                <div class="text-2xl font-bold">
                    ${{ number_format($invoices->where('status', 'active')->where('frequency', 'monthly')->sum('total_amount'), 0) }}
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="px-6 py-6 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm">Due This Month</flux:heading>
            </div>
            <div class="p-6">
                <div class="text-2xl font-bold text-blue-600">
                    {{ $invoices->where('next_invoice_date', '>=', now()->startOfMonth())->where('next_invoice_date', '<=', now()->endOfMonth())->count() }}
                </div>
            </div>
        </flux:card>
    </div>
    @endif
</div>
@endsection
