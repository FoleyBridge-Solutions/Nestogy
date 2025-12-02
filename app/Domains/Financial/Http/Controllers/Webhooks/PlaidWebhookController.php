<?php

namespace App\Domains\Financial\Http\Controllers\Webhooks;

use App\Domains\Financial\Models\PlaidItem;
use App\Domains\Financial\Services\PlaidService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * PlaidWebhookController
 *
 * Handles webhooks from Plaid for transaction updates, item errors,
 * and other events.
 */
class PlaidWebhookController extends Controller
{
    public function __construct(
        protected PlaidService $plaidService
    ) {}

    /**
     * Handle incoming Plaid webhooks.
     */
    public function handle(Request $request)
    {
        $webhookType = $request->input('webhook_type');
        $webhookCode = $request->input('webhook_code');
        $itemId = $request->input('item_id');

        Log::info('Plaid webhook received', [
            'type' => $webhookType,
            'code' => $webhookCode,
            'item_id' => $itemId,
        ]);

        // Verify webhook signature if configured
        // TODO: Implement webhook signature verification for production

        try {
            // Find the Plaid item
            $item = PlaidItem::where('plaid_item_id', $itemId)->first();

            if (!$item) {
                Log::warning('Plaid webhook for unknown item', [
                    'item_id' => $itemId,
                    'webhook_type' => $webhookType,
                ]);
                return response('Item not found', 404);
            }

            // Route to appropriate handler based on webhook type
            match($webhookType) {
                'TRANSACTIONS' => $this->handleTransactionsWebhook($item, $webhookCode, $request),
                'ITEM' => $this->handleItemWebhook($item, $webhookCode, $request),
                'AUTH' => $this->handleAuthWebhook($item, $webhookCode, $request),
                default => Log::info('Unhandled webhook type', ['type' => $webhookType]),
            };

            return response('Webhook processed', 200);
        } catch (\Exception $e) {
            Log::error('Plaid webhook processing error', [
                'error' => $e->getMessage(),
                'webhook_type' => $webhookType,
                'item_id' => $itemId,
            ]);

            return response('Webhook error', 500);
        }
    }

    /**
     * Handle TRANSACTIONS webhooks.
     */
    protected function handleTransactionsWebhook(PlaidItem $item, string $code, Request $request): void
    {
        match($code) {
            'INITIAL_UPDATE' => $this->handleInitialUpdate($item, $request),
            'HISTORICAL_UPDATE' => $this->handleHistoricalUpdate($item, $request),
            'DEFAULT_UPDATE' => $this->handleDefaultUpdate($item, $request),
            'TRANSACTIONS_REMOVED' => $this->handleTransactionsRemoved($item, $request),
            default => Log::info('Unhandled transaction webhook code', ['code' => $code]),
        };
    }

    /**
     * Handle ITEM webhooks.
     */
    protected function handleItemWebhook(PlaidItem $item, string $code, Request $request): void
    {
        match($code) {
            'ERROR' => $this->handleItemError($item, $request),
            'PENDING_EXPIRATION' => $this->handlePendingExpiration($item, $request),
            'USER_PERMISSION_REVOKED' => $this->handlePermissionRevoked($item, $request),
            'WEBHOOK_UPDATE_ACKNOWLEDGED' => Log::info('Webhook update acknowledged', ['item_id' => $item->id]),
            default => Log::info('Unhandled item webhook code', ['code' => $code]),
        };
    }

    /**
     * Handle AUTH webhooks.
     */
    protected function handleAuthWebhook(PlaidItem $item, string $code, Request $request): void
    {
        match($code) {
            'AUTOMATICALLY_VERIFIED' => $this->handleAutoVerified($item, $request),
            'VERIFICATION_EXPIRED' => $this->handleVerificationExpired($item, $request),
            default => Log::info('Unhandled auth webhook code', ['code' => $code]),
        };
    }

    /**
     * Handle initial update (first transaction data available).
     */
    protected function handleInitialUpdate(PlaidItem $item, Request $request): void
    {
        Log::info('Initial update received', ['item_id' => $item->id]);

        try {
            // Sync transactions
            $count = $this->plaidService->syncTransactions($item);
            
            Log::info('Initial transactions synced', [
                'item_id' => $item->id,
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync initial transactions', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle historical update (historical data complete).
     */
    protected function handleHistoricalUpdate(PlaidItem $item, Request $request): void
    {
        Log::info('Historical update received', ['item_id' => $item->id]);

        try {
            // Sync all historical transactions
            $count = $this->plaidService->syncTransactions($item);
            
            Log::info('Historical transactions synced', [
                'item_id' => $item->id,
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync historical transactions', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle default update (new transaction data available).
     */
    protected function handleDefaultUpdate(PlaidItem $item, Request $request): void
    {
        Log::info('Default update received', ['item_id' => $item->id]);

        try {
            // Sync new transactions
            $count = $this->plaidService->syncTransactions($item);
            
            // Update balances
            $this->plaidService->updateBalances($item);
            
            Log::info('Transactions and balances updated', [
                'item_id' => $item->id,
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync transactions', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle transactions removed.
     */
    protected function handleTransactionsRemoved(PlaidItem $item, Request $request): void
    {
        $removedTransactions = $request->input('removed_transactions', []);

        Log::info('Transactions removed', [
            'item_id' => $item->id,
            'count' => count($removedTransactions),
        ]);

        // Mark transactions as deleted in our database
        foreach ($removedTransactions as $transactionId) {
            $transaction = $item->bankTransactions()
                ->where('plaid_transaction_id', $transactionId)
                ->first();

            if ($transaction && !$transaction->is_reconciled) {
                $transaction->delete();
                
                Log::info('Transaction removed', [
                    'transaction_id' => $transaction->id,
                    'plaid_transaction_id' => $transactionId,
                ]);
            }
        }
    }

    /**
     * Handle item error.
     */
    protected function handleItemError(PlaidItem $item, Request $request): void
    {
        $error = $request->input('error', []);
        $errorCode = $error['error_code'] ?? 'UNKNOWN_ERROR';
        $errorMessage = $error['error_message'] ?? 'Unknown error occurred';

        Log::error('Plaid item error', [
            'item_id' => $item->id,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
        ]);

        $item->markAsError($errorCode, $errorMessage);

        // TODO: Send notification to user about the error
    }

    /**
     * Handle pending expiration.
     */
    protected function handlePendingExpiration(PlaidItem $item, Request $request): void
    {
        $consentExpirationTime = $request->input('consent_expiration_time');

        Log::warning('Item consent expiring soon', [
            'item_id' => $item->id,
            'expiration_time' => $consentExpirationTime,
        ]);

        $item->update([
            'consent_expiration_time' => $consentExpirationTime,
        ]);

        $item->markAsNeedingReauth();

        // TODO: Send notification to user about pending expiration
    }

    /**
     * Handle user permission revoked.
     */
    protected function handlePermissionRevoked(PlaidItem $item, Request $request): void
    {
        Log::warning('User permission revoked', ['item_id' => $item->id]);

        $item->update([
            'status' => 'inactive',
            'error_message' => 'User revoked permission at their bank',
        ]);

        // TODO: Send notification to user
    }

    /**
     * Handle auto-verified.
     */
    protected function handleAutoVerified(PlaidItem $item, Request $request): void
    {
        Log::info('Account automatically verified', ['item_id' => $item->id]);
        
        $item->markAsActive();
    }

    /**
     * Handle verification expired.
     */
    protected function handleVerificationExpired(PlaidItem $item, Request $request): void
    {
        Log::warning('Account verification expired', ['item_id' => $item->id]);
        
        $item->markAsNeedingReauth();

        // TODO: Send notification to user
    }
}
