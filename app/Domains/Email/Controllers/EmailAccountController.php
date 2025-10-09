<?php

namespace App\Domains\Email\Controllers;

use App\Domains\Email\Models\EmailAccount;
use App\Domains\Email\Services\ImapService;
use App\Domains\Email\Services\UnifiedEmailSyncService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmailAccountController extends Controller
{
    private const ENCRYPTION_VALIDATION_RULE = 'required|in:ssl,tls,none';

    public function __construct(
        private ImapService $imapService,
        private UnifiedEmailSyncService $unifiedSyncService
    ) {}

    public function index()
    {
        return view('email.accounts.index');
    }

    public function create()
    {
        $providers = [
            'gmail' => [
                'name' => 'Gmail',
                'imap_host' => 'imap.gmail.com',
                'imap_port' => 993,
                'imap_encryption' => 'ssl',
                'smtp_host' => 'smtp.gmail.com',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
            ],
            'outlook' => [
                'name' => 'Outlook/Hotmail',
                'imap_host' => 'outlook.office365.com',
                'imap_port' => 993,
                'imap_encryption' => 'ssl',
                'smtp_host' => 'smtp-mail.outlook.com',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
            ],
            'yahoo' => [
                'name' => 'Yahoo Mail',
                'imap_host' => 'imap.mail.yahoo.com',
                'imap_port' => 993,
                'imap_encryption' => 'ssl',
                'smtp_host' => 'smtp.mail.yahoo.com',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
            ],
            'manual' => [
                'name' => 'Manual Configuration',
                'imap_host' => '',
                'imap_port' => 993,
                'imap_encryption' => 'ssl',
                'smtp_host' => '',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
            ],
        ];

        return view('email.accounts.create', compact('providers'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email_address' => [
                'required',
                'email',
                Rule::unique('email_accounts')->where(function ($query) {
                    return $query->where('user_id', Auth::id());
                }),
            ],
            'provider' => 'required|in:gmail,outlook,yahoo,manual',
            'imap_host' => 'required|string|max:255',
            'imap_port' => 'required|integer|min:1|max:65535',
            'imap_encryption' => self::ENCRYPTION_VALIDATION_RULE,
            'imap_username' => 'required|string|max:255',
            'imap_password' => 'required|string',
            'smtp_host' => 'required|string|max:255',
            'smtp_port' => 'required|integer|min:1|max:65535',
            'smtp_encryption' => self::ENCRYPTION_VALIDATION_RULE,
            'smtp_username' => 'required|string|max:255',
            'smtp_password' => 'required|string',
            'is_default' => 'boolean',
            'sync_interval_minutes' => 'integer|min:1|max:1440',
            'auto_create_tickets' => 'boolean',
            'auto_log_communications' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // If this is the user's first account, make it default
        $isFirstAccount = ! EmailAccount::forUser(Auth::id())->exists();

        $account = EmailAccount::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'email_address' => $request->email_address,
            'provider' => $request->provider,
            'imap_host' => $request->imap_host,
            'imap_port' => $request->imap_port,
            'imap_encryption' => $request->imap_encryption,
            'imap_username' => $request->imap_username,
            'imap_password' => $request->imap_password,
            'imap_validate_cert' => $request->boolean('imap_validate_cert', true),
            'smtp_host' => $request->smtp_host,
            'smtp_port' => $request->smtp_port,
            'smtp_encryption' => $request->smtp_encryption,
            'smtp_username' => $request->smtp_username,
            'smtp_password' => $request->smtp_password,
            'is_default' => $request->boolean('is_default') || $isFirstAccount,
            'sync_interval_minutes' => $request->sync_interval_minutes ?? 5,
            'auto_create_tickets' => $request->boolean('auto_create_tickets'),
            'auto_log_communications' => $request->boolean('auto_log_communications', true),
        ]);

        // Test connection
        $testResult = $this->imapService->testConnection($account);

        if (! $testResult['success']) {
            $account->update(['sync_error' => $testResult['message']]);

            return back()
                ->withErrors(['connection' => 'Connection test failed: '.$testResult['message']])
                ->withInput();
        }

        // Initial sync of folders
        try {
            $this->imapService->syncAccount($account);
            $successMessage = "Email account added successfully! Found {$testResult['folder_count']} folders.";
        } catch (\Exception $e) {
            $successMessage = 'Email account added successfully, but initial sync failed. You can retry sync from the account list.';
        }

        return redirect()
            ->route('email.accounts.index')
            ->with('success', $successMessage);
    }

    public function show($id)
    {
        $emailAccount = EmailAccount::where('id', $id)
            ->where('company_id', auth()->user()->company_id)
            ->first();

        if (! $emailAccount) {
            abort(404, 'Email account not found');
        }

        $this->authorize('view', $emailAccount);

        $emailAccount->load(['folders', 'signatures']);

        $stats = [
            'total_messages' => $emailAccount->messages()->count(),
            'unread_messages' => $emailAccount->messages()->unread()->count(),
            'folders_count' => $emailAccount->folders()->count(),
            'last_synced' => $emailAccount->last_synced_at?->diffForHumans(),
        ];

        return view('email.accounts.show', compact('emailAccount', 'stats'));
    }

    public function edit($id)
    {
        // Manually resolve the email account to bypass route model binding issues
        $emailAccount = EmailAccount::where('id', $id)
            ->where('company_id', auth()->user()->company_id)
            ->first();

        if (! $emailAccount) {
            abort(404, 'Email account not found');
        }

        $this->authorize('update', $emailAccount);

        $providers = [
            'gmail' => [
                'name' => 'Gmail',
                'imap_host' => 'imap.gmail.com',
                'imap_port' => 993,
                'imap_encryption' => 'ssl',
                'smtp_host' => 'smtp.gmail.com',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
            ],
            'outlook' => [
                'name' => 'Outlook/Hotmail',
                'imap_host' => 'outlook.office365.com',
                'imap_port' => 993,
                'imap_encryption' => 'ssl',
                'smtp_host' => 'smtp-mail.outlook.com',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
            ],
            'yahoo' => [
                'name' => 'Yahoo Mail',
                'imap_host' => 'imap.mail.yahoo.com',
                'imap_port' => 993,
                'imap_encryption' => 'ssl',
                'smtp_host' => 'smtp.mail.yahoo.com',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
            ],
            'custom' => [
                'name' => 'Custom',
                'imap_host' => '',
                'imap_port' => 993,
                'imap_encryption' => 'ssl',
                'smtp_host' => '',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
            ],
        ];

        return view('email.accounts.edit', compact('emailAccount', 'providers'));
    }

    public function update(Request $request, $id)
    {
        $emailAccount = EmailAccount::where('id', $id)
            ->where('company_id', auth()->user()->company_id)
            ->first();

        if (! $emailAccount) {
            abort(404, 'Email account not found');
        }

        $this->authorize('update', $emailAccount);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email_address' => [
                'required',
                'email',
                Rule::unique('email_accounts')->where(function ($query) use ($emailAccount) {
                    return $query->where('user_id', Auth::id())
                        ->where('id', '!=', $emailAccount->id);
                }),
            ],
            'imap_host' => 'required|string|max:255',
            'imap_port' => 'required|integer|min:1|max:65535',
            'imap_encryption' => self::ENCRYPTION_VALIDATION_RULE,
            'imap_username' => 'required|string|max:255',
            'imap_password' => 'nullable|string',
            'smtp_host' => 'required|string|max:255',
            'smtp_port' => 'required|integer|min:1|max:65535',
            'smtp_encryption' => self::ENCRYPTION_VALIDATION_RULE,
            'smtp_username' => 'required|string|max:255',
            'smtp_password' => 'nullable|string',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sync_interval_minutes' => 'integer|min:1|max:1440',
            'auto_create_tickets' => 'boolean',
            'auto_log_communications' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $updateData = [
            'name' => $request->name,
            'email_address' => $request->email_address,
            'imap_host' => $request->imap_host,
            'imap_port' => $request->imap_port,
            'imap_encryption' => $request->imap_encryption,
            'imap_username' => $request->imap_username,
            'imap_validate_cert' => $request->boolean('imap_validate_cert', true),
            'smtp_host' => $request->smtp_host,
            'smtp_port' => $request->smtp_port,
            'smtp_encryption' => $request->smtp_encryption,
            'smtp_username' => $request->smtp_username,
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active', true),
            'sync_interval_minutes' => $request->sync_interval_minutes ?? 5,
            'auto_create_tickets' => $request->boolean('auto_create_tickets'),
            'auto_log_communications' => $request->boolean('auto_log_communications', true),
        ];

        // Only update passwords if provided
        if ($request->filled('imap_password')) {
            $updateData['imap_password'] = $request->imap_password;
        }
        if ($request->filled('smtp_password')) {
            $updateData['smtp_password'] = $request->smtp_password;
        }

        $emailAccount->update($updateData);

        // Test connection if credentials changed
        if ($request->filled(['imap_password', 'smtp_password', 'imap_host', 'imap_port'])) {
            $testResult = $this->imapService->testConnection($emailAccount);

            if (! $testResult['success']) {
                $emailAccount->update(['sync_error' => $testResult['message']]);

                return back()
                    ->with('warning', 'Account updated but connection test failed: '.$testResult['message'])
                    ->withInput();
            } else {
                $emailAccount->update(['sync_error' => null]);
            }
        }

        return redirect()
            ->route('email.accounts.show', $emailAccount)
            ->with('success', 'Email account updated successfully.');
    }

    public function destroy($id)
    {
        $emailAccount = EmailAccount::where('id', $id)
            ->where('company_id', auth()->user()->company_id)
            ->first();

        if (! $emailAccount) {
            abort(404, 'Email account not found');
        }

        $this->authorize('delete', $emailAccount);

        $accountName = $emailAccount->name;
        $emailAccount->delete();

        return redirect()
            ->route('email.accounts.index')
            ->with('success', "Email account '{$accountName}' deleted successfully.");
    }

    public function testConnection(EmailAccount $emailAccount)
    {
        $this->authorize('update', $emailAccount);

        $result = $this->imapService->testConnection($emailAccount);

        if ($result['success']) {
            $emailAccount->update(['sync_error' => null]);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'folder_count' => $result['folder_count'],
            ]);
        } else {
            $emailAccount->update(['sync_error' => $result['message']]);

            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }
    }

    public function sync(EmailAccount $emailAccount)
    {
        error_log("OAUTH_DEBUG: EmailAccountController::sync() called for account {$emailAccount->id}");
        error_log("OAUTH_DEBUG: Account email: {$emailAccount->email_address}, connection_type: {$emailAccount->connection_type}");

        $this->authorize('update', $emailAccount);

        error_log('OAUTH_DEBUG: Authorization passed, about to sync');

        try {
            \Log::info('Starting email sync', [
                'account_id' => $emailAccount->id,
                'account_email' => $emailAccount->email_address,
                'connection_type' => $emailAccount->connection_type,
                'provider' => $emailAccount->provider,
            ]);

            // Use UnifiedEmailSyncService to handle both OAuth and IMAP accounts
            $result = $this->unifiedSyncService->syncAccount($emailAccount);

            \Log::info('Email sync completed', [
                'account_id' => $emailAccount->id,
                'result' => $result,
            ]);

            return response()->json([
                'success' => $result['success'] ?? true,
                'message' => 'Sync completed! '.($result['folders_synced'] ?? 0).' folders and '.($result['messages_synced'] ?? 0).' messages synced.',
                'folders_synced' => $result['folders_synced'] ?? 0,
                'messages_synced' => $result['messages_synced'] ?? 0,
                'errors' => $result['errors'] ?? [],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: '.$e->getMessage(),
            ], 500);
        }
    }

    public function setDefault(EmailAccount $emailAccount)
    {
        $this->authorize('update', $emailAccount);

        // Remove default flag from other accounts
        EmailAccount::forUser(Auth::id())
            ->where('id', '!=', $emailAccount->id)
            ->update(['is_default' => false]);

        // Set this account as default
        $emailAccount->update(['is_default' => true]);

        return response()->json([
            'success' => true,
            'message' => "'{$emailAccount->name}' set as default account.",
        ]);
    }
}
