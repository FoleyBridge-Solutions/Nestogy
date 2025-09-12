<?php

namespace App\Http\Controllers\Email;

use App\Http\Controllers\Controller;
use App\Domains\Email\Models\EmailAccount;
use App\Domains\Email\Services\EmailProviderService;
use App\Domains\Email\Services\OAuthTokenManager;
use App\Domains\Email\Services\EmailProviderValidationService;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class EmailAccountController extends Controller
{
    protected EmailProviderService $providerService;
    protected OAuthTokenManager $tokenManager;
    protected EmailProviderValidationService $validationService;

    public function __construct(
        EmailProviderService $providerService,
        OAuthTokenManager $tokenManager,
        EmailProviderValidationService $validationService
    ) {
        $this->providerService = $providerService;
        $this->tokenManager = $tokenManager;
        $this->validationService = $validationService;
    }

    /**
     * Display a listing of email accounts
     */
    public function index()
    {
        $company = Auth::user()->company;

        $accounts = EmailAccount::where('company_id', $company->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $availableProviders = EmailProviderService::getAvailableProviders();

        return view('email.accounts.index', compact('accounts', 'availableProviders'));
    }

    /**
     * Show the form for creating a new email account
     */
    public function create()
    {
        $company = Auth::user()->company;
        $availableProviders = EmailProviderService::getAvailableProviders();

        // Check if company has OAuth provider configured
        $hasOAuthProvider = in_array($company->email_provider_type, ['microsoft365', 'google_workspace']);

        return view('email.accounts.create', compact('availableProviders', 'hasOAuthProvider'));
    }

    /**
     * Initiate OAuth connection for email account
     */
    public function connectOAuth(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $company = Auth::user()->company;
        $user = Auth::user();

        // Check if company has OAuth provider configured
        if (!in_array($company->email_provider_type, ['microsoft365', 'google_workspace'])) {
            return redirect()->back()->with('error', 'No OAuth provider configured for this company');
        }

        // Check if email domain is allowed
        if (!$this->validationService->validateEmailDomain($company, $request->email)) {
            return redirect()->back()->with('error', 'Email domain not allowed for this company\'s email provider');
        }

        // Check if account already exists
        $existingAccount = EmailAccount::where('company_id', $company->id)
            ->where('email_address', $request->email)
            ->first();

        if ($existingAccount) {
            return redirect()->back()->with('error', 'An account with this email address already exists');
        }

        try {
            // Generate state for CSRF protection
            $state = Str::random(32);

            // Store OAuth context in session
            Session::put('oauth_state', $state);
            Session::put('oauth_context', [
                'company_id' => $company->id,
                'user_id' => $user->id,
                'email' => $request->email,
            ]);

            // Get authorization URL
            $authUrl = $this->providerService->getAuthorizationUrl($company, $state);

            return redirect($authUrl);

        } catch (\Exception $e) {
            \Log::error('Failed to initiate OAuth connection', [
                'error' => $e->getMessage(),
                'company_id' => $company->id,
                'user_id' => $user->id,
            ]);

            return redirect()->back()->with('error', 'Failed to initiate OAuth connection: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created email account (manual configuration)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email_address' => 'required|email',
            'provider' => 'required|string',
            'imap_host' => 'required_if:provider,manual|string',
            'imap_port' => 'required_if:provider,manual|integer|min:1|max:65535',
            'imap_encryption' => 'required_if:provider,manual|string|in:ssl,tls,none',
            'imap_username' => 'required_if:provider,manual|string',
            'imap_password' => 'required_if:provider,manual|string',
            'smtp_host' => 'required_if:provider,manual|string',
            'smtp_port' => 'required_if:provider,manual|integer|min:1|max:65535',
            'smtp_encryption' => 'required_if:provider,manual|string|in:ssl,tls,none',
            'smtp_username' => 'required_if:provider,manual|string',
            'smtp_password' => 'required_if:provider,manual|string',
            'is_default' => 'boolean',
            'sync_interval_minutes' => 'integer|min:1|max:1440',
            'auto_create_tickets' => 'boolean',
            'auto_log_communications' => 'boolean',
        ]);

        $company = Auth::user()->company;
        $user = Auth::user();

        // Check if account already exists
        $existingAccount = EmailAccount::where('company_id', $company->id)
            ->where('email_address', $request->email_address)
            ->first();

        if ($existingAccount) {
            return redirect()->back()->with('error', 'An account with this email address already exists');
        }

        // Validate account configuration
        $validationErrors = $this->validationService->validateEmailAccountConfig($request->all(), $company);
        if (!empty($validationErrors)) {
            return redirect()->back()
                ->with('error', 'Validation failed: ' . implode(', ', $validationErrors))
                ->withInput();
        }

        $account = EmailAccount::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'name' => $request->name,
            'email_address' => $request->email_address,
            'provider' => $request->provider,
            'connection_type' => 'manual',
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
            'is_default' => $request->boolean('is_default'),
            'is_active' => true,
            'sync_interval_minutes' => $request->sync_interval_minutes ?? 5,
            'auto_create_tickets' => $request->boolean('auto_create_tickets'),
            'auto_log_communications' => $request->boolean('auto_log_communications', true),
        ]);

        return redirect()->route('email.accounts.index')
            ->with('success', 'Email account created successfully!');
    }

    /**
     * Display the specified email account
     */
    public function show(EmailAccount $account)
    {
        $this->authorize('view', $account);

        $tokenInfo = $this->tokenManager->getTokenExpiryInfo($account);

        return view('email.accounts.show', compact('account', 'tokenInfo'));
    }

    /**
     * Show the form for editing the specified email account
     */
    public function edit(EmailAccount $account)
    {
        $this->authorize('update', $account);

        return view('email.accounts.edit', compact('account'));
    }

    /**
     * Update the specified email account
     */
    public function update(Request $request, EmailAccount $account)
    {
        $this->authorize('update', $account);

        $request->validate([
            'name' => 'required|string|max:255',
            'is_default' => 'boolean',
            'sync_interval_minutes' => 'integer|min:1|max:1440',
            'auto_create_tickets' => 'boolean',
            'auto_log_communications' => 'boolean',
        ]);

        $account->update([
            'name' => $request->name,
            'is_default' => $request->boolean('is_default'),
            'sync_interval_minutes' => $request->sync_interval_minutes,
            'auto_create_tickets' => $request->boolean('auto_create_tickets'),
            'auto_log_communications' => $request->boolean('auto_log_communications'),
        ]);

        return redirect()->route('email.accounts.index')
            ->with('success', 'Email account updated successfully!');
    }

    /**
     * Remove the specified email account
     */
    public function destroy(EmailAccount $account)
    {
        $this->authorize('delete', $account);

        // Revoke OAuth tokens if applicable
        if ($account->connection_type === 'oauth') {
            $this->tokenManager->revokeTokens($account);
        }

        $account->delete();

        return redirect()->route('email.accounts.index')
            ->with('success', 'Email account deleted successfully!');
    }

    /**
     * Refresh OAuth tokens for an account
     */
    public function refreshTokens(EmailAccount $account)
    {
        $this->authorize('update', $account);

        if ($account->connection_type !== 'oauth') {
            return redirect()->back()->with('error', 'This account does not use OAuth');
        }

        $success = $this->tokenManager->refreshTokens($account);

        if ($success) {
            return redirect()->back()->with('success', 'OAuth tokens refreshed successfully!');
        } else {
            return redirect()->back()->with('error', 'Failed to refresh OAuth tokens');
        }
    }


}