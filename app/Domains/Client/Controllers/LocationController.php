<?php

namespace App\Domains\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Location;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    /**
     * LocationController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('company');
        
        // Apply permission-based middleware
        $this->middleware('permission:clients.locations.view')->only(['index', 'show']);
        $this->middleware('permission:clients.locations.manage')->only(['create', 'store', 'edit', 'update']);
        $this->middleware('permission:clients.locations.manage')->only(['destroy']);
        $this->middleware('permission:clients.locations.export')->only(['export']);
    }

    /**
     * Display a listing of locations for a specific client (client-centric view)
     */
    public function index(Request $request, Client $client)
    {
        // Authorize client access
        $this->authorize('view', $client);
        
        // Additional authorization check
        if (!auth()->user()->hasPermission('clients.locations.view')) {
            abort(403, 'Insufficient permissions to view locations');
        }

        // Query locations for the specific client only
        $query = Location::with('contact')->where('client_id', $client->id);

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address_line_1', 'like', "%{$search}%")
                  ->orWhere('address_line_2', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('state', 'like', "%{$search}%")
                  ->orWhere('zip_code', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%");
            });
        }

        // Apply primary filter
        if ($request->get('primary_only')) {
            $query->where('primary', true);
        }

        // Apply state filter
        if ($state = $request->get('state')) {
            $query->where('state', $state);
        }

        // Apply country filter
        if ($country = $request->get('country')) {
            $query->where('country', $country);
        }

        $locations = $query->orderBy('primary', 'desc')
                          ->orderBy('name')
                          ->paginate(20)
                          ->appends($request->query());

        // Get unique states and countries for filters (for this client)
        $states = Location::where('client_id', $client->id)
                  ->whereNotNull('state')
                  ->distinct()
                  ->orderBy('state')
                  ->pluck('state');

        $countries = Location::where('client_id', $client->id)
                     ->whereNotNull('country')
                     ->distinct()
                     ->orderBy('country')
                     ->pluck('country');

        return view('clients.locations.index', compact('locations', 'client', 'states', 'countries'));
    }

    /**
     * Show the form for creating a new location for a specific client
     */
    public function create(Request $request, Client $client)
    {
        // Authorize client access
        $this->authorize('view', $client);
        
        // Additional authorization check
        if (!auth()->user()->hasPermission('clients.locations.manage')) {
            abort(403, 'Insufficient permissions to create locations');
        }

        // Get contacts for this client
        $contacts = Contact::where('client_id', $client->id)->orderBy('name')->get();

        return view('clients.locations.create', compact('client', 'contacts'));
    }

    /**
     * Store a newly created location for a specific client
     */
    public function store(Request $request, Client $client)
    {
        // Authorize client access
        $this->authorize('view', $client);
        
        // Additional authorization check
        if (!auth()->user()->hasPermission('clients.locations.manage')) {
            abort(403, 'Insufficient permissions to create locations');
        }

        $validator = Validator::make($request->all(), [
            'contact_id' => [
                'nullable',
                'exists:client_contacts,id',
                Rule::exists('client_contacts', 'id')->where(function ($query) use ($client) {
                    $query->where('client_id', $client->id);
                }),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'required|string|max:100',
            'phone' => 'nullable|string|max:50',
            'hours' => 'nullable|string',
            'primary' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $location = new Location($request->all());
        $location->client_id = $client->id;
        $location->company_id = auth()->user()->company_id;
        $location->save();

        // If this is set as primary, unset other primary locations for this client
        if ($location->primary) {
            Location::where('client_id', $client->id)
                        ->where('id', '!=', $location->id)
                        ->update(['primary' => false]);
        }

        return redirect()->route('clients.locations.index', $client)
                        ->with('success', 'Location created successfully.');
    }

    /**
     * Display the specified location for a specific client
     */
    public function show(Client $client, Location $location)
    {
        // Verify location belongs to client
        if ($location->client_id !== $client->id) {
            abort(404, 'Location not found for this client');
        }
        
        // Authorize access
        $this->authorize('view', $client);
        $this->authorize('view', $location);
        
        // Additional permission check
        if (!auth()->user()->hasPermission('clients.locations.view')) {
            abort(403, 'Insufficient permissions to view locations');
        }

        $location->load('client', 'contact');

        return view('clients.locations.show', compact('location', 'client'));
    }

    /**
     * Show the form for editing the specified location for a specific client
     */
    public function edit(Client $client, Location $location)
    {
        // Verify location belongs to client
        if ($location->client_id !== $client->id) {
            abort(404, 'Location not found for this client');
        }
        
        // Authorize access
        $this->authorize('view', $client);
        $this->authorize('update', $location);
        
        // Additional permission check
        if (!auth()->user()->hasPermission('clients.locations.manage')) {
            abort(403, 'Insufficient permissions to edit locations');
        }

        $contacts = Contact::where('client_id', $client->id)
                                ->orderBy('name')
                                ->get();

        return view('clients.locations.edit', compact('location', 'client', 'contacts'));
    }

    /**
     * Update the specified location for a specific client
     */
    public function update(Request $request, Client $client, Location $location)
    {
        // Verify location belongs to client
        if ($location->client_id !== $client->id) {
            abort(404, 'Location not found for this client');
        }
        
        // Authorize access
        $this->authorize('view', $client);
        $this->authorize('update', $location);
        
        // Additional permission check
        if (!auth()->user()->hasPermission('clients.locations.manage')) {
            abort(403, 'Insufficient permissions to update locations');
        }

        $validator = Validator::make($request->all(), [
            'contact_id' => [
                'nullable',
                'exists:client_contacts,id',
                Rule::exists('client_contacts', 'id')->where(function ($query) use ($client) {
                    $query->where('client_id', $client->id);
                }),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'required|string|max:100',
            'phone' => 'nullable|string|max:50',
            'hours' => 'nullable|string',
            'primary' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $location->fill($request->all());
        $location->save();

        // If this is set as primary, unset other primary locations for this client
        if ($location->primary) {
            Location::where('client_id', $client->id)
                        ->where('id', '!=', $location->id)
                        ->update(['primary' => false]);
        }

        return redirect()->route('clients.locations.index', $client)
                        ->with('success', 'Location updated successfully.');
    }

    /**
     * Remove the specified location for a specific client
     */
    public function destroy(Client $client, Location $location)
    {
        // Verify location belongs to client
        if ($location->client_id !== $client->id) {
            abort(404, 'Location not found for this client');
        }
        
        // Authorize access
        $this->authorize('view', $client);
        $this->authorize('delete', $location);
        
        // Additional permission check
        if (!auth()->user()->hasPermission('clients.locations.manage')) {
            abort(403, 'Insufficient permissions to delete locations');
        }

        $location->delete();

        return redirect()->route('clients.locations.index', $client)
                        ->with('success', 'Location deleted successfully.');
    }

    /**
     * Export locations for a specific client to CSV
     */
    public function export(Request $request, Client $client)
    {
        // Authorize client access
        $this->authorize('view', $client);
        
        // Authorization check for export permission
        if (!auth()->user()->hasPermission('clients.locations.export')) {
            abort(403, 'Insufficient permissions to export location data');
        }
        
        // Additional gate check for sensitive data export
        if (!auth()->user()->can('export-client-data')) {
            abort(403, 'Export permissions denied');
        }

        $query = Location::with('contact')->where('client_id', $client->id);

        // Apply same filters as index
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address_line_1', 'like', "%{$search}%")
                  ->orWhere('address_line_2', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('state', 'like', "%{$search}%")
                  ->orWhere('zip_code', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%");
            });
        }

        if ($request->get('primary_only')) {
            $query->where('primary', true);
        }

        if ($state = $request->get('state')) {
            $query->where('state', $state);
        }

        if ($country = $request->get('country')) {
            $query->where('country', $country);
        }

        $locations = $query->orderBy('primary', 'desc')->orderBy('name')->get();

        $filename = 'locations_' . $client->name . '_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($locations, $client) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Location Name',
                'Description',
                'Client Name',
                'Contact Name',
                'Address Line 1',
                'Address Line 2',
                'City',
                'State',
                'ZIP Code',
                'Country',
                'Phone',
                'Hours',
                'Primary',
                'Notes'
            ]);

            // CSV data
            foreach ($locations as $location) {
                fputcsv($file, [
                    $location->name,
                    $location->description,
                    $client->name,
                    $location->contact ? $location->contact->name : '',
                    $location->address_line_1,
                    $location->address_line_2,
                    $location->city,
                    $location->state,
                    $location->zip_code,
                    $location->country,
                    $location->phone,
                    $location->hours,
                    $location->primary ? 'Yes' : 'No',
                    $location->notes,
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get contacts for a specific client (AJAX endpoint)
     */
    public function getContacts(Client $client)
    {
        // Authorize client access
        $this->authorize('view', $client);

        $contacts = Contact::where('client_id', $client->id)
                                ->orderBy('name')
                                ->get(['id', 'name', 'title']);

        return response()->json($contacts);
    }
}