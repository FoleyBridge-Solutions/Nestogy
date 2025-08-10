<?php

namespace App\Domains\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
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
     * Display a listing of contacts for a specific client (client-centric view)
     */
    public function index(Request $request, Client $client)
    {
        // Authorize client access
        $this->authorize('view', $client);
        
        // Additional authorization check
        if (!auth()->user()->hasPermission('clients.contacts.view')) {
            abort(403, 'Insufficient permissions to view contacts');
        }

        // Query contacts for the specific client only
        $query = Contact::where('client_id', $client->id);

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        // Apply type filters
        if ($type = $request->get('type')) {
            switch ($type) {
                case 'primary':
                    $query->where('primary', true);
                    break;
                case 'billing':
                    $query->where('billing', true);
                    break;
                case 'technical':
                    $query->where('technical', true);
                    break;
                case 'important':
                    $query->where('important', true);
                    break;
            }
        }

        $contacts = $query->orderBy('primary', 'desc')
                         ->orderBy('name')
                         ->paginate(20)
                         ->appends($request->query());

        return view('clients.contacts.index', compact('contacts', 'client'));
    }

    /**
     * Show the form for creating a new contact for a specific client
     */
    public function create(Request $request, Client $client)
    {
        // Authorize client access
        $this->authorize('view', $client);
        
        // Additional authorization check
        if (!auth()->user()->hasPermission('clients.contacts.manage')) {
            abort(403, 'Insufficient permissions to create contacts');
        }

        return view('clients.contacts.create', compact('client'));
    }

    /**
     * Store a newly created contact for a specific client
     */
    public function store(Request $request, Client $client)
    {
        // Authorize client access
        $this->authorize('view', $client);
        
        // Additional authorization check
        if (!auth()->user()->hasPermission('clients.contacts.manage')) {
            abort(403, 'Insufficient permissions to create contacts');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'extension' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:50',
            'department' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'primary' => 'boolean',
            'important' => 'boolean',
            'billing' => 'boolean',
            'technical' => 'boolean',
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

        return redirect()->route('clients.contacts.index', $client)
                        ->with('success', 'Contact created successfully.');
    }

    /**
     * Display the specified contact for a specific client
     */
    public function show(Client $client, Contact $contact)
    {
        // Verify contact belongs to client
        if ($contact->client_id !== $client->id) {
            abort(404, 'Contact not found for this client');
        }
        
        // Authorize client access
        $this->authorize('view', $client);
        $this->authorize('view', $contact);
        
        // Additional permission check
        if (!auth()->user()->hasPermission('clients.contacts.view')) {
            abort(403, 'Insufficient permissions to view contacts');
        }

        $contact->load('client', 'addresses');

        return view('clients.contacts.show', compact('contact', 'client'));
    }

    /**
     * Show the form for editing the specified contact for a specific client
     */
    public function edit(Client $client, Contact $contact)
    {
        // Verify contact belongs to client
        if ($contact->client_id !== $client->id) {
            abort(404, 'Contact not found for this client');
        }
        
        // Authorize access
        $this->authorize('view', $client);
        $this->authorize('update', $contact);
        
        // Additional permission check
        if (!auth()->user()->hasPermission('clients.contacts.manage')) {
            abort(403, 'Insufficient permissions to edit contacts');
        }

        return view('clients.contacts.edit', compact('contact', 'client'));
    }

    /**
     * Update the specified contact for a specific client
     */
    public function update(Request $request, Client $client, Contact $contact)
    {
        // Verify contact belongs to client
        if ($contact->client_id !== $client->id) {
            abort(404, 'Contact not found for this client');
        }
        
        // Authorize access
        $this->authorize('view', $client);
        $this->authorize('update', $contact);
        
        // Additional permission check
        if (!auth()->user()->hasPermission('clients.contacts.manage')) {
            abort(403, 'Insufficient permissions to update contacts');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'extension' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:50',
            'department' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'primary' => 'boolean',
            'important' => 'boolean',
            'billing' => 'boolean',
            'technical' => 'boolean',
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

        return redirect()->route('clients.contacts.index', $client)
                        ->with('success', 'Contact updated successfully.');
    }

    /**
     * Remove the specified contact for a specific client
     */
    public function destroy(Client $client, Contact $contact)
    {
        // Verify contact belongs to client
        if ($contact->client_id !== $client->id) {
            abort(404, 'Contact not found for this client');
        }
        
        // Authorize access
        $this->authorize('view', $client);
        $this->authorize('delete', $contact);
        
        // Additional permission check
        if (!auth()->user()->hasPermission('clients.contacts.manage')) {
            abort(403, 'Insufficient permissions to delete contacts');
        }

        $contact->delete();

        return redirect()->route('clients.contacts.index', $client)
                        ->with('success', 'Contact deleted successfully.');
    }

    /**
     * Export contacts for a specific client to CSV
     */
    public function export(Request $request, Client $client)
    {
        // Authorize client access
        $this->authorize('view', $client);
        
        // Authorization check for export permission
        if (!auth()->user()->hasPermission('clients.contacts.export')) {
            abort(403, 'Insufficient permissions to export contact data');
        }
        
        // Additional gate check for sensitive data export
        if (!auth()->user()->can('export-client-data')) {
            abort(403, 'Export permissions denied');
        }

        $query = Contact::where('client_id', $client->id);

        // Apply same filters as index
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('type')) {
            switch ($type) {
                case 'primary':
                    $query->where('primary', true);
                    break;
                case 'billing':
                    $query->where('billing', true);
                    break;
                case 'technical':
                    $query->where('technical', true);
                    break;
                case 'important':
                    $query->where('important', true);
                    break;
            }
        }

        $contacts = $query->orderBy('primary', 'desc')->orderBy('name')->get();

        $filename = 'contacts_' . $client->name . '_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($contacts, $client) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
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
                'Notes'
            ]);

            // CSV data
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

        return response()->stream($callback, 200, $headers);
    }
}