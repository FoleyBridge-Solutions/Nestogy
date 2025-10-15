@extends('layouts.app')

@section('title', 'Credit Details')

@php
$pageTitle = 'Credit Details';
$pageSubtitle = ucfirst($credit->type) . ' Credit • ' . $credit->client->name;
$pageActions = [
    [
        'label' => 'Back to Credits',
        'href' => route('financial.credits.index'),
        'icon' => 'arrow-left',
        'variant' => 'ghost'
    ],
];

if($credit->status === 'active' && $credit->available_amount > 0) {
    $pageActions[] = [
        'label' => 'Apply Credit',
        'href' => route('financial.credits.apply', $credit),
        'icon' => 'currency-dollar',
        'variant' => 'primary'
    ];
}
@endphp

@section('content')
<div class="grid grid-cols-12 gap-6">
    
    <div class="col-span-12 lg:col-span-8 space-y-6">
        
        <flux:card>
            <flux:tabs wire:model="activeTab">
                <flux:tab name="details">Credit Details</flux:tab>
                @if($credit->applications->count() > 0)
                <flux:tab name="applications">Applications ({{ $credit->applications->count() }})</flux:tab>
                @endif
            </flux:tabs>

            <div class="mt-6">
                <flux:tab.panel name="details">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <flux:text size="sm" variant="muted">Credit Type</flux:text>
                                <flux:text>{{ ucfirst(str_replace('_', ' ', $credit->type)) }}</flux:text>
                            </div>
                            
                            <div>
                                <flux:text size="sm" variant="muted">Status</flux:text>
                                <flux:badge 
                                    color="{{ match($credit->status) {
                                        'active' => 'green',
                                        'depleted' => 'zinc',
                                        'expired' => 'amber',
                                        'voided' => 'red',
                                        default => 'zinc'
                                    } }}"
                                >
                                    {{ ucfirst($credit->status) }}
                                </flux:badge>
                            </div>
                            
                            <div>
                                <flux:text size="sm" variant="muted">Created Date</flux:text>
                                <flux:text>{{ $credit->created_at->format('M d, Y') }}</flux:text>
                            </div>
                            
                            @if($credit->expiry_date)
                            <div>
                                <flux:text size="sm" variant="muted">Expiry Date</flux:text>
                                <flux:text class="{{ $credit->expiry_date->isPast() ? 'text-red-600 font-medium' : '' }}">
                                    {{ $credit->expiry_date->format('M d, Y') }}
                                    @if($credit->expiry_date->isPast())
                                        <flux:icon name="exclamation-triangle" class="size-4 inline ml-1 text-red-500" />
                                    @endif
                                </flux:text>
                            </div>
                            @endif
                            
                            <div>
                                <flux:text size="sm" variant="muted">Currency</flux:text>
                                <flux:text>{{ strtoupper($credit->currency) }}</flux:text>
                            </div>
                        </div>
                        
                        @if($credit->reason)
                        <flux:separator />
                        <div>
                            <flux:text size="sm" variant="muted" class="mb-2">Reason</flux:text>
                            <flux:text class="text-zinc-700 dark:text-zinc-300">{{ $credit->reason }}</flux:text>
                        </div>
                        @endif
                        
                        @if($credit->metadata)
                        <flux:separator />
                        <div>
                            <flux:text size="sm" variant="muted" class="mb-2">Additional Information</flux:text>
                            <div class="bg-zinc-50 dark:bg-zinc-900 rounded-lg p-4">
                                <pre class="text-xs">{{ json_encode($credit->metadata, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                        @endif
                    </div>
                </flux:tab.panel>

                @if($credit->applications->count() > 0)
                <flux:tab.panel name="applications">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Invoice</flux:table.column>
                            <flux:table.column>Applied Amount</flux:table.column>
                            <flux:table.column>Applied Date</flux:table.column>
                            <flux:table.column>Applied By</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($credit->applications as $application)
                            <flux:table.row :key="'app-' . $application->id">
                                <flux:table.cell>
                                    @if($application->invoice)
                                        <div>
                                            <flux:text variant="strong">Invoice #{{ $application->invoice->getFullNumber() }}</flux:text>
                                            <flux:text size="sm" variant="muted" class="block">
                                                Balance: ${{ number_format($application->invoice->getBalance(), 2) }}
                                            </flux:text>
                                        </div>
                                    @else
                                        <flux:text variant="muted">-</flux:text>
                                    @endif
                                </flux:table.cell>
                                
                                <flux:table.cell>
                                    <flux:text variant="strong">${{ number_format($application->amount, 2) }}</flux:text>
                                </flux:table.cell>
                                
                                <flux:table.cell>
                                    {{ $application->applied_at ? $application->applied_at->format('M d, Y') : '-' }}
                                </flux:table.cell>
                                
                                <flux:table.cell>
                                    {{ $application->appliedBy->name ?? 'System' }}
                                </flux:table.cell>
                                
                                <flux:table.cell>
                                    @if($application->unapplied_at)
                                    <flux:badge size="sm" color="zinc">Unapplied</flux:badge>
                                    @else
                                    <flux:badge size="sm" color="green">Applied</flux:badge>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </flux:tab.panel>
                @endif
            </div>
        </flux:card>
        
    </div>

    <div class="col-span-12 lg:col-span-4 space-y-6">
        
        <flux:card>
            <flux:heading size="lg" class="mb-4">Credit Summary</flux:heading>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <flux:text size="sm" class="text-zinc-500">Client</flux:text>
                    <flux:text size="sm" class="font-medium">{{ $credit->client->name }}</flux:text>
                </div>
                
                <flux:separator />
                
                <div class="flex justify-between items-center">
                    <flux:text size="sm" class="text-zinc-500">Original Amount</flux:text>
                    <flux:text class="font-semibold text-lg">${{ number_format($credit->amount, 2) }}</flux:text>
                </div>
                
                <div class="flex justify-between items-center">
                    <flux:text size="sm" class="text-zinc-500">Applied Amount</flux:text>
                    <flux:text size="sm" class="font-medium text-red-600">
                        ${{ number_format($credit->amount - $credit->available_amount, 2) }}
                    </flux:text>
                </div>
                
                <div class="flex justify-between items-center">
                    <flux:text size="sm" class="text-zinc-500">Available Amount</flux:text>
                    <flux:text size="sm" class="font-medium {{ $credit->available_amount > 0 ? 'text-green-600' : '' }}">
                        ${{ number_format($credit->available_amount, 2) }}
                    </flux:text>
                </div>
            </div>
        </flux:card>

        @if($credit->status === 'active' && $credit->available_amount > 0)
        <flux:card>
            <flux:heading size="lg" class="mb-4">Available Actions</flux:heading>
            <div class="space-y-2">
                <flux:text size="sm" variant="muted" class="mb-3">
                    This credit has ${{ number_format($credit->available_amount, 2) }} available to apply to invoices.
                </flux:text>
                
                <form action="{{ route('financial.credits.apply', $credit) }}" method="GET">
                    <flux:button type="submit" variant="primary" class="w-full" icon="currency-dollar">
                        Apply to Invoice
                    </flux:button>
                </form>
            </div>
        </flux:card>
        @endif

        @if($credit->status === 'active')
        <flux:card>
            <flux:heading size="lg" class="mb-4">Danger Zone</flux:heading>
            <div class="space-y-2">
                <flux:text size="sm" variant="muted" class="mb-3">
                    Voiding this credit will make it unavailable for future use. This action cannot be undone.
                </flux:text>
                
                <form action="{{ route('financial.credits.void', $credit) }}" method="POST">
                    @csrf
                    <flux:button 
                        type="submit" 
                        variant="danger" 
                        class="w-full" 
                        icon="x-circle"
                        onclick="return confirm('Are you sure you want to void this credit? This action cannot be undone.')"
                    >
                        Void Credit
                    </flux:button>
                </form>
            </div>
        </flux:card>
        @endif

        <flux:card>
            <flux:heading size="lg" class="mb-4">Audit Information</flux:heading>
            <div class="space-y-3">
                @if($credit->createdBy)
                <div>
                    <flux:text size="sm" variant="muted">Created By</flux:text>
                    <flux:text size="sm">{{ $credit->createdBy->name }}</flux:text>
                </div>
                @endif
                
                <div>
                    <flux:text size="sm" variant="muted">Created</flux:text>
                    <flux:text size="sm">{{ $credit->created_at->format('M d, Y \a\t g:i A') }}</flux:text>
                </div>
                
                @if($credit->updated_at->ne($credit->created_at))
                <div>
                    <flux:text size="sm" variant="muted">Last Updated</flux:text>
                    <flux:text size="sm">{{ $credit->updated_at->format('M d, Y \a\t g:i A') }}</flux:text>
                </div>
                @endif
                
                @if($credit->voided_at)
                <div>
                    <flux:text size="sm" variant="muted">Voided</flux:text>
                    <flux:text size="sm" class="text-red-600">{{ $credit->voided_at->format('M d, Y \a\t g:i A') }}</flux:text>
                </div>
                @endif
            </div>
        </flux:card>

    </div>
</div>
@endsection
