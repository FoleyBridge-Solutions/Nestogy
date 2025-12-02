<?php

namespace App\Domains\Financial\Controllers;

use App\Domains\Financial\Models\PlaidItem;
use App\Domains\Financial\Services\PlaidService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BankConnectionController extends Controller
{
    public function __construct(
        protected PlaidService $plaidService
    ) {}

    /**
     * Display list of bank connections.
     */
    public function index(): View
    {
        $items = PlaidItem::where('company_id', Auth::user()->company_id)
            ->with('accounts')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('financial.bank-connections.index', compact('items'));
    }

    /**
     * Initiate new bank connection (create link token).
     */
    public function create(Request $request)
    {
        try {
            $linkTokenData = $this->plaidService->createLinkToken(
                Auth::user()->company_id,
                route('financial.bank-connections.store')
            );

            return response()->json([
                'link_token' => $linkTokenData['link_token'],
                'expiration' => $linkTokenData['expiration'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create Plaid link token', [
                'error' => $e->getMessage(),
                'company_id' => Auth::user()->company_id,
            ]);

            return response()->json([
                'error' => 'Failed to initialize bank connection',
            ], 500);
        }
    }

    /**
     * Complete bank connection (exchange public token).
     */
    public function store(Request $request)
    {
        $request->validate([
            'public_token' => 'required|string',
            'institution_id' => 'required|string',
            'institution_name' => 'required|string',
        ]);

        try {
            // Exchange public token for access token
            $exchangeData = $this->plaidService->exchangePublicToken($request->public_token);

            // Create Plaid item
            $item = PlaidItem::create([
                'company_id' => Auth::user()->company_id,
                'plaid_item_id' => $exchangeData['item_id'],
                'plaid_access_token' => $exchangeData['access_token'],
                'institution_id' => $request->institution_id,
                'institution_name' => $request->institution_name,
                'status' => 'active',
                'webhook_url' => config('integrations.plaid.webhook_url'),
            ]);

            // Sync accounts and initial transactions
            $this->plaidService->syncAccounts($item);
            $this->plaidService->syncTransactions($item);

            Log::info('Bank connection created', [
                'item_id' => $item->id,
                'institution_name' => $item->institution_name,
                'company_id' => $item->company_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bank connected successfully',
                'item_id' => $item->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create bank connection', [
                'error' => $e->getMessage(),
                'company_id' => Auth::user()->company_id,
            ]);

            return response()->json([
                'error' => 'Failed to connect bank account',
            ], 500);
        }
    }

    /**
     * Display connection details.
     */
    public function show(PlaidItem $item): View
    {
        $this->authorize('view', $item);

        $item->load(['accounts', 'bankTransactions' => function ($query) {
            $query->orderBy('date', 'desc')->limit(100);
        }]);

        $unreconciledCount = $item->getUnreconciledTransactionCount();
        $lastSynced = $item->last_synced_at;

        return view('financial.bank-connections.show', compact('item', 'unreconciledCount', 'lastSynced'));
    }

    /**
     * Manually sync a connection.
     */
    public function sync(PlaidItem $item)
    {
        $this->authorize('update', $item);

        try {
            $transactionCount = $this->plaidService->syncTransactions($item);
            $this->plaidService->updateBalances($item);

            Log::info('Bank connection manually synced', [
                'item_id' => $item->id,
                'transaction_count' => $transactionCount,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Synced {$transactionCount} transactions",
                'transaction_count' => $transactionCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync bank connection', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to sync connection',
            ], 500);
        }
    }

    /**
     * Remove bank connection.
     */
    public function destroy(PlaidItem $item)
    {
        $this->authorize('delete', $item);

        try {
            $this->plaidService->removeItem($item);

            Log::info('Bank connection removed', [
                'item_id' => $item->id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('financial.bank-connections.index')
                ->with('success', 'Bank connection removed successfully');
        } catch (\Exception $e) {
            Log::error('Failed to remove bank connection', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to remove connection');
        }
    }

    /**
     * Initiate reauthorization flow.
     */
    public function reauthorize(PlaidItem $item)
    {
        $this->authorize('update', $item);

        try {
            // Create update mode link token
            $linkTokenData = $this->plaidService->createLinkToken(
                Auth::user()->company_id,
                route('financial.bank-connections.store')
            );

            return response()->json([
                'link_token' => $linkTokenData['link_token'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create reauth link token', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to initialize reauthorization',
            ], 500);
        }
    }
}
