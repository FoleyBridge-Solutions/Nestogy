<?php

namespace App\Domains\Financial\Services;

use App\Domains\Client\Models\Client;
use App\Domains\Financial\Models\ClientCredit;
use App\Domains\Financial\Models\ClientCreditApplication;
use App\Domains\Financial\Models\CreditNote;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Financial\Models\Payment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientCreditService
{
    public function createCreditFromOverpayment(
        Payment $payment,
        float $amount
    ): ClientCredit {
        return DB::transaction(function () use ($payment, $amount) {
            $credit = ClientCredit::create([
                'company_id' => $payment->company_id,
                'client_id' => $payment->client_id,
                'source_type' => Payment::class,
                'source_id' => $payment->id,
                'amount' => $amount,
                'available_amount' => $amount,
                'currency' => $payment->currency,
                'type' => ClientCredit::TYPE_OVERPAYMENT,
                'status' => ClientCredit::STATUS_ACTIVE,
                'credit_date' => now()->toDateString(),
                'reason' => "Overpayment from payment {$payment->payment_reference}",
            ]);

            Log::info('Client credit created from overpayment', [
                'credit_id' => $credit->id,
                'payment_id' => $payment->id,
                'client_id' => $payment->client_id,
                'amount' => $amount,
            ]);

            return $credit;
        });
    }

    public function createCreditFromCreditNote(CreditNote $creditNote): ClientCredit
    {
        return DB::transaction(function () use ($creditNote) {
            $credit = ClientCredit::create([
                'company_id' => $creditNote->company_id,
                'client_id' => $creditNote->client_id,
                'source_type' => CreditNote::class,
                'source_id' => $creditNote->id,
                'amount' => $creditNote->total_amount,
                'available_amount' => $creditNote->remaining_balance,
                'currency' => $creditNote->currency_code,
                'type' => ClientCredit::TYPE_CREDIT_NOTE,
                'status' => ClientCredit::STATUS_ACTIVE,
                'credit_date' => $creditNote->credit_date,
                'reason' => "Credit note {$creditNote->reference_number}",
            ]);

            Log::info('Client credit created from credit note', [
                'credit_id' => $credit->id,
                'credit_note_id' => $creditNote->id,
                'client_id' => $creditNote->client_id,
                'amount' => $creditNote->total_amount,
            ]);

            return $credit;
        });
    }

    public function createManualCredit(
        Client $client,
        float $amount,
        string $type,
        array $data = []
    ): ClientCredit {
        return DB::transaction(function () use ($client, $amount, $type, $data) {
            $credit = ClientCredit::create([
                'company_id' => $client->company_id,
                'client_id' => $client->id,
                'source_type' => null,
                'source_id' => null,
                'amount' => $amount,
                'available_amount' => $amount,
                'currency' => $data['currency'] ?? 'USD',
                'type' => $type,
                'status' => ClientCredit::STATUS_ACTIVE,
                'credit_date' => $data['credit_date'] ?? now()->toDateString(),
                'expiry_date' => $data['expiry_date'] ?? null,
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            Log::info('Manual client credit created', [
                'credit_id' => $credit->id,
                'client_id' => $client->id,
                'type' => $type,
                'amount' => $amount,
                'user_id' => Auth::id(),
            ]);

            return $credit;
        });
    }

    public function applyCreditToInvoice(
        ClientCredit $credit,
        Invoice $invoice,
        float $amount
    ): ClientCreditApplication {
        if (! $credit->canApply($amount)) {
            throw new \Exception("Credit does not have {$amount} available");
        }

        if ($amount > $invoice->getBalance()) {
            throw new \Exception("Amount exceeds invoice balance");
        }

        return DB::transaction(function () use ($credit, $invoice, $amount) {
            $application = ClientCreditApplication::create([
                'company_id' => $credit->company_id,
                'client_credit_id' => $credit->id,
                'applicable_type' => Invoice::class,
                'applicable_id' => $invoice->id,
                'amount' => $amount,
                'applied_date' => now()->toDateString(),
                'applied_by' => Auth::id(),
            ]);

            $credit->recalculateAvailableAmount();
            $invoice->updatePaymentStatus();

            Log::info('Client credit applied to invoice', [
                'credit_id' => $credit->id,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'user_id' => Auth::id(),
            ]);

            return $application;
        });
    }

    public function expireCredit(ClientCredit $credit): bool
    {
        if ($credit->status !== ClientCredit::STATUS_ACTIVE) {
            return false;
        }

        $credit->update([
            'status' => ClientCredit::STATUS_EXPIRED,
        ]);

        Log::info('Client credit expired', [
            'credit_id' => $credit->id,
            'client_id' => $credit->client_id,
            'remaining_amount' => $credit->available_amount,
        ]);

        return true;
    }

    public function voidCredit(ClientCredit $credit, string $reason): bool
    {
        if ($credit->status === ClientCredit::STATUS_VOIDED) {
            return false;
        }

        $credit->void($reason);

        Log::info('Client credit voided', [
            'credit_id' => $credit->id,
            'client_id' => $credit->client_id,
            'reason' => $reason,
            'user_id' => Auth::id(),
        ]);

        return true;
    }

    public function getClientAvailableCredits(Client $client): Collection
    {
        return ClientCredit::where('client_id', $client->id)
            ->where('status', ClientCredit::STATUS_ACTIVE)
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', now());
            })
            ->where('available_amount', '>', 0)
            ->orderBy('credit_date', 'asc')
            ->get();
    }

    public function getTotalAvailableCredit(Client $client): float
    {
        return $this->getClientAvailableCredits($client)->sum('available_amount');
    }

    public function autoExpireCredits(): int
    {
        $expiredCount = 0;

        $expiredCredits = ClientCredit::where('status', ClientCredit::STATUS_ACTIVE)
            ->where('expiry_date', '<', now())
            ->get();

        foreach ($expiredCredits as $credit) {
            if ($this->expireCredit($credit)) {
                $expiredCount++;
            }
        }

        Log::info('Auto-expired client credits', [
            'count' => $expiredCount,
        ]);

        return $expiredCount;
    }

    public function getCreditHistory(ClientCredit $credit): Collection
    {
        return $credit->applications()
            ->with(['applicable', 'appliedBy'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
