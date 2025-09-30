<?php

namespace App\Domains\Asset\Controllers;

use App\Domains\Asset\Requests\StoreAssetRequest;
use App\Domains\Asset\Requests\UpdateAssetRequest;
use App\Domains\Asset\Services\AssetService;
use App\Http\Controllers\Controller;
use App\Domains\Core\Controllers\Traits\UsesSelectedClient;
use App\Models\Asset;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    use UsesSelectedClient;

    public function __construct(
        private AssetService $assetService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'type', 'status', 'location_id']);
        $filters = $this->addClientToFilters($filters);

        $assets = $this->assetService->getPaginatedAssets($filters);
        $clients = $this->assetService->getClientsForFilter();
        $locations = $this->assetService->getLocationsForFilter();
        $contacts = $this->assetService->getContactsForFilter();

        return view('assets.index-livewire');
    }

    public function create()
    {
        return view('assets.create');
    }

    public function store(StoreAssetRequest $request)
    {
        $asset = $this->assetService->create($request->validated());

        return redirect()->route('assets.show', $asset)
            ->with('success', 'Asset created successfully.');
    }

    public function show(Asset $asset)
    {
        $this->authorize('view', $asset);

        $asset = $this->assetService->getAssetWithRelationships($asset);

        // Generate QR code for asset
        $qrCodeUrl = route('assets.show', $asset);
        $qrCode = $this->generateQrCode($qrCodeUrl);

        return view('assets.show', compact('asset', 'qrCode'));
    }

    public function edit(Asset $asset)
    {
        $this->authorize('update', $asset);

        return view('assets.edit', compact('asset'));
    }

    public function update(UpdateAssetRequest $request, Asset $asset)
    {
        $this->authorize('update', $asset);

        $asset = $this->assetService->update($asset, $request->validated());

        return redirect()->route('assets.show', $asset)
            ->with('success', 'Asset updated successfully.');
    }

    public function destroy(Asset $asset)
    {
        $this->authorize('delete', $asset);

        $this->assetService->archive($asset);

        return redirect()->route('assets.index')
            ->with('success', 'Asset archived successfully.');
    }

    // Client-scoped asset methods

    public function clientIndex(Request $request, \App\Models\Client $client)
    {
        $this->authorize('viewAny', Asset::class);

        $filters = array_merge(
            $request->only(['search', 'type', 'status', 'location_id']),
            ['client_id' => $client->id]
        );

        // Return JSON for AJAX requests - get ALL assets, not paginated
        if ($request->wantsJson() || $request->ajax()) {
            $query = Asset::where('company_id', auth()->user()->company_id)
                ->whereNull('archived_at')
                ->where('client_id', $client->id);

            // Apply search filter
            if (! empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('serial', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('make', 'like', "%{$search}%");
                });
            }

            // Apply type filter
            if (! empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            // Apply status filter
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            // Apply location filter
            if (! empty($filters['location_id'])) {
                $query->where('location_id', $filters['location_id']);
            }

            $allAssets = $query->orderBy('name')->get(['id', 'name', 'type', 'status', 'serial', 'model', 'supporting_contract_id']);

            return response()->json($allAssets);
        }

        $assets = $this->assetService->getPaginatedAssets($filters);
        $locations = $this->assetService->getLocationsForFilter();
        $contacts = $this->assetService->getContactsForFilter();

        return view('assets.client.index', compact('assets', 'client', 'locations', 'contacts'));
    }

    public function clientCreate(\App\Models\Client $client)
    {
        $this->authorize('create', Asset::class);

        $locations = $this->assetService->getLocationsForFilter();

        return view('assets.client.create', compact('client', 'locations'));
    }

    public function clientStore(StoreAssetRequest $request, \App\Models\Client $client)
    {
        $data = $request->validated();
        $data['client_id'] = $client->id;

        $asset = $this->assetService->create($data);

        return redirect()->route('clients.assets.show', [$client, $asset])
            ->with('success', 'Asset created successfully.');
    }

    public function clientShow(\App\Models\Client $client, Asset $asset)
    {
        $this->authorize('view', $asset);

        // Ensure asset belongs to the specified client
        if ($asset->client_id !== $client->id) {
            abort(404);
        }

        $asset = $this->assetService->getAssetWithRelationships($asset);

        return view('assets.client.show', compact('client', 'asset'));
    }

    public function clientEdit(\App\Models\Client $client, Asset $asset)
    {
        $this->authorize('update', $asset);

        // Ensure asset belongs to the specified client
        if ($asset->client_id !== $client->id) {
            abort(404);
        }

        $locations = $this->assetService->getLocationsForFilter();

        return view('assets.client.edit', compact('client', 'asset', 'locations'));
    }

    public function clientUpdate(UpdateAssetRequest $request, \App\Models\Client $client, Asset $asset)
    {
        $this->authorize('update', $asset);

        // Ensure asset belongs to the specified client
        if ($asset->client_id !== $client->id) {
            abort(404);
        }

        $asset = $this->assetService->update($asset, $request->validated());

        return redirect()->route('clients.assets.show', [$client, $asset])
            ->with('success', 'Asset updated successfully.');
    }

    public function clientDestroy(\App\Models\Client $client, Asset $asset)
    {
        $this->authorize('delete', $asset);

        // Ensure asset belongs to the specified client
        if ($asset->client_id !== $client->id) {
            abort(404);
        }

        $this->assetService->archive($asset);

        return redirect()->route('clients.assets.index', $client)
            ->with('success', 'Asset archived successfully.');
    }

    // Additional asset management methods

    public function bulkUpdate(Request $request)
    {
        $this->authorize('viewAny', Asset::class);

        $request->validate([
            'action' => 'required|in:update_location,update_contact,update_status,archive',
            'asset_ids' => 'required|array',
            'asset_ids.*' => 'exists:assets,id',
        ]);

        $assets = Asset::whereIn('id', $request->asset_ids)
            ->where('company_id', auth()->user()->company_id)
            ->get();

        $count = 0;
        foreach ($assets as $asset) {
            if (auth()->user()->can('update', $asset)) {
                switch ($request->action) {
                    case 'update_location':
                        if ($request->location_id) {
                            $asset->update(['location_id' => $request->location_id]);
                        }
                        break;
                    case 'update_contact':
                        if ($request->contact_id) {
                            $asset->update(['contact_id' => $request->contact_id]);
                        }
                        break;
                    case 'update_status':
                        if ($request->status) {
                            $this->assetService->updateStatus($asset, $request->status);
                        }
                        break;
                    case 'archive':
                        $this->assetService->archive($asset);
                        break;
                }
                $count++;
            }
        }

        return redirect()->route('assets.index')
            ->with('success', "Successfully processed {$count} assets.");
    }

    public function qrCode(Asset $asset)
    {
        $this->authorize('view', $asset);

        // Generate QR code for asset
        $qrCodeUrl = route('assets.show', $asset);

        return response()->json([
            'url' => $qrCodeUrl,
            'asset' => $asset->name,
        ]);
    }

    public function label(Asset $asset)
    {
        $this->authorize('view', $asset);

        // Generate QR code for asset label
        $qrCodeUrl = route('assets.show', $asset);
        $qrCode = $this->generateQrCode($qrCodeUrl);

        // Generate printable label for asset
        return view('assets.label', compact('asset', 'qrCode'));
    }

    public function archive(Asset $asset)
    {
        $this->authorize('delete', $asset);

        $this->assetService->archive($asset);

        return redirect()->route('assets.index')
            ->with('success', 'Asset archived successfully.');
    }

    public function checkInOut(Asset $asset, Request $request)
    {
        $this->authorize('update', $asset);

        $request->validate([
            'action' => 'required|in:check_in,check_out',
            'contact_id' => 'nullable|exists:contacts,id',
        ]);

        $checkOut = $request->action === 'check_out';
        $this->assetService->checkInOut($asset, $checkOut, $request->contact_id);

        $message = $checkOut ? 'Asset checked out successfully.' : 'Asset checked in successfully.';

        return redirect()->route('assets.show', $asset)
            ->with('success', $message);
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Asset::class);

        $filters = $request->only(['search', 'type', 'status', 'location_id']);
        $filters = $this->addClientToFilters($filters);
        $assets = $this->assetService->getPaginatedAssets($filters, 10000); // Get all for export

        // Log export activity
        \Log::info('Assets export initiated', [
            'user_id' => auth()->id(),
            'company_id' => auth()->user()->company_id,
            'filters' => $filters,
            'asset_count' => $assets->count(),
        ]);

        // Define CSV headers
        $headers = [
            'ID',
            'Name',
            'Type',
            'Make',
            'Model',
            'Serial Number',
            'Asset Tag',
            'Status',
            'Client',
            'Location',
            'IP Address',
            'MAC Address',
            'Operating System',
            'Purchase Date',
            'Warranty Expiry',
            'Install Date',
            'Next Maintenance',
            'Description',
            'Notes',
            'Created At',
            'Updated At',
        ];

        // Generate CSV content
        $csvData = [];
        $csvData[] = $headers;

        foreach ($assets as $asset) {
            $csvData[] = [
                $asset->id,
                $asset->name ?: '',
                $asset->type ?: '',
                $asset->make ?: '',
                $asset->model ?: '',
                $asset->serial ?: '',
                $asset->asset_tag ?: '',
                $asset->status ?: '',
                $asset->client ? $asset->client->name : '',
                $asset->location ? $asset->location->name : '',
                $asset->ip ?: '',
                $asset->mac ?: '',
                $asset->os ?: '',
                $asset->purchase_date ? $asset->purchase_date->format('Y-m-d') : '',
                $asset->warranty_expire ? $asset->warranty_expire->format('Y-m-d') : '',
                $asset->install_date ? $asset->install_date->format('Y-m-d') : '',
                $asset->next_maintenance_date ? $asset->next_maintenance_date->format('Y-m-d') : '',
                $asset->description ?: '',
                $asset->notes ? strip_tags($asset->notes) : '', // Strip HTML tags from notes
                $asset->created_at->format('Y-m-d H:i:s'),
                $asset->updated_at->format('Y-m-d H:i:s'),
            ];
        }

        // Create CSV content
        $filename = 'assets_export_'.now()->format('Y-m-d_H-i-s').'.csv';

        $callback = function () use ($csvData) {
            $file = fopen('php://output', 'w');

            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream($callback, 200, $headers);
    }

    public function importForm()
    {
        $this->authorize('create', Asset::class);

        return view('assets.import');
    }

    public function import(Request $request)
    {
        $this->authorize('create', Asset::class);

        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        return redirect()->route('assets.index')
            ->with('info', 'Import functionality not implemented yet.');
    }

    public function downloadTemplate()
    {
        $this->authorize('create', Asset::class);

        return response()->json([
            'message' => 'Template download functionality not implemented yet',
        ]);
    }

    public function checkinoutManagement()
    {
        $this->authorize('viewAny', Asset::class);

        // Get dashboard analytics
        $analytics = $this->assetService->getAnalytics();

        // Get recent activity (last 10 check-in/out operations)
        $recentActivity = $this->assetService->getRecentActivity(10);

        // Get assets by status for quick access
        $availableAssets = $this->assetService->getAssetsByStatus('Ready To Deploy');
        $checkedOutAssets = $this->assetService->getAssetsByStatus('Deployed');

        // Get filter options
        $clients = $this->assetService->getClientsForFilter();
        $locations = $this->assetService->getLocationsForFilter();
        $contacts = $this->assetService->getContactsForFilter();

        return view('assets.checkinout', compact(
            'analytics',
            'recentActivity',
            'availableAssets',
            'checkedOutAssets',
            'clients',
            'locations',
            'contacts'
        ));
    }

    public function bulk()
    {
        $this->authorize('viewAny', Asset::class);

        return view('assets.bulk');
    }

    public function bulkCheckinout(Request $request)
    {
        $this->authorize('viewAny', Asset::class);

        $request->validate([
            'action' => 'required|in:check_in,check_out',
            'asset_ids' => 'required|array|min:1',
            'asset_ids.*' => 'exists:assets,id',
            'contact_id' => 'nullable|exists:contacts,id',
        ]);

        $checkOut = $request->action === 'check_out';
        $results = $this->assetService->bulkCheckInOut(
            $request->asset_ids,
            $checkOut,
            $request->contact_id
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Successfully processed {$results['success']} assets.",
                'results' => $results,
            ]);
        }

        $message = "Successfully processed {$results['success']} assets.";
        if ($results['failed'] > 0) {
            $message .= " {$results['failed']} failed.";
        }

        return redirect()->route('assets.checkinout')
            ->with('success', $message);
    }

    public function getAssetsByFilter(Request $request)
    {
        $this->authorize('viewAny', Asset::class);

        $filters = $request->only(['status', 'type', 'location_id']);
        $filters = $this->addClientToFilters($filters);
        $assets = $this->assetService->getAssetsForBulkOperations($filters);

        return response()->json($assets->map(function ($asset) {
            // Parse RMM data from notes to get connectivity status
            $rmmData = null;
            if ($asset->notes) {
                try {
                    $rmmData = json_decode($asset->notes, true);
                } catch (\Exception $e) {
                    $rmmData = null;
                }
            }

            return [
                'id' => $asset->id,
                'name' => $asset->name,
                'type' => $asset->type,
                'status' => $asset->status,
                'status_color' => $asset->status_color,
                'client' => $asset->client ? $asset->client->name : null,
                'contact' => $asset->contact ? $asset->contact->name : null,
                'location' => $asset->location ? $asset->location->name : null,
                'serial' => $asset->serial,
                'rmm_online' => $rmmData['rmm_online'] ?? null, // Include RMM connectivity status
                'can_check_out' => $asset->status === 'Ready To Deploy' && $asset->type !== 'Server',
                'can_check_in' => $asset->status === 'Deployed' && $asset->type !== 'Server',
            ];
        }));
    }

    public function getMetrics()
    {
        $this->authorize('viewAny', Asset::class);

        $metrics = $this->assetService->getCheckInOutMetrics();

        return response()->json($metrics);
    }

    /**
     * Get assets for a specific client (API endpoint)
     */
    public function clientAssetsApi(Request $request, \App\Models\Client $client)
    {
        try {
            // Ensure client belongs to current company (middleware should handle this but double-check)
            if ($client->company_id !== auth()->user()->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to client data',
                    'data' => [],
                ], 403);
            }

            // Get assets for the client
            $assets = Asset::where('company_id', auth()->user()->company_id)
                ->where('client_id', $client->id)
                ->select(['id', 'name', 'type', 'ip', 'status', 'supporting_contract_id', 'make', 'model'])
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $assets,
                'client' => [
                    'id' => $client->id,
                    'name' => $client->name,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch client assets for API', [
                'client_id' => $client->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch client assets: '.$e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /**
     * Generate QR code for the given URL.
     */
    protected function generateQrCode(string $url): string
    {
        try {
            $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd
            );

            $writer = new \BaconQrCode\Writer($renderer);
            $qrCode = $writer->writeString($url);

            return $qrCode;
        } catch (\Exception $e) {
            // Fallback if QR code generation fails
            return '<div class="text-center p-4 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 rounded">
                        <i class="fas fa-qrcode fa-3x text-gray-400 dark:text-gray-500"></i>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">QR Code unavailable</p>
                    </div>';
        }
    }
}
