<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Domains\Email\Services\EmailProviderService;
use App\Domains\Email\Services\EmailProviderValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CompanyEmailProviderController extends Controller
{
    protected EmailProviderService $providerService;
    protected EmailProviderValidationService $validationService;

    public function __construct(
        EmailProviderService $providerService,
        EmailProviderValidationService $validationService
    ) {
        $this->providerService = $providerService;
        $this->validationService = $validationService;
    }

    /**
     * Show the company email provider settings
     */
    public function show()
    {
        $company = Auth::user()->company;

        // Check if user has permission to manage company settings
        Gate::authorize('manage-company-settings', $company);

        $availableProviders = EmailProviderService::getAvailableProviders();
        $currentConfig = $company->email_provider_config ?? [];

        return view('settings.company-email-provider', compact(
            'company',
            'availableProviders',
            'currentConfig'
        ));
    }

    /**
     * Update the company email provider settings
     */
    public function update(Request $request)
    {
        $company = Auth::user()->company;

        // Check if user has permission to manage company settings
        Gate::authorize('manage-company-settings', $company);

        $request->validate([
            'email_provider_type' => 'required|string|in:' . implode(',', array_keys(EmailProviderService::getAvailableProviders())),
            'client_id' => 'nullable|string|max:255',
            'client_secret' => 'nullable|string|max:255',
            'tenant_id' => 'nullable|string|max:255',
            'allowed_domains' => 'nullable|string',
        ]);

        $config = [];

        // Build provider-specific configuration
        if ($request->email_provider_type === 'microsoft365') {
            $config = [
                'client_id' => $request->client_id,
                'client_secret' => $request->client_secret,
                'tenant_id' => $request->tenant_id ?: 'common',
                'allowed_domains' => $this->parseDomains($request->allowed_domains),
            ];
        } elseif ($request->email_provider_type === 'google_workspace') {
            $config = [
                'client_id' => $request->client_id,
                'client_secret' => $request->client_secret,
                'allowed_domains' => $this->parseDomains($request->allowed_domains),
            ];
        }

        // Validate configuration if OAuth provider
        if (in_array($request->email_provider_type, ['microsoft365', 'google_workspace'])) {
            $tempCompany = clone $company;
            $tempCompany->email_provider_type = $request->email_provider_type;
            $tempCompany->email_provider_config = $config;

            $validationErrors = $this->validationService->validateProviderConfig($tempCompany, $config);
            if (!empty($validationErrors)) {
                return redirect()->back()
                    ->with('error', 'Configuration validation failed: ' . implode(', ', $validationErrors))
                    ->withInput();
            }
        }

        // Update company settings
        $company->update([
            'email_provider_type' => $request->email_provider_type,
            'email_provider_config' => $config,
        ]);

        return redirect()->back()
            ->with('success', 'Email provider settings updated successfully!');
    }

    /**
     * Test the OAuth configuration
     */
    public function testConnection(Request $request)
    {
        $company = Auth::user()->company;

        // Check if user has permission to manage company settings
        Gate::authorize('manage-company-settings', $company);

        try {
            // Validate configuration
            $config = $company->email_provider_config ?? [];
            $validationErrors = $this->validationService->validateProviderConfig($company, $config);

            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuration validation failed: ' . implode(', ', $validationErrors),
                ]);
            }

            // For OAuth providers, test the OAuth configuration
            if (in_array($company->email_provider_type, ['microsoft365', 'google_workspace'])) {
                $testResult = $this->validationService->testOAuthConfiguration($company);
                return response()->json($testResult);
            }

            // For manual providers, configuration is always "valid"
            return response()->json([
                'success' => true,
                'message' => 'Manual configuration is valid',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Configuration test failed: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Parse domain list from string
     */
    protected function parseDomains(?string $domains): array
    {
        if (!$domains) {
            return [];
        }

        return array_map('trim', array_filter(explode(',', $domains)));
    }
}