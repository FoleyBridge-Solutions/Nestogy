<?php

namespace App\Domains\Integration\Controllers;

use App\Domains\Integration\Models\RmmClientMapping;
use App\Domains\Integration\Models\RmmIntegration;
use App\Domains\Integration\Services\RmmServiceFactory;
use App\Http\Controllers\Controller;
use App\Jobs\SyncRmmAgents;
use App\Jobs\SyncRmmAlerts;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RmmClientController extends Controller
{
    public function syncAgents(Request $request)
    {
        $integration = RmmIntegration::where('company_id', auth()->user()->company_id)->first();
        if (! $integration) {
            return response()->json(['success' => false, 'message' => 'No RMM integration found'], 404);
        }

        $mappedClients = Client::where('company_id', auth()->user()->company_id)
            ->whereHas('rmmClientMappings')
            ->count();

        if ($mappedClients === 0) {
            return response()->json([
                'success' => false,
                'message' => 'At least one client mapping is required before syncing agents. Please map at least one Nestogy client to an RMM client.',
                'requires_mapping' => true,
                'mapped_count' => $mappedClients,
            ], 422);
        }

        try {
            SyncRmmAgents::dispatch($integration);

            return response()->json(['success' => true, 'message' => 'Agents sync job queued successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to trigger sync: '.$e->getMessage()], 500);
        }
    }

    public function syncAlerts(Request $request)
    {
        $integration = RmmIntegration::where('company_id', auth()->user()->company_id)->first();
        if (! $integration) {
            return response()->json(['success' => false, 'message' => 'No RMM integration found'], 404);
        }

        $mappedClients = Client::where('company_id', auth()->user()->company_id)
            ->whereHas('rmmClientMappings')
            ->count();

        if ($mappedClients === 0) {
            return response()->json([
                'success' => false,
                'message' => 'At least one client mapping is required before syncing alerts. Please map at least one Nestogy client to an RMM client.',
                'requires_mapping' => true,
                'mapped_count' => $mappedClients,
            ], 422);
        }

        $filters = $request->only(['from_date', 'to_date', 'severity']);
        try {
            SyncRmmAlerts::dispatch($integration, $filters);

            return response()->json(['success' => true, 'message' => 'Alerts sync job queued successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to trigger sync: '.$e->getMessage()], 500);
        }
    }

    public function getNestogyClients(Request $request)
    {
        return response()->json([
            'success' => true,
            'clients' => Client::where('company_id', auth()->user()->company_id)
                ->select('id', 'name', 'company_name', 'status')
                ->with(['rmmClientMappings' => function ($query) {
                    $integration = RmmIntegration::where('company_id', auth()->user()->company_id)->first();
                    if ($integration) {
                        $query->where('integration_id', $integration->id);
                    }
                }])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function getRmmClients(Request $request)
    {
        $integration = RmmIntegration::where('company_id', auth()->user()->company_id)->first();
        if (! $integration) {
            return response()->json(['success' => false, 'message' => 'No RMM integration found'], 404);
        }

        try {
            $rmmService = app(RmmServiceFactory::class)->make($integration);
            $clientsResult = $rmmService->getClients();

            return response()->json([
                'success' => true,
                'clients' => $clientsResult['data'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch RMM clients: '.$e->getMessage()], 500);
        }
    }

    public function storeClientMapping(Request $request)
    {
        $integration = RmmIntegration::where('company_id', auth()->user()->company_id)->first();
        if (! $integration) {
            return response()->json(['success' => false, 'message' => 'No RMM integration found'], 404);
        }

        Log::info('Client mapping request data:', $request->all());

        try {
            $validated = $request->validate([
                'client_id' => 'required',
                'rmm_client_id' => 'required',
                'rmm_client_name' => 'required|string',
            ]);

            $validated['rmm_client_id'] = (string) $validated['rmm_client_id'];

            $client = Client::where('id', $validated['client_id'])
                ->where('company_id', auth()->user()->company_id)
                ->first();

            if (! $client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found or access denied',
                ], 422);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Client mapping validation failed:', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
                'company_id' => auth()->user()->company_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            $mapping = RmmClientMapping::createOrUpdateMapping([
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
                'mapping' => $mapping->load('client'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create mapping: '.$e->getMessage()], 500);
        }
    }

    public function destroyClientMapping(Request $request, $mappingId)
    {
        $mapping = RmmClientMapping::where('id', $mappingId)
            ->where('company_id', auth()->user()->company_id)
            ->first();

        if (! $mapping) {
            return response()->json(['success' => false, 'message' => 'Client mapping not found or access denied'], 404);
        }

        try {
            $mapping->delete();

            return response()->json(['success' => true, 'message' => 'Client mapping deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete mapping: '.$e->getMessage()], 500);
        }
    }
}
