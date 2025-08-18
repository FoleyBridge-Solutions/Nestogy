<?php

namespace App\Domains\Integration\Controllers;

use App\Domains\Integration\Models\RmmIntegration;
use App\Domains\Integration\Services\RmmServiceFactory;
use App\Http\Controllers\Controller;
use App\Jobs\SyncRmmAgents;
use App\Jobs\SyncRmmAlerts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * RMM Integrations Controller
 * 
 * Handles admin interface for managing RMM integrations.
 * Provides CRUD operations and integration testing.
 */
class RmmIntegrationsController extends Controller
{
    protected RmmServiceFactory $rmmFactory;

    public function __construct(RmmServiceFactory $rmmFactory)
    {
        $this->rmmFactory = $rmmFactory;
    }

    /**
     * Display list of RMM integrations.
     */
    public function index(Request $request)
    {
        $query = RmmIntegration::where('company_id', auth()->user()->company_id)
                              ->with(['company']);

        // Apply search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('rmm_type', 'like', "%{$search}%");
            });
        }

        // Apply type filter
        if ($type = $request->get('type')) {
            $query->where('rmm_type', $type);
        }

        // Apply status filter
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $integrations = $query->orderBy('created_at', 'desc')
                             ->paginate(20)
                             ->appends($request->query());

        // Get available types for filter dropdown
        $availableTypes = $this->rmmFactory->getAvailableTypes();

        if ($request->wantsJson()) {
            // Add security indicators for saved credentials
            $integrationsWithIndicators = $integrations->items();
            foreach ($integrationsWithIndicators as $integration) {
                $integration->has_api_url = !empty($integration->api_url);
                $integration->has_api_key = !empty($integration->api_key);
                // Remove the actual encrypted values from the response for security
                unset($integration->api_url_encrypted, $integration->api_key_encrypted);
            }
            
            return response()->json([
                'integrations' => $integrationsWithIndicators,
                'pagination' => [
                    'current_page' => $integrations->currentPage(),
                    'total' => $integrations->total(),
                    'per_page' => $integrations->perPage(),
                ],
                'available_types' => $availableTypes,
            ]);
        }

        return view('admin.integrations.rmm.index', compact('integrations', 'availableTypes'));
    }

    /**
     * Show the form for creating a new RMM integration.
     */
    public function create()
    {
        $availableTypes = $this->rmmFactory->getAvailableTypes();
        
        return view('admin.integrations.rmm.create', compact('availableTypes'));
    }

    /**
     * Store a newly created RMM integration.
     */
    public function store(Request $request)
    {
        // Get validation rules without company_id since we set it in the controller
        $rules = RmmIntegration::getValidationRules();
        unset($rules['company_id']);
        
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        try {
            // Validate RMM type is supported
            $this->rmmFactory->validateRmmType($request->rmm_type);

            // Check if integration already exists for this company and type
            $existingIntegration = RmmIntegration::where([
                'company_id' => auth()->user()->company_id,
                'rmm_type' => $request->rmm_type,
            ])->first();

            if ($existingIntegration) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'An integration of this type already exists for your company.',
                    ], 409);
                }
                
                return redirect()->back()
                               ->withInput()
                               ->with('error', 'An integration of this type already exists for your company.');
            }

            // Create integration
            $integration = RmmIntegration::createWithCredentials([
                'company_id' => auth()->user()->company_id,
                'rmm_type' => $request->rmm_type,
                'name' => $request->name,
                'api_url' => $request->api_url,
                'api_key' => $request->api_key,
                'is_active' => $request->boolean('is_active', true),
                'settings' => $request->settings ?? [],
            ]);

            Log::info('RMM integration created', [
                'integration_id' => $integration->id,
                'rmm_type' => $integration->rmm_type,
                'user_id' => auth()->id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Integration created successfully',
                    'integration' => $integration,
                ], 201);
            }

            return redirect()->route('admin.integrations.rmm.show', $integration)
                           ->with('success', 'RMM integration created successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to create RMM integration', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->except(['api_key']),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create integration: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Failed to create integration: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified RMM integration.
     */
    public function show(Request $request, RmmIntegration $integration)
    {
        $this->authorize('view', $integration);

        // Get sync status
        $syncStatus = $integration->getSyncStatus();

        // Get recent sync statistics
        $recentStats = [
            'total_agents' => $integration->total_agents,
            'last_alerts_count' => $integration->last_alerts_count,
            'last_sync' => $integration->last_sync_at,
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'integration' => $integration,
                'sync_status' => $syncStatus,
                'recent_stats' => $recentStats,
            ]);
        }

        return view('admin.integrations.rmm.show', compact('integration', 'syncStatus', 'recentStats'));
    }

    /**
     * Show the form for editing the specified integration.
     */
    public function edit(RmmIntegration $integration)
    {
        $this->authorize('update', $integration);

        $availableTypes = $this->rmmFactory->getAvailableTypes();
        
        return view('admin.integrations.rmm.edit', compact('integration', 'availableTypes'));
    }

    /**
     * Update the specified RMM integration.
     */
    public function update(Request $request, RmmIntegration $integration)
    {
        $this->authorize('update', $integration);

        $validator = Validator::make($request->all(), RmmIntegration::getUpdateValidationRules());

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        try {
            $updateData = [
                'name' => $request->name,
                'is_active' => $request->boolean('is_active'),
                'settings' => $request->settings ?? $integration->settings,
            ];

            // Update API credentials only if provided
            if ($request->filled('api_url')) {
                $updateData['api_url'] = $request->api_url;
            }

            if ($request->filled('api_key')) {
                $updateData['api_key'] = $request->api_key;
            }

            $integration->update($updateData);

            Log::info('RMM integration updated', [
                'integration_id' => $integration->id,
                'user_id' => auth()->id(),
                'changes' => $integration->getChanges(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Integration updated successfully',
                    'integration' => $integration->fresh(),
                ]);
            }

            return redirect()->route('admin.integrations.rmm.show', $integration)
                           ->with('success', 'Integration updated successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to update RMM integration', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update integration: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Failed to update integration: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified RMM integration.
     */
    public function destroy(Request $request, RmmIntegration $integration)
    {
        $this->authorize('delete', $integration);

        try {
            $integrationName = $integration->name;
            $integration->delete();

            Log::warning('RMM integration deleted', [
                'integration_id' => $integration->id,
                'integration_name' => $integrationName,
                'user_id' => auth()->id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Integration deleted successfully',
                ]);
            }

            return redirect()->route('admin.integrations.rmm.index')
                           ->with('success', "Integration '{$integrationName}' deleted successfully.");

        } catch (\Exception $e) {
            Log::error('Failed to delete RMM integration', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete integration: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                           ->with('error', 'Failed to delete integration: ' . $e->getMessage());
        }
    }

    /**
     * Test connection to the RMM system with raw credentials.
     */
    public function testConnection(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'api_url' => 'required|url',
            'api_key' => 'required|string',
            'rmm_type' => 'nullable|string|in:TRMM',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Test connection using raw credentials without creating model instance
            $rmmService = $this->rmmFactory->makeFromCredentials(
                $request->input('rmm_type', 'TRMM'),
                $request->input('api_url'),
                $request->input('api_key')
            );

            $result = $rmmService->testConnection();

            Log::info('RMM integration connection test (raw credentials)', [
                'rmm_type' => $request->input('rmm_type', 'TRMM'),
                'api_url' => $request->input('api_url'),
                'success' => $result['success'],
                'user_id' => auth()->id(),
            ]);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('RMM integration connection test failed (raw credentials)', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'api_url' => $request->input('api_url'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test connection to the RMM system for existing integration.
     */
    public function testExistingConnection(Request $request, RmmIntegration $integration)
    {
        // Ensure integration belongs to current company
        if ($integration->company_id !== auth()->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Integration not found'], 404);
        }

        try {
            $result = $integration->testConnection();

            Log::info('RMM integration connection test', [
                'integration_id' => $integration->id,
                'success' => $result['success'],
                'user_id' => auth()->id(),
            ]);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('RMM integration connection test failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Manually trigger agents sync.
     */
    public function syncAgents(Request $request, RmmIntegration $integration)
    {
        $this->authorize('update', $integration);

        try {
            SyncRmmAgents::dispatch($integration);

            Log::info('Manual RMM agents sync triggered', [
                'integration_id' => $integration->id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Agents sync job queued successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to trigger RMM agents sync', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to trigger sync: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Manually trigger alerts sync.
     */
    public function syncAlerts(Request $request, RmmIntegration $integration)
    {
        $this->authorize('update', $integration);

        $filters = $request->only(['from_date', 'to_date', 'severity']);

        try {
            SyncRmmAlerts::dispatch($integration, $filters);

            Log::info('Manual RMM alerts sync triggered', [
                'integration_id' => $integration->id,
                'filters' => $filters,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Alerts sync job queued successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to trigger RMM alerts sync', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to trigger sync: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle integration active status.
     */
    public function toggleStatus(Request $request, RmmIntegration $integration)
    {
        $this->authorize('update', $integration);

        try {
            $integration->update([
                'is_active' => !$integration->is_active,
            ]);

            $status = $integration->is_active ? 'activated' : 'deactivated';

            Log::info("RMM integration {$status}", [
                'integration_id' => $integration->id,
                'new_status' => $integration->is_active,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Integration {$status} successfully",
                'is_active' => $integration->is_active,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle RMM integration status', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available RMM types and their details.
     */
    public function getAvailableTypes()
    {
        $types = $this->rmmFactory->getAvailableTypes();
        
        return response()->json([
            'success' => true,
            'types' => $types,
        ]);
    }

    /**
     * Get integration statistics.
     */
    public function getStats(Request $request, RmmIntegration $integration)
    {
        $this->authorize('view', $integration);

        $stats = [
            'basic' => [
                'total_agents' => $integration->total_agents,
                'last_alerts_count' => $integration->last_alerts_count,
                'last_sync' => $integration->last_sync_at?->format('Y-m-d H:i:s'),
                'sync_status' => $integration->getSyncStatus(),
            ],
            'health' => [
                'is_active' => $integration->is_active,
                'connection_status' => 'unknown', // Will be populated by connection test
            ],
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }
}