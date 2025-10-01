@extends('client-portal.layouts.app')

@section('title', 'Contract Details')

@section('content')
<!-- Header -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">
                {{ $contract->title ?? 'Contract #' . $contract->contract_number }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400">Contract Number: {{ $contract->contract_number }}</p>
        </div>
        <div class="flex gap-2">
            <flux:button href="{{ route('client.contracts') }}" variant="ghost" icon="arrow-left">
                Back to Contracts
            </flux:button>
        </div>
    </div>
</div>

<!-- Contract Status and Actions -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <flux:card>
        <div class="text-center">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Status</div>
            @php
                $statusVariant = match($contract->status) {
                    'active' => 'success',
                    'pending' => 'warning',
                    'expired', 'terminated' => 'danger',
                    'draft' => 'secondary',
                    default => 'secondary'
                };
            @endphp
            <flux:badge variant="{{ $statusVariant }}" size="lg">
                {{ ucfirst($contract->status ?? 'pending') }}
            </flux:badge>
        </div>
    </flux:card>

    <flux:card>
        <div class="text-center">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Contract Value</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                ${{ number_format($contract->contract_value ?? 0, 2) }}
            </div>
        </div>
    </flux:card>

    <flux:card>
        <div class="text-center">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Type</div>
            <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ ucfirst($contract->contract_type ?? 'N/A') }}
            </div>
        </div>
    </flux:card>
</div>

<!-- Pending Signatures Alert -->
@if($pendingSignatures->count() > 0)
    <flux:card class="mb-6 border-yellow-500">
        <div class="flex items-center gap-4">
            <i class="fas fa-signature fa-2x text-yellow-600"></i>
            <div class="flex-1">
                <flux:heading size="lg">Signature Required</flux:heading>
                <p class="text-gray-600 dark:text-gray-400">This contract requires {{ $pendingSignatures->count() }} signature(s) to be completed.</p>
            </div>
            @if(Route::has('client.contracts.sign'))
                <flux:button href="{{ route('client.contracts.sign', $contract->id) }}" variant="primary" icon="pencil">
                    Sign Now
                </flux:button>
            @endif
        </div>
    </flux:card>
@endif

<!-- Contract Details -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <!-- Description -->
        @if($contract->description)
            <flux:card>
                <flux:heading size="lg" class="mb-4">Description</flux:heading>
                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $contract->description }}</p>
            </flux:card>
        @endif

        <!-- Scope of Work -->
        @if($contract->scope_of_work)
            <flux:card>
                <flux:heading size="lg" class="mb-4">Scope of Work</flux:heading>
                <div class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{!! nl2br(e($contract->scope_of_work)) !!}</div>
            </flux:card>
        @endif

        <!-- Deliverables -->
        @if($contract->deliverables)
            <flux:card>
                <flux:heading size="lg" class="mb-4">Deliverables</flux:heading>
                <div class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{!! nl2br(e($contract->deliverables)) !!}</div>
            </flux:card>
        @endif

        <!-- Terms and Conditions -->
        @if($contract->terms_and_conditions)
            <flux:card>
                <flux:heading size="lg" class="mb-4">Terms and Conditions</flux:heading>
                <div class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap text-sm">{!! nl2br(e($contract->terms_and_conditions)) !!}</div>
            </flux:card>
        @endif

        <!-- Related Invoices -->
        @if($outstandingInvoices->count() > 0)
            <flux:card>
                <flux:heading size="lg" class="mb-4">Outstanding Invoices</flux:heading>
                <div class="space-y-3">
                    @foreach($outstandingInvoices as $invoice)
                        <div class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded">
                            <div>
                                <div class="font-semibold">{{ $invoice->invoice_number }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    Due: {{ $invoice->due_date ? $invoice->due_date->format('M j, Y') : 'N/A' }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold">${{ number_format($invoice->total ?? 0, 2) }}</div>
                                @if(Route::has('client.invoices.show'))
                                    <flux:button href="{{ route('client.invoices.show', $invoice->id) }}" variant="ghost" size="sm">
                                        View
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </flux:card>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Contract Information -->
        <flux:card>
            <flux:heading size="lg" class="mb-4">Contract Information</flux:heading>
            <div class="space-y-3 text-sm">
                @if($contract->start_date)
                    <div>
                        <div class="text-gray-600 dark:text-gray-400">Start Date</div>
                        <div class="font-semibold">{{ $contract->start_date->format('M j, Y') }}</div>
                    </div>
                @endif

                @if($contract->end_date)
                    <div>
                        <div class="text-gray-600 dark:text-gray-400">End Date</div>
                        <div class="font-semibold">{{ $contract->end_date->format('M j, Y') }}</div>
                    </div>
                @endif

                @if($contract->signed_date)
                    <div>
                        <div class="text-gray-600 dark:text-gray-400">Signed Date</div>
                        <div class="font-semibold">{{ \Carbon\Carbon::parse($contract->signed_date)->format('M j, Y') }}</div>
                    </div>
                @endif

                @if($contract->payment_terms)
                    <div>
                        <div class="text-gray-600 dark:text-gray-400">Payment Terms</div>
                        <div class="font-semibold">{{ $contract->payment_terms }}</div>
                    </div>
                @endif

                @if($contract->auto_renew)
                    <div>
                        <div class="text-gray-600 dark:text-gray-400">Auto Renewal</div>
                        <div class="font-semibold flex items-center gap-2">
                            <i class="fas fa-sync text-green-600"></i>
                            Enabled
                        </div>
                    </div>
                @endif
            </div>
        </flux:card>

        <!-- Signatures -->
        @if($contract->signatures->count() > 0)
            <flux:card>
                <flux:heading size="lg" class="mb-4">Signatures</flux:heading>
                <div class="space-y-3 text-sm">
                    @foreach($contract->signatures as $signature)
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-semibold">{{ $signature->signer_name ?? 'Unknown' }}</div>
                                <div class="text-gray-600 dark:text-gray-400">{{ $signature->signer_role ?? 'Signer' }}</div>
                            </div>
                            @php
                                $sigVariant = match($signature->status) {
                                    'signed' => 'success',
                                    'pending' => 'warning',
                                    'declined' => 'danger',
                                    default => 'secondary'
                                };
                            @endphp
                            <flux:badge variant="{{ $sigVariant }}" size="sm">
                                {{ ucfirst($signature->status ?? 'pending') }}
                            </flux:badge>
                        </div>
                    @endforeach
                </div>
            </flux:card>
        @endif
    </div>
</div>
@endsection
