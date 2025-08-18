<?php

namespace App\Domains\Asset\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Domains\Asset\Services\AssetService;
use App\Domains\Asset\Requests\StoreAssetRequest;
use App\Domains\Asset\Requests\UpdateAssetRequest;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function __construct(
        private AssetService $assetService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'type', 'status', 'client_id', 'location_id']);
        
        $assets = $this->assetService->getPaginatedAssets($filters);
        $clients = $this->assetService->getClientsForFilter();
        $locations = $this->assetService->getLocationsForFilter();
        $contacts = $this->assetService->getContactsForFilter();

        return view('assets.index', compact('assets', 'clients', 'locations', 'contacts'));
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

        return view('assets.show', compact('asset'));
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
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('serial', 'like', "%{$search}%")
                      ->orWhere('model', 'like', "%{$search}%")
                      ->orWhere('make', 'like', "%{$search}%");
                });
            }
            
            // Apply type filter
            if (!empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }
            
            // Apply status filter
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            
            // Apply location filter
            if (!empty($filters['location_id'])) {
                $query->where('location_id', $filters['location_id']);
            }
            
            $allAssets = $query->orderBy('name')->get(['id', 'name', 'type', 'status', 'serial', 'model']);
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
            'asset' => $asset->name
        ]);
    }

    public function label(Asset $asset)
    {
        $this->authorize('view', $asset);
        
        // Generate printable label for asset
        return view('assets.label', compact('asset'));
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
            'contact_id' => 'nullable|exists:contacts,id'
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
        
        $filters = $request->only(['search', 'type', 'status', 'client_id', 'location_id']);
        $assets = $this->assetService->getPaginatedAssets($filters, 1000); // Get all for export
        
        return response()->json([
            'message' => 'Export functionality not implemented yet',
            'count' => $assets->count()
        ]);
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
            'file' => 'required|file|mimes:csv,txt'
        ]);
        
        return redirect()->route('assets.index')
            ->with('info', 'Import functionality not implemented yet.');
    }

    public function downloadTemplate()
    {
        $this->authorize('create', Asset::class);
        
        return response()->json([
            'message' => 'Template download functionality not implemented yet'
        ]);
    }

    public function checkinoutManagement()
    {
        $this->authorize('viewAny', Asset::class);
        
        return view('assets.checkinout');
    }

    public function bulk()
    {
        $this->authorize('viewAny', Asset::class);
        
        return view('assets.bulk');
    }
}