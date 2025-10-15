@extends('layouts.app')

@section('title', 'Create Credit')

@php
$pageTitle = 'Create Client Credit';
$pageSubtitle = 'Create a new credit for a client';
$pageActions = [
    [
        'label' => 'Back to Credits',
        'href' => route('financial.credits.index'),
        'icon' => 'arrow-left',
        'variant' => 'ghost'
    ],
];
@endphp

@section('content')
<div class="max-w-3xl mx-auto">
    <flux:card>
        <form action="{{ route('financial.credits.store') }}" method="POST">
            @csrf
            
            <div class="space-y-6">
                <div>
                    <flux:label>Client *</flux:label>
                    <flux:select name="client_id" required>
                        <flux:select.option value="">Select a client</flux:select.option>
                        @foreach($clients as $client)
                        <flux:select.option value="{{ $client->id }}">
                            {{ $client->name }}{{ $client->company_name ? ' - ' . $client->company_name : '' }}
                        </flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('client_id')
                    <flux:text size="sm" class="text-red-600 mt-1">{{ $message }}</flux:text>
                    @enderror
                </div>

                <div>
                    <flux:label>Credit Type *</flux:label>
                    <flux:select name="type" required>
                        <flux:select.option value="">Select credit type</flux:select.option>
                        <flux:select.option value="promotional">Promotional</flux:select.option>
                        <flux:select.option value="goodwill">Goodwill</flux:select.option>
                        <flux:select.option value="refund">Refund</flux:select.option>
                        <flux:select.option value="adjustment">Adjustment</flux:select.option>
                    </flux:select>
                    @error('type')
                    <flux:text size="sm" class="text-red-600 mt-1">{{ $message }}</flux:text>
                    @enderror
                </div>

                <div>
                    <flux:label>Amount *</flux:label>
                    <flux:input 
                        type="number" 
                        name="amount" 
                        step="0.01" 
                        min="0.01" 
                        placeholder="0.00"
                        required
                    />
                    @error('amount')
                    <flux:text size="sm" class="text-red-600 mt-1">{{ $message }}</flux:text>
                    @enderror
                </div>

                <div>
                    <flux:label>Reason *</flux:label>
                    <flux:textarea 
                        name="reason" 
                        rows="3" 
                        placeholder="Enter the reason for this credit..."
                        required
                    />
                    @error('reason')
                    <flux:text size="sm" class="text-red-600 mt-1">{{ $message }}</flux:text>
                    @enderror
                </div>

                <div>
                    <flux:label>Expiry Date</flux:label>
                    <flux:input 
                        type="date" 
                        name="expiry_date" 
                        min="{{ now()->format('Y-m-d') }}"
                    />
                    <flux:text size="sm" variant="muted" class="mt-1">
                        Leave blank for no expiration
                    </flux:text>
                    @error('expiry_date')
                    <flux:text size="sm" class="text-red-600 mt-1">{{ $message }}</flux:text>
                    @enderror
                </div>

                <flux:separator />

                <div class="flex justify-end gap-3">
                    <flux:button 
                        type="button" 
                        variant="ghost"
                        href="{{ route('financial.credits.index') }}"
                    >
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Create Credit
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:card>
</div>
@endsection
