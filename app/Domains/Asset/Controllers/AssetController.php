<?php

namespace App\Domains\Asset\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $assets = Asset::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->when($request->get('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('serial_number', 'like', "%{$search}%")
                      ->orWhere('asset_tag', 'like', "%{$search}%");
                });
            })
            ->when($request->get('type'), function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($request->get('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->get('client_id'), function ($query, $client_id) {
                $query->where('client_id', $client_id);
            })
            ->when($request->get('location_id'), function ($query, $location_id) {
                $query->where('location_id', $location_id);
            })
            ->with(['client', 'location'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get clients for filter dropdown
        $clients = Client::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // Get locations for filter dropdown
        $locations = Location::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get();

        return view('assets.index', compact('assets', 'clients', 'locations'));
    }

    public function create()
    {
        return view('assets.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'serial_number' => 'nullable|string|max:255',
            'asset_tag' => 'nullable|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'location_id' => 'nullable|exists:locations,id',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'warranty_expires' => 'nullable|date',
            'status' => 'required|in:active,inactive,maintenance,retired',
            'notes' => 'nullable|string'
        ]);

        $validated['company_id'] = Auth::user()->company_id;
        
        $asset = Asset::create($validated);

        return redirect()->route('assets.show', $asset)
            ->with('success', 'Asset created successfully.');
    }

    public function show(Asset $asset)
    {
        $this->authorize('view', $asset);
        
        $asset->load([
            'client',
            'location',
            'warranties',
            'maintenances',
            'depreciations'
        ]);

        return view('assets.show', compact('asset'));
    }

    public function edit(Asset $asset)
    {
        $this->authorize('update', $asset);
        
        return view('assets.edit', compact('asset'));
    }

    public function update(Request $request, Asset $asset)
    {
        $this->authorize('update', $asset);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'serial_number' => 'nullable|string|max:255',
            'asset_tag' => 'nullable|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'location_id' => 'nullable|exists:locations,id',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'warranty_expires' => 'nullable|date',
            'status' => 'required|in:active,inactive,maintenance,retired',
            'notes' => 'nullable|string'
        ]);

        $asset->update($validated);

        return redirect()->route('assets.show', $asset)
            ->with('success', 'Asset updated successfully.');
    }

    public function destroy(Asset $asset)
    {
        $this->authorize('delete', $asset);

        $asset->archived_at = now();
        $asset->save();

        return redirect()->route('assets.index')
            ->with('success', 'Asset archived successfully.');
    }
}