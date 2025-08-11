<?php

namespace App\Domains\Integration\Controllers;

use App\Domains\Integration\Models\Integration;
use App\Domains\Integration\Models\RMMAlert;
use App\Domains\Integration\Models\DeviceMapping;
use App\Domains\Integration\Services\RMMIntegrationService;
use App\Http\Controllers\Controller;
use App\Jobs\SyncDeviceInventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Integration Management Controller
 * 
 * Handles administrative operations for RMM integrations.
 * Provides CRUD operations, testing, and monitoring capabilities.
 */
class IntegrationController extends Controller
{
    protected RMMIntegrationService $rmmService;

    public function __construct(RMMIntegrationService $rmmService)
    {
        $this->rmmService = $rmmService;
    }

    /**
     * Get all integrations for the current company.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Integration::forCompany()
                ->with(['rmmAlerts' => function($query) {
                    $query->recent(24)->limit(5);
                }])
                ->withCount(['rmmAlerts', 'deviceMappings']);

            if ($request->filled('provider')) {
                $query->where('provider', $request->provider);
            }

            if ($request->filled('active')) {
                $query->where('is_active', $request->boolean('active'));
            }

            $integrations = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $integrations,
                'meta' => [
                    'total_integrations' => Integration::forCompany()->count(),
                    'active_integrations' => Integration::forCompany()->active()->count(),
                    'providers' => Integration::forCompany()->distinct('provider')->pluck('provider'),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch integrations', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch integrations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new integration.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'provider' => 'required|in:connectwise,datto,ninja,generic',
                'name' => 'required|string|max:255',
                'api_endpoint' => 'nullable|url',
                'credentials' => 'required|array',
                'field_mappings' => 'nullable|array',
                'alert_rules' => 'nullable|array',
            ]);

            $integration = Integration::create([
                'company_id' => auth()->user()->company_id,
                'provider' => $validated['provider'],
                'name' => $validated['name'],
                'api_endpoint' => $validated['api_endpoint'],
                'field_mappings' => $validated['field_mappings'] 
                    ?? Integration::getDefaultFieldMappings($validated['provider']),
                'alert_rules' => $validated['alert_rules'] 
                    ?? Integration::getDefaultAlertRules($validated['provider']),
                'is_active' => false, // Start inactive until tested
            ]);

            $integration->setCredentials($validated['credentials']);

            Log::info('Integration created', [
                'integration_id' => $integration->id,
                'provider' => $integration->provider,
                'name' => $integration->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Integration created successfully',
                'data' => $integration->load('rmmAlerts', 'deviceMappings'),
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to create integration', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create integration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a specific integration.
     */
    public function show(Integration $integration): JsonResponse
    {
        try {
            $integration->load([
                'rmmAlerts' => function($query) {
                    $query->recent(48)->limit(50);
                },
                'deviceMappings' => function($query) {
                    $query->active()->limit(100);
                }
            ]);

            // Add statistics
            $stats = [
                'total_alerts' => $integration->rmmAlerts()->count(),
                'alerts_last_24h' => $integration->rmmAlerts()->recent(24)->count(),
                'alerts_last_7d' => $integration->rmmAlerts()->recent(24 * 7)->count(),
                'duplicate_alerts' => $integration->rmmAlerts()->duplicate()->count(),
                'processed_alerts' => $integration->rmmAlerts()->processed()->count(),
                'total_devices' => $integration->deviceMappings()->count(),
                'active_devices' => $integration->deviceMappings()->active()->count(),
                'mapped_devices' => $integration->deviceMappings()->mapped()->count(),
                'last_sync' => $integration->last_sync,
            ];

            return response()->json([
                'success' => true,
                'data' => $integration,
                'stats' => $stats,
                'webhook_url' => $integration->getWebhookEndpoint(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch integration', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch integration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing integration.
     */
    public function update(Request $request, Integration $integration): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'api_endpoint' => 'sometimes|nullable|url',
                'credentials' => 'sometimes|array',
                'field_mappings' => 'sometimes|nullable|array',
                'alert_rules' => 'sometimes|nullable|array',
            ]);

            $integration->fill([
                'name' => $validated['name'] ?? $integration->name,
                'api_endpoint' => $validated['api_endpoint'] ?? $integration->api_endpoint,
                'field_mappings' => $validated['field_mappings'] ?? $integration->field_mappings,
                'alert_rules' => $validated['alert_rules'] ?? $integration->alert_rules,
            ]);

            if (isset($validated['credentials'])) {
                $integration->setCredentials($validated['credentials']);
            }

            $integration->save();

            Log::info('Integration updated', [
                'integration_id' => $integration->id,
                'changes' => array_keys($validated),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Integration updated successfully',
                'data' => $integration,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to update integration', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update integration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an integration.
     */
    public function destroy(Integration $integration): JsonResponse
    {
        try {
            $integrationName = $integration->name;
            $integrationId = $integration->id;

            // Delete related data
            $integration->rmmAlerts()->delete();
            $integration->deviceMappings()->delete();
            $integration->delete();

            Log::info('Integration deleted', [
                'integration_id' => $integrationId,
                'name' => $integrationName,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Integration deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete integration', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete integration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle integration active status.
     */
    public function toggle(Integration $integration): JsonResponse
    {
        try {
            $integration->update(['is_active' => !$integration->is_active]);

            $status = $integration->is_active ? 'activated' : 'deactivated';

            Log::info("Integration {$status}", [
                'integration_id' => $integration->id,
                'is_active' => $integration->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Integration {$status} successfully",
                'data' => ['is_active' => $integration->is_active],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle integration', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle integration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test integration connection.
     */
    public function testConnection(Integration $integration): JsonResponse
    {
        try {
            // Dispatch test sync job
            SyncDeviceInventory::dispatch($integration)->onQueue('high-priority');

            Log::info('Integration connection test initiated', [
                'integration_id' => $integration->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Connection test initiated. Check logs for results.',
                'webhook_url' => $integration->getWebhookEndpoint(),
                'test_endpoint' => route('api.webhooks.' . $integration->provider . '.test', [
                    'integration' => $integration->uuid
                ]),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to test integration connection', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to test integration connection',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync devices for an integration.
     */
    public function syncDevices(Integration $integration): JsonResponse
    {
        try {
            SyncDeviceInventory::dispatch($integration)->onQueue('device-sync');

            Log::info('Device sync initiated', [
                'integration_id' => $integration->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Device synchronization initiated',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to initiate device sync', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate device sync',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available providers and their capabilities.
     */
    public function getProviders(): JsonResponse
    {
        $providers = [
            'connectwise' => [
                'name' => 'ConnectWise Automate',
                'description' => 'Leading RMM platform with comprehensive monitoring capabilities',
                'features' => ['Device monitoring', 'Alert management', 'Patch management', 'Script deployment'],
                'auth_methods' => ['api_key'],
                'webhook_support' => true,
                'bidirectional_sync' => true,
            ],
            'datto' => [
                'name' => 'Datto RMM',
                'description' => 'Cloud-based RMM with backup integration',
                'features' => ['Device monitoring', 'Backup alerts', 'Patch management', 'Mobile support'],
                'auth_methods' => ['shared_secret'],
                'webhook_support' => true,
                'bidirectional_sync' => true,
            ],
            'ninja' => [
                'name' => 'NinjaOne',
                'description' => 'Modern RMM platform with intuitive interface',
                'features' => ['Device monitoring', 'Software management', 'Security monitoring', 'Automation'],
                'auth_methods' => ['bearer_token'],
                'webhook_support' => true,
                'bidirectional_sync' => true,
            ],
            'generic' => [
                'name' => 'Generic RMM',
                'description' => 'Flexible integration for custom or unsupported RMM systems',
                'features' => ['Webhook processing', 'Custom field mapping', 'Alert processing'],
                'auth_methods' => ['api_key', 'hmac', 'none'],
                'webhook_support' => true,
                'bidirectional_sync' => false,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $providers,
        ]);
    }

    /**
     * Get default configuration for a provider.
     */
    public function getProviderDefaults(string $provider): JsonResponse
    {
        if (!in_array($provider, ['connectwise', 'datto', 'ninja', 'generic'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid provider',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'field_mappings' => Integration::getDefaultFieldMappings($provider),
                'alert_rules' => Integration::getDefaultAlertRules($provider),
            ],
        ]);
    }

    /**
     * Get integration statistics.
     */
    public function getStats(): JsonResponse
    {
        try {
            $cacheKey = 'integration_stats_' . auth()->user()->company_id;
            
            $stats = Cache::remember($cacheKey, 300, function () { // 5 minute cache
                return [
                    'total_integrations' => Integration::forCompany()->count(),
                    'active_integrations' => Integration::forCompany()->active()->count(),
                    'inactive_integrations' => Integration::forCompany()->where('is_active', false)->count(),
                    'total_alerts_today' => RMMAlert::whereHas('integration', function($query) {
                        $query->forCompany();
                    })->recent(24)->count(),
                    'total_devices' => DeviceMapping::whereHas('integration', function($query) {
                        $query->forCompany();
                    })->count(),
                    'mapped_devices' => DeviceMapping::whereHas('integration', function($query) {
                        $query->forCompany();
                    })->mapped()->count(),
                    'provider_breakdown' => Integration::forCompany()
                        ->select('provider', DB::raw('count(*) as count'))
                        ->groupBy('provider')
                        ->pluck('count', 'provider'),
                    'alert_severity_breakdown' => RMMAlert::whereHas('integration', function($query) {
                        $query->forCompany();
                    })->recent(24 * 7)
                        ->select('severity', DB::raw('count(*) as count'))
                        ->groupBy('severity')
                        ->pluck('count', 'severity'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch integration stats', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics for a specific integration.
     */
    public function getIntegrationStats(Integration $integration): JsonResponse
    {
        try {
            $stats = [
                'alerts' => [
                    'total' => $integration->rmmAlerts()->count(),
                    'last_24h' => $integration->rmmAlerts()->recent(24)->count(),
                    'last_7d' => $integration->rmmAlerts()->recent(24 * 7)->count(),
                    'last_30d' => $integration->rmmAlerts()->recent(24 * 30)->count(),
                    'by_severity' => $integration->rmmAlerts()
                        ->select('severity', DB::raw('count(*) as count'))
                        ->groupBy('severity')
                        ->pluck('count', 'severity'),
                    'processed' => $integration->rmmAlerts()->processed()->count(),
                    'duplicates' => $integration->rmmAlerts()->duplicate()->count(),
                ],
                'devices' => [
                    'total' => $integration->deviceMappings()->count(),
                    'active' => $integration->deviceMappings()->active()->count(),
                    'mapped' => $integration->deviceMappings()->mapped()->count(),
                    'stale' => $integration->deviceMappings()->stale(24)->count(),
                ],
                'performance' => [
                    'last_sync' => $integration->last_sync,
                    'avg_processing_time' => $this->getAverageProcessingTime($integration),
                    'uptime_percentage' => $this->getUptimePercentage($integration),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch integration statistics', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch integration statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate average processing time for an integration.
     */
    protected function getAverageProcessingTime(Integration $integration): ?float
    {
        // This would be implemented with more detailed logging/metrics
        // For now, return null to indicate not available
        return null;
    }

    /**
     * Calculate uptime percentage for an integration.
     */
    protected function getUptimePercentage(Integration $integration): ?float
    {
        // This would be implemented with uptime monitoring
        // For now, return null to indicate not available
        return null;
    }
}