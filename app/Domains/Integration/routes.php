<?php
// RMM Integration API (for settings page AJAX calls)

use Illuminate\Support\Facades\Route;
Route::middleware(['auth', 'verified', 'company'])->prefix('api/rmm')->name('api.rmm.')->group(function () {
    // RMM Integration CRUD
    Route::get('integrations', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'index'])->name('integrations.index');
    Route::post('integrations', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'store'])->name('integrations.store');
    Route::get('integrations/{integration}', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'show'])->name('integrations.show');
    Route::put('integrations/{integration}', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'update'])->name('integrations.update');
    Route::delete('integrations/{integration}', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'destroy'])->name('integrations.destroy');
    
    // RMM Integration Actions
    Route::post('test-connection', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'testConnection'])->name('test-connection');
    Route::post('integrations/{integration}/test-connection', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'testExistingConnection'])->name('integrations.test-connection');
    Route::post('integrations/{integration}/sync-agents', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'syncAgents'])->name('integrations.sync-agents');
    Route::post('integrations/{integration}/sync-alerts', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'syncAlerts'])->name('integrations.sync-alerts');
    Route::patch('integrations/{integration}/toggle', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'toggleStatus'])->name('integrations.toggle');
    
    // Simplified routes for frontend - bypass authorization since we filter by company
    Route::post('sync-agents', function(\Illuminate\Http\Request $request) {
        $integration = \App\Domains\Integration\Models\RmmIntegration::where('company_id', auth()->user()->company_id)->first();
        if (!$integration) {
            return response()->json(['success' => false, 'message' => 'No RMM integration found'], 404);
        }
        
        // Check if at least one client has mapping (optional requirement)
        $mappedClients = \App\Models\Client::where('company_id', auth()->user()->company_id)
            ->whereHas('rmmClientMappings')
            ->count();
            
        if ($mappedClients === 0) {
            return response()->json([
                'success' => false, 
                'message' => 'At least one client mapping is required before syncing agents. Please map at least one Nestogy client to an RMM client.',
                'requires_mapping' => true,
                'mapped_count' => $mappedClients
            ], 422);
        }
        
        // Dispatch sync job directly without authorization
        try {
            \App\Jobs\SyncRmmAgents::dispatch($integration);
            return response()->json(['success' => true, 'message' => 'Agents sync job queued successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to trigger sync: ' . $e->getMessage()], 500);
        }
    })->name('sync-agents');
    
    Route::post('sync-alerts', function(\Illuminate\Http\Request $request) {
        $integration = \App\Domains\Integration\Models\RmmIntegration::where('company_id', auth()->user()->company_id)->first();
        if (!$integration) {
            return response()->json(['success' => false, 'message' => 'No RMM integration found'], 404);
        }
        
        // Check if at least one client has mapping (optional requirement)
        $mappedClients = \App\Models\Client::where('company_id', auth()->user()->company_id)
            ->whereHas('rmmClientMappings')
            ->count();
            
        if ($mappedClients === 0) {
            return response()->json([
                'success' => false, 
                'message' => 'At least one client mapping is required before syncing alerts. Please map at least one Nestogy client to an RMM client.',
                'requires_mapping' => true,
                'mapped_count' => $mappedClients
            ], 422);
        }
        
        // Dispatch sync job directly without authorization
        $filters = $request->only(['from_date', 'to_date', 'severity']);
        try {
            \App\Jobs\SyncRmmAlerts::dispatch($integration, $filters);
            return response()->json(['success' => true, 'message' => 'Alerts sync job queued successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to trigger sync: ' . $e->getMessage()], 500);
        }
    })->name('sync-alerts');
    
    // Get available RMM types
    Route::get('types', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'getAvailableTypes'])->name('types');
    
    // Get integration statistics
    Route::get('stats', [App\Domains\Integration\Controllers\RmmIntegrationsController::class, 'getStats'])->name('stats');
    
    // Client mapping endpoints
    Route::get('clients/nestogy', function(\Illuminate\Http\Request $request) {
        return response()->json([
            'success' => true,
            'clients' => \App\Models\Client::where('company_id', auth()->user()->company_id)
                ->select('id', 'name', 'company_name', 'status')
                ->with(['rmmClientMappings' => function($query) {
                    $integration = \App\Domains\Integration\Models\RmmIntegration::where('company_id', auth()->user()->company_id)->first();
                    if ($integration) {
                        $query->where('integration_id', $integration->id);
                    }
                }])
                ->orderBy('name')
                ->get()
        ]);
    })->name('clients.nestogy');
    
    Route::get('clients/rmm', function(\Illuminate\Http\Request $request) {
        $integration = \App\Domains\Integration\Models\RmmIntegration::where('company_id', auth()->user()->company_id)->first();
        if (!$integration) {
            return response()->json(['success' => false, 'message' => 'No RMM integration found'], 404);
        }
        
        try {
            $rmmService = app(\App\Domains\Integration\Services\RmmServiceFactory::class)->make($integration);
            $clientsResult = $rmmService->getClients();
            
            return response()->json([
                'success' => true,
                'clients' => $clientsResult['data'] ?? []
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch RMM clients: ' . $e->getMessage()], 500);
        }
    })->name('clients.rmm');
    
    Route::post('client-mappings', function(\Illuminate\Http\Request $request) {
        $integration = \App\Domains\Integration\Models\RmmIntegration::where('company_id', auth()->user()->company_id)->first();
        if (!$integration) {
            return response()->json(['success' => false, 'message' => 'No RMM integration found'], 404);
        }
        
        // Log the request data for debugging
        \Illuminate\Support\Facades\Log::info('Client mapping request data:', $request->all());
        
        try {
            $validated = $request->validate([
                'client_id' => 'required',
                'rmm_client_id' => 'required',
                'rmm_client_name' => 'required|string',
            ]);
            
            // Convert rmm_client_id to string if it's not already
            $validated['rmm_client_id'] = (string) $validated['rmm_client_id'];
            
            // Manually check if client exists and belongs to company
            $client = \App\Models\Client::where('id', $validated['client_id'])
                ->where('company_id', auth()->user()->company_id)
                ->first();
                
            if (!$client) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Client not found or access denied'
                ], 422);
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('Client mapping validation failed:', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
                'company_id' => auth()->user()->company_id
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
        
        try {
            $mapping = \App\Domains\Integration\Models\RmmClientMapping::createOrUpdateMapping([
                'company_id' => auth()->user()->company_id,
                'client_id' => $validated['client_id'],
                'integration_id' => $integration->id,
                'rmm_client_id' => $validated['rmm_client_id'],
                'rmm_client_name' => $validated['rmm_client_name'],
                'is_active' => true,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Client mapping created successfully',
                'mapping' => $mapping->load('client')
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create mapping: ' . $e->getMessage()], 500);
        }
    })->name('client-mappings.store');
    
    Route::delete('client-mappings/{mappingId}', function(\Illuminate\Http\Request $request, $mappingId) {
        // Find the mapping with proper company scoping
        $mapping = \App\Domains\Integration\Models\RmmClientMapping::where('id', $mappingId)
            ->where('company_id', auth()->user()->company_id)
            ->first();
            
        if (!$mapping) {
            return response()->json(['success' => false, 'message' => 'Client mapping not found or access denied'], 404);
        }
        
        try {
            $mapping->delete();
            return response()->json(['success' => true, 'message' => 'Client mapping deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete mapping: ' . $e->getMessage()], 500);
        }
    })->name('client-mappings.destroy');
});
