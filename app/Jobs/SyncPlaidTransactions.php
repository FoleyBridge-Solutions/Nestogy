<?php

namespace App\Jobs;

use App\Domains\Financial\Models\PlaidItem;
use App\Domains\Financial\Services\PlaidService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncPlaidTransactions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public PlaidItem $plaidItem
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PlaidService $plaidService): void
    {
        try {
            Log::info('Syncing Plaid transactions', [
                'item_id' => $this->plaidItem->id,
                'institution' => $this->plaidItem->institution_name,
            ]);

            // Sync transactions
            $count = $plaidService->syncTransactions($this->plaidItem);
            
            // Update balances
            $plaidService->updateBalances($this->plaidItem);

            Log::info('Plaid sync completed', [
                'item_id' => $this->plaidItem->id,
                'transaction_count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error('Plaid sync failed', [
                'item_id' => $this->plaidItem->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Plaid sync job failed permanently', [
            'item_id' => $this->plaidItem->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
