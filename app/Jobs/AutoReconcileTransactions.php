<?php

namespace App\Jobs;

use App\Domains\Financial\Models\BankTransaction;
use App\Domains\Financial\Services\BankReconciliationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoReconcileTransactions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    /**
     * Execute the job.
     */
    public function handle(BankReconciliationService $reconciliationService): void
    {
        Log::info('Starting auto-reconciliation job');

        // Get all unreconciled, non-pending transactions
        $transactions = BankTransaction::where('is_reconciled', false)
            ->where('is_ignored', false)
            ->where('pending', false)
            ->whereDate('created_at', '>=', now()->subDays(7)) // Only recent transactions
            ->orderBy('date', 'desc')
            ->get();

        $reconciled = 0;
        $failed = 0;

        foreach ($transactions as $transaction) {
            try {
                if ($reconciliationService->autoReconcileTransaction($transaction)) {
                    $reconciled++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                Log::error('Auto-reconciliation failed for transaction', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        Log::info('Auto-reconciliation job completed', [
            'total' => $transactions->count(),
            'reconciled' => $reconciled,
            'failed' => $failed,
        ]);
    }
}
