<?php

namespace App\Livewire\Email;

use App\Domains\Email\Models\EmailAccount;
use App\Domains\Email\Services\OAuthTokenManager;
use App\Domains\Email\Services\UnifiedEmailSyncService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class EmailAccountsIndex extends Component
{
    // State properties
    public $showDeleteModal = false;

    public $accountToDelete = null;

    public $syncingAccountId = null;

    public $testingAccountId = null;

    // Services injected via boot()
    protected UnifiedEmailSyncService $syncService;

    protected OAuthTokenManager $tokenManager;

    /**
     * Boot - inject services
     */
    public function boot(
        UnifiedEmailSyncService $syncService,
        OAuthTokenManager $tokenManager
    ) {
        $this->syncService = $syncService;
        $this->tokenManager = $tokenManager;
    }

    /**
     * Get accounts list (computed property for reactivity)
     */
    #[Computed]
    public function accounts()
    {
        return EmailAccount::forUser(Auth::id())
            ->with('folders')
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Sync email account
     */
    public function syncAccount($accountId)
    {
        try {
            $this->syncingAccountId = $accountId;

            $account = EmailAccount::findOrFail($accountId);

            // Authorization check
            if ($account->user_id !== Auth::id()) {
                throw new \Exception('Unauthorized');
            }

            error_log("LIVEWIRE_DEBUG: Starting sync for account {$accountId}");

            // Perform sync
            $result = $this->syncService->syncAccount($account);

            error_log('LIVEWIRE_DEBUG: Sync completed with result: '.json_encode($result));

            // Success notification
            Flux::toast(
                heading: 'Sync Completed',
                text: ($result['folders_synced'] ?? 0).' folders and '.($result['messages_synced'] ?? 0).' messages synced',
                variant: 'success'
            );

            // Refresh accounts to show updated sync time
            unset($this->accounts);

        } catch (\Exception $e) {
            error_log('LIVEWIRE_DEBUG: Sync failed: '.$e->getMessage());

            Flux::toast(
                heading: 'Sync Failed',
                text: $e->getMessage(),
                variant: 'danger'
            );
        } finally {
            $this->syncingAccountId = null;
        }
    }

    /**
     * Test connection
     */
    public function testConnection($accountId)
    {
        try {
            $this->testingAccountId = $accountId;

            $account = EmailAccount::findOrFail($accountId);

            if ($account->user_id !== Auth::id()) {
                throw new \Exception('Unauthorized');
            }

            // Test based on connection type
            if ($account->connection_type === 'oauth') {
                // Check if tokens are valid
                $valid = $this->tokenManager->ensureValidTokens($account);
                if (! $valid) {
                    throw new \Exception('OAuth tokens are invalid or expired');
                }
                $message = 'OAuth connection is valid and tokens are fresh';
            } else {
                // For IMAP accounts, we'll implement a basic test later
                $message = 'IMAP/SMTP connection test - feature coming soon';
            }

            Flux::toast(
                heading: 'Connection Test Successful',
                text: $message,
                variant: 'success'
            );

        } catch (\Exception $e) {
            Flux::toast(
                heading: 'Connection Test Failed',
                text: $e->getMessage(),
                variant: 'danger'
            );
        } finally {
            $this->testingAccountId = null;
        }
    }

    /**
     * Set account as default
     */
    public function setDefault($accountId)
    {
        try {
            $account = EmailAccount::findOrFail($accountId);

            if ($account->user_id !== Auth::id()) {
                throw new \Exception('Unauthorized');
            }

            // Remove default from all other accounts
            EmailAccount::where('user_id', Auth::id())
                ->where('id', '!=', $accountId)
                ->update(['is_default' => false]);

            // Set this account as default
            $account->update(['is_default' => true]);

            Flux::toast(
                heading: 'Default Account Updated',
                text: "{$account->name} is now your default email account",
                variant: 'success'
            );

            // Refresh accounts list
            unset($this->accounts);

        } catch (\Exception $e) {
            Flux::toast(
                heading: 'Error',
                text: 'Failed to set default account: '.$e->getMessage(),
                variant: 'danger'
            );
        }
    }

    /**
     * Confirm account deletion
     */
    public function confirmDelete($accountId)
    {
        $this->accountToDelete = EmailAccount::find($accountId);
        $this->showDeleteModal = true;
    }

    /**
     * Delete account
     */
    public function deleteAccount()
    {
        try {
            if (! $this->accountToDelete) {
                throw new \Exception('No account selected for deletion');
            }

            if ($this->accountToDelete->user_id !== Auth::id()) {
                throw new \Exception('Unauthorized');
            }

            $name = $this->accountToDelete->name;
            $this->accountToDelete->delete();

            Flux::toast(
                heading: 'Account Deleted',
                text: "{$name} has been removed",
                variant: 'success'
            );

            $this->showDeleteModal = false;
            $this->accountToDelete = null;

            // Refresh accounts list
            unset($this->accounts);

        } catch (\Exception $e) {
            Flux::toast(
                heading: 'Deletion Failed',
                text: $e->getMessage(),
                variant: 'danger'
            );
        }
    }

    /**
     * Cancel deletion
     */
    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->accountToDelete = null;
    }

    public function render()
    {
        return view('livewire.email.email-accounts-index');
    }
}
