<?php

namespace App\Domains\Financial\Services;

use App\Domains\Client\Models\Client;
use App\Domains\Financial\Models\ClientCredit;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Financial\Models\Payment;
use App\Domains\Financial\Models\PaymentApplication;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentApplicationService
{
    public function __construct(
        protected ClientCreditService $creditService
    ) {}

    public function applyPaymentToInvoice(
        Payment $payment,
        Invoice $invoice,
        float $amount,
        ?string $notes = null
    ): PaymentApplication {
        if (! $payment->canApply($amount)) {
            throw new \Exception("Payment does not have {$amount} available to apply");
        }

        if ($amount > $invoice->getBalance()) {
            throw new \Exception("Amount exceeds invoice balance");
        }

        return DB::transaction(function () use ($payment, $invoice, $amount, $notes) {
            $application = PaymentApplication::create([
                'company_id' => $payment->company_id,
                'payment_id' => $payment->id,
                'applicable_type' => Invoice::class,
                'applicable_id' => $invoice->id,
                'amount' => $amount,
                'applied_date' => now()->toDateString(),
                'applied_by' => Auth::id(),
                'notes' => $notes,
            ]);

            $payment->recalculateApplicationAmounts();
            $invoice->updatePaymentStatus();

            Log::info('Payment applied to invoice', [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'user_id' => Auth::id(),
            ]);

            return $application;
        });
    }

    public function applyPaymentToMultipleInvoices(
        Payment $payment,
        array $allocations
    ): Collection {
        $totalAllocated = array_sum(array_column($allocations, 'amount'));
        
        if (! $payment->canApply($totalAllocated)) {
            throw new \Exception("Payment does not have {$totalAllocated} available to apply");
        }

        return DB::transaction(function () use ($payment, $allocations) {
            $applications = collect();

            foreach ($allocations as $allocation) {
                $invoice = Invoice::findOrFail($allocation['invoice_id']);
                $amount = $allocation['amount'];
                $notes = $allocation['notes'] ?? null;

                if ($amount > $invoice->getBalance()) {
                    throw new \Exception("Amount {$amount} exceeds invoice {$invoice->id} balance");
                }

                $application = $this->applyPaymentToInvoice($payment, $invoice, $amount, $notes);
                $applications->push($application);
            }

            return $applications;
        });
    }

    public function unapplyPayment(
        PaymentApplication $application,
        string $reason
    ): bool {
        return DB::transaction(function () use ($application, $reason) {
            $result = $application->unapply($reason, Auth::id());

            if ($result) {
                Log::info('Payment application unapplied', [
                    'application_id' => $application->id,
                    'payment_id' => $application->payment_id,
                    'reason' => $reason,
                    'user_id' => Auth::id(),
                ]);
            }

            return $result;
        });
    }

    public function reallocatePayment(
        Payment $payment,
        array $newAllocations
    ): bool {
        return DB::transaction(function () use ($payment, $newAllocations) {
            foreach ($payment->activeApplications as $application) {
                $application->unapply('Reallocating payment', Auth::id());
            }

            $this->applyPaymentToMultipleInvoices($payment, $newAllocations);

            Log::info('Payment reallocated', [
                'payment_id' => $payment->id,
                'new_allocations' => count($newAllocations),
                'user_id' => Auth::id(),
            ]);

            return true;
        });
    }

    public function autoApplyPayment(
        Payment $payment,
        array $options = []
    ): Collection {
        $client = $payment->client;
        $availableAmount = $payment->getAvailableAmount();

        if ($availableAmount <= 0) {
            return collect();
        }

        $strategy = $options['strategy'] ?? 'oldest_first';

        $unpaidInvoices = $this->getUnpaidInvoicesForClient($client, $strategy);
        
        $applications = collect();
        $remainingAmount = $availableAmount;

        return DB::transaction(function () use ($payment, $unpaidInvoices, &$remainingAmount, &$applications) {
            foreach ($unpaidInvoices as $invoice) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $invoiceBalance = $invoice->getBalance();
                $amountToApply = min($remainingAmount, $invoiceBalance);

                if ($amountToApply > 0) {
                    $application = $this->applyPaymentToInvoice(
                        $payment,
                        $invoice,
                        $amountToApply,
                        'Auto-applied'
                    );

                    $applications->push($application);
                    $remainingAmount -= $amountToApply;
                }
            }

            if ($remainingAmount > 0) {
                $this->creditService->createCreditFromOverpayment($payment, $remainingAmount);
            }

            Log::info('Payment auto-applied', [
                'payment_id' => $payment->id,
                'applications_created' => $applications->count(),
                'total_applied' => $payment->getAvailableAmount() - $remainingAmount,
                'overpayment_credit' => $remainingAmount,
            ]);

            return $applications;
        });
    }

    public function getAvailableApplicationTargets(Payment $payment): Collection
    {
        $client = $payment->client;
        
        return Invoice::where('client_id', $client->id)
            ->where('company_id', $payment->company_id)
            ->whereNotIn('status', [Invoice::STATUS_PAID, Invoice::STATUS_CANCELLED, Invoice::STATUS_DRAFT])
            ->with('items')
            ->get()
            ->filter(function ($invoice) {
                return $invoice->getBalance() > 0;
            })
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'number' => $invoice->getFullNumber(),
                    'date' => $invoice->date,
                    'due_date' => $invoice->due_date,
                    'amount' => $invoice->amount,
                    'balance' => $invoice->getBalance(),
                    'is_overdue' => $invoice->isOverdue(),
                ];
            });
    }

    protected function getUnpaidInvoicesForClient(Client $client, string $strategy): Collection
    {
        $query = Invoice::where('client_id', $client->id)
            ->whereNotIn('status', [Invoice::STATUS_PAID, Invoice::STATUS_CANCELLED, Invoice::STATUS_DRAFT]);

        switch ($strategy) {
            case 'oldest_first':
                $query->orderBy('due_date', 'asc');
                break;
            case 'newest_first':
                $query->orderBy('due_date', 'desc');
                break;
            case 'highest_first':
                $query->orderBy('amount', 'desc');
                break;
            case 'lowest_first':
                $query->orderBy('amount', 'asc');
                break;
            default:
                $query->orderBy('due_date', 'asc');
        }

        return $query->get()->filter(function ($invoice) {
            return $invoice->getBalance() > 0;
        });
    }

    public function getPaymentApplicationHistory(Payment $payment): Collection
    {
        return $payment->applications()
            ->with(['applicable', 'appliedBy', 'unappliedBy'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getTotalAppliedToInvoice(Invoice $invoice): float
    {
        return $invoice->activePaymentApplications()->sum('amount') + 
               $invoice->activeCreditApplications()->sum('amount');
    }
}
