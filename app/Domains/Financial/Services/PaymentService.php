<?php

namespace App\Domains\Financial\Services;

use App\Domains\Client\Models\Client;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Financial\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        protected PaymentApplicationService $applicationService
    ) {}
    /**
     * Create a new payment
     */
    public function createPayment(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $payment = Payment::create([
                'client_id' => $data['client_id'],
                'company_id' => Auth::user()->company_id,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'] ?? now(),
                'payment_method' => $data['payment_method'] ?? 'check',
                'payment_reference' => $data['payment_reference'] ?? '',
                'notes' => $data['notes'] ?? '',
                'status' => $data['status'] ?? 'completed',
                'gateway' => $data['gateway'] ?? 'manual',
                'gateway_transaction_id' => $data['gateway_transaction_id'] ?? null,
                'processed_by' => Auth::id(),
                'currency' => $data['currency'] ?? 'USD',
                'auto_apply' => $data['auto_apply'] ?? true,
            ]);

            if ($payment->auto_apply && $payment->status === 'completed') {
                $this->applicationService->autoApplyPayment($payment);
            } elseif (isset($data['invoice_id']) && $data['invoice_id']) {
                $invoice = Invoice::find($data['invoice_id']);
                if ($invoice) {
                    $amountToApply = min($payment->amount, $invoice->getBalance());
                    $this->applicationService->applyPaymentToInvoice($payment, $invoice, $amountToApply);
                }
            }

            Log::info('Payment created', [
                'payment_id' => $payment->id,
                'client_id' => $payment->client_id,
                'amount' => $payment->amount,
                'auto_applied' => $payment->auto_apply,
                'user_id' => Auth::id(),
            ]);

            return $payment;
        });
    }

    /**
     * Update payment
     */
    public function updatePayment(Payment $payment, array $data): Payment
    {
        return DB::transaction(function () use ($payment, $data) {
            $payment->update($data);

            Log::info('Payment updated', [
                'payment_id' => $payment->id,
                'user_id' => Auth::id(),
            ]);

            return $payment;
        });
    }

    /**
     * Process credit card payment
     */
    public function processCreditCardPayment(array $paymentData): array
    {
        try {
            // This is where you would integrate with payment gateway
            // For now, we'll simulate successful payment

            $payment = $this->createPayment([
                'client_id' => $paymentData['client_id'],
                'invoice_id' => $paymentData['invoice_id'] ?? null,
                'amount' => $paymentData['amount'],
                'method' => 'credit_card',
                'reference' => 'CC-'.uniqid(),
                'status' => 'completed',
                'gateway' => $paymentData['gateway'] ?? 'stripe',
                'notes' => $paymentData['notes'] ?? '',
            ]);

            Log::info('Credit card payment processed', [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'user_id' => Auth::id(),
            ]);

            return [
                'success' => true,
                'payment' => $payment,
                'message' => 'Payment processed successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Credit card payment failed', [
                'error' => $e->getMessage(),
                'payment_data' => $paymentData,
                'user_id' => Auth::id(),
            ]);

            return [
                'success' => false,
                'message' => 'Payment processing failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Process ACH payment
     */
    public function processAchPayment(array $paymentData): array
    {
        try {
            $payment = $this->createPayment([
                'client_id' => $paymentData['client_id'],
                'invoice_id' => $paymentData['invoice_id'] ?? null,
                'amount' => $paymentData['amount'],
                'method' => 'ach',
                'reference' => 'ACH-'.uniqid(),
                'status' => 'pending', // ACH payments typically start as pending
                'gateway' => $paymentData['gateway'] ?? 'stripe',
                'notes' => $paymentData['notes'] ?? '',
            ]);

            Log::info('ACH payment initiated', [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'user_id' => Auth::id(),
            ]);

            return [
                'success' => true,
                'payment' => $payment,
                'message' => 'ACH payment initiated successfully',
            ];

        } catch (\Exception $e) {
            Log::error('ACH payment failed', [
                'error' => $e->getMessage(),
                'payment_data' => $paymentData,
                'user_id' => Auth::id(),
            ]);

            return [
                'success' => false,
                'message' => 'ACH payment failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Refund payment
     */
    public function refundPayment(Payment $payment, float $amount, string $reason = ''): array
    {
        try {
            // Create refund record
            $refund = Payment::create([
                'client_id' => $payment->client_id,
                'invoice_id' => $payment->invoice_id,
                'company_id' => $payment->company_id,
                'amount' => -abs($amount), // Negative amount for refund
                'date' => now()->toDateString(),
                'method' => $payment->method,
                'reference' => 'REFUND-'.$payment->reference,
                'notes' => "Refund for payment #{$payment->id}. Reason: {$reason}",
                'status' => 'completed',
                'gateway' => $payment->gateway,
                'created_by' => Auth::id(),
            ]);

            // Update invoice status if applicable
            if ($payment->invoice_id) {
                $this->updateInvoicePaymentStatus($payment->invoice);
            }

            Log::info('Payment refunded', [
                'original_payment_id' => $payment->id,
                'refund_payment_id' => $refund->id,
                'refund_amount' => $amount,
                'reason' => $reason,
                'user_id' => Auth::id(),
            ]);

            return [
                'success' => true,
                'refund' => $refund,
                'message' => 'Payment refunded successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Payment refund failed', [
                'payment_id' => $payment->id,
                'refund_amount' => $amount,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return [
                'success' => false,
                'message' => 'Refund failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get payment statistics for client
     */
    public function getPaymentStats(Client $client): array
    {
        $payments = Payment::where('client_id', $client->id)
            ->where('amount', '>', 0); // Exclude refunds

        return [
            'total_count' => $payments->count(),
            'total_amount' => $payments->sum('amount'),
            'pending_count' => $payments->where('status', 'pending')->count(),
            'completed_count' => $payments->where('status', 'completed')->count(),
            'failed_count' => $payments->where('status', 'failed')->count(),
            'average_payment' => $payments->avg('amount') ?? 0,
            'last_payment_date' => $payments->orderBy('date', 'desc')->first()?->date,
            'methods_used' => $payments->groupBy('method')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('amount'),
                ];
            }),
        ];
    }

    /**
     * Get outstanding balance for client
     */
    public function getOutstandingBalance(Client $client): float
    {
        $totalInvoiced = Invoice::where('client_id', $client->id)
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->sum('total');

        $totalPaid = Payment::where('client_id', $client->id)
            ->where('status', 'completed')
            ->sum('amount');

        return max(0, $totalInvoiced - $totalPaid);
    }

    /**
     * Archive payment
     */
    public function archivePayment(Payment $payment): bool
    {
        try {
            $payment->update(['archived_at' => now()]);

            Log::info('Payment archived', [
                'payment_id' => $payment->id,
                'user_id' => Auth::id(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to archive payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return false;
        }
    }
}
