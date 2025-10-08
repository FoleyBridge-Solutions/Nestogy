<?php

namespace App\Domains\Email\Controllers\Legacy;

use App\Domains\Email\Services\EmailProviderService;
use App\Domains\Email\Services\OAuthTokenManager;
use App\Http\Controllers\Controller;
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
            $request->validate([
                'code' => 'required|string',
                'state' => 'required|string',
            ]);
            Log::info('OAuth parameter validation passed');

            $result = $this->processOAuthCallback($request);

            return redirect()->route('email.accounts.index')
                ->with($result['type'], $result['message']);

        } catch (\Exception $e) {
            Log::error('OAuth callback EXCEPTION - REDIRECTING WITH ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_params' => $request->all(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()->route('email.accounts.index')
                ->with('error', 'OAuth authentication failed: '.$e->getMessage());
        }
    }

    /**
     * Process OAuth callback and create email account
     */
    protected function processOAuthCallback(Request $request): array
    {
        $oauthState = \DB::table('oauth_states')
            ->where('state', $request->state)
            ->where('expires_at', '>', now())
            ->first();

        Log::info('OAuth state lookup', [
            'request_state' => $request->state,
            'state_found' => ! empty($oauthState),
            'state_data' => $oauthState,
        ]);

        if (! $oauthState) {
            Log::warning('OAuth state not found in database', [
                'request_state' => $request->state,
            ]);

            throw new \Exception('Invalid or expired state parameter');
        }

        $oauthContext = [
            'company_id' => $oauthState->company_id,
            'user_id' => $oauthState->user_id,
            'email' => $oauthState->email,
        ];

        \DB::table('oauth_states')->where('id', $oauthState->id)->delete();
        \DB::table('oauth_states')->where('expires_at', '<', now())->delete();

        $company = \App\Models\Company::find($oauthContext['company_id']);
        if (! $company) {
            throw new \Exception('Company not found');
        }

        $tokens = $this->providerService->exchangeCodeForTokens($company, $request->code);
        $email = $oauthContext['email'] ?? $this->getEmailFromTokens($company, $tokens);

        if (! $email) {
            throw new \Exception('Could not determine email address');
        }

        $account = $this->providerService->createAccountFromOAuth(
            $company,
            $tokens,
            $email,
            $oauthContext['user_id']
        );

        Log::info('OAuth email account created successfully', [
            'account_id' => $account->id,
            'company_id' => $company->id,
            'user_id' => $oauthContext['user_id'],
            'email' => $email,
            'provider' => $company->email_provider_type,
            'account_user_id' => $account->user_id,
            'tokens_received' => ! empty($tokens),
        ]);

        return [
            'type' => 'success',
            'message' => 'Email account connected successfully!',
        ];
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
