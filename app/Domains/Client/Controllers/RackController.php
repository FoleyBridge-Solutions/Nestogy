<?php

namespace App\Domains\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientRack;
use App\Services\NavigationService;
use App\Traits\UsesSelectedClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RackController extends Controller
{
    use UsesSelectedClient;
    /**
     * Display a listing of racks for the selected client
     */
    public function index(Request $request)
    {
        $client = $this->getSelectedClient($request);

        if (!$client) {
            return redirect()->route('clients.select-screen');
        }

        $query = $client->racks()->with(['client']);

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('rack_number', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Apply location filter
        if ($location = $request->get('location')) {
            $query->location($location);
        }

        // Apply environmental status filter
        if ($request->get('environmental_warning')) {
            $query->where(function($q) {
                $q->where('temperature_celsius', '<', 18)
                  ->orWhere('temperature_celsius', '>', 24)
                  ->orWhere('humidity_percent', '<', 40)
                  ->orWhere('humidity_percent', '>', 60);
            });
        }

        // Apply warranty expiring filter
        if ($request->get('warranty_expiring')) {
            $query->where(function($q) {
                $q->where('warranty_expiry', '<=', now()->addDays(30))
                  ->whereNotNull('warranty_expiry');
            });
        }

        $racks = $query->orderBy('created_at', 'desc')
                      ->paginate(20)
                      ->appends($request->query());

        $statuses = ClientRack::getStatuses();
        $locations = $client->racks()
                              ->whereNotNull('location')
                              ->distinct()
                              ->pluck('location')
                              ->filter()
                              ->sort()
                              ->values();

        return view('clients.racks.index', compact('racks', 'client', 'statuses', 'locations'));
    }

    /**
     * Show the form for creating a new rack
     */
    public function create(Request $request)
    {
        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $selectedClientId = $request->get('client_id');
        $statuses = ClientRack::getStatuses();
        $heights = ClientRack::getCommonHeights();
        $coolingRequirements = ClientRack::getCoolingRequirements();

        return view('clients.racks.create', compact('clients', 'selectedClientId', 'statuses', 'heights', 'coolingRequirements'));
    }

    /**
     * Store a newly created rack
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => [
                'required',
                'exists:clients,id',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'rack_number' => 'nullable|integer|min:1',
            'height_units' => 'required|integer|min:1|max:100',
            'width_inches' => 'nullable|numeric|min:0',
            'depth_inches' => 'nullable|numeric|min:0',
            'max_weight_lbs' => 'nullable|numeric|min:0',
            'power_capacity_watts' => 'required|integer|min:0',
            'power_used_watts' => 'nullable|integer|min:0|lte:power_capacity_watts',
            'cooling_requirements' => 'nullable|in:' . implode(',', array_keys(ClientRack::getCoolingRequirements())),
            'network_connections' => 'nullable|string',
            'status' => 'required|in:' . implode(',', array_keys(ClientRack::getStatuses())),
            'temperature_celsius' => 'nullable|numeric|min:-50|max:100',
            'humidity_percent' => 'nullable|numeric|min:0|max:100',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date|before_or_equal:today',
            'warranty_expiry' => 'nullable|date|after:purchase_date',
            'maintenance_schedule' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $rack = new ClientRack($request->all());
        $rack->company_id = auth()->user()->company_id;
        $rack->save();

        return redirect()->route('clients.racks.standalone.index')
                        ->with('success', 'Client rack created successfully.');
    }

    /**
     * Display the specified rack
     */
    public function show(ClientRack $rack)
    {
        $this->authorize('view', $rack);

        $rack->load('client');
        
        // Update access timestamp
        $rack->update(['accessed_at' => now()]);

        return view('clients.racks.show', compact('rack'));
    }

    /**
     * Show the form for editing the specified rack
     */
    public function edit(ClientRack $rack)
    {
        $this->authorize('update', $rack);

        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $statuses = ClientRack::getStatuses();
        $heights = ClientRack::getCommonHeights();
        $coolingRequirements = ClientRack::getCoolingRequirements();

        return view('clients.racks.edit', compact('rack', 'clients', 'statuses', 'heights', 'coolingRequirements'));
    }

    /**
     * Update the specified rack
     */
    public function update(Request $request, ClientRack $rack)
    {
        $this->authorize('update', $rack);

        $validator = Validator::make($request->all(), [
            'client_id' => [
                'required',
                'exists:clients,id',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'rack_number' => 'nullable|integer|min:1',
            'height_units' => 'required|integer|min:1|max:100',
            'width_inches' => 'nullable|numeric|min:0',
            'depth_inches' => 'nullable|numeric|min:0',
            'max_weight_lbs' => 'nullable|numeric|min:0',
            'power_capacity_watts' => 'required|integer|min:0',
            'power_used_watts' => 'nullable|integer|min:0|lte:power_capacity_watts',
            'cooling_requirements' => 'nullable|in:' . implode(',', array_keys(ClientRack::getCoolingRequirements())),
            'network_connections' => 'nullable|string',
            'status' => 'required|in:' . implode(',', array_keys(ClientRack::getStatuses())),
            'temperature_celsius' => 'nullable|numeric|min:-50|max:100',
            'humidity_percent' => 'nullable|numeric|min:0|max:100',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date|before_or_equal:today',
            'warranty_expiry' => 'nullable|date|after:purchase_date',
            'maintenance_schedule' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $rack->fill($request->all());
        $rack->save();

        return redirect()->route('clients.racks.standalone.index')
                        ->with('success', 'Client rack updated successfully.');
    }

    /**
     * Remove the specified rack
     */
    public function destroy(ClientRack $rack)
    {
        $this->authorize('delete', $rack);

        $rack->delete();

        return redirect()->route('clients.racks.standalone.index')
                        ->with('success', 'Client rack deleted successfully.');
    }

    /**
     * Export racks to CSV
     */
    public function export(Request $request)
    {
        $query = ClientRack::with(['client'])
            ->whereHas('client', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply same filters as index
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        if ($location = $request->get('location')) {
            $query->location($location);
        }

        $racks = $query->orderBy('created_at', 'desc')->get();

        $filename = 'client_racks_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($racks) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Rack Name',
                'Description',
                'Client Name',
                'Location',
                'Rack Number',
                'Height (U)',
                'Power Capacity (W)',
                'Power Used (W)',
                'Power Available (W)',
                'Status',
                'Temperature (°C)',
                'Humidity (%)',
                'Manufacturer',
                'Model',
                'Serial Number',
                'Purchase Date',
                'Warranty Expiry',
                'Created At'
            ]);

            // CSV data
            foreach ($racks as $rack) {
                fputcsv($file, [
                    $rack->name,
                    $rack->description,
                    $rack->client->display_name,
                    $rack->location,
                    $rack->rack_number,
                    $rack->height_units,
                    $rack->power_capacity_watts,
                    $rack->power_used_watts,
                    $rack->available_power,
                    $rack->status,
                    $rack->temperature_celsius,
                    $rack->humidity_percent,
                    $rack->manufacturer,
                    $rack->model,
                    $rack->serial_number,
                    $rack->purchase_date ? $rack->purchase_date->format('Y-m-d') : '',
                    $rack->warranty_expiry ? $rack->warranty_expiry->format('Y-m-d') : '',
                    $rack->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}