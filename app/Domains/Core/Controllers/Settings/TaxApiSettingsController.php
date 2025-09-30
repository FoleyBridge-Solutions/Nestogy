<?php

namespace App\Domains\Core\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\TaxApiSettings;
use App\Domains\Financial\Services\TaxEngine\TaxEngineRouter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Tax API Settings Controller
 * 
 * Manages configuration and settings for tax calculation API integrations
 */
class TaxApiSettingsController extends Controller
{
    /**
     * Display tax API settings dashboard
     */
    public function index(): View
    {
        $companyId = auth()->user()->company_id;
        
        // Get all provider schemas
        $providers = TaxApiSettings::getAllProviderSchemas();
        
        // Get current settings for each provider
        $currentSettings = TaxApiSettings::where('company_id', $companyId)->get()->keyBy('provider');
        
        // Get usage statistics
        $usageStats = $this->getUsageStatistics($companyId);
        
        // Test tax engine router status
        $routerStatus = $this->getRouterStatus($companyId);
        
        return view('settings.tax-api', compact(
            'providers',
            'currentSettings',
            'usageStats',
            'routerStatus'
        ));
    }

    /**
     * Get current configuration for a specific provider
     */
    public function show(string $provider): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        
        $schema = TaxApiSettings::getProviderSchema($provider);
        if (empty($schema['name'])) {
            return response()->json(['error' => 'Unknown provider'], 404);
        }
        
        $settings = TaxApiSettings::getProviderSettings($companyId, $provider);
        
        return response()->json([
            'provider' => $provider,
            'schema' => $schema,
            'settings' => $settings ? [
                'enabled' => $settings->enabled,
                'status' => $settings->status,
                'configuration' => $settings->configuration ?? [],
                'monthly_api_calls' => $settings->monthly_api_calls,
                'monthly_limit' => $settings->monthly_limit,
                'last_api_call' => $settings->last_api_call,
                'last_health_check' => $settings->last_health_check,
                'health_data' => $settings->health_data,
            ] : null,
        ]);
    }

    /**
     * Update or create API settings for a provider
     */
    public function update(Request $request, string $provider): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        
        $schema = TaxApiSettings::getProviderSchema($provider);
        if (empty($schema['name'])) {
            return response()->json(['error' => 'Unknown provider'], 404);
        }
        
        // Build validation rules based on schema
        $rules = $this->buildValidationRules($schema);
        
        try {
            $validator = Validator::make($request->all(), $rules);
            
            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            $validated = $validator->validated();
            
            // Extract credentials and configuration
            $credentials = [];
            $configuration = [];
            
            foreach ($schema['credentials'] as $key => $credentialSchema) {
                if (isset($validated[$key])) {
                    $credentials[$key] = $validated[$key];
                }
            }
            
            foreach ($schema['configuration'] as $key => $configSchema) {
                if (isset($validated[$key])) {
                    $configuration[$key] = $validated[$key];
                } elseif (isset($configSchema['default'])) {
                    $configuration[$key] = $configSchema['default'];
                }
            }
            
            // Create or update settings
            $settings = TaxApiSettings::configureProvider(
                $companyId,
                $provider,
                $credentials,
                $configuration,
                $validated['enabled'] ?? false
            );
            
            // Test connection if enabled
            $connectionTest = null;
            if ($settings->enabled) {
                $connectionTest = $settings->testConnection();
            }
            
            return response()->json([
                'success' => true,
                'message' => "Settings updated for {$schema['name']}",
                'settings' => [
                    'enabled' => $settings->enabled,
                    'status' => $settings->status,
                    'configuration' => $settings->configuration,
                ],
                'connection_test' => $connectionTest,
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test API connection for a provider
     */
    public function testConnection(string $provider): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        
        $settings = TaxApiSettings::getProviderSettings($companyId, $provider);
        
        if (!$settings) {
            return response()->json([
                'error' => 'Provider not configured',
            ], 404);
        }
        
        $result = $settings->testConnection();
        
        return response()->json($result);
    }

    /**
     * Get usage statistics for all providers
     */
    public function usage(): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $stats = $this->getUsageStatistics($companyId);
        
        return response()->json($stats);
    }

    /**
     * Reset monthly usage counters (admin function)
     */
    public function resetUsage(string $provider): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        
        $settings = TaxApiSettings::getProviderSettings($companyId, $provider);
        
        if (!$settings) {
            return response()->json([
                'error' => 'Provider not configured',
            ], 404);
        }
        
        $settings->resetMonthlyCounters();
        $settings->logAuditEvent('usage_reset', [], auth()->id());
        
        return response()->json([
            'success' => true,
            'message' => 'Usage counters reset successfully',
        ]);
    }

    /**
     * Get tax calculation preview using current settings
     */
    public function preview(Request $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'category_type' => 'string|nullable',
            'customer_address' => 'array|nullable',
            'customer_address.address1' => 'string|nullable',
            'customer_address.city' => 'string|nullable',
            'customer_address.state' => 'string|nullable',
            'customer_address.zip' => 'string|nullable',
            'vat_number' => 'string|nullable',
            'customer_country' => 'string|nullable',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid parameters',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            $router = new TaxEngineRouter($companyId);
            
            $params = [
                'base_price' => $request->input('amount'),
                'quantity' => 1,
                'category_type' => $request->input('category_type', 'managed_services'),
                'customer_address' => $request->input('customer_address', []),
                'vat_number' => $request->input('vat_number'),
                'customer_country' => $request->input('customer_country'),
            ];
            
            $result = $router->calculateTaxes($params);
            
            return response()->json([
                'success' => true,
                'calculation' => $result,
                'preview_mode' => true,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Tax calculation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build validation rules from provider schema
     */
    protected function buildValidationRules(array $schema): array
    {
        $rules = [
            'enabled' => 'boolean',
        ];
        
        // Add credential validation rules
        foreach ($schema['credentials'] as $key => $credentialSchema) {
            $rule = [];
            
            if ($credentialSchema['required']) {
                $rule[] = 'required';
            } else {
                $rule[] = 'nullable';
            }
            
            switch ($credentialSchema['type']) {
                case 'string':
                case 'password':
                    $rule[] = 'string';
                    break;
                case 'email':
                    $rule[] = 'email';
                    break;
                case 'url':
                    $rule[] = 'url';
                    break;
                case 'integer':
                    $rule[] = 'integer';
                    break;
            }
            
            $rules[$key] = implode('|', $rule);
        }
        
        // Add configuration validation rules
        foreach ($schema['configuration'] as $key => $configSchema) {
            $rule = [];
            
            if ($configSchema['required']) {
                $rule[] = 'required';
            } else {
                $rule[] = 'nullable';
            }
            
            switch ($configSchema['type']) {
                case 'string':
                    $rule[] = 'string';
                    break;
                case 'object':
                    $rule[] = 'array';
                    break;
                case 'boolean':
                    $rule[] = 'boolean';
                    break;
                case 'integer':
                    $rule[] = 'integer';
                    break;
            }
            
            $rules[$key] = implode('|', $rule);
        }
        
        return $rules;
    }

    /**
     * Get usage statistics for all providers
     */
    protected function getUsageStatistics(int $companyId): array
    {
        $settings = TaxApiSettings::where('company_id', $companyId)->get();
        
        $stats = [
            'total_providers' => $settings->count(),
            'active_providers' => $settings->where('enabled', true)->count(),
            'total_monthly_calls' => $settings->sum('monthly_api_calls'),
            'total_monthly_cost' => $settings->sum('monthly_cost'),
            'providers' => [],
        ];
        
        foreach ($settings as $setting) {
            $schema = TaxApiSettings::getProviderSchema($setting->provider);
            
            $stats['providers'][$setting->provider] = [
                'name' => $schema['name'],
                'enabled' => $setting->enabled,
                'status' => $setting->status,
                'monthly_calls' => $setting->monthly_api_calls,
                'monthly_limit' => $setting->monthly_limit,
                'monthly_cost' => $setting->monthly_cost,
                'usage_percentage' => $setting->monthly_limit ? 
                    round(($setting->monthly_api_calls / $setting->monthly_limit) * 100, 1) : null,
                'last_api_call' => $setting->last_api_call,
                'last_health_check' => $setting->last_health_check,
            ];
        }
        
        return $stats;
    }

    /**
     * Get router status information
     */
    protected function getRouterStatus(int $companyId): array
    {
        try {
            $router = new TaxEngineRouter($companyId);
            
            return [
                'available' => true,
                'company_id' => $companyId,
                'api_enhancements' => $router->getApiClient('taxcloud') ? 
                    $router->getApiClient('taxcloud')->getConfigurationStatus() : null,
            ];
            
        } catch (\Exception $e) {
            return [
                'available' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}