<?php

namespace App\Http\Controllers\Email;

use App\Http\Controllers\Controller;
use App\Domains\Email\Services\EmailProviderService;
use App\Domains\Email\Services\OAuthTokenManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class OAuthCallbackController extends Controller
{
    protected EmailProviderService $providerService;
    protected OAuthTokenManager $tokenManager;

    public function __construct(
        EmailProviderService $providerService,
        OAuthTokenManager $tokenManager
    ) {
        $this->providerService = $providerService;
        $this->tokenManager = $tokenManager;
    }

    /**
     * Handle OAuth callback
     */
    public function callback(Request $request)
    {
        try {
            // Validate required parameters
            $request->validate([
                'code' => 'required|string',
                'state' => 'required|string',
            ]);

            // Verify state to prevent CSRF attacks
            $sessionState = Session::get('oauth_state');
            if (!$sessionState || $sessionState !== $request->state) {
                Log::warning('OAuth state mismatch', [
                    'session_state' => $sessionState,
                    'request_state' => $request->state,
                ]);
                return redirect()->route('email.accounts.index')
                    ->with('error', 'OAuth authentication failed: Invalid state parameter');
            }

            // Get OAuth context from session
            $oauthContext = Session::get('oauth_context');
            if (!$oauthContext) {
                return redirect()->route('email.accounts.index')
                    ->with('error', 'OAuth authentication failed: Missing context');
            }

            // Clear session data
            Session::forget(['oauth_state', 'oauth_context']);

            $companyId = $oauthContext['company_id'];
            $userId = $oauthContext['user_id'];

            // Get company
            $company = \App\Models\Company::find($companyId);
            if (!$company) {
                return redirect()->route('email.accounts.index')
                    ->with('error', 'OAuth authentication failed: Company not found');
            }

            // Exchange code for tokens
            $tokens = $this->providerService->exchangeCodeForTokens($company, $request->code);

            // Get user email from tokens (if available)
            $email = $oauthContext['email'] ?? $this->getEmailFromTokens($company, $tokens);

            if (!$email) {
                return redirect()->route('email.accounts.index')
                    ->with('error', 'OAuth authentication failed: Could not determine email address');
            }

            // Create email account
            $account = $this->providerService->createAccountFromOAuth(
                $company,
                $tokens,
                $email,
                $userId
            );

            Log::info('OAuth email account created successfully', [
                'account_id' => $account->id,
                'company_id' => $company->id,
                'user_id' => $userId,
                'email' => $email,
                'provider' => $company->email_provider_type,
                'account_user_id' => $account->user_id,
                'tokens_received' => !empty($tokens),
            ]);

            return redirect()->route('email.accounts.index')
                ->with('success', 'Email account connected successfully!');

        } catch (\Exception $e) {
            Log::error('OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_params' => $request->all(),
            ]);

            return redirect()->route('email.accounts.index')
                ->with('error', 'OAuth authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Get email address from OAuth tokens
     */
    protected function getEmailFromTokens($company, array $tokens): ?string
    {
        try {
            $provider = $this->providerService->getProvider($company);
            $accountData = $provider->getAccountData($tokens, '');
            return $accountData['email'] ?? null;
        } catch (\Exception $e) {
            Log::error('Failed to get email from OAuth tokens', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}