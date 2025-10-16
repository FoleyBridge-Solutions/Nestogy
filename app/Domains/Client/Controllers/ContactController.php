<?php

namespace App\Domains\Client\Controllers;

use App\Domains\Client\Services\PortalInvitationService;
use App\Domains\Core\Services\NavigationService;
use App\Http\Controllers\Controller;
use App\Domains\Client\Models\Client;
use App\Domains\Client\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        // SECURITY: Use validated data only to prevent mass assignment vulnerabilities
        $contact = new Contact($request->validated());
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

        // SECURITY: Use validated data only to prevent mass assignment vulnerabilities
        $contact->fill($request->validated());
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

    /**
     * Update portal access settings for a contact
     */
    public function updatePortalAccess(Request $request, Client $client, Contact $contact)
    {
        try {
            // Verify contact belongs to client
            if ($contact->client_id !== $client->id) {
                return response()->json(['error' => self::CONTACT_NOT_FOUND_MESSAGE], 404);
            }

            // Skip policy checks for now and add simple permission check
            if (! auth()->user()->hasPermission('clients.contacts.manage')) {
                return response()->json(['error' => 'Insufficient permissions'], 403);
            }

            // Log the incoming request for debugging
            \Log::info('Portal access update request', [
                'contact_id' => $contact->id,
                'client_id' => $client->id,
                'request_data' => $request->all(),
            ]);

            $validator = Validator::make($request->all(), [
                'has_portal_access' => 'boolean',
                'auth_method' => 'nullable|in:none,password,pin',
                'password' => 'nullable|string|min:8',
                'pin' => 'nullable|string|min:4|max:10|regex:/^[0-9]+$/',
                'must_change_password' => 'boolean',
                'email_verified' => 'boolean',
            ]);

            if ($validator->fails()) {
                \Log::error('Portal access validation failed', ['errors' => $validator->errors()]);

                return response()->json(['errors' => $validator->errors()], 422);
            }

            $contact->has_portal_access = $request->boolean('has_portal_access');

            if ($contact->has_portal_access) {
                $contact->auth_method = $request->input('auth_method', 'none');

                if ($request->filled('password')) {
                    $contact->password_hash = Hash::make($request->input('password'));
                    $contact->password_changed_at = now();
                }

                if ($request->filled('pin') && $contact->auth_method === 'pin') {
                    $contact->pin = Hash::make($request->input('pin'));
                }

                $contact->must_change_password = $request->boolean('must_change_password');
                $contact->email_verified_at = $request->boolean('email_verified') ? now() : null;
            } else {
                $contact->auth_method = 'none';
                $contact->password_hash = null;
                $contact->pin = null;
                $contact->must_change_password = false;
            }

            $contact->save();

            \Log::info('Portal access updated successfully', ['contact_id' => $contact->id]);

            return response()->json([
                'message' => 'Portal access updated successfully',
                'data' => [
                    'has_portal_access' => $contact->has_portal_access,
                    'auth_method' => $contact->auth_method,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating portal access', [
                'contact_id' => $contact->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal server error: '.$e->getMessage()], 500);
        }
    }

    /**
     * Update security settings for a contact
     */
    public function updateSecurity(Request $request, Client $client, Contact $contact)
    {
        // Verify contact belongs to client
        if ($contact->client_id !== $client->id) {
            abort(404, self::CONTACT_NOT_FOUND_MESSAGE);
        }

        // Authorize access
        $this->authorize('view', $client);
        $this->authorize('update', $contact);

        $validator = Validator::make($request->all(), [
            'session_timeout_minutes' => 'nullable|integer|min:5|max:480',
            'allowed_ips' => 'nullable|array',
            'allowed_ips.*' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $contact->session_timeout_minutes = $request->input('session_timeout_minutes', 30);

        $allowedIps = array_filter($request->input('allowed_ips', []));
        $contact->allowed_ip_addresses = $allowedIps;

        $contact->save();

        return response()->json(['message' => 'Security settings updated successfully']);
    }

    /**
     * Update portal permissions for a contact
     */
    public function updatePermissions(Request $request, Client $client, Contact $contact)
    {
        // Verify contact belongs to client
        if ($contact->client_id !== $client->id) {
            abort(404, self::CONTACT_NOT_FOUND_MESSAGE);
        }

        // Authorize access
        $this->authorize('view', $client);
        $this->authorize('update', $contact);

        $validator = Validator::make($request->all(), [
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $permissions = array_filter($request->input('permissions', []));
        $contact->portal_permissions = $permissions;

        $contact->save();

        return response()->json(['message' => 'Permissions updated successfully']);
    }

    /**
     * Lock a contact account
     */
    public function lockAccount(Client $client, Contact $contact)
    {
        // Verify contact belongs to client
        if ($contact->client_id !== $client->id) {
            abort(404, self::CONTACT_NOT_FOUND_MESSAGE);
        }

        // Authorize access
        $this->authorize('view', $client);
        $this->authorize('update', $contact);

        $contact->locked_until = now()->addMinutes(30);
        $contact->save();

        return response()->json(['message' => 'Account locked successfully']);
    }

    /**
     * Unlock a contact account
     */
    public function unlockAccount(Client $client, Contact $contact)
    {
        // Verify contact belongs to client
        if ($contact->client_id !== $client->id) {
            abort(404, self::CONTACT_NOT_FOUND_MESSAGE);
        }

        // Authorize access
        $this->authorize('view', $client);
        $this->authorize('update', $contact);

        $contact->locked_until = null;
        $contact->save();

        return response()->json(['message' => 'Account unlocked successfully']);
    }

    /**
     * Reset failed login attempts
     */
    public function resetFailedAttempts(Client $client, Contact $contact)
    {
        // Verify contact belongs to client
        if ($contact->client_id !== $client->id) {
            abort(404, self::CONTACT_NOT_FOUND_MESSAGE);
        }

        // Authorize access
        $this->authorize('view', $client);
        $this->authorize('update', $contact);

        $contact->failed_login_count = 0;
        $contact->save();

        return response()->json(['message' => 'Failed login attempts reset successfully']);
    }

    /**
     * Send portal invitation to contact
     */
    public function sendInvitation(Request $request, Contact $contact)
    {
        // Get client from navigation service
        $client = app(NavigationService::class)->getSelectedClient();

        if (! $client || $contact->client_id !== $client->id) {
            return response()->json(['error' => self::CONTACT_NOT_FOUND_MESSAGE], 404);
        }

        // Check permissions
        if (! auth()->user()->hasPermission('clients.contacts.manage')) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        // Send invitation
        $invitationService = app(PortalInvitationService::class);
        $result = $invitationService->sendInvitation($contact, auth()->user());

        if (! $result['success']) {
            return response()->json(['error' => $result['message']], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Invitation sent successfully',
            'expires_at' => $result['data']['expires_at'],
        ]);
    }

    /**
     * Resend portal invitation to contact
     */
    public function resendInvitation(Request $request, Contact $contact)
    {
        // Get client from navigation service
        $client = app(NavigationService::class)->getSelectedClient();

        if (! $client || $contact->client_id !== $client->id) {
            return response()->json(['error' => self::CONTACT_NOT_FOUND_MESSAGE], 404);
        }

        // Check permissions
        if (! auth()->user()->hasPermission('clients.contacts.manage')) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        // Resend invitation
        $invitationService = app(PortalInvitationService::class);
        $result = $invitationService->resendInvitation($contact, auth()->user());

        if (! $result['success']) {
            return response()->json(['error' => $result['message']], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Invitation resent successfully',
            'expires_at' => $result['data']['expires_at'],
        ]);
    }

    /**
     * Revoke portal invitation for contact
     */
    public function revokeInvitation(Request $request, Contact $contact)
    {
        // Get client from navigation service
        $client = app(NavigationService::class)->getSelectedClient();

        if (! $client || $contact->client_id !== $client->id) {
            return response()->json(['error' => self::CONTACT_NOT_FOUND_MESSAGE], 404);
        }

        // Check permissions
        if (! auth()->user()->hasPermission('clients.contacts.manage')) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        // Revoke invitation
        $invitationService = app(PortalInvitationService::class);
        $result = $invitationService->revokeInvitation($contact, auth()->user());

        if (! $result['success']) {
            return response()->json(['error' => $result['message']], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Invitation revoked successfully',
        ]);
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
