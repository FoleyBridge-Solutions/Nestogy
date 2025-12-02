<?php

namespace App\Jobs;

use App\Domains\Financial\Models\PlaidItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncAllPlaidAccounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting batch Plaid sync for all active items');

        $items = PlaidItem::where('status', PlaidItem::STATUS_ACTIVE)
            ->where(function ($query) {
                $query->whereNull('last_synced_at')
                    ->orWhere('last_synced_at', '<', now()->subHour());
            })
            ->get();

        $queued = 0;
        $skipped = 0;

        foreach ($items as $item) {
            try {
                // Dispatch individual sync job
                SyncPlaidTransactions::dispatch($item);
                $queued++;
            } catch (\Exception $e) {
                Log::error('Failed to queue sync job', [
                    'item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
                $skipped++;
            }
        }

        Log::info('Batch Plaid sync completed', [
            'total_items' => $items->count(),
            'queued' => $queued,
            'skipped' => $skipped,
        ]);
    }
}
