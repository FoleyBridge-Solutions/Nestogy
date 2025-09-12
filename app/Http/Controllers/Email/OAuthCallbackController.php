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
        Log::info('OAuth callback received', [
            'has_code' => $request->has('code'),
            'has_state' => $request->has('state'),
            'code_length' => $request->code ? strlen($request->code) : 0,
            'state' => $request->state,
            'all_params' => $request->all(),
        ]);

        try {
            // Validate required parameters
            Log::info('About to validate OAuth parameters');
            $request->validate([
                'code' => 'required|string',
                'state' => 'required|string',
            ]);
            Log::info('OAuth parameter validation passed');

            // Verify state from DATABASE instead of session
            $oauthState = \DB::table('oauth_states')
                ->where('state', $request->state)
                ->where('expires_at', '>', now())
                ->first();

            Log::info('OAuth state lookup', [
                'request_state' => $request->state,
                'state_found' => !empty($oauthState),
                'state_data' => $oauthState,
            ]);

            if (!$oauthState) {
                Log::warning('OAuth state not found in database - REDIRECTING WITH ERROR', [
                    'request_state' => $request->state,
                ]);
                return redirect()->route('email.accounts.index')
                    ->with('error', 'OAuth authentication failed: Invalid or expired state parameter');
            }

            // Get OAuth context from database record
            $oauthContext = [
                'company_id' => $oauthState->company_id,
                'user_id' => $oauthState->user_id,
                'email' => $oauthState->email,
            ];

            // Delete used state
            \DB::table('oauth_states')->where('id', $oauthState->id)->delete();

            // Clean up expired states
            \DB::table('oauth_states')->where('expires_at', '<', now())->delete();

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
            Log::error('OAuth callback EXCEPTION - REDIRECTING WITH ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_params' => $request->all(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
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