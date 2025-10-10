<?php

namespace App\Domains\Client\Controllers;

use App\Domains\Core\Services\NavigationService;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    private const CONTACT_NOT_FOUND_MESSAGE = 'Contact not found for this client';

    /**
     * ContactController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('company');

        // Apply permission-based middleware
        $this->middleware('permission:clients.contacts.view')->only(['index', 'show']);
        $this->middleware('permission:clients.contacts.manage')->only(['create', 'store', 'edit', 'update']);
        $this->middleware('permission:clients.contacts.manage')->only(['destroy']);
        $this->middleware('permission:clients.contacts.export')->only(['export']);
    }

    /**
     * Display a listing of contacts for a specific client (session-based client selection)
     */
    public function index(Request $request)
    {
        $client = app(NavigationService::class)->getSelectedClient();

        if (! $client) {
            return redirect()->route('clients.index')->with('error', 'Please select a client first.');
        }

        $this->authorize('view', $client);

        if (! auth()->user()->hasPermission('clients.contacts.view')) {
            abort(403, 'Insufficient permissions to view contacts');
        }

        $query = Contact::where('client_id', $client->id);
        $query = $this->applyContactFilters($query, $request);

        $contacts = $query->orderBy('primary', 'desc')
            ->orderBy('name')
            ->paginate(20)
            ->appends($request->query());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($contacts->items());
        }

        return view('clients.contacts.index', compact('contacts', 'client'));
    }

    /**
     * Show the form for creating a new contact (session-based client selection)
     */
    public function create(Request $request)
    {
        // Get selected client from session
        $client = app(NavigationService::class)->getSelectedClient();

        if (! $client) {
            return redirect()->route('clients.index')->with('error', 'Please select a client first.');
        }

        // Authorize client access
        $this->authorize('view', $client);

        // Additional authorization check
        if (! auth()->user()->hasPermission('clients.contacts.manage')) {
            abort(403, 'Insufficient permissions to create contacts');
        }

        return view('clients.contacts.create', compact('client'));
    }

    /**
     * Store a newly created contact (session-based client selection)
     */
    public function store(Request $request)
    {
        // Get selected client from session
        $client = app(NavigationService::class)->getSelectedClient();

        if (! $client) {
            return redirect()->route('clients.index')->with('error', 'Please select a client first.');
        }

        // Authorize client access
        $this->authorize('view', $client);

        // Additional authorization check
        if (! auth()->user()->hasPermission('clients.contacts.manage')) {
            abort(403, 'Insufficient permissions to create contacts');
        }

        $validator = Validator::make($request->all(), [
            // Basic Information
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'extension' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:50',
            'department' => 'nullable|string|max:255',
            'notes' => 'nullable|string',

            // Contact Types
            'primary' => 'boolean',
            'important' => 'boolean',
            'billing' => 'boolean',
            'technical' => 'boolean',

            // Relationships
            'location_id' => 'nullable|integer|exists:locations,id',
            'vendor_id' => 'nullable|integer|exists:vendors,id',

            // Photo
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $contact = new Contact($request->all());
        $contact->client_id = $client->id;
        $contact->company_id = auth()->user()->company_id;
        $contact->save();

        // If this is set as primary, unset other primary contacts for this client
        if ($contact->primary) {
            Contact::where('client_id', $client->id)
                ->where('id', '!=', $contact->id)
                ->update(['primary' => false]);
        }

        return redirect()->route('clients.contacts.index')
            ->with('success', 'Contact created successfully.');
    }

    /**
     * Display the specified contact (session-based client selection)
     */
    public function show(Contact $contact)
    {
        // Get selected client from session
        $client = app(NavigationService::class)->getSelectedClient();

        if (! $client) {
            return redirect()->route('clients.index')->with('error', 'Please select a client first.');
        }

        // Verify contact belongs to client
        if ($contact->client_id !== $client->id) {
            abort(404, self::CONTACT_NOT_FOUND_MESSAGE);
        }

        // Authorize client access
        $this->authorize('view', $client);
        $this->authorize('view', $contact);

        // Additional permission check
        if (! auth()->user()->hasPermission('clients.contacts.view')) {
            abort(403, 'Insufficient permissions to view contacts');
        }

        $contact->load('client', 'client.addresses', 'location');

        return view('clients.contacts.show', compact('contact', 'client'));
    }

    /**
     * Show the form for editing the specified contact (session-based client selection)
     */
    public function edit(Contact $contact)
    {
        // Get selected client from session
        $client = app(NavigationService::class)->getSelectedClient();

        if (! $client) {
            return redirect()->route('clients.index')->with('error', 'Please select a client first.');
        }

        // Verify contact belongs to client
        if ($contact->client_id !== $client->id) {
            abort(404, self::CONTACT_NOT_FOUND_MESSAGE);
        }

        // Authorize access
        $this->authorize('view', $client);
        $this->authorize('update', $contact);

        // Additional permission check
        if (! auth()->user()->hasPermission('clients.contacts.manage')) {
            abort(403, 'Insufficient permissions to edit contacts');
        }

        return view('clients.contacts.edit', compact('contact', 'client'));
    }

    /**
     * Update the specified contact (session-based client selection)
     */
    public function update(Request $request, Contact $contact)
    {
        // Get selected client from session
        $client = app(NavigationService::class)->getSelectedClient();

        if (! $client) {
            return redirect()->route('clients.index')->with('error', 'Please select a client first.');
        }

        // Verify contact belongs to client
        if ($contact->client_id !== $client->id) {
            abort(404, self::CONTACT_NOT_FOUND_MESSAGE);
        }

        // Authorize access
        $this->authorize('view', $client);
        $this->authorize('update', $contact);

        // Additional permission check
        if (! auth()->user()->hasPermission('clients.contacts.manage')) {
            abort(403, 'Insufficient permissions to update contacts');
        }

        $validator = Validator::make($request->all(), [
            // Basic Information
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'extension' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:50',
            'department' => 'nullable|string|max:255',
            'notes' => 'nullable|string',

            // Contact Types
            'primary' => 'boolean',
            'important' => 'boolean',
            'billing' => 'boolean',
            'technical' => 'boolean',

            // Relationships
            'location_id' => 'nullable|integer|exists:locations,id',
            'vendor_id' => 'nullable|integer|exists:vendors,id',

            // Photo
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

            // Portal Access
            'has_portal_access' => 'boolean',
            'auth_method' => 'nullable|in:none,password,pin',
            'password' => 'nullable|string|min:8|confirmed',
            'pin' => 'nullable|string|min:4|max:10|regex:/^[0-9]+$/',
            'must_change_password' => 'boolean',
            'email_verified_at' => 'nullable|boolean',

            // Portal Permissions
            'portal_permissions' => 'nullable|array',
            'portal_permissions.*' => 'string',

            // Security Settings
            'session_timeout_minutes' => 'nullable|integer|min:5|max:480',
            'allowed_ip_addresses' => 'nullable|array',
            'allowed_ip_addresses.*' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $contact->fill($request->all());
        $contact->save();

        // If this is set as primary, unset other primary contacts for this client
        if ($contact->primary) {
            Contact::where('client_id', $client->id)
                ->where('id', '!=', $contact->id)
                ->update(['primary' => false]);
        }

        return redirect()->route('clients.contacts.index')
            ->with('success', 'Contact updated successfully.');
    }

    /**
     * Remove the specified contact (session-based client selection)
     */
    public function destroy(Contact $contact)
    {
        // Get selected client from session
        $client = app(NavigationService::class)->getSelectedClient();

        if (! $client) {
            return redirect()->route('clients.index')->with('error', 'Please select a client first.');
        }

        // Verify contact belongs to client
        if ($contact->client_id !== $client->id) {
            abort(404, self::CONTACT_NOT_FOUND_MESSAGE);
        }

        // Authorize access
        $this->authorize('view', $client);
        $this->authorize('delete', $contact);

        // Additional permission check
        if (! auth()->user()->hasPermission('clients.contacts.manage')) {
            abort(403, 'Insufficient permissions to delete contacts');
        }

        $contact->delete();

        return redirect()->route('clients.contacts.index')
            ->with('success', 'Contact deleted successfully.');
    }

    /**
     * Export contacts to CSV (session-based client selection)
     */
    public function export(Request $request)
    {
        $client = app(NavigationService::class)->getSelectedClient();

        if (! $client) {
            return redirect()->route('clients.index')->with('error', 'Please select a client first.');
        }

        $this->authorize('view', $client);
        $this->authorizeExportPermissions();

        $query = Contact::where('client_id', $client->id);
        $query = $this->applyContactFilters($query, $request);
        $contacts = $query->orderBy('primary', 'desc')->orderBy('name')->get();

        $filename = 'contacts_'.$client->name.'_'.date('Y-m-d_H-i-s').'.csv';
        $headers = $this->getCsvHeaders($filename);
        $callback = $this->generateCsvCallback($contacts, $client);

        return response()->stream($callback, 200, $headers);
    }



    private function authorizeExportPermissions(): void
    {
        if (! auth()->user()->hasPermission('clients.contacts.export')) {
            abort(403, 'Insufficient permissions to export contact data');
        }

        if (! auth()->user()->can('export-client-data')) {
            abort(403, 'Export permissions denied');
        }
    }

    private function applyContactFilters($query, Request $request)
    {
        if ($search = $request->get('search')) {
            $query = $this->applySearchFilter($query, $search);
        }

        if ($type = $request->get('type')) {
            $query = $this->applyTypeFilter($query, $type);
        }

        return $query;
    }

    private function applySearchFilter($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%")
                ->orWhere('department', 'like', "%{$search}%");
        });
    }

    private function applyTypeFilter($query, string $type)
    {
        $typeMapping = [
            'primary' => 'primary',
            'billing' => 'billing',
            'technical' => 'technical',
            'important' => 'important',
        ];

        if (isset($typeMapping[$type])) {
            $query->where($typeMapping[$type], true);
        }

        return $query;
    }

    private function getCsvHeaders(string $filename): array
    {
        return [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];
    }

    private function generateCsvCallback($contacts, $client): \Closure
    {
        return function () use ($contacts, $client) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Contact Name',
                'Title',
                'Email',
                'Phone',
                'Extension',
                'Mobile',
                'Department',
                'Client Name',
                'Primary',
                'Billing',
                'Technical',
                'Important',
                'Notes',
            ]);

            foreach ($contacts as $contact) {
                fputcsv($file, [
                    $contact->name,
                    $contact->title,
                    $contact->email,
                    $contact->phone,
                    $contact->extension,
                    $contact->mobile,
                    $contact->department,
                    $client->name,
                    $contact->primary ? 'Yes' : 'No',
                    $contact->billing ? 'Yes' : 'No',
                    $contact->technical ? 'Yes' : 'No',
                    $contact->important ? 'Yes' : 'No',
                    $contact->notes,
                ]);
            }

            fclose($file);
        };
    }
}
