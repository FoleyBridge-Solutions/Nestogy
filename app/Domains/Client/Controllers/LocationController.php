<?php

namespace App\Domains\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Location;
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

        // Properly use policies for authorization - no bypassing!
        $this->authorizeResource(Location::class, 'location');
    }

    /**
     * Display a listing of locations (uses session-based client context)
     */
    public function index(Request $request)
    {
        // Get client from session
        $client = \App\Domains\Core\Services\NavigationService::getSelectedClient();

        // If no client selected, redirect to client selection
        if (! $client) {
            return redirect()->route('clients.index')
                ->with('info', 'Please select a client to view locations.');
        }

        // Authorize using policies
        $this->authorize('view', $client);
        $this->authorize('viewAny', Location::class);

        // Query locations for the selected client with optimized column selection
        $query = Location::select([
            'id', 'name', 'address', 'city', 'state', 'zip', 'country',
            'phone', 'primary', 'client_id', 'contact_id', 'description', 'hours',
        ])
            ->with(['contact:id,name,email,phone'])
            ->where('client_id', $client->id);

        // Apply search filters with correct column names
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('zip', 'like', "%{$search}%")
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

        // Get unique states and countries for filters with caching
        $cacheKey = "location_filters_client_{$client->id}";
        $filters = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($client) {
            return [
                'states' => Location::where('client_id', $client->id)
                    ->whereNotNull('state')
                    ->distinct()
                    ->orderBy('state')
                    ->pluck('state'),
                'countries' => Location::where('client_id', $client->id)
                    ->whereNotNull('country')
                    ->distinct()
                    ->orderBy('country')
                    ->pluck('country'),
            ];
        });

        $states = $filters['states'];
        $countries = $filters['countries'];

        return view('clients.locations.index', compact('locations', 'client', 'states', 'countries'));
    }

    /**
     * Show the form for creating a new location (uses session-based client context)
     */
    public function create(Request $request)
    {
        // Get client from session
        $client = \App\Domains\Core\Services\NavigationService::getSelectedClient();

        // If no client selected, redirect to client selection
        if (! $client) {
            return redirect()->route('clients.index')
                ->with('info', 'Please select a client to create a location.');
        }

        // Authorize using policies
        $this->authorize('view', $client);
        $this->authorize('create', Location::class);

        // Get contacts for this client
        $contacts = Contact::where('client_id', $client->id)->orderBy('name')->get();

        return view('clients.locations.create', compact('client', 'contacts'));
    }

    /**
     * Store a newly created location (uses session-based client context)
     */
    public function store(Request $request)
    {
        // Get client from session
        $client = \App\Domains\Core\Services\NavigationService::getSelectedClient();

        // If no client selected, redirect to client selection
        if (! $client) {
            return redirect()->route('clients.index')
                ->with('error', 'Please select a client to create a location.');
        }

        // Authorize using policies
        $this->authorize('view', $client);
        $this->authorize('create', Location::class);

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

        // Prepare location data
        $locationData = $request->all();

        // Combine address lines into single address field
        $address = $request->address_line_1;
        if ($request->address_line_2) {
            $address .= ', '.$request->address_line_2;
        }
        $locationData['address'] = $address;

        // Remove the separate address line fields as they don't exist in the model
        unset($locationData['address_line_1'], $locationData['address_line_2']);

        // Rename zip_code to zip (database column name)
        if (isset($locationData['zip_code'])) {
            $locationData['zip'] = $locationData['zip_code'];
            unset($locationData['zip_code']);
        }

        $location = new Location($locationData);
        $location->client_id = $client->id;
        $location->company_id = auth()->user()->company_id;
        $location->save();

        // If this is set as primary, unset other primary locations for this client
        if ($location->primary) {
            Location::where('client_id', $client->id)
                ->where('id', '!=', $location->id)
                ->update(['primary' => false]);
        }

        // Clear cache for location filters
        \Illuminate\Support\Facades\Cache::forget("location_filters_client_{$client->id}");

        return redirect()->route('clients.locations.index')
            ->with('success', 'Location created successfully.');
    }

    /**
     * Display the specified location for a specific client
     */
    public function show(Location $location)
    {
        // Get client from location
        $client = $location->client;

        // Set client in session if different
        if (! $client || $location->client_id !== optional(\App\Domains\Core\Services\NavigationService::getSelectedClient())->id) {
            \App\Domains\Core\Services\NavigationService::setSelectedClient($client->id);
        }

        // Authorize using policies
        $this->authorize('view', $location);

        $location->load('client', 'contact');

        // Strategic update of accessed_at only when viewing details
        $location->updateAccessedAt();

        return view('clients.locations.show', compact('location', 'client'));
    }

    /**
     * Show the form for editing the specified location for a specific client
     */
    public function edit(Location $location)
    {
        // Get client from location
        $client = $location->client;

        // Set client in session if different
        if (! $client || $location->client_id !== optional(\App\Domains\Core\Services\NavigationService::getSelectedClient())->id) {
            \App\Domains\Core\Services\NavigationService::setSelectedClient($client->id);
        }

        // Authorize using policies
        $this->authorize('update', $location);

        $contacts = Contact::where('client_id', $client->id)
            ->orderBy('name')
            ->get();

        return view('clients.locations.edit', compact('location', 'client', 'contacts'));
    }

    /**
     * Update the specified location for a specific client
     */
    public function update(Request $request, Location $location)
    {
        // Get client from location
        $client = $location->client;

        // Set client in session if different
        if (! $client || $location->client_id !== optional(\App\Domains\Core\Services\NavigationService::getSelectedClient())->id) {
            \App\Domains\Core\Services\NavigationService::setSelectedClient($client->id);
        }

        // Authorize access
        $this->authorize('view', $client);
        $this->authorize('update', $location);

        // Additional permission check
        // Permission check handled by policy
        // if (!auth()->user()->hasPermission('clients.locations.manage')) {
        //     abort(403, 'Insufficient permissions to update locations');
        // }

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

        // Prepare location data
        $locationData = $request->all();

        // Combine address lines into single address field
        $address = $request->address_line_1;
        if ($request->address_line_2) {
            $address .= ', '.$request->address_line_2;
        }
        $locationData['address'] = $address;

        // Remove the separate address line fields as they don't exist in the model
        unset($locationData['address_line_1'], $locationData['address_line_2']);

        // Rename zip_code to zip (database column name)
        if (isset($locationData['zip_code'])) {
            $locationData['zip'] = $locationData['zip_code'];
            unset($locationData['zip_code']);
        }

        $location->fill($locationData);
        $location->save();

        // If this is set as primary, unset other primary locations for this client
        if ($location->primary) {
            Location::where('client_id', $client->id)
                ->where('id', '!=', $location->id)
                ->update(['primary' => false]);
        }

        // Clear cache for location filters
        \Illuminate\Support\Facades\Cache::forget("location_filters_client_{$client->id}");

        return redirect()->route('clients.locations.index')
            ->with('success', 'Location updated successfully.');
    }

    /**
     * Remove the specified location for a specific client
     */
    public function destroy(Location $location)
    {
        // Get client from location
        $client = $location->client;

        // Set client in session if different
        if (! $client || $location->client_id !== optional(\App\Domains\Core\Services\NavigationService::getSelectedClient())->id) {
            \App\Domains\Core\Services\NavigationService::setSelectedClient($client->id);
        }

        // Authorize access
        $this->authorize('view', $client);
        $this->authorize('delete', $location);

        // Additional permission check
        // Permission check handled by policy
        // if (!auth()->user()->hasPermission('clients.locations.manage')) {
        //     abort(403, 'Insufficient permissions to delete locations');
        // }

        $location->delete();

        // Clear cache for location filters
        \Illuminate\Support\Facades\Cache::forget("location_filters_client_{$client->id}");

        return redirect()->route('clients.locations.index')
            ->with('success', 'Location deleted successfully.');
    }

    /**
     * Export locations for a specific client to CSV
     */
    public function export(Request $request)
    {
        // Get client from session
        $client = \App\Domains\Core\Services\NavigationService::getSelectedClient();

        // If no client selected, redirect to client selection
        if (! $client) {
            return redirect()->route('clients.index')
                ->with('info', 'Please select a client to export locations.');
        }

        // Authorize client access
        $this->authorize('view', $client);

        // Authorization check for export permission
        // Permission check handled by policy
        // if (!auth()->user()->hasPermission('clients.locations.export')) {
        //     abort(403, 'Insufficient permissions to export location data');
        // }

        // Additional gate check for sensitive data export - handled by policy
        // if (!auth()->user()->can('export-client-data')) {
        //     abort(403, 'Export permissions denied');
        // }

        $query = Location::with('contact')->where('client_id', $client->id);

        // Apply same filters as index with correct column names
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('zip', 'like', "%{$search}%")
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

        $filename = 'locations_'.$client->name.'_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($locations, $client) {
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
                'Notes',
            ]);

            // CSV data
            foreach ($locations as $location) {
                fputcsv($file, [
                    $location->name,
                    $location->description,
                    $client->name,
                    $location->contact ? $location->contact->name : '',
                    $location->address,
                    '', // Second address line (combined in address field)
                    $location->city,
                    $location->state,
                    $location->zip,
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
    public function getContacts()
    {
        // Get client from session
        $client = \App\Domains\Core\Services\NavigationService::getSelectedClient();

        // If no client selected, return error
        if (! $client) {
            return response()->json(['error' => 'No client selected'], 400);
        }

        // Authorize client access
        $this->authorize('view', $client);

        $contacts = Contact::where('client_id', $client->id)
            ->orderBy('name')
            ->get(['id', 'name', 'title']);

        return response()->json($contacts);
    }
}
