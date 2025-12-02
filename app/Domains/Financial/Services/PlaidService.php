<?php

namespace App\Domains\Financial\Services;

use App\Domains\Company\Models\Account;
use App\Domains\Financial\Models\BankTransaction;
use App\Domains\Financial\Models\PlaidItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PlaidService
 *
 * Handles all interactions with Plaid API for bank account syncing.
 * Uses Guzzle/HTTP client instead of SDK for better control.
 */
class PlaidService
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $secret;
    protected string $environment;

    public function __construct()
    {
        $this->environment = config('integrations.plaid.environment', 'sandbox');
        $this->clientId = config('integrations.plaid.client_id') ?? '';
        $this->secret = config('integrations.plaid.secret') ?? '';
        
        $this->baseUrl = $this->getBaseUrl();
    }

    /**
     * Get base URL for Plaid API based on environment.
     */
    protected function getBaseUrl(): string
    {
        return match($this->environment) {
            'production' => 'https://production.plaid.com',
            'development' => 'https://development.plaid.com',
            default => 'https://sandbox.plaid.com',
        };
    }

    /**
     * Create a link token for Plaid Link initialization.
     */
    public function createLinkToken(int $companyId, ?string $redirectUri = null): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/link/token/create", [
                'client_id' => $this->clientId,
                'secret' => $this->secret,
                'client_name' => config('integrations.plaid.client_name', 'Nestogy ERP'),
                'user' => [
                    'client_user_id' => (string) $companyId,
                ],
                'products' => config('integrations.plaid.products', ['transactions']),
                'country_codes' => config('integrations.plaid.country_codes', ['US']),
                'language' => config('integrations.plaid.language', 'en'),
                'webhook' => config('integrations.plaid.webhook_url'),
                'redirect_uri' => $redirectUri ?? config('integrations.plaid.redirect_uri'),
            ]);

            if (!$response->successful()) {
                Log::error('Plaid link token creation failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
                throw new \Exception('Failed to create Plaid link token');
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Plaid link token error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Exchange public token for access token after Plaid Link success.
     */
    public function exchangePublicToken(string $publicToken): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/item/public_token/exchange", [
                'client_id' => $this->clientId,
                'secret' => $this->secret,
                'public_token' => $publicToken,
            ]);

            if (!$response->successful()) {
                Log::error('Plaid public token exchange failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
                throw new \Exception('Failed to exchange public token');
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Plaid public token exchange error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get accounts for a Plaid item.
     */
    public function getAccounts(PlaidItem $item): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/accounts/get", [
                'client_id' => $this->clientId,
                'secret' => $this->secret,
                'access_token' => $item->plaid_access_token,
            ]);

            if (!$response->successful()) {
                $this->handlePlaidError($item, $response->json());
                throw new \Exception('Failed to get accounts from Plaid');
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Plaid get accounts error', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sync accounts from Plaid to local database.
     */
    public function syncAccounts(PlaidItem $item): int
    {
        try {
            $data = $this->getAccounts($item);
            $synced = 0;

            foreach ($data['accounts'] as $plaidAccount) {
                $account = Account::updateOrCreate(
                    [
                        'company_id' => $item->company_id,
                        'plaid_account_id' => $plaidAccount['account_id'],
                    ],
                    [
                        'plaid_item_id' => $item->id,
                        'plaid_id' => $plaidAccount['account_id'],
                        'plaid_name' => $plaidAccount['name'],
                        'plaid_official_name' => $plaidAccount['official_name'] ?? null,
                        'plaid_mask' => $plaidAccount['mask'] ?? null,
                        'plaid_subtype' => $plaidAccount['subtype'] ?? null,
                        'name' => $plaidAccount['official_name'] ?? $plaidAccount['name'],
                        'type' => $this->mapAccountType($plaidAccount['subtype'] ?? $plaidAccount['type']),
                        'currency_code' => $plaidAccount['balances']['iso_currency_code'] ?? 'USD',
                        'available_balance' => $plaidAccount['balances']['available'] ?? null,
                        'current_balance' => $plaidAccount['balances']['current'] ?? null,
                        'limit_balance' => $plaidAccount['balances']['limit'] ?? null,
                        'last_synced_at' => now(),
                    ]
                );
                
                $synced++;
            }

            $item->markAsActive();
            $item->markAsSynced();

            Log::info('Accounts synced from Plaid', [
                'item_id' => $item->id,
                'count' => $synced,
            ]);

            return $synced;
        } catch (\Exception $e) {
            Log::error('Plaid sync accounts error', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get transactions for a Plaid item.
     */
    public function getTransactions(PlaidItem $item, Carbon $startDate, Carbon $endDate, array $options = []): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/transactions/get", [
                'client_id' => $this->clientId,
                'secret' => $this->secret,
                'access_token' => $item->plaid_access_token,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'options' => array_merge([
                    'include_personal_finance_category' => true,
                ], $options),
            ]);

            if (!$response->successful()) {
                $this->handlePlaidError($item, $response->json());
                throw new \Exception('Failed to get transactions from Plaid');
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Plaid get transactions error', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sync transactions from Plaid to local database.
     */
    public function syncTransactions(PlaidItem $item, ?Carbon $startDate = null): int
    {
        try {
            $startDate = $startDate ?? now()->subDays(30);
            $endDate = now();
            
            $data = $this->getTransactions($item, $startDate, $endDate);
            $synced = 0;

            foreach ($data['transactions'] as $plaidTransaction) {
                // Find the matching local account
                $account = Account::where('company_id', $item->company_id)
                    ->where('plaid_account_id', $plaidTransaction['account_id'])
                    ->first();

                if (!$account) {
                    Log::warning('Account not found for transaction', [
                        'plaid_account_id' => $plaidTransaction['account_id'],
                        'transaction_id' => $plaidTransaction['transaction_id'],
                    ]);
                    continue;
                }

                BankTransaction::updateOrCreate(
                    [
                        'plaid_transaction_id' => $plaidTransaction['transaction_id'],
                    ],
                    [
                        'company_id' => $item->company_id,
                        'account_id' => $account->id,
                        'plaid_item_id' => $item->id,
                        'plaid_account_id' => $plaidTransaction['account_id'],
                        'amount' => $plaidTransaction['amount'],
                        'date' => $plaidTransaction['date'],
                        'authorized_date' => $plaidTransaction['authorized_date'] ?? null,
                        'name' => $plaidTransaction['name'],
                        'merchant_name' => $plaidTransaction['merchant_name'] ?? null,
                        'category' => $plaidTransaction['category'] ?? [],
                        'pending' => $plaidTransaction['pending'] ?? false,
                        'payment_channel' => $plaidTransaction['payment_channel'] ?? null,
                        'transaction_type' => $plaidTransaction['transaction_type'] ?? null,
                        'location' => $plaidTransaction['location'] ?? [],
                        'payment_meta' => $plaidTransaction['payment_meta'] ?? [],
                        'metadata' => $plaidTransaction,
                    ]
                );
                
                $synced++;
            }

            $item->markAsSynced();

            Log::info('Transactions synced from Plaid', [
                'item_id' => $item->id,
                'count' => $synced,
            ]);

            return $synced;
        } catch (\Exception $e) {
            Log::error('Plaid sync transactions error', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get account balances.
     */
    public function getAccountBalances(PlaidItem $item): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/accounts/balance/get", [
                'client_id' => $this->clientId,
                'secret' => $this->secret,
                'access_token' => $item->plaid_access_token,
            ]);

            if (!$response->successful()) {
                $this->handlePlaidError($item, $response->json());
                throw new \Exception('Failed to get balances from Plaid');
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Plaid get balances error', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update balances for all accounts.
     */
    public function updateBalances(PlaidItem $item): int
    {
        try {
            $data = $this->getAccountBalances($item);
            $updated = 0;

            foreach ($data['accounts'] as $plaidAccount) {
                $account = Account::where('company_id', $item->company_id)
                    ->where('plaid_account_id', $plaidAccount['account_id'])
                    ->first();

                if ($account) {
                    $account->update([
                        'available_balance' => $plaidAccount['balances']['available'] ?? null,
                        'current_balance' => $plaidAccount['balances']['current'] ?? null,
                        'limit_balance' => $plaidAccount['balances']['limit'] ?? null,
                        'last_synced_at' => now(),
                    ]);
                    $updated++;
                }
            }

            Log::info('Balances updated from Plaid', [
                'item_id' => $item->id,
                'count' => $updated,
            ]);

            return $updated;
        } catch (\Exception $e) {
            Log::error('Plaid update balances error', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get item information.
     */
    public function getItem(PlaidItem $item): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/item/get", [
                'client_id' => $this->clientId,
                'secret' => $this->secret,
                'access_token' => $item->plaid_access_token,
            ]);

            if (!$response->successful()) {
                $this->handlePlaidError($item, $response->json());
                throw new \Exception('Failed to get item from Plaid');
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Plaid get item error', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update webhook URL for an item.
     */
    public function updateWebhook(PlaidItem $item, string $webhookUrl): bool
    {
        try {
            $response = Http::post("{$this->baseUrl}/item/webhook/update", [
                'client_id' => $this->clientId,
                'secret' => $this->secret,
                'access_token' => $item->plaid_access_token,
                'webhook' => $webhookUrl,
            ]);

            if (!$response->successful()) {
                $this->handlePlaidError($item, $response->json());
                return false;
            }

            $item->update(['webhook_url' => $webhookUrl]);
            return true;
        } catch (\Exception $e) {
            Log::error('Plaid update webhook error', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Remove/delete a Plaid item.
     */
    public function removeItem(PlaidItem $item): bool
    {
        try {
            $response = Http::post("{$this->baseUrl}/item/remove", [
                'client_id' => $this->clientId,
                'secret' => $this->secret,
                'access_token' => $item->plaid_access_token,
            ]);

            if (!$response->successful()) {
                Log::warning('Plaid item removal failed', [
                    'item_id' => $item->id,
                    'response' => $response->json(),
                ]);
                // Continue with local deletion even if Plaid fails
            }

            $item->delete();
            return true;
        } catch (\Exception $e) {
            Log::error('Plaid remove item error', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get institution details.
     */
    public function getInstitution(string $institutionId): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/institutions/get_by_id", [
                'client_id' => $this->clientId,
                'secret' => $this->secret,
                'institution_id' => $institutionId,
                'country_codes' => config('integrations.plaid.country_codes', ['US']),
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to get institution from Plaid');
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Plaid get institution error', [
                'institution_id' => $institutionId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Search institutions.
     */
    public function searchInstitutions(string $query): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/institutions/search", [
                'client_id' => $this->clientId,
                'secret' => $this->secret,
                'query' => $query,
                'country_codes' => config('integrations.plaid.country_codes', ['US']),
                'products' => config('integrations.plaid.products', ['transactions']),
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to search institutions');
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Plaid search institutions error', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle Plaid API errors.
     */
    protected function handlePlaidError(PlaidItem $item, array $errorData): void
    {
        $errorCode = $errorData['error_code'] ?? 'UNKNOWN_ERROR';
        $errorMessage = $errorData['error_message'] ?? 'Unknown error occurred';

        // Check if error requires reauthorization
        $reauthErrors = ['ITEM_LOGIN_REQUIRED', 'PENDING_EXPIRATION'];
        
        if (in_array($errorCode, $reauthErrors)) {
            $item->markAsNeedingReauth();
        } else {
            $item->markAsError($errorCode, $errorMessage);
        }

        Log::error('Plaid API error', [
            'item_id' => $item->id,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Map Plaid account type to local account type.
     */
    protected function mapAccountType(string $plaidType): int
    {
        return match(strtolower($plaidType)) {
            'checking' => Account::TYPE_CHECKING,
            'savings' => Account::TYPE_SAVINGS,
            'credit card', 'credit' => Account::TYPE_CREDIT_CARD,
            'investment', 'brokerage' => Account::TYPE_INVESTMENT,
            'loan', 'mortgage' => Account::TYPE_LOAN,
            default => Account::TYPE_OTHER,
        };
    }

    /**
     * Sync all active items.
     */
    public function syncAllActiveItems(): array
    {
        $items = PlaidItem::active()->get();
        $results = [];

        foreach ($items as $item) {
            try {
                $transactionCount = $this->syncTransactions($item);
                $this->updateBalances($item);
                
                $results[] = [
                    'item_id' => $item->id,
                    'success' => true,
                    'transaction_count' => $transactionCount,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'item_id' => $item->id,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
