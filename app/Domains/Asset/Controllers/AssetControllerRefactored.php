<?php

namespace App\Domains\Asset\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Asset;
use App\Domains\Asset\Services\AssetService;
use App\Domains\Asset\Requests\StoreAssetRequest;
use App\Domains\Asset\Requests\UpdateAssetRequest;
use Illuminate\Http\Request;

class AssetControllerRefactored extends BaseController
{
    protected function initializeController(): void
    {
        $this->modelClass = Asset::class;
        $this->serviceClass = AssetService::class;
        $this->resourceName = 'assets';
        $this->viewPrefix = 'assets';
        $this->eagerLoadRelations = ['client', 'location', 'contact'];
    }

    protected function getFilters(Request $request): array
    {
        return $request->only(['search', 'type', 'status', 'client_id', 'location_id']);
    }

    protected function getIndexViewData(Request $request): array
    {
        $service = app($this->serviceClass);
        
        return [
            'clients' => $service->getClientsForFilter(),
            'locations' => $service->getLocationsForFilter(),
            'contacts' => $service->getContactsForFilter(),
        ];
    }

    protected function getShowViewData(\Illuminate\Database\Eloquent\Model $model): array
    {
        $service = app($this->serviceClass);
        
        return [
            'asset' => $service->getAssetWithRelationships($model),
        ];
    }

    protected function applyCustomFilters($query, Request $request)
    {
        // Apply client filter
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->get('client_id'));
        }

        // Apply location filter  
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->get('location_id'));
        }

        return $query;
    }

    // Client-scoped asset methods (these remain as custom methods)
    public function clientIndex(Request $request, \App\Models\Client $client)
    {
        $this->authorize('viewAny', Asset::class);
        
        $filters = array_merge(
            $request->only(['search', 'type', 'status', 'location_id']),
            ['client_id' => $client->id]
        );
        
        $service = app($this->serviceClass);
        $assets = $service->getPaginatedAssets($filters);
        $locations = $service->getLocationsForFilter();
        $contacts = $service->getContactsForFilter();

        return view('assets.client.index', compact('assets', 'client', 'locations', 'contacts'));
    }

    public function clientCreate(\App\Models\Client $client)
    {
        $this->authorize('create', Asset::class);
        
        $service = app($this->serviceClass);
        $locations = $service->getLocationsForFilter();
        
        return view('assets.client.create', compact('client', 'locations'));
    }

    public function clientStore(StoreAssetRequest $request, \App\Models\Client $client)
    {
        $data = $request->validated();
        $data['client_id'] = $client->id;
        
        $service = app($this->serviceClass);
        $asset = $service->create($data);

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
        
        $service = app($this->serviceClass);
        $asset = $service->getAssetWithRelationships($asset);

        return view('assets.client.show', compact('client', 'asset'));
    }

    // Additional asset management methods that don't fit base patterns
    public function bulkUpdate(Request $request)
    {
        $this->authorize('update', Asset::class);

        $request->validate([
            'action' => 'required|in:update_location,update_contact,update_status,archive',
            'asset_ids' => 'required|array',
            'asset_ids.*' => 'exists:assets,id',
        ]);

        $service = app($this->serviceClass);
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
                            $service->updateStatus($asset, $request->status);
                        }
                        break;
                    case 'archive':
                        $service->archive($asset);
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
        
        $qrCodeUrl = route('assets.show', $asset);
        
        return response()->json([
            'url' => $qrCodeUrl,
            'asset' => $asset->name
        ]);
    }

    public function checkInOut(Asset $asset, Request $request)
    {
        $this->authorize('update', $asset);
        
        $request->validate([
            'action' => 'required|in:check_in,check_out',
            'contact_id' => 'nullable|exists:contacts,id'
        ]);
        
        $service = app($this->serviceClass);
        $checkOut = $request->action === 'check_out';
        $service->checkInOut($asset, $checkOut, $request->contact_id);
        
        $message = $checkOut ? 'Asset checked out successfully.' : 'Asset checked in successfully.';
        
        return redirect()->route('assets.show', $asset)
            ->with('success', $message);
    }
}