@extends('client-portal.layouts.app')

@section('title', 'Payment Confirmation')

@section('content')
<div class="max-w-3xl mx-auto">
    <flux:card class="text-center space-y-6 py-12">
        {{-- Success Icon --}}
        <div class="flex justify-center">
            <div class="rounded-full bg-green-100 dark:bg-green-900/30 p-6">
                <flux:icon.check-circle class="w-16 h-16 text-green-600 dark:text-green-400" />
            </div>
        </div>

        {{-- Success Message --}}
        <div>
            <flux:heading size="xl" class="mb-2">Payment Successful!</flux:heading>
            <flux:subheading>Your payment has been processed successfully</flux:subheading>
        </div>

        {{-- Payment Details --}}
        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-6 text-left max-w-md mx-auto">
            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-600 dark:text-zinc-400">Amount Paid:</span>
                    <span class="font-semibold text-lg">${{ number_format($payment->amount, 2) }}</span>
                </div>
                
                @if($payment->invoice)
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-600 dark:text-zinc-400">Invoice:</span>
                    <span class="font-medium">#{{ $payment->invoice->number }}</span>
                </div>
                @endif

                <div class="flex justify-between text-sm">
                    <span class="text-zinc-600 dark:text-zinc-400">Payment Method:</span>
                    <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
                </div>

                <div class="flex justify-between text-sm">
                    <span class="text-zinc-600 dark:text-zinc-400">Transaction ID:</span>
                    <span class="font-mono text-xs">{{ $payment->gateway_transaction_id ?? $payment->payment_reference }}</span>
                </div>

                <div class="flex justify-between text-sm">
                    <span class="text-zinc-600 dark:text-zinc-400">Date:</span>
                    <span class="font-medium">{{ $payment->payment_date->format('M d, Y g:i A') }}</span>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row gap-3 justify-center pt-4">
            <flux:button 
                href="{{ route('client.payments.receipt', $payment) }}" 
                variant="outline"
                icon="document-text"
            >
                View Receipt
            </flux:button>

            @if($payment->invoice)
            <flux:button 
                href="{{ route('client.invoices.show', $payment->invoice) }}" 
                variant="outline"
            >
                View Invoice
            </flux:button>
            @endif

            <flux:button 
                href="{{ route('client.dashboard') }}" 
                variant="primary"
            >
                Return to Dashboard
            </flux:button>
        </div>

        {{-- Additional Info --}}
        <div class="pt-6 border-t border-zinc-200 dark:border-zinc-700">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                A confirmation email has been sent to {{ $contact->email }}
            </p>
        </div>
    </flux:card>
</div>
@endsection
